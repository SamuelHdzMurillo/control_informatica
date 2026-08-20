<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$errors = [];
$old = $_POST;
$pdo = db();
$prePersona = !empty($_GET['persona']) ? getPersona($pdo, (int) $_GET['persona']) : null;
$preBien = !empty($_GET['bien']) ? getBien($pdo, (int) $_GET['bien']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'La sesión expiró. Intente de nuevo.';
    }

    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $numeroSerie = trim($_POST['numero_serie'] ?? '');
    $numeroInventario = trim($_POST['numero_inventario'] ?? '');
    $tipoEquipo = trim($_POST['tipo_equipo'] ?? '');
    $personaSel = (string) ($_POST['persona_id'] ?? '');
    $area = trim($_POST['area_dependencia'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaRecepcion = trim($_POST['fecha_recepcion'] ?? '');
    $problema = trim($_POST['problema_reportado'] ?? '');
    $tipoProblema = trim($_POST['tipo_problema'] ?? '');
    $descripcion = trim($_POST['descripcion_falla'] ?? '');
    $estadoFisico = trim($_POST['estado_fisico'] ?? '');
    $accesorios = trim($_POST['accesorios'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $bienId = (int) ($_POST['bien_id'] ?? 0);
    $personaId = 0;
    $entregadoPor = '';

    if ($personaSel === 'nueva') {
        $entregadoPor = trim($_POST['entregado_por'] ?? '');
        if ($entregadoPor === '') {
            $errors[] = 'Capture el nombre de la persona nueva.';
        }
    } elseif ($personaSel !== '' && ctype_digit($personaSel)) {
        $personaId = (int) $personaSel;
        $perfil = getPersona($pdo, $personaId);
        if (!$perfil) {
            $errors[] = 'La persona seleccionada no existe.';
        } else {
            $entregadoPor = $perfil['nombre'];
            if ($area === '') {
                $area = (string) ($perfil['area_dependencia'] ?? '');
            }
            if ($telefono === '') {
                $telefono = (string) ($perfil['telefono'] ?? '');
            }
        }
    } else {
        $errors[] = 'Seleccione quién entrega el equipo.';
    }

    if ($marca === '') $errors[] = 'La marca es obligatoria.';
    if ($modelo === '') $errors[] = 'El modelo es obligatorio.';
    if ($tipoEquipo === '') $errors[] = 'El tipo de equipo es obligatorio.';
    if ($problema === '') $errors[] = 'El problema reportado es obligatorio.';
    if ($tipoProblema === '') $errors[] = 'El tipo de problema es obligatorio.';
    if ($descripcion === '') $errors[] = 'La descripción de la falla es obligatoria.';
    if ($estadoFisico === '') $errors[] = 'El estado físico es obligatorio.';
    if ($fechaRecepcion === '') {
        $fechaRecepcion = date('Y-m-d\TH:i');
    }

    $fechaSql = date('Y-m-d H:i:s', strtotime($fechaRecepcion) ?: time());

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            if ($bienId > 0) {
                $selBien = getBien($pdo, $bienId);
                $mismaSerie = $selBien && $numeroSerie !== '' && strcasecmp((string) $selBien['numero_serie'], $numeroSerie) === 0;
                $mismoInv = $selBien && $numeroInventario !== '' && strcasecmp((string) $selBien['numero_inventario'], $numeroInventario) === 0;
                $mismoEquipo = $selBien
                    && strcasecmp((string) $selBien['marca'], $marca) === 0
                    && strcasecmp((string) $selBien['modelo'], $modelo) === 0
                    && strcasecmp((string) $selBien['tipo_equipo'], $tipoEquipo) === 0;
                if (!$selBien || (!$mismaSerie && !$mismoInv && !$mismoEquipo)) {
                    $bienId = 0;
                }
            }
            $personaId = savePersona($pdo, $entregadoPor, $area, $telefono, $personaId);
            $bienId = saveBien($pdo, [
                'tipo_equipo' => $tipoEquipo,
                'marca' => $marca,
                'modelo' => $modelo,
                'numero_serie' => $numeroSerie,
                'numero_inventario' => $numeroInventario,
            ], $personaId, $bienId);
            $folio = generateFolio($pdo);
            $oficio = null;
            if (!empty($_FILES['oficio']['name'])) {
                $oficio = saveUploadedFile(
                    $_FILES['oficio'],
                    'oficios',
                    ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                    MAX_OFICIO_BYTES
                );
            }

            $stmt = $pdo->prepare(
                'INSERT INTO equipos (
                    folio, persona_id, bien_id, marca, modelo, numero_serie, numero_inventario, tipo_equipo,
                    entregado_por, area_dependencia, telefono, fecha_recepcion, problema_reportado,
                    tipo_problema, descripcion_falla, estado_fisico, accesorios, observaciones,
                    oficio_path, estado, recibido_por
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $folio, $personaId, $bienId, $marca, $modelo, $numeroSerie ?: null, $numeroInventario ?: null, $tipoEquipo,
                $entregadoPor, $area ?: null, $telefono ?: null, $fechaSql, $problema,
                $tipoProblema, $descripcion, $estadoFisico, $accesorios ?: null, $observaciones ?: null,
                $oficio, 'recibido', currentUser()['nombre'] ?? null,
            ]);
            $id = (int) $pdo->lastInsertId();

            if (!empty($_FILES['fotos']['name'][0])) {
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
            }

            addBitacora(
                $pdo,
                $id,
                'recibido',
                'Equipo recibido. Folio ' . $folio . ' asignado. Se entregó recibo de ingreso a quien lo dejó.',
                true,
                (int) currentUser()['id']
            );
            $pdo->commit();
            flash('ok', 'Equipo recibido. Se actualizó el perfil de la persona y del bien. Folio ' . $folio . '.');
            redirect('recibo.php?id=' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
} elseif ($prePersona || $preBien) {
    if ($prePersona) {
        $old['persona_id'] = $prePersona['id'];
        $old['entregado_por'] = $prePersona['nombre'];
        $old['area_dependencia'] = $prePersona['area_dependencia'];
        $old['telefono'] = $prePersona['telefono'];
    }
    if ($preBien) {
        $old['bien_id'] = $preBien['id'];
        $old['tipo_equipo'] = $preBien['tipo_equipo'];
        $old['marca'] = $preBien['marca'];
        $old['modelo'] = $preBien['modelo'];
        $old['numero_serie'] = $preBien['numero_serie'];
        $old['numero_inventario'] = $preBien['numero_inventario'];
        if (!$prePersona && !empty($preBien['persona_id'])) {
            $dueno = getPersona($pdo, (int) $preBien['persona_id']);
            if ($dueno) {
                $old['persona_id'] = $dueno['id'];
                $old['entregado_por'] = $dueno['nombre'];
                $old['area_dependencia'] = $dueno['area_dependencia'];
                $old['telefono'] = $dueno['telefono'];
            }
        }
    }
}

$personas = listPersonas($pdo);
$personasJson = array_map(static function (array $p): array {
    return [
        'id' => (int) $p['id'],
        'nombre' => $p['nombre'],
        'area' => $p['area_dependencia'] ?? '',
        'telefono' => $p['telefono'] ?? '',
    ];
}, $personas);
$bienesJson = array_map(static function (array $b): array {
    return [
        'id' => (int) $b['id'],
        'tipo' => $b['tipo_equipo'],
        'marca' => $b['marca'],
        'modelo' => $b['modelo'],
        'serie' => $b['numero_serie'] ?? '',
        'inventario' => $b['numero_inventario'] ?? '',
        'persona' => $b['persona_nombre'] ?? '',
        'persona_id' => (int) ($b['persona_id'] ?? 0),
    ];
}, listBienes($pdo));

$pageTitle = 'Recibir equipo';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Ingreso</p>
        <h1>Recibir equipo</h1>
        <p class="lead">Elija a la persona del directorio o registre una nueva. Los campos con <span class="req">*</span> son obligatorios.</p>
    </div>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<script type="application/json" id="data-personas"><?= json_encode($personasJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="application/json" id="data-bienes"><?= json_encode($bienesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<form method="post" enctype="multipart/form-data" class="sections">
    <?= csrfField() ?>
    <input type="hidden" name="bien_id" id="bien_id" value="<?= h((string) ($old['bien_id'] ?? '')) ?>">

    <section class="section">
        <div class="section-head">
            <span class="section-num">1</span>
            <div>
                <h2>Quién lo entrega</h2>
                <p class="hint">Seleccione una persona dada de alta. Si no está en la lista, elija “Registrar persona nueva”.</p>
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>Persona <span class="req">*</span></label>
                <select name="persona_id" id="persona_id" required data-persona-select>
                    <option value="">Seleccione</option>
                    <?php foreach ($personas as $p): ?>
                        <option
                            value="<?= (int) $p['id'] ?>"
                            data-area="<?= h($p['area_dependencia'] ?? '') ?>"
                            data-telefono="<?= h($p['telefono'] ?? '') ?>"
                            <?= ((string) ($old['persona_id'] ?? '') === (string) $p['id']) ? 'selected' : '' ?>
                        ><?= h($p['nombre']) ?><?= !empty($p['area_dependencia']) ? ' · ' . h($p['area_dependencia']) : '' ?></option>
                    <?php endforeach; ?>
                    <option value="nueva" <?= (($old['persona_id'] ?? '') === 'nueva') ? 'selected' : '' ?>>+ Registrar persona nueva</option>
                </select>
            </div>
            <div class="field">
                <label>Fecha de recepción</label>
                <input type="datetime-local" name="fecha_recepcion" value="<?= h($old['fecha_recepcion'] ?? date('Y-m-d\TH:i')) ?>">
            </div>
            <div class="field" id="campo-persona-nueva" data-persona-nueva <?= (($old['persona_id'] ?? '') === 'nueva') ? '' : 'hidden' ?> style="<?= (($old['persona_id'] ?? '') === 'nueva') ? '' : 'display:none' ?>">
                <label>Nombre de la persona nueva <span class="req">*</span></label>
                <input name="entregado_por" id="entregado_por" value="<?= h($old['entregado_por'] ?? '') ?>" <?= (($old['persona_id'] ?? '') === 'nueva') ? 'required' : '' ?>>
            </div>
            <div class="field">
                <label>Área / dependencia</label>
                <input name="area_dependencia" id="area_dependencia" value="<?= h($old['area_dependencia'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Teléfono o extensión</label>
                <input name="telefono" id="telefono" value="<?= h($old['telefono'] ?? '') ?>">
            </div>
        </div>
    </section>
    <script>
    (function () {
        var personas = [];
        try {
            var raw = document.getElementById('data-personas');
            personas = raw ? JSON.parse(raw.textContent || '[]') : [];
        } catch (e) {
            personas = [];
        }
        var sel = document.getElementById('persona_id');
        var wrap = document.getElementById('campo-persona-nueva');
        var nombre = document.getElementById('entregado_por');
        var area = document.getElementById('area_dependencia');
        var tel = document.getElementById('telefono');
        if (!sel) return;

        function datoOpcion(opt, clave, attr) {
            if (!opt) return '';
            var fromDs = opt.dataset ? (opt.dataset[clave] || '') : '';
            return fromDs || opt.getAttribute(attr) || '';
        }

        function syncPersona(fromChange) {
            var valor = sel.value;
            var esNueva = valor === 'nueva';
            if (wrap) {
                wrap.hidden = !esNueva;
                wrap.style.display = esNueva ? '' : 'none';
            }
            if (nombre) nombre.required = esNueva;

            if (esNueva) {
                if (fromChange) {
                    if (nombre) nombre.value = '';
                    if (area) area.value = '';
                    if (tel) tel.value = '';
                }
                return;
            }

            if (nombre) nombre.value = '';
            if (valor === '') {
                if (area) area.value = '';
                if (tel) tel.value = '';
                return;
            }

            var p = null;
            for (var i = 0; i < personas.length; i++) {
                if (String(personas[i].id) === String(valor)) {
                    p = personas[i];
                    break;
                }
            }
            var opt = sel.options[sel.selectedIndex];
            if (area) area.value = (p && p.area) || datoOpcion(opt, 'area', 'data-area');
            if (tel) tel.value = (p && p.telefono) || datoOpcion(opt, 'telefono', 'data-telefono');
        }

        sel.addEventListener('change', function () { syncPersona(true); });
        sel.addEventListener('input', function () { syncPersona(true); });
        syncPersona(false);
    })();
    </script>

    <section class="section">
        <div class="section-head">
            <span class="section-num">2</span>
            <div>
                <h2>Identificación del equipo</h2>
                <p class="hint">Busque por serie o inventario para traer el historial del mismo bien. Si es la primera vez, se crea el perfil del equipo.</p>
            </div>
        </div>
        <div class="grid-3">
            <div class="field">
                <label>Tipo de equipo <span class="req">*</span></label>
                <select name="tipo_equipo" id="tipo_equipo" required>
                    <option value="">Seleccione</option>
                    <?php foreach (tiposEquipo() as $t): ?>
                        <option <?= (($old['tipo_equipo'] ?? '') === $t) ? 'selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Marca <span class="req">*</span></label>
                <input name="marca" id="marca" required value="<?= h($old['marca'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Modelo <span class="req">*</span></label>
                <input name="modelo" id="modelo" required value="<?= h($old['modelo'] ?? '') ?>">
            </div>
            <div class="field suggest-wrap">
                <label>Número de serie</label>
                <input name="numero_serie" id="numero_serie" autocomplete="off" value="<?= h($old['numero_serie'] ?? '') ?>" data-suggest-bien="serie">
                <div class="suggest-box" data-suggest-box-bien hidden></div>
            </div>
            <div class="field suggest-wrap">
                <label>Número de inventario</label>
                <input name="numero_inventario" id="numero_inventario" autocomplete="off" value="<?= h($old['numero_inventario'] ?? '') ?>" data-suggest-bien="inventario">
                <div class="suggest-box" data-suggest-box-bien-inv hidden></div>
                <small class="chip-sel" data-bien-estado><?= !empty($old['bien_id']) ? 'Equipo del inventario seleccionado' : 'Se creará el perfil del equipo' ?></small>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <span class="section-num">3</span>
            <div>
                <h2>Problema reportado</h2>
                <p class="hint">Qué falla indica quien entrega y el detalle técnico de la solicitud.</p>
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>Problema reportado <span class="req">*</span></label>
                <input name="problema_reportado" required value="<?= h($old['problema_reportado'] ?? '') ?>" placeholder="Ej. No enciende, no imprime, se traba Windows">
            </div>
            <div class="field">
                <label>Tipo de problema <span class="req">*</span></label>
                <select name="tipo_problema" required>
                    <option value="">Seleccione</option>
                    <?php foreach (tiposProblema() as $t): ?>
                        <option <?= (($old['tipo_problema'] ?? '') === $t) ? 'selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field" style="margin-top:14px">
            <label>Descripción detallada de la falla <span class="req">*</span></label>
            <textarea name="descripcion_falla" required placeholder="Cuándo empezó, qué se intentó, mensajes de error, etc."><?= h($old['descripcion_falla'] ?? '') ?></textarea>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <span class="section-num">4</span>
            <div>
                <h2>Estado físico y accesorios</h2>
                <p class="hint">Cómo llega el equipo y qué se recibe junto con él.</p>
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>Estado físico del equipo <span class="req">*</span></label>
                <select name="estado_fisico" required>
                    <option value="">Seleccione</option>
                    <?php foreach (estadosFisicos() as $t): ?>
                        <option <?= (($old['estado_fisico'] ?? '') === $t) ? 'selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Accesorios recibidos</label>
                <textarea name="accesorios" placeholder="Cargador, cable de poder, mouse, maletín, tóner, etc."><?= h($old['accesorios'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="field" style="margin-top:14px">
            <label>Observaciones</label>
            <textarea name="observaciones" placeholder="Notas internas o del interesado al momento de recibir."><?= h($old['observaciones'] ?? '') ?></textarea>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <span class="section-num">5</span>
            <div>
                <h2>Documentos y evidencias</h2>
                <p class="hint">Oficio de solicitud (si lo traen) y fotografías del equipo al ingresar.</p>
            </div>
        </div>
        <div class="grid-2">
            <div class="field dropzone">
                <label>Oficio (PDF, Word o imagen)</label>
                <input type="file" name="oficio" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>
            <div class="field dropzone">
                <label>Fotografías del equipo</label>
                <input type="file" name="fotos[]" accept="image/*" multiple data-preview-fotos>
                <div class="photos" data-preview-box></div>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-ok" type="submit">Registrar y generar recibo</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
