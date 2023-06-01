<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public static function rules(Request $request = null, Event $event = null)
    {
        return [
            'hasMany' => [
                'tickets' => [
                    'name' => 'required|string|max:255',
                    'category' => 'required|string|max:255',
                    'desc' => 'string|max:255|nullable',
                    'quota' => 'required|numeric|max:10000',
                    'price' => 'numeric|max:1000000000|nullable',
                    'sale_started' => 'required|string|max:255',
                    'sale_end' => 'required|string|max:255',
                ],
            ],
            'store' => [
                'name' => 'required|string|max:255',
                'banner' => 'required|string|max:255',
                'organizer_name' => 'required|string|max:255',
                'organizer_logo' => 'string|max:255|nullable',
                'date' => 'required|array',
                'location' => 'required|array',
                'description' => 'string|nullable',
                'setting' => 'array|nullable',
            ],
            'update' => [
                'id_bank' => 'exists:ref_banks,id',
                'account_name' => 'string|max:255',
                'account_number' => 'string|max:255',
            ]
        ];
    }

    public function __construct()
    {
//        $this->middleware('auth:api')->except(['index']);
    }

    public function index(Request $request)
    {
        $event = Event::query();
        if ($request->filled('search')) $event = $event->search($request->search);
        $event = $event->with(['tickets'])->paginate()->appends(request()->query());
        return (new GeneralResponseCollection($event, ['Success get event'], true))
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
        foreach (self::rules($request)['hasMany'] as $key => $rule) {
            if ($request->filled($key) && gettype($request->{$key}) == 'array') {
                foreach ($request->{$key} as $req_key => $req_value) {
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

        $event = new Event;
        $event->user_id = auth()->user() ? auth()->user()->id : 1;
        $event->type = array_key_exists('is_online', $request['location']) && $request['location']['is_online'] ? 'online' : 'offline';
        $event->slug = Str::slug($request->name).'-'.random_int(1000, 9999);
        foreach (self::rules($request)['store'] as $key => $value) {
            if (in_array($key, array_keys(self::rules($request)['hasMany']))) continue;
            if (Str::contains($value, [ 'file', 'image', 'mimetypes', 'mimes' ])) {
                if ($request->hasFile($key)) {
                    $event->{$key} = $request->file($key)->store('events');
                } elseif ($request->exists($key)) {
                    $event->{$key} = $request->{$key};
                }
            } elseif ($request->exists($key)) {
                $event->{$key} = $request->{$key};
            }
        }
        try {
            DB::beginTransaction();
            $event->save();
            foreach (self::rules($request)['hasMany'] as $key => $rule) {
                if (!$request->exists($key)) continue;
                $models = [];
                foreach ($request->{$key} as $value) {
                    $model = $event->{$key}()->getRelated();
                    foreach ($rule as $index => $attr) {
                        $model->{$index} = $value[$index];
                    }
                    $models[] = $model;
                }
                $models = $event->{$key}()->saveMany($models);
                $event->setRelation($key, new Collection($models));
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'data' => [],
                'message' => $exception->getMessage()
            ], 400);
        }
        return (new GeneralResponseCollection($event, ['Success create event'], true))
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

    public function slug($slug)
    {
        $event = Event::query()->where('slug', $slug)->with(['tickets'])->first();
        return (new GeneralResponseCollection($event, ['Success get event'], true))
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
