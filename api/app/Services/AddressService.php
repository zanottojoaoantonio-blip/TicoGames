<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AddressService
{
    public function listAll(User $user): Collection
    {
        $addresses = $user->addresses()->orderBy('id')->get();

        return $addresses;
    }

    public function findById(User $user, int $id): Address
    {
        $address = $user->addresses()->findOrFail($id);

        return $address;
    }

    public function create(User $user, array $data): Address
    {
        $address = $user->addresses()->create($data);

        return $address;
    }

    public function update(User $user, int $id, array $data): Address
    {
        $address = $this->findById($user, $id);
        $address->update($data);

        return $address;
    }

    public function delete(User $user, int $id): void
    {
        $address = $this->findById($user, $id);
        $address->delete();
    }
}
