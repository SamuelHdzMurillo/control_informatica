<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$q = trim($_GET['q'] ?? '');
$bienes = listBienes($pdo, $q);
$pageTitle = 'Inventario';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Bienes</p>
        <h1>Inventario de equipos</h1>
        <p class="lead">Cada equipo queda en catálogo. Si vuelve a ingresar, se reutiliza el perfil y se ve su historial de servicios.</p>
    </div>
    <a class="btn btn-ok" href="<?= h(url('recibir.php')) ?>">+ Recibir equipo</a>
</div>

<section class="card">
    <form class="toolbar" method="get">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Serie, inventario, marca, modelo o persona">
        <button class="btn btn-primary" type="submit">Buscar</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>Equipo</th>
                <th>Serie</th>
                <th>Inventario</th>
                <th>Persona</th>
                <th>Servicios</th>
                <th>Último ingreso</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$bienes): ?>
            <tr><td colspan="7">Aún no hay equipos en inventario. Se crean al recibir uno.</td></tr>
        <?php endif; ?>
        <?php foreach ($bienes as $b): ?>
            <tr>
                <td>
                    <?= h($b['tipo_equipo']) ?><br>
                    <small><?= h($b['marca'] . ' ' . $b['modelo']) ?></small>
                </td>
                <td><?= h($b['numero_serie'] ?: '—') ?></td>
                <td><?= h($b['numero_inventario'] ?: '—') ?></td>
                <td>
                    <?php if (!empty($b['persona_id'])): ?>
                        <a class="btn btn-sm btn-ghost" href="<?= h(url('persona.php?id=' . $b['persona_id'])) ?>"><?= h($b['persona_nombre'] ?: 'Ver perfil') ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= (int) $b['servicios'] ?></td>
                <td><?= h(formatFecha($b['ultimo_servicio'] ?? null, true)) ?></td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('bien.php?id=' . $b['id'])) ?>">Historial</a>
                        <a class="btn btn-sm btn-ok" href="<?= h(url('recibir.php?bien=' . $b['id'])) ?>">Nuevo servicio</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
