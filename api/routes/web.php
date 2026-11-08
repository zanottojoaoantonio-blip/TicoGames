<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/storage/products/{file}', function (string $file) {
    abort_unless(preg_match('/\A[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)\z/', $file), 404);
    abort_unless(Storage::disk('public')->exists('products/'.$file), 404);

    return Storage::disk('public')->response('products/'.$file, headers: ['X-Content-Type-Options' => 'nosniff']);
});

Route::get('/', function () {
    return view('welcome');
});
