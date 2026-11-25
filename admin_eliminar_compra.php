<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: usuarios.php");
    exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    die("Error de seguridad: Token CSRF inválido.");
}

if (!isset($_POST['compra_id'], $_POST['usuario_id'])) {
    header("Location: usuarios.php");
    exit();
}

$compraId  = (int) $_POST['compra_id'];
$usuarioId = (int) $_POST['usuario_id'];

if ($compraId <= 0 || $usuarioId <= 0) {
    header("Location: usuarios.php");
    exit();
}

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();
$conn->begin_transaction();

try {
    // 1) Verificar que la compra exista y pertenezca realmente a ese usuario antes de borrar nada
    $stmtCheck = $conn->prepare("SELECT compra_id FROM compra WHERE compra_id = ? AND usuario_usuario_id = ? FOR UPDATE");
    $stmtCheck->bind_param("ii", $compraId, $usuarioId);
    $stmtCheck->execute();
    if (!$stmtCheck->get_result()->fetch_assoc()) {
        $conn->rollback();
        header("Location: datos-usuario-admin.php?id=" . $usuarioId . "&error=not_found");
        exit();
    }

    // 2) Borrar registro de pago asociado (por integridad referencial)
    $stmtPago = $conn->prepare("DELETE FROM pago WHERE compra_compra_id = ?");
    $stmtPago->bind_param("i", $compraId);
    $stmtPago->execute();

    // 3) Borrar detalles de la compra
    $stmtDet = $conn->prepare("DELETE FROM detalle_compra WHERE compra_compra_id = ?");
    $stmtDet->bind_param("i", $compraId);
    $stmtDet->execute();

    // 4) Borrar cabecera de la compra
    $stmtCmp = $conn->prepare("DELETE FROM compra WHERE compra_id = ? AND usuario_usuario_id = ?");
    $stmtCmp->bind_param("ii", $compraId, $usuarioId);
    $stmtCmp->execute();

    $conn->commit();
    header("Location: datos-usuario-admin.php?id=" . $usuarioId . "&msg=deleted");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error al eliminar compra (admin): " . $e->getMessage());
    header("Location: datos-usuario-admin.php?id=" . $usuarioId . "&error=db");
    exit();
}