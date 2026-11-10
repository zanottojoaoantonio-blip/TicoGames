<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

class StoreScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (Cart::where('session_id', hash('sha256', 'demo-guest-cart'))->exists()) {
            return;
        }
        $cartService = app(CartService::class);
        $product = Product::where('name', 'Hades')->where('is_active', true)->where('stock', '>=', 5)->firstOrFail();
        $user = User::whereIn('email', ['customer@ticogames.test', 'ana@ticogames.test', 'bruno@ticogames.test'])
            ->whereDoesntHave('cart.items')->firstOrFail();
        $address = $user->addresses()->firstOrCreate(['street' => 'Rua de Exemplo', 'number' => '12A'], [
            'recipient_name' => 'Cliente Exemplo', 'postal_code' => '00000-000',
            'neighborhood' => 'Bairro de Exemplo', 'city' => 'São Paulo', 'state' => 'SP',
        ]);
        $admin = User::where('role', 'admin')->firstOrFail();
        foreach (['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'] as $status) {
            $cart = $cartService->findForUser($user);
            $cartService->add($cart, ['product_id' => $product->id, 'quantity' => 1]);
            $order = app(OrderService::class)->create($user, $address->id);
            if ($status === 'cancelled') {
                app(OrderService::class)->transition($user, $order->id, 'cancelled');
            } else {
                foreach (['paid', 'shipped', 'delivered'] as $next) {
                    if ($status === 'pending_payment') {
                        break;
                    }
                    app(OrderService::class)->transition($admin, $order->id, $next);
                    if ($next === $status) {
                        break;
                    }
                }
            }
        }
        $guest = $cartService->findForUser(null, 'demo-guest-cart');
        $cartService->add($guest, ['product_id' => $product->id, 'quantity' => 1]);
        $demoUser = User::where('email', 'ana@ticogames.test')->firstOrFail();
        $cart = $cartService->findForUser($demoUser);
        if (! $cart->items()->exists()) {
            $cartService->add($cart, ['product_id' => $product->id, 'quantity' => 1]);
        }
    }
}
