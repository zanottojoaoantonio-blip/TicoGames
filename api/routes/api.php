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

foreach (['products' => ProductController::class, 'categories' => CategoryController::class,
    'platforms' => PlatformController::class, 'genres' => GenreController::class] as $resource => $controller) {
    Route::apiResource($resource, $controller)->only(['index', 'show'])
        ->parameters([$resource => 'id'])->whereNumber('id');
    Route::apiResource($resource, $controller)->only(['store', 'update', 'destroy'])
        ->parameters([$resource => 'id'])->whereNumber('id')->middleware(['auth:sanctum', 'admin']);
}

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
