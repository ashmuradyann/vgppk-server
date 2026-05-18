<?php

return [
    'paths' => ['*'],  // Применять ко всем маршрутам

    'allowed_methods' => ['*'],  // Разрешить все HTTP методы

    'allowed_origins' => ['*'],  // Разрешить запросы с любых доменов

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],  // Разрешить все заголовки

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,  // Отключить передачу учетных данных
];
