<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        // Busca o cliente criado pelo UserSeeder, sem depender de um ID fixo.
        $customer = User::where('email', 'customer@ticogames.test')->firstOrFail();

        // Endereços inteiramente fictícios para demonstrar a seleção entre duas opções.
        $addresses = [
            ['street' => 'Rua de Exemplo', 'number' => '12A', 'complement' => null],
            ['street' => 'Avenida de Demonstração', 'number' => '200', 'complement' => 'Apartamento 10'],
        ];

        foreach ($addresses as $data) {
            // A relação já preenche user_id. Rua e número identificam o exemplo.
            // Os demais campos só são preenchidos quando o endereço é novo.
            $customer->addresses()->firstOrCreate(
                ['street' => $data['street'], 'number' => $data['number']],
                [
                    'recipient_name' => 'Cliente Exemplo',
                    'postal_code' => '00000-000',
                    'complement' => $data['complement'],
                    'neighborhood' => 'Bairro de Exemplo',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                ],
            );
        }
    }
}
