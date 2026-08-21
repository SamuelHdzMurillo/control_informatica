<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();
$persona = getPersona($pdo, $id);
if (!$persona) {
    flash('error', 'La persona no existe.');
    redirect('personas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('error', 'La sesión expiró. Intente de nuevo.');
        redirect('persona.php?id=' . $id);
    }
    try {
        savePersona(
            $pdo,
            trim($_POST['nombre'] ?? ''),
            trim($_POST['area_dependencia'] ?? ''),
            trim($_POST['telefono'] ?? ''),
            $id
        );
        flash('ok', 'Perfil actualizado.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('persona.php?id=' . $id);
}

$bienes = bienesDePersona($pdo, $id);
$servicios = serviciosDePersona($pdo, $id);
$prestamos = prestamosDePersona($pdo, $id);
$prestamosActivos = array_values(array_filter($prestamos, static fn(array $p): bool => $p['estado'] === 'activo'));
$pageTitle = $persona['nombre'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="ppl-head">
        <span class="ppl-avatar lg"><?= h(nombreIniciales($persona['nombre'] ?? '')) ?></span>
        <div>
            <p class="eyebrow">Perfil de persona</p>
            <h1><?= h($persona['nombre']) ?></h1>
            <p class="page-sub"><?= h($persona['area_dependencia'] ?: 'Sin área') ?> · <?= h($persona['telefono'] ?: 'Sin teléfono') ?></p>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('personas.php')) ?>">Directorio</a>
        <button class="btn btn-ghost" type="button" data-toggle-panel="#editar-persona">Editar</button>
        <a class="btn btn-ghost" href="<?= h(url('prestar.php?persona=' . $id)) ?>">Prestar material</a>
        <a class="btn btn-ok" href="<?= h(url('recibir.php?persona=' . $id)) ?>">+ Recibir equipo</a>
    </div>
</div>

<section class="card ppl-alta" id="editar-persona" hidden>
    <div class="section-head">
        <span class="section-num">1</span>
        <div>
            <h2>Datos del perfil</h2>
            <p class="hint">Se reutilizan al recibir equipos.</p>
        </div>
    </div>
    <form method="post" class="ppl-form-grid">
        <?= csrfField() ?>
        <div class="field">
            <label>Nombre</label>
            <input name="nombre" required value="<?= h($persona['nombre']) ?>">
        </div>
        <div class="field">
            <label>Área / dependencia</label>
            <input name="area_dependencia" value="<?= h($persona['area_dependencia'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Teléfono</label>
            <input name="telefono" value="<?= h($persona['telefono'] ?? '') ?>">
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
    </form>
</section>

<div class="kv-cards">
<section class="card">
    <div class="section-head">
        <div>
            <h2>Equipos</h2>
            <p class="hint">Bienes asociados a este perfil.</p>
        </div>
    </div>
    <?php if (!$bienes): ?>
        <p class="empty-state">Aún no tiene equipos en inventario.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Identificación</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bienes as $b): ?>
                <tr>
                    <td>
                        <strong><?= h($b['tipo_equipo']) ?></strong>
                        <?= h(trim($b['marca'] . ' ' . $b['modelo'])) ?>
                    </td>
                    <td>
                        <?= h($b['numero_inventario'] ? 'Inv. ' . $b['numero_inventario'] : 'Sin inventario') ?>
                        <small><?= h($b['numero_serie'] ? 'Serie ' . $b['numero_serie'] : 'Sin serie') ?></small>
                    </td>
                    <td>
                        <div class="btn-row">
                            <a class="btn btn-sm btn-primary" href="<?= h(url('bien.php?id=' . $b['id'])) ?>">Ver</a>
                            <a class="btn btn-sm btn-ok" href="<?= h(url('recibir.php?bien=' . $b['id'] . '&persona=' . $id)) ?>">Servicio</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Servicios</h2>
            <p class="hint">Folios en los que entregó equipo.</p>
        </div>
    </div>
    <?php if (!$servicios): ?>
        <p class="empty-state">Sin servicios registrados.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Equipo</th>
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
                    <td><?= h($eq['tipo_equipo'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']) ?></td>
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
</div>

<?php
$prestamosVencidosPersona = array_values(array_filter($prestamosActivos, static fn(array $p): bool => $p['estado_visual'] === 'vencido'));
?>
<?php if ($prestamosVencidosPersona): ?>
    <div class="alert alert-error">
        Esta persona tiene <?= count($prestamosVencidosPersona) === 1 ? 'un préstamo vencido' : count($prestamosVencidosPersona) . ' préstamos vencidos' ?> de informática.
    </div>
<?php endif; ?>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Préstamos de informática</h2>
            <p class="hint">Material interno que tiene o ha tenido a su cargo.</p>
        </div>
        <a class="btn btn-sm btn-ok" href="<?= h(url('prestar.php?persona=' . $id)) ?>">Prestar</a>
    </div>
    <?php if (!$prestamos): ?>
        <p class="empty-state">Sin préstamos de inventario interno.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Periodo</th>
                    <th>Bienes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr class="<?= $p['estado_visual'] === 'vencido' ? 'row-vencido' : ($p['estado_visual'] === 'vence_pronto' ? 'row-pronto' : '') ?>">
                    <td>
                        <a href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>"><strong><?= h($p['folio']) ?></strong></a>
                        <span class="badge st-<?= h($p['estado_visual']) ?>"><?= h($p['estado_visual_label']) ?></span>
                    </td>
                    <td>
                        <?= h(formatFecha($p['fecha_prestamo'], true)) ?>
                        <small>Hasta <?= h(formatFecha($p['fecha_compromiso'])) ?></small>
                    </td>
                    <td>
                        <?= (int) $p['items_fuera'] ?> fuera
                        <small>de <?= (int) $p['items_total'] ?></small>
                    </td>
                    <td>
                        <div class="btn-row">
                            <a class="btn btn-sm btn-primary" href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>">Ver</a>
                            <a class="btn btn-sm btn-ghost" href="<?= h(url('recibo_prestamo.php?id=' . $p['id'])) ?>">Recibí</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
