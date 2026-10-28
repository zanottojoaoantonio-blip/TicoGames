<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e o tipo das relações com pedido e produto.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Preserva o nome e o preço unitário do produto no momento da compra.
class OrderItem extends Model
{
    /**
     * Campos preenchidos pelo backend ao copiar os dados do produto para o pedido.
     * product_name e unit_price são valores salvos, sem atualização automática.
     * A cópia será preenchida na criação do pedido, na etapa de CRUD.
     * O par order_id e product_id será único na migration.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id', // Pedido ao qual este item pertence.
        'product_id', // Produto original do catálogo.
        'product_name', // Cópia do nome no momento da compra.
        'unit_price', // Cópia do preço de uma unidade no momento da compra.
        'quantity', // Quantidade de unidades compradas.
    ];

    /**
     * Retorna o preço como texto decimal com duas casas e a quantidade como inteiro.
     * A validação de quantidade positiva será implementada nas etapas próprias.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    // Cada item pertence a um pedido.
    // O Eloquent relaciona order_items.order_id a orders.id por convenção.
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Mantém o vínculo com o produto, sem substituir os dados históricos do item.
    // O Eloquent relaciona order_items.product_id a products.id por convenção.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
