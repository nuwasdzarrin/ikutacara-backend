<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public static function rules()
    {
        return [
            'hasMany' => [
                'order_items' => [
                    'ticket_id' => 'required|numeric|exists:tickets,id',
                    'ticket_price' => 'required|numeric',
                    'attendee' => 'required|array',
                ],
            ],
            'store' => [
                'event_id' => 'required|numeric|exists:events,id',
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
            return response()->json([
                'data' => [],
                'message' => $errors
            ], 400);
        }
        $order = new Order;
        $order->user_id = auth()->user() ? auth()->user()->id : 1;
        foreach (self::rules()['store'] as $key => $value) {
            if (in_array($key, array_keys(self::rules($request)['hasMany']))) continue;
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
            return response()->json([
                'data' => [],
                'message' => $exception->getMessage()
            ], 400);
        }
        return (new GeneralResponseCollection($order, ['Success create order'], true))
            ->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
