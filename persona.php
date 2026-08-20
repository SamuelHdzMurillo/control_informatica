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
$pageTitle = $persona['nombre'];
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow">Perfil de persona</p>
        <h1><?= h($persona['nombre']) ?></h1>
        <p class="lead"><?= h($persona['area_dependencia'] ?: 'Sin área') ?> · <?= h($persona['telefono'] ?: 'Sin teléfono') ?></p>
    </div>
    <a class="btn btn-ok" href="<?= h(url('recibir.php?persona=' . $id)) ?>">+ Recibir equipo</a>
</div>

<div class="sections">
<section class="section">
    <div class="section-head">
        <span class="section-num">1</span>
        <div>
            <h2>Datos del perfil</h2>
            <p class="hint">Estos datos se reutilizan al recibir equipos.</p>
        </div>
    </div>
    <form method="post" class="grid-3">
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
        <div class="field" style="justify-content:end">
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit">Guardar cambios</button>
        </div>
    </form>
</section>

<section class="section">
    <div class="section-head">
        <span class="section-num">2</span>
        <div>
            <h2>Equipos de esta persona</h2>
            <p class="hint">Bienes asociados a su perfil.</p>
        </div>
    </div>
    <?php if (!$bienes): ?>
        <p class="lead">Aún no tiene equipos en inventario.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Serie</th>
                    <th>Inventario</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bienes as $b): ?>
                <tr>
                    <td><?= h($b['tipo_equipo']) ?><br><small><?= h($b['marca'] . ' ' . $b['modelo']) ?></small></td>
                    <td><?= h($b['numero_serie'] ?: '—') ?></td>
                    <td><?= h($b['numero_inventario'] ?: '—') ?></td>
                    <td>
                        <a href="<?= h(url('bien.php?id=' . $b['id'])) ?>">Historial</a>
                        ·
                        <a href="<?= h(url('recibir.php?bien=' . $b['id'] . '&persona=' . $id)) ?>">Nuevo servicio</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <span class="section-num">3</span>
        <div>
            <h2>Servicios</h2>
            <p class="hint">Folios en los que esta persona entregó equipo.</p>
        </div>
    </div>
    <?php if (!$servicios): ?>
        <p class="lead">Sin servicios registrados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Equipo</th>
                    <th>Recepción</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servicios as $eq): ?>
                <tr>
                    <td><strong><?= h($eq['folio']) ?></strong></td>
                    <td><?= h($eq['tipo_equipo'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']) ?></td>
                    <td><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></td>
                    <td><span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span></td>
                    <td><a href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
