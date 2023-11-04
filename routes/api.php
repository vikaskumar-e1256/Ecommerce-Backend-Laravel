<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\API\Product\ProductController;
use App\Http\Controllers\API\Category\CategoryController;

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

Route::post('signin', [AuthController::class, 'signin']);
Route::post('signup', [AuthController::class, 'signup']);

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('signout', [AuthController::class, 'signout']);
    Route::get('profile', UserProfileController::class);
    Route::get('products/related/{product_id}', [ProductController::class, 'relatedProducts']);
    Route::post('products/by/search', [ProductController::class, 'listBySearch']);
    Route::apiResources([
        'categories' => CategoryController::class,
        'products' => ProductController::class
    ]);


});
