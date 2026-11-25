<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

$adminId = (int) $_SESSION['usuario_id'];

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Error de seguridad: Token CSRF inválido.");
    }

    $usuarioId = isset($_POST['usuario_id']) ? (int) $_POST['usuario_id'] : 0;

    if ($usuarioId <= 0) {
        $error = "Usuario inválido.";
    } elseif ($usuarioId === $adminId) {
        $error = "No puedes eliminar tu propia cuenta desde esta sección.";
    } else {
        $stmt = $conn->prepare("SELECT rol FROM usuario WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = "El usuario no existe.";
        } else {
            if ($row['rol'] === 'admin') {
                $stmt = $conn->prepare("SELECT COUNT(*) AS total_admins FROM usuario WHERE rol = 'admin'");
                $stmt->execute();
                $resAdmins = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ((int) $resAdmins['total_admins'] <= 1) {
                    $error = "No puedes eliminar al único administrador restante.";
                } else {
                    $stmtDel = $conn->prepare("DELETE FROM usuario WHERE usuario_id = ?");
                    $stmtDel->bind_param("i", $usuarioId);
                    $stmtDel->execute();
                    $stmtDel->close();
                    $mensaje = "Usuario eliminado correctamente.";
                }
            } else {
                $stmtDel = $conn->prepare("DELETE FROM usuario WHERE usuario_id = ?");
                $stmtDel->bind_param("i", $usuarioId);
                $stmtDel->execute();
                $stmtDel->close();
                $mensaje = "Usuario eliminado correctamente.";
            }
        }
    }
}

$buscarId = isset($_GET['buscar_id']) ? trim($_GET['buscar_id']) : '';

if ($buscarId !== '' && ctype_digit($buscarId)) {
    $stmt = $conn->prepare("SELECT usuario_id, nombre, apellidos, email FROM usuario WHERE usuario_id = ?");
    $idBuscarInt = (int) $buscarId;
    $stmt->bind_param("i", $idBuscarInt);
    $stmt->execute();
    $usuariosResult = $stmt->get_result();
    $stmt->close();
} else {
    $usuariosResult = $conn->query("SELECT usuario_id, nombre, apellidos, email FROM usuario ORDER BY usuario_id ASC");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel Admin – Usuarios</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/admin-usuarios.css" />
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Panel Administración</a>
            <span>›</span>
            <span>Usuarios</span>
        </nav>

        <header class="au-header">
            <h1>USUARIOS</h1>
        </header>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="au-toolbar" aria-label="Búsqueda de usuarios">
            <form class="au-search-group" method="GET" action="usuarios.php">
                <label for="buscarId" class="au-label">Buscar por ID</label>
                <div class="au-search">
                    <input id="buscarId" name="buscar_id" type="text" placeholder="Ej. 5"
                        value="<?php echo htmlspecialchars($buscarId, ENT_QUOTES, 'UTF-8'); ?>" />
                    <button type="submit" class="btn-primary">Buscar</button>
                </div>
            </form>

            <a href="añadir-usuario.php">
                <button type="button" class="btn-secondary">Añadir usuario</button>
            </a>
        </section>

        <section class="au-table" aria-label="Listado de usuarios">
            <div class="au-row au-row--head">
                <div class="au-col au-col-id">ID</div>
                <div class="au-col au-col-name">Nombre</div>
                <div class="au-col au-col-email">Correo</div>
                <div class="au-col au-col-actions">Información</div>
            </div>

            <?php if ($usuariosResult && $usuariosResult->num_rows > 0): ?>
                <?php while ($u = $usuariosResult->fetch_assoc()): ?>
                    <div class="au-row">
                        <div class="au-col au-col-id"><?php echo (int) $u['usuario_id']; ?></div>
                        <div class="au-col au-col-name">
                            <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellidos'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="au-col au-col-email">
                            <?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="au-col au-col-actions">
                            <button class="icon-btn info" type="button" aria-label="Ver información de usuario"
                                onclick="window.location.href='datos-usuario-admin.php?id=<?php echo (int) $u['usuario_id']; ?>'">
                                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                    <rect x="4" y="4" width="16" height="16" rx="3" fill="currentColor" />
                                    <rect x="7" y="7" width="6" height="2" fill="#FFF8ED" />
                                    <rect x="7" y="11" width="10" height="2" fill="#FFF8ED" />
                                    <rect x="7" y="15" width="8" height="2" fill="#FFF8ED" />
                                </svg>
                            </button>

                            <button type="button" class="icon-btn danger btn-open-delete-user"
                                data-usuario="<?php echo (int) $u['usuario_id']; ?>" aria-label="Eliminar usuario">
                                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                    <path d="M9 4h6l1 2h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M6 6h12l-1 12H7L6 6Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                    <path d="M10 10v6M14 10v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="au-row">
                    <div class="au-col au-col-id">–</div>
                    <div class="au-col au-col-name">No hay usuarios</div>
                    <div class="au-col au-col-email"></div>
                    <div class="au-col au-col-actions"></div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>

    <div class="modal-backdrop" id="modalEliminarUsuario" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">Eliminar usuario</h2>
            <p class="modal-text">
                ¿Estás seguro de eliminar este usuario?<br>
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
            <form id="formEliminarUsuario" method="POST" action="usuarios.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="usuario_id" id="inputUsuarioEliminar">
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelarEliminarUsuario">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modal = document.getElementById("modalEliminarUsuario");
            const inputUsuario = document.getElementById("inputUsuarioEliminar");
            const btnCancelar = document.getElementById("cancelarEliminarUsuario");

            document.querySelectorAll(".btn-open-delete-user").forEach(btn => {
                btn.addEventListener("click", () => {
                    inputUsuario.value = btn.getAttribute("data-usuario");
                    modal.removeAttribute("hidden");
                });
            });

            btnCancelar.addEventListener("click", () => {
                modal.setAttribute("hidden", true);
            });

            modal.addEventListener("click", (e) => {
                if (e.target === modal) {
                    modal.setAttribute("hidden", true);
                }
            });
        });
    </script>
</body>

</html>