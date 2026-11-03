<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRouteTest extends TestCase
{
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
