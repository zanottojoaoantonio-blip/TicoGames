<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os produtos e as quantidades de cada carrinho, sem reservar estoque.
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id(); // Identificador do item.
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete(); // Excluir o carrinho remove seus itens.
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // Produto escolhido no catálogo.
            $table->unsignedInteger('quantity')->default(1); // Valor inteiro sem sinal; mínimo 1 será validado no CRUD.
            $table->timestamps(); // Datas de criação e atualização.
            $table->unique(['cart_id', 'product_id']); // Um produto por linha dentro de cada carrinho.
        });
    }

    // Desfaz a criação da tabela.
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
