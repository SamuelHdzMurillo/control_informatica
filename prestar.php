<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$errors = [];
$old = $_POST;
$user = currentUser() ?? [];
$preBienId = (int) ($_GET['bien'] ?? 0);
$prePersona = !empty($_GET['persona']) ? getPersona($pdo, (int) $_GET['persona']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'La sesión expiró. Intente de nuevo.';
    } else {
        try {
            $personaSel = (string) ($_POST['persona_id'] ?? '');
            $area = trim($_POST['area_dependencia'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $personaId = 0;
            $nombreNuevo = '';

            if ($personaSel === 'nueva') {
                $nombreNuevo = trim($_POST['entregado_por'] ?? '');
                if ($nombreNuevo === '') {
                    throw new RuntimeException('Capture el nombre de la persona nueva.');
                }
                $personaId = savePersona($pdo, $nombreNuevo, $area, $telefono);
            } elseif ($personaSel !== '' && ctype_digit($personaSel)) {
                $personaId = (int) $personaSel;
                $perfil = getPersona($pdo, $personaId);
                if (!$perfil) {
                    throw new RuntimeException('La persona seleccionada no existe.');
                }
                savePersona(
                    $pdo,
                    (string) $perfil['nombre'],
                    $area !== '' ? $area : ($perfil['area_dependencia'] ?? null),
                    $telefono !== '' ? $telefono : ($perfil['telefono'] ?? null),
                    $personaId
                );
            }

            $prestamoId = crearPrestamo($pdo, [
                'persona_id' => $personaId,
                'fecha_prestamo' => trim($_POST['fecha_prestamo'] ?? ''),
                'fecha_compromiso' => trim($_POST['fecha_compromiso'] ?? ''),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'prestado_por' => $user['nombre'] ?? ORG_AREA,
            ], $_POST['bienes'] ?? []);
            flash('ok', 'Préstamo registrado. Imprima el recibí.');
            redirect('recibo_prestamo.php?id=' . $prestamoId);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
} else {
    if ($prePersona) {
        $old['persona_id'] = (string) $prePersona['id'];
        $old['area_dependencia'] = $prePersona['area_dependencia'] ?? '';
        $old['telefono'] = $prePersona['telefono'] ?? '';
    }
    if ($preBienId > 0) {
        $old['bienes'] = [(string) $preBienId];
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
$disponibles = listBienesInternosDisponibles($pdo);
if ($preBienId > 0) {
    $preBien = getBienInterno($pdo, $preBienId);
    if ($preBien && $preBien['estado'] === 'disponible') {
        $ya = false;
        foreach ($disponibles as $d) {
            if ((int) $d['id'] === $preBienId) {
                $ya = true;
                break;
            }
        }
        if (!$ya) {
            $disponibles[] = $preBien;
        }
    }
}
$seleccionados = array_map('strval', $old['bienes'] ?? []);
$pageTitle = 'Prestar material';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Inventario interno</p>
        <h1>Prestar material</h1>
        <p class="lead">Un recibí puede incluir varios bienes, con una sola fecha de devolución.</p>
    </div>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<script type="application/json" id="data-personas"><?= json_encode($personasJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<form method="post" class="sections">
    <?= csrfField() ?>

    <section class="section">
        <div class="section-head">
            <span class="section-num">1</span>
            <div>
                <h2>Quién lo recibe</h2>
                <p class="hint">Es la persona que firma el recibí. Si no está en el directorio, regístrela aquí.</p>
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
            <div class="field" id="campo-persona-nueva" data-persona-nueva <?= (($old['persona_id'] ?? '') === 'nueva') ? '' : 'hidden' ?> style="<?= (($old['persona_id'] ?? '') === 'nueva') ? '' : 'display:none' ?>">
                <label>Nombre de la persona nueva <span class="req">*</span></label>
                <input name="entregado_por" id="entregado_por" value="<?= h($old['entregado_por'] ?? '') ?>">
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
        } catch (e) { personas = []; }
        var sel = document.getElementById('persona_id');
        var wrap = document.getElementById('campo-persona-nueva');
        var nombre = document.getElementById('entregado_por');
        var area = document.getElementById('area_dependencia');
        var tel = document.getElementById('telefono');
        if (!sel) return;
        function syncPersona(fromChange) {
            var valor = sel.value;
            var esNueva = valor === 'nueva';
            if (wrap) { wrap.hidden = !esNueva; wrap.style.display = esNueva ? '' : 'none'; }
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
                if (String(personas[i].id) === String(valor)) { p = personas[i]; break; }
            }
            var opt = sel.options[sel.selectedIndex];
            if (area) area.value = (p && p.area) || (opt && opt.getAttribute('data-area')) || '';
            if (tel) tel.value = (p && p.telefono) || (opt && opt.getAttribute('data-telefono')) || '';
        }
        sel.addEventListener('change', function () { syncPersona(true); });
        syncPersona(false);
    })();
    </script>

    <section class="section">
        <div class="section-head">
            <span class="section-num">2</span>
            <div>
                <h2>Bienes a prestar</h2>
                <p class="hint">Solo aparecen los que están disponibles. Puede marcar varios para el mismo recibí.</p>
            </div>
        </div>
        <?php if (!$disponibles): ?>
            <p class="empty-state">No hay bienes disponibles. <a href="<?= h(url('bienes_internos.php')) ?>">Registre material</a> o espere una devolución.</p>
        <?php else: ?>
            <div class="field" style="margin-bottom:10px">
                <input type="search" data-pick-filter placeholder="Filtrar por tipo, marca, serie o inventario">
            </div>
            <div class="pick-list" data-pick-list>
                <?php foreach ($disponibles as $b): ?>
                    <?php
                    $texto = strtolower(trim(
                        ($b['tipo_equipo'] ?? '') . ' ' . ($b['marca'] ?? '') . ' ' . ($b['modelo'] ?? '') . ' ' .
                        ($b['numero_serie'] ?? '') . ' ' . ($b['numero_inventario'] ?? '') . ' ' . ($b['ubicacion'] ?? '')
                    ));
                    ?>
                    <label class="pick-item" data-pick-text="<?= h($texto) ?>">
                        <input type="checkbox" name="bienes[]" value="<?= (int) $b['id'] ?>" <?= in_array((string) $b['id'], $seleccionados, true) ? 'checked' : '' ?>>
                        <span>
                            <strong><?= h($b['tipo_equipo']) ?></strong>
                            <?= h(trim($b['marca'] . ' ' . $b['modelo'])) ?>
                            <small>
                                <?= h($b['numero_inventario'] ? 'Inv. ' . $b['numero_inventario'] : 'Sin inventario') ?>
                                · <?= h($b['numero_serie'] ? 'Serie ' . $b['numero_serie'] : 'Sin serie') ?>
                                <?php if (!empty($b['ubicacion'])): ?> · <?= h($b['ubicacion']) ?><?php endif; ?>
                                <?php if (!empty($b['accesorios'])): ?> · <?= h($b['accesorios']) ?><?php endif; ?>
                            </small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section">
        <div class="section-head">
            <span class="section-num">3</span>
            <div>
                <h2>Tiempo y notas</h2>
                <p class="hint">Al pasar la fecha de compromiso el préstamo aparece como vencido en el panel.</p>
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label>Fecha de préstamo</label>
                <input type="datetime-local" name="fecha_prestamo" value="<?= h($old['fecha_prestamo'] ?? date('Y-m-d\TH:i')) ?>">
            </div>
            <div class="field">
                <label>Fecha de devolución <span class="req">*</span></label>
                <div class="btn-row" style="margin-bottom:8px">
                    <button class="btn btn-sm btn-ghost" type="button" data-dias="1">1 día</button>
                    <button class="btn btn-sm btn-ghost" type="button" data-dias="7">7 días</button>
                    <button class="btn btn-sm btn-ghost" type="button" data-dias="15">15 días</button>
                    <button class="btn btn-sm btn-ghost" type="button" data-dias="30">30 días</button>
                </div>
                <input type="date" name="fecha_compromiso" id="fecha_compromiso" required value="<?= h($old['fecha_compromiso'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
            </div>
            <div class="field field-span">
                <label>Observaciones</label>
                <textarea name="observaciones" placeholder="Motivo del préstamo, condiciones, etc."><?= h($old['observaciones'] ?? '') ?></textarea>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-ok" type="submit" <?= $disponibles ? '' : 'disabled' ?>>Registrar y generar recibí</button>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
