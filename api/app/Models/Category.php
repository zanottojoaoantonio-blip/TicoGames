<?php

// Organiza esta classe no grupo de Models da aplicação.

namespace App\Models;

// Importa a base do Eloquent e o tipo do relacionamento muitos para muitos.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Uma categoria tem vários produtos; cada produto tem uma categoria.
    // O vínculo fica em products.category_id.
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
