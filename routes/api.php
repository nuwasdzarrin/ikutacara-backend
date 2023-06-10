<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth/google', [Api\AuthController::class, 'google_login'])->name('uploader.slug');
Route::get('events/detail/{slug}', [Api\EventController::class, 'slug'])->name('uploader.slug');
Route::apiResource('events', Api\EventController::class, [ 'as' => 'api' ]);
Route::apiResource('orders', Api\OrderController::class, [ 'as' => 'api' ]);

Route::post('payments/callback/va_create', [Api\PaymentController::class, 'callback_va_create'])
    ->name('payments.callback_va_create');
Route::post('payments/callback/va_paid', [Api\PaymentController::class, 'callback_va_paid'])
    ->name('payments.callback_va_paid');
Route::post('payments/callback/qrcode_paid', [Api\PaymentController::class, 'callback_qrcode_paid'])
    ->name('payments.callback_qrcode_paid');
Route::post('payments/callback/ewallet_paid', [Api\PaymentController::class, 'callback_ewallet_paid'])
    ->name('payments.callback_ewallet_paid');
Route::apiResource('payments', Api\PaymentController::class, [ 'as' => 'api' ]);

Route::get('profile', [Api\ProfileController::class, 'index'])->name('profile.index');
Route::put('profile', [Api\ProfileController::class, 'update'])->name('profile.update');

Route::post('wysiwyg_uploader', [Api\UploaderController::class, 'wysiwyg'])->name('uploader.wysiwyg');
Route::post('image_uploader', [Api\UploaderController::class, 'image'])->name('uploader.image');
Route::middleware('auth:sanctum')->post('/avatar_uploader', [Api\UploaderController::class, 'avatar'])
    ->name('uploader.avatar');

Route::get('committee/events', [Api\CommitteeController::class, 'committee_event'])->name('committee_events');
Route::get('committee/events/{event_id}/members', [Api\CommitteeController::class, 'committee_member'])
    ->name('committee_member');
Route::post('committee/add_member', [Api\CommitteeController::class, 'committee_add_member'])->name('committee_add_member');
