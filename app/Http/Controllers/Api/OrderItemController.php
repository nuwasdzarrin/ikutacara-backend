<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Committee;
use App\Models\OrderItem;

class OrderItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index($event_id) {
        $auth = auth()->user();
        $is_member = Committee::query()->where('user_id', $auth->id)->where('event_id', $event_id)
            ->first();
        if (!$is_member)
            return (new GeneralResponseCollection([], ['sorry you are not part of this event'], false))
                ->response()->setStatusCode(400);
        $order_items = OrderItem::query()->where('event_id', $event_id)->get();
        return (new GeneralResponseCollection($order_items, ['Success get order item'], true))
            ->response()->setStatusCode(200);
    }
}
