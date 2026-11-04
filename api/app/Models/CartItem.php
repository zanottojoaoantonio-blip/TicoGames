<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e o tipo das relações com carrinho e produto.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Representa a quantidade de um produto no carrinho, sem reservar estoque.
class CartItem extends Model
{
    /**
     * Campos que podem ser preenchidos em conjunto.
     * O preço do carrinho vem do produto, sem uma cópia neste item.
     * O par cart_id e product_id será único na migration: um item por produto no carrinho.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cart_id', // Carrinho ao qual este item pertence.
        'product_id', // Produto escolhido no catálogo.
        'quantity', // Quantidade de unidades deste produto no carrinho.
    ];

    /**
     * Converte a quantidade para inteiro; a validação de valor positivo virá depois.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    // Cada item pertence a um carrinho.
    // O Eloquent relaciona cart_items.cart_id a carts.id por convenção.
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    // Cada item aponta para um produto do catálogo.
    // O Eloquent relaciona cart_items.product_id a products.id por convenção.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
