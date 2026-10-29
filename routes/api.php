<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// O prefixo /api vem de bootstrap/app.php; não é necessário repeti-lo aqui.
// Cria as rotas index, store, show, update e destroy, sem páginas HTML de formulário.
// A autenticação e a restrição ao administrador serão tratadas na etapa 8.
// Cada recurso registra cinco ações; {id} é recebido como inteiro pelo Controller.
Route::apiResource('products', ProductController::class)->parameters(['products' => 'id']);
Route::apiResource('categories', CategoryController::class)->parameters(['categories' => 'id']);
Route::apiResource('platforms', PlatformController::class)->parameters(['platforms' => 'id']);
Route::apiResource('genres', GenreController::class)->parameters(['genres' => 'id']);
