<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no está logueado, lo mandamos a login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

// Conexión a la BD
require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

// ID del usuario logueado
$usuario_id = (int) $_SESSION['usuario_id'];

// Consultar datos del usuario (AHORA TAMBIÉN TRAEMOS rol)
$query = "SELECT nombre, apellidos, email, direccion, telefono, rol 
          FROM usuario 
          WHERE usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Por si algo raro pasa y no encuentra el usuario
$nombre = $user['nombre'] ?? '';
$apellidos = $user['apellidos'] ?? '';
$email = $user['email'] ?? '';
$direccion = $user['direccion'] ?? '';
$telefono = $user['telefono'] ?? '';
$rol = $user['rol'] ?? 'cliente';   // valor por defecto
$isAdmin = ($rol === 'admin');

// Para el header
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);

// ====== CONTADOR DEL CARRITO ======
$cartCount = 0;

if ($logged) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount($usuario_id);
}
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}

$adminName = $_SESSION['nombre'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Datos personales – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/account-profile.css" />
</head>

<body>
    <!-- Header Admin -->
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
                    <?php echo htmlspecialchars($adminName); ?>
                </span>
            </div>
        </div>
    </header>

    <main class="page">
        <!-- Breadcrumb simple -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <?php if ($isAdmin): ?>
                <a href="panel-admin.php">Admin</a> › <span>Datos personales</span>
            <?php else: ?>
                <a href="mi-cuenta.php">Mi cuenta</a> › <span>Datos personales</span>
            <?php endif; ?>
        </nav>

        <section class="profile-card">
            <h1 class="profile-title">DATOS PERSONALES</h1>

            <!-- Datos -->
            <dl class="profile-data">
                <div class="row">
                    <dt>Nombre</dt>
                    <dd><?php echo htmlspecialchars($nombre); ?></dd>
                </div>

                <div class="row">
                    <dt>Apellido(s)</dt>
                    <dd><?php echo htmlspecialchars($apellidos); ?></dd>
                </div>

                <div class="row">
                    <dt>Correo</dt>
                    <dd><?php echo htmlspecialchars($email); ?></dd>
                </div>

                <div class="row">
                    <dt>Dirección</dt>
                    <dd><?php echo htmlspecialchars($direccion); ?></dd>
                </div>

                <div class="row">
                    <dt>Teléfono</dt>
                    <dd><?php echo htmlspecialchars($telefono); ?></dd>
                </div>
            </dl>

            <div class="profile-actions">
                <a class="btn-edit" href="cuenta-datos-admin-editar.php">Editar</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>

</html>