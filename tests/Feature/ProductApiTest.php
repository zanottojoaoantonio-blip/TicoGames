<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Platform;
use App\Models\Product;
use App\Services\ProductService;
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
        $this->seed();
    }

    public function test_products_can_be_listed_with_their_relationships(): void
    {
        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(7)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'price', 'stock', 'is_active', 'platform', 'categories', 'genres'],
            ]);
    }

    public function test_a_product_can_be_found_by_its_id(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('price', $product->price)
            ->assertJsonPath('platform.id', $product->platform_id)
            ->assertJsonCount(2, 'genres');
    }

    public function test_a_product_can_be_created_with_categories_and_genres(): void
    {
        $data = $this->productData();

        // Não envia is_active: a resposta deve incluir o padrão true do banco.
        $this->postJson('/api/products', $data)
            ->assertCreated()
            ->assertJsonPath('name', 'Jogo de Teste')
            ->assertJsonPath('price', '199.90')
            ->assertJsonPath('stock', 12)
            ->assertJsonPath('is_active', true)
            ->assertJsonCount(2, 'categories')
            ->assertJsonCount(2, 'genres');

        $product = Product::where('name', 'Jogo de Teste')->firstOrFail();
        $this->assertDatabaseCount('products', 8);
        $this->assertEqualsCanonicalizing($data['category_ids'], $product->categories()->pluck('categories.id')->all());
        $this->assertEqualsCanonicalizing($data['genre_ids'], $product->genres()->pluck('genres.id')->all());
    }

    public function test_a_console_can_be_created_without_genres(): void
    {
        $data = $this->productData();
        $data['category_ids'] = [Category::where('name', 'Consoles')->firstOrFail()->id];
        unset($data['genre_ids']);

        $this->postJson('/api/products', $data)
            ->assertCreated()
            ->assertJsonCount(0, 'genres');
    }

    public function test_partial_updates_preserve_omitted_fields_and_relationships(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $categories = $product->categories()->pluck('categories.id')->all();

        $this->patchJson('/api/products/'.$product->id, ['stock' => 3])
            ->assertOk()
            ->assertJsonPath('stock', 3)
            ->assertJsonPath('price', $product->price)
            ->assertJsonCount(2, 'genres');

        $this->assertEqualsCanonicalizing($categories, $product->categories()->pluck('categories.id')->all());
    }

    public function test_updates_replace_provided_relationships_and_can_clear_genres(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $category = Category::where('name', 'Jogos')->firstOrFail();
        $platform = Platform::where('name', 'Xbox Series')->firstOrFail();

        $this->putJson('/api/products/'.$product->id, [
            'price' => '149.90',
            'platform_id' => $platform->id,
            'category_ids' => [$category->id],
            'genre_ids' => [],
        ])->assertOk()
            ->assertJsonPath('price', '149.90')
            ->assertJsonPath('platform.id', $platform->id)
            ->assertJsonPath('categories.0.id', $category->id)
            ->assertJsonCount(1, 'categories')
            ->assertJsonCount(0, 'genres');
    }

    public function test_deleting_deactivates_the_product_without_removing_its_links(): void
    {
        $product = Product::where('name', 'Elden Ring')->firstOrFail();
        $categoryCount = $product->categories()->count();

        $this->deleteJson('/api/products/'.$product->id)->assertNoContent();

        $this->assertDatabaseCount('products', 7);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertSame($categoryCount, $product->categories()->count());

        // Esta API administrativa inicial permite conferir e reativar cadastros inativos.
        $this->getJson('/api/products/'.$product->id)->assertOk()->assertJsonPath('is_active', false);
        $this->patchJson('/api/products/'.$product->id, ['is_active' => true])
            ->assertOk()->assertJsonPath('is_active', true);
    }

    public function test_invalid_input_is_rejected_without_inserting_a_product(): void
    {
        $data = $this->productData();
        $data['name'] = '';
        $data['platform_id'] = 999999;
        $data['price'] = '12.345';
        $data['stock'] = -1;
        $data['category_ids'] = [];
        $data['genre_ids'] = [999999];

        $this->postJson('/api/products', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'platform_id', 'price', 'stock', 'category_ids', 'genre_ids.0']);

        $this->assertDatabaseCount('products', 7);

        // Categorias repetidas também não podem gerar vínculos duplicados.
        $data = $this->productData();
        $data['category_ids'] = [$data['category_ids'][0], $data['category_ids'][0]];
        $this->postJson('/api/products', $data)->assertUnprocessable()->assertJsonValidationErrors('category_ids.0');
        $this->assertDatabaseCount('products', 7);
    }

    public function test_missing_products_return_not_found(): void
    {
        $this->getJson('/api/products/999999')->assertNotFound();
        $this->patchJson('/api/products/999999', ['stock' => 3])->assertNotFound();
        $this->deleteJson('/api/products/999999')->assertNotFound();
    }

    public function test_a_failed_association_rolls_back_the_whole_creation(): void
    {
        // Chama diretamente o Service para provocar uma falha após começar a gravação.
        // Pela API, esse ID já seria rejeitado pela validação do Controller.
        $data = $this->productData();
        $data['genre_ids'] = [999999];

        try {
            app(ProductService::class)->create($data);
            $this->fail('A associação com gênero inexistente deveria falhar.');
        } catch (QueryException $exception) {
            // A transação deve desfazer tanto o produto quanto as categorias já ligadas.
            $this->assertDatabaseMissing('products', ['name' => 'Jogo de Teste']);
            $this->assertDatabaseCount('products', 7);
            $this->assertDatabaseCount('category_product', 11);
        }
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
            'category_ids' => Category::whereIn('name', ['Jogos', 'Destaques'])->pluck('id')->all(),
            'genre_ids' => Genre::whereIn('name', ['Ação', 'RPG'])->pluck('id')->all(),
        ];
    }
}
