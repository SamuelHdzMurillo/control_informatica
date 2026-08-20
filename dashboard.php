<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');

$sql = 'SELECT * FROM equipos WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (folio LIKE ? OR marca LIKE ? OR modelo LIKE ? OR numero_serie LIKE ? OR numero_inventario LIKE ? OR entregado_por LIKE ? OR area_dependencia LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_fill(0, 7, $like);
}
if ($estado !== '' && isset(estadosEquipo()[$estado])) {
    $sql .= ' AND estado = ?';
    $params[] = $estado;
}
$sql .= ' ORDER BY fecha_recepcion DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll();

$counts = [];
foreach (array_keys(estadosEquipo()) as $key) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM equipos WHERE estado = ?');
    $c->execute([$key]);
    $counts[$key] = (int) $c->fetchColumn();
}
$total = (int) $pdo->query('SELECT COUNT(*) FROM equipos')->fetchColumn();
$enProceso = $counts['diagnostico'] + $counts['reparacion'] + $counts['refacciones'];

$pageTitle = 'Equipos en soporte';
require __DIR__ . '/includes/header.php';
?>
<div class="topbar">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Informática</p>
        <h1>Panel de control</h1>
        <p class="lead">Equipos en soporte técnico</p>
    </div>
    <a class="btn btn-ok" href="<?= h(url('recibir.php')) ?>">+ Recibir equipo</a>
</div>

<section class="stats">
    <div class="stat n-info"><span>Recibidos</span><b><?= $counts['recibido'] ?></b><small>Ingreso reciente</small></div>
    <div class="stat n-orange"><span>En reparación</span><b><?= $enProceso ?></b><small>Diagnóstico, taller o refacciones</small></div>
    <div class="stat n-green"><span>Listos</span><b><?= $counts['listo'] ?></b><small>Pendientes de entrega</small></div>
    <div class="stat n-muted"><span>Entregados</span><b><?= $counts['entregado'] ?></b><small>Total histórico <?= $total ?></small></div>
</section>

<section class="card">
    <form class="toolbar" method="get">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Folio, serie, inventario, persona o área">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (estadosEquipo() as $k => $label): ?>
                <option value="<?= h($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filtrar</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Equipo</th>
                <th>Quien entrega</th>
                <th>Recepción</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$equipos): ?>
            <tr><td colspan="6">Aún no hay equipos registrados.</td></tr>
        <?php endif; ?>
        <?php foreach ($equipos as $eq): ?>
            <tr>
                <td><strong><?= h($eq['folio']) ?></strong></td>
                <td>
                    <?= h($eq['tipo_equipo']) ?><br>
                    <small><?= h($eq['marca'] . ' ' . $eq['modelo']) ?></small>
                </td>
                <td>
                    <?= h($eq['entregado_por']) ?><br>
                    <small><?= h($eq['area_dependencia'] ?: '') ?></small>
                </td>
                <td><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></td>
                <td><span class="badge st-<?= h($eq['estado']) ?>"><?= h(estadoLabel($eq['estado'])) ?></span></td>
                <td>
                    <div class="btn-row">
                        <a class="btn btn-sm btn-primary" href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>">Ver</a>
                        <a class="btn btn-sm btn-ghost" href="<?= h(url('recibo.php?id=' . $eq['id'])) ?>">Recibo</a>
                        <?php if (puedeEmitirOrden($eq)): ?>
                            <a class="btn btn-sm btn-ok" href="<?= h(url('orden.php?id=' . $eq['id'])) ?>">Orden</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
