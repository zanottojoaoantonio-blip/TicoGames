<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index()
    {
        $categories = $this->categoryService->listAll();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(int $id)
    {
        $category = $this->categoryService->findById($id);

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, int $id)
    {
        $category = $this->categoryService->update($id, $request->validated());

        return new CategoryResource($category);
    }

    public function destroy(int $id)
    {
        $this->categoryService->delete($id);

        return response()->noContent();
    }
}
