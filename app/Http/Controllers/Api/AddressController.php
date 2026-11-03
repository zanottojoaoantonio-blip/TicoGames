<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private AddressService $addressService
    ) {}

    public function index(Request $request)
    {
        $addresses = $this->addressService->listAll($request->user());

        return AddressResource::collection($addresses);
    }

    public function show(Request $request, int $id)
    {
        $address = $this->addressService->findById($request->user(), $id);

        return new AddressResource($address);
    }

    public function store(StoreAddressRequest $request)
    {
        $address = $this->addressService->create($request->user(), $request->validated());

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function update(UpdateAddressRequest $request, int $id)
    {
        $address = $this->addressService->update($request->user(), $id, $request->validated());

        return new AddressResource($address);
    }

    public function destroy(Request $request, int $id)
    {
        $this->addressService->delete($request->user(), $id);

        return response()->noContent();
    }
}
