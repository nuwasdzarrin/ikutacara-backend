<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Committee;
use App\Models\Event;
use Illuminate\Http\Request;

class CommitteeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'slug']);
    }
    public function committee_event()
    {
        $events = Event::query()->whereHas('committees', function ($query) {
            return $query->where('user_id', auth()->user() ? auth()->user()->id : 0);
        })->orderBy('id', 'desc')->get();
        return (new GeneralResponseCollection($events->append('committee_rule'), ['Success get event'], true))
            ->response()->setStatusCode(200);
    }
    public function committee_member($event_id) {
        $member = Committee::query()->where('event_id', $event_id)->with('user')->get();
        return (new GeneralResponseCollection($member, ['Success get members'], true))
            ->response()->setStatusCode(200);
    }

    public function committee_member_delete($event_id, $id) {
        $committee = Committee::query()->findOrFail($id);
        if (!$committee) {
            return (new GeneralResponseCollection([], ['Member not found'], true))
                ->response()->setStatusCode(400);
        }
        $committee->delete();
        $member = Committee::query()->where('event_id', $event_id)->with('user')->get();
        return (new GeneralResponseCollection($member, ['Success deleted'], true))
            ->response()->setStatusCode(200);
    }

    public function committee_add_member(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $owner = Committee::query()->where([
            'user_id' => auth()->user() ? auth()->user()->id : 0,
            'event_id' => $request->event_id,
            'committee_rule' => 'owner',
        ])->first();
        if (!$owner)
            return (new GeneralResponseCollection([], ['Sorry you are not owner'], true))
                ->response()->setStatusCode(400);
        $committee_exist = Committee::query()->where('user_id', $request->user_id)->first();
        if (!$committee_exist) {
            $committee = new Committee;
            $committee->user_id = $request->user_id;
            $committee->event_id = $request->event_id;
            $committee->committee_rule = Committee::COMMITTEE_RULES[1];
            $committee->save();
        }
        $member = Committee::query()->where('event_id', $request->event_id)->with('user')->get();
        return (new GeneralResponseCollection($member, ['Success add committe'], true))
            ->response()->setStatusCode(200);
    }
}
