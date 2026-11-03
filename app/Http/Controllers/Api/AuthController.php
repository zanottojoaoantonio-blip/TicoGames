<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register(
            $request->validated(),
            $request
        );

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201)
            ->header('Cache-Control', 'no-store');
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->login(
            $request->validated(),
            $request
        );

        return (new UserResource($user))
            ->response()
            ->header('Cache-Control', 'no-store');
    }

    public function me(Request $request)
    {
        return (new UserResource($request->user()))
            ->response()
            ->setStatusCode(200)
            ->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return response()->noContent();
    }
}
