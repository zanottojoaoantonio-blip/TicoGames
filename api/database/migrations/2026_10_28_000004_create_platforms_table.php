<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria as plataformas, como PS5, Xbox Series e Nintendo Switch.
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id(); // Identificador da plataforma.
            $table->string('name')->unique(); // Impede nomes repetidos.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // Desfaz a criação da tabela.
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
