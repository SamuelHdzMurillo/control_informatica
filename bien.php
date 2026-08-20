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
$nServicios = count($servicios);
$titulo = trim($bien['marca'] . ' ' . $bien['modelo']);
$ultimaFalla = '';
if ($ultimo) {
    $ultimaFalla = trim(($ultimo['tipo_problema'] ?? '') . (!empty($ultimo['problema_reportado']) ? ' · ' . $ultimo['problema_reportado'] : ''));
}
$pageTitle = $titulo;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="ppl-head">
        <span class="ppl-avatar lg eq-avatar"><?= h(nombreIniciales($bien['tipo_equipo'] ?? '')) ?></span>
        <div>
            <p class="eyebrow">Perfil de equipo</p>
            <h1><?= h($titulo) ?></h1>
            <p class="page-sub"><?= h($bien['tipo_equipo']) ?> · Inv. <?= h($bien['numero_inventario'] ?: 's/n') ?> · Serie <?= h($bien['numero_serie'] ?: 's/n') ?></p>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('inventario.php')) ?>">Inventario</a>
        <a class="btn btn-ok" href="<?= h(url('recibir.php?bien=' . $id)) ?>">+ Nuevo servicio</a>
    </div>
</div>

<div class="eq-stats">
    <div class="stat">
        <span>Servicios</span>
        <b><?= $nServicios ?></b>
        <small><?= $nServicios === 1 ? 'Folio registrado' : 'Folios registrados' ?></small>
    </div>
    <div class="stat">
        <span>Último estado</span>
        <?php if ($ultimo): ?>
            <b class="eq-stat-badge"><span class="badge st-<?= h($ultimo['estado']) ?>"><?= h(estadoLabel($ultimo['estado'])) ?></span></b>
            <small><?= h($ultimo['folio']) ?></small>
        <?php else: ?>
            <b>—</b>
            <small>Sin historial</small>
        <?php endif; ?>
    </div>
    <div class="stat">
        <span>Último ingreso</span>
        <b class="eq-stat-text"><?= $ultimo ? h(formatFecha($ultimo['fecha_recepcion'], true)) : '—' ?></b>
        <small><?= $ultimo ? h($ultimo['estado_fisico'] ?: 'Sin condición') : 'Aún no entra a soporte' ?></small>
    </div>
    <div class="stat">
        <span>Responsable</span>
        <b class="eq-stat-text"><?= $dueno ? h($dueno['nombre']) : 'Sin asignar' ?></b>
        <small><?= $dueno ? h($dueno['area_dependencia'] ?: 'Sin área') : 'No hay persona vinculada' ?></small>
    </div>
</div>

<div class="eq-layout">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Identificación</h2>
                <p class="hint">Datos fijos del bien en inventario.</p>
            </div>
        </div>
        <dl class="eq-facts">
            <div class="fact"><dt>Tipo</dt><dd><?= h($bien['tipo_equipo']) ?></dd></div>
            <div class="fact"><dt>Marca</dt><dd><?= h($bien['marca']) ?></dd></div>
            <div class="fact"><dt>Modelo</dt><dd><?= h($bien['modelo']) ?></dd></div>
            <div class="fact"><dt>Número de serie</dt><dd><?= h($bien['numero_serie'] ?: '—') ?></dd></div>
            <div class="fact"><dt>Número de inventario</dt><dd><?= h($bien['numero_inventario'] ?: '—') ?></dd></div>
            <div class="fact"><dt>Servicios</dt><dd><?= $nServicios ?></dd></div>
        </dl>
        <?php if ($ultimo && ($ultimaFalla !== '' || !empty($ultimo['accesorios']) || !empty($ultimo['observaciones']) || !empty($ultimo['estado_fisico']))): ?>
            <div class="eq-split">
                <h3>Último ingreso</h3>
                <dl class="eq-facts">
                    <div class="fact"><dt>Estado físico</dt><dd><?= h($ultimo['estado_fisico'] ?: '—') ?></dd></div>
                    <div class="fact"><dt>Última falla</dt><dd><?= h($ultimaFalla !== '' ? $ultimaFalla : '—') ?></dd></div>
                    <?php if (!empty($ultimo['accesorios'])): ?>
                        <div class="fact"><dt>Accesorios</dt><dd><?= h($ultimo['accesorios']) ?></dd></div>
                    <?php endif; ?>
                    <?php if (!empty($ultimo['observaciones'])): ?>
                        <div class="fact eq-fact-wide"><dt>Observaciones</dt><dd><?= h($ultimo['observaciones']) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>
    </section>

    <section class="card eq-side">
        <div class="section-head">
            <div>
                <h2>Persona asociada</h2>
                <p class="hint">Último perfil vinculado a este equipo.</p>
            </div>
        </div>
        <?php if ($dueno): ?>
            <div class="eq-person">
                <span class="ppl-avatar lg"><?= h(nombreIniciales($dueno['nombre'] ?? '')) ?></span>
                <div>
                    <strong><?= h($dueno['nombre']) ?></strong>
                    <small><?= h($dueno['area_dependencia'] ?: 'Sin área') ?></small>
                    <small><?= h($dueno['telefono'] ?: 'Sin teléfono') ?></small>
                </div>
            </div>
            <div class="btn-row eq-person-actions">
                <a class="btn btn-sm btn-primary" href="<?= h(url('persona.php?id=' . $dueno['id'])) ?>">Ver perfil</a>
                <a class="btn btn-sm btn-ok" href="<?= h(url('recibir.php?bien=' . $id . '&persona=' . $dueno['id'])) ?>">Servicio</a>
            </div>
        <?php else: ?>
            <p class="empty-state eq-empty">Sin persona asociada.</p>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Historial de servicios</h2>
            <p class="hint">Cada folio es un ingreso de este mismo equipo.</p>
        </div>
    </div>
    <?php if (!$servicios): ?>
        <p class="empty-state">Este equipo aún no tiene servicios.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Entregado por</th>
                    <th>Problema</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servicios as $eq): ?>
                <tr>
                    <td>
                        <a href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>"><strong><?= h($eq['folio']) ?></strong></a>
                        <span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span>
                        <small><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></small>
                    </td>
                    <td><?= h($eq['entregado_por'] ?: '—') ?></td>
                    <td>
                        <?= h($eq['problema_reportado'] ?: '—') ?>
                        <?php if (!empty($eq['tipo_problema'])): ?>
                            <small><?= h($eq['tipo_problema']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
