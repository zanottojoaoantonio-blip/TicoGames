<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private AuthService $authService
    ) {}

    // As ações de listagem e gerenciamento por ID ficam restritas ao administrador.
    public function index()
    {
        $users = $this->userService->listAll();

        return UserResource::collection($users);
    }

    public function show(int $id)
    {
        $user = $this->userService->findById($id);

        return new UserResource($user);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->userService->update($id, $request->validated());

        return new UserResource($user);
    }

    public function destroy(Request $request, int $id)
    {
        // Ao excluir a própria conta, encerra também a sessão atual.
        if ($request->user()->id === $id) {
            return $this->deleteMe($request);
        }
        $this->userService->delete($id);

        return response()->noContent();
    }

    public function updateMe(UpdateUserRequest $request)
    {
        $user = $this->userService->update($request->user()->id, $request->validated());

        return new UserResource($user);
    }

    public function deleteMe(Request $request)
    {
        $this->userService->delete($request->user()->id);
        $this->authService->logout($request);

        return response()->noContent();
    }
}
