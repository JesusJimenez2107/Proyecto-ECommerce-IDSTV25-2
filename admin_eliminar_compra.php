<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo admins
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['compra_id'], $_POST['usuario_id'])) {
    header("Location: usuarios.php");
    exit();
}

$compraId = (int) $_POST['compra_id'];
$usuarioId = (int) $_POST['usuario_id'];

if ($compraId <= 0 || $usuarioId <= 0) {
    header("Location: usuarios.php");
    exit();
}

require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

// 1) borrar detalles
$stmtDet = $conn->prepare("DELETE FROM detalle_compra WHERE compra_compra_id = ?");
$stmtDet->bind_param("i", $compraId);
$stmtDet->execute();
$stmtDet->close();

// 2) borrar compra
$stmtCmp = $conn->prepare("DELETE FROM compra WHERE compra_id = ? AND usuario_usuario_id = ?");
$stmtCmp->bind_param("ii", $compraId, $usuarioId);
$stmtCmp->execute();
$stmtCmp->close();

// Volver a la ficha del usuario
header("Location: datos-usuario-admin.php?id=" . $usuarioId);
exit();
