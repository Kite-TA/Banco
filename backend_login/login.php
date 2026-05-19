<?php
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host       = 'localhost';
$dbname     = 'banco_db';
$usuario_bd = 'root';
$password_bd = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario_bd, $password_bd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

function ensureLockColumns(PDO $pdo) {
    $columns = ['failed_attempts' => 'INT(3) NOT NULL DEFAULT 0', 'lock_expires_at' => 'DATETIME NULL DEFAULT NULL'];
    foreach ($columns as $name => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '$name'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN $name $definition");
        }
    }
}

try {
    ensureLockColumns($pdo);
} catch (PDOException $e) {
    // Si no se pueden crear, seguimos sin bloqueo automático.
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Por favor, completa todos los campos']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.password_hash, u.failed_attempts, u.lock_expires_at, c.numero_cuenta, c.saldo, c.id AS cuenta_id FROM usuarios u LEFT JOIN cuentas c ON c.usuario_id = u.id WHERE u.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $now = new DateTimeImmutable('now');
        $locked = false;
        if (!empty($usuario['lock_expires_at'])) {
            $expires = new DateTimeImmutable($usuario['lock_expires_at']);
            if ($expires > $now) {
                $locked = true;
            }
        }

        if ($locked) {
            $remaining = max(0, $expires->getTimestamp() - $now->getTimestamp());
            $minutes = ceil($remaining / 60);
            $timeText = $minutes > 1 ? "$minutes minutos" : "$remaining segundos";
            http_response_code(423);
            echo json_encode(['success' => false, 'error' => "Cuenta bloqueada temporalmente. Intenta de nuevo en $timeText."]);
            exit;
        }

        if (password_verify($password, $usuario['password_hash'])) {
            if (intval($usuario['failed_attempts']) > 0 || !empty($usuario['lock_expires_at'])) {
                $reset = $pdo->prepare("UPDATE usuarios SET failed_attempts = 0, lock_expires_at = NULL WHERE id = ?");
                $reset->execute([$usuario['id']]);
            }

            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];

            echo json_encode([
                'success'      => true,
                'mensaje'      => '¡Bienvenido, ' . $usuario['nombre'] . '!',
                'usuarioId'    => $usuario['id'],
                'nombre'       => $usuario['nombre'],
                'numeroCuenta' => $usuario['numero_cuenta'],
                'saldo'        => $usuario['saldo'],
                'cuentaId'     => $usuario['cuenta_id']
            ]);
            exit;
        }

        $attempts = intval($usuario['failed_attempts']) + 1;
        if ($attempts >= 3) {
            $lockUntil = $now->modify('+10 minutes');
            $lock = $pdo->prepare("UPDATE usuarios SET failed_attempts = 3, lock_expires_at = ? WHERE id = ?");
            $lock->execute([$lockUntil->format('Y-m-d H:i:s'), $usuario['id']]);
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Has excedido 3 intentos. La cuenta está bloqueada durante 10 minutos.']);
            exit;
        }

        $update = $pdo->prepare("UPDATE usuarios SET failed_attempts = ? WHERE id = ?");
        $update->execute([$attempts, $usuario['id']]);
        $remaining = 3 - $attempts;
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Correo o contraseña incorrectos. Te quedan $remaining intentos."]);
        exit;
    }

    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Correo o contraseña incorrectos']);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión al banco']);
    exit;
}
?>
