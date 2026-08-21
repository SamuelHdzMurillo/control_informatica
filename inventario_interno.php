<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$stats = statsInventarioInterno($pdo);
$vencidos = listPrestamos($pdo, '', 'vencido');
$pronto = listPrestamos($pdo, '', 'vence_pronto');
$pageTitle = 'Inventario interno';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Informática</p>
        <h1>Inventario interno</h1>
        <p class="lead">Bienes del área y control de préstamos</p>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('bienes_internos.php')) ?>">Ver bienes</a>
        <a class="btn btn-ok" href="<?= h(url('prestar.php')) ?>">+ Prestar</a>
    </div>
</div>

<section class="stats">
    <a class="stat n-green" href="<?= h(url('bienes_internos.php?estado=disponible')) ?>">
        <span>Disponibles</span>
        <b><?= (int) $stats['disponibles'] ?></b>
        <small>Listos para prestar</small>
    </a>
    <a class="stat n-info" href="<?= h(url('bienes_internos.php?estado=prestado')) ?>">
        <span>Prestados</span>
        <b><?= (int) $stats['prestados'] ?></b>
        <small><?= (int) $stats['activos'] ?> préstamo<?= (int) $stats['activos'] === 1 ? '' : 's' ?> activo<?= (int) $stats['activos'] === 1 ? '' : 's' ?></small>
    </a>
    <a class="stat n-danger" href="<?= h(url('prestamos.php?estado=vencido')) ?>">
        <span>Vencidos</span>
        <b><?= (int) $stats['vencidos'] ?></b>
        <small>Ya pasó la fecha de devolución</small>
    </a>
    <a class="stat n-orange" href="<?= h(url('prestamos.php?estado=vence_pronto')) ?>">
        <span>Vencen pronto</span>
        <b><?= (int) $stats['vence_pronto'] ?></b>
        <small>Hoy o mañana</small>
    </a>
</section>

<?php if ($vencidos): ?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Préstamos vencidos</h2>
            <p class="hint">Hay material fuera de tiempo. Conviene localizar a la persona y registrarlo al devolverlo.</p>
        </div>
        <a class="btn btn-sm btn-ghost" href="<?= h(url('prestamos.php?estado=vencido')) ?>">Ver todos</a>
    </div>
    <div class="table-wrap">
    <table class="inv-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Persona</th>
                <th>Compromiso</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($vencidos as $p): ?>
            <tr class="row-vencido">
                <td>
                    <a href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>"><strong><?= h($p['folio']) ?></strong></a>
                    <span class="badge st-vencido"><?= h($p['estado_visual_label']) ?></span>
                    <small><?= (int) $p['items_fuera'] ?> fuera · <?= (int) $p['items_total'] ?> en el recibí</small>
                </td>
                <td>
                    <a href="<?= h(url('persona.php?id=' . $p['persona_id'])) ?>"><?= h($p['persona_nombre'] ?: 'Ver perfil') ?></a>
                    <small><?= h($p['persona_area'] ?: 'Sin área') ?></small>
                </td>
                <td><?= h(formatFecha($p['fecha_compromiso'])) ?></td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>">Devolver</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php endif; ?>

<?php if ($pronto): ?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Vencen hoy o mañana</h2>
            <p class="hint">Aviso previo para pedir el material a tiempo.</p>
        </div>
    </div>
    <div class="table-wrap">
    <table class="inv-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Persona</th>
                <th>Compromiso</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pronto as $p): ?>
            <tr class="row-pronto">
                <td>
                    <a href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>"><strong><?= h($p['folio']) ?></strong></a>
                    <span class="badge st-vence_pronto"><?= h($p['estado_visual_label']) ?></span>
                    <small><?= (int) $p['items_fuera'] ?> bien<?= (int) $p['items_fuera'] === 1 ? '' : 'es' ?> fuera</small>
                </td>
                <td>
                    <a href="<?= h(url('persona.php?id=' . $p['persona_id'])) ?>"><?= h($p['persona_nombre'] ?: 'Ver perfil') ?></a>
                    <small><?= h($p['persona_area'] ?: 'Sin área') ?></small>
                </td>
                <td><?= h(formatFecha($p['fecha_compromiso'])) ?></td>
                <td>
                    <a class="btn btn-sm btn-primary" href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>">Ver</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php endif; ?>

<?php if (!$vencidos && !$pronto): ?>
<section class="card">
    <p class="empty-state">
        <?php if ((int) $stats['bienes'] === 0): ?>
            Aún no hay bienes internos. Empiece por <a href="<?= h(url('bienes_internos.php')) ?>">darlos de alta</a>.
        <?php else: ?>
            No hay préstamos vencidos ni por vencer. El catálogo tiene <?= (int) $stats['bienes'] ?> bien<?= (int) $stats['bienes'] === 1 ? '' : 'es' ?>.
        <?php endif; ?>
    </p>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
