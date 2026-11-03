<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
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
    public function index(Request $request)
    {
        $products = $this->productService->listAll($request->user()?->role === 'admin');

        return ProductResource::collection($products);
    }

    // POST /api/products: envia somente os dados validados ao Service.
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    // GET /api/products/{id}: o Service busca o registro ou gera o erro 404.
    public function show(Request $request, int $id)
    {
        $product = $this->productService->findById($id, $request->user()?->role === 'admin');

        return new ProductResource($product);
    }

    // PUT ou PATCH /api/products/{id}: envia o ID e os dados ao Service.
    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->productService->update($id, $request->validated());

        return new ProductResource($product);
    }

    // DELETE /api/products/{id}: desativa o produto e responde sem corpo.
    public function destroy(int $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
