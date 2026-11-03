<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data, Request $request): User
    {
        // role não está em fillable: o banco aplica o padrão customer.
        $user = User::create($data);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // Recarrega também o perfil definido pelo banco para o Resource.
        $user->refresh();

        return $user;
    }

    public function login(array $credentials, Request $request): User
    {
        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        // Troca o identificador da sessão depois de autenticar.
        $request->session()->regenerate();
        $user = Auth::guard('web')->user();

        return $user;
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
