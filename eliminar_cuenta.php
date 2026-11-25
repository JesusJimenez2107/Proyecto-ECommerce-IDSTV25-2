<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mi-cuenta.php");
    exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    die("Error de seguridad: Token CSRF inválido.");
}

$usuarioId = (int) $_SESSION['usuario_id'];

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$conn->begin_transaction();

try {
    // 1. Borrar carrito del usuario
    $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_usuario_id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $stmt->close();

    // 2. Borrar registro de tabla usuario
    $stmtUser = $conn->prepare("DELETE FROM usuario WHERE usuario_id = ?");
    $stmtUser->bind_param("i", $usuarioId);
    $stmtUser->execute();
    $stmtUser->close();

    // 3. Confirmar cambios
    $conn->commit();

    // 4. Cerrar sesión y regenerar ID limpio
    session_unset();
    session_destroy();

    session_start();
    session_regenerate_id(true);
    $_SESSION['account_deleted'] = true;

    header("Location: login.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error al eliminar cuenta usuario $usuarioId: " . $e->getMessage());
    header("Location: mi-cuenta.php?error=delete_failed");
    exit();
}