<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
?>
