<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'La sesión expiró. Intente de nuevo.';
    } else {
        try {
            $id = saveBienInterno($pdo, [
                'tipo_equipo' => trim($_POST['tipo_equipo'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'numero_serie' => trim($_POST['numero_serie'] ?? ''),
                'numero_inventario' => trim($_POST['numero_inventario'] ?? ''),
                'estado_fisico' => trim($_POST['estado_fisico'] ?? ''),
                'accesorios' => trim($_POST['accesorios'] ?? ''),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            ]);
            if (!empty($_FILES['fotos']['name'][0])) {
                addFotosInternos($pdo, $id, $_FILES['fotos']);
            }
            flash('ok', 'Bien interno registrado.');
            redirect('bien_interno.php?id=' . $id);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$q = trim($_GET['q'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$marca = trim($_GET['marca'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$ubicacion = trim($_GET['ubicacion'] ?? '');
$filters = compact('tipo', 'marca', 'estado', 'ubicacion');
$bienes = listBienesInternos($pdo, $q, $filters);
$marcas = listBienesInternosMarcas($pdo);
$ubicaciones = listBienesInternosUbicaciones($pdo);
$hayFiltros = $q !== '' || $tipo !== '' || $marca !== '' || $estado !== '' || $ubicacion !== '';
$nFiltros = (int) array_sum(array_map(static fn($v) => $v !== '' ? 1 : 0, [$tipo, $marca, $estado, $ubicacion]));
$mostrarAlta = $errors !== [];
$pageTitle = 'Bienes internos';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Inventario interno</p>
        <h1>Bienes de informática</h1>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= h(url('prestar.php')) ?>">Prestar</a>
        <button class="btn btn-ok" type="button" data-toggle-panel="#alta-bien-interno">+ Registrar</button>
    </div>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<section class="card ppl-alta" id="alta-bien-interno" <?= $mostrarAlta ? '' : 'hidden' ?>>
    <div class="section-head">
        <span class="section-num">+</span>
        <div>
            <h2>Registrar bien interno</h2>
            <p class="hint">Catálogo del área de informática. No se mezcla con el inventario de soporte.</p>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data" class="ppl-form-grid bi-alta-grid">
        <?= csrfField() ?>
        <div class="field">
            <label>Tipo <span class="req">*</span></label>
            <select name="tipo_equipo" required>
                <option value="">Seleccione</option>
                <?php foreach (tiposEquipoInterno() as $t): ?>
                    <option <?= (($old['tipo_equipo'] ?? '') === $t) ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Marca <span class="req">*</span></label>
            <input name="marca" required value="<?= h($old['marca'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Modelo <span class="req">*</span></label>
            <input name="modelo" required value="<?= h($old['modelo'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Número de serie</label>
            <input name="numero_serie" value="<?= h($old['numero_serie'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Número de inventario</label>
            <input name="numero_inventario" value="<?= h($old['numero_inventario'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Estado físico</label>
            <select name="estado_fisico">
                <option value="">Seleccione</option>
                <?php foreach (estadosFisicos() as $t): ?>
                    <option <?= (($old['estado_fisico'] ?? '') === $t) ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Ubicación</label>
            <input name="ubicacion" value="<?= h($old['ubicacion'] ?? '') ?>" placeholder="Almacén, laboratorio, oficina…">
        </div>
        <div class="field field-span">
            <label>Accesorios</label>
            <textarea name="accesorios" placeholder="Cargador, cables, control, funda…"><?= h($old['accesorios'] ?? '') ?></textarea>
        </div>
        <div class="field field-span">
            <label>Observaciones</label>
            <textarea name="observaciones"><?= h($old['observaciones'] ?? '') ?></textarea>
        </div>
        <div class="field field-span dropzone">
            <label>Fotografías</label>
            <input type="file" name="fotos[]" accept="image/*" multiple data-preview-fotos>
            <div class="photos" data-preview-box></div>
        </div>
        <button class="btn btn-ok" type="submit">Guardar</button>
    </form>
</section>

<section class="card inv-card">
    <form method="get" class="inv-form" data-filters-open="<?= $hayFiltros ? '1' : '0' ?>">
        <div class="inv-bar">
            <input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar serie, inventario, marca, ubicación o persona">
            <button class="btn btn-ghost" type="button" data-toggle-filters>
                Filtros<?= $nFiltros ? ' · ' . $nFiltros : '' ?>
            </button>
            <button class="btn btn-primary" type="submit">Buscar</button>
            <?php if ($hayFiltros): ?>
                <a class="btn btn-ghost" href="<?= h(url('bienes_internos.php')) ?>">Quitar</a>
            <?php endif; ?>
        </div>
        <div class="inv-filters" data-filters-panel <?= $nFiltros ? '' : 'hidden' ?>>
            <select name="tipo">
                <option value="">Tipo</option>
                <?php foreach (tiposEquipoInterno() as $t): ?>
                    <option value="<?= h($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="marca">
                <option value="">Marca</option>
                <?php foreach ($marcas as $m): ?>
                    <option value="<?= h($m) ?>" <?= $marca === $m ? 'selected' : '' ?>><?= h($m) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="estado">
                <option value="">Estado</option>
                <?php foreach (estadosBienInterno() as $k => $label): ?>
                    <option value="<?= h($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="ubicacion">
                <option value="">Ubicación</option>
                <?php foreach ($ubicaciones as $u): ?>
                    <option value="<?= h($u) ?>" <?= $ubicacion === $u ? 'selected' : '' ?>><?= h($u) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="table-wrap">
    <table class="inv-table">
        <thead>
            <tr>
                <th>Bien</th>
                <th>Ubicación</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$bienes): ?>
            <tr><td colspan="4"><?= $hayFiltros ? 'Ningún bien coincide con los filtros.' : 'Aún no hay bienes internos. Use Registrar para darlos de alta.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($bienes as $b): ?>
            <?php
            $ids = [];
            $ids[] = $b['numero_inventario'] ? 'Inv. ' . $b['numero_inventario'] : 'Sin inventario';
            $ids[] = $b['numero_serie'] ? 'Serie ' . $b['numero_serie'] : 'Sin serie';
            if (!empty($b['estado_fisico'])) {
                $ids[] = $b['estado_fisico'];
            }
            ?>
            <tr class="<?= $b['estado'] === 'prestado' && !empty($b['prestamo_compromiso']) && $b['prestamo_compromiso'] < date('Y-m-d') ? 'row-vencido' : '' ?>">
                <td>
                    <strong><?= h($b['tipo_equipo']) ?></strong>
                    <?= h(trim($b['marca'] . ' ' . $b['modelo'])) ?>
                    <small><?= h(implode(' · ', $ids)) ?></small>
                </td>
                <td><?= h($b['ubicacion'] ?: '—') ?></td>
                <td>
                    <span class="badge st-<?= h($b['estado']) ?>"><?= h(estadoBienInternoLabel($b['estado'])) ?></span>
                    <?php if ($b['estado'] === 'prestado' && !empty($b['prestamo_folio'])): ?>
                        <small>
                            <a href="<?= h(url('prestamo.php?id=' . $b['prestamo_id'])) ?>"><?= h($b['prestamo_folio']) ?></a>
                            · <?= h($b['prestamo_persona'] ?: '') ?>
                            · hasta <?= h(formatFecha($b['prestamo_compromiso'] ?? null)) ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('bien_interno.php?id=' . $b['id'])) ?>">Ver</a>
                        <?php if ($b['estado'] === 'disponible'): ?>
                            <a class="btn btn-sm btn-ok" href="<?= h(url('prestar.php?bien=' . $b['id'])) ?>">Prestar</a>
                        <?php elseif ($b['estado'] === 'prestado' && !empty($b['prestamo_id'])): ?>
                            <a class="btn btn-sm btn-ok" href="<?= h(url('prestamo.php?id=' . $b['prestamo_id'])) ?>">Devolver</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
