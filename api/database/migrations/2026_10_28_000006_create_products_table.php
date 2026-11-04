<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria cada jogo por plataforma e cada console com preço e estoque próprios.
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // Identificador do produto.
            $table->foreignId('platform_id')->constrained()->restrictOnDelete(); // Plataforma obrigatória.
            $table->string('name'); // Nome do jogo ou console.
            $table->text('description'); // Descrição com espaço para textos maiores.
            $table->decimal('price', 10, 2); // Dez dígitos no total, sendo dois decimais.
            $table->string('image_path'); // Caminho da imagem, sem armazenar o arquivo no banco.
            $table->unsignedInteger('stock')->default(0); // Estoque aceita zero, mas não valores negativos.
            $table->boolean('is_active')->default(true); // Permite desativar a oferta sem excluir o cadastro.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // As tabelas dependentes são desfeitas antes, pela ordem inversa das migrations.
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
