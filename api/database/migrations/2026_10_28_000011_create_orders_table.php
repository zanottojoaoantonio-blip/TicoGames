<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'])->default('pending_payment');
            $table->timestamp('paid_at')->nullable();
            $table->decimal('total', 10, 2);
            $table->string('shipping_recipient_name');
            $table->string('shipping_postal_code', 9);
            $table->string('shipping_street');
            $table->string('shipping_number', 20);
            $table->string('shipping_complement')->nullable();
            $table->string('shipping_neighborhood');
            $table->string('shipping_city');
            $table->char('shipping_state', 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
