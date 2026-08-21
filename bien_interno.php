<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();
$bien = getBienInterno($pdo, $id);
if (!$bien) {
    flash('error', 'El bien no existe en el inventario interno.');
    redirect('bienes_internos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('error', 'La sesión expiró. Intente de nuevo.');
        redirect('bien_interno.php?id=' . $id);
    }
    $accion = (string) ($_POST['accion'] ?? 'guardar');
    try {
        if ($accion === 'fotos') {
            $n = addFotosInternos($pdo, $id, $_FILES['fotos'] ?? []);
            if ($n < 1) {
                throw new RuntimeException('Seleccione al menos una fotografía.');
            }
            flash('ok', $n === 1 ? 'Foto agregada.' : $n . ' fotos agregadas.');
        } elseif ($accion === 'borrar_foto') {
            deleteFotoInterno($pdo, (int) ($_POST['foto_id'] ?? 0), $id);
            flash('ok', 'Foto eliminada.');
        } elseif ($accion === 'baja') {
            setEstadoBienInterno($pdo, $id, 'baja');
            flash('ok', 'El bien quedó en baja.');
        } elseif ($accion === 'reactivar') {
            setEstadoBienInterno($pdo, $id, 'disponible');
            flash('ok', 'El bien volvió a disponible.');
        } else {
            saveBienInterno($pdo, [
                'tipo_equipo' => trim($_POST['tipo_equipo'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'numero_serie' => trim($_POST['numero_serie'] ?? ''),
                'numero_inventario' => trim($_POST['numero_inventario'] ?? ''),
                'estado_fisico' => trim($_POST['estado_fisico'] ?? ''),
                'accesorios' => trim($_POST['accesorios'] ?? ''),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            ], $id);
            flash('ok', 'Datos actualizados.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('bien_interno.php?id=' . $id);
}

$bien = getBienInterno($pdo, $id);
$fotos = getFotosInternos($pdo, $id);
$historial = prestamosDeBienInterno($pdo, $id);
$activo = $bien['estado'] === 'prestado' ? prestamoActivoDeBien($pdo, $id) : null;
$titulo = trim($bien['marca'] . ' ' . $bien['modelo']);
$pageTitle = $titulo;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="ppl-head">
        <span class="ppl-avatar lg eq-avatar"><?= h(nombreIniciales($bien['tipo_equipo'] ?? '')) ?></span>
        <div>
            <p class="eyebrow">Bien interno</p>
            <h1><?= h($titulo) ?></h1>
            <p class="page-sub"><?= h($bien['tipo_equipo']) ?> · Inv. <?= h($bien['numero_inventario'] ?: 's/n') ?> · Serie <?= h($bien['numero_serie'] ?: 's/n') ?></p>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('bienes_internos.php')) ?>">Bienes</a>
        <button class="btn btn-ghost" type="button" data-toggle-panel="#editar-bien">Editar</button>
        <?php if ($bien['estado'] === 'disponible'): ?>
            <a class="btn btn-ok" href="<?= h(url('prestar.php?bien=' . $id)) ?>">Prestar</a>
        <?php elseif ($activo): ?>
            <a class="btn btn-ok" href="<?= h(url('prestamo.php?id=' . $activo['id'])) ?>">Devolver</a>
        <?php endif; ?>
    </div>
</div>

<div class="eq-stats">
    <div class="stat">
        <span>Estado</span>
        <b class="eq-stat-badge"><span class="badge st-<?= h($bien['estado']) ?>"><?= h(estadoBienInternoLabel($bien['estado'])) ?></span></b>
        <small><?= h($bien['ubicacion'] ?: 'Sin ubicación') ?></small>
    </div>
    <div class="stat">
        <span>Condición</span>
        <b class="eq-stat-text"><?= h($bien['estado_fisico'] ?: '—') ?></b>
        <small><?= h($bien['accesorios'] ? 'Con accesorios' : 'Sin accesorios capturados') ?></small>
    </div>
    <div class="stat">
        <span>Préstamos</span>
        <b><?= count($historial) ?></b>
        <small><?= count($historial) === 1 ? 'Folio registrado' : 'Folios registrados' ?></small>
    </div>
    <div class="stat">
        <span><?= $activo ? 'En manos de' : 'Disponible para' ?></span>
        <?php if ($activo): ?>
            <b class="eq-stat-text"><?= h($activo['persona_nombre'] ?: '—') ?></b>
            <small>Hasta <?= h(formatFecha($activo['fecha_compromiso'])) ?> · <?= h($activo['folio']) ?></small>
        <?php else: ?>
            <b class="eq-stat-text"><?= $bien['estado'] === 'baja' ? 'No se presta' : 'Préstamo' ?></b>
            <small><?= $bien['estado'] === 'baja' ? 'Reactivar para volver a usarlo' : 'Quien lo pida firma un recibí' ?></small>
        <?php endif; ?>
    </div>
</div>

<section class="card ppl-alta" id="editar-bien" hidden>
    <div class="section-head">
        <span class="section-num">1</span>
        <div>
            <h2>Datos del bien</h2>
            <p class="hint">Identificación permanente en el inventario interno.</p>
        </div>
    </div>
    <form method="post" class="ppl-form-grid bi-alta-grid">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="guardar">
        <div class="field">
            <label>Tipo</label>
            <select name="tipo_equipo" required>
                <?php foreach (tiposEquipoInterno() as $t): ?>
                    <option <?= $bien['tipo_equipo'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Marca</label>
            <input name="marca" required value="<?= h($bien['marca']) ?>">
        </div>
        <div class="field">
            <label>Modelo</label>
            <input name="modelo" required value="<?= h($bien['modelo']) ?>">
        </div>
        <div class="field">
            <label>Número de serie</label>
            <input name="numero_serie" value="<?= h($bien['numero_serie'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Número de inventario</label>
            <input name="numero_inventario" value="<?= h($bien['numero_inventario'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Estado físico</label>
            <select name="estado_fisico">
                <option value="">Seleccione</option>
                <?php foreach (estadosFisicos() as $t): ?>
                    <option <?= ($bien['estado_fisico'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Ubicación</label>
            <input name="ubicacion" value="<?= h($bien['ubicacion'] ?? '') ?>">
        </div>
        <div class="field field-span">
            <label>Accesorios</label>
            <textarea name="accesorios"><?= h($bien['accesorios'] ?? '') ?></textarea>
        </div>
        <div class="field field-span">
            <label>Observaciones</label>
            <textarea name="observaciones"><?= h($bien['observaciones'] ?? '') ?></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
    </form>
</section>

<div class="eq-layout">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Identificación</h2>
                <p class="hint">Datos fijos del bien en informática.</p>
            </div>
        </div>
        <dl class="eq-facts">
            <div class="fact"><dt>Tipo</dt><dd><?= h($bien['tipo_equipo']) ?></dd></div>
            <div class="fact"><dt>Marca</dt><dd><?= h($bien['marca']) ?></dd></div>
            <div class="fact"><dt>Modelo</dt><dd><?= h($bien['modelo']) ?></dd></div>
            <div class="fact"><dt>Número de serie</dt><dd><?= h($bien['numero_serie'] ?: '—') ?></dd></div>
            <div class="fact"><dt>Número de inventario</dt><dd><?= h($bien['numero_inventario'] ?: '—') ?></dd></div>
            <div class="fact"><dt>Ubicación</dt><dd><?= h($bien['ubicacion'] ?: '—') ?></dd></div>
            <div class="fact"><dt>Estado físico</dt><dd><?= h($bien['estado_fisico'] ?: '—') ?></dd></div>
            <?php if (!empty($bien['accesorios'])): ?>
                <div class="fact eq-fact-wide"><dt>Accesorios</dt><dd><?= h($bien['accesorios']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($bien['observaciones'])): ?>
                <div class="fact eq-fact-wide"><dt>Observaciones</dt><dd><?= h($bien['observaciones']) ?></dd></div>
            <?php endif; ?>
        </dl>
    </section>

    <section class="card eq-side">
        <div class="section-head">
            <div>
                <h2>Fotos</h2>
                <p class="hint">Evidencia del estado del bien.</p>
            </div>
        </div>
        <?php if ($fotos): ?>
            <div class="photos">
                <?php foreach ($fotos as $f): ?>
                    <div class="photo-item">
                        <a href="<?= h(url('archivo.php?tipo=foto_interno&fid=' . $f['id'])) ?>" target="_blank" rel="noopener">
                            <img src="<?= h(url('archivo.php?tipo=foto_interno&fid=' . $f['id'])) ?>" alt="Foto del bien">
                        </a>
                        <form method="post" onsubmit="return confirm('¿Quitar esta foto?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="accion" value="borrar_foto">
                            <input type="hidden" name="foto_id" value="<?= (int) $f['id'] ?>">
                            <button class="btn btn-sm btn-ghost" type="submit">Quitar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state eq-empty">Sin fotografías.</p>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="upload-row">
            <?= csrfField() ?>
            <input type="hidden" name="accion" value="fotos">
            <div class="field">
                <label>Agregar fotografías</label>
                <input type="file" name="fotos[]" accept="image/*" multiple required>
            </div>
            <button class="btn btn-ghost" type="submit">Subir</button>
        </form>
        <?php if ($bien['estado'] === 'disponible'): ?>
            <form method="post" class="bi-baja" onsubmit="return confirm('¿Dar de baja este bien? Dejará de poder prestarse.');">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="baja">
                <button class="btn btn-ghost" type="submit">Dar de baja</button>
            </form>
        <?php elseif ($bien['estado'] === 'baja'): ?>
            <form method="post" class="bi-baja">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="reactivar">
                <button class="btn btn-ok" type="submit">Reactivar</button>
            </form>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Historial de préstamos</h2>
            <p class="hint">Cada folio es un recibí en el que salió este bien.</p>
        </div>
    </div>
    <?php if (!$historial): ?>
        <p class="empty-state">Este bien aún no se ha prestado.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Persona</th>
                    <th>Periodo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $p): ?>
                <tr class="<?= $p['estado_visual'] === 'vencido' ? 'row-vencido' : '' ?>">
                    <td>
                        <a href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>"><strong><?= h($p['folio']) ?></strong></a>
                        <span class="badge st-<?= h($p['estado_visual']) ?>"><?= h($p['estado_visual_label']) ?></span>
                    </td>
                    <td>
                        <a href="<?= h(url('persona.php?id=' . $p['persona_id'])) ?>"><?= h($p['persona_nombre'] ?: '—') ?></a>
                        <small><?= h($p['persona_area'] ?: '') ?></small>
                    </td>
                    <td>
                        <?= h(formatFecha($p['fecha_prestamo'], true)) ?>
                        <small>Hasta <?= h(formatFecha($p['fecha_compromiso'])) ?></small>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= h(url('prestamo.php?id=' . $p['id'])) ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
