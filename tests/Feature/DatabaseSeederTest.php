<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    // Prepara um banco de testes; phpunit.xml configura SQLite em memória.
    use RefreshDatabase;

    public function test_seeders_create_related_demo_data_without_orders(): void
    {
        // Executa o DatabaseSeeder real, sem factories.
        $this->seed();

        // Confere o conjunto inicial e garante que a compra não foi simulada pelo seeder.
        foreach ([
            'users' => 2, 'addresses' => 2, 'categories' => 3, 'platforms' => 3,
            'genres' => 4, 'products' => 7, 'category_product' => 11, 'genre_product' => 7,
            'carts' => 0, 'cart_items' => 0, 'orders' => 0, 'order_items' => 0,
        ] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        // O mesmo jogo deve existir em plataformas diferentes e com estoque independente.
        $versions = Product::where('name', 'Elden Ring')->with('platform', 'genres', 'categories')->get();
        $this->assertCount(2, $versions);
        $this->assertEqualsCanonicalizing(['PlayStation 5', 'Xbox Series'], $versions->pluck('platform.name')->all());
        $this->assertEqualsCanonicalizing([6, 8], $versions->pluck('stock')->all());

        // Verifica o caso de múltiplas categorias e gêneros e o arquivo de imagem utilizado.
        $game = $versions->firstWhere('platform.name', 'PlayStation 5');
        $this->assertEqualsCanonicalizing(['Jogos', 'Destaques'], $game->categories->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Ação', 'RPG'], $game->genres->pluck('name')->all());
        $this->assertFileExists(public_path($game->image_path));

        // Consoles não recebem gêneros de jogos.
        $consoles = Product::whereHas('categories', fn ($query) => $query->where('name', 'Consoles'))->get();
        $this->assertCount(3, $consoles);
        foreach ($consoles as $console) {
            $this->assertSame(0, $console->genres()->count());
        }

        // As duas opções de endereço pertencem ao cliente, e não ao administrador.
        $customer = User::where('email', 'customer@ticogames.test')->firstOrFail();
        $this->assertSame(2, $customer->addresses()->count());
        $this->assertSame('customer', $customer->role);
        $this->assertDatabaseHas('users', ['email' => 'admin@ticogames.test', 'role' => 'admin']);
        $this->assertDatabaseHas('addresses', ['user_id' => $customer->id, 'number' => '12A', 'complement' => null]);
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
        foreach (['users', 'addresses', 'categories', 'platforms', 'genres', 'products', 'category_product', 'genre_product'] as $table) {
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
}
