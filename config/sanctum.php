<?php

return [
    // Origens locais reconhecidas como frontend da loja, incluindo a porta do Vite.
    // As demais opções são herdadas da configuração padrão do pacote.
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:5173,localhost:8000,localhost,127.0.0.1:8000')),
];
