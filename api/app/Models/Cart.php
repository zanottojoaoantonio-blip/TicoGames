<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e os tipos dos relacionamentos do carrinho.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Guarda o carrinho de uma conta; visitantes usam localStorage no frontend.
class Cart extends Model
{
    /**
     * Permite preencher o identificador do usuário dono do carrinho.
     * Os produtos e suas quantidades ficam nos itens, na tabela cart_items.
     * A unicidade de user_id será definida na migration: um carrinho por usuário.
     *
     * @var list<string>
     */
    protected $fillable = ['user_id'];

    // Cada carrinho pertence a um usuário.
    // O Eloquent relaciona carts.user_id a users.id por convenção.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Um carrinho possui vários itens, cada um com produto e quantidade.
    // O Eloquent relaciona carts.id a cart_items.cart_id por convenção.
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
