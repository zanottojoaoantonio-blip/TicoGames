<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$key = config('app.key');
assert(is_string($key) && $key !== '', 'A chave da aplicação deve estar configurada.');
assert(Crypt::decryptString(
    Crypt::encryptString('compose-bootstrap')
) === 'compose-bootstrap', 'A chave deve permitir criptografar e descriptografar.');

if (! env('APP_KEY')) {
    assert(is_file(storage_path('app.key')), 'A chave gerada deve estar no storage persistente.');
    assert($key === trim(file_get_contents(storage_path('app.key'))), 'Artisan deve carregar a chave persistida.');
}

$counts = [];
foreach (['users' => 4, 'products' => 30, 'orders' => 5] as $table => $minimum) {
    $counts[$table] = DB::table($table)->count();
    assert($counts[$table] >= $minimum, "A carga de demonstração deve preencher {$table}.");
}

echo json_encode(['key_fingerprint' => hash('sha256', $key), 'counts' => $counts]), PHP_EOL;
