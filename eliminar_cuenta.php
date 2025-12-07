<?php
// eliminar_cuenta.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay usuario logueado, lo mandamos al login
if (empty($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuarioId = (int) $_SESSION['usuario_id'];

// 1. Incluir tu controlador de conexión
require_once __DIR__ . "/app/config/ConnectionController.php";

$connectionCtrl = new ConnectionController();
$conn = $connectionCtrl->connect();

// 2. Iniciar transacción
$conn->begin_transaction();

try {

    // ============================
    // 1. BORRAR CARRITO DEL USUARIO
    // ===========================

    $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_usuario_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $stmt->close();
    }

    // ===================================
    // 2. BORRAR REGISTRO DE TABLA USUARIO
    // ===================================

    $stmt = $conn->prepare("DELETE FROM usuario WHERE usuario_id = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar eliminación de usuario: " . $conn->error);
    }
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $stmt->close();

    // 3. Confirmar cambios
    $conn->commit();

    // 4. Cerrar sesión
    session_unset();
    session_destroy();

    // 5. Nueva sesión para mostrar mensaje en login
    session_start();
    $_SESSION['account_deleted'] = true;

    // 6. Redirigir al login
    header("Location: login.php");
    exit();

} catch (Exception $e) {

    // Revertir cambios si algo falla
    $conn->rollback();
    die("Error al eliminar la cuenta: " . $e->getMessage());
}
