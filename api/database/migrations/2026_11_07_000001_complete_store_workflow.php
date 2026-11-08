<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Não escolher silenciosamente qual categoria preservar em dados existentes.
        if (DB::table('category_product')->select('product_id')->groupBy('product_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Defina uma única categoria por produto antes de executar esta migration.');
        }
        if (DB::table('products')->whereNotIn('id', DB::table('category_product')->select('product_id'))->exists()) {
            throw new RuntimeException('Defina uma categoria para cada produto antes de migrar.');
        }
        $reserved = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'pending_payment')->selectRaw('product_id, SUM(quantity) AS quantity')->groupBy('product_id')->get();
        foreach ($reserved as $item) {
            if (DB::table('products')->where('id', $item->product_id)->value('stock') < $item->quantity) {
                throw new RuntimeException('Estoque insuficiente para reservar pedidos pendentes existentes.');
            }
        }
        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('session_id', 64)->nullable()->unique();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
        });
        foreach (DB::table('category_product')->orderBy('product_id')->get() as $link) {
            DB::table('products')->where('id', $link->product_id)->update(['category_id' => $link->category_id]);
        }
        Schema::table('products', fn (Blueprint $table) => $table->unsignedBigInteger('category_id')->nullable(false)->change());
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'])->default('pending_payment')->change();
            $table->timestamp('paid_at')->nullable();
        });
        // Pedidos antigos pendentes ainda não tinham reservado estoque.
        DB::transaction(function () {
            $quantities = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'pending_payment')->selectRaw('product_id, SUM(quantity) AS quantity')->groupBy('product_id')->orderBy('product_id')->get();
            foreach ($quantities as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();
                if (! $product || $product->stock < $item->quantity) {
                    throw new RuntimeException('Estoque insuficiente para reservar os pedidos pendentes existentes.');
                }
            }
            foreach ($quantities as $item) {
                DB::table('products')->where('id', $item->product_id)->decrement('stock', $item->quantity);
            }
        });
        // A tabela antiga fica como arquivo da migração, sem uso pela aplicação.
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migração preserva reservas e histórico; restaure um backup para reverter.');
    }
};
