<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

require_once __DIR__ . "/app/config/connectionController.php";

$usuario_id = (int) $_SESSION['usuario_id'];

$conn = (new ConnectionController())->connect();
$stmt = $conn->prepare("SELECT rol FROM usuario WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resUsuario = $stmt->get_result()->fetch_assoc();

$rolUsuario = strtolower(trim($resUsuario['rol'] ?? 'cliente'));
$esVendedor = ($rolUsuario === 'vendedor' || $rolUsuario === 'admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi cuenta – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/account.css" />
</head>
<body>
    <?php require_once "app/views/layouts/header.php"; ?>

    <main class="page">
        <h1 class="account-title">MI CUENTA</h1>

        <section class="account-grid" aria-label="Accesos de cuenta">
            <a class="tile" href="cuenta-datos.php">
                <img class="tile-icon" src="Assets/icons/id-card.svg" alt="" aria-hidden="true">
                <span class="tile-title">Datos personales</span>
            </a>

            <a class="tile" href="mis-compras.php">
                <img class="tile-icon" src="Assets/icons/bag.svg" alt="" aria-hidden="true">
                <span class="tile-title">Mis compras</span>
            </a>

            <?php if ($esVendedor): ?>
                <a class="tile" href="mis-productos.php">
                    <img class="tile-icon" src="Assets/icons/boxes.svg" alt="" aria-hidden="true">
                    <span class="tile-title">Mis productos</span>
                </a>
                
                <a class="tile" href="dashboard.php">
                    <img class="tile-icon" src="Assets/icons/report.svg" alt="" aria-hidden="true">
                    <span class="tile-title">Dashboard</span>
                </a>
            <?php else: ?>
                <a class="tile" href="alta-vendedor.php">
                    <svg class="tile-icon" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <span class="tile-title">Dar de alta como vendedor</span>
                </a>
            <?php endif; ?>

            <a class="tile" href="logout.php" id="logout-link">
                <img class="tile-icon" src="Assets/icons/logout.svg" alt="" aria-hidden="true">
                <span class="tile-title">Cerrar sesión</span>
            </a>

            <a class="tile tile-danger" href="eliminar_cuenta.php" id="delete-account-link">
                <img class="tile-icon" src="Assets/icons/delete-account.svg" alt="" aria-hidden="true">
                <span class="tile-title">Eliminar cuenta</span>
            </a>
        </section>
    </main>

    <form id="deleteAccountForm" action="eliminar_cuenta.php" method="POST" style="display:none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="deleteAccount">
    </form>

    <div class="modal-backdrop" id="confirmLogoutModal" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">¿Cerrar sesión?</h2>
            <p class="modal-text">Estás a punto de cerrar sesión en Raíz Viva. ¿Deseas continuar?</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="cancelLogout">Cancelar</button>
                <button type="button" class="btn-danger" id="confirmLogout">Cerrar sesión</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="deleteAccountModal" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">Eliminar cuenta</h2>
            <p class="modal-text">Esta acción eliminará tu cuenta y los datos asociados. Esta operación no se puede deshacer. ¿Seguro que deseas continuar?</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="cancelDelete">Cancelar</button>
                <button type="button" class="btn-danger" id="confirmDelete">Eliminar cuenta</button>
            </div>
        </div>
    </div>

    <?php require_once "app/views/layouts/footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logoutLink = document.getElementById('logout-link');
            const modalLogout = document.getElementById('confirmLogoutModal');
            const btnCancel = document.getElementById('cancelLogout');
            const btnConfirm = document.getElementById('confirmLogout');

            if (logoutLink && modalLogout && btnCancel && btnConfirm) {
                logoutLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    modalLogout.removeAttribute('hidden');
                });
                btnCancel.addEventListener('click', () => {
                    modalLogout.setAttribute('hidden', 'true');
                });
                btnConfirm.addEventListener('click', () => {
                    window.location.href = 'logout.php';
                });
                modalLogout.addEventListener('click', (e) => {
                    if (e.target === modalLogout) {
                        modalLogout.setAttribute('hidden', 'true');
                    }
                });
            }

            const deleteLink = document.getElementById('delete-account-link');
            const deleteModal = document.getElementById('deleteAccountModal');
            const btnCancelDelete = document.getElementById('cancelDelete');
            const btnConfirmDelete = document.getElementById('confirmDelete');
            const deleteForm = document.getElementById('deleteAccountForm');

            if (deleteLink && deleteModal && btnCancelDelete && btnConfirmDelete && deleteForm) {
                deleteLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    deleteModal.removeAttribute('hidden');
                });
                btnCancelDelete.addEventListener('click', () => {
                    deleteModal.setAttribute('hidden', 'true');
                });
                btnConfirmDelete.addEventListener('click', () => {
                    deleteForm.submit();
                });
                deleteModal.addEventListener('click', (e) => {
                    if (e.target === deleteModal) {
                        deleteModal.setAttribute('hidden', 'true');
                    }
                });
            }
        });
    </script>
</body>
</html>