<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Liga produtos a várias categorias, sem duplicar o cadastro do produto.
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete(); // Categoria associada.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Produto associado.
            $table->primary(['category_id', 'product_id']); // O mesmo par só pode aparecer uma vez.
            // A chave é composta pelas duas colunas; não são necessários id próprio nem timestamps.
        });
    }

    // Remove somente as associações, sem excluir categorias ou produtos.
    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
