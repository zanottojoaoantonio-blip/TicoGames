<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Http\Resources\GenreResource;
use App\Services\GenreService;

// Recebe as requisições de gêneros e delega as operações ao Service.
class GenreController extends Controller
{
    // O Laravel injeta o Service e o guarda nesta propriedade.
    public function __construct(
        private GenreService $genreService
    ) {}

    // GET /api/genres: busca os cadastros e devolve a lista em JSON.
    public function index()
    {
        $genres = $this->genreService->listAll();

        return GenreResource::collection($genres);
    }

    // POST /api/genres: o FormRequest valida antes de executar esta ação.
    public function store(StoreGenreRequest $request)
    {
        $genre = $this->genreService->create($request->validated());

        // O Resource define os campos; 201 informa que um cadastro foi criado.
        return (new GenreResource($genre))->response()->setStatusCode(201);
    }

    // GET /api/genres/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $genre = $this->genreService->findById($id);

        return new GenreResource($genre);
    }

    // PUT ou PATCH /api/genres/{id}: envia somente os campos validados ao Service.
    public function update(UpdateGenreRequest $request, int $id)
    {
        $genre = $this->genreService->update($id, $request->validated());

        return new GenreResource($genre);
    }

    // DELETE /api/genres/{id}: exclui se não houver produtos associados e responde sem corpo.
    public function destroy(int $id)
    {
        $this->genreService->delete($id);

        return response()->noContent();
    }
}
