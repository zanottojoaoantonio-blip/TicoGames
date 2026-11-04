<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Liga os jogos aos seus gêneros; um console pode ficar sem associações.
    public function up(): void
    {
        Schema::create('genre_product', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete(); // Gênero associado.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Produto associado.
            $table->primary(['genre_id', 'product_id']); // Impede repetir a mesma associação.
            // Assim como category_product, esta tabela não usa id próprio nem timestamps.
        });
    }

    // Remove somente a tabela de associações.
    public function down(): void
    {
        Schema::dropIfExists('genre_product');
    }
};
