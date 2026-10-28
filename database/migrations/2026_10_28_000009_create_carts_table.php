<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os carrinhos persistentes das contas; visitantes usam localStorage.
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // Identificador do carrinho.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // Um carrinho por usuário.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // Desfaz a criação da tabela.
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
