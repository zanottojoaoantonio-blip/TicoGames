<?php

namespace App\Http\Controllers;

// O Controller recebe os dados HTTP, valida a entrada e devolve a resposta JSON.
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    // O Laravel cria e entrega o Service ao construir este Controller.
    // A propriedade guarda essa instância para os cinco métodos abaixo.
    public function __construct(private ProductService $productService) {}

    // GET /api/products: lista os cadastros com seus relacionamentos.
    public function index(): JsonResponse
    {
        return response()->json($this->productService->getAll());
    }

    // POST /api/products: valida e cadastra um produto.
    public function store(Request $request): JsonResponse
    {
        // A validação fica aqui no CRUD básico; na etapa 7 irá para FormRequests.
        // Somente os campos validados são repassados ao Service.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'image_path' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ]);

        // 201 significa que um novo recurso foi criado.
        return response()->json($this->productService->create($data), 201);
    }

    // GET /api/products/{product}: o tipo Product ativa a busca automática pelo ID.
    // Se o ID não existir, o Laravel devolve 404 antes de executar este método.
    public function show(Product $product): JsonResponse
    {
        return response()->json($this->productService->getOne($product));
    }

    // PUT ou PATCH /api/products/{product}: atualiza somente os campos enviados.
    public function update(Request $request, Product $product): JsonResponse
    {
        // sometimes permite omitir um campo; required impede enviá-lo vazio.
        // As listas completas enviadas substituem as associações atuais.
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'platform_id' => ['sometimes', 'required', 'integer', 'exists:platforms,id'],
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'image_path' => ['sometimes', 'required', 'string', 'max:255'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'category_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ]);

        return response()->json($this->productService->update($product, $data));
    }

    // DELETE /api/products/{product}: desativa o cadastro em vez de apagar a linha.
    public function destroy(Product $product): Response
    {
        $this->productService->delete($product);

        // 204 confirma a operação sem enviar um corpo de resposta.
        return response()->noContent();
    }
}
