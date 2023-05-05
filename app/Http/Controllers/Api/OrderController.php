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
//        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $order = Order::query();
        if ($request->filled('active'))
            $order = $order->whereHas('event', function ($query) {
               return $query->whereDate('date->started', '>=', Carbon::now());
            });
        $order = $order->with(['event'])->paginate();
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
        $expires_at = null;
        if ($payment->code === 'QRIS') {
            $params = [
                'external_id' => $reference_uuid,
                'type' => 'DYNAMIC',
                'channel_code' => 'ID_DANA',
                'callback_url' => 'https://webhook.site',
                'expires_at' => now()->addDay(1)->format('c'),
                'amount' => 10000,
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
            $payment_url = $create_payment['qr_string'];
            $expires_at = $create_payment['expires_at'];
        }

        $order = new Order;
        $order->user_id = auth()->user() ? auth()->user()->id : 1;
        $order->uuid = $reference_uuid;

        $order->xendit_payment_id = $create_payment['id'];
        $order->payment_url = $payment_url;
        $order->payment_status = $create_payment['status'];
        $order->expired_at = $expires_at;

        foreach (self::rules()['store'] as $key => $value) {
            if (in_array($key, array_keys(self::rules($request)['hasMany']))) {
                $order->order_quantity = count($request->{$key});
                $order->order_price = array_sum(array_column($request->{$key}, 'ticket_price'));
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
        $order = Order::query()->where('uuid', $uuid)->with(['event', 'order_items'])->first();
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
