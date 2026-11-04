<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e os tipos dos relacionamentos usados pelo produto.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Cada jogo por plataforma e cada console possui seu próprio preço e estoque.
class Product extends Model
{
    /**
     * Campos que podem ser preenchidos em conjunto pelo cadastro de produtos.
     * Categorias e gêneros são associados por relacionamentos, em tabelas de ligação.
     *
     * @var list<string>
     */
    protected $fillable = [
        'platform_id', // Plataforma desta versão do produto.
        'name', // Nome do jogo ou console.
        'description', // Informações sobre o produto.
        'price', // Preço; a coluna será decimal(10,2) na migration.
        'image_path', // Caminho da imagem, sem guardar o arquivo no banco.
        'stock', // Quantidade de unidades disponíveis.
        'is_active', // Indica se o produto está disponível no catálogo.
    ];

    /**
     * O preço retorna como texto decimal com duas casas, evitando float.
     * Estoque é inteiro e o indicador de ativo é booleano.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Um produto pode ter várias categorias, e uma categoria reúne vários produtos.
    // Por convenção, o Eloquent usa category_product com category_id e product_id.
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    // Cada produto pertence a uma plataforma.
    // O Eloquent relaciona products.platform_id a platforms.id por convenção.
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    // Jogos podem ter vários gêneros; consoles podem não ter nenhum.
    // A ligação usa genre_product, com genre_id e product_id, sem timestamps.
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    // Permite consultar as ocorrências deste produto em carrinhos.
    // Cada item se relaciona ao produto por cart_items.product_id.
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Liga o produto aos itens de pedidos que preservam seu nome e preço históricos.
    // Cada item se relaciona ao produto por order_items.product_id.
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
