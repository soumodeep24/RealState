<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\ApiAuthController;

Route::middleware('auth:api')->group(function () {
    Route::post('/wishlist/add/{property_id}', [WishlistController::class, 'addToWishlist']);
    Route::get('/wishlist', [WishlistController::class, 'getWishlistProperties']);
    Route::delete('/wishlist/remove/{id}', [WishlistController::class, 'removeWishlistItem']);
});


Route::post('login', [ApiAuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



