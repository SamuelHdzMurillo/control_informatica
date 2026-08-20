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
$personas = listPersonas($pdo, $q);
$pageTitle = 'Personas';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Directorio</p>
        <h1>Personas</h1>
        <p class="lead">Perfiles de quienes entregan equipos. Sirven para no recapturar datos y ver su historial.</p>
    </div>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<section class="section" style="margin-bottom:16px">
    <div class="section-head">
        <span class="section-num">+</span>
        <div>
            <h2>Registrar persona</h2>
            <p class="hint">Si el nombre ya existe, se actualiza el perfil.</p>
        </div>
    </div>
    <form method="post" class="grid-3">
        <?= csrfField() ?>
        <div class="field">
            <label>Nombre <span class="req">*</span></label>
            <input name="nombre" required value="<?= h($_POST['nombre'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Área / dependencia</label>
            <input name="area_dependencia" value="<?= h($_POST['area_dependencia'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Teléfono</label>
            <input name="telefono" value="<?= h($_POST['telefono'] ?? '') ?>">
        </div>
        <div class="field" style="justify-content:end">
            <label>&nbsp;</label>
            <button class="btn btn-ok" type="submit">Guardar perfil</button>
        </div>
    </form>
</section>

<section class="card">
    <form class="toolbar" method="get">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Nombre, área o teléfono">
        <button class="btn btn-primary" type="submit">Buscar</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>Persona</th>
                <th>Área</th>
                <th>Teléfono</th>
                <th>Equipos</th>
                <th>Servicios</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$personas): ?>
            <tr><td colspan="6">Aún no hay perfiles. Se crean al recibir un equipo o con el formulario de arriba.</td></tr>
        <?php endif; ?>
        <?php foreach ($personas as $p): ?>
            <tr>
                <td><strong><?= h($p['nombre']) ?></strong></td>
                <td><?= h($p['area_dependencia'] ?: '—') ?></td>
                <td><?= h($p['telefono'] ?: '—') ?></td>
                <td><?= (int) $p['equipos'] ?></td>
                <td><?= (int) $p['servicios'] ?></td>
                <td>
                    <a href="<?= h(url('persona.php?id=' . $p['id'])) ?>">Ver perfil</a>
                    ·
                    <a href="<?= h(url('recibir.php?persona=' . $p['id'])) ?>">Recibir equipo</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
