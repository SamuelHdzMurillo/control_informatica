<?php

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '/');
}

function asset(string $path): string
{
    $rel = 'assets/' . ltrim($path, '/');
    $url = url($rel);
    $file = BASE_PATH . '/' . $rel;
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

function logoUrl(): string
{
    return asset('img/logo-cecyte.png');
}

function userInitial(?array $user = null): string
{
    $user = $user ?? currentUser();
    $name = trim((string) ($user['nombre'] ?? 'A'));
    return strtoupper(substr($name, 0, 1));
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function csrfValid(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function tiposEquipo(): array
{
    return [
        'Computadora de escritorio',
        'Laptop',
        'All-in-one',
        'Monitor',
        'Impresora',
        'Escáner',
        'Proyector',
        'Tablet',
        'Servidor',
        'Switch / Router',
        'No-break / UPS',
        'Otro',
    ];
}

function tiposProblema(): array
{
    return [
        'No enciende',
        'Hardware',
        'Software',
        'Sistema operativo',
        'Red / Internet',
        'Impresión',
        'Daño físico',
        'Lentitud',
        'Virus / malware',
        'Mantenimiento preventivo',
        'Otro',
    ];
}

function estadosFisicos(): array
{
    return [
        'Bueno',
        'Regular',
        'Con desgaste',
        'Con daños visibles',
        'Incompleto',
    ];
}

function estadosEquipo(): array
{
    return [
        'recibido' => 'Recibido',
        'diagnostico' => 'En diagnóstico',
        'reparacion' => 'En reparación',
        'refacciones' => 'Espera de refacciones',
        'listo' => 'Listo para entrega',
        'entregado' => 'Entregado',
        'no_reparable' => 'No reparable',
    ];
}

function estadoLabel(string $estado): string
{
    return estadosEquipo()[$estado] ?? $estado;
}

function estadoPaso(string $estado): int
{
    $map = [
        'recibido' => 1,
        'diagnostico' => 2,
        'reparacion' => 3,
        'refacciones' => 3,
        'listo' => 4,
        'entregado' => 5,
        'no_reparable' => 3,
    ];
    return $map[$estado] ?? 1;
}

function formatFecha(?string $fecha, bool $conHora = false): string
{
    if (!$fecha) {
        return '—';
    }
    $ts = strtotime($fecha);
    if ($ts === false) {
        return h($fecha);
    }
    return $conHora ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
}

function formatFechaLarga(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $ts = strtotime($fecha);
    if ($ts === false) {
        return $fecha;
    }
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    return date('j', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
}

function tipoMantenimiento(string $tipoProblema): string
{
    if (stripos($tipoProblema, 'preventivo') !== false) {
        return 'Preventivo';
    }
    return 'Reparación';
}

function puedeEmitirOrden(array $eq): bool
{
    return in_array($eq['estado'] ?? '', ['listo', 'entregado', 'no_reparable'], true);
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ' . $pdo->quote($column));
    return (bool) $stmt->fetch();
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function ensureEquipoSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!columnExists($pdo, 'equipos', 'trabajo_realizado')) {
        $pdo->exec('ALTER TABLE equipos ADD COLUMN trabajo_realizado TEXT DEFAULT NULL AFTER observaciones');
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS personas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(160) NOT NULL,
            area_dependencia VARCHAR(160) DEFAULT NULL,
            telefono VARCHAR(40) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bienes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            persona_id INT DEFAULT NULL,
            tipo_equipo VARCHAR(80) NOT NULL,
            marca VARCHAR(120) NOT NULL,
            modelo VARCHAR(120) NOT NULL,
            numero_serie VARCHAR(120) DEFAULT NULL,
            numero_inventario VARCHAR(120) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_bienes_persona FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (!columnExists($pdo, 'equipos', 'persona_id')) {
        $pdo->exec('ALTER TABLE equipos ADD COLUMN persona_id INT DEFAULT NULL AFTER folio');
    }
    if (!columnExists($pdo, 'equipos', 'bien_id')) {
        $pdo->exec('ALTER TABLE equipos ADD COLUMN bien_id INT DEFAULT NULL AFTER persona_id');
    }

    backfillPerfiles($pdo);
}

function backfillPerfiles(PDO $pdo): void
{
    if (!columnExists($pdo, 'equipos', 'persona_id')) {
        return;
    }
    $rows = $pdo->query('SELECT * FROM equipos WHERE persona_id IS NULL OR bien_id IS NULL')->fetchAll();
    foreach ($rows as $eq) {
        $personaId = savePersona(
            $pdo,
            (string) $eq['entregado_por'],
            $eq['area_dependencia'] ?? null,
            $eq['telefono'] ?? null,
            (int) ($eq['persona_id'] ?? 0)
        );
        $bienId = saveBien($pdo, [
            'tipo_equipo' => $eq['tipo_equipo'],
            'marca' => $eq['marca'],
            'modelo' => $eq['modelo'],
            'numero_serie' => $eq['numero_serie'] ?? null,
            'numero_inventario' => $eq['numero_inventario'] ?? null,
        ], $personaId, (int) ($eq['bien_id'] ?? 0));
        $upd = $pdo->prepare('UPDATE equipos SET persona_id = ?, bien_id = ? WHERE id = ?');
        $upd->execute([$personaId, $bienId, (int) $eq['id']]);
    }
}

function getPersona(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM personas WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findPersona(PDO $pdo, string $nombre): ?array
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM personas WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
    $stmt->execute([$nombre]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function savePersona(PDO $pdo, string $nombre, ?string $area, ?string $telefono, int $id = 0): int
{
    $nombre = trim($nombre);
    $area = trim((string) $area) ?: null;
    $telefono = trim((string) $telefono) ?: null;
    if ($nombre === '') {
        throw new RuntimeException('El nombre de la persona es obligatorio.');
    }
    if ($id > 0 && getPersona($pdo, $id)) {
        $stmt = $pdo->prepare('UPDATE personas SET nombre = ?, area_dependencia = ?, telefono = ? WHERE id = ?');
        $stmt->execute([$nombre, $area, $telefono, $id]);
        return $id;
    }
    $exist = findPersona($pdo, $nombre);
    if ($exist) {
        $stmt = $pdo->prepare(
            'UPDATE personas SET
                area_dependencia = COALESCE(?, area_dependencia),
                telefono = COALESCE(?, telefono)
             WHERE id = ?'
        );
        $stmt->execute([$area, $telefono, (int) $exist['id']]);
        return (int) $exist['id'];
    }
    $stmt = $pdo->prepare('INSERT INTO personas (nombre, area_dependencia, telefono) VALUES (?, ?, ?)');
    $stmt->execute([$nombre, $area, $telefono]);
    return (int) $pdo->lastInsertId();
}

function getBien(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bienes WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findBien(PDO $pdo, ?string $serie, ?string $inventario): ?array
{
    $serie = trim((string) $serie);
    $inventario = trim((string) $inventario);
    if ($serie !== '') {
        $stmt = $pdo->prepare('SELECT * FROM bienes WHERE numero_serie = ? LIMIT 1');
        $stmt->execute([$serie]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    if ($inventario !== '') {
        $stmt = $pdo->prepare('SELECT * FROM bienes WHERE numero_inventario = ? LIMIT 1');
        $stmt->execute([$inventario]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    return null;
}

function saveBien(PDO $pdo, array $data, int $personaId = 0, int $id = 0): int
{
    $tipo = trim((string) ($data['tipo_equipo'] ?? ''));
    $marca = trim((string) ($data['marca'] ?? ''));
    $modelo = trim((string) ($data['modelo'] ?? ''));
    $serie = trim((string) ($data['numero_serie'] ?? '')) ?: null;
    $inventario = trim((string) ($data['numero_inventario'] ?? '')) ?: null;
    $personaId = $personaId > 0 ? $personaId : null;
    if ($tipo === '' || $marca === '' || $modelo === '') {
        throw new RuntimeException('Tipo, marca y modelo del equipo son obligatorios.');
    }
    if ($id > 0 && getBien($pdo, $id)) {
        $stmt = $pdo->prepare(
            'UPDATE bienes SET persona_id = COALESCE(?, persona_id), tipo_equipo = ?, marca = ?, modelo = ?,
             numero_serie = ?, numero_inventario = ? WHERE id = ?'
        );
        $stmt->execute([$personaId, $tipo, $marca, $modelo, $serie, $inventario, $id]);
        return $id;
    }
    $exist = findBien($pdo, $serie, $inventario);
    if ($exist) {
        $stmt = $pdo->prepare(
            'UPDATE bienes SET persona_id = COALESCE(?, persona_id), tipo_equipo = ?, marca = ?, modelo = ?,
             numero_serie = COALESCE(?, numero_serie), numero_inventario = COALESCE(?, numero_inventario)
             WHERE id = ?'
        );
        $stmt->execute([$personaId, $tipo, $marca, $modelo, $serie, $inventario, (int) $exist['id']]);
        return (int) $exist['id'];
    }
    $stmt = $pdo->prepare(
        'INSERT INTO bienes (persona_id, tipo_equipo, marca, modelo, numero_serie, numero_inventario)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$personaId, $tipo, $marca, $modelo, $serie, $inventario]);
    return (int) $pdo->lastInsertId();
}

function listPersonas(PDO $pdo, string $q = ''): array
{
    $sql = 'SELECT p.*,
                (SELECT COUNT(*) FROM bienes b WHERE b.persona_id = p.id) AS equipos,
                (SELECT COUNT(*) FROM equipos e WHERE e.persona_id = p.id) AS servicios
            FROM personas p';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE p.nombre LIKE ? OR p.area_dependencia LIKE ? OR p.telefono LIKE ?';
        $like = '%' . $q . '%';
        $params = [$like, $like, $like];
    }
    $sql .= ' ORDER BY p.nombre';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function listBienes(PDO $pdo, string $q = ''): array
{
    $sql = 'SELECT b.*, p.nombre AS persona_nombre,
                (SELECT COUNT(*) FROM equipos e WHERE e.bien_id = b.id) AS servicios,
                (SELECT MAX(e.fecha_recepcion) FROM equipos e WHERE e.bien_id = b.id) AS ultimo_servicio
            FROM bienes b
            LEFT JOIN personas p ON p.id = b.persona_id';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE b.marca LIKE ? OR b.modelo LIKE ? OR b.numero_serie LIKE ?
                  OR b.numero_inventario LIKE ? OR b.tipo_equipo LIKE ? OR p.nombre LIKE ?';
        $like = '%' . $q . '%';
        $params = [$like, $like, $like, $like, $like, $like];
    }
    $sql .= ' ORDER BY b.marca, b.modelo, b.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function serviciosDePersona(PDO $pdo, int $personaId): array
{
    $stmt = $pdo->prepare('SELECT * FROM equipos WHERE persona_id = ? ORDER BY fecha_recepcion DESC, id DESC');
    $stmt->execute([$personaId]);
    return $stmt->fetchAll();
}

function bienesDePersona(PDO $pdo, int $personaId): array
{
    $stmt = $pdo->prepare('SELECT * FROM bienes WHERE persona_id = ? ORDER BY marca, modelo');
    $stmt->execute([$personaId]);
    return $stmt->fetchAll();
}

function serviciosDeBien(PDO $pdo, int $bienId): array
{
    $stmt = $pdo->prepare('SELECT * FROM equipos WHERE bien_id = ? ORDER BY fecha_recepcion DESC, id DESC');
    $stmt->execute([$bienId]);
    return $stmt->fetchAll();
}

function getTecnicoOrden(PDO $pdo, int $equipoId): array
{
    $stmt = $pdo->prepare(
        "SELECT u.nombre, u.usuario
         FROM bitacora b
         LEFT JOIN usuarios u ON u.id = b.usuario_id
         WHERE b.equipo_id = ? AND b.estado IN ('listo', 'no_reparable')
         ORDER BY b.id DESC
         LIMIT 1"
    );
    $stmt->execute([$equipoId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['nombre'])) {
        return $row;
    }
    $user = currentUser() ?? [];
    return [
        'nombre' => $user['nombre'] ?? ORG_AREA,
        'usuario' => $user['usuario'] ?? '',
    ];
}

function getFechaOrden(PDO $pdo, array $eq): string
{
    $stmt = $pdo->prepare(
        "SELECT created_at FROM bitacora
         WHERE equipo_id = ? AND estado IN ('listo', 'no_reparable')
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([(int) $eq['id']]);
    $fecha = $stmt->fetchColumn();
    return $fecha ?: ($eq['fecha_entrega'] ?? $eq['fecha_recepcion'] ?? date('Y-m-d H:i:s'));
}

function getTrabajoRealizado(PDO $pdo, array $eq): string
{
    $stmt = $pdo->prepare(
        "SELECT comentario FROM bitacora
         WHERE equipo_id = ? AND estado = 'reparacion'
         ORDER BY id ASC"
    );
    $stmt->execute([(int) $eq['id']]);
    $lineas = [];
    foreach ($stmt->fetchAll() as $row) {
        $comentario = trim((string) ($row['comentario'] ?? ''));
        if ($comentario === '' || $comentario === 'El equipo pasó a: En reparación') {
            continue;
        }
        $lineas[] = $comentario;
    }
    if ($lineas) {
        return implode("\n\n", $lineas);
    }
    return trim((string) ($eq['trabajo_realizado'] ?? ''));
}

function generateFolio(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'ST-' . $year . '-';
    $stmt = $pdo->prepare('SELECT folio FROM equipos WHERE folio LIKE ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $n = 1;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $n = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('login.php');
    }
}

function ensureUploadDirs(): void
{
    foreach (['fotos', 'oficios'] as $dir) {
        $path = UPLOAD_DIR . '/' . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}

function extensionOf(string $name): string
{
    return strtolower(pathinfo($name, PATHINFO_EXTENSION));
}

function saveUploadedFile(array $file, string $subdir, array $allowed, int $maxBytes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo cargar el archivo.');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('El archivo excede el tamaño permitido.');
    }
    $ext = extensionOf($file['name'] ?? '');
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Tipo de archivo no permitido.');
    }
    ensureUploadDirs();
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $subdir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }
    return $subdir . '/' . $filename;
}

function addBitacora(PDO $pdo, int $equipoId, string $estado, string $comentario, bool $publico = true, ?int $usuarioId = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO bitacora (equipo_id, estado, comentario, visible_publico, usuario_id)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$equipoId, $estado, $comentario, $publico ? 1 : 0, $usuarioId]);
}

function getEquipo(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM equipos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getEquipoByFolio(PDO $pdo, string $folio): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM equipos WHERE folio = ?');
    $stmt->execute([strtoupper(trim($folio))]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getFotos(PDO $pdo, int $equipoId): array
{
    $stmt = $pdo->prepare('SELECT * FROM fotos WHERE equipo_id = ? ORDER BY id');
    $stmt->execute([$equipoId]);
    return $stmt->fetchAll();
}

function getBitacora(PDO $pdo, int $equipoId, bool $soloPublico = false): array
{
    $sql = 'SELECT b.*, u.nombre AS usuario_nombre
            FROM bitacora b
            LEFT JOIN usuarios u ON u.id = b.usuario_id
            WHERE b.equipo_id = ?';
    if ($soloPublico) {
        $sql .= ' AND b.visible_publico = 1';
    }
    $sql .= ' ORDER BY b.created_at ASC, b.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$equipoId]);
    return $stmt->fetchAll();
}

function consultaUrl(string $folio): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url('consulta.php?folio=' . urlencode($folio));
}
?>
