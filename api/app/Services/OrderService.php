<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function listAll(User $user): Collection
    {
        $orders = $user->orders()->with('items')->orderByDesc('id')->get();

        return $orders;
    }

    public function findById(User $user, int $id): Order
    {
        $order = $user->orders()->with('items')->findOrFail($id);

        return $order;
    }

    public function create(User $user, int $addressId): Order
    {
        $order = DB::transaction(function () use ($user, $addressId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $address = $user->addresses()->findOrFail($addressId);
            $cart = $user->cart()->with('items')->first();

            if (! $cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'O carrinho está vazio.']);
            }

            // Ordem fixa de bloqueios reduz conflitos entre operações simultâneas.
            $products = Product::whereIn('id', $cart->items->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $total = '0.00';
            $items = [];

            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);
                abort_unless($product, 404);
                $this->cartService->checkAvailability($product, $item->quantity);
                // BCMath calcula dinheiro como texto decimal, sem float.
                $total = bcadd($total, bcmul($product->price, (string) $item->quantity, 2), 2);
                $items[] = ['product_id' => $product->id, 'product_name' => $product->name,
                    'unit_price' => $product->price, 'quantity' => $item->quantity];
            }

            if (bccomp($total, '99999999.99', 2) > 0) {
                throw ValidationException::withMessages(['total' => 'O total ultrapassa o limite do pedido.']);
            }

            $data = ['status' => 'pending_payment', 'total' => $total];
            foreach (['recipient_name', 'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state'] as $field) {
                $data['shipping_'.$field] = $address->{$field};
            }

            $order = $user->orders()->create($data);
            $order->items()->createMany($items);
            $cart->items()->delete();

            $order->load('items');

            return $order;
        });

        return $order;
    }

    public function decide(User $user, int $id, bool $pay): Order
    {
        $order = DB::transaction(function () use ($user, $id, $pay) {
            // Confirmação e cancelamento disputam o mesmo bloqueio do pedido.
            $order = $user->orders()->lockForUpdate()->findOrFail($id);
            if ($order->status !== 'pending_payment') {
                throw ValidationException::withMessages(['status' => 'Este pedido já foi pago ou cancelado.']);
            }

            $order->load('items');
            if ($pay) {
                $products = Product::whereIn('id', $order->items->pluck('product_id'))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($order->items as $item) {
                    $product = $products->get($item->product_id);
                    abort_unless($product, 404);
                    $this->cartService->checkAvailability($product, $item->quantity);
                }

                // Valida todos antes de baixar qualquer item; a transação garante atomicidade.
                foreach ($order->items as $item) {
                    $products[$item->product_id]->decrement('stock', $item->quantity);
                }
            }

            $order->update(['status' => $pay ? 'paid' : 'cancelled']);

            return $order;
        });

        return $order;
    }
}
