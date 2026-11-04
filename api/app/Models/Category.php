<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e o tipo do relacionamento muitos para muitos.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Organiza os produtos em categorias, como Jogos e Consoles.
class Category extends Model
{
    /**
     * Permite preencher o nome da categoria em conjunto, por exemplo com create().
     * A unicidade do nome será definida na migration.
     *
     * @var list<string>
     */
    protected $fillable = ['name'];

    // Uma categoria tem vários produtos, e um produto pode ter várias categorias.
    // Por convenção, o Eloquent usa category_product com category_id e product_id.
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
