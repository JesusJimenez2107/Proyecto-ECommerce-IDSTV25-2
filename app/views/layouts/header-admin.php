<?php
require_once __DIR__ . "/../../config/security.php";
requireAdmin();
$adminName = $_SESSION['nombre'] ?? 'Admin';
?>
<header class="topbar">
    <div class="topbar__inner">
        <a href="panel-admin.php" class="brand">
            <img src="Assets/img/logo.png" alt="Raíz Viva">
        </a>

        <div class="topbar__admin-center">
            <span class="topbar__admin-title">Panel de administración</span>

            <nav class="topbar__admin-nav">
                <a href="panel-admin.php">Inicio</a>
                <a href="usuarios.php">Usuarios</a>
                <a href="productos-admin.php">Productos</a>
                <a href="reportes-admin.php">Reportes</a>
            </nav>
        </div>

        <div class="actions">
            <span class="action action--text">
                <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
    </div>
</header>