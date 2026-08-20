<?php

define('APP_NAME', 'Soporte Técnico');
define('ORG_NAME', 'CECYTE Baja California Sur');
define('ORG_AREA', 'Informática');
define('ORG_SHORT', 'CECYTE BCS');
define('ORG_JEFE_INFORMATICA', 'Daniel Carillo Cortes');

if (is_file(__DIR__ . '/db_local.php')) {
    require __DIR__ . '/db_local.php';
} else {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'control_informatica');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('BASE_PATH', str_replace('\\', '/', dirname(__FILE__)));
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('MAX_PHOTO_BYTES', 8 * 1024 * 1024);
define('MAX_OFICIO_BYTES', 12 * 1024 * 1024);

$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptName = rtrim($scriptName, '/');
if ($scriptName === '/' || $scriptName === '\\' || $scriptName === '.') {
    $scriptName = '';
}
define('BASE_URL', $scriptName);

date_default_timezone_set('America/Mexico_City');
?>
