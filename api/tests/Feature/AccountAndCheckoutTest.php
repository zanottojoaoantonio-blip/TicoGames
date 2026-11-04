<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Simula a origem do Vue para ativar a sessão stateful do Sanctum.
        $this->withHeader('Origin', 'http://localhost');
    }

    private function signIn(User $user): void
    {
        // Cada troca de conta simula uma sessão nova, sem reutilizar o guard anterior.
        $this->flushSession();
        Auth::forgetGuards();
        $this->actingAs($user, 'web');
    }

    private function customer(): User
    {
        return User::where('role', 'customer')->firstOrFail();
    }

    private function other(): User
    {
        return User::create(['name' => 'Outra Conta', 'email' => 'other@test.test', 'password' => 'password']);
    }

    private function checkout(User $user, int $quantity = 2): int
    {
        $product = Product::firstOrFail();
        $this->signIn($user);
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => $quantity])->assertOk();

        return $this->postJson('/api/orders', ['address_id' => $user->addresses()->firstOrFail()->id])
            ->assertCreated()->assertJsonPath('data.status', 'pending_payment')->json('data.id');
    }

    public function test_registration_hashes_password_and_cannot_choose_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nova Conta', 'email' => 'new@test.test',
            'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'admin',
        ])->assertCreated()->assertJsonPath('data.role', 'customer')->assertJsonMissingPath('data.password')->assertJsonMissingPath('token');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $user = User::where('email', 'new@test.test')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_registration_and_login_require_the_frontend_session(): void
    {
        $this->withoutHeader('Origin');

        $this->postJson('/api/register', [
            'name' => 'Nova Conta',
            'email' => 'no-session@test.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(419);

        $this->assertDatabaseMissing('users', ['email' => 'no-session@test.test']);

        $this->postJson('/api/login', [
            'email' => $this->customer()->email,
            'password' => 'password',
        ])->assertStatus(419);

        $this->assertGuest('web');
    }

    public function test_login_logout_and_invalid_credentials(): void
    {
        $this->postJson('/api/login', ['email' => $this->customer()->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest('web');
        $this->postJson('/api/login', ['email' => $this->customer()->email, 'password' => 'password'])->assertOk();
        $this->assertAuthenticatedAs($this->customer());
        $this->postJson('/api/logout')->assertNoContent();
        $this->assertGuest('web');
        Auth::forgetGuards();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_registration_rejects_invalid_fields_without_creating_an_account(): void
    {
        $this->postJson('/api/register', [
            'name' => '',
            'email' => $this->customer()->email,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 2);
        $this->assertGuest('web');
    }

    public function test_login_is_limited_to_six_attempts_per_minute(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/login', [
                'email' => $this->customer()->email,
                'password' => 'wrong',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login', [
            'email' => $this->customer()->email,
            'password' => 'wrong',
        ])->assertTooManyRequests();

        $this->assertGuest('web');
    }

    public function test_guests_and_customers_cannot_modify_catalog_or_list_users(): void
    {
        $this->getJson('/api/products')->assertOk();
        $this->postJson('/api/categories', ['name' => 'Sem acesso'])->assertUnauthorized();
        $this->getJson('/api/addresses')->assertUnauthorized();
        $this->signIn($this->customer());
        $this->postJson('/api/categories', ['name' => 'Sem acesso'])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->patchJson('/api/user', ['role' => 'admin', 'name' => 'Cliente'])
            ->assertOk()->assertJsonPath('data.role', 'customer');
    }

    public function test_admin_manages_users_without_exposing_passwords_or_changing_roles(): void
    {
        $this->signIn(User::where('role', 'admin')->firstOrFail());
        $this->getJson('/api/users')->assertOk()->assertJsonMissingPath('data.0.password');
        $id = $this->postJson('/api/users', ['name' => 'Conta', 'email' => 'manage@test.test',
            'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'admin'])
            ->assertCreated()->assertJsonPath('data.role', 'customer')->json('data.id');
        $this->getJson('/api/users/'.$id)->assertOk();
        $this->patchJson('/api/users/'.$id, ['email' => 'manage@test.test', 'name' => 'Renomeado'])->assertOk();
        $this->deleteJson('/api/users/'.$id)->assertNoContent();
        $this->getJson('/api/users/'.$id)->assertNotFound();
    }

    public function test_public_catalog_hides_inactive_products(): void
    {
        $product = Product::firstOrFail();
        $product->update(['is_active' => false]);
        $this->getJson('/api/products')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/products/'.$product->id)->assertNotFound();
        $this->signIn(User::where('role', 'admin')->firstOrFail());
        $this->getJson('/api/products/'.$product->id)->assertOk();
    }

    public function test_addresses_are_scoped_and_ignore_submitted_owner(): void
    {
        $customer = $this->customer();
        $other = $this->other();
        $address = $customer->addresses()->firstOrFail();
        $this->signIn($other);
        $this->getJson('/api/addresses')->assertJsonCount(0, 'data');
        $this->getJson('/api/addresses/'.$address->id)->assertNotFound();
        $this->patchJson('/api/addresses/'.$address->id, ['city' => 'Outra'])->assertNotFound();
        $this->deleteJson('/api/addresses/'.$address->id)->assertNotFound();
        $data = $address->only(['recipient_name', 'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state']);
        $data['user_id'] = $customer->id;
        $id = $this->postJson('/api/addresses', $data)->assertCreated()->json('data.id');
        $this->assertDatabaseHas('addresses', ['id' => $id, 'user_id' => $other->id]);
        $this->patchJson('/api/addresses/'.$id, ['city' => 'Campinas'])->assertOk()->assertJsonPath('data.city', 'Campinas');
        $this->deleteJson('/api/addresses/'.$id)->assertNoContent();
    }

    public function test_cart_merges_quantities_and_persists_through_logout(): void
    {
        $user = $this->customer();
        $product = Product::firstOrFail();
        $this->signIn($user);
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->postJson('/api/cart/merge', ['items' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.quantity', 3);
        $id = $response->json('data.items.0.id');
        $this->patchJson('/api/cart/items/'.$id, ['quantity' => 0])->assertUnprocessable();
        $this->postJson('/api/logout')->assertNoContent();
        $this->signIn($user);
        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.items.0.quantity', 3);
        $this->signIn($this->other());
        $this->patchJson('/api/cart/items/'.$id, ['quantity' => 1])->assertNotFound();
        $this->deleteJson('/api/cart/items/'.$id)->assertNotFound();
        $this->signIn($user);
        $this->patchJson('/api/cart/items/'.$id, ['quantity' => 2])->assertOk()->assertJsonPath('data.items.0.quantity', 2);
        $this->deleteJson('/api/cart/items/'.$id)->assertNoContent();
        $this->getJson('/api/cart')->assertJsonCount(0, 'data.items');
    }

    public function test_invalid_merge_rolls_back_all_items(): void
    {
        $products = Product::take(2)->get();
        $products[1]->update(['is_active' => false]);
        $this->signIn($this->customer());
        $this->postJson('/api/cart/merge', ['items' => [
            ['product_id' => $products[0]->id, 'quantity' => 1],
            ['product_id' => $products[1]->id, 'quantity' => 1],
        ]])->assertUnprocessable();
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_order_copies_history_and_payment_decrements_stock_once(): void
    {
        $user = $this->customer();
        $product = Product::firstOrFail();
        $expectedTotal = bcmul($product->price, '2', 2);
        $id = $this->checkout($user);
        $this->assertSame($product->stock, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
        $product->update(['name' => 'Nome alterado', 'price' => '1.00']);
        $user->addresses()->update(['city' => 'Cidade alterada']);
        $this->getJson('/api/orders/'.$id)->assertOk()
            ->assertJsonPath('data.total', $expectedTotal)
            ->assertJsonPath('data.items.0.product_name', 'Elden Ring')
            ->assertJsonPath('data.shipping_city', 'São Paulo');
        $this->postJson('/api/orders/'.$id.'/pay')->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertSame($product->stock - 2, $product->fresh()->stock);
        $this->postJson('/api/orders/'.$id.'/pay')->assertUnprocessable();
        $this->postJson('/api/orders/'.$id.'/cancel')->assertUnprocessable();
        $this->assertSame($product->stock - 2, $product->fresh()->stock);
        $this->deleteJson('/api/user')->assertUnprocessable();
    }

    public function test_payment_rechecks_stock_and_cancellation_does_not_restock(): void
    {
        $user = $this->customer();
        $product = Product::firstOrFail();
        $id = $this->checkout($user);
        $product->update(['stock' => 1]);
        $this->postJson('/api/orders/'.$id.'/pay')->assertUnprocessable();
        $this->getJson('/api/orders/'.$id)->assertJsonPath('data.status', 'pending_payment');
        $this->assertSame(1, $product->fresh()->stock);
        $this->postJson('/api/orders/'.$id.'/cancel')->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->postJson('/api/orders/'.$id.'/pay')->assertUnprocessable();
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_checkout_requires_own_address_and_orders_are_private(): void
    {
        $customer = $this->customer();
        $id = $this->checkout($customer);
        $this->signIn($this->other());
        $this->getJson('/api/orders')->assertJsonCount(0, 'data');
        $this->getJson('/api/orders/'.$id)->assertNotFound();
        $this->postJson('/api/orders/'.$id.'/pay')->assertNotFound();
        $this->postJson('/api/orders/'.$id.'/cancel')->assertNotFound();
        $this->postJson('/api/orders', ['address_id' => $customer->addresses()->firstOrFail()->id])->assertNotFound();
        $this->signIn($customer);
        $this->postJson('/api/orders', ['address_id' => $customer->addresses()->firstOrFail()->id])->assertUnprocessable();
    }

    public function test_payment_failure_preserves_stock_of_every_item(): void
    {
        $user = $this->customer();
        $products = Product::take(2)->get();
        $this->signIn($user);
        foreach ($products as $product) {
            $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        }
        $id = $this->postJson('/api/orders', ['address_id' => $user->addresses()->firstOrFail()->id])
            ->assertCreated()->json('data.id');
        $products[1]->update(['stock' => 1]);
        $this->postJson('/api/orders/'.$id.'/pay')->assertUnprocessable();
        $this->assertSame($products[0]->stock, $products[0]->fresh()->stock);
        $this->assertSame(1, $products[1]->fresh()->stock);
        $this->getJson('/api/orders/'.$id)->assertJsonPath('data.status', 'pending_payment');
    }

    public function test_checkout_rechecks_active_products_and_preserves_cart_on_failure(): void
    {
        $user = $this->customer();
        $product = Product::firstOrFail();
        $this->signIn($user);
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $product->update(['is_active' => false]);
        $this->postJson('/api/orders', ['address_id' => $user->addresses()->firstOrFail()->id])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_order_total_limit_is_checked_before_creation(): void
    {
        $user = $this->customer();
        $product = Product::firstOrFail();
        $product->update(['price' => '99999999.99']);
        $this->signIn($user);
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->postJson('/api/orders', ['address_id' => $user->addresses()->firstOrFail()->id])
            ->assertUnprocessable()->assertJsonValidationErrors('total');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_account_without_orders_can_be_deleted(): void
    {
        $user = $this->other();
        $this->signIn($user);
        $this->deleteJson('/api/user')->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest('web');
    }
}
