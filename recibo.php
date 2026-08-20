<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$eq = getEquipo(db(), $id);
if (!$eq) {
    flash('error', 'El equipo no existe.');
    redirect('dashboard.php');
}
$entregado = $eq['entregado_por'] . ($eq['area_dependencia'] ? ', ' . $eq['area_dependencia'] : '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo <?= h($eq['folio']) ?></title>
    <link rel="icon" href="<?= h(logoUrl()) ?>">
    <style>
        @page { size: letter portrait; margin: 12mm 14mm 12mm 14mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "Calibri", "Segoe UI", Arial, sans-serif;
            background: #e8e8e8;
            color: #111;
        }
        .no-print {
            width: min(216mm, 100%);
            margin: 16px auto 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 0 12px;
        }
        .no-print a, .no-print button {
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: 8px; padding: 9px 14px; cursor: pointer;
            text-decoration: none; font-weight: 700; font-size: 14px; font-family: inherit;
        }
        .btn-print { background: #F17829; color: #fff; }
        .btn-back { background: #fff; color: #222; border: 1px solid #ccc; }

        .hoja {
            width: 216mm;
            min-height: 279mm;
            max-width: 100%;
            margin: 0 auto 24px;
            background: #fff;
            padding: 16mm 16mm 14mm;
            box-shadow: 0 8px 28px rgba(0,0,0,.18);
            display: flex;
            flex-direction: column;
        }

        .membrete {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 2.5px solid #111;
        }
        .membrete-izq { min-width: 0; }
        .membrete img {
            display: block;
            height: 58px;
            width: auto;
            max-width: 280px;
        }
        .membrete-meta {
            margin: 8px 0 0;
            font-size: 11px;
            line-height: 1.35;
            color: #333;
        }
        .membrete-meta strong { display: block; font-size: 12.5px; color: #111; }
        .folio-box {
            border: 1.5px solid #111;
            min-width: 150px;
            text-align: center;
            padding: 8px 12px 10px;
        }
        .folio-box span {
            display: block;
            font-size: 9px;
            letter-spacing: .14em;
            font-weight: 700;
            text-transform: uppercase;
        }
        .folio-box b {
            display: block;
            margin-top: 4px;
            font-size: 16px;
            letter-spacing: .04em;
        }
        .franja {
            height: 4px;
            background: linear-gradient(90deg, #76BC43 0 28%, #F17829 28% 100%);
            margin: 0 0 14px;
        }

        .doc-titulo {
            text-align: center;
            margin: 0 0 4px;
            font-size: 15px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .doc-sub {
            text-align: center;
            margin: 0 0 12px;
            font-size: 11.5px;
            color: #333;
        }
        .constancia {
            font-size: 12px;
            line-height: 1.45;
            text-align: justify;
            margin: 0 0 14px;
        }

        .seccion { margin: 0 0 11px; }
        .seccion h2 {
            margin: 0 0 7px;
            font-size: 11px;
            letter-spacing: .1em;
            text-transform: uppercase;
            border-bottom: 1.5px solid #111;
            padding-bottom: 3px;
        }
        .fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 18px;
            margin-bottom: 6px;
        }
        .campo.ancho { grid-column: 1 / -1; }
        .campo label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 2px;
        }
        .campo .valor {
            display: block;
            min-height: 18px;
            border-bottom: 1px solid #111;
            font-size: 12.5px;
            padding: 1px 0 3px;
            line-height: 1.3;
        }
        .campo .valor.caja {
            border: 1px solid #111;
            border-bottom: 1px solid #111;
            min-height: 38px;
            padding: 5px 7px;
        }

        .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 36px;
            margin-top: auto;
            padding-top: 18px;
        }
        .firma { text-align: center; }
        .firma-espacio { height: 42px; }
        .firma-linea { border-top: 1.5px solid #111; padding-top: 6px; }
        .firma strong { display: block; font-size: 11.5px; }
        .firma span { display: block; font-size: 10.5px; color: #333; margin-top: 2px; }

        .pie {
            margin-top: 16px;
            border-top: 1.5px solid #111;
            padding-top: 6px;
            font-size: 9px;
            color: #333;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .hoja {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .membrete img, .franja {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @media screen and (max-width: 820px) {
            .hoja { padding: 18px; }
            .membrete, .fila, .firmas { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button class="btn-print" type="button" onclick="window.print()">Imprimir recibo</button>
    <a class="btn-back" href="<?= h(url('equipo.php?id=' . $eq['id'])) ?>">Volver al expediente</a>
    <a class="btn-back" href="<?= h(url('recibir.php')) ?>">Recibir otro</a>
</div>

<article class="hoja">
    <header class="membrete">
        <div class="membrete-izq">
            <img src="<?= h(logoUrl()) ?>" alt="CECYTE Baja California Sur">
            <p class="membrete-meta">
                <strong>Colegio de Estudios Científicos y Tecnológicos del Estado de Baja California Sur</strong>
                Dirección de Informática · Soporte técnico institucional
            </p>
        </div>
        <div class="folio-box">
            <span>Folio de servicio</span>
            <b><?= h($eq['folio']) ?></b>
        </div>
    </header>
    <div class="franja"></div>

    <h1 class="doc-titulo">Recibo de ingreso de equipo</h1>
    <p class="doc-sub">Comprobante de recepción para diagnóstico y/o reparación</p>

    <p class="constancia">
        El área de <strong>Informática</strong> del <strong>CECYTE Baja California Sur</strong> hace constar
        que el día <strong><?= h(formatFecha($eq['fecha_recepcion'], true)) ?></strong> recibió de
        <strong><?= h($entregado) ?></strong> el equipo que se describe, para su revisión, diagnóstico
        y, en su caso, reparación. Este documento es el comprobante de ingreso; deberá conservarse
        hasta la devolución del bien. Con el folio <strong><?= h($eq['folio']) ?></strong> se puede
        consultar el avance del servicio.
    </p>

    <section class="seccion">
        <h2>1. Identificación del equipo</h2>
        <div class="fila">
            <div class="campo"><label>Tipo de equipo</label><span class="valor"><?= h($eq['tipo_equipo']) ?></span></div>
            <div class="campo"><label>Marca y modelo</label><span class="valor"><?= h($eq['marca'] . ' / ' . $eq['modelo']) ?></span></div>
            <div class="campo"><label>Número de serie</label><span class="valor"><?= h($eq['numero_serie'] ?: 'No proporcionado') ?></span></div>
            <div class="campo"><label>Número de inventario</label><span class="valor"><?= h($eq['numero_inventario'] ?: 'No proporcionado') ?></span></div>
        </div>
    </section>

    <section class="seccion">
        <h2>2. Datos de quien entrega y de quien recibe</h2>
        <div class="fila">
            <div class="campo"><label>Entregado por</label><span class="valor"><?= h($eq['entregado_por']) ?></span></div>
            <div class="campo"><label>Área o dependencia</label><span class="valor"><?= h($eq['area_dependencia'] ?: 'No especificada') ?></span></div>
            <div class="campo"><label>Teléfono o extensión</label><span class="valor"><?= h($eq['telefono'] ?: 'No proporcionado') ?></span></div>
            <div class="campo"><label>Recibido por</label><span class="valor"><?= h($eq['recibido_por'] ?: ORG_NAME) ?></span></div>
        </div>
    </section>

    <section class="seccion">
        <h2>3. Falla reportada y estado físico</h2>
        <div class="fila">
            <div class="campo"><label>Tipo de problema</label><span class="valor"><?= h($eq['tipo_problema']) ?></span></div>
            <div class="campo"><label>Estado físico al ingreso</label><span class="valor"><?= h($eq['estado_fisico']) ?></span></div>
            <div class="campo ancho"><label>Problema reportado</label><span class="valor"><?= h($eq['problema_reportado']) ?></span></div>
            <div class="campo ancho"><label>Descripción de la falla</label><span class="valor caja"><?= h($eq['descripcion_falla'] ?: 'Sin detalle adicional.') ?></span></div>
        </div>
    </section>

    <section class="seccion">
        <h2>4. Accesorios, oficio y observaciones</h2>
        <div class="fila">
            <div class="campo ancho"><label>Accesorios recibidos</label><span class="valor"><?= h($eq['accesorios'] ?: 'Ninguno declarado') ?></span></div>
            <div class="campo"><label>Oficio anexo</label><span class="valor"><?= $eq['oficio_path'] ? 'Sí, quedó integrado al expediente' : 'No se anexó oficio' ?></span></div>
            <div class="campo"><label>Folio</label><span class="valor"><?= h($eq['folio']) ?></span></div>
            <div class="campo ancho"><label>Observaciones</label><span class="valor"><?= h($eq['observaciones'] ?: 'Ninguna') ?></span></div>
        </div>
    </section>

    <div class="firmas">
        <div class="firma">
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <strong><?= h($eq['entregado_por']) ?></strong>
                <span>Nombre y firma de quien entrega</span>
            </div>
        </div>
        <div class="firma">
            <div class="firma-espacio"></div>
            <div class="firma-linea">
                <strong><?= h($eq['recibido_por'] ?: ORG_SHORT) ?></strong>
                <span>Nombre y firma de quien recibe · Informática</span>
            </div>
        </div>
    </div>

    <p class="pie">
        CECYTE Baja California Sur · Informática · Documento de uso institucional ·
        Con el folio se da seguimiento al avance del equipo · Generado el <?= h(date('d/m/Y H:i')) ?>.
    </p>
</article>
</body>
</html>
