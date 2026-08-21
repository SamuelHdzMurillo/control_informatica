<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$prestamos = listPrestamos($pdo, $q, $estado);
$hayFiltros = $q !== '' || $estado !== '';
$pageTitle = 'Préstamos';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Inventario interno</p>
        <h1>Préstamos</h1>
    </div>
    <a class="btn btn-ok" href="<?= h(url('prestar.php')) ?>">+ Prestar</a>
</div>

<section class="card inv-card">
    <form method="get" class="inv-bar" style="margin-bottom:12px">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Folio, persona, área o técnico">
        <select name="estado">
            <option value="">Todos</option>
            <option value="abiertos" <?= $estado === 'abiertos' ? 'selected' : '' ?>>Activos (todos)</option>
            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>En tiempo</option>
            <option value="vence_pronto" <?= $estado === 'vence_pronto' ? 'selected' : '' ?>>Vencen pronto</option>
            <option value="vencido" <?= $estado === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
            <option value="cerrado" <?= $estado === 'cerrado' ? 'selected' : '' ?>>Devueltos</option>
        </select>
        <button class="btn btn-primary" type="submit">Buscar</button>
        <?php if ($hayFiltros): ?>
            <a class="btn btn-ghost" href="<?= h(url('prestamos.php')) ?>">Quitar</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
    <table class="inv-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Persona</th>
                <th>Compromiso</th>
                <th>Bienes</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$prestamos): ?>
            <tr><td colspan="5"><?= $hayFiltros ? 'Ningún préstamo coincide.' : 'Aún no hay préstamos.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($prestamos as $p): ?>
            <tr class="<?= $p['estado_visual'] === 'vencido' ? 'row-vencido' : ($p['estado_visual'] === 'vence_pronto' ? 'row-pronto' : '') ?>">
                <td>
                    <a href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>"><strong><?= h($p['folio']) ?></strong></a>
                    <span class="badge st-<?= h($p['estado_visual']) ?>"><?= h($p['estado_visual_label']) ?></span>
                    <small><?= h(formatFecha($p['fecha_prestamo'], true)) ?></small>
                </td>
                <td>
                    <a href="<?= h(url('persona.php?id=' . $p['persona_id'])) ?>"><?= h($p['persona_nombre'] ?: '—') ?></a>
                    <small><?= h($p['persona_area'] ?: 'Sin área') ?></small>
                </td>
                <td><?= h(formatFecha($p['fecha_compromiso'])) ?></td>
                <td>
                    <?= (int) $p['items_fuera'] ?> fuera
                    <small>de <?= (int) $p['items_total'] ?></small>
                </td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>">Ver</a>
                        <a class="btn btn-sm btn-ghost" href="<?= h(url('recibo_prestamo.php?id=' . $p['id'])) ?>">Recibí</a>
                        <?php if ($p['estado'] === 'activo'): ?>
                            <a class="btn btn-sm btn-ok" href="<?= h(url('prestamo.php?id=' . $p['id'] . '#devolver')) ?>">Devolver</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
