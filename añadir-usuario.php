<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$errors = [];
$success = '';
$nombre = '';
$apellidos = '';
$correo = '';
$direccion = '';
$telefono = '';
$rol = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Error de seguridad: Token CSRF inválido.");
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';

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

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT usuario_id FROM usuario WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existe) {
            $errors[] = "Ya existe un usuario registrado con ese correo.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO usuario (nombre, apellidos, email, password, rol, direccion, telefono)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssssss", $nombre, $apellidos, $correo, $hash, $rol, $direccion, $telefono);

            if ($stmt->execute()) {
                $success = "Usuario creado correctamente.";
                $nombre = $apellidos = $correo = $direccion = $telefono = $rol = '';
            } else {
                $errors[] = "Error al crear el usuario. Inténtalo más tarde.";
            }
            $stmt->close();
        }
    }
}
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
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page admin-user-form">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="panel-admin.php">Panel Administración</a>
            <span>›</span>
            <a href="usuarios.php">Usuarios</a>
            <span>›</span>
            <span>Añadir usuario</span>
        </nav>

        <section class="auf-card" aria-labelledby="titulo-add-usuario">
            <h1 id="titulo-add-usuario">Añadir usuario</h1>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="auf-form" action="añadir-usuario.php" method="post">
                <?php echo csrfField(); ?>

                <div class="auf-field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" type="text" required
                        value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="auf-field">
                    <label for="apellidos">Apellido(s)</label>
                    <input id="apellidos" name="apellidos" type="text" required
                        value="<?php echo htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="auf-field">
                    <label for="correo">Correo</label>
                    <input id="correo" name="correo" type="email" required
                        value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="auf-field">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div class="auf-field">
                    <label for="direccion">Dirección</label>
                    <input id="direccion" name="direccion" type="text"
                        value="<?php echo htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="auf-field">
                    <label for="telefono">Teléfono</label>
                    <input id="telefono" name="telefono" type="tel"
                        value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="auf-field">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="" disabled <?php echo ($rol === '' ? 'selected' : ''); ?>>Selecciona uno</option>
                        <option value="admin" <?php echo ($rol === 'admin' ? 'selected' : ''); ?>>Administrador</option>
                        <option value="cliente" <?php echo ($rol === 'cliente' ? 'selected' : ''); ?>>Comprador / Vendedor</option>
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

    <?php require_once "app/views/layouts/footer.php"; ?>
    <script src="Assets/js/validaciones.js"></script>
</body>

</html>