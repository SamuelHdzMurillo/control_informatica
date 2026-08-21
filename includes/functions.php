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
    return nombreIniciales((string) ($user['nombre'] ?? 'A'), 1);
}

function nombreIniciales(?string $nombre, int $max = 2): string
{
    $nombre = trim((string) $nombre);
    if ($nombre === '') {
        return '?';
    }
    $parts = preg_split('/\s+/u', $nombre) ?: [$nombre];
    $take = static function (string $s): string {
        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
        }
        return strtoupper(substr($s, 0, 1));
    };
    $ini = $take((string) $parts[0]);
    if ($max > 1 && count($parts) > 1) {
        $ini .= $take((string) $parts[count($parts) - 1]);
    }
    return $ini;
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

function tiposEquipoInterno(): array
{
    return array_values(array_unique(array_merge(tiposEquipo(), [
        'Cable HDMI',
        'Cable VGA',
        'Cable de red',
        'Mouse',
        'Teclado',
        'Webcam',
        'Micrófono',
        'Bocinas',
        'Adaptador',
        'Cargador',
        'Extensión / multicontacto',
        'Memoria USB',
        'Disco duro / SSD',
    ])));
}

function estadosBienInterno(): array
{
    return [
        'disponible' => 'Disponible',
        'prestado' => 'Prestado',
        'baja' => 'Baja',
    ];
}

function estadoBienInternoLabel(string $estado): string
{
    return estadosBienInterno()[$estado] ?? $estado;
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

function estadosOrdenServicio(): array
{
    return ['listo', 'entregado', 'no_reparable'];
}

function comentarioEsAutomatico(string $comentario): bool
{
    $comentario = trim($comentario);
    return $comentario === '' || str_starts_with($comentario, 'El equipo pasó a:');
}

function comentarioEsObservacion(string $comentario): bool
{
    $comentario = trim($comentario);
    if ($comentario === '' || comentarioEsAutomatico($comentario)) {
        return false;
    }
    return !str_starts_with($comentario, 'Equipo recibido.');
}

function observacionesPorEstado(array $bitacora): array
{
    $out = [];
    foreach ($bitacora as $b) {
        $comentario = trim((string) ($b['comentario'] ?? ''));
        if (!comentarioEsObservacion($comentario)) {
            continue;
        }
        $out[(string) ($b['estado'] ?? '')] = $comentario;
    }
    return $out;
}

function observacionesPorPaso(array $bitacora, array $eq = []): array
{
    $pasoDe = [
        'recibido' => 1,
        'diagnostico' => 2,
        'reparacion' => 3,
        'refacciones' => 3,
        'no_reparable' => 3,
        'listo' => 4,
        'entregado' => 5,
    ];
    $out = [];
    $recepcion = trim((string) ($eq['observaciones'] ?? ''));
    if ($recepcion !== '') {
        $out[1] = $recepcion;
    }
    foreach ($bitacora as $b) {
        $comentario = trim((string) ($b['comentario'] ?? ''));
        if (!comentarioEsObservacion($comentario)) {
            continue;
        }
        $paso = $pasoDe[(string) ($b['estado'] ?? '')] ?? 0;
        if ($paso > 0) {
            $out[$paso] = $comentario;
        }
    }
    return $out;
}

function getDiagnostico(PDO $pdo, array $eq): string
{
    $directo = trim((string) ($eq['diagnostico'] ?? ''));
    if ($directo !== '') {
        return $directo;
    }
    $stmt = $pdo->prepare(
        "SELECT comentario FROM bitacora
         WHERE equipo_id = ? AND estado IN ('diagnostico', 'no_reparable')
         ORDER BY id ASC"
    );
    $stmt->execute([(int) $eq['id']]);
    $lineas = [];
    foreach ($stmt->fetchAll() as $row) {
        $comentario = trim((string) ($row['comentario'] ?? ''));
        if (comentarioEsAutomatico($comentario)) {
            continue;
        }
        $lineas[] = $comentario;
    }
    return implode("\n\n", $lineas);
}

function faltantesOrden(PDO $pdo, array $eq): array
{
    $faltan = [];
    $estado = (string) ($eq['estado'] ?? '');
    if (!in_array($estado, estadosOrdenServicio(), true)) {
        $faltan[] = 'Cambiar el estado a “Listo para entrega” o “No reparable” (ahora está en “' . estadoLabel($estado) . '”).';
    }
    $diagnostico = getDiagnostico($pdo, $eq);
    $trabajo = getTrabajoRealizado($pdo, $eq);
    if ($estado === 'no_reparable' && $diagnostico === '') {
        $faltan[] = 'Capturar el diagnóstico (por qué no se puede reparar).';
    }
    if (in_array($estado, ['listo', 'entregado'], true) && $trabajo === '') {
        $faltan[] = 'Capturar el trabajo realizado. Ese dato se llena al pasar a “Listo para entrega”.';
    }
    return $faltan;
}

function puedeEmitirOrden(PDO $pdo, array $eq): bool
{
    return faltantesOrden($pdo, $eq) === [];
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
    if (!columnExists($pdo, 'equipos', 'diagnostico')) {
        $pdo->exec('ALTER TABLE equipos ADD COLUMN diagnostico TEXT DEFAULT NULL AFTER trabajo_realizado');
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

function listPersonas(PDO $pdo, string $q = '', string $area = ''): array
{
    $sql = 'SELECT p.*,
                (SELECT COUNT(*) FROM bienes b WHERE b.persona_id = p.id) AS equipos,
                (SELECT COUNT(*) FROM equipos e WHERE e.persona_id = p.id) AS servicios,
                (SELECT MAX(e.fecha_recepcion) FROM equipos e WHERE e.persona_id = p.id) AS ultimo_servicio,
                (SELECT COUNT(*) FROM prestamos pr WHERE pr.persona_id = p.id AND pr.estado = \'activo\') AS prestamos_activos
            FROM personas p';
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(p.nombre LIKE ? OR p.area_dependencia LIKE ? OR p.telefono LIKE ?)';
        $like = '%' . $q . '%';
        $params = [$like, $like, $like];
    }
    if ($area !== '') {
        $where[] = 'p.area_dependencia = ?';
        $params[] = $area;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY p.nombre';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function listPersonasAreas(PDO $pdo): array
{
    return $pdo->query(
        "SELECT DISTINCT area_dependencia FROM personas
         WHERE area_dependencia IS NOT NULL AND area_dependencia <> ''
         ORDER BY area_dependencia"
    )->fetchAll(PDO::FETCH_COLUMN);
}

function listBienesMarcas(PDO $pdo): array
{
    return $pdo->query(
        "SELECT DISTINCT marca FROM bienes WHERE marca IS NOT NULL AND marca <> '' ORDER BY marca"
    )->fetchAll(PDO::FETCH_COLUMN);
}

function listBienesAreas(PDO $pdo): array
{
    return $pdo->query(
        "SELECT DISTINCT p.area_dependencia
         FROM personas p
         INNER JOIN bienes b ON b.persona_id = p.id
         WHERE p.area_dependencia IS NOT NULL AND p.area_dependencia <> ''
         ORDER BY p.area_dependencia"
    )->fetchAll(PDO::FETCH_COLUMN);
}

function listBienes(PDO $pdo, string $q = '', array $filters = []): array
{
    $sql = "SELECT b.*,
                p.nombre AS persona_nombre,
                p.area_dependencia AS persona_area,
                p.telefono AS persona_telefono,
                (SELECT COUNT(*) FROM equipos e WHERE e.bien_id = b.id) AS servicios,
                ult.id AS ultimo_equipo_id,
                ult.folio AS ultimo_folio,
                ult.estado AS ultimo_estado,
                ult.fecha_recepcion AS ultimo_servicio,
                ult.problema_reportado AS ultimo_problema,
                ult.tipo_problema AS ultimo_tipo_problema,
                ult.estado_fisico AS ultimo_estado_fisico,
                ult.accesorios AS ultimo_accesorios,
                ult.observaciones AS ultimo_observaciones
            FROM bienes b
            LEFT JOIN personas p ON p.id = b.persona_id
            LEFT JOIN equipos ult ON ult.id = (
                SELECT e.id FROM equipos e
                WHERE e.bien_id = b.id
                ORDER BY e.fecha_recepcion DESC, e.id DESC
                LIMIT 1
            )";
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(b.marca LIKE ? OR b.modelo LIKE ? OR b.numero_serie LIKE ?
                     OR b.numero_inventario LIKE ? OR b.tipo_equipo LIKE ?
                     OR p.nombre LIKE ? OR p.area_dependencia LIKE ? OR p.telefono LIKE ?
                     OR ult.folio LIKE ? OR ult.problema_reportado LIKE ? OR ult.accesorios LIKE ?)';
        $like = '%' . $q . '%';
        $params = array_fill(0, 11, $like);
    }

    $tipo = trim((string) ($filters['tipo'] ?? ''));
    if ($tipo !== '') {
        $where[] = 'b.tipo_equipo = ?';
        $params[] = $tipo;
    }

    $marca = trim((string) ($filters['marca'] ?? ''));
    if ($marca !== '') {
        $where[] = 'b.marca = ?';
        $params[] = $marca;
    }

    $area = trim((string) ($filters['area'] ?? ''));
    if ($area !== '') {
        $where[] = 'p.area_dependencia = ?';
        $params[] = $area;
    }

    $estado = trim((string) ($filters['estado'] ?? ''));
    if ($estado !== '' && isset(estadosEquipo()[$estado])) {
        $where[] = 'ult.estado = ?';
        $params[] = $estado;
    }

    $ident = trim((string) ($filters['ident'] ?? ''));
    if ($ident === 'con_inventario') {
        $where[] = "b.numero_inventario IS NOT NULL AND b.numero_inventario <> ''";
    } elseif ($ident === 'sin_inventario') {
        $where[] = "(b.numero_inventario IS NULL OR b.numero_inventario = '')";
    } elseif ($ident === 'con_serie') {
        $where[] = "b.numero_serie IS NOT NULL AND b.numero_serie <> ''";
    } elseif ($ident === 'sin_serie') {
        $where[] = "(b.numero_serie IS NULL OR b.numero_serie = '')";
    }

    $historial = trim((string) ($filters['historial'] ?? ''));
    if ($historial === 'con_servicios') {
        $where[] = '(SELECT COUNT(*) FROM equipos e WHERE e.bien_id = b.id) > 0';
    } elseif ($historial === 'sin_servicios') {
        $where[] = '(SELECT COUNT(*) FROM equipos e WHERE e.bien_id = b.id) = 0';
    } elseif ($historial === 'en_soporte') {
        $where[] = "ult.estado IS NOT NULL AND ult.estado <> 'entregado'";
    } elseif ($historial === 'entregados') {
        $where[] = "ult.estado = 'entregado'";
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY b.tipo_equipo, b.marca, b.modelo, b.id';
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
    $directo = trim((string) ($eq['trabajo_realizado'] ?? ''));
    if ($directo !== '') {
        return $directo;
    }
    $stmt = $pdo->prepare(
        "SELECT comentario FROM bitacora
         WHERE equipo_id = ? AND estado IN ('listo')
         ORDER BY id ASC"
    );
    $stmt->execute([(int) $eq['id']]);
    $lineas = [];
    foreach ($stmt->fetchAll() as $row) {
        $comentario = trim((string) ($row['comentario'] ?? ''));
        if (comentarioEsAutomatico($comentario)) {
            continue;
        }
        $lineas[] = $comentario;
    }
    return implode("\n\n", $lineas);
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

function isAdmin(?array $user = null): bool
{
    $user = $user ?? currentUser();
    return (($user['rol'] ?? '') === 'admin');
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        flash('error', 'Solo el administrador puede entrar a esta sección.');
        redirect('dashboard.php');
    }
}

function listUsuarios(PDO $pdo): array
{
    return $pdo->query(
        'SELECT id, nombre, usuario, rol, created_at FROM usuarios ORDER BY rol ASC, nombre ASC'
    )->fetchAll();
}

function saveTecnico(PDO $pdo, string $nombre, string $usuario, string $password): int
{
    $nombre = trim($nombre);
    $usuario = strtolower(trim($usuario));
    if ($nombre === '') {
        throw new RuntimeException('El nombre del técnico es obligatorio.');
    }
    if (!preg_match('/^[a-z0-9_]{3,60}$/', $usuario)) {
        throw new RuntimeException('El usuario debe tener de 3 a 60 caracteres: letras, números o guion bajo, sin espacios.');
    }
    if (strlen($password) < 6) {
        throw new RuntimeException('La contraseña debe tener al menos 6 caracteres.');
    }
    $check = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
    $check->execute([$usuario]);
    if ($check->fetch()) {
        throw new RuntimeException('Ese nombre de usuario ya existe.');
    }
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?, ?, ?, ?)');
    $stmt->execute([$nombre, $usuario, password_hash($password, PASSWORD_DEFAULT), 'tecnico']);
    return (int) $pdo->lastInsertId();
}

function resetUsuarioPassword(PDO $pdo, int $id, string $password): void
{
    if ($id < 1) {
        throw new RuntimeException('El usuario no existe.');
    }
    if (strlen($password) < 6) {
        throw new RuntimeException('La contraseña debe tener al menos 6 caracteres.');
    }
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('El usuario no existe.');
    }
    $upd = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
    $upd->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
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

function ensureInventarioInternoSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bienes_internos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_equipo VARCHAR(80) NOT NULL,
            marca VARCHAR(120) NOT NULL,
            modelo VARCHAR(120) NOT NULL,
            numero_serie VARCHAR(120) DEFAULT NULL,
            numero_inventario VARCHAR(120) DEFAULT NULL,
            estado_fisico VARCHAR(80) DEFAULT NULL,
            accesorios TEXT DEFAULT NULL,
            observaciones TEXT DEFAULT NULL,
            ubicacion VARCHAR(160) DEFAULT NULL,
            estado VARCHAR(40) NOT NULL DEFAULT 'disponible',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_bi_estado (estado),
            KEY idx_bi_serie (numero_serie),
            KEY idx_bi_inventario (numero_inventario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fotos_internos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bien_interno_id INT NOT NULL,
            ruta VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_fotos_internos_bien FOREIGN KEY (bien_interno_id) REFERENCES bienes_internos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prestamos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(20) NOT NULL UNIQUE,
            persona_id INT NOT NULL,
            fecha_prestamo DATETIME NOT NULL,
            fecha_compromiso DATE NOT NULL,
            observaciones TEXT DEFAULT NULL,
            prestado_por VARCHAR(160) DEFAULT NULL,
            estado VARCHAR(40) NOT NULL DEFAULT 'activo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_prestamos_estado_fecha (estado, fecha_compromiso),
            CONSTRAINT fk_prestamos_persona FOREIGN KEY (persona_id) REFERENCES personas(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prestamo_devoluciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prestamo_id INT NOT NULL,
            fecha DATETIME NOT NULL,
            recibido_por VARCHAR(160) DEFAULT NULL,
            observaciones TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_pdev_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prestamo_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prestamo_id INT NOT NULL,
            bien_interno_id INT NOT NULL,
            estado_fisico_salida VARCHAR(80) DEFAULT NULL,
            accesorios_salida TEXT DEFAULT NULL,
            fecha_devolucion DATETIME DEFAULT NULL,
            devolucion_id INT DEFAULT NULL,
            estado_fisico_regreso VARCHAR(80) DEFAULT NULL,
            CONSTRAINT fk_pitems_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
            CONSTRAINT fk_pitems_bien FOREIGN KEY (bien_interno_id) REFERENCES bienes_internos(id) ON DELETE RESTRICT,
            CONSTRAINT fk_pitems_devolucion FOREIGN KEY (devolucion_id) REFERENCES prestamo_devoluciones(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function etiquetaBienInterno(array $b): string
{
    $nombre = trim((string) ($b['marca'] ?? '') . ' ' . (string) ($b['modelo'] ?? ''));
    $tipo = trim((string) ($b['tipo_equipo'] ?? ''));
    $label = $tipo !== '' ? $tipo : 'Bien';
    if ($nombre !== '') {
        $label .= ' · ' . $nombre;
    }
    if (!empty($b['numero_inventario'])) {
        $label .= ' · Inv. ' . $b['numero_inventario'];
    } elseif (!empty($b['numero_serie'])) {
        $label .= ' · Serie ' . $b['numero_serie'];
    }
    return $label;
}

function getBienInterno(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM bienes_internos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findBienInterno(PDO $pdo, ?string $serie, ?string $inventario, int $exceptId = 0): ?array
{
    $serie = trim((string) $serie);
    $inventario = trim((string) $inventario);
    if ($serie !== '') {
        $sql = 'SELECT * FROM bienes_internos WHERE numero_serie = ?';
        $params = [$serie];
        if ($exceptId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    if ($inventario !== '') {
        $sql = 'SELECT * FROM bienes_internos WHERE numero_inventario = ?';
        $params = [$inventario];
        if ($exceptId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    return null;
}

function saveBienInterno(PDO $pdo, array $data, int $id = 0): int
{
    $tipo = trim((string) ($data['tipo_equipo'] ?? ''));
    $marca = trim((string) ($data['marca'] ?? ''));
    $modelo = trim((string) ($data['modelo'] ?? ''));
    $serie = trim((string) ($data['numero_serie'] ?? '')) ?: null;
    $inventario = trim((string) ($data['numero_inventario'] ?? '')) ?: null;
    $estadoFisico = trim((string) ($data['estado_fisico'] ?? '')) ?: null;
    $accesorios = trim((string) ($data['accesorios'] ?? '')) ?: null;
    $observaciones = trim((string) ($data['observaciones'] ?? '')) ?: null;
    $ubicacion = trim((string) ($data['ubicacion'] ?? '')) ?: null;
    if ($tipo === '' || $marca === '' || $modelo === '') {
        throw new RuntimeException('Tipo, marca y modelo del bien son obligatorios.');
    }
    $dup = findBienInterno($pdo, $serie, $inventario, $id);
    if ($dup) {
        throw new RuntimeException('Ya existe un bien interno con esa serie o número de inventario.');
    }
    if ($id > 0 && getBienInterno($pdo, $id)) {
        $stmt = $pdo->prepare(
            'UPDATE bienes_internos SET tipo_equipo = ?, marca = ?, modelo = ?, numero_serie = ?,
             numero_inventario = ?, estado_fisico = ?, accesorios = ?, observaciones = ?, ubicacion = ?
             WHERE id = ?'
        );
        $stmt->execute([$tipo, $marca, $modelo, $serie, $inventario, $estadoFisico, $accesorios, $observaciones, $ubicacion, $id]);
        return $id;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO bienes_internos (
            tipo_equipo, marca, modelo, numero_serie, numero_inventario,
            estado_fisico, accesorios, observaciones, ubicacion, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$tipo, $marca, $modelo, $serie, $inventario, $estadoFisico, $accesorios, $observaciones, $ubicacion, 'disponible']);
    return (int) $pdo->lastInsertId();
}

function setEstadoBienInterno(PDO $pdo, int $id, string $estado): void
{
    if (!isset(estadosBienInterno()[$estado])) {
        throw new RuntimeException('Estado de bien no válido.');
    }
    $bien = getBienInterno($pdo, $id);
    if (!$bien) {
        throw new RuntimeException('El bien no existe.');
    }
    if ($estado === 'baja' && $bien['estado'] === 'prestado') {
        throw new RuntimeException('No se puede dar de baja un bien que está prestado. Recíbalo primero.');
    }
    if ($estado === 'disponible' && $bien['estado'] === 'prestado') {
        throw new RuntimeException('Un bien prestado solo vuelve a disponible al devolverlo.');
    }
    $stmt = $pdo->prepare('UPDATE bienes_internos SET estado = ? WHERE id = ?');
    $stmt->execute([$estado, $id]);
}

function listBienesInternosMarcas(PDO $pdo): array
{
    return $pdo->query(
        "SELECT DISTINCT marca FROM bienes_internos WHERE marca IS NOT NULL AND marca <> '' ORDER BY marca"
    )->fetchAll(PDO::FETCH_COLUMN);
}

function listBienesInternosUbicaciones(PDO $pdo): array
{
    return $pdo->query(
        "SELECT DISTINCT ubicacion FROM bienes_internos WHERE ubicacion IS NOT NULL AND ubicacion <> '' ORDER BY ubicacion"
    )->fetchAll(PDO::FETCH_COLUMN);
}

function listBienesInternos(PDO $pdo, string $q = '', array $filters = []): array
{
    $sql = 'SELECT b.*,
                (SELECT COUNT(*) FROM prestamo_items i WHERE i.bien_interno_id = b.id) AS prestamos,
                ult.folio AS prestamo_folio,
                ult.prestamo_id AS prestamo_id,
                ult.persona_nombre AS prestamo_persona,
                ult.fecha_compromiso AS prestamo_compromiso
            FROM bienes_internos b
            LEFT JOIN (
                SELECT i.bien_interno_id, p.id AS prestamo_id, p.folio, p.fecha_compromiso, pe.nombre AS persona_nombre
                FROM prestamo_items i
                INNER JOIN prestamos p ON p.id = i.prestamo_id
                LEFT JOIN personas pe ON pe.id = p.persona_id
                WHERE i.fecha_devolucion IS NULL
            ) ult ON ult.bien_interno_id = b.id';
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(b.marca LIKE ? OR b.modelo LIKE ? OR b.numero_serie LIKE ?
                     OR b.numero_inventario LIKE ? OR b.tipo_equipo LIKE ?
                     OR b.ubicacion LIKE ? OR b.accesorios LIKE ? OR ult.folio LIKE ? OR ult.persona_nombre LIKE ?)';
        $like = '%' . $q . '%';
        $params = array_fill(0, 9, $like);
    }
    $tipo = trim((string) ($filters['tipo'] ?? ''));
    if ($tipo !== '') {
        $where[] = 'b.tipo_equipo = ?';
        $params[] = $tipo;
    }
    $marca = trim((string) ($filters['marca'] ?? ''));
    if ($marca !== '') {
        $where[] = 'b.marca = ?';
        $params[] = $marca;
    }
    $estado = trim((string) ($filters['estado'] ?? ''));
    if ($estado !== '' && isset(estadosBienInterno()[$estado])) {
        $where[] = 'b.estado = ?';
        $params[] = $estado;
    }
    $ubicacion = trim((string) ($filters['ubicacion'] ?? ''));
    if ($ubicacion !== '') {
        $where[] = 'b.ubicacion = ?';
        $params[] = $ubicacion;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY b.tipo_equipo, b.marca, b.modelo, b.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function listBienesInternosDisponibles(PDO $pdo): array
{
    return listBienesInternos($pdo, '', ['estado' => 'disponible']);
}

function getFotosInternos(PDO $pdo, int $bienId): array
{
    $stmt = $pdo->prepare('SELECT * FROM fotos_internos WHERE bien_interno_id = ? ORDER BY id');
    $stmt->execute([$bienId]);
    return $stmt->fetchAll();
}

function saveUploadedFotosList(array $files, string $subdir = 'fotos'): array
{
    $rutas = [];
    if (empty($files['name']) || !is_array($files['name'])) {
        return $rutas;
    }
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i] ?? 0,
        ];
        $ruta = saveUploadedFile($file, $subdir, ['jpg', 'jpeg', 'png', 'webp'], MAX_PHOTO_BYTES);
        if ($ruta) {
            $rutas[] = $ruta;
        }
    }
    return $rutas;
}

function addFotosInternos(PDO $pdo, int $bienId, array $files): int
{
    $rutas = saveUploadedFotosList($files);
    $ins = $pdo->prepare('INSERT INTO fotos_internos (bien_interno_id, ruta) VALUES (?, ?)');
    foreach ($rutas as $ruta) {
        $ins->execute([$bienId, $ruta]);
    }
    return count($rutas);
}

function deleteFotoInterno(PDO $pdo, int $fotoId, int $bienId): void
{
    $stmt = $pdo->prepare('SELECT * FROM fotos_internos WHERE id = ? AND bien_interno_id = ?');
    $stmt->execute([$fotoId, $bienId]);
    $foto = $stmt->fetch();
    if (!$foto) {
        throw new RuntimeException('La foto no existe.');
    }
    $pdo->prepare('DELETE FROM fotos_internos WHERE id = ?')->execute([$fotoId]);
}

function generateFolioPrestamo(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'PI-' . $year . '-';
    $stmt = $pdo->prepare('SELECT folio FROM prestamos WHERE folio LIKE ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $n = 1;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $n = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

function prestamoVencido(array $prestamo): bool
{
    if (($prestamo['estado'] ?? '') !== 'activo') {
        return false;
    }
    $fecha = substr((string) ($prestamo['fecha_compromiso'] ?? ''), 0, 10);
    return $fecha !== '' && $fecha < date('Y-m-d');
}

function prestamoVencePronto(array $prestamo): bool
{
    if (($prestamo['estado'] ?? '') !== 'activo' || prestamoVencido($prestamo)) {
        return false;
    }
    $fecha = substr((string) ($prestamo['fecha_compromiso'] ?? ''), 0, 10);
    if ($fecha === '') {
        return false;
    }
    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    return $fecha === $hoy || $fecha === $manana;
}

function prestamoEstadoVisual(array $prestamo): string
{
    if (($prestamo['estado'] ?? '') === 'cerrado') {
        return 'cerrado';
    }
    if (prestamoVencido($prestamo)) {
        return 'vencido';
    }
    if (prestamoVencePronto($prestamo)) {
        return 'vence_pronto';
    }
    return 'activo';
}

function prestamoEstadoLabel(string $estado): string
{
    return [
        'activo' => 'Activo',
        'vencido' => 'Vencido',
        'vence_pronto' => 'Vence pronto',
        'cerrado' => 'Devuelto',
    ][$estado] ?? $estado;
}

function sqlPrestamoSelect(): string
{
    return "SELECT p.*,
                pe.nombre AS persona_nombre,
                pe.area_dependencia AS persona_area,
                pe.telefono AS persona_telefono,
                (SELECT COUNT(*) FROM prestamo_items i WHERE i.prestamo_id = p.id) AS items_total,
                (SELECT COUNT(*) FROM prestamo_items i WHERE i.prestamo_id = p.id AND i.fecha_devolucion IS NULL) AS items_fuera
            FROM prestamos p
            LEFT JOIN personas pe ON pe.id = p.persona_id";
}

function decoratePrestamo(array $p): array
{
    $p['estado_visual'] = prestamoEstadoVisual($p);
    $p['estado_visual_label'] = prestamoEstadoLabel($p['estado_visual']);
    return $p;
}

function getPrestamo(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(sqlPrestamoSelect() . ' WHERE p.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? decoratePrestamo($row) : null;
}

function getPrestamoItems(PDO $pdo, int $prestamoId, bool $soloPendientes = false): array
{
    $sql = 'SELECT i.*, b.tipo_equipo, b.marca, b.modelo, b.numero_serie, b.numero_inventario, b.ubicacion, b.estado AS bien_estado
            FROM prestamo_items i
            INNER JOIN bienes_internos b ON b.id = i.bien_interno_id
            WHERE i.prestamo_id = ?';
    if ($soloPendientes) {
        $sql .= ' AND i.fecha_devolucion IS NULL';
    }
    $sql .= ' ORDER BY i.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$prestamoId]);
    return $stmt->fetchAll();
}

function getPrestamoDevolucion(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT d.*, p.folio, p.persona_id, p.fecha_prestamo, p.fecha_compromiso, p.prestado_por,
                pe.nombre AS persona_nombre, pe.area_dependencia AS persona_area, pe.telefono AS persona_telefono
         FROM prestamo_devoluciones d
         INNER JOIN prestamos p ON p.id = d.prestamo_id
         LEFT JOIN personas pe ON pe.id = p.persona_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getDevolucionItems(PDO $pdo, int $devolucionId): array
{
    $stmt = $pdo->prepare(
        'SELECT i.*, b.tipo_equipo, b.marca, b.modelo, b.numero_serie, b.numero_inventario
         FROM prestamo_items i
         INNER JOIN bienes_internos b ON b.id = i.bien_interno_id
         WHERE i.devolucion_id = ?
         ORDER BY i.id'
    );
    $stmt->execute([$devolucionId]);
    return $stmt->fetchAll();
}

function listPrestamoDevoluciones(PDO $pdo, int $prestamoId): array
{
    $stmt = $pdo->prepare('SELECT * FROM prestamo_devoluciones WHERE prestamo_id = ? ORDER BY id DESC');
    $stmt->execute([$prestamoId]);
    return $stmt->fetchAll();
}

function listPrestamos(PDO $pdo, string $q = '', string $filtro = ''): array
{
    $sql = sqlPrestamoSelect();
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(p.folio LIKE ? OR pe.nombre LIKE ? OR pe.area_dependencia LIKE ? OR pe.telefono LIKE ?
                     OR p.prestado_por LIKE ? OR p.observaciones LIKE ?)';
        $like = '%' . $q . '%';
        $params = array_fill(0, 6, $like);
    }
    if ($filtro === 'activo') {
        $where[] = "p.estado = 'activo' AND p.fecha_compromiso > CURDATE()";
    } elseif ($filtro === 'vencido') {
        $where[] = "p.estado = 'activo' AND p.fecha_compromiso < CURDATE()";
    } elseif ($filtro === 'vence_pronto') {
        $where[] = "p.estado = 'activo' AND p.fecha_compromiso >= CURDATE() AND p.fecha_compromiso <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($filtro === 'cerrado') {
        $where[] = "p.estado = 'cerrado'";
    } elseif ($filtro === 'abiertos') {
        $where[] = "p.estado = 'activo'";
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY (p.estado = \'activo\') DESC, p.fecha_compromiso ASC, p.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return array_map('decoratePrestamo', $stmt->fetchAll());
}

function prestamosDePersona(PDO $pdo, int $personaId): array
{
    $stmt = $pdo->prepare(sqlPrestamoSelect() . ' WHERE p.persona_id = ? ORDER BY p.fecha_prestamo DESC, p.id DESC');
    $stmt->execute([$personaId]);
    return array_map('decoratePrestamo', $stmt->fetchAll());
}

function prestamosDeBienInterno(PDO $pdo, int $bienId): array
{
    $stmt = $pdo->prepare(
        sqlPrestamoSelect() . '
         WHERE p.id IN (SELECT i.prestamo_id FROM prestamo_items i WHERE i.bien_interno_id = ?)
         ORDER BY p.fecha_prestamo DESC, p.id DESC'
    );
    $stmt->execute([$bienId]);
    return array_map('decoratePrestamo', $stmt->fetchAll());
}

function prestamoActivoDeBien(PDO $pdo, int $bienId): ?array
{
    $stmt = $pdo->prepare(
        sqlPrestamoSelect() . '
         INNER JOIN prestamo_items i ON i.prestamo_id = p.id
         WHERE i.bien_interno_id = ? AND i.fecha_devolucion IS NULL
         LIMIT 1'
    );
    $stmt->execute([$bienId]);
    $row = $stmt->fetch();
    return $row ? decoratePrestamo($row) : null;
}

function countPrestamosVencidos(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM prestamos WHERE estado = 'activo' AND fecha_compromiso < CURDATE()"
    )->fetchColumn();
}

function statsInventarioInterno(PDO $pdo): array
{
    $disponibles = (int) $pdo->query("SELECT COUNT(*) FROM bienes_internos WHERE estado = 'disponible'")->fetchColumn();
    $prestados = (int) $pdo->query("SELECT COUNT(*) FROM bienes_internos WHERE estado = 'prestado'")->fetchColumn();
    $bajas = (int) $pdo->query("SELECT COUNT(*) FROM bienes_internos WHERE estado = 'baja'")->fetchColumn();
    $vencidos = countPrestamosVencidos($pdo);
    $vencePronto = (int) $pdo->query(
        "SELECT COUNT(*) FROM prestamos
         WHERE estado = 'activo' AND fecha_compromiso >= CURDATE()
           AND fecha_compromiso <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
    )->fetchColumn();
    $activos = (int) $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo'")->fetchColumn();
    return [
        'disponibles' => $disponibles,
        'prestados' => $prestados,
        'bajas' => $bajas,
        'vencidos' => $vencidos,
        'vence_pronto' => $vencePronto,
        'activos' => $activos,
        'bienes' => $disponibles + $prestados + $bajas,
    ];
}

function crearPrestamo(PDO $pdo, array $data, array $bienIds): int
{
    $personaId = (int) ($data['persona_id'] ?? 0);
    $fechaPrestamo = trim((string) ($data['fecha_prestamo'] ?? ''));
    $fechaCompromiso = trim((string) ($data['fecha_compromiso'] ?? ''));
    $observaciones = trim((string) ($data['observaciones'] ?? '')) ?: null;
    $prestadoPor = trim((string) ($data['prestado_por'] ?? '')) ?: null;
    $bienIds = array_values(array_unique(array_map('intval', $bienIds)));
    $bienIds = array_values(array_filter($bienIds, static fn(int $id): bool => $id > 0));
    if ($personaId < 1 || !getPersona($pdo, $personaId)) {
        throw new RuntimeException('Seleccione a la persona que recibe el material.');
    }
    if (!$bienIds) {
        throw new RuntimeException('Seleccione al menos un bien para prestar.');
    }
    if ($fechaPrestamo === '') {
        $fechaPrestamo = date('Y-m-d H:i:s');
    } else {
        $ts = strtotime(str_replace('T', ' ', $fechaPrestamo));
        if ($ts === false) {
            throw new RuntimeException('La fecha de préstamo no es válida.');
        }
        $fechaPrestamo = date('Y-m-d H:i:s', $ts);
    }
    if ($fechaCompromiso === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCompromiso)) {
        throw new RuntimeException('Indique la fecha de devolución comprometida.');
    }
    if ($fechaCompromiso < substr($fechaPrestamo, 0, 10)) {
        throw new RuntimeException('La fecha de devolución no puede ser anterior al préstamo.');
    }

    $pdo->beginTransaction();
    try {
        $folio = generateFolioPrestamo($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO prestamos (folio, persona_id, fecha_prestamo, fecha_compromiso, observaciones, prestado_por, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$folio, $personaId, $fechaPrestamo, $fechaCompromiso, $observaciones, $prestadoPor, 'activo']);
        $prestamoId = (int) $pdo->lastInsertId();
        $insItem = $pdo->prepare(
            'INSERT INTO prestamo_items (prestamo_id, bien_interno_id, estado_fisico_salida, accesorios_salida)
             VALUES (?, ?, ?, ?)'
        );
        $updBien = $pdo->prepare('UPDATE bienes_internos SET estado = ? WHERE id = ? AND estado = ?');
        foreach ($bienIds as $bid) {
            $bien = getBienInterno($pdo, $bid);
            if (!$bien) {
                throw new RuntimeException('Uno de los bienes ya no existe.');
            }
            if ($bien['estado'] !== 'disponible') {
                throw new RuntimeException('«' . etiquetaBienInterno($bien) . '» no está disponible.');
            }
            $updBien->execute(['prestado', $bid, 'disponible']);
            if ($updBien->rowCount() !== 1) {
                throw new RuntimeException('«' . etiquetaBienInterno($bien) . '» acaba de prestarse a alguien más.');
            }
            $insItem->execute([$prestamoId, $bid, $bien['estado_fisico'], $bien['accesorios']]);
        }
        $pdo->commit();
        return $prestamoId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function devolverPrestamoItems(PDO $pdo, int $prestamoId, array $itemIds, string $recibidoPor, string $observaciones = '', array $estadosFisicos = []): int
{
    $prestamo = getPrestamo($pdo, $prestamoId);
    if (!$prestamo || $prestamo['estado'] !== 'activo') {
        throw new RuntimeException('El préstamo no está activo.');
    }
    $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
    $itemIds = array_values(array_filter($itemIds, static fn(int $id): bool => $id > 0));
    if (!$itemIds) {
        throw new RuntimeException('Seleccione al menos un bien a devolver.');
    }
    $recibidoPor = trim($recibidoPor) ?: (currentUser()['nombre'] ?? ORG_AREA);
    $observaciones = trim($observaciones) ?: null;
    $ahora = date('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $insDev = $pdo->prepare(
            'INSERT INTO prestamo_devoluciones (prestamo_id, fecha, recibido_por, observaciones) VALUES (?, ?, ?, ?)'
        );
        $insDev->execute([$prestamoId, $ahora, $recibidoPor, $observaciones]);
        $devolucionId = (int) $pdo->lastInsertId();

        $getItem = $pdo->prepare(
            'SELECT * FROM prestamo_items WHERE id = ? AND prestamo_id = ? AND fecha_devolucion IS NULL'
        );
        $updItem = $pdo->prepare(
            'UPDATE prestamo_items
             SET fecha_devolucion = ?, devolucion_id = ?, estado_fisico_regreso = ?
             WHERE id = ?'
        );
        $updBien = $pdo->prepare('UPDATE bienes_internos SET estado = ?, estado_fisico = COALESCE(?, estado_fisico) WHERE id = ?');

        foreach ($itemIds as $itemId) {
            $getItem->execute([$itemId, $prestamoId]);
            $item = $getItem->fetch();
            if (!$item) {
                throw new RuntimeException('Uno de los bienes ya fue devuelto o no pertenece a este préstamo.');
            }
            $estadoRegreso = trim((string) ($estadosFisicos[$itemId] ?? $estadosFisicos[(string) $itemId] ?? '')) ?: null;
            $updItem->execute([$ahora, $devolucionId, $estadoRegreso, $itemId]);
            $updBien->execute(['disponible', $estadoRegreso, (int) $item['bien_interno_id']]);
        }

        $pend = $pdo->prepare('SELECT COUNT(*) FROM prestamo_items WHERE prestamo_id = ? AND fecha_devolucion IS NULL');
        $pend->execute([$prestamoId]);
        if ((int) $pend->fetchColumn() === 0) {
            $pdo->prepare("UPDATE prestamos SET estado = 'cerrado' WHERE id = ?")->execute([$prestamoId]);
        }
        $pdo->commit();
        return $devolucionId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
?>
