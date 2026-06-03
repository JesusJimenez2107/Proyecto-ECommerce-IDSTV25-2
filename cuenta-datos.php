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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Datos personales – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/account-profile.css" />
</head>

<body>
    <!-- Topbar global -->
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php">
                <img src="Assets/img/logo.png" alt="Raíz Viva" />
            </a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn" id="btnProductos" aria-haspopup="true" aria-expanded="false"
                    aria-controls="menuProductos">
                    Productos
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>
                </button>

                <!-- Menú de categorías -->
                <nav class="nav-menu" id="menuProductos" role="menu" hidden>
                    <a role="menuitem" href="productos.php?cat=1" class="nav-menu__item">
                        Plantas de interior
                    </a>

                    <a role="menuitem" href="productos.php?cat=2" class="nav-menu__item">
                        Plantas de exterior
                    </a>

                    <a role="menuitem" href="productos.php?cat=3" class="nav-menu__item">
                        Bajo mantenimiento
                    </a>

                    <a role="menuitem" href="productos.php?cat=4" class="nav-menu__item">
                        Aromáticas y comestibles
                    </a>

                    <a role="menuitem" href="productos.php?cat=5" class="nav-menu__item">
                        Macetas y accesorios
                    </a>

                    <a role="menuitem" href="productos.php?cat=6" class="nav-menu__item">
                        Cuidados y bienestar
                    </a>
                </nav>
            </div>

            <form action="productos.php" method="GET" class="search" role="search">
                <input type="search" name="search" placeholder="Buscar" aria-label="Buscar"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </button>
            </form>

            <div class="actions">

                <?php if ($logged): ?>
                    <a href="mi-cuenta.php" class="action">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M20 21a8 8 0 1 0-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>
                            <?php
                            // Si existe el nombre en sesión, extraemos solo el primer nombre
                            if (isset($_SESSION['nombre']) && !empty($_SESSION['nombre'])) {
                                $primerNombre = explode(' ', trim($_SESSION['nombre']))[0];
                                // Ponemos la primera letra en mayúscula
                                echo htmlspecialchars(ucfirst(strtolower($primerNombre)));
                            } else {
                                echo 'Mi cuenta';
                            }
                            ?>
                        </span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="action">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M20 21a8 8 0 1 0-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Ingresar</span>
                    </a>
                <?php endif; ?>


                <a href="carrito.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                        <circle cx="10" cy="20" r="1"></circle>
                        <circle cx="18" cy="20" r="1"></circle>
                        <path d="M2 2h3l2.2 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 6H6"></path>
                    </svg>
                    <span><?php echo $cartCount; ?></span>
                </a>
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
                <a class="btn-edit" href="cuenta-datos-editar.php">Editar</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>

</html>