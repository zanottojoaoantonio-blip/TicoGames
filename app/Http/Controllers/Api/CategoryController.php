<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\Request;

// Recebe as requisições de categorias e delega as operações ao Service.
class CategoryController extends Controller
{
    // O Laravel injeta o Service e o guarda nesta propriedade.
    public function __construct(
        private CategoryService $categoryService
    ) {}

    // GET /api/categories: busca os cadastros e devolve a lista em JSON.
    public function index()
    {
        $categories = $this->categoryService->listAll();

        return response()->json($categories);
    }

    // POST /api/categories: repassa os dados; a validação ficará nos FormRequests.
    public function store(Request $request)
    {
        $category = $this->categoryService->create($request->all());

        return response()->json($category, 201);
    }

    // GET /api/categories/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $category = $this->categoryService->findById($id);

        return response()->json($category);
    }

    // PUT ou PATCH /api/categories/{id}: envia o ID e os dados ao Service.
    public function update(Request $request, int $id)
    {
        $category = $this->categoryService->update($id, $request->all());

        return response()->json($category);
    }

    // DELETE /api/categories/{id}: exclui se não houver produtos associados e responde sem corpo.
    public function destroy(int $id)
    {
        $this->categoryService->delete($id);

        return response()->noContent();
    }
}
