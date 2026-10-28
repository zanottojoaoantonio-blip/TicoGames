<?php

// Organiza esta classe no grupo de Models da aplicação.
namespace App\Models;

// Importa os recursos do Laravel e os tipos dos relacionamentos.
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Authenticatable fornece a base de autenticação para clientes e administradores.
class User extends Authenticatable
{
    // Disponibiliza os recursos de notificações do Laravel para este usuário.
    use Notifiable;

    /**
     * Campos que podem ser preenchidos em conjunto, por exemplo com create().
     * O perfil role fica fora para impedir sua atribuição em massa no cadastro.
     * O padrão customer será definido na migration; admin será atribuído pelo servidor.
     * Esta lista controla a atribuição em massa, mas não valida os dados recebidos.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Impede que a senha e o token sejam incluídos em respostas JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * A data de verificação vira um objeto de data ao ser lida.
     * A senha atribuída recebe hash, usado depois para conferir o login.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Busca os endereços cujo user_id corresponde ao id deste usuário.
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // Relaciona o usuário ao seu carrinho por carts.user_id.
    // A restrição de um único carrinho será garantida na migration.
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    // Busca os pedidos cujo user_id corresponde ao id deste usuário.
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
