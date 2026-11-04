<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRouteTest extends TestCase
{
    public function test_private_api_returns_json_401_for_guests_regardless_of_accept_header(): void
    {
        foreach ([[], ['Accept' => '*/*'], ['Accept' => 'text/html'], ['Accept' => 'application/json']] as $headers) {
            $this->get('/api/users', $headers)
                ->assertUnauthorized()
                ->assertHeader('Content-Type', 'application/json')
                ->assertHeaderMissing('Location')
                ->assertJson(['message' => 'Unauthenticated.']);
        }
    }

    public function test_api_not_found_errors_are_json_with_a_wildcard_accept_header(): void
    {
        // A configuração local de erros continua ativa após integrar as novas rotas.
        $this->get('/api/products/abc', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    // Todos os verbos devem rejeitar IDs com letras antes de acessar o banco.
    public function test_resource_routes_reject_non_numeric_ids(): void
    {
        foreach (['products', 'categories', 'platforms', 'genres'] as $resource) {
            foreach (['GET', 'PUT', 'PATCH', 'DELETE'] as $method) {
                $this->json($method, '/api/'.$resource.'/abc')->assertNotFound();
            }
        }
    }
}
