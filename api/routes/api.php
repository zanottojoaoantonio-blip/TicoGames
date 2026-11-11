<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\RequireFrontendSession;
use Illuminate\Support\Facades\Route;

Route::get('docs', fn () => response()->view('api-docs'));
Route::get('openapi.yaml', fn () => response()->file(base_path('openapi.yaml'), ['Content-Type' => 'application/yaml']));

Route::middleware([RequireFrontendSession::class, 'throttle:6,1'])->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::apiResource('products', ProductController::class)->only(['index', 'show'])
    ->parameters(['products' => 'id'])->whereNumber('id');
Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy'])
    ->parameters(['products' => 'id'])->whereNumber('id')->middleware(['auth:sanctum', 'admin']);

Route::apiResource('categories', CategoryController::class)->only(['index', 'show'])
    ->parameters(['categories' => 'id'])->whereNumber('id');
Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy'])
    ->parameters(['categories' => 'id'])->whereNumber('id')->middleware(['auth:sanctum', 'admin']);

Route::apiResource('platforms', PlatformController::class)->only(['index', 'show'])
    ->parameters(['platforms' => 'id'])->whereNumber('id');
Route::apiResource('platforms', PlatformController::class)->only(['store', 'update', 'destroy'])
    ->parameters(['platforms' => 'id'])->whereNumber('id')->middleware(['auth:sanctum', 'admin']);

Route::apiResource('genres', GenreController::class)->only(['index', 'show'])
    ->parameters(['genres' => 'id'])->whereNumber('id');
Route::apiResource('genres', GenreController::class)->only(['store', 'update', 'destroy'])
    ->parameters(['genres' => 'id'])->whereNumber('id')->middleware(['auth:sanctum', 'admin']);

Route::middleware(RequireFrontendSession::class)->group(function () {
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::patch('cart/items/{id}', [CartController::class, 'update'])->whereNumber('id');
    Route::delete('cart/items/{id}', [CartController::class, 'destroy'])->whereNumber('id');
    Route::delete('cart', [CartController::class, 'clear']);

});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('cart/merge', [CartController::class, 'merge'])->middleware(RequireFrontendSession::class);
    Route::patch('orders/{id}/status', [OrderController::class, 'status'])->whereNumber('id')->middleware('admin');
    Route::post('product-images', [ProductController::class, 'upload'])->middleware('admin');
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::patch('user', [UserController::class, 'updateMe']);
    Route::delete('user', [UserController::class, 'deleteMe']);
    Route::apiResource('addresses', AddressController::class)->parameters(['addresses' => 'id'])->whereNumber('id');

    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store'])
        ->parameters(['orders' => 'id'])->whereNumber('id');
    Route::post('orders/{id}/pay', [OrderController::class, 'pay'])->whereNumber('id');
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');

    Route::apiResource('users', UserController::class)->parameters(['users' => 'id'])
        ->whereNumber('id')->middleware('admin');
});
