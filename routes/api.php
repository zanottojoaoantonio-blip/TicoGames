<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// O prefixo /api vem de bootstrap/app.php; não é necessário repeti-lo aqui.
// Cria as rotas index, store, show, update e destroy, sem páginas HTML de formulário.
// A autenticação e a restrição ao administrador serão tratadas na etapa 8.
// apiResources() registra as mesmas cinco ações para cada cadastro do catálogo.
Route::apiResources([
    'products' => ProductController::class,
    'categories' => CategoryController::class,
    'platforms' => PlatformController::class,
    'genres' => GenreController::class,
]);
