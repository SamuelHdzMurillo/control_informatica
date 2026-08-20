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
$faltanOrden = faltantesOrden($pdo, $eq);
if ($faltanOrden) {
    flash('error', 'No se puede emitir la orden. Falta: ' . implode(' ', $faltanOrden));
    redirect('equipo.php?id=' . $id);
}

$fotos = getFotos($pdo, $id);
$tecnico = getTecnicoOrden($pdo, $id);
$fechaOrden = getFechaOrden($pdo, $eq);
$trabajoRealizado = getTrabajoRealizado($pdo, $eq);
$diagnostico = getDiagnostico($pdo, $eq);
$recibeNombre = $eq['entregado_a'] ?: $eq['entregado_por'];
$recibeDato = $eq['telefono'] ?: ($eq['area_dependencia'] ?: '');
$equipoLinea = trim($eq['marca'] . ' ' . $eq['modelo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de servicio <?= h($eq['folio']) ?></title>
    <link rel="icon" href="<?= h(logoUrl()) ?>">
    <style>
        @page { size: letter portrait; margin: 10mm 12mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "Calibri", "Segoe UI", Arial, sans-serif;
            background: #e8e8e8;
            color: #111;
        }
        .no-print {
            width: min(216mm, 100%);
            margin: 12px auto 8px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 0 12px;
        }
        .no-print a, .no-print button {
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid transparent; border-radius: 8px; padding: 10px 16px;
            min-height: 40px; cursor: pointer;
            text-decoration: none; font-weight: 700; font-size: 14px; font-family: inherit;
        }
        .btn-print { background: #F17829; color: #fff; }
        .btn-print:hover { background: #d96a22; }
        .btn-back { background: #fff; color: #222; border-color: #ccc; }
        .btn-back:hover { border-color: #F17829; color: #F17829; }

        .hoja {
            width: 216mm;
            max-width: 100%;
            margin: 0 auto 20px;
            background: #fff;
            padding: 10mm 12mm 8mm;
            box-shadow: 0 8px 28px rgba(0,0,0,.18);
        }

        .membrete {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            padding-bottom: 6px;
            border-bottom: 2px solid #111;
        }
        .membrete img { display: block; height: 46px; width: auto; max-width: 240px; }
        .membrete-meta {
            margin: 4px 0 0;
            font-size: 10.5px;
            line-height: 1.3;
            color: #333;
        }
        .membrete-meta strong { display: block; font-size: 12px; color: #111; }
        .folio-box {
            border: 1.5px solid #111;
            min-width: 140px;
            text-align: center;
            padding: 6px 10px 8px;
        }
        .folio-box span {
            display: block;
            font-size: 8.5px;
            letter-spacing: .12em;
            font-weight: 700;
            text-transform: uppercase;
        }
        .folio-box b { display: block; margin-top: 3px; font-size: 15px; }
        .franja {
            height: 3px;
            background: linear-gradient(90deg, #76BC43 0 28%, #F17829 28% 100%);
            margin: 0 0 8px;
        }

        .cab-doc {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: end;
            gap: 10px;
            margin: 0 0 8px;
        }
        .cab-doc h1 {
            margin: 0;
            font-size: 14px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .cab-doc time { font-size: 12px; }

        .seccion { margin: 0 0 8px; }
        .seccion h2 {
            margin: 0 0 5px;
            font-size: 10px;
            letter-spacing: .1em;
            text-transform: uppercase;
            border-bottom: 1.5px solid #111;
            padding-bottom: 2px;
        }
        .fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 14px;
        }
        .campo.ancho { grid-column: 1 / -1; }
        .campo label {
            display: block;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #333;
        }
        .campo .valor {
            display: block;
            border-bottom: 1px solid #111;
            font-size: 12px;
            padding: 1px 0 2px;
            line-height: 1.25;
        }
        .campo .valor.caja {
            border: 1px solid #111;
            padding: 4px 6px;
            white-space: pre-wrap;
        }

        .tabla-obs {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .tabla-obs th, .tabla-obs td {
            border: 1px solid #111;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }
        .tabla-obs th {
            font-size: 8.5px;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: #f3f3f3;
        }
        .tabla-obs small { display: block; color: #333; margin-top: 2px; font-size: 11px; }

        .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 18px;
            margin-top: 12px;
        }
        .firma { text-align: center; }
        .firma-espacio { height: 28px; }
        .firma-linea { border-top: 1.5px solid #111; padding-top: 4px; }
        .firma strong { display: block; font-size: 11.5px; }
        .firma span { display: block; font-size: 10px; color: #333; margin-top: 1px; }

        .pie {
            margin-top: 8px;
            border-top: 1px solid #111;
            padding-top: 4px;
            font-size: 8.5px;
            color: #333;
        }

        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .foto-item {
            border: 1px solid #111;
            padding: 5px;
            text-align: center;
        }
        .foto-item img {
            display: block;
            width: 100%;
            height: 52mm;
            object-fit: cover;
            background: #f3f3f3;
        }
        .foto-item span {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            letter-spacing: .06em;
            font-weight: 700;
            text-transform: uppercase;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .hoja { width: auto; margin: 0; padding: 0; box-shadow: none; }
            .hoja + .hoja { page-break-before: always; }
            .membrete img, .franja, .tabla-obs th, .foto-item img {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @media screen and (max-width: 820px) {
            .hoja { padding: 16px; }
            .membrete, .fila, .firmas, .cab-doc, .fotos-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button class="btn-print" type="button" onclick="window.print()">Imprimir orden de servicio</button>
    <a class="btn-back" href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>">Volver al expediente</a>
    <a class="btn-back" href="<?= h(url('recibo.php?id=' . $eq['id'])) ?>">Recibo de ingreso</a>
</div>

<article class="hoja">
    <header class="membrete">
        <div>
            <img src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
            <p class="membrete-meta">
                <strong>Colegio de Estudios Científicos y Tecnológicos del Estado de Baja California Sur</strong>
                Departamento de Informática
            </p>
        </div>
        <div class="folio-box">
            <span>Orden de servicio no.</span>
            <b><?= h($eq['folio']) ?></b>
        </div>
    </header>
    <div class="franja"></div>

    <div class="cab-doc">
        <h1>Orden de servicio</h1>
        <time>Fecha: <?= h(formatFechaLarga($fechaOrden)) ?></time>
    </div>

    <section class="seccion">
        <h2>Datos del equipo</h2>
        <div class="fila">
            <div class="campo"><label>Plantel / usuario</label><span class="valor"><?= h($eq['entregado_por']) ?></span></div>
            <div class="campo"><label>Tipo de equipo</label><span class="valor"><?= h($eq['tipo_equipo']) ?></span></div>
            <div class="campo"><label>Marca</label><span class="valor"><?= h($eq['marca']) ?></span></div>
            <div class="campo"><label>Modelo</label><span class="valor"><?= h($eq['modelo']) ?></span></div>
            <div class="campo"><label>No. serie</label><span class="valor"><?= h($eq['numero_serie'] ?: 'No proporcionado') ?></span></div>
            <div class="campo"><label>No. inventario</label><span class="valor"><?= h($eq['numero_inventario'] ?: 'No proporcionado') ?></span></div>
            <div class="campo"><label>Entidad</label><span class="valor"><?= h($eq['area_dependencia'] ?: 'No especificada') ?></span></div>
            <div class="campo"><label>Especificaciones</label><span class="valor"><?= h($eq['estado_fisico']) ?></span></div>
            <div class="campo ancho"><label>Accesorios incluidos</label><span class="valor"><?= h($eq['accesorios'] ?: 'Ninguno declarado') ?></span></div>
        </div>
    </section>

    <section class="seccion">
        <h2>Orden de servicio</h2>
        <div class="fila">
            <div class="campo"><label>Tipo de mantenimiento</label><span class="valor"><?= h(tipoMantenimiento($eq['tipo_problema'])) ?></span></div>
            <div class="campo"><label>Problema reportado</label><span class="valor"><?= h($eq['problema_reportado']) ?></span></div>
            <div class="campo ancho"><label>Descripción del problema</label><span class="valor caja"><?= h($eq['descripcion_falla'] ?: $eq['problema_reportado']) ?></span></div>
        </div>
    </section>

    <?php if (in_array($eq['estado'], ['no_reparable'], true)): ?>
    <section class="seccion">
        <h2>Diagnóstico</h2>
        <div class="campo">
            <span class="valor caja"><?= h($diagnostico !== '' ? $diagnostico : 'Sin diagnóstico capturado.') ?></span>
        </div>
    </section>
    <?php else: ?>
    <section class="seccion">
        <h2>Trabajo realizado · descripción</h2>
        <div class="campo">
            <span class="valor caja"><?= h($trabajoRealizado !== '' ? $trabajoRealizado : 'Sin registro de trabajo realizado.') ?></span>
        </div>
    </section>
    <?php endif; ?>

    <section class="seccion">
        <h2>Observaciones</h2>
        <table class="tabla-obs">
            <thead>
                <tr>
                    <th style="width:38%">Equipo</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= h($equipoLinea) ?>
                        <?php if ($eq['numero_serie']): ?>
                            <small>Serie: <?= h($eq['numero_serie']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= nl2br(h($eq['observaciones'] ?: $eq['estado_fisico'])) ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <div class="firmas">
        <div class="firma">
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <strong><?= h($tecnico['nombre']) ?></strong>
                <?php if (!empty($tecnico['usuario'])): ?>
                    <span><?= h($tecnico['usuario']) ?></span>
                <?php endif; ?>
                <span>Técnico</span>
            </div>
        </div>
        <div class="firma">
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <strong><?= h(ORG_JEFE_INFORMATICA) ?></strong>
                <span>Jefe de Departamento de Informática</span>
            </div>
        </div>
        <div class="firma">
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <strong><?= h($recibeNombre) ?></strong>
                <?php if ($recibeDato): ?>
                    <span><?= h($recibeDato) ?></span>
                <?php endif; ?>
                <span>Recibe</span>
            </div>
        </div>
    </div>

    <p class="pie">CECYTE BCS · Informática · <?= h($eq['folio']) ?> · <?= h(date('d/m/Y H:i')) ?></p>
</article>

<?php if ($fotos): ?>
<article class="hoja">
    <header class="membrete">
        <div>
            <img src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
            <p class="membrete-meta">
                <strong>Colegio de Estudios Científicos y Tecnológicos del Estado de Baja California Sur</strong>
                Departamento de Informática
            </p>
        </div>
        <div class="folio-box">
            <span>Orden de servicio no.</span>
            <b><?= h($eq['folio']) ?></b>
        </div>
    </header>
    <div class="franja"></div>

    <div class="cab-doc">
        <h1>Imágenes del bien</h1>
        <time><?= count($fotos) ?> imagen(es)</time>
    </div>

    <div class="fotos-grid">
        <?php foreach ($fotos as $i => $f): ?>
            <div class="foto-item">
                <img src="<?= h(url('archivo.php?tipo=foto&fid=' . $f['id'])) ?>" alt="Imagen <?= (int) $i + 1 ?>">
                <span>Imagen <?= (int) $i + 1 ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="pie">CECYTE BCS · Informática · Anexo fotográfico · <?= h($eq['folio']) ?></p>
</article>
<?php endif; ?>
</body>
</html>
