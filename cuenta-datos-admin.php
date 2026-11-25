<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$usuario_id = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT nombre, apellidos, email, direccion, telefono FROM usuario WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$nombre = $user['nombre'] ?? '';
$apellidos = $user['apellidos'] ?? '';
$email = $user['email'] ?? '';
$direccion = $user['direccion'] ?? '';
$telefono = $user['telefono'] ?? '';
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
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Admin</a> › <span>Datos personales</span>
        </nav>

        <section class="profile-card">
            <h1 class="profile-title">DATOS PERSONALES</h1>

            <dl class="profile-data">
                <div class="row">
                    <dt>Nombre</dt>
                    <dd><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>

                <div class="row">
                    <dt>Apellido(s)</dt>
                    <dd><?php echo htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>

                <div class="row">
                    <dt>Correo</dt>
                    <dd><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>

                <div class="row">
                    <dt>Dirección</dt>
                    <dd><?php echo htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>

                <div class="row">
                    <dt>Teléfono</dt>
                    <dd><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>

            <div class="profile-actions">
                <a class="btn-edit" href="cuenta-datos-admin-editar.php">Editar</a>
            </div>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
</body>

</html>