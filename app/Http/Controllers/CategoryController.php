<?php

namespace App\Http\Controllers;

// Recebe as requisições de categorias e devolve respostas JSON.
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // O Laravel entrega o Service automaticamente ao construir o Controller.
    public function __construct(private CategoryService $categoryService) {}

    // GET /api/categories: lista os cadastros com a quantidade de produtos associados.
    public function index(): JsonResponse
    {
        return response()->json($this->categoryService->getAll());
    }

    // POST /api/categories: valida o nome e cria o cadastro.
    public function store(Request $request): JsonResponse
    {
        // O nome é obrigatório, cabe na coluna e não pode existir nessa tabela.
        // Esta validação será organizada em FormRequest na etapa 7.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        return response()->json($this->categoryService->create($data), 201);
    }

    // GET /api/categories/{category}: o Laravel busca pelo ID e responde 404 se não existir.
    public function show(Category $category): JsonResponse
    {
        return response()->json($this->categoryService->getOne($category));
    }

    // PUT ou PATCH /api/categories/{category}: altera o nome enviado.
    public function update(Request $request, Category $category): JsonResponse
    {
        // ignore() permite manter o próprio nome, mas bloqueia o nome de outro registro.
        // O registro ignorado vem do Model encontrado pela rota.
        $data = $request->validate([
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ]);

        return response()->json($this->categoryService->update($category, $data));
    }

    // DELETE /api/categories/{category}: só exclui um cadastro sem produtos associados.
    public function destroy(Category $category): Response
    {
        $this->categoryService->delete($category);

        return response()->noContent();
    }
}
