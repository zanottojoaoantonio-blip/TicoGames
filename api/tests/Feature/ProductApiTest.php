<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Platform;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\AddressSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    // Cada teste usa a estrutura em SQLite em memória, sem apagar o MySQL do projeto.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reutiliza os dados de demonstração; não utiliza factories.
        $this->seed([UserSeeder::class, CategorySeeder::class, PlatformSeeder::class, GenreSeeder::class, ProductSeeder::class, AddressSeeder::class]);
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
    }

    public function test_products_can_be_listed_with_their_relationships(): void
    {
        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(12, 'data')->assertJsonPath('meta.total', 30)
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'price', 'stock', 'is_active', 'platform', 'categories', 'genres', 'created_at', 'updated_at']],
            ]);
    }

    public function test_a_product_can_be_found_by_its_id(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.price', $product->price)
            ->assertJsonPath('data.platform.id', $product->platform_id)
            ->assertJsonCount(2, 'data.genres')
            // O Resource apresenta as classificações sem expor os vínculos internos.
            ->assertJsonMissingPath('data.categories.0.pivot')
            ->assertJsonMissingPath('data.genres.0.pivot');
    }

    public function test_a_product_can_be_created_with_categories_and_genres(): void
    {
        $data = $this->productData();

        // Não envia is_active: a resposta deve incluir o padrão true do banco.
        $this->postJson('/api/products', $data)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Jogo de Teste')
            ->assertJsonPath('data.price', '199.90')
            ->assertJsonPath('data.stock', 12)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(2, 'data.genres');

        $product = Product::where('name', 'Jogo de Teste')->firstOrFail();
        $this->assertDatabaseCount('products', 31);
        $this->assertSame($data['category_id'], $product->category_id);
        $this->assertEqualsCanonicalizing($data['genre_ids'], $product->genres()->pluck('genres.id')->all());
    }

    public function test_a_console_can_be_created_without_genres(): void
    {
        $data = $this->productData();
        $data['category_id'] = Category::where('name', 'Consoles')->firstOrFail()->id;
        unset($data['genre_ids']);

        $this->postJson('/api/products', $data)
            ->assertCreated()
            ->assertJsonCount(0, 'data.genres');
    }

    public function test_partial_updates_preserve_omitted_fields_and_relationships(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $categories = $product->category_id;

        $this->patchJson('/api/products/'.$product->id, ['stock' => 3])
            ->assertOk()
            ->assertJsonPath('data.stock', 3)
            ->assertJsonPath('data.price', $product->price)
            ->assertJsonCount(2, 'data.genres');

        $this->assertSame($categories, $product->category_id);
    }

    public function test_updates_replace_provided_relationships_and_can_clear_genres(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $category = Category::where('name', 'Jogos')->firstOrFail();
        $platform = Platform::where('name', 'Xbox Series')->firstOrFail();

        $this->putJson('/api/products/'.$product->id, [
            'price' => '149.90',
            'platform_id' => $platform->id,
            'category_id' => $category->id,
            'genre_ids' => [],
        ])->assertOk()
            ->assertJsonPath('data.price', '149.90')
            ->assertJsonPath('data.platform.id', $platform->id)
            ->assertJsonPath('data.categories.0.id', $category->id)
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(0, 'data.genres');
    }

    public function test_deleting_deactivates_the_product_without_removing_its_links(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $categoryCount = $product->category_id;

        $this->deleteJson('/api/products/'.$product->id)->assertNoContent();

        $this->assertDatabaseCount('products', 30);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertSame($categoryCount, $product->category_id);

        // Esta API administrativa inicial permite conferir e reativar cadastros inativos.
        $this->getJson('/api/products/'.$product->id)->assertOk()->assertJsonPath('data.is_active', false);
        $this->patchJson('/api/products/'.$product->id, ['is_active' => true])
            ->assertOk()->assertJsonPath('data.is_active', true);
    }

    public function test_invalid_input_is_rejected_without_inserting_a_product(): void
    {
        // O FormRequest impede chegar ao Service quando faltam os dados obrigatórios.
        $this->postJson('/api/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'image_path', 'platform_id', 'price', 'stock', 'category_id']);

        $data = $this->productData();
        $data['name'] = '';
        $data['platform_id'] = 999999;
        $data['price'] = '12.345';
        $data['stock'] = -1;
        $data['category_id'] = [];
        $data['genre_ids'] = [999999];

        $this->postJson('/api/products', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'platform_id', 'price', 'stock', 'category_id', 'genre_ids.0']);

        $this->assertDatabaseCount('products', 30);

        // Categorias repetidas também não podem gerar vínculos duplicados.
        $data = $this->productData();
        $data['category_id'] = [$data['category_id'], $data['category_id']];
        $this->postJson('/api/products', $data)->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('products', 30);
    }

    public function test_invalid_updates_preserve_the_product_and_its_relationships(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $initialStock = $product->stock;
        $initialPrice = $product->price;
        $categoryIds = $product->category_id;
        $genreIds = $product->genres()->pluck('genres.id')->all();

        // Cada envio inválido deve produzir 422 sem alterar o cadastro existente.
        foreach ([
            [['stock' => -1], 'stock'],
            [['stock' => 1.5], 'stock'],
            [['stock' => 4294967296], 'stock'],
            [['price' => '-0.01'], 'price'],
            [['price' => '12.345'], 'price'],
            [['price' => '100000000.00'], 'price'],
            [['platform_id' => 999999], 'platform_id'],
            [['category_id' => []], 'category_id'],
            [['genre_ids' => [999999]], 'genre_ids.0'],
            [['genre_ids' => null], 'genre_ids'],
            [['is_active' => null], 'is_active'],
        ] as [$data, $errorField]) {
            $this->patchJson('/api/products/'.$product->id, $data)
                ->assertUnprocessable()->assertJsonValidationErrors($errorField);
        }

        $product->refresh();
        $this->assertSame($initialStock, $product->stock);
        $this->assertSame($initialPrice, $product->price);
        $this->assertSame($categoryIds, $product->category_id);
        $this->assertEqualsCanonicalizing($genreIds, $product->genres()->pluck('genres.id')->all());
    }

    public function test_missing_products_return_not_found(): void
    {
        $this->getJson('/api/products/999999')->assertNotFound();
        $this->patchJson('/api/products/999999', ['stock' => 3])->assertNotFound();
        // A busca do FormRequest mantém 404 mesmo quando o corpo também é inválido.
        $this->patchJson('/api/products/999999', ['stock' => -1])->assertNotFound();
        $this->deleteJson('/api/products/999999')->assertNotFound();
    }

    public function test_a_failed_association_rolls_back_the_whole_creation(): void
    {
        // Chama diretamente o Service para provocar uma falha após começar a gravação.
        // Pela API, esse ID seria rejeitado pelo FormRequest antes de gravar.
        $data = $this->productData();
        $data['genre_ids'] = [999999];

        try {
            app(ProductService::class)->create($data);
            $this->fail('A associação com gênero inexistente deveria falhar.');
        } catch (QueryException $exception) {
            // A transação deve desfazer tanto o produto quanto as categorias já ligadas.
            $this->assertDatabaseMissing('products', ['name' => 'Jogo de Teste']);
            $this->assertDatabaseCount('products', 30);
            $this->assertDatabaseCount('category_product', 0);
        }
    }

    public function test_invalid_updates_preserve_product_data(): void
    {
        $product = Product::firstOrFail();

        $this->patchJson('/api/products/'.$product->id, [
            'price' => '-1',
            'stock' => 4294967296,
            'category_id' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'stock', 'category_id']);

        $this->assertSame($product->price, $product->fresh()->price);
        $this->assertSame($product->stock, $product->fresh()->stock);
    }

    public function test_resources_preserve_public_fields_and_unvalidated_input_is_ignored(): void
    {
        $data = $this->productData();
        $data['created_at'] = '2000-01-01 00:00:00';

        $response = $this->postJson('/api/products', $data)->assertCreated();
        $product = Product::findOrFail($response->json('data.id'));

        $this->assertNotSame('2000-01-01 00:00:00', $product->created_at->format('Y-m-d H:i:s'));
        // Mantém os campos públicos locais, usando a data do banco em vez da entrada ignorada.
        $response->assertJsonPath('data.created_at', $product->created_at->toISOString())
            ->assertJsonPath('data.platform_id', $product->platform_id)
            ->assertJsonMissingPath('data.categories.0.pivot')
            ->assertJsonMissingPath('data.genres.0.pivot');
    }

    // Prepara um corpo JSON válido com os IDs efetivamente criados pelos seeders.
    private function productData(): array
    {
        return [
            'name' => 'Jogo de Teste',
            'description' => 'Jogo físico de demonstração para testar o CRUD.',
            'platform_id' => Platform::where('name', 'PlayStation 5')->firstOrFail()->id,
            'price' => '199.90',
            'image_path' => 'images/product-placeholder.svg',
            'stock' => 12,
            'category_id' => Category::where('name', 'Jogos')->firstOrFail()->id,
            'genre_ids' => Genre::whereIn('name', ['Ação', 'RPG'])->pluck('id')->all(),
        ];
    }
}
