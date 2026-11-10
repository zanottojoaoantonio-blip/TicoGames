<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Http\Resources\PlatformResource;
use App\Services\PlatformService;

class PlatformController extends Controller
{
    public function __construct(
        private PlatformService $platformService
    ) {}

    public function index()
    {
        $platforms = $this->platformService->listAll();

        return PlatformResource::collection($platforms);
    }

    public function store(StorePlatformRequest $request)
    {
        $platform = $this->platformService->create($request->validated());

        return (new PlatformResource($platform))->response()->setStatusCode(201);
    }

    public function show(int $id)
    {
        $platform = $this->platformService->findById($id);

        return new PlatformResource($platform);
    }

    public function update(UpdatePlatformRequest $request, int $id)
    {
        $platform = $this->platformService->update($id, $request->validated());

        return new PlatformResource($platform);
    }

    public function destroy(int $id)
    {
        $this->platformService->delete($id);

        return response()->noContent();
    }
}
