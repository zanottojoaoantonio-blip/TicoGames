<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os gêneros usados para classificar e filtrar os jogos.
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id(); // Identificador do gênero.
            $table->string('name')->unique(); // Impede nomes repetidos.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // Desfaz a criação da tabela.
    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
