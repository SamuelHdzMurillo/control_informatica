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
    <script>
        (function () {
            try {
                if (localStorage.getItem('sidebar') === 'collapsed') {
                    document.documentElement.classList.add('nav-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
<div class="nav-backdrop" data-close-nav hidden></div>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
        </div>
        <nav class="nav">
            <div class="nav-section">Principal</div>
            <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= h(url('dashboard.php')) ?>" title="Panel">
                <?= $icon('<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>') ?>
                <span class="nav-label">Panel</span>
            </a>
            <div class="nav-section">Operaciones</div>
            <a class="<?= $current === 'recibir.php' ? 'active' : '' ?>" href="<?= h(url('recibir.php')) ?>" title="Recibir equipo">
                <?= $icon('<path d="M12 5v14"/><path d="M5 12h14"/>') ?>
                <span class="nav-label">Recibir equipo</span>
            </a>
            <a class="<?= $current === 'personas.php' || $current === 'persona.php' ? 'active' : '' ?>" href="<?= h(url('personas.php')) ?>" title="Personas">
                <?= $icon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>') ?>
                <span class="nav-label">Personas</span>
            </a>
            <a class="<?= $current === 'inventario.php' || $current === 'bien.php' ? 'active' : '' ?>" href="<?= h(url('inventario.php')) ?>" title="Inventario">
                <?= $icon('<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 18v3"/>') ?>
                <span class="nav-label">Inventario</span>
            </a>
            <a class="<?= $current === 'consulta.php' ? 'active' : '' ?>" href="<?= h(url('consulta.php')) ?>" title="Consulta pública">
                <?= $icon('<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>') ?>
                <span class="nav-label">Consulta pública</span>
            </a>
            <?php if (isAdmin($user)): ?>
            <div class="nav-section">Administración</div>
            <a class="<?= $current === 'tecnicos.php' ? 'active' : '' ?>" href="<?= h(url('tecnicos.php')) ?>" title="Técnicos">
                <?= $icon('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') ?>
                <span class="nav-label">Técnicos</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-foot">
            <button type="button" class="sidebar-collapse" data-toggle-sidebar title="Retraer menú">
                <?= $icon('<polyline points="15 18 9 12 15 6"/>') ?>
                <span class="nav-label">Retraer menú</span>
            </button>
            <a class="nav-logout" href="<?= h(url('logout.php')) ?>" title="Cerrar sesión">
                <?= $icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') ?>
                <span class="nav-label">Cerrar sesión</span>
            </a>
        </div>
    </aside>
    <div class="main">
        <header class="topnav">
            <button type="button" class="nav-burger" data-toggle-sidebar title="Menú" aria-label="Menú">
                <?= $icon('<line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/>') ?>
            </button>
            <form class="topnav-search" action="<?= h(url('dashboard.php')) ?>" method="get">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input id="q-global" type="search" name="q" value="<?= h($qTop) ?>" placeholder="Buscar folio, serie, inventario o persona">
                <kbd>Ctrl+K</kbd>
            </form>
            <div class="user-menu" data-user-menu>
                <button type="button" class="user-menu-btn" data-user-menu-btn aria-haspopup="true" aria-expanded="false">
                    <span class="user-menu-meta">
                        <strong><?= h($user['nombre'] ?? 'Usuario') ?></strong>
                        <small><?= h(ucfirst((string) ($user['rol'] ?? ''))) ?></small>
                    </span>
                    <span class="avatar"><?= h(userInitial($user)) ?></span>
                </button>
                <div class="user-menu-panel" hidden>
                    <div class="user-menu-head">
                        <span class="avatar"><?= h(userInitial($user)) ?></span>
                        <div>
                            <strong><?= h($user['nombre'] ?? 'Usuario') ?></strong>
                            <small><?= h(ucfirst((string) ($user['rol'] ?? 'técnico'))) ?></small>
                        </div>
                    </div>
                    <a class="user-logout" href="<?= h(url('logout.php')) ?>">
                        <?= $icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') ?>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </header>
        <div class="content">
        <?php $flash = takeFlash(); if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'ok' ? 'alert-ok' : 'alert-error' ?>"><?= h($flash['message']) ?></div>
        <?php endif; ?>
