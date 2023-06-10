<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Xendit\Xendit;

class OrderController extends Controller
{
    private $api_key;
    public static function rules()
    {
        return [
            'hasMany' => [
                'order_items' => [
                    'ticket_id' => 'required|numeric|exists:tickets,id',
                    'ticket_price' => 'required|numeric',
                    'ticket_name' => 'required|string|max:255',
                    'attendee' => 'required|array',
                ],
            ],
            'store' => [
                'event_id' => 'required|numeric|exists:events,id',
                'payment_id' => 'required|numeric|exists:payments,id',
                'order_user_name' => 'required|string|max:255',
                'order_user_email' => 'required|string|max:255',
                'order_user_whatsapp' => 'required|string|max:20',
                'order_items' => 'required|array',
            ],
            'update' => [
                'event_id' => 'numeric|exists:events,id',
                'order_user_name' => 'string|max:255',
                'order_user_email' => 'string|max:255',
                'order_user_whatsapp' => 'string|max:20',
                'order_items' => 'array',
            ]
        ];
    }

    public function __construct()
    {
        $this->api_key = config('payment.xendit.api_key');
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $order = Order::query();
        if ($request->filled('active'))
            $order = $order->whereHas('event', function ($query) {
               return $query->whereDate('date->started', '>=', Carbon::now());
            });
        $order = $order->orderBy('id', 'desc')->with(['event', 'order_items'])->paginate();
        return (new GeneralResponseCollection($order, ['Success get order'], true))
            ->response()->setStatusCode(200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validator      = Validator::make($request->all(), self::rules($request)['store']);
        $errors         = array_values($validator->errors()->all());
        foreach (self::rules()['hasMany'] as $key => $rule) {
            if ($request->filled($key) && gettype($request->{$key}) == 'array') {
                foreach ($request->{$key} as $req_value) {
                    $validator = Validator::make($req_value, $rule);
                    $errors = array_merge($errors, array_values($validator->errors()->all()));
                }
            }
        }
        if ($errors) {
            return (new GeneralResponseCollection([], $errors, false))
                ->response()->setStatusCode(400);
        }
        $payment = Payment::query()->findOrFail($request->payment_id);
        if (!$payment)
            return (new GeneralResponseCollection([], ['Payment not found'], false))
                ->response()->setStatusCode(400);

        $reference_uuid = 'order_'.Str::uuid();
        Xendit::setApiKey($this->api_key);
        $create_payment = [];
        $payment_url = null;
        $payment_account_name = null;
        $item_price = array_sum(array_column($request->order_items, 'ticket_price'));
        $admin_fee = $payment['cost_type'] == 'percent' ? ($payment['cost_value']/100) * $item_price : $payment['cost_value'];
        $expires_at = now()->addDay(1);
        if (in_array($payment->code, ['ID_DANA','ID_OVO'])) {
            $params = [
                'reference_id' => $reference_uuid,
                'type' => 'DYNAMIC',
                'channel_code' => $payment->code,
                'checkout_method' => 'ONE_TIME_PAYMENT',
                'channel_properties' => [
                    'success_redirect_url' => 'https://yourwebsite.com/order/123',
                ],
                'currency' => 'IDR',
                'amount' => $item_price + $admin_fee,
                'metadata' => [
                    'reference_id' => $reference_uuid,
                    'event_id' => $request->event_id,
                    'order_user_name' => $request->order_user_name,
                    'order_user_email' => $request->order_user_email,
                    'order_user_whatsapp' => $request->order_user_whatsapp
                ]
            ];
            $create_payment = \Xendit\EWallets::createEWalletCharge($params);
            if (!$create_payment)
                return (new GeneralResponseCollection([], ['Create payment fail'], false))
                    ->response()->setStatusCode(400);
            $payment_url = $create_payment['actions']['mobile_web_checkout_url'] ?? null;
        }
        else if (in_array($payment->code, ['MANDIRI','BNI'])) {
            $params = [
                'external_id' => $reference_uuid,
                "bank_code" => $payment->code,
                "name" => "PT Kreatora Teknologi Indonesia",
                "is_single_use" => true,
                "is_closed" => true,
                "expected_amount" => $item_price + $admin_fee
            ];
            $create_payment = \Xendit\VirtualAccounts::create($params);
            if (!$create_payment)
                return (new GeneralResponseCollection([], ['Create payment fail'], false))
                    ->response()->setStatusCode(400);
            $payment_url = $create_payment['account_number'] ?? null;
            $payment_account_name = $create_payment['name'] ?? null;
        }
        else if ($payment->code === 'QRIS') {
            $params = [
                'external_id' => $reference_uuid,
                'type' => 'DYNAMIC',
                'channel_code' => 'ID_DANA',
                'amount' => $item_price + $admin_fee,
                'callback_url' => config('payment.xendit.callback_base_url').'/api/payments/callback/qrcode_paid',
                'metadata' => [
                    'reference_id' => $reference_uuid,
                    'event_id' => $request->event_id,
                    'order_user_name' => $request->order_user_name,
                    'order_user_email' => $request->order_user_email,
                    'order_user_whatsapp' => $request->order_user_whatsapp
                ]
            ];
            $create_payment = \Xendit\QRCode::create($params);
            if (!$create_payment)
                return (new GeneralResponseCollection([], ['Create payment fail'], false))
                    ->response()->setStatusCode(400);
            $payment_url = $create_payment['qr_string'] ?? null;
        }

        $order = new Order;
        $order->user_id = auth()->user() ? auth()->user()->id : 0;
        $order->uuid = $reference_uuid;

        $order->xendit_payment_id = $create_payment['id'];
        $order->order_price = $item_price;
        $order->admin_fee = $admin_fee;
        $order->payment_url = $payment_url;
        $order->order_status = in_array($payment->code, ['MANDIRI','BNI']) ? "idle" : "menunggu pembayaran";
        $order->payment_status = $create_payment['status'];
        $order->payment_account_name = $payment_account_name;
        $order->expired_at = $expires_at;

        foreach (self::rules()['store'] as $key => $value) {
            if (in_array($key, array_keys(self::rules($request)['hasMany']))) {
                $order->order_quantity = count($request->{$key});
                continue;
            }
            if (Str::contains($value, [ 'file', 'image', 'mimetypes', 'mimes' ])) {
                if ($request->hasFile($key)) {
                    $order->{$key} = $request->file($key)->store('bank_accounts');
                } elseif ($request->exists($key)) {
                    $order->{$key} = $request->{$key};
                }
            } elseif ($request->exists($key)) {
                $order->{$key} = $request->{$key};
            }
        }
        try {
            DB::beginTransaction();
            $order->save();
            foreach (self::rules()['hasMany'] as $key => $rule) {
                if (!$request->exists($key)) continue;
                $models = [];
                foreach ($request->{$key} as $value) {
                    $model = $order->{$key}()->getRelated();
                    foreach ($rule as $index => $attr) {
                        $model->{$index} = $value[$index];
                    }
                    $model['ticket_code'] = strtoupper(Str::random(5)).$order->getKey();
                    $models[] = $model;
                }
                $models = $order->{$key}()->saveMany($models);
                $order->setRelation($key, new Collection($models));
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return (new GeneralResponseCollection([], $exception->getMessage(), false))
                ->response()->setStatusCode(400);
        }
        return (new GeneralResponseCollection($order, ['Success create order'], true))
            ->response()->setStatusCode(201);
    }

    public function show($uuid)
    {
        $order = Order::query()->where('uuid', $uuid)
            ->with(['event', 'order_items', 'payment_instructions'])->first();
        return (new GeneralResponseCollection($order, ['Success get detail order'], true))
            ->response()->setStatusCode(200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
