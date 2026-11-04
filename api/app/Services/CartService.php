<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function findForUser(User $user): Cart
    {
        $cart = DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cart = $user->cart()->firstOrCreate([]);

            $cart->load('items.product');

            return $cart;
        });

        return $cart;
    }

    public function add(User $user, array $items): Cart
    {
        // A linha do usuário serializa mudanças no carrinho, inclusive sua criação.
        $cart = DB::transaction(function () use ($user, $items) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cart = $user->cart()->firstOrCreate([]);

            foreach ($items as $data) {
                $product = Product::findOrFail($data['product_id']);
                $item = $cart->items()->where('product_id', $product->id)->first();
                $quantity = ($item?->quantity ?? 0) + $data['quantity'];
                $this->checkAvailability($product, $quantity);

                $cart->items()->updateOrCreate(['product_id' => $product->id], ['quantity' => $quantity]);
            }

            $cart->load('items.product');

            return $cart;
        });

        return $cart;
    }

    public function update(User $user, int $id, int $quantity): Cart
    {
        $cart = DB::transaction(function () use ($user, $id, $quantity) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cart = $user->cart()->firstOrFail();
            $item = $cart->items()->with('product')->findOrFail($id);
            $this->checkAvailability($item->product, $quantity);
            $item->update(['quantity' => $quantity]);

            $cart->load('items.product');

            return $cart;
        });

        return $cart;
    }

    public function delete(User $user, ?int $id = null): void
    {
        DB::transaction(function () use ($user, $id) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cart = $user->cart()->first();

            if ($id !== null) {
                abort_unless($cart, 404);
                $cart->items()->findOrFail($id)->delete();
            } elseif ($cart) {
                $cart->items()->delete();
            }
        });
    }

    public function checkAvailability(Product $product, int $quantity): void
    {
        if (! $product->is_active || $quantity < 1 || $quantity > $product->stock) {
            throw ValidationException::withMessages(['items' => 'Produto inativo ou quantidade indisponível: '.$product->name]);
        }
    }
}
