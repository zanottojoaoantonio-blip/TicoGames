<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria as categorias usadas para organizar o catálogo.
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // Identificador da categoria.
            $table->string('name')->unique(); // Impede nomes repetidos.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // Desfaz a criação da tabela.
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
