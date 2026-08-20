<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();

$folio = strtoupper(trim($_GET['folio'] ?? $_POST['folio'] ?? ''));
$eq = $folio !== '' ? getEquipoByFolio(db(), $folio) : null;
$error = '';
if ($folio !== '' && !$eq) {
    $error = 'No se encontró un equipo con ese folio. Revise el recibo.';
}
$bitacora = $eq ? getBitacora(db(), (int) $eq['id'], true) : [];
$paso = $eq ? estadoPaso($eq['estado']) : 0;
$obsPorPaso = $eq ? observacionesPorPaso($bitacora) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de avance · <?= h(ORG_SHORT) ?></title>
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="icon" href="<?= h(logoUrl()) ?>">
</head>
<body class="auth-body">
<main class="auth-card" style="width:min(760px,100%)">
    <img class="auth-logo" src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
    <p class="eyebrow"><?= h(ORG_NAME) ?></p>
    <h1>Consulta de avance</h1>
    <p class="lead">Capture el folio impreso en su recibo para ver cómo va el equipo.</p>
    <form method="get" class="toolbar">
        <input name="folio" value="<?= h($folio) ?>" placeholder="Ej. ST-2026-0001" required>
        <button class="btn btn-primary" type="submit">Consultar</button>
    </form>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

    <?php if ($eq): ?>
        <p class="folio-stamp" style="display:inline-block"><?= h($eq['folio']) ?></p>
        <p><span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span></p>
        <div class="steps">
            <?php
            $pasos = [1 => 'Recibido', 2 => 'Diagnóstico', 3 => 'Reparación', 4 => 'Listo', 5 => 'Entregado'];
            foreach ($pasos as $n => $label):
                $class = $n < $paso ? 'done' : ($n === $paso ? 'on' : '');
                $nota = trim((string) ($obsPorPaso[$n] ?? ''));
                ?>
                <div class="step <?= $class ?>">
                    <?= h($label) ?>
                    <?php if ($nota !== ''): ?>
                        <small class="step-note" title="<?= h($nota) ?>"><?= h($nota) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <dl class="kv">
            <dt>Equipo</dt><dd><?= h($eq['tipo_equipo'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']) ?></dd>
            <dt>Inventario</dt><dd><?= h($eq['numero_inventario'] ?: '—') ?></dd>
            <dt>Fecha de recepción</dt><dd><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></dd>
            <dt>Problema reportado</dt><dd><?= h($eq['problema_reportado']) ?></dd>
            <?php if ($eq['estado'] === 'entregado'): ?>
                <dt>Entregado</dt><dd><?= h(formatFecha($eq['fecha_entrega'], true)) ?></dd>
            <?php endif; ?>
        </dl>
        <h2>Historial visible</h2>
        <ul class="timeline">
            <?php foreach ($bitacora as $b): ?>
                <li>
                    <time><?= h(formatFecha($b['created_at'], true)) ?> · <?= h(estadoLabel($b['estado'])) ?></time>
                    <div><?= nl2br(h($b['comentario'])) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
</body>
</html>
