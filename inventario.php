<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$q = trim($_GET['q'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$marca = trim($_GET['marca'] ?? '');
$area = trim($_GET['area'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$ident = trim($_GET['ident'] ?? '');
$historial = trim($_GET['historial'] ?? '');

$filters = [
    'tipo' => $tipo,
    'marca' => $marca,
    'area' => $area,
    'estado' => $estado,
    'ident' => $ident,
    'historial' => $historial,
];
$bienes = listBienes($pdo, $q, $filters);
$marcas = listBienesMarcas($pdo);
$areas = listBienesAreas($pdo);
$resumen = inventarioResumen($pdo);
$hayFiltros = $q !== '' || $tipo !== '' || $marca !== '' || $area !== '' || $estado !== '' || $ident !== '' || $historial !== '';
$nFiltros = (int) array_sum(array_map(static fn($v) => $v !== '' ? 1 : 0, [$tipo, $marca, $area, $estado, $ident, $historial]));
$pageTitle = 'Inventario';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Bienes</p>
        <h1>Inventario de equipos</h1>
        <p class="inv-summary">
            <b><?= (int) $resumen['total'] ?></b> en catálogo
            · <b class="n-orange"><?= (int) $resumen['en_soporte'] ?></b> en soporte
            · <b><?= (int) $resumen['con_inventario'] ?></b> con inventario
            · mostrando <?= count($bienes) ?>
        </p>
    </div>
    <a class="btn btn-ok" href="<?= h(url('recibir.php')) ?>">+ Recibir equipo</a>
</div>

<section class="card inv-card">
    <form method="get" class="inv-form" data-filters-open="<?= $hayFiltros ? '1' : '0' ?>">
        <div class="inv-bar">
            <input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar serie, inventario, marca, persona o folio">
            <button class="btn btn-ghost" type="button" data-toggle-filters>
                Filtros<?= $nFiltros ? ' · ' . $nFiltros : '' ?>
            </button>
            <button class="btn btn-primary" type="submit">Buscar</button>
            <?php if ($hayFiltros): ?>
                <a class="btn btn-ghost" href="<?= h(url('inventario.php')) ?>">Quitar</a>
            <?php endif; ?>
        </div>
        <div class="inv-filters" data-filters-panel <?= $nFiltros ? '' : 'hidden' ?>>
        <select name="tipo">
            <option value="">Tipo</option>
            <?php foreach (tiposEquipo() as $t): ?>
                <option value="<?= h($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= h($t) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="marca">
            <option value="">Marca</option>
            <?php foreach ($marcas as $m): ?>
                <option value="<?= h($m) ?>" <?= $marca === $m ? 'selected' : '' ?>><?= h($m) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="area">
            <option value="">Área</option>
            <?php foreach ($areas as $a): ?>
                <option value="<?= h($a) ?>" <?= $area === $a ? 'selected' : '' ?>><?= h($a) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="estado">
            <option value="">Estado</option>
            <?php foreach (estadosEquipo() as $k => $label): ?>
                <option value="<?= h($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="ident">
            <option value="">Identificación</option>
            <option value="con_inventario" <?= $ident === 'con_inventario' ? 'selected' : '' ?>>Con inventario</option>
            <option value="sin_inventario" <?= $ident === 'sin_inventario' ? 'selected' : '' ?>>Sin inventario</option>
            <option value="con_serie" <?= $ident === 'con_serie' ? 'selected' : '' ?>>Con serie</option>
            <option value="sin_serie" <?= $ident === 'sin_serie' ? 'selected' : '' ?>>Sin serie</option>
        </select>
        <select name="historial">
            <option value="">Historial</option>
            <option value="en_soporte" <?= $historial === 'en_soporte' ? 'selected' : '' ?>>En soporte</option>
            <option value="entregados" <?= $historial === 'entregados' ? 'selected' : '' ?>>Entregados</option>
            <option value="con_servicios" <?= $historial === 'con_servicios' ? 'selected' : '' ?>>Con servicios</option>
            <option value="sin_servicios" <?= $historial === 'sin_servicios' ? 'selected' : '' ?>>Sin servicios</option>
        </select>
        </div>
    </form>

    <div class="table-wrap">
    <table class="inv-table">
        <thead>
            <tr>
                <th>Equipo</th>
                <th>Responsable</th>
                <th>Último servicio</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$bienes): ?>
            <tr><td colspan="4"><?= $hayFiltros ? 'Ningún equipo coincide con los filtros.' : 'Aún no hay equipos en inventario. Se crean al recibir uno.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($bienes as $b): ?>
            <?php
            $servicios = (int) ($b['servicios'] ?? 0);
            $ids = [];
            $ids[] = $b['numero_inventario'] ? 'Inv. ' . $b['numero_inventario'] : 'Sin inventario';
            $ids[] = $b['numero_serie'] ? 'Serie ' . $b['numero_serie'] : 'Sin serie';
            if (!empty($b['ultimo_estado_fisico'])) {
                $ids[] = $b['ultimo_estado_fisico'];
            }
            $ids[] = $servicios === 1 ? '1 servicio' : $servicios . ' servicios';
            $detalle = [];
            if (!empty($b['ultimo_tipo_problema'])) {
                $detalle[] = $b['ultimo_tipo_problema'];
            }
            if (!empty($b['ultimo_problema'])) {
                $detalle[] = $b['ultimo_problema'];
            }
            ?>
            <tr>
                <td>
                    <strong><?= h($b['tipo_equipo']) ?></strong>
                    <?= h(trim($b['marca'] . ' ' . $b['modelo'])) ?>
                    <small><?= h(implode(' · ', $ids)) ?></small>
                </td>
                <td>
                    <?php if (!empty($b['persona_id'])): ?>
                        <a href="<?= h(url('persona.php?id=' . $b['persona_id'])) ?>"><?= h($b['persona_nombre'] ?: 'Ver perfil') ?></a>
                        <?php if (!empty($b['persona_area']) || !empty($b['persona_telefono'])): ?>
                            <small><?= h(trim(($b['persona_area'] ?? '') . (!empty($b['persona_telefono']) ? ' · ' . $b['persona_telefono'] : ''))) ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($b['ultimo_folio'])): ?>
                        <a href="<?= h(url('equipo.php?id=' . $b['ultimo_equipo_id'])) ?>"><strong><?= h($b['ultimo_folio']) ?></strong></a>
                        <span class="badge st-<?= h($b['ultimo_estado']) ?>"><?= h(estadoLabel((string) $b['ultimo_estado'])) ?></span>
                        <small><?= h(formatFecha($b['ultimo_servicio'] ?? null, true)) ?><?= $detalle ? ' · ' . h(implode(' · ', $detalle)) : '' ?></small>
                    <?php else: ?>
                        <small>Sin servicios</small>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('bien.php?id=' . $b['id'])) ?>">Ver</a>
                        <a class="btn btn-sm btn-ok" href="<?= h(url('recibir.php?bien=' . $b['id'])) ?>">Servicio</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
