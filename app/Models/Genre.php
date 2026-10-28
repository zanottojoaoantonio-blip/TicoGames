<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa a base do Eloquent e o tipo do relacionamento muitos para muitos.
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Classifica jogos em gêneros, como Ação, Aventura e RPG.
class Genre extends Model
{
    /**
     * Permite preencher o nome do gênero em conjunto, por exemplo com create().
     * A unicidade do nome será definida na migration.
     *
     * @var list<string>
     */
    protected $fillable = ['name'];

    // Um gênero pode aparecer em vários jogos, e um jogo pode ter vários gêneros.
    // Por convenção, o Eloquent usa genre_product com genre_id e product_id.
    // Essa tabela de ligação não terá timestamps; consoles podem não ter gêneros.
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
