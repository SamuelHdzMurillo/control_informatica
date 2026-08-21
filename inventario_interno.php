<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pageTitle = 'Inventario interno';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><?= h(ORG_SHORT) ?> · Informática</p>
        <h1>Inventario interno</h1>
        <p class="page-sub">Módulo aparte del soporte técnico. Aquí se construirá el control interno de bienes.</p>
    </div>
</div>

<section class="card">
    <p class="empty-state">Este apartado está listo para armarse. Dime qué debe incluir (alta de bienes, existencias, resguardos, etc.) y lo desarrollamos aquí sin mezclarlo con el inventario de soporte.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
