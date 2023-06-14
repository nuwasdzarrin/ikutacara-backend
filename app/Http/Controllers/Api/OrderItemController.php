<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Committee;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function isPartEvent($event_id) {
        $auth = auth()->user();
        $is_member = Committee::query()->where('user_id', $auth->id)->where('event_id', $event_id)
            ->first();
        if (!$is_member)
            return (new GeneralResponseCollection([], ['sorry you are not part of this event'], false))
                ->response()->setStatusCode(400);
        return null;
    }

    public function index($event_id, Request $request) {
        $is_part = self::isPartEvent($event_id);
        if ($is_part) return $is_part;
        $order_items = OrderItem::query()->where('event_id', $event_id);
        if ($request->filled('sort')) {
            $sort = explode(",", $request->sort);
            if (count($sort) > 1) {
                $sort_by = $sort[0];
                $direction = $sort[1] == 'desc' ? 'desc' : 'asc';
                if ($sort_by == 'ticket_name') $order_items = $order_items->orderBy('ticket_name', $direction);
                else if ($sort_by == 'ticket_price')
                    $order_items = $order_items->orderBy('ticket_price', $direction);
                else if ($sort_by == 'created_at')
                    $order_items = $order_items->orderBy('created_at', $direction);
            }
        }
        $order_items = $order_items->get();
        return (new GeneralResponseCollection($order_items, ['Success get order item'], true))
            ->response()->setStatusCode(200);
    }

    public function check_in(Request $request) {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'code' => 'required|string|max:10',
        ]);
        $event_id = $request->event_id;
        $code = $request->code;
        $is_part = self::isPartEvent($event_id);
        if ($is_part) return $is_part;
        $order_item = OrderItem::query()->where('event_id', $event_id)->where('ticket_code', $code)
            ->first();
        if (!$order_item)
            return (new GeneralResponseCollection([], ['Ticket code not found'], false))
                ->response()->setStatusCode(400);
        $order_item->ticket_status = OrderItem::STATUS[2];
        $order_item->save();
        return (new GeneralResponseCollection($order_item, ['Successful check in'], true))
            ->response()->setStatusCode(200);
    }
}
