<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    // Executa somente no banco de testes, configurado como SQLite em memória.
    use RefreshDatabase;

    // Os três cadastros têm o mesmo formato; os testes percorrem cada rota e Model.
    private const RESOURCES = [
        'categories' => Category::class,
        'platforms' => Platform::class,
        'genres' => Genre::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\UserSeeder::class, \Database\Seeders\CategorySeeder::class, \Database\Seeders\PlatformSeeder::class, \Database\Seeders\GenreSeeder::class, \Database\Seeders\ProductSeeder::class, \Database\Seeders\AddressSeeder::class]);
        $this->actingAs(User::where('role', 'admin')->firstOrFail()); // Utiliza os exemplos reais, sem factories.
    }

    public function test_catalog_lists_and_details_include_product_counts(): void
    {
        foreach (self::RESOURCES as $resource => $model) {
            $record = $model::firstOrFail();

            $this->getJson('/api/'.$resource)
                ->assertOk()
                ->assertJsonCount($model::count(), 'data')
                ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'products_count', 'created_at', 'updated_at']]]);

            $this->getJson('/api/'.$resource.'/'.$record->id)
                ->assertOk()
                ->assertJsonPath('data.id', $record->id)
                ->assertJsonPath('data.name', $record->name)
                ->assertJsonPath('data.products_count', $record->products()->count());
        }
    }

    public function test_each_catalog_record_can_be_created_renamed_and_deleted(): void
    {
        foreach (self::RESOURCES as $resource => $model) {
            // Um cadastro sem produtos pode completar o ciclo de criação até exclusão.
            $created = $this->postJson('/api/'.$resource, ['name' => 'Cadastro de Teste'])
                ->assertCreated()
                ->assertJsonPath('data.name', 'Cadastro de Teste')
                ->assertJsonPath('data.products_count', 0);
            $id = $created->json('data.id');

            $this->patchJson('/api/'.$resource.'/'.$id, ['name' => 'Cadastro Renomeado'])
                ->assertOk()->assertJsonPath('data.name', 'Cadastro Renomeado');

            // PUT também é aceito; manter o próprio nome não viola a unicidade.
            $this->putJson('/api/'.$resource.'/'.$id, ['name' => 'Cadastro Renomeado'])
                ->assertOk();

            $this->assertDatabaseHas($resource, ['id' => $id, 'name' => 'Cadastro Renomeado']);
            $this->deleteJson('/api/'.$resource.'/'.$id)->assertNoContent();
            $this->assertDatabaseMissing($resource, ['id' => $id]);
            $this->getJson('/api/'.$resource.'/'.$id)->assertNotFound();
        }
    }

    public function test_names_must_be_present_valid_and_unique(): void
    {
        foreach (self::RESOURCES as $resource => $model) {
            $record = $model::firstOrFail();
            $initialCount = $model::count();

            // Erros de entrada retornam 422 e não criam cadastros parciais.
            foreach ([[], ['name' => ''], ['name' => null], ['name' => 123], ['name' => ['invalid']], ['name' => str_repeat('a', 256)], ['name' => $record->name]] as $data) {
                $this->postJson('/api/'.$resource, $data)
                    ->assertUnprocessable()->assertJsonValidationErrors('name');
            }

            $this->assertDatabaseCount($resource, $initialCount);
        }
    }

    public function test_validation_errors_are_json_with_a_wildcard_accept_header(): void
    {
        // O admin já está autenticado; a validação não deve redirecionar para HTML.
        $this->json('POST', '/api/categories', ['name' => ''], ['Accept' => '*/*'])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors('name');
    }

    public function test_updates_cannot_take_another_records_name(): void
    {
        foreach (self::RESOURCES as $resource => $model) {
            $record = $model::firstOrFail();
            $other = $model::create(['name' => 'Nome Já Utilizado']);

            $this->patchJson('/api/'.$resource.'/'.$record->id, ['name' => $other->name])
                ->assertUnprocessable()->assertJsonValidationErrors('name');
            $this->assertDatabaseHas($resource, ['id' => $record->id, 'name' => $record->name]);

            foreach (['', null, 123, ['invalid'], str_repeat('a', 256)] as $name) {
                $this->patchJson('/api/'.$resource.'/'.$record->id, ['name' => $name])
                    ->assertUnprocessable()->assertJsonValidationErrors('name');
            }
        }
    }

    public function test_records_used_by_products_cannot_be_deleted(): void
    {
        foreach (self::RESOURCES as $resource => $model) {
            // Os primeiros registros dos seeders já estão associados a produtos.
            $record = $model::firstOrFail();
            $productIds = $record->products()->pluck('products.id')->all();
            $this->assertNotEmpty($productIds);

            $this->deleteJson('/api/'.$resource.'/'.$record->id)
                ->assertUnprocessable()->assertJsonValidationErrors(lcfirst(class_basename($model)));

            // Tanto o cadastro quanto suas associações devem continuar intactos.
            $this->assertDatabaseHas($resource, ['id' => $record->id]);
            $this->assertEqualsCanonicalizing($productIds, $record->products()->pluck('products.id')->all());
        }
    }

    public function test_missing_catalog_records_return_not_found(): void
    {
        foreach (self::RESOURCES as $resource => $model) {
            $this->getJson('/api/'.$resource.'/999999')->assertNotFound();
            $this->patchJson('/api/'.$resource.'/999999', ['name' => 'Teste'])->assertNotFound();
            $this->patchJson('/api/'.$resource.'/999999', ['name' => ''])->assertNotFound();
            $this->deleteJson('/api/'.$resource.'/999999')->assertNotFound();
        }
    }
}
