<?php

namespace App\Http\Controllers\Api\Committees;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Committee;
use App\Models\OrderItem;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function summary($event_id) {
        $committee = Committee::query()->where([
            'user_id' => auth()->user() ? auth()->user()->id : 0,
            'event_id' => $event_id,
        ]);
        $is_owner = $committee->where('committee_rule', 'owner')->first();
        $is_member = $committee->first();
        if (!$is_member)
            return (new GeneralResponseCollection([], ['Sorry you are not member'], false))
                ->response()->setStatusCode(400);
        $tickets = Ticket::query()->select(['name','quota'])->where('event_id', $event_id)->get();
        $order_items = OrderItem::query()->select(['ticket_id', 'ticket_price', 'ticket_name', 'ticket_status'])
            ->where('event_id', $event_id)->get();
        $total_ticket = $tickets->sum('quota');
        $ticket_sold = $order_items->whereIn('ticket_status', ['active', 'entry']);
        $total_sold = $ticket_sold->count();
        $total_waiting = $order_items->where('ticket_status', 'waiting')->count();
        $total_income = $is_owner ? $ticket_sold->sum('ticket_price') : 0;
        $summary = [
          'total_ticket' => $total_ticket,
          'sold' => $total_sold,
          'waiting' => $total_waiting,
          'remaining' => $total_ticket - ($total_sold + $total_waiting),
          'income' => $total_income,
        ];
        $group_name = $order_items->groupBy('ticket_name');
        $detail_summary = [];
        foreach ($group_name as $key_name => $value_name) {
            $total = $tickets->where('name', $key_name)->sum('quota');
            $value_sold = $value_name->whereIn('ticket_status', ['active', 'entry']);
            $sold = $value_sold->count();
            $waiting = $value_name->where('ticket_status', 'waiting')->count();
            $income = $is_owner ? $value_sold->sum('ticket_price') : 0;
            $detail_summary[] = [
                'ticket_name' => $key_name,
                'summary' => [
                    'total_ticket' => $total,
                    'sold' => $sold,
                    'waiting' => $waiting,
                    'remaining' => $total - ($sold + $waiting),
                    'income' => $income,
                ]
            ];
        }
        return (new GeneralResponseCollection([
            'summary' => $summary,
            'detail_summary' => $detail_summary
        ], ['Success get summary'], true))->response()->setStatusCode(200);
    }
}
