<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function findForUser(?User $user, ?string $sessionId = null): Cart
    {
        return DB::transaction(function () use ($user, $sessionId) {
            if ($user) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $cart = $user->cart()->firstOrCreate([]);
            } else {
                abort_unless($sessionId, 419);
                $cart = Cart::firstOrCreate(['session_id' => hash('sha256', $sessionId)], ['user_id' => null]);
            }

            return $this->totals($cart);
        });
    }

    private function lock(Cart $cart): Cart
    {
        if ($cart->user_id) {
            User::whereKey($cart->user_id)->lockForUpdate()->firstOrFail();
        }

        return Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
    }

    public function add(Cart $cart, array $data): Cart
    {
        return DB::transaction(function () use ($cart, $data) {
            $cart = $this->lock($cart);
            $product = Product::findOrFail($data['product_id']);
            $item = $cart->items()->where('product_id', $product->id)->first();
            $quantity = ($item?->quantity ?? 0) + $data['quantity'];
            $this->checkAvailability($product, $quantity);
            abort_if(! $item && $cart->items()->count() >= 100, 409, 'O carrinho aceita até 100 produtos diferentes.');
            $cart->items()->updateOrCreate(['product_id' => $product->id], ['quantity' => $quantity]);

            return $this->totals($cart);
        });
    }

    public function update(Cart $cart, int $id, int $quantity): Cart
    {
        return DB::transaction(function () use ($cart, $id, $quantity) {
            $cart = $this->lock($cart);
            $item = $cart->items()->with('product')->findOrFail($id);
            if ($quantity === 0) {
                $item->delete();
            } else {
                $this->checkAvailability($item->product, $quantity);
                $item->update(['quantity' => $quantity]);
            }

            return $this->totals($cart);
        });
    }

    public function delete(Cart $cart, ?int $id = null): void
    {
        DB::transaction(function () use ($cart, $id) {
            $cart = $this->lock($cart);
            if ($id !== null) {
                $cart->items()->findOrFail($id)->delete();
            } else {
                $cart->items()->delete();
            }
        });
    }

    public function merge(User $user, ?string $sessionId): Cart
    {
        return DB::transaction(function () use ($user, $sessionId) {
            $cart = $this->lock($this->findForUser($user));
            $guest = $sessionId ? Cart::whereNull('user_id')->where('session_id', hash('sha256', $sessionId))->lockForUpdate()->first() : null;
            $warnings = [];
            if ($guest) {
                $products = Product::whereIn('id', $guest->items()->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($guest->items as $item) {
                    $product = $products->get($item->product_id);
                    $existing = $cart->items()->where('product_id', $item->product_id)->first();
                    $wanted = ($existing?->quantity ?? 0) + $item->quantity;
                    $quantity = $product?->is_active ? min($wanted, $product->stock) : 0;
                    if ($quantity !== $wanted) {
                        $warnings[] = ($product?->name ?? 'Produto indisponível').': quantidade ajustada para '.$quantity.'.';
                    }
                    if ($quantity > 0) {
                        abort_if(! $existing && $cart->items()->count() >= 100, 409, 'O carrinho aceita até 100 produtos diferentes.');
                        $cart->items()->updateOrCreate(['product_id' => $item->product_id], ['quantity' => $quantity]);
                    } elseif ($existing) {
                        $existing->delete();
                    }
                }
                $guest->delete();
            }
            $cart = $this->totals($cart);
            $cart->setAttribute('warnings', $warnings);

            return $cart;
        });
    }

    public function totals(Cart $cart): Cart
    {
        $cart->load('items.product');
        $subtotal = '0.00';
        foreach ($cart->items as $item) {
            $item->setAttribute('subtotal', bcmul($item->product->price, (string) $item->quantity, 2));
            $subtotal = bcadd($subtotal, $item->getAttribute('subtotal'), 2);
        }
        $cart->setAttribute('subtotal', $subtotal);
        $cart->setAttribute('shipping_cost', '0.00');
        $cart->setAttribute('total', $subtotal);

        return $cart;
    }

    public function checkAvailability(Product $product, int $quantity): void
    {
        abort_if(! $product->is_active || $quantity < 1 || $quantity > $product->stock || $quantity > 4294967295,
            409, 'Produto inativo ou quantidade indisponível: '.$product->name);
    }
}
