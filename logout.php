<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todos los datos de sesión
session_unset();
session_destroy();

// Crear una nueva sesión para guardar el mensaje de éxito
session_start();
$_SESSION['logout_success'] = true;

// Redirigir al login (debe ser login.php)
header("Location: login.php");
exit();
?>
