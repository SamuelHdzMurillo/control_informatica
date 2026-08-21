<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'La sesión expiró. Intente de nuevo.';
    } else {
        try {
            $id = savePersona(
                $pdo,
                trim($_POST['nombre'] ?? ''),
                trim($_POST['area_dependencia'] ?? ''),
                trim($_POST['telefono'] ?? '')
            );
            flash('ok', 'Perfil de persona guardado.');
            redirect('persona.php?id=' . $id);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$q = trim($_GET['q'] ?? '');
$area = trim($_GET['area'] ?? '');
$personas = listPersonas($pdo, $q, $area);
$areas = listPersonasAreas($pdo);
$hayFiltros = $q !== '' || $area !== '';
$mostrarAlta = $errors !== [];
$pageTitle = 'Personas';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Directorio</p>
        <h1>Personas</h1>
    </div>
    <button class="btn btn-ok" type="button" data-toggle-panel="#alta-persona">+ Registrar</button>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<section class="card ppl-alta" id="alta-persona" <?= $mostrarAlta ? '' : 'hidden' ?>>
    <div class="section-head">
        <span class="section-num">+</span>
        <div>
            <h2>Registrar persona</h2>
            <p class="hint">Si el nombre ya existe, se actualiza el perfil.</p>
        </div>
    </div>
    <form method="post" class="ppl-form-grid">
        <?= csrfField() ?>
        <div class="field">
            <label>Nombre <span class="req">*</span></label>
            <input name="nombre" required value="<?= h($_POST['nombre'] ?? '') ?>" placeholder="Nombre completo">
        </div>
        <div class="field">
            <label>Área / dependencia</label>
            <input name="area_dependencia" value="<?= h($_POST['area_dependencia'] ?? '') ?>" placeholder="Plantel, área o departamento">
        </div>
        <div class="field">
            <label>Teléfono</label>
            <input name="telefono" value="<?= h($_POST['telefono'] ?? '') ?>" placeholder="Opcional">
        </div>
        <button class="btn btn-ok" type="submit">Guardar</button>
    </form>
</section>

<section class="card">
    <form method="get" class="inv-bar" style="margin-bottom:12px">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar nombre, área o teléfono">
        <select name="area">
            <option value="">Todas las áreas</option>
            <?php foreach ($areas as $a): ?>
                <option value="<?= h($a) ?>" <?= $area === $a ? 'selected' : '' ?>><?= h($a) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Buscar</button>
        <?php if ($hayFiltros): ?>
            <a class="btn btn-ghost" href="<?= h(url('personas.php')) ?>">Quitar</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
    <table class="inv-table ppl-table">
        <thead>
            <tr>
                <th>Persona</th>
                <th>Contacto</th>
                <th>Actividad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$personas): ?>
            <tr>
                <td colspan="4">
                    <div class="empty-state">
                        <?= $hayFiltros ? 'Ningún perfil coincide con la búsqueda.' : 'Aún no hay perfiles. Se crean al recibir un equipo o con Registrar.' ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        <?php foreach ($personas as $p): ?>
            <?php
            $equipos = (int) ($p['equipos'] ?? 0);
            $servicios = (int) ($p['servicios'] ?? 0);
            ?>
            <tr>
                <td>
                    <div class="ppl-person">
                        <span class="ppl-avatar"><?= h(nombreIniciales($p['nombre'] ?? '')) ?></span>
                        <div>
                            <strong><?= h($p['nombre']) ?></strong>
                            <small><?= h($p['area_dependencia'] ?: 'Sin área') ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <?= h($p['telefono'] ?: 'Sin teléfono') ?>
                </td>
                <td>
                    <?= $equipos === 1 ? '1 equipo' : $equipos . ' equipos' ?>
                    · <?= $servicios === 1 ? '1 servicio' : $servicios . ' servicios' ?>
                    <?php
                    $prestamosAct = (int) ($p['prestamos_activos'] ?? 0);
                    if ($prestamosAct > 0):
                    ?>
                        · <?= $prestamosAct === 1 ? '1 préstamo activo' : $prestamosAct . ' préstamos activos' ?>
                    <?php endif; ?>
                    <?php if (!empty($p['ultimo_servicio'])): ?>
                        <small>Último ingreso <?= h(formatFecha($p['ultimo_servicio'], true)) ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('persona.php?id=' . $p['id'])) ?>">Ver</a>
                        <a class="btn btn-sm btn-ghost" href="<?= h(url('prestar.php?persona=' . $p['id'])) ?>">Prestar</a>
                        <a class="btn btn-sm btn-ok" href="<?= h(url('recibir.php?persona=' . $p['id'])) ?>">Recibir</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
