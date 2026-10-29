<?php

namespace App\Http\Controllers;

// Recebe as requisições de plataformas e devolve respostas JSON.
use App\Models\Platform;
use App\Services\PlatformService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    // O Laravel entrega o Service automaticamente ao construir o Controller.
    public function __construct(private PlatformService $platformService) {}

    // GET /api/platforms: lista os cadastros com a quantidade de produtos associados.
    public function index(): JsonResponse
    {
        return response()->json($this->platformService->getAll());
    }

    // POST /api/platforms: valida o nome e cria o cadastro.
    public function store(Request $request): JsonResponse
    {
        // O nome é obrigatório, cabe na coluna e não pode existir nessa tabela.
        // Esta validação será organizada em FormRequest na etapa 7.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:platforms,name'],
        ]);

        return response()->json($this->platformService->create($data), 201);
    }

    // GET /api/platforms/{platform}: o Laravel busca pelo ID e responde 404 se não existir.
    public function show(Platform $platform): JsonResponse
    {
        return response()->json($this->platformService->getOne($platform));
    }

    // PUT ou PATCH /api/platforms/{platform}: altera o nome enviado.
    public function update(Request $request, Platform $platform): JsonResponse
    {
        // ignore() permite manter o próprio nome, mas bloqueia o nome de outro registro.
        // O registro ignorado vem do Model encontrado pela rota.
        $data = $request->validate([
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('platforms', 'name')->ignore($platform->id),
            ],
        ]);

        return response()->json($this->platformService->update($platform, $data));
    }

    // DELETE /api/platforms/{platform}: só exclui um cadastro sem produtos associados.
    public function destroy(Platform $platform): Response
    {
        $this->platformService->delete($platform);

        return response()->noContent();
    }
}
