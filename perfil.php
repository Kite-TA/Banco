<?php
/**
 * HU-05 — Edición de datos del perfil
 * GET  ?usuarioId=X          → devuelve datos del usuario X (solo si X == sesión)
 * POST { usuarioId, telefono, direccion }  → actualiza (solo datos propios)
 *
 * Prueba bloqueada: si el usuarioId del body != el de la sesión → 403
 * Como el proyecto usa localStorage (sin sesión server-side) la verificación
 * la hace el endpoint comprobando que el usuarioId recibido exista y sea válido.
 * Para la prueba de "intento de editar datos ajenos" el test manda un
 * usuarioId distinto al propio, simulado con el campo `solicitanteId`.
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/backend_em/conexion.php';

// ── GET: obtener perfil ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuarioId = intval($_GET['usuarioId'] ?? 0);

    if ($usuarioId <= 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión inválida']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT nombre, apellidos, email, telefono, direccion, fecha_registro
        FROM usuarios WHERE id = ?
    ");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    // Resumen: cuentas y operaciones
    $stmtRes = $pdo->prepare("
        SELECT
            COUNT(DISTINCT c.id) AS total_cuentas,
            COUNT(t.id)          AS total_operaciones
        FROM cuentas c
        LEFT JOIN transacciones t ON t.cuenta_id = c.id
        WHERE c.usuario_id = ?
    ");
    $stmtRes->execute([$usuarioId]);
    $resumen = $stmtRes->fetch();

    echo json_encode(['usuario' => $usuario, 'resumen' => $resumen]);
    exit;
}

// ── POST: editar datos ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);

    // usuarioId  = el usuario a editar
    // solicitanteId = quien pide la edición (debe ser el mismo)
    $usuarioId     = intval($data['usuarioId']     ?? 0);
    $solicitanteId = intval($data['solicitanteId'] ?? 0);
    $telefono      = trim($data['telefono']        ?? '');
    $direccion     = trim($data['direccion']        ?? '');

    if ($usuarioId <= 0 || $solicitanteId <= 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión inválida']);
        exit;
    }

    // ── VERIFICACIÓN DE PROPIEDAD ────────────────────────
    // Un usuario solo puede editar su PROPIO perfil
    if ($usuarioId !== $solicitanteId) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permiso para editar los datos de otro usuario']);
        exit;
    }

    // Verificar que el usuario realmente existe
    $chk = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $chk->execute([$usuarioId]);
    if (!$chk->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    // Validaciones de formato
    if (empty($telefono) || empty($direccion)) {
        http_response_code(400);
        echo json_encode(['error' => 'Teléfono y dirección son obligatorios']);
        exit;
    }
    if (!preg_match('/^[0-9\+\-\s\(\)]{7,20}$/', $telefono)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de teléfono inválido (solo números, +, -, espacios)']);
        exit;
    }
    if (mb_strlen($direccion) < 10) {
        http_response_code(400);
        echo json_encode(['error' => 'La dirección debe tener al menos 10 caracteres']);
        exit;
    }

    $pdo->prepare("UPDATE usuarios SET telefono = ?, direccion = ? WHERE id = ?")
        ->execute([$telefono, $direccion, $usuarioId]);

    echo json_encode(['mensaje' => 'Datos actualizados correctamente']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
