<?php

namespace App\Http\Controllers;

// Recebe as requisições de gêneros e devolve respostas JSON.
use App\Models\Genre;
use App\Services\GenreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GenreController extends Controller
{
    // O Laravel entrega o Service automaticamente ao construir o Controller.
    public function __construct(private GenreService $genreService) {}

    // GET /api/genres: lista os cadastros com a quantidade de produtos associados.
    public function index(): JsonResponse
    {
        return response()->json($this->genreService->getAll());
    }

    // POST /api/genres: valida o nome e cria o cadastro.
    public function store(Request $request): JsonResponse
    {
        // O nome é obrigatório, cabe na coluna e não pode existir nessa tabela.
        // Esta validação será organizada em FormRequest na etapa 7.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
        ]);

        return response()->json($this->genreService->create($data), 201);
    }

    // GET /api/genres/{genre}: o Laravel busca pelo ID e responde 404 se não existir.
    public function show(Genre $genre): JsonResponse
    {
        return response()->json($this->genreService->getOne($genre));
    }

    // PUT ou PATCH /api/genres/{genre}: altera o nome enviado.
    public function update(Request $request, Genre $genre): JsonResponse
    {
        // ignore() permite manter o próprio nome, mas bloqueia o nome de outro registro.
        // O registro ignorado vem do Model encontrado pela rota.
        $data = $request->validate([
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('genres', 'name')->ignore($genre->id),
            ],
        ]);

        return response()->json($this->genreService->update($genre, $data));
    }

    // DELETE /api/genres/{genre}: só exclui um cadastro sem produtos associados.
    public function destroy(Genre $genre): Response
    {
        $this->genreService->delete($genre);

        return response()->noContent();
    }
}
