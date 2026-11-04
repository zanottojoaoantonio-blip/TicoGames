<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MergeCartRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function show(Request $request)
    {
        $cart = $this->cartService->findForUser($request->user());

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function store(StoreCartItemRequest $request)
    {
        $cart = $this->cartService->add($request->user(), [$request->validated()]);

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function update(UpdateCartItemRequest $request, int $id)
    {
        $cart = $this->cartService->update($request->user(), $id, $request->validated('quantity'));

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function merge(MergeCartRequest $request)
    {
        // O frontend enviará os itens locais após o login e os limpará após sucesso.
        $cart = $this->cartService->add($request->user(), $request->validated('items'));

        return (new CartResource($cart))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, int $id)
    {
        $this->cartService->delete($request->user(), $id);

        return response()->noContent();
    }

    public function clear(Request $request)
    {
        $this->cartService->delete($request->user());

        return response()->noContent();
    }
}
