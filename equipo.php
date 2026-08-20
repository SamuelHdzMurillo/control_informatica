<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();
$eq = getEquipo($pdo, $id);
if (!$eq) {
    flash('error', 'El equipo no existe.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        flash('error', 'La sesión expiró. Intente de nuevo.');
        redirect('equipo.php?id=' . $id);
    }
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'avance') {
            $estado = $_POST['estado'] ?? '';
            $diagnostico = trim($_POST['diagnostico'] ?? '');
            $trabajo = trim($_POST['trabajo_realizado'] ?? '');
            $publico = isset($_POST['visible_publico']);
            if (!isset(estadosEquipo()[$estado])) {
                throw new RuntimeException('Estado no válido.');
            }
            $esDiagnostico = in_array($estado, ['diagnostico', 'no_reparable'], true);
            $esReparacion = in_array($estado, ['reparacion', 'listo'], true);
            if ($esDiagnostico && $diagnostico === '') {
                throw new RuntimeException('Capture el diagnóstico. Ese dato corresponde a la revisión del equipo.');
            }
            if ($esReparacion && $trabajo === '') {
                throw new RuntimeException('Capture el trabajo realizado. Ese dato se llena cuando el equipo ya está reparado.');
            }
            $comentario = 'El equipo pasó a: ' . estadoLabel($estado);
            if ($esDiagnostico) {
                $comentario = $diagnostico;
                $upd = $pdo->prepare('UPDATE equipos SET estado = ?, diagnostico = ? WHERE id = ?');
                $upd->execute([$estado, $diagnostico, $id]);
            } elseif ($esReparacion) {
                $comentario = $trabajo;
                $upd = $pdo->prepare('UPDATE equipos SET estado = ?, trabajo_realizado = ? WHERE id = ?');
                $upd->execute([$estado, $trabajo, $id]);
            } else {
                $upd = $pdo->prepare('UPDATE equipos SET estado = ? WHERE id = ?');
                $upd->execute([$estado, $id]);
            }
            addBitacora($pdo, $id, $estado, $comentario, $publico, (int) currentUser()['id']);
            $eqActualizado = getEquipo($pdo, $id);
            if (in_array($estado, ['listo', 'no_reparable'], true) && puedeEmitirOrden($pdo, $eqActualizado)) {
                flash('ok', 'Equipo listo. Ya puede imprimir la orden de servicio.');
                redirect('orden.php?id=' . $id);
            }
            flash('ok', 'Avance registrado. Quien tenga el recibo ya puede verlo.');
        } elseif ($accion === 'entregar') {
            $entregadoA = trim($_POST['entregado_a'] ?? '');
            if ($entregadoA === '') {
                throw new RuntimeException('Indique a quién se entrega el equipo.');
            }
            $upd = $pdo->prepare('UPDATE equipos SET estado = ?, entregado_a = ?, fecha_entrega = NOW() WHERE id = ?');
            $upd->execute(['entregado', $entregadoA, $id]);
            addBitacora(
                $pdo,
                $id,
                'entregado',
                'Equipo entregado a ' . $entregadoA . '.',
                true,
                (int) currentUser()['id']
            );
            flash('ok', 'El equipo quedó marcado como entregado.');
        } elseif ($accion === 'fotos') {
            if (empty($_FILES['fotos']['name'][0])) {
                throw new RuntimeException('Seleccione al menos una fotografía.');
            }
            $count = count($_FILES['fotos']['name']);
            for ($i = 0; $i < $count; $i++) {
                if (($_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file = [
                    'name' => $_FILES['fotos']['name'][$i],
                    'type' => $_FILES['fotos']['type'][$i],
                    'tmp_name' => $_FILES['fotos']['tmp_name'][$i],
                    'error' => $_FILES['fotos']['error'][$i],
                    'size' => $_FILES['fotos']['size'][$i],
                ];
                $ruta = saveUploadedFile($file, 'fotos', ['jpg', 'jpeg', 'png', 'webp'], MAX_PHOTO_BYTES);
                if ($ruta) {
                    $ins = $pdo->prepare('INSERT INTO fotos (equipo_id, ruta) VALUES (?, ?)');
                    $ins->execute([$id, $ruta]);
                }
            }
            flash('ok', 'Fotografías agregadas.');
        } elseif ($accion === 'oficio') {
            if (empty($_FILES['oficio']['name'])) {
                throw new RuntimeException('Seleccione el oficio.');
            }
            $oficio = saveUploadedFile(
                $_FILES['oficio'],
                'oficios',
                ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                MAX_OFICIO_BYTES
            );
            $upd = $pdo->prepare('UPDATE equipos SET oficio_path = ? WHERE id = ?');
            $upd->execute([$oficio, $id]);
            flash('ok', 'Oficio adjuntado.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('equipo.php?id=' . $id);
}

$fotos = getFotos($pdo, $id);
$bitacora = getBitacora($pdo, $id, false);
$paso = estadoPaso($eq['estado']);
$diagnosticoTxt = getDiagnostico($pdo, $eq);
$trabajoTxt = getTrabajoRealizado($pdo, $eq);
$faltanOrden = faltantesOrden($pdo, $eq);
$puedeOrden = $faltanOrden === [];
$pageTitle = $eq['folio'];
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow">Folio de servicio</p>
        <h1><?= h($eq['folio']) ?></h1>
        <p class="lead"><?= h($eq['tipo_equipo'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']) ?></p>
        <div class="exp-status">
            <span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span>
            <small style="color:var(--muted)">Recepción <?= h(formatFecha($eq['fecha_recepcion'], true)) ?></small>
        </div>
    </div>
</div>

<div class="action-bar">
    <div class="action-group">
        <span class="action-label">Actualizar el servicio</span>
        <div class="btn-row">
            <?php if ($eq['estado'] !== 'entregado'): ?>
                <button class="btn btn-primary" type="button" data-show-panel="panel-avance">Registrar avance</button>
                <?php if (in_array($eq['estado'], ['listo', 'no_reparable'], true)): ?>
                    <button class="btn btn-ok" type="button" data-show-panel="panel-entrega">Entregar equipo</button>
                <?php endif; ?>
            <?php else: ?>
                <span class="lead" style="margin:0">Este folio ya fue entregado.</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="action-group">
        <span class="action-label">Imprimir y consultar</span>
        <div class="btn-row">
            <a class="btn btn-ghost" href="<?= h(url('recibo.php?id=' . $eq['id'])) ?>">Recibo de ingreso</a>
            <?php if ($puedeOrden): ?>
                <a class="btn btn-ok" href="<?= h(url('orden.php?id=' . $eq['id'])) ?>">Orden de servicio</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= h(url('consulta.php?folio=' . urlencode($eq['folio']))) ?>" target="_blank" rel="noopener">Consulta pública</a>
        </div>
    </div>
</div>

<div class="steps">
    <?php
    $pasos = [1 => 'Recibido', 2 => 'Diagnóstico', 3 => 'Reparación', 4 => 'Listo', 5 => 'Entregado'];
    foreach ($pasos as $n => $label):
        $class = $n < $paso ? 'done' : ($n === $paso ? 'on' : '');
        ?>
        <div class="step <?= $class ?>"><?= h($label) ?></div>
    <?php endforeach; ?>
</div>

<?php if (!$puedeOrden): ?>
    <div class="alert alert-error">
        <strong>Aún no se puede emitir la orden de servicio. Falta:</strong>
        <ul class="faltantes">
            <?php foreach ($faltanOrden as $falta): ?>
                <li><?= h($falta) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($eq['estado'] !== 'entregado'): ?>
            <button class="btn btn-sm btn-primary" type="button" data-show-panel="panel-avance">Completar ahora</button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($eq['estado'] !== 'entregado'): ?>
<section class="section" id="panel-avance" hidden>
    <div class="section-head">
        <span class="section-num">+</span>
        <div>
            <h2>Registrar avance</h2>
            <p class="hint">El diagnóstico se captura en “En diagnóstico”. El trabajo realizado se captura cuando el equipo ya está reparado.</p>
        </div>
        <button class="btn btn-sm btn-ghost" type="button" data-hide-panels>Cerrar</button>
    </div>
    <form method="post" class="stack" id="form-avance">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="avance">
        <div class="field">
            <label>Nuevo estado</label>
            <select name="estado" id="avance-estado" required>
                <?php foreach (estadosEquipo() as $k => $label): ?>
                    <option value="<?= h($k) ?>" <?= $eq['estado'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" id="campo-diagnostico" <?= in_array($eq['estado'], ['diagnostico', 'no_reparable'], true) ? '' : 'hidden' ?>>
            <label>Diagnóstico <span class="req">*</span></label>
            <textarea name="diagnostico" id="avance-diagnostico" placeholder="Qué se encontró al revisar el equipo."><?= h($diagnosticoTxt) ?></textarea>
        </div>
        <div class="field" id="campo-trabajo" <?= in_array($eq['estado'], ['reparacion', 'listo'], true) ? '' : 'hidden' ?>>
            <label>Trabajo realizado <span class="req">*</span></label>
            <textarea name="trabajo_realizado" id="avance-trabajo" placeholder="Qué se hizo: piezas, instalación, limpieza, etc."><?= h($trabajoTxt) ?></textarea>
        </div>
        <label class="check">
            <input type="checkbox" name="visible_publico" checked>
            Visible en la consulta pública
        </label>
        <div class="btn-row">
            <button class="btn btn-primary" type="submit">Guardar avance</button>
            <button class="btn btn-ghost" type="button" data-hide-panels>Cancelar</button>
        </div>
    </form>
</section>
<section class="section" id="panel-entrega" hidden>
    <div class="section-head">
        <span class="section-num">+</span>
        <div>
            <h2>Entregar equipo</h2>
            <p class="hint">Marque la salida cuando alguien recoja el aparato.</p>
        </div>
        <button class="btn btn-sm btn-ghost" type="button" data-hide-panels>Cerrar</button>
    </div>
    <form method="post" class="stack">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="entregar">
        <div class="field">
            <label>Entregar a</label>
            <input name="entregado_a" id="entregado_a" required placeholder="Nombre de quien recoge" value="<?= h($eq['entregado_por']) ?>">
        </div>
        <div class="btn-row">
            <button class="btn btn-ok" type="submit">Marcar como entregado</button>
            <button class="btn btn-ghost" type="button" data-hide-panels>Cancelar</button>
        </div>
    </form>
</section>
<script>
(function () {
    var paneles = ['panel-avance', 'panel-entrega'];
    function mostrar(id) {
        paneles.forEach(function (pid) {
            var el = document.getElementById(pid);
            if (!el) return;
            var on = pid === id;
            el.hidden = !on;
            el.style.display = on ? '' : 'none';
            if (on) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    function ocultar() {
        paneles.forEach(function (pid) {
            var el = document.getElementById(pid);
            if (!el) return;
            el.hidden = true;
            el.style.display = 'none';
        });
    }
    document.querySelectorAll('[data-show-panel]').forEach(function (btn) {
        btn.addEventListener('click', function () { mostrar(btn.getAttribute('data-show-panel')); });
    });
    document.querySelectorAll('[data-hide-panels]').forEach(function (btn) {
        btn.addEventListener('click', ocultar);
    });

    var sel = document.getElementById('avance-estado');
    var diag = document.getElementById('campo-diagnostico');
    var trab = document.getElementById('campo-trabajo');
    var tDiag = document.getElementById('avance-diagnostico');
    var tTrab = document.getElementById('avance-trabajo');
    if (!sel) return;
    function sync() {
        var v = sel.value;
        var esDiag = v === 'diagnostico' || v === 'no_reparable';
        var esTrab = v === 'reparacion' || v === 'listo';
        if (diag) {
            diag.hidden = !esDiag;
            diag.style.display = esDiag ? '' : 'none';
        }
        if (trab) {
            trab.hidden = !esTrab;
            trab.style.display = esTrab ? '' : 'none';
        }
        if (tDiag) tDiag.required = esDiag;
        if (tTrab) tTrab.required = esTrab;
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>

<div class="exp-grid">
    <div class="sections">
        <section class="section">
            <div class="section-head">
                <span class="section-num">1</span>
                <div>
                    <h2>Equipo y quien lo entregó</h2>
                    <p class="hint">Datos de inventario y de ingreso.</p>
                </div>
            </div>
            <dl class="facts">
                <div class="fact"><dt>Tipo</dt><dd><?= h($eq['tipo_equipo']) ?></dd></div>
                <div class="fact"><dt>Marca y modelo</dt><dd><?= h($eq['marca'] . ' ' . $eq['modelo']) ?></dd></div>
                <div class="fact"><dt>Número de serie</dt><dd><?= h($eq['numero_serie'] ?: '—') ?></dd></div>
                <div class="fact"><dt>Inventario</dt><dd><?= h($eq['numero_inventario'] ?: '—') ?></dd></div>
                <div class="fact"><dt>Entregado por</dt><dd>
                    <?php if (!empty($eq['persona_id'])): ?>
                        <a class="btn btn-sm btn-ghost" href="<?= h(url('persona.php?id=' . $eq['persona_id'])) ?>"><?= h($eq['entregado_por']) ?></a>
                    <?php else: ?>
                        <?= h($eq['entregado_por']) ?>
                    <?php endif; ?>
                </dd></div>
                <div class="fact"><dt>Área / dependencia</dt><dd><?= h($eq['area_dependencia'] ?: '—') ?></dd></div>
                <div class="fact"><dt>Teléfono</dt><dd><?= h($eq['telefono'] ?: '—') ?></dd></div>
                <div class="fact"><dt>Recibido por</dt><dd><?= h($eq['recibido_por'] ?: '—') ?></dd></div>
                <?php if ($eq['estado'] === 'entregado'): ?>
                    <div class="fact wide"><dt>Entregado a</dt><dd><?= h($eq['entregado_a'] ?: '—') ?> · <?= h(formatFecha($eq['fecha_entrega'], true)) ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($eq['bien_id'])): ?>
                    <div class="fact wide"><dt>Historial del bien</dt><dd>
                        <a class="btn btn-sm btn-ghost" href="<?= h(url('bien.php?id=' . $eq['bien_id'])) ?>">Ver historial</a>
                    </dd></div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="section">
            <div class="section-head">
                <span class="section-num">2</span>
                <div>
                    <h2>Problema y condición de ingreso</h2>
                    <p class="hint">Falla reportada y cómo llegó el equipo.</p>
                </div>
            </div>
            <dl class="facts">
                <div class="fact"><dt>Problema reportado</dt><dd><?= h($eq['problema_reportado']) ?></dd></div>
                <div class="fact"><dt>Tipo de problema</dt><dd><?= h($eq['tipo_problema']) ?></dd></div>
                <div class="fact"><dt>Estado físico</dt><dd><?= h($eq['estado_fisico']) ?></dd></div>
                <div class="fact"><dt>Oficio</dt><dd>
                    <?php if ($eq['oficio_path']): ?>
                        <a class="btn btn-sm btn-info" href="<?= h(url('archivo.php?tipo=oficio&id=' . $eq['id'])) ?>">Ver oficio</a>
                    <?php else: ?>
                        Sin oficio
                    <?php endif; ?>
                </dd></div>
                <div class="fact wide"><dt>Descripción de la falla</dt><dd><?= nl2br(h($eq['descripcion_falla'])) ?></dd></div>
                <div class="fact"><dt>Diagnóstico</dt><dd><?= $diagnosticoTxt !== '' ? nl2br(h($diagnosticoTxt)) : 'Sin capturar' ?></dd></div>
                <div class="fact"><dt>Trabajo realizado</dt><dd><?= $trabajoTxt !== '' ? nl2br(h($trabajoTxt)) : 'Sin capturar' ?></dd></div>
                <div class="fact"><dt>Accesorios</dt><dd><?= nl2br(h($eq['accesorios'] ?: '—')) ?></dd></div>
                <div class="fact"><dt>Observaciones</dt><dd><?= nl2br(h($eq['observaciones'] ?: '—')) ?></dd></div>
            </dl>
        </section>

        <section class="section">
            <div class="section-head">
                <span class="section-num">3</span>
                <div>
                    <h2>Fotos y documentos</h2>
                    <p class="hint">Evidencia del ingreso. Puede agregar más si faltó.</p>
                </div>
            </div>
            <?php if ($fotos): ?>
                <div class="photos">
                    <?php foreach ($fotos as $f): ?>
                        <a href="<?= h(url('archivo.php?tipo=foto&fid=' . $f['id'])) ?>" target="_blank" rel="noopener">
                            <img src="<?= h(url('archivo.php?tipo=foto&fid=' . $f['id'])) ?>" alt="Foto del equipo">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="lead">No hay fotografías todavía.</p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="upload-row">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="fotos">
                <div class="field">
                    <label>Agregar fotografías</label>
                    <input type="file" name="fotos[]" accept="image/*" multiple required>
                </div>
                <button class="btn btn-ghost" type="submit">Subir fotos</button>
            </form>
            <?php if (!$eq['oficio_path']): ?>
                <form method="post" enctype="multipart/form-data" class="upload-row">
                    <?= csrfField() ?>
                    <input type="hidden" name="accion" value="oficio">
                    <div class="field">
                        <label>Adjuntar oficio</label>
                        <input type="file" name="oficio" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                    </div>
                    <button class="btn btn-ghost" type="submit">Subir oficio</button>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <section class="section side-card">
        <div class="section-head">
            <span class="section-num">4</span>
            <div>
                <h2>Bitácora</h2>
                <p class="hint">Historial del servicio.</p>
            </div>
        </div>
        <?php if (!$bitacora): ?>
            <p class="lead">Aún no hay movimientos.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($bitacora as $b): ?>
                    <li>
                        <time><?= h(formatFecha($b['created_at'], true)) ?> · <?= h(estadoLabel($b['estado'])) ?></time>
                        <div><?= nl2br(h($b['comentario'])) ?></div>
                        <small><?= h($b['usuario_nombre'] ?: 'Sistema') ?> · <?= $b['visible_publico'] ? 'Público' : 'Interno' ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
