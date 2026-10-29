<?php

namespace Database\Seeders;

// Usa o Model diretamente, sem factories ou funcionalidades de login.
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Contas fictícias para demonstração local; autenticação virá na etapa 8.
        $users = [
            ['name' => 'Admin TicoGames', 'email' => 'admin@ticogames.test', 'role' => 'admin'],
            ['name' => 'Cliente Exemplo', 'email' => 'customer@ticogames.test', 'role' => 'customer'],
        ];

        foreach ($users as $data) {
            // Busca pelo email ou prepara um novo usuário, ainda sem salvá-lo.
            $user = User::firstOrNew(['email' => $data['email']]);

            // Preserva nome, senha e perfil de uma conta que já exista.
            if ($user->exists) {
                continue;
            }

            // Atribui os campos explicitamente; role continua fora de $fillable.
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = 'password'; // O cast do User salva o hash, não o texto.
            $user->role = $data['role'];
            $user->save(); // Grava a nova conta no banco.
        }
    }
}
