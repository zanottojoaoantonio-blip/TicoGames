<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os carrinhos persistentes de contas e visitantes.
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // Identificador do carrinho.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete(); // Um carrinho por usuário.
            $table->string('session_id', 64)->nullable()->unique(); // Hash da sessão do visitante.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // Desfaz a criação da tabela.
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
