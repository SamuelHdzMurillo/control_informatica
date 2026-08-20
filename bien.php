<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();
$bien = getBien($pdo, $id);
if (!$bien) {
    flash('error', 'El equipo no existe en el inventario.');
    redirect('inventario.php');
}
$dueno = !empty($bien['persona_id']) ? getPersona($pdo, (int) $bien['persona_id']) : null;
$servicios = serviciosDeBien($pdo, $id);
$ultimo = $servicios[0] ?? null;
$pageTitle = trim($bien['marca'] . ' ' . $bien['modelo']);
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow">Perfil de equipo</p>
        <h1><?= h($bien['marca'] . ' ' . $bien['modelo']) ?></h1>
        <p class="lead"><?= h($bien['tipo_equipo']) ?> · Serie <?= h($bien['numero_serie'] ?: 's/n') ?> · Inventario <?= h($bien['numero_inventario'] ?: 's/n') ?></p>
    </div>
    <a class="btn btn-ok" href="<?= h(url('recibir.php?bien=' . $id)) ?>">+ Nuevo servicio</a>
</div>

<div class="sections">
<div class="kv-cards">
    <section class="section">
        <div class="section-head">
            <span class="section-num">1</span>
            <div>
                <h2>Identificación</h2>
                <p class="hint">Datos fijos del bien en inventario.</p>
            </div>
        </div>
        <dl>
            <div class="kv-item"><dt>Tipo</dt><dd><?= h($bien['tipo_equipo']) ?></dd></div>
            <div class="kv-item"><dt>Marca</dt><dd><?= h($bien['marca']) ?></dd></div>
            <div class="kv-item"><dt>Modelo</dt><dd><?= h($bien['modelo']) ?></dd></div>
            <div class="kv-item"><dt>Número de serie</dt><dd><?= h($bien['numero_serie'] ?: '—') ?></dd></div>
            <div class="kv-item"><dt>Número de inventario</dt><dd><?= h($bien['numero_inventario'] ?: '—') ?></dd></div>
            <div class="kv-item"><dt>Servicios</dt><dd><?= count($servicios) ?></dd></div>
            <?php if ($ultimo): ?>
                <div class="kv-item"><dt>Estado físico (último ingreso)</dt><dd><?= h($ultimo['estado_fisico'] ?: '—') ?></dd></div>
                <div class="kv-item"><dt>Última falla</dt><dd><?= h(trim(($ultimo['tipo_problema'] ?? '') . (!empty($ultimo['problema_reportado']) ? ' · ' . $ultimo['problema_reportado'] : '')) ?: '—') ?></dd></div>
                <div class="kv-item wide"><dt>Accesorios recibidos</dt><dd><?= h($ultimo['accesorios'] ?: '—') ?></dd></div>
                <?php if (!empty($ultimo['observaciones'])): ?>
                    <div class="kv-item wide"><dt>Observaciones</dt><dd><?= h($ultimo['observaciones']) ?></dd></div>
                <?php endif; ?>
            <?php endif; ?>
        </dl>
    </section>
    <section class="section">
        <div class="section-head">
            <span class="section-num">2</span>
            <div>
                <h2>Persona asociada</h2>
                <p class="hint">Último perfil vinculado a este equipo.</p>
            </div>
        </div>
        <?php if ($dueno): ?>
            <dl>
                <div class="kv-item"><dt>Nombre</dt><dd>
                    <a class="btn btn-sm btn-ghost" href="<?= h(url('persona.php?id=' . $dueno['id'])) ?>"><?= h($dueno['nombre']) ?></a>
                </dd></div>
                <div class="kv-item"><dt>Área</dt><dd><?= h($dueno['area_dependencia'] ?: '—') ?></dd></div>
                <div class="kv-item"><dt>Teléfono</dt><dd><?= h($dueno['telefono'] ?: '—') ?></dd></div>
            </dl>
        <?php else: ?>
            <p class="lead">Sin persona asociada.</p>
        <?php endif; ?>
    </section>
</div>

<section class="section">
    <div class="section-head">
        <span class="section-num">3</span>
        <div>
            <h2>Historial de servicios</h2>
            <p class="hint">Cada folio es un ingreso de este mismo equipo.</p>
        </div>
    </div>
    <?php if (!$servicios): ?>
        <p class="lead">Este equipo aún no tiene servicios.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Entregado por</th>
                    <th>Problema</th>
                    <th>Recepción</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servicios as $eq): ?>
                <tr>
                    <td><strong><?= h($eq['folio']) ?></strong></td>
                    <td><?= h($eq['entregado_por']) ?></td>
                    <td><?= h($eq['problema_reportado']) ?></td>
                    <td><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></td>
                    <td><span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span></td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
