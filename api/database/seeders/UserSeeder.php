<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin TicoGames', 'email' => 'admin@ticogames.test', 'role' => 'admin'],
            ['name' => 'Cliente Exemplo', 'email' => 'customer@ticogames.test', 'role' => 'customer'],
            ['name' => 'Ana Exemplo', 'email' => 'ana@ticogames.test', 'role' => 'customer'],
            ['name' => 'Bruno Exemplo', 'email' => 'bruno@ticogames.test', 'role' => 'customer'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            if ($user->exists) {
                continue;
            }

            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = 'password';
            $user->role = $data['role'];
            $user->save();
        }
    }
}
