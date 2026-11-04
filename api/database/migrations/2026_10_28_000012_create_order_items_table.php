<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os itens que guardarão nome e preço do produto no momento da compra.
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); // Identificador do item.
            $table->foreignId('order_id')->constrained()->restrictOnDelete(); // Impede excluir um pedido com histórico.
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // Impede excluir o produto comprado.
            $table->string('product_name'); // Cópia do nome do produto.
            $table->decimal('unit_price', 10, 2); // Cópia do preço unitário.
            $table->unsignedInteger('quantity')->default(1); // Mínimo 1 será validado no CRUD.
            $table->timestamps(); // Datas de criação e atualização.
            $table->unique(['order_id', 'product_id']); // Uma linha por produto no pedido.
        });
    }

    // Desfaz a criação da tabela antes de remover pedidos e produtos.
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
