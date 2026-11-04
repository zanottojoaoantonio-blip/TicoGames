<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e o tipo do relacionamento um para muitos.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Identifica a plataforma do produto, como PlayStation 5 ou Nintendo Switch.
class Platform extends Model
{
    /**
     * Permite preencher o nome da plataforma em conjunto, por exemplo com create().
     * A unicidade do nome será definida na migration.
     *
     * @var list<string>
     */
    protected $fillable = ['name'];

    // Uma plataforma pode estar associada a vários jogos e consoles.
    // O Eloquent relaciona platforms.id a products.platform_id por convenção.
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
