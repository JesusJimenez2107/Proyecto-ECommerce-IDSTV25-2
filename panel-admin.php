<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel Administración – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/account.css" />
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <h1 class="account-title">PANEL ADMINISTRACIÓN</h1>

        <section class="account-grid" aria-label="Accesos de panel de administración">
            <a class="tile" href="cuenta-datos-admin.php">
                <img class="tile-icon" src="Assets/icons/id-card.svg" alt="" aria-hidden="true">
                <span class="tile-title">Datos personales</span>
            </a>

            <a class="tile" href="usuarios.php">
                <img class="tile-icon" src="Assets/icons/People.svg" alt="" aria-hidden="true">
                <span class="tile-title">USUARIOS</span>
            </a>

            <a class="tile" href="productos-admin.php">
                <img class="tile-icon" src="Assets/icons/boxes.svg" alt="" aria-hidden="true">
                <span class="tile-title">PRODUCTOS</span>
            </a>

            <a class="tile" href="logout.php" id="logout-link">
                <img class="tile-icon" src="Assets/icons/logout.svg" alt="" aria-hidden="true">
                <span class="tile-title">Cerrar sesión</span>
            </a>

            <a class="tile tile-danger" href="eliminar_cuenta.php" id="delete-account-link">
                <img class="tile-icon" src="Assets/icons/delete-account.svg" alt="" aria-hidden="true">
                <span class="tile-title">Eliminar cuenta</span>
            </a>

            <a class="tile" href="reportes-admin.php">
                <img class="tile-icon" src="Assets/icons/report.svg" alt="" aria-hidden="true">
                <span class="tile-title">Reportes</span>
            </a>
        </section>
    </main>

    <div class="modal-backdrop" id="confirmLogoutModal" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">¿Cerrar sesión?</h2>
            <p class="modal-text">
                Estás a punto de cerrar sesión en Raíz Viva. ¿Deseas continuar?
            </p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="cancelLogout">Cancelar</button>
                <button type="button" class="btn-danger" id="confirmLogout">Cerrar sesión</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="deleteAccountModal" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">Eliminar cuenta</h2>
            <p class="modal-text">
                Esta acción eliminará tu cuenta de administrador y los datos asociados.
                Esta operación no se puede deshacer. ¿Seguro que deseas continuar?
            </p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="cancelDelete">Cancelar</button>
                <button type="button" class="btn-danger" id="confirmDelete">Eliminar cuenta</button>
            </div>
        </div>
    </div>

    <form id="deleteAccountForm" action="eliminar_cuenta.php" method="POST" style="display:none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="deleteAccount">
    </form>

    <?php require_once "app/views/layouts/footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logoutLink = document.getElementById('logout-link');
            const modalLogout = document.getElementById('confirmLogoutModal');
            const btnCancelLogout = document.getElementById('cancelLogout');
            const btnConfirmLogout = document.getElementById('confirmLogout');

            if (logoutLink && modalLogout) {
                logoutLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    modalLogout.removeAttribute('hidden');
                });

                btnCancelLogout.addEventListener('click', () => {
                    modalLogout.setAttribute('hidden', 'true');
                });

                btnConfirmLogout.addEventListener('click', () => {
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

            if (deleteLink && deleteModal && deleteForm) {
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