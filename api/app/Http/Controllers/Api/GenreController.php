<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Http\Resources\GenreResource;
use App\Services\GenreService;

class GenreController extends Controller
{
    public function __construct(
        private GenreService $genreService
    ) {}

    public function index()
    {
        $genres = $this->genreService->listAll();

        return GenreResource::collection($genres);
    }

    public function store(StoreGenreRequest $request)
    {
        $genre = $this->genreService->create($request->validated());

        return (new GenreResource($genre))->response()->setStatusCode(201);
    }

    public function show(int $id)
    {
        $genre = $this->genreService->findById($id);

        return new GenreResource($genre);
    }

    public function update(UpdateGenreRequest $request, int $id)
    {
        $genre = $this->genreService->update($id, $request->validated());

        return new GenreResource($genre);
    }

    public function destroy(int $id)
    {
        $this->genreService->delete($id);

        return response()->noContent();
    }
}
