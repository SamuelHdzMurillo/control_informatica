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
            $comentario = trim($_POST['comentario'] ?? '');
            $publico = isset($_POST['visible_publico']);
            if (!isset(estadosEquipo()[$estado])) {
                throw new RuntimeException('Estado no válido.');
            }
            if ($comentario === '') {
                $comentario = 'El equipo pasó a: ' . estadoLabel($estado);
            }
            $upd = $pdo->prepare('UPDATE equipos SET estado = ? WHERE id = ?');
            $upd->execute([$estado, $id]);
            addBitacora($pdo, $id, $estado, $comentario, $publico, (int) currentUser()['id']);
            if ($estado === 'listo') {
                flash('ok', 'Equipo listo para entrega. Imprima la orden de servicio.');
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
$pageTitle = $eq['folio'];
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow">Folio de servicio</p>
        <h1><?= h($eq['folio']) ?></h1>
        <p class="lead"><?= h($eq['tipo_equipo'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']) ?></p>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('recibo.php?id=' . $eq['id'])) ?>">Imprimir recibo</a>
        <?php if (puedeEmitirOrden($eq)): ?>
            <a class="btn btn-ok" href="<?= h(url('orden.php?id=' . $eq['id'])) ?>">Orden de servicio</a>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= h(url('consulta.php?folio=' . urlencode($eq['folio']))) ?>" target="_blank" rel="noopener">Vista pública</a>
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
<p><span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span></p>

<div class="sections">
<div class="kv-cards">
    <section class="section">
        <div class="section-head">
            <span class="section-num">1</span>
            <div>
                <h2>Identificación del equipo</h2>
                <p class="hint">Datos de inventario y tipo de aparato.</p>
            </div>
        </div>
        <dl>
            <div class="kv-item"><dt>Tipo de equipo</dt><dd><?= h($eq['tipo_equipo']) ?></dd></div>
            <div class="kv-item"><dt>Marca</dt><dd><?= h($eq['marca']) ?></dd></div>
            <div class="kv-item"><dt>Modelo</dt><dd><?= h($eq['modelo']) ?></dd></div>
            <div class="kv-item"><dt>Número de serie</dt><dd><?= h($eq['numero_serie'] ?: '—') ?></dd></div>
            <div class="kv-item"><dt>Número de inventario</dt><dd><?= h($eq['numero_inventario'] ?: '—') ?></dd></div>
            <?php if (!empty($eq['bien_id'])): ?>
                <div class="kv-item wide"><dt>Perfil del equipo</dt><dd>
                    <a class="btn btn-sm btn-ghost" href="<?= h(url('bien.php?id=' . $eq['bien_id'])) ?>">Ver historial</a>
                </dd></div>
            <?php endif; ?>
        </dl>
    </section>

    <section class="section">
        <div class="section-head">
            <span class="section-num">2</span>
            <div>
                <h2>Quién lo entregó</h2>
                <p class="hint">Datos de ingreso al área de informática.</p>
            </div>
        </div>
        <dl>
            <div class="kv-item"><dt>Persona / área</dt><dd>
                <?php if (!empty($eq['persona_id'])): ?>
                    <a class="btn btn-sm btn-ghost" href="<?= h(url('persona.php?id=' . $eq['persona_id'])) ?>"><?= h($eq['entregado_por']) ?></a>
                <?php else: ?>
                    <?= h($eq['entregado_por']) ?>
                <?php endif; ?>
            </dd></div>
            <div class="kv-item"><dt>Dependencia</dt><dd><?= h($eq['area_dependencia'] ?: '—') ?></dd></div>
            <div class="kv-item"><dt>Teléfono</dt><dd><?= h($eq['telefono'] ?: '—') ?></dd></div>
            <div class="kv-item"><dt>Fecha de recepción</dt><dd><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></dd></div>
            <div class="kv-item"><dt>Recibido por</dt><dd><?= h($eq['recibido_por'] ?: '—') ?></dd></div>
            <?php if ($eq['estado'] === 'entregado'): ?>
                <div class="kv-item"><dt>Entregado a</dt><dd><?= h($eq['entregado_a'] ?: '—') ?> · <?= h(formatFecha($eq['fecha_entrega'], true)) ?></dd></div>
            <?php endif; ?>
        </dl>
    </section>
</div>

<section class="section">
    <div class="section-head">
        <span class="section-num">3</span>
        <div>
            <h2>Problema reportado</h2>
            <p class="hint">Falla indicada al momento de recibir el equipo.</p>
        </div>
    </div>
    <dl class="kv-cards">
        <div class="kv-item"><dt>Problema reportado</dt><dd><?= h($eq['problema_reportado']) ?></dd></div>
        <div class="kv-item"><dt>Tipo de problema</dt><dd><?= h($eq['tipo_problema']) ?></dd></div>
        <div class="kv-item wide"><dt>Descripción detallada de la falla</dt><dd><?= nl2br(h($eq['descripcion_falla'])) ?></dd></div>
    </dl>
</section>

<section class="section">
    <div class="section-head">
        <span class="section-num">4</span>
        <div>
            <h2>Estado físico y accesorios</h2>
            <p class="hint">Condición en que llegó y lo que se recibió junto con el equipo.</p>
        </div>
    </div>
    <dl class="kv-cards">
        <div class="kv-item"><dt>Estado físico</dt><dd><?= h($eq['estado_fisico']) ?></dd></div>
        <div class="kv-item"><dt>Oficio</dt>
            <dd>
                <?php if ($eq['oficio_path']): ?>
                    <a class="btn btn-sm btn-info" href="<?= h(url('archivo.php?tipo=oficio&id=' . $eq['id'])) ?>">Ver oficio</a>
                <?php else: ?>
                    Sin oficio
                <?php endif; ?>
            </dd>
        </div>
        <div class="kv-item wide"><dt>Accesorios recibidos</dt><dd><?= nl2br(h($eq['accesorios'] ?: '—')) ?></dd></div>
        <div class="kv-item wide"><dt>Observaciones</dt><dd><?= nl2br(h($eq['observaciones'] ?: '—')) ?></dd></div>
    </dl>
</section>

<section class="section">
    <div class="section-head">
        <span class="section-num">5</span>
        <div>
            <h2>Fotografías y documentos</h2>
            <p class="hint">Evidencia visual del ingreso. Puede agregar más fotos o el oficio si faltó.</p>
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
    <form method="post" enctype="multipart/form-data" class="stack" style="margin-top:12px">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="fotos">
        <input type="file" name="fotos[]" accept="image/*" multiple required>
        <button class="btn btn-ghost" type="submit">Agregar fotos</button>
    </form>
    <?php if (!$eq['oficio_path']): ?>
        <form method="post" enctype="multipart/form-data" class="stack" style="margin-top:12px">
            <?= csrfField() ?>
            <input type="hidden" name="accion" value="oficio">
            <label>Adjuntar oficio</label>
            <input type="file" name="oficio" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            <button class="btn btn-ghost" type="submit">Subir oficio</button>
        </form>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <span class="section-num">6</span>
        <div>
            <h2>Registrar avance</h2>
            <p class="hint">El comentario que capture en “En reparación” se imprime como trabajo realizado en la orden de servicio.</p>
        </div>
    </div>
    <form method="post" class="stack">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="avance">
        <div class="grid-2">
            <div class="field">
                <label>Nuevo estado</label>
                <select name="estado" required>
                    <?php foreach (estadosEquipo() as $k => $label): ?>
                        <option value="<?= h($k) ?>" <?= $eq['estado'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Comentario para el interesado</label>
                <textarea name="comentario" placeholder="Ej. Se cambió disco duro y se reinstaló el sistema."></textarea>
            </div>
        </div>
        <label><input type="checkbox" name="visible_publico" checked> Visible en la consulta pública</label>
        <button class="btn btn-primary" type="submit">Guardar avance</button>
    </form>
    <?php if ($eq['estado'] !== 'entregado'): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
        <form method="post" class="stack">
            <?= csrfField() ?>
            <input type="hidden" name="accion" value="entregar">
            <div class="field">
                <label>Entregar equipo a</label>
                <input name="entregado_a" required placeholder="Nombre de quien recoge">
            </div>
            <button class="btn btn-ok" type="submit">Marcar como entregado</button>
        </form>
    <?php endif; ?>
</section>

<section class="section">
    <div class="section-head">
        <span class="section-num">7</span>
        <div>
            <h2>Bitácora</h2>
            <p class="hint">Historial de estados y comentarios del servicio.</p>
        </div>
    </div>
    <ul class="timeline">
        <?php foreach ($bitacora as $b): ?>
            <li>
                <time><?= h(formatFecha($b['created_at'], true)) ?> · <?= h(estadoLabel($b['estado'])) ?></time>
                <div><?= nl2br(h($b['comentario'])) ?></div>
                <small><?= h($b['usuario_nombre'] ?: 'Sistema') ?> · <?= $b['visible_publico'] ? 'Público' : 'Interno' ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
