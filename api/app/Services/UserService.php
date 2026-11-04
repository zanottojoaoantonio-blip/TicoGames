<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function listAll(): Collection
    {
        $users = User::orderBy('id')->get();

        return $users;
    }

    public function findById(int $id): User
    {
        $user = User::findOrFail($id);

        return $user;
    }

    public function create(array $data): User
    {
        // Nem o cadastro público nem este CRUD aceitam promover o perfil.
        $user = new User($data);
        $user->role = 'customer';
        $user->save();

        return $user->refresh();
    }

    public function update(int $id, array $data): User
    {
        $user = $this->findById($id);
        $user->update($data);

        return $user;
    }

    public function delete(int $id): void
    {
        $user = $this->findById($id);

        if ($user->orders()->exists()) {
            throw ValidationException::withMessages(['user' => 'Uma conta com pedidos não pode ser excluída.']);
        }

        $user->delete();
    }
}
