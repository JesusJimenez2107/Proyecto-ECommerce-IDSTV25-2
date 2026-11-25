<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$usuario_id = (int) $_SESSION['usuario_id'];

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

    if ($nombre !== '' && $apellidos !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update = "UPDATE usuario 
                   SET nombre = ?, apellidos = ?, email = ?, direccion = ?, telefono = ?
                   WHERE usuario_id = ?";
        $stmtUp = $conn->prepare($update);
        $stmtUp->bind_param("sssssi", $nombre, $apellidos, $email, $direccion, $telefono, $usuario_id);
        $stmtUp->execute();

        $_SESSION['email'] = $email;
        $_SESSION['nombre'] = $nombre;

        header("Location: cuenta-datos-admin.php?msg=updated");
        exit();
    }
}

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
    <title>Editar datos Admin – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/account-edit.css" />
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Admin</a> ›
            <a href="cuenta-datos-admin.php">Datos Personales</a> ›
            <span>Editar Datos</span>
        </nav>

        <section class="profile-card">
            <h1 class="profile-title">DATOS PERSONALES</h1>

            <form class="profile-form" action="cuenta-datos-admin-editar.php" method="post" novalidate id="profile-form">
                <?php echo csrfField(); ?>

                <label class="pf-label" for="nombre">Nombre</label>
                <input class="pf-input" type="text" id="nombre" name="nombre"
                    value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
                    required minlength="2" maxlength="60" />

                <label class="pf-label" for="apellidos">Apellido(s)</label>
                <input class="pf-input" type="text" id="apellidos" name="apellidos"
                    value="<?php echo htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8'); ?>"
                    required minlength="2" maxlength="80" />

                <label class="pf-label" for="correo">Correo</label>
                <input class="pf-input" type="email" id="correo" name="correo"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    required />

                <label class="pf-label" for="direccion">Dirección</label>
                <input class="pf-input" type="text" id="direccion" name="direccion"
                    value="<?php echo htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="120" />

                <label class="pf-label" for="telefono">Teléfono</label>
                <input class="pf-input" type="tel" id="telefono" name="telefono"
                    value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>"
                    inputmode="numeric" pattern="[0-9\s+-]{8,15}" placeholder="Ej. 612 123 4567" />

                <div class="pf-actions">
                    <a class="btn-cancel" href="cuenta-datos-admin.php">Cancelar</a>
                    <button class="btn-confirm" type="button" id="open-confirm-modal">
                        Confirmar
                    </button>
                </div>
            </form>
        </section>
    </main>

    <div class="modal-overlay" id="modal-editar">
        <div class="modal-box">
            <h2>Confirmar cambios</h2>
            <p>¿Deseas guardar los cambios en tus datos personales?</p>
            <div class="modal-actions">
                <button type="button" id="btn-close-modal" class="modal-btn-cancel">Cancelar</button>
                <button type="button" id="btn-confirmar-modal" class="modal-btn-confirm">Sí, guardar cambios</button>
            </div>
        </div>
    </div>

    <?php require_once "app/views/layouts/footer.php"; ?>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modal = document.getElementById("modal-editar");
            const openBtn = document.getElementById("open-confirm-modal");
            const closeBtn = document.getElementById("btn-close-modal");
            const confirmBtn = document.getElementById("btn-confirmar-modal");
            const form = document.getElementById("profile-form");

            openBtn.addEventListener("click", () => {
                modal.classList.add("is-visible");
                document.body.classList.add("modal-open");
            });

            closeBtn.addEventListener("click", () => {
                modal.classList.remove("is-visible");
                document.body.classList.remove("modal-open");
            });

            modal.addEventListener("click", (e) => {
                if (e.target === modal) {
                    modal.classList.remove("is-visible");
                    document.body.classList.remove("modal-open");
                }
            });

            confirmBtn.addEventListener("click", () => {
                form.submit();
            });
        });
    </script>
    <script src="Assets/js/validaciones.js"></script>
</body>

</html>