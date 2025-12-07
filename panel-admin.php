<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// PROTEGER SOLO PARA ADMIN (ajusta la clave de sesión según tu login)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: index.php");
  exit();
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if (isset($_SESSION['usuario_id'])) {
  require_once "app/controllers/cartController.php";
  $cartCtrl = new CartController();
  $cartCount = $cartCtrl->getCartCount((int) $_SESSION['usuario_id']);
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
  <title>Panel Administración – Raíz Viva</title>

  <link rel="stylesheet" href="Assets/styles/global.css" />
  <link rel="stylesheet" href="Assets/styles/account.css" />
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
    <h1 class="account-title">PANEL ADMINISTRACIÓN</h1>

    <!-- Grid de accesos -->
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

  <!-- Modales -->
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

  <!-- Form oculto para eliminar cuenta -->
  <form id="deleteAccountForm" action="eliminar_cuenta.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="deleteAccount">
  </form>

  <footer class="footer">
    <p>© Raíz Viva</p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Confirmar logout
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

      // Confirmar eliminar cuenta
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