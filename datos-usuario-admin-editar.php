<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$usuarioId = (int) $_GET['id'];

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$errores = [];
$exito = "";

$stmt = $conn->prepare(
    "SELECT usuario_id, nombre, apellidos, email, direccion, telefono 
     FROM usuario 
     WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: usuarios.php");
    exit();
}

$nombre = $user['nombre'];
$apellidos = $user['apellidos'];
$email = $user['email'];
$direccion = $user['direccion'];
$telefono = $user['telefono'];
$emailOriginal = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Error de seguridad: Token CSRF inválido.");
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === "") {
        $errores[] = "El nombre es obligatorio.";
    }
    if ($apellidos === "") {
        $errores[] = "Los apellidos son obligatorios.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no es válido.";
    }
    if ($direccion === "") {
        $errores[] = "La dirección es obligatoria.";
    }
    if ($telefono === "") {
        $errores[] = "El teléfono es obligatorio.";
    }

    if ($email !== $emailOriginal && empty($errores)) {
        $stmt = $conn->prepare(
            "SELECT usuario_id FROM usuario 
             WHERE email = ? AND usuario_id <> ? LIMIT 1"
        );
        $stmt->bind_param("si", $email, $usuarioId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errores[] = "Ya existe un usuario con ese correo.";
        }
        $stmt->close();
    }

    if (empty($errores)) {
        $stmtUp = $conn->prepare(
            "UPDATE usuario 
             SET nombre=?, apellidos=?, email=?, direccion=?, telefono=?
             WHERE usuario_id=?"
        );
        $stmtUp->bind_param("sssssi", $nombre, $apellidos, $email, $direccion, $telefono, $usuarioId);

        if ($stmtUp->execute()) {
            $exito = "Datos actualizados correctamente.";
            $emailOriginal = $email;
        } else {
            $errores[] = "Error al actualizar la información.";
        }
        $stmtUp->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario – Admin</title>
    <link rel="stylesheet" href="Assets/styles/global.css">
    <link rel="stylesheet" href="Assets/styles/account-edit.css">
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb">
            <a href="panel-admin.php">Panel Administración</a> ›
            <a href="usuarios.php">Usuarios</a> ›
            <a href="datos-usuario-admin.php?id=<?php echo $usuarioId; ?>">Datos Usuario</a> ›
            <span>Editar</span>
        </nav>

        <section class="profile-card">
            <h1 class="profile-title">EDITAR DATOS DEL USUARIO</h1>

            <?php if (!empty($exito)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($exito, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errores as $e): ?>
                            <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="profile-form" method="POST">
                <?php echo csrfField(); ?>

                <label class="pf-label">Nombre</label>
                <input class="pf-input" type="text" id="nombre" name="nombre" required
                    value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="pf-label">Apellido(s)</label>
                <input class="pf-input" type="text" id="apellidos" name="apellidos" required
                    value="<?php echo htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="pf-label">Correo</label>
                <input class="pf-input" type="email" id="correo" name="correo" required
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="pf-label">Dirección</label>
                <input class="pf-input" type="text" id="direccion" name="direccion" required
                    value="<?php echo htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="pf-label">Teléfono</label>
                <input class="pf-input" type="text" id="telefono" name="telefono" required
                    value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="pf-actions">
                    <a class="btn-cancel" href="datos-usuario-admin.php?id=<?php echo $usuarioId; ?>">Cancelar</a>
                    <button class="btn-confirm" type="submit">Guardar cambios</button>
                </div>
            </form>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
    <script src="Assets/js/validaciones.js"></script>
</body>

</html>