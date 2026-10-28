<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e o tipo do relacionamento com o usuário.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Representa um endereço salvo pelo cliente, separado do endereço de um pedido.
class Address extends Model
{
    /**
     * Campos que podem ser preenchidos em conjunto.
     * CEP e número permanecem como texto para aceitar zeros iniciais e letras.
     * O complemento será opcional, com coluna nullable na migration.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', // Identificador do usuário que cadastrou o endereço.
        'recipient_name', // Nome da pessoa que receberá a entrega.
        'postal_code', // CEP, preservando zeros à esquerda.
        'street', // Nome da rua ou avenida.
        'number', // Número do imóvel, permitindo valores como 12A ou s/n.
        'complement', // Informação adicional, como apartamento ou bloco.
        'neighborhood', // Bairro.
        'city', // Cidade.
        'state', // Estado, como SP ou RJ.
    ];

    // Cada endereço pertence a um usuário.
    // O Eloquent relaciona addresses.user_id a users.id por convenção.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
