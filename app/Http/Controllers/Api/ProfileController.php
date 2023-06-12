<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResponseCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public static function rules(Request $request = null)
    {
        return [
            'store' => [
                'name' => 'required|string|max:255',
            ],
            'update' => [
                'name' => 'string|max:255|nullable'
            ]
        ];
    }
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index()
    {
        $profile = auth()->user();
        return (new GeneralResponseCollection($profile, ['Success get profile'], true))
            ->response()->setStatusCode(200);
    }

    public function search_user(Request $request)
    {
        $email = $request->email;
        if (!$email)
            return (new GeneralResponseCollection([], ['Fail search'], false))
                ->response()->setStatusCode(200);
        $profile = User::query()->where('email', $email)->first();
        if (!$profile)
            return (new GeneralResponseCollection([], ['Fail search'], false))
                ->response()->setStatusCode(200);
        return (new GeneralResponseCollection($profile, ['Success get user'], true))
            ->response()->setStatusCode(200);
    }

    public function update(Request $request)
    {
        $validator      = Validator::make($request->all(), self::rules($request)['update']);
        $errors         = array_values($validator->errors()->all());
        if ($errors) {
            return response()->json([
                'data' => [],
                'message' => $errors
            ], 400);
        }

        $user_id = auth()->user()->id;
        $profile = User::query()->findOrFail($user_id);
        if (!$profile) {
            return response()->json([
                'data' => [],
                'message' => ['user not found']
            ], 400);
        }
        foreach (self::rules($request)['update'] as $key => $value) {
            if (Str::contains($value, [ 'file', 'image', 'mimetypes', 'mimes' ])) {
                if ($request->hasFile($key)) {
                    $profile->{$key} = $request->file($key)->store('bank_accounts');
                } elseif ($request->exists($key)) {
                    $profile->{$key} = $request->{$key};
                }
            } elseif ($request->exists($key)) {
                $profile->{$key} = $request->{$key};
            }
        }
        $profile->save();
        return (new GeneralResponseCollection($profile, ['Success update profile'], true))
            ->response()->setStatusCode(200);
    }
}
