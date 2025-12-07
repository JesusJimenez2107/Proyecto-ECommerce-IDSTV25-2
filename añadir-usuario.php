<?php
// añadir-usuario.php – Formulario para crear usuarios desde el panel admin

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo admins pueden entrar
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$adminId = (int) $_SESSION['usuario_id'];

// Conexión a la BD
require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$errors = [];
$success = '';
// Valores para mantener en el form si hay errores
$nombre = '';
$apellidos = '';
$correo = '';
$direccion = '';
$telefono = '';
$rol = '';

// ========== PROCESAR FORMULARIO ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';

    // Validaciones básicas
    if ($nombre === '') {
        $errors[] = "El nombre es obligatorio.";
    }
    if ($apellidos === '') {
        $errors[] = "Los apellidos son obligatorios.";
    }

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El correo no es válido.";
    }

    if ($password === '' || strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if ($rol !== 'admin' && $rol !== 'cliente') {
        $errors[] = "Debes seleccionar un rol válido.";
    }

    // Si no hay errores de validación, verificamos correo duplicado e insertamos
    if (empty($errors)) {
        // ¿Correo ya existe?
        $stmt = $conn->prepare("SELECT usuario_id FROM usuario WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $res = $stmt->get_result();
        $existe = $res->fetch_assoc();
        $stmt->close();

        if ($existe) {
            $errors[] = "Ya existe un usuario registrado con ese correo.";
        } else {
            // Insertar usuario
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO usuario (nombre, apellidos, email, password, rol, direccion, telefono)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssssss",
                $nombre,
                $apellidos,
                $correo,
                $hash,
                $rol,
                $direccion,
                $telefono
            );

            if ($stmt->execute()) {
                $success = "Usuario creado correctamente.";
                // Limpiar campos del form después de crear
                $nombre = $apellidos = $correo = $direccion = $telefono = '';
                $rol = '';
            } else {
                $errors[] = "Error al crear el usuario. Inténtalo más tarde.";
            }

            $stmt->close();
        }
    }
}

// ====== CONTADOR DEL CARRITO PARA EL HEADER ======
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if ($logged) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount($adminId);
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir usuario – Panel Admin</title>
    <link rel="stylesheet" href="Assets/styles/global.css">
    <link rel="stylesheet" href="Assets/styles/admin-usuario-form.css">
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

    <main class="page admin-user-form">
        <!-- Breadcrumbs -->
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="panel-admin.php">Panel Administración</a>
            <span>›</span>
            <a href="usuarios.php">Usuarios</a>
            <span>›</span>
            <span>Añadir usuario</span>
        </nav>

        <!-- Card con formulario -->
        <section class="auf-card" aria-labelledby="titulo-add-usuario">
            <h1 id="titulo-add-usuario">Añadir usuario</h1>

            <!-- Mensajes -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li>
                                <?php echo htmlspecialchars($e); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="auf-form" action="añadir-usuario.php" method="post">
                <div class="auf-field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" type="text" required
                        value="<?php echo htmlspecialchars($nombre); ?>">
                </div>

                <div class="auf-field">
                    <label for="apellidos">Apellido(s)</label>
                    <input id="apellidos" name="apellidos" type="text" required
                        value="<?php echo htmlspecialchars($apellidos); ?>">
                </div>

                <div class="auf-field">
                    <label for="correo">Correo</label>
                    <input id="correo" name="correo" type="email" required
                        value="<?php echo htmlspecialchars($correo); ?>">
                </div>

                <div class="auf-field">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div class="auf-field">
                    <label for="direccion">Dirección</label>
                    <input id="direccion" name="direccion" type="text"
                        value="<?php echo htmlspecialchars($direccion); ?>">
                </div>

                <div class="auf-field">
                    <label for="telefono">Teléfono</label>
                    <input id="telefono" name="telefono" type="tel" value="<?php echo htmlspecialchars($telefono); ?>">
                </div>

                <div class="auf-field">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="" disabled <?php echo ($rol === '' ? 'selected' : ''); ?>>Selecciona uno</option>
                        <option value="admin" <?php echo ($rol === 'admin' ? 'selected' : ''); ?>>Administrador</option>
                        <option value="cliente" <?php echo ($rol === 'cliente' ? 'selected' : ''); ?>>Comprador /
                            Vendedor</option>
                    </select>
                </div>

                <div class="auf-actions">
                    <button type="button" class="btn-cancelar" onclick="window.location.href='usuarios.php'">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-confirmar">Confirmar</button>
                </div>
            </form>
        </section>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>

</html>