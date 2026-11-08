<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_sorting_and_pagination_are_applied_before_returning_products(): void
    {
        $this->seed();
        $category = Category::where('name', 'Acessórios')->firstOrFail();
        $response = $this->getJson('/api/products?category_id='.$category->id.'&sort=price_desc&per_page=1')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2)->assertJsonPath('meta.last_page', 2);
        $id = $response->json('data.0.id');
        $this->getJson('/api/products?category_id='.$category->id.'&sort=price_desc&per_page=1&page=2')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('meta.current_page', 2);
        $this->getJson('/api/products?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/products?per_page=101')->assertUnprocessable();
        $this->getJson('/api/products?q=Headset')->assertOk()->assertJsonCount(1, 'data');
        $ascending = $this->getJson('/api/products?sort=price_asc&per_page=100')->json('data');
        $prices = array_column($ascending, 'price');
        $sorted = $prices;
        usort($sorted, fn ($a, $b) => bccomp($a, $b, 2));
        $this->assertSame($sorted, $prices);
    }

    public function test_only_admin_can_upload_images_and_uploaded_bytes_are_stored(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $image = UploadedFile::fake()->createWithContent('cover.png', $png);
        $this->postJson('/api/product-images', ['image' => $image])->assertUnauthorized();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.test', 'password' => 'password']);
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $path = $this->postJson('/api/product-images', ['image' => $image])->assertCreated()->json('data.image_path');
        Storage::disk('public')->assertExists(substr($path, strlen('/storage/')));
        $this->get($path)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->postJson('/api/product-images', ['image' => UploadedFile::fake()->createWithContent('bad.svg', '<svg onload="alert(1)"/>')])->assertUnprocessable();
    }
}
