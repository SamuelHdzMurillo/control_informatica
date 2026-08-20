<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireAdmin();

$pdo = db();
$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'La sesión expiró. Intente de nuevo.';
    } else {
        $accion = (string) ($_POST['accion'] ?? 'crear');
        try {
            if ($accion === 'clave') {
                resetUsuarioPassword(
                    $pdo,
                    (int) ($_POST['usuario_id'] ?? 0),
                    (string) ($_POST['nueva_password'] ?? '')
                );
                flash('ok', 'Contraseña actualizada.');
                redirect('tecnicos.php');
            }

            $pass = (string) ($_POST['password'] ?? '');
            $pass2 = (string) ($_POST['password2'] ?? '');
            if ($pass !== $pass2) {
                throw new RuntimeException('Las contraseñas no coinciden.');
            }
            saveTecnico(
                $pdo,
                (string) ($_POST['nombre'] ?? ''),
                (string) ($_POST['usuario'] ?? ''),
                $pass
            );
            flash('ok', 'Técnico dado de alta. Ya puede entrar con su usuario.');
            redirect('tecnicos.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$usuarios = listUsuarios($pdo);
$pageTitle = 'Técnicos';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Administración</p>
        <h1>Técnicos</h1>
        <p class="lead">Alta de cuentas para el personal de informática. Solo el administrador puede crearlas.</p>
    </div>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= h($err) ?></div>
<?php endforeach; ?>

<section class="section" style="margin-bottom:16px">
    <div class="section-head">
        <span class="section-num">+</span>
        <div>
            <h2>Agregar técnico</h2>
            <p class="hint">Con estos datos podrá iniciar sesión y registrar equipos.</p>
        </div>
    </div>
    <form method="post" class="grid-3" autocomplete="off">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="crear">
        <div class="field">
            <label>Nombre completo <span class="req">*</span></label>
            <input name="nombre" required value="<?= h($old['nombre'] ?? '') ?>" placeholder="Ej. Juan Pérez">
        </div>
        <div class="field">
            <label>Usuario de acceso <span class="req">*</span></label>
            <input name="usuario" required value="<?= h($old['usuario'] ?? '') ?>" placeholder="ej. jperez" pattern="[A-Za-z0-9_]{3,60}" title="Letras, números o guion bajo, sin espacios">
        </div>
        <div class="field">
            <label>Contraseña <span class="req">*</span></label>
            <input type="password" name="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="field">
            <label>Confirmar contraseña <span class="req">*</span></label>
            <input type="password" name="password2" required minlength="6" autocomplete="new-password">
        </div>
        <div class="field" style="justify-content:end">
            <label>&nbsp;</label>
            <button class="btn btn-ok" type="submit">Guardar técnico</button>
        </div>
    </form>
</section>

<section class="card">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Alta</th>
                <th>Nueva contraseña</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$usuarios): ?>
            <tr><td colspan="5">No hay cuentas registradas.</td></tr>
        <?php endif; ?>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><strong><?= h($u['nombre']) ?></strong></td>
                <td><?= h($u['usuario']) ?></td>
                <td><span class="badge <?= $u['rol'] === 'admin' ? 'st-reparacion' : 'st-listo' ?>"><?= h($u['rol'] === 'admin' ? 'Administrador' : 'Técnico') ?></span></td>
                <td><?= h(formatFecha($u['created_at'] ?? null, true)) ?></td>
                <td>
                    <form method="post" class="btn-row" autocomplete="off">
                        <?= csrfField() ?>
                        <input type="hidden" name="accion" value="clave">
                        <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                        <input type="password" name="nueva_password" required minlength="6" placeholder="Nueva clave" autocomplete="new-password" style="min-width:140px;width:160px">
                        <button class="btn btn-sm btn-ghost" type="submit">Cambiar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
