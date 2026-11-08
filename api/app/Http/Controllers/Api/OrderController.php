<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', 'in:pending_payment,paid,shipped,delivered,cancelled']]);
        $orders = $this->orderService->listAll($request->user(), $data['status'] ?? null);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = $this->orderService->findById($request->user(), $id);

        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->user(), $request->validated('address_id'));

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function pay(Request $request, int $id)
    {
        $order = $this->orderService->decide($request->user(), $id, true);

        return new OrderResource($order);
    }

    public function status(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', 'in:paid,shipped,delivered,cancelled']]);

        return new OrderResource($this->orderService->transition($request->user(), $id, $data['status']));
    }

    public function cancel(Request $request, int $id)
    {
        $order = $this->orderService->decide($request->user(), $id, false);

        return new OrderResource($order);
    }
}
