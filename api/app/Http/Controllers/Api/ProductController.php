<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadProductImageRequest;
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
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'platform_id' => ['nullable', 'integer', 'min:1'],
            'genre_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'in:id,price_asc,price_desc,newest'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $products = $this->productService->listAll($request->user()?->role === 'admin', $filters);

        return ProductResource::collection($products);
    }

    public function upload(UploadProductImageRequest $request)
    {
        $path = $this->productService->upload($request->file('image'));

        return response()->json(['data' => ['image_path' => $path]], 201);
    }

    // POST /api/products: o FormRequest valida antes de executar esta ação.
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        // O Resource define os campos; 201 informa que um cadastro foi criado.
        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    // GET /api/products/{id}: o Service busca o registro ou gera o erro 404.
    public function show(Request $request, int $id)
    {
        $product = $this->productService->findById($id, $request->user()?->role === 'admin');

        return new ProductResource($product);
    }

    // PUT ou PATCH /api/products/{id}: envia somente os campos validados ao Service.
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
