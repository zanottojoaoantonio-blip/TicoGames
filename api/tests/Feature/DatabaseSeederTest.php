<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Genre;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\AddressSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    // Prepara um banco de testes; phpunit.xml configura SQLite em memória.
    use RefreshDatabase;

    public function test_seeders_cover_minimum_quantities_and_store_scenarios(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 4);
        $this->assertSame(3, User::where('role', 'customer')->count());
        $this->assertDatabaseCount('products', 30);
        $this->assertDatabaseCount('categories', 3);
        $this->assertSame(0, Product::whereNull('category_id')->count());
        $this->assertTrue(Product::where('stock', 0)->exists());
        $this->assertTrue(Product::where('is_active', false)->exists());
        $this->assertEqualsCanonicalizing(['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'], Order::pluck('status')->all());
        $this->assertTrue(Cart::whereNull('user_id')->whereHas('items')->exists());
        $this->assertTrue(Cart::whereNotNull('user_id')->whereHas('items')->exists());
        $this->assertSame(3, Order::whereNotNull('paid_at')->count());
        $game = Product::where('name', 'Elden Ring')->with('category', 'genres')->firstOrFail();
        $this->assertSame('Jogos', $game->category->name);
        $this->assertEqualsCanonicalizing(['Ação', 'RPG'], $game->genres->pluck('name')->all());
        $this->assertFileExists(public_path($game->image_path));
    }

    public function test_repeating_seeders_preserves_records_and_later_changes(): void
    {
        $this->seed();

        // Simula alterações feitas depois da carga inicial.
        $game = Product::where('name', 'Elden Ring')->firstOrFail();
        $game->update(['stock' => 2, 'price' => '149.90', 'is_active' => false]);
        $extraGenre = Genre::create(['name' => 'Puzzle']);
        $game->genres()->attach($extraGenre->id);
        $customer = User::where('email', 'customer@ticogames.test')->firstOrFail();
        $customer->update(['name' => 'Nome ajustado']);

        // Registra as contagens após as alterações para detectar duplicações na repetição.
        $counts = [];
        foreach (['users', 'addresses', 'categories', 'platforms', 'genres', 'products', 'category_product', 'genre_product', 'orders', 'order_items', 'carts', 'cart_items'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        $this->seed();

        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        // Reexecutar não pode repor estoque, mudar preço, reativar produto ou apagar vínculos.
        $game->refresh();
        $this->assertSame(2, $game->stock);
        $this->assertSame('149.90', $game->price);
        $this->assertFalse($game->is_active);
        $this->assertTrue($game->genres()->whereKey($extraGenre->id)->exists());
        $this->assertSame('Nome ajustado', $customer->fresh()->name);
    }

    public function test_demo_scenarios_preserve_an_existing_customer_cart(): void
    {
        $this->seed([UserSeeder::class, CategorySeeder::class,
            PlatformSeeder::class, GenreSeeder::class,
            ProductSeeder::class, AddressSeeder::class]);
        $service = app(CartService::class);
        $customer = User::where('email', 'customer@ticogames.test')->firstOrFail();
        $product = Product::firstOrFail();
        $cart = $service->add($service->findForUser($customer), ['product_id' => $product->id, 'quantity' => 2]);
        $originalItems = $cart->items()->get()->toArray();

        $this->seed();

        $this->assertSame($originalItems, $cart->items()->get()->toArray());
        $this->assertSame($product->stock, $product->fresh()->stock);
        $this->assertEqualsCanonicalizing(['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'], Order::pluck('status')->all());
        $this->seed();
        $this->assertDatabaseCount('orders', 5);
        $this->assertSame($originalItems, $cart->items()->get()->toArray());
    }
}
