<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e os tipos das relações com usuário e itens.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Guarda o pedido e a cópia do endereço de entrega escolhido na compra.
class Order extends Model
{
    /**
     * Campos que o backend poderá preencher ao criar ou atualizar o pedido.
     * total será calculado no servidor e status seguirá as transições permitidas.
     * Os campos shipping_* preservam o endereço, mesmo se o cadastro mudar.
     * A cópia do endereço será preenchida na criação do pedido, na etapa de CRUD.
     * O padrão pending_payment e os três valores de status serão definidos na migration.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', // Usuário que realizou a compra.
        'status', // pending_payment, paid ou cancelled.
        'total', // Soma dos preços unitários históricos multiplicados pelas quantidades.
        'shipping_recipient_name', // Cópia do nome do destinatário.
        'shipping_postal_code', // Cópia do CEP, preservando zeros iniciais.
        'shipping_street', // Cópia da rua ou avenida.
        'shipping_number', // Cópia do número do imóvel, como texto.
        'shipping_complement', // Cópia do complemento, que pode ficar vazio.
        'shipping_neighborhood', // Cópia do bairro.
        'shipping_city', // Cópia da cidade.
        'shipping_state', // Cópia do estado.
    ];

    /**
     * Mantém o total como texto decimal com duas casas, sem float.
     * Esse cast define o tipo de leitura; o cálculo do total será implementado depois.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    // Cada pedido pertence ao usuário que fez a compra.
    // O Eloquent relaciona orders.user_id a users.id por convenção.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Um pedido possui vários itens com os dados históricos da compra.
    // O Eloquent relaciona orders.id a order_items.order_id por convenção.
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
