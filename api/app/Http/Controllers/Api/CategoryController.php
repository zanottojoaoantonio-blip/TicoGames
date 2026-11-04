<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;

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

        return CategoryResource::collection($categories);
    }

    // POST /api/categories: o FormRequest valida antes de executar esta ação.
    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->create($request->validated());

        // O Resource define os campos; 201 informa que um cadastro foi criado.
        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    // GET /api/categories/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $category = $this->categoryService->findById($id);

        return new CategoryResource($category);
    }

    // PUT ou PATCH /api/categories/{id}: envia somente os campos validados ao Service.
    public function update(UpdateCategoryRequest $request, int $id)
    {
        $category = $this->categoryService->update($id, $request->validated());

        return new CategoryResource($category);
    }

    // DELETE /api/categories/{id}: exclui se não houver produtos associados e responde sem corpo.
    public function destroy(int $id)
    {
        $this->categoryService->delete($id);

        return response()->noContent();
    }
}
