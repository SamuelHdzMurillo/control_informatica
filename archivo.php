<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
requireLogin();

$pdo = db();
$tipo = $_GET['tipo'] ?? '';

function sendStoredFile(string $relative): void
{
    $relative = str_replace('\\', '/', $relative);
    if ($relative === '' || str_contains($relative, '..') || !preg_match('#^(fotos|oficios)/[A-Za-z0-9._-]+$#', $relative)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }
    $full = UPLOAD_DIR . '/' . $relative;
    if (!is_file($full)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    header('Content-Type: ' . ($map[$ext] ?? 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . basename($full) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($full);
    exit;
}

if ($tipo === 'foto') {
    $fid = (int) ($_GET['fid'] ?? 0);
    $stmt = $pdo->prepare('SELECT ruta FROM fotos WHERE id = ?');
    $stmt->execute([$fid]);
    $ruta = $stmt->fetchColumn();
    if (!$ruta) {
        http_response_code(404);
        exit('Foto no encontrada');
    }
    sendStoredFile($ruta);
}

if ($tipo === 'foto_interno') {
    $fid = (int) ($_GET['fid'] ?? 0);
    $stmt = $pdo->prepare('SELECT ruta FROM fotos_internos WHERE id = ?');
    $stmt->execute([$fid]);
    $ruta = $stmt->fetchColumn();
    if (!$ruta) {
        http_response_code(404);
        exit('Foto no encontrada');
    }
    sendStoredFile($ruta);
}

if ($tipo === 'oficio') {
    $id = (int) ($_GET['id'] ?? 0);
    $eq = getEquipo($pdo, $id);
    if (!$eq || !$eq['oficio_path']) {
        http_response_code(404);
        exit('Oficio no encontrado');
    }
    sendStoredFile($eq['oficio_path']);
}

http_response_code(400);
exit('Solicitud no válida');
