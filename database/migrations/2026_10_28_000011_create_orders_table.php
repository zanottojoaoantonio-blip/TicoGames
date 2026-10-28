<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cria os pedidos e os campos que guardarão a cópia do endereço de entrega.
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Identificador do pedido.
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // Preserva o vínculo com o cliente.
            $table->enum('status', ['pending_payment', 'paid', 'cancelled'])->default('pending_payment');
            $table->decimal('total', 10, 2); // O fluxo de pedidos preencherá o total calculado.
            $table->string('shipping_recipient_name'); // Cópia do destinatário.
            $table->string('shipping_postal_code', 9); // Cópia do CEP, como texto.
            $table->string('shipping_street'); // Cópia da rua.
            $table->string('shipping_number', 20); // Cópia do número, como texto.
            $table->string('shipping_complement')->nullable(); // Complemento opcional.
            $table->string('shipping_neighborhood'); // Cópia do bairro.
            $table->string('shipping_city'); // Cópia da cidade.
            $table->char('shipping_state', 2); // Cópia da sigla do estado.
            $table->timestamps(); // Datas de criação e atualização.
            // Não há address_id: editar ou excluir o endereço salvo não modifica esta cópia.
        });
    }

    // Os itens do pedido são desfeitos antes desta tabela.
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
