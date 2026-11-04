<?php

return [
    // Origem do proxy, que atende o Vue e a API com o mesmo host e porta.
    // As demais opções são herdadas da configuração padrão do pacote.
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),
];
