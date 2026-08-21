<?php

function dbAvailable(): PDO
{
    static $root = null;
    if ($root instanceof PDO) {
        return $root;
    }
    $root = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $root;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $pdo;
}

function dbInstalled(): bool
{
    try {
        $pdo = db();
        $pdo->query('SELECT 1 FROM usuarios LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function requireInstalled(): void
{
    if (!dbInstalled()) {
        redirect('install.php');
    }
    ensureEquipoSchema(db());
    ensureInventarioInternoSchema(db());
}
?>
