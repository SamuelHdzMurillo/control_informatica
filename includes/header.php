<?php
$user = currentUser();
$pageTitle = $pageTitle ?? APP_NAME;
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$qTop = trim($_GET['q'] ?? '');
$icon = static function (string $path): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> · <?= h(ORG_SHORT) ?></title>
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="icon" href="<?= h(logoUrl()) ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
        </div>
        <nav class="nav">
            <div class="nav-section">Principal</div>
            <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= h(url('dashboard.php')) ?>">
                <?= $icon('<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>') ?>
                Panel
            </a>
            <div class="nav-section">Operaciones</div>
            <a class="<?= $current === 'recibir.php' ? 'active' : '' ?>" href="<?= h(url('recibir.php')) ?>">
                <?= $icon('<path d="M12 5v14"/><path d="M5 12h14"/>') ?>
                Recibir equipo
            </a>
            <a class="<?= $current === 'personas.php' || $current === 'persona.php' ? 'active' : '' ?>" href="<?= h(url('personas.php')) ?>">
                <?= $icon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>') ?>
                Personas
            </a>
            <a class="<?= $current === 'inventario.php' || $current === 'bien.php' ? 'active' : '' ?>" href="<?= h(url('inventario.php')) ?>">
                <?= $icon('<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 18v3"/>') ?>
                Inventario
            </a>
            <a class="<?= $current === 'consulta.php' ? 'active' : '' ?>" href="<?= h(url('consulta.php')) ?>">
                <?= $icon('<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>') ?>
                Consulta pública
            </a>
            <div class="nav-section">Sesión</div>
            <a href="<?= h(url('logout.php')) ?>">
                <?= $icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') ?>
                Cerrar sesión
            </a>
        </nav>
        <?php if ($user): ?>
            <p class="who"><?= h($user['nombre']) ?><br><?= h(ucfirst((string) $user['rol'])) ?></p>
        <?php endif; ?>
    </aside>
    <div class="main">
        <header class="topnav">
            <form class="topnav-search" action="<?= h(url('dashboard.php')) ?>" method="get">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input id="q-global" type="search" name="q" value="<?= h($qTop) ?>" placeholder="Buscar folio, serie, inventario o persona">
                <kbd>Ctrl+K</kbd>
            </form>
            <div class="userchip">
                <span><?= h($user['nombre'] ?? 'Usuario') ?></span>
                <span class="avatar"><?= h(userInitial($user)) ?></span>
            </div>
        </header>
        <div class="content">
        <?php $flash = takeFlash(); if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-error' ?>"><?= h($flash['message']) ?></div>
        <?php endif; ?>
