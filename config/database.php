
<?php

function getDatabaseConnection(): PDO
{
    $host = getenv('DB_HOST');
    $database = getenv('DB_DATABASE');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');

    if (!$host || !$database || !$username || $password === false) {
        throw new RuntimeException(
            'Database environment variables are missing.'
        );
    }

    $dsn = "mysql:host={$host};port=3306;dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
