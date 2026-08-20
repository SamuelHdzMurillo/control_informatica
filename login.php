<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();

if (currentUser()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE usuario = ? LIMIT 1');
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        redirect('dashboard.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso · <?= h(ORG_SHORT) ?></title>
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="icon" href="<?= h(logoUrl()) ?>">
</head>
<body class="auth-body">
    <main class="auth-card">
        <img class="auth-logo" src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
        <p class="eyebrow"><?= h(ORG_NAME) ?></p>
        <h1><?= h(APP_NAME) ?></h1>
        <p class="lead">Informática · ingrese para registrar equipos y dar seguimiento.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="stack">
            <label>Usuario
                <input type="text" name="usuario" required autofocus>
            </label>
            <label>Contraseña
                <input type="password" name="password" required>
            </label>
            <button class="btn btn-primary" type="submit">Entrar</button>
        </form>
        <p class="lead" style="margin-top:18px">¿Trajo un equipo? Consulte el avance con su folio.</p>
        <a class="btn btn-ghost" href="<?= h(url('consulta.php')) ?>">Consulta pública</a>
    </main>
</body>
</html>
