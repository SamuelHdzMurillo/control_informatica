<?php

require_once __DIR__ . '/includes/init.php';

if (dbInstalled()) {
    redirect(currentUser() ? 'dashboard.php' : 'login.php');
}

$error = '';
$ok = false;

function installPdo(string $host, string $port, string $user, string $pass): PDO
{
    return new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4',
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function writeDbLocal(string $host, string $port, string $name, string $user, string $pass): void
{
    $export = static function (string $value): string {
        return var_export($value, true);
    };
    $code = "<?php\n"
        . 'define(\'DB_HOST\', ' . $export($host) . ");\n"
        . 'define(\'DB_PORT\', ' . $export($port) . ");\n"
        . 'define(\'DB_NAME\', ' . $export($name) . ");\n"
        . 'define(\'DB_USER\', ' . $export($user) . ");\n"
        . 'define(\'DB_PASS\', ' . $export($pass) . ");\n";
    if (file_put_contents(__DIR__ . '/db_local.php', $code) === false) {
        throw new RuntimeException('No se pudo guardar db_local.php. Revise permisos de la carpeta.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? 'control_informatica');
    $dbUser = trim($_POST['db_user'] ?? 'root');
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $nombre = trim($_POST['nombre'] ?? 'Administrador');
    $usuario = trim($_POST['usuario'] ?? 'admin');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || strlen($password) < 6) {
        $error = 'El usuario es obligatorio y la contraseña debe tener al menos 6 caracteres.';
    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
        $error = 'El nombre de la base de datos solo puede tener letras, números y guion bajo.';
    } else {
        try {
            $root = installPdo($dbHost, $dbPort, $dbUser, $dbPass);
            $root->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $root->exec('USE `' . $dbName . '`');
            $pdo = $root;
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(120) NOT NULL,
                    usuario VARCHAR(60) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    rol VARCHAR(30) NOT NULL DEFAULT 'tecnico',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS personas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(160) NOT NULL,
                    area_dependencia VARCHAR(160) DEFAULT NULL,
                    telefono VARCHAR(40) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS bienes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    persona_id INT DEFAULT NULL,
                    tipo_equipo VARCHAR(80) NOT NULL,
                    marca VARCHAR(120) NOT NULL,
                    modelo VARCHAR(120) NOT NULL,
                    numero_serie VARCHAR(120) DEFAULT NULL,
                    numero_inventario VARCHAR(120) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_bienes_persona FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS equipos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    folio VARCHAR(20) NOT NULL UNIQUE,
                    persona_id INT DEFAULT NULL,
                    bien_id INT DEFAULT NULL,
                    marca VARCHAR(120) NOT NULL,
                    modelo VARCHAR(120) NOT NULL,
                    numero_serie VARCHAR(120) DEFAULT NULL,
                    numero_inventario VARCHAR(120) DEFAULT NULL,
                    tipo_equipo VARCHAR(80) NOT NULL,
                    entregado_por VARCHAR(160) NOT NULL,
                    area_dependencia VARCHAR(160) DEFAULT NULL,
                    telefono VARCHAR(40) DEFAULT NULL,
                    fecha_recepcion DATETIME NOT NULL,
                    problema_reportado VARCHAR(255) NOT NULL,
                    tipo_problema VARCHAR(80) NOT NULL,
                    descripcion_falla TEXT NOT NULL,
                    estado_fisico VARCHAR(80) NOT NULL,
                    accesorios TEXT DEFAULT NULL,
                    observaciones TEXT DEFAULT NULL,
                    trabajo_realizado TEXT DEFAULT NULL,
                    oficio_path VARCHAR(255) DEFAULT NULL,
                    estado VARCHAR(40) NOT NULL DEFAULT 'recibido',
                    recibido_por VARCHAR(120) DEFAULT NULL,
                    entregado_a VARCHAR(160) DEFAULT NULL,
                    fecha_entrega DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS fotos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    equipo_id INT NOT NULL,
                    ruta VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_fotos_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS bitacora (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    equipo_id INT NOT NULL,
                    estado VARCHAR(40) NOT NULL,
                    comentario TEXT DEFAULT NULL,
                    visible_publico TINYINT(1) NOT NULL DEFAULT 1,
                    usuario_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_bitacora_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
                    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $exists = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = ?');
            $exists->execute([$usuario]);
            if ((int) $exists->fetchColumn() === 0) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nombre ?: 'Administrador', $usuario, $hash, 'admin']);
            }
            writeDbLocal($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
            ensureUploadDirs();
            $ok = true;
        } catch (Throwable $e) {
            $error = 'No se pudo conectar o crear la base. En XAMPP inicie MySQL; si usa Docker, ponga usuario y contraseña de ese servidor. ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación · <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="icon" href="<?= h(logoUrl()) ?>">
</head>
<body class="auth-body">
    <main class="auth-card" style="width:min(560px,100%)">
        <img class="auth-logo" src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
        <p class="eyebrow"><?= h(ORG_NAME) ?></p>
        <h1>Instalar sistema</h1>
        <p class="lead">Se creará la base de datos y el usuario administrador. Si MySQL pide contraseña, indíquela abajo.</p>
        <?php if ($ok): ?>
            <div class="alert alert-ok">Instalación lista. Ya puede iniciar sesión.</div>
            <a class="btn btn-primary" href="<?= h(url('login.php')) ?>">Ir al acceso</a>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
            <form method="post" class="stack">
                <h2>MySQL</h2>
                <label>Servidor
                    <input type="text" name="db_host" value="<?= h($_POST['db_host'] ?? '127.0.0.1') ?>" required>
                </label>
                <label>Puerto
                    <input type="text" name="db_port" value="<?= h($_POST['db_port'] ?? '3306') ?>" required>
                </label>
                <label>Base de datos
                    <input type="text" name="db_name" value="<?= h($_POST['db_name'] ?? 'control_informatica') ?>" required>
                </label>
                <label>Usuario MySQL
                    <input type="text" name="db_user" value="<?= h($_POST['db_user'] ?? 'root') ?>" required>
                </label>
                <label>Contraseña MySQL
                    <input type="password" name="db_pass" value="<?= h($_POST['db_pass'] ?? '') ?>">
                </label>
                <h2>Administrador del sistema</h2>
                <label>Nombre
                    <input type="text" name="nombre" value="<?= h($_POST['nombre'] ?? 'Administrador') ?>" required>
                </label>
                <label>Usuario
                    <input type="text" name="usuario" value="<?= h($_POST['usuario'] ?? 'admin') ?>" required>
                </label>
                <label>Contraseña
                    <input type="password" name="password" minlength="6" required>
                </label>
                <button class="btn btn-primary" type="submit">Crear sistema</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
