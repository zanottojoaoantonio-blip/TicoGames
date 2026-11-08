<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private CartService $cartService) {}

    public function register(array $data, Request $request): User
    {
        // role não está em fillable: o banco aplica o padrão customer.
        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create($data)->refresh();
            $this->mergeGuestCart($user, $request);

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate(true);
        $request->session()->forget('guest_cart_token');

        return $user;
    }

    public function login(array $credentials, Request $request): User
    {
        if (! Auth::guard('web')->attemptWhen($credentials, fn (User $user) => $this->mergeGuestCart($user, $request))) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        // Troca o identificador da sessão depois de autenticar.
        $request->session()->regenerate(true);
        $request->session()->forget('guest_cart_token');
        $user = Auth::guard('web')->user();

        return $user;
    }

    private function mergeGuestCart(User $user, Request $request): bool
    {
        $cart = $this->cartService->merge($user, $request->session()->get('guest_cart_token'));
        $request->session()->put('cart_warnings', $cart->warnings);

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
