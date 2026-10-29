<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GenreService;
use Illuminate\Http\Request;

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

        return response()->json($genres);
    }

    // POST /api/genres: repassa os dados; a validação ficará nos FormRequests.
    public function store(Request $request)
    {
        $genre = $this->genreService->create($request->all());

        return response()->json($genre, 201);
    }

    // GET /api/genres/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $genre = $this->genreService->findById($id);

        return response()->json($genre);
    }

    // PUT ou PATCH /api/genres/{id}: envia o ID e os dados ao Service.
    public function update(Request $request, int $id)
    {
        $genre = $this->genreService->update($id, $request->all());

        return response()->json($genre);
    }

    // DELETE /api/genres/{id}: exclui se não houver produtos associados e responde sem corpo.
    public function destroy(int $id)
    {
        $this->genreService->delete($id);

        return response()->noContent();
    }
}
