<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => 'db',
        'port' => 3306,
        'database' => 'lostandfound_db',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'token_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET',
        'token_ttl_seconds' => 60 * 60 * 24,
        'java_enabled' => true,
        'java_bin' => 'java',
        'java_classpath' => __DIR__ . '/../../java-matcher/bin',
        'java_main_class' => 'com.lostandfound.MatchRunner',
        'cors_origin' => '*',
    ],
];