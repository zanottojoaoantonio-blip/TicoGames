<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Acrescenta o perfil sem modificar a migration padrão de usuários.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Limita os valores aceitos no banco e define o padrão para novos registros.
            $table->enum('role', ['customer', 'admin'])->default('customer');
        });
    }

    // Desfaz somente a coluna acrescentada por esta migration.
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
