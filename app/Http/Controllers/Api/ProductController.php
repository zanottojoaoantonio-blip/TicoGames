<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

// Recebe as requisições de produtos e delega as operações ao Service.
class ProductController extends Controller
{
    // O Laravel injeta o Service e o guarda nesta propriedade.
    public function __construct(
        private ProductService $productService
    ) {}

    // GET /api/products: busca os cadastros e devolve a lista em JSON.
    public function index()
    {
        $products = $this->productService->listAll();

        return response()->json($products);
    }

    // POST /api/products: repassa os dados; a validação ficará nos FormRequests.
    public function store(Request $request)
    {
        $product = $this->productService->create($request->all());

        return response()->json($product, 201);
    }

    // GET /api/products/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $product = $this->productService->findById($id);

        return response()->json($product);
    }

    // PUT ou PATCH /api/products/{id}: envia o ID e os dados ao Service.
    public function update(Request $request, int $id)
    {
        $product = $this->productService->update($id, $request->all());

        return response()->json($product);
    }

    // DELETE /api/products/{id}: desativa o produto e responde sem corpo.
    public function destroy(int $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
