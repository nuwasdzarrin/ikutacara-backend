<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public static function verification_token(Request $request) {
        return $request->header('X_CALLBACK_TOKEN') && $request->header('X_CALLBACK_TOKEN')==config('payment.xendit.callback_token');
    }
    public function __construct()
    {
//        $this->middleware('auth:api')->except(['index']);
    }

    public function index()
    {
        $payments = Payment::query()->get();
        return (new GeneralResponseCollection($payments, ['Success get event'], true))
            ->response()->setStatusCode(200);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }

    public function callback_va_create(Request $request) {
        if(!self::verification_token($request)) {
            return (new GeneralResponseCollection([], ['Callback token not same'], false))
                ->response()->setStatusCode(400);
        }
        $order = Order::query()->where('uuid', $request['external_id'])->first();
        if (!$order)
            return (new GeneralResponseCollection([], ['Order not found'], false))
                ->response()->setStatusCode(400);
        $order->order_status = 'menunggu pembayaran';
        $order->payment_status = $request['status'];
        $order->save();
        return (new GeneralResponseCollection($order, ['Success update order'], true))
            ->response()->setStatusCode(200);
    }

    public function callback_va_paid(Request $request) {
        if(!self::verification_token($request)) {
            return (new GeneralResponseCollection([], ['Callback token not same'], false))
                ->response()->setStatusCode(400);
        }
        $order = Order::query()->where('uuid', $request['external_id'])->first();
        if (!$order)
            return (new GeneralResponseCollection([], ['Order not found'], false))
                ->response()->setStatusCode(400);
        $order->order_status = 'success';
        $order->payment_status = 'SUCCEEDED';
        $order->save();
        return (new GeneralResponseCollection($order, ['Success update order'], true))
            ->response()->setStatusCode(200);
    }
    public function callback_qrcode_paid(Request $request) {
        if(!self::verification_token($request)) {
            return (new GeneralResponseCollection([], ['Callback token not same'], false))
                ->response()->setStatusCode(400);
        }
        $order = Order::query()->where('uuid', $request['data']['reference_id'])->first();
        if (!$order)
            return (new GeneralResponseCollection([], ['Order not found'], false))
                ->response()->setStatusCode(400);
        $order->order_status = 'success';
        $order->payment_status = $request['data']['status'];
        $order->save();
        return (new GeneralResponseCollection($order, ['Success update order'], true))
            ->response()->setStatusCode(200);
    }
    public function callback_ewallet_paid(Request $request) {
        if(!self::verification_token($request)) {
            return (new GeneralResponseCollection([], ['Callback token not same'], false))
                ->response()->setStatusCode(400);
        }
        $order = Order::query()->where('uuid', $request['data']['reference_id'])->first();
        if (!$order)
            return (new GeneralResponseCollection([], ['Order not found'], false))
                ->response()->setStatusCode(400);
        $order->order_status = 'success';
        $order->payment_status = $request['data']['status'];
        $order->save();
        return (new GeneralResponseCollection($order, ['Success update order'], true))
            ->response()->setStatusCode(200);
    }
}
