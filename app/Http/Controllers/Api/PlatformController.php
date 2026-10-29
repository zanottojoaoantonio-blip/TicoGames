<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlatformService;
use Illuminate\Http\Request;

// Recebe as requisições de plataformas e delega as operações ao Service.
class PlatformController extends Controller
{
    // O Laravel injeta o Service e o guarda nesta propriedade.
    public function __construct(
        private PlatformService $platformService
    ) {}

    // GET /api/platforms: busca os cadastros e devolve a lista em JSON.
    public function index()
    {
        $platforms = $this->platformService->listAll();

        return response()->json($platforms);
    }

    // POST /api/platforms: repassa os dados; a validação ficará nos FormRequests.
    public function store(Request $request)
    {
        $platform = $this->platformService->create($request->all());

        return response()->json($platform, 201);
    }

    // GET /api/platforms/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $platform = $this->platformService->findById($id);

        return response()->json($platform);
    }

    // PUT ou PATCH /api/platforms/{id}: envia o ID e os dados ao Service.
    public function update(Request $request, int $id)
    {
        $platform = $this->platformService->update($id, $request->all());

        return response()->json($platform);
    }

    // DELETE /api/platforms/{id}: exclui se não houver produtos associados e responde sem corpo.
    public function destroy(int $id)
    {
        $this->platformService->delete($id);

        return response()->noContent();
    }
}
