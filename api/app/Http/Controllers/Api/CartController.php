<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    private function cart(Request $request)
    {
        if (! $request->user() && ! $request->session()->has('guest_cart_token')) {
            $request->session()->put('guest_cart_token', Str::random(64));
        }

        return $this->cartService->findForUser($request->user(), $request->session()->get('guest_cart_token'));
    }

    public function show(Request $request)
    {
        $cart = $this->cart($request);
        $cart->setAttribute('warnings', $request->session()->pull('cart_warnings', []));

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function store(StoreCartItemRequest $request)
    {
        $cart = $this->cartService->add($this->cart($request), $request->validated());

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function update(UpdateCartItemRequest $request, int $id)
    {
        $cart = $this->cartService->update($this->cart($request), $id, $request->validated('quantity'));

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function merge(Request $request)
    {
        $cart = $this->cartService->merge($request->user(), $request->session()->get('guest_cart_token'));
        $request->session()->forget('guest_cart_token');

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, int $id)
    {
        $this->cartService->delete($this->cart($request), $id);

        return response()->noContent();
    }

    public function clear(Request $request)
    {
        $this->cartService->delete($this->cart($request));

        return response()->noContent();
    }
}
