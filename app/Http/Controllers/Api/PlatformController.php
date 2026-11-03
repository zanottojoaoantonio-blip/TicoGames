<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Http\Resources\PlatformResource;
use App\Services\PlatformService;

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

        return PlatformResource::collection($platforms);
    }

    // POST /api/platforms: envia somente os dados validados ao Service.
    public function store(StorePlatformRequest $request)
    {
        $platform = $this->platformService->create($request->validated());

        return (new PlatformResource($platform))->response()->setStatusCode(201);
    }

    // GET /api/platforms/{id}: o Service busca o registro ou gera o erro 404.
    public function show(int $id)
    {
        $platform = $this->platformService->findById($id);

        return new PlatformResource($platform);
    }

    // PUT ou PATCH /api/platforms/{id}: envia o ID e os dados ao Service.
    public function update(UpdatePlatformRequest $request, int $id)
    {
        $platform = $this->platformService->update($id, $request->validated());

        return new PlatformResource($platform);
    }

    // DELETE /api/platforms/{id}: exclui se não houver produtos associados e responde sem corpo.
    public function destroy(int $id)
    {
        $this->platformService->delete($id);

        return response()->noContent();
    }
}
