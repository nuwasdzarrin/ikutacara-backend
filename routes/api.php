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

Route::get('events/detail/{slug}', [Api\EventController::class, 'slug'])->name('uploader.slug');
Route::apiResource('events', Api\EventController::class, [ 'as' => 'api' ]);
//Route::apiResource('tickets', 'Api\TicketController', [ 'as' => 'api' ]);
//Route::apiResource('book_users', 'Api\BookUserController', [ 'as' => 'api' ]);
//Route::apiResource('book_tickets', 'Api\BookTicketController', [ 'as' => 'api' ]);

Route::post('/wysiwyg_uploader', [Api\UploaderController::class, 'wysiwyg'])->name('uploader.wysiwyg');
Route::post('/image_uploader', [Api\UploaderController::class, 'image'])->name('uploader.image');
