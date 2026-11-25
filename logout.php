<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todos los datos de sesión
session_unset();
session_destroy();

// Crear una nueva sesión limpia para guardar el mensaje de éxito
session_start();
session_regenerate_id(true);
$_SESSION['logout_success'] = true;

header("Location: login.php");
exit();