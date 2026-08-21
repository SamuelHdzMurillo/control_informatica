<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();
$prestamo = getPrestamo($pdo, $id);
if (!$prestamo) {
    flash('error', 'El préstamo no existe.');
    redirect('prestamos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('error', 'La sesión expiró. Intente de nuevo.');
        redirect('prestamo.php?id=' . $id);
    }
    try {
        $itemIds = $_POST['items'] ?? [];
        $estados = [];
        foreach ((array) ($_POST['estado_fisico'] ?? []) as $itemId => $valor) {
            $estados[(int) $itemId] = (string) $valor;
        }
        $devId = devolverPrestamoItems(
            $pdo,
            $id,
            $itemIds,
            (string) ((currentUser() ?? [])['nombre'] ?? ORG_AREA),
            trim($_POST['observaciones'] ?? ''),
            $estados
        );
        flash('ok', 'Devolución registrada. Imprima el recibí.');
        redirect('recibo_devolucion.php?id=' . $devId);
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('prestamo.php?id=' . $id);
    }
}

$items = getPrestamoItems($pdo, $id);
$pendientes = array_values(array_filter($items, static fn(array $i): bool => empty($i['fecha_devolucion'])));
$devoluciones = listPrestamoDevoluciones($pdo, $id);
$pageTitle = $prestamo['folio'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="ppl-head">
        <span class="ppl-avatar lg"><?= h(nombreIniciales($prestamo['persona_nombre'] ?? '')) ?></span>
        <div>
            <p class="eyebrow">Préstamo interno</p>
            <h1><?= h($prestamo['folio']) ?></h1>
            <p class="page-sub">
                <?= h($prestamo['persona_nombre'] ?: 'Sin persona') ?>
                · <?= h($prestamo['persona_area'] ?: 'Sin área') ?>
                · hasta <?= h(formatFecha($prestamo['fecha_compromiso'])) ?>
            </p>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('prestamos.php')) ?>">Préstamos</a>
        <a class="btn btn-ghost" href="<?= h(url('recibo_prestamo.php?id=' . $id)) ?>">Recibí de entrega</a>
        <?php if ($pendientes): ?>
            <a class="btn btn-ok" href="#devolver">Devolver</a>
        <?php endif; ?>
    </div>
</div>

<div class="eq-stats">
    <div class="stat">
        <span>Estado</span>
        <b class="eq-stat-badge"><span class="badge st-<?= h($prestamo['estado_visual']) ?>"><?= h($prestamo['estado_visual_label']) ?></span></b>
        <small><?= (int) $prestamo['items_fuera'] ?> fuera de <?= (int) $prestamo['items_total'] ?></small>
    </div>
    <div class="stat">
        <span>Prestado</span>
        <b class="eq-stat-text"><?= h(formatFecha($prestamo['fecha_prestamo'], true)) ?></b>
        <small>Por <?= h($prestamo['prestado_por'] ?: ORG_AREA) ?></small>
    </div>
    <div class="stat">
        <span>Compromiso</span>
        <b class="eq-stat-text"><?= h(formatFecha($prestamo['fecha_compromiso'])) ?></b>
        <small><?= $prestamo['estado_visual'] === 'vencido' ? 'Ya debió regresarse' : ($prestamo['estado'] === 'cerrado' ? 'Cerrado' : 'Fecha acordada') ?></small>
    </div>
    <div class="stat">
        <span>Persona</span>
        <b class="eq-stat-text"><?= h($prestamo['persona_nombre'] ?: '—') ?></b>
        <small><?= h($prestamo['persona_telefono'] ?: 'Sin teléfono') ?></small>
    </div>
</div>

<?php if ($prestamo['estado_visual'] === 'vencido'): ?>
    <div class="alert alert-error">Este préstamo ya pasó la fecha de devolución. <?= (int) $prestamo['items_fuera'] === 1 ? 'Queda 1 bien fuera.' : 'Quedan ' . (int) $prestamo['items_fuera'] . ' bienes fuera.' ?></div>
<?php elseif ($prestamo['estado_visual'] === 'vence_pronto'): ?>
    <div class="alert alert-info">La fecha de compromiso es <?= h(formatFecha($prestamo['fecha_compromiso'])) ?>.</div>
<?php endif; ?>

<div class="eq-layout">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Bienes del recibí</h2>
                <p class="hint">Lo que salió con este folio. Puede devolverse todo o solo una parte.</p>
            </div>
        </div>
        <div class="table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Bien</th>
                    <th>Al salir</th>
                    <th>Devolución</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <a href="<?= h(url('bien_interno.php?id=' . $it['bien_interno_id'])) ?>"><strong><?= h($it['tipo_equipo']) ?></strong></a>
                        <?= h(trim($it['marca'] . ' ' . $it['modelo'])) ?>
                        <small>
                            <?= h($it['numero_inventario'] ? 'Inv. ' . $it['numero_inventario'] : 'Sin inventario') ?>
                            · <?= h($it['numero_serie'] ? 'Serie ' . $it['numero_serie'] : 'Sin serie') ?>
                        </small>
                    </td>
                    <td>
                        <?= h($it['estado_fisico_salida'] ?: '—') ?>
                        <?php if (!empty($it['accesorios_salida'])): ?>
                            <small><?= h($it['accesorios_salida']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($it['fecha_devolucion'])): ?>
                            <span class="badge st-cerrado">Devuelto</span>
                            <small><?= h(formatFecha($it['fecha_devolucion'], true)) ?><?= !empty($it['estado_fisico_regreso']) ? ' · ' . h($it['estado_fisico_regreso']) : '' ?></small>
                        <?php else: ?>
                            <span class="badge st-prestado">Fuera</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= h(url('bien_interno.php?id=' . $it['bien_interno_id'])) ?>">Ver bien</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if (!empty($prestamo['observaciones'])): ?>
            <p class="hint" style="margin-top:12px"><strong>Observaciones:</strong> <?= h($prestamo['observaciones']) ?></p>
        <?php endif; ?>
    </section>

    <section class="card eq-side">
        <div class="section-head">
            <div>
                <h2>Persona</h2>
                <p class="hint">Quien firmó el recibí.</p>
            </div>
        </div>
        <div class="eq-person">
            <span class="ppl-avatar lg"><?= h(nombreIniciales($prestamo['persona_nombre'] ?? '')) ?></span>
            <div>
                <strong><?= h($prestamo['persona_nombre'] ?: '—') ?></strong>
                <small><?= h($prestamo['persona_area'] ?: 'Sin área') ?></small>
                <small><?= h($prestamo['persona_telefono'] ?: 'Sin teléfono') ?></small>
            </div>
        </div>
        <div class="btn-row eq-person-actions">
            <a class="btn btn-sm btn-primary" href="<?= h(url('persona.php?id=' . $prestamo['persona_id'])) ?>">Ver perfil</a>
        </div>
    </section>
</div>

<?php if ($pendientes): ?>
<section class="card" id="devolver">
    <div class="section-head">
        <span class="section-num">←</span>
        <div>
            <h2>Registrar devolución</h2>
            <p class="hint">Marque lo que regresan ahora. Lo que quede fuera sigue en el préstamo.</p>
        </div>
    </div>
    <form method="post">
        <?= csrfField() ?>
        <div class="pick-list">
            <?php foreach ($pendientes as $it): ?>
                <label class="pick-item">
                    <input type="checkbox" name="items[]" value="<?= (int) $it['id'] ?>" checked>
                    <span>
                        <strong><?= h($it['tipo_equipo']) ?></strong>
                        <?= h(trim($it['marca'] . ' ' . $it['modelo'])) ?>
                        <small><?= h($it['numero_inventario'] ? 'Inv. ' . $it['numero_inventario'] : ($it['numero_serie'] ? 'Serie ' . $it['numero_serie'] : 'Sin identificación')) ?></small>
                    </span>
                </label>
                <div class="field pick-extra">
                    <label>Estado físico al regresar</label>
                    <select name="estado_fisico[<?= (int) $it['id'] ?>]">
                        <option value="">Sin cambio</option>
                        <?php foreach (estadosFisicos() as $t): ?>
                            <option <?= ($it['estado_fisico_salida'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="field" style="margin-top:12px">
            <label>Observaciones de la devolución</label>
            <textarea name="observaciones" placeholder="Faltantes, daños, notas al recibir."></textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-ok" type="submit">Recibir y generar recibí</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if ($devoluciones): ?>
<section class="card">
    <div class="section-head">
        <div>
            <h2>Devoluciones</h2>
            <p class="hint">Cada lote tiene su propio recibí de regreso.</p>
        </div>
    </div>
    <div class="table-wrap">
    <table class="inv-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Recibió</th>
                <th>Notas</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($devoluciones as $d): ?>
            <tr>
                <td><?= h(formatFecha($d['fecha'], true)) ?></td>
                <td><?= h($d['recibido_por'] ?: '—') ?></td>
                <td><?= h($d['observaciones'] ?: '—') ?></td>
                <td>
                    <a class="btn btn-sm btn-ghost" href="<?= h(url('recibo_devolucion.php?id=' . $d['id'])) ?>">Recibí</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
