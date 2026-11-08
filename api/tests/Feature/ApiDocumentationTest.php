<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_openapi_covers_every_api_operation_and_docs_are_public(): void
    {
        $spec = Yaml::parseFile(base_path('openapi.yaml'));
        $this->assertSame('3.0.3', $spec['openapi']);
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/') || in_array($route->uri(), ['api/docs', 'api/openapi.yaml'], true)) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->assertArrayHasKey(strtolower($method), $spec['paths']['/'.$route->uri()] ?? [], $method.' '.$route->uri());
            }
        }
        $this->get('/api/docs')->assertOk()->assertSee('SwaggerUIBundle', false);
        $this->get('/api/openapi.yaml')->assertOk()->assertHeader('Content-Type', 'application/yaml');
    }

    public function test_openapi_describes_automatic_merge_empty_cart_and_pending_only_cancellation(): void
    {
        $spec = Yaml::parseFile(base_path('openapi.yaml'));
        foreach (['/api/login', '/api/register'] as $path) {
            $this->assertStringContainsString('fusão', $spec['paths'][$path]['post']['description']);
            $this->assertStringContainsString('GET /api/cart', $spec['paths'][$path]['post']['description']);
        }
        $checkout = $spec['paths']['/api/orders']['post'];
        $this->assertStringContainsString('Carrinho vazio', $checkout['responses'][422]['description']);
        $this->assertStringContainsString('estoque', $checkout['responses'][409]['description']);
        $this->assertStringContainsString('somente pending_payment', $spec['paths']['/api/orders/{id}/cancel']['post']['description']);
        $this->assertStringContainsString('paid para shipped', $spec['paths']['/api/orders/{id}/status']['patch']['description']);
    }
}
