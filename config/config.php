<?php
return [
     
     
    'db' => [
        'host' => getenv('BARBERQ_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('BARBERQ_DB_PORT') ?: '3306',
        'name' => getenv('BARBERQ_DB_NAME') ?: 'barberq',
        'user' => getenv('BARBERQ_DB_USER') ?: 'root',
        'pass' => getenv('BARBERQ_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'BARBERQ',
        'timezone' => 'Asia/Jakarta',
        'open_hour' => '09:00',
        'close_hour' => '18:00',
        'slot_minutes' => 30,
    ],
];
