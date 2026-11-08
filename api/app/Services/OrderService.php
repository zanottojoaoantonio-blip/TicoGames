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

    public function listAll(User $user, ?string $status = null): Collection
    {
        $orders = Order::with('items')->when($user->role !== 'admin', fn ($query) => $query->where('user_id', $user->id))
            ->when($status, fn ($query) => $query->where('status', $status))->orderByDesc('id')->get();

        return $orders;
    }

    public function findById(User $user, int $id): Order
    {
        $order = Order::with('items')->when($user->role !== 'admin', fn ($query) => $query->where('user_id', $user->id))->findOrFail($id);

        return $order;
    }

    public function create(User $user, int $addressId): Order
    {
        $order = DB::transaction(function () use ($user, $addressId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $address = $user->addresses()->findOrFail($addressId);
            $cart = $user->cart()->with('items')->first();

            if (! $cart || $cart->items->isEmpty()) {
                abort(422, 'O carrinho está vazio.');
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
            foreach ($cart->items as $item) {
                $products[$item->product_id]->decrement('stock', $item->quantity);
            }
            $cart->items()->delete();

            $order->load('items');

            return $order;
        });

        return $order;
    }

    public function decide(User $user, int $id, bool $pay): Order
    {
        return $this->transition($user, $id, $pay ? 'paid' : 'cancelled');
    }

    public function transition(User $user, int $id, string $status): Order
    {
        return DB::transaction(function () use ($user, $id, $status) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($order->user_id === $user->id || $user->role === 'admin', 403, 'Este pedido pertence a outro cliente.');
            if (in_array($status, ['shipped', 'delivered'], true)) {
                abort_unless($user->role === 'admin', 403);
            }
            $allowed = match ($order->status) {
                'pending_payment' => ['paid', 'cancelled'],
                'paid' => $user->role === 'admin' ? ['shipped'] : [],
                'shipped' => ['delivered'],
                default => [],
            };
            abort_unless(in_array($status, $allowed, true), 409, 'Transição de status inválida para este pedido.');
            $order->load('items');
            if ($status === 'cancelled') {
                $products = Product::whereIn('id', $order->items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($order->items as $item) {
                    $product = $products->get($item->product_id);
                    abort_unless($product, 409, 'Produto do pedido indisponível para reposição.');
                    abort_if($product->stock + $item->quantity > 4294967295, 409, 'Reposição excede o limite do estoque.');
                    $product->increment('stock', $item->quantity);
                }
            }
            $data = ['status' => $status];
            if ($status === 'paid') {
                $data['paid_at'] = now();
            }
            $order->update($data);

            return $order;
        });
    }
}
