<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os endereços salvos, permitindo vários para o mesmo usuário.
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); // Identificador único gerado pelo banco.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Dono do endereço.
            $table->string('recipient_name'); // Nome de quem recebe a entrega.
            $table->string('postal_code', 9); // Texto para preservar zeros e aceitar o hífen.
            $table->string('street'); // Rua ou avenida.
            $table->string('number', 20); // Texto para aceitar 12A ou s/n.
            $table->string('complement')->nullable(); // Informação opcional.
            $table->string('neighborhood'); // Bairro.
            $table->string('city'); // Cidade.
            $table->char('state', 2); // Sigla do estado, como SP.
            $table->timestamps(); // Datas de criação e atualização.
        });
    }

    // Remove a tabela ao desfazer esta migration.
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
