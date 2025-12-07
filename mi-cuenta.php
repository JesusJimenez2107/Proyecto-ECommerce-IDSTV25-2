<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if (isset($_SESSION['usuario_id'])) {
  require_once "app/controllers/cartController.php";
  $cartCtrl = new CartController();
  $cartCount = $cartCtrl->getCartCount((int) $_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mi cuenta – Raíz Viva</title>

  <link rel="stylesheet" href="Assets/styles/global.css" />
  <link rel="stylesheet" href="Assets/styles/account.css" />
</head>

<body>
  <!-- Header global -->
  <header class="topbar">
    <div class="topbar__inner">
      <a class="brand" href="index.php"><img src="Assets/img/logo.png" alt="Raíz Viva" /></a>

      <div class="nav-dropdown">
        <button class="nav-dropbtn">Productos
          <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
            <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </button>
        <nav class="nav-menu" hidden>
          <a class="nav-menu__item" href="/productos?cat=interior">Plantas de interior</a>
          <a class="nav-menu__item" href="/productos?cat=exterior">Plantas de exterior</a>
          <a class="nav-menu__item" href="/productos?cat=bajo-mantenimiento">Bajo mantenimiento</a>
          <a class="nav-menu__item" href="/productos?cat=aromaticas-comestibles">Aromáticas y comestibles</a>
          <a class="nav-menu__item" href="/productos?cat=macetas-accesorios">Macetas y accesorios</a>
          <a class="nav-menu__item" href="/productos?cat=cuidados-bienestar">Cuidados y bienestar</a>
        </nav>
      </div>

      <form class="search" role="search">
        <input type="search" placeholder="Buscar" aria-label="Buscar productos" />
        <button type="submit" aria-label="Buscar">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
        </button>
      </form>

      <div class="actions">
        <a href="#" class="action">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
            <path d="M20 21a8 8 0 1 0-16 0" />
            <circle cx="12" cy="7" r="4" />
          </svg>
          <span>Mi cuenta</span>
        </a>
        <a href="carrito.php" class="action">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
            <circle cx="10" cy="20" r="1" />
            <circle cx="18" cy="20" r="1" />
            <path d="M2 2h3l2.2 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 6H6" />
          </svg>
          <span><?php echo $cartCount; ?></span>
        </a>
      </div>
    </div>
  </header>

  <main class="page">
    <h1 class="account-title">MI CUENTA</h1>

    <!-- Grid de accesos -->
    <section class="account-grid" aria-label="Accesos de cuenta">
      <a class="tile" href="cuenta-datos.php">
        <img class="tile-icon" src="Assets/icons/id-card.svg" alt="" aria-hidden="true">
        <span class="tile-title">Datos personales</span>
      </a>

      <a class="tile" href="mis-productos.php">
        <img class="tile-icon" src="Assets/icons/boxes.svg" alt="" aria-hidden="true">
        <span class="tile-title">Mis productos</span>
      </a>

      <a class="tile" href="mis-compras.php">
        <img class="tile-icon" src="Assets/icons/bag.svg" alt="" aria-hidden="true">
        <span class="tile-title">Mis compras</span>
      </a>

      <!-- Cerrar sesión -->
      <a class="tile" href="logout.php" id="logout-link">
        <img class="tile-icon" src="Assets/icons/logout.svg" alt="" aria-hidden="true">
        <span class="tile-title">Cerrar sesión</span>
      </a>

      <!-- Eliminar cuenta -->
      <a class="tile tile-danger" href="eliminar_cuenta.php" id="delete-account-link">
        <img class="tile-icon" src="Assets/icons/delete-account.svg" alt="" aria-hidden="true">
        <span class="tile-title">Eliminar cuenta</span>
      </a>

      <a class="tile" href="reportes.php">
        <img class="tile-icon" src="Assets/icons/report.svg" alt="" aria-hidden="true">
        <span class="tile-title">Reportes</span>
      </a>
    </section>
  </main>

  <!-- FORM OCULTO PARA ELIMINAR CUENTA POR POST -->
  <form id="deleteAccountForm" action="eliminar_cuenta.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="deleteAccount">
  </form>

  <!-- MODAL CERRAR SESIÓN -->
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

  <!-- MODAL ELIMINAR CUENTA -->
  <div class="modal-backdrop" id="deleteAccountModal" hidden>
    <div class="modal-dialog">
      <h2 class="modal-title">Eliminar cuenta</h2>
      <p class="modal-text">
        Esta acción eliminará tu cuenta y los datos asociados.
        Esta operación no se puede deshacer. ¿Seguro que deseas continuar?
      </p>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" id="cancelDelete">Cancelar</button>
        <button type="button" class="btn-danger" id="confirmDelete">Eliminar cuenta</button>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>© Raíz Viva</p>
  </footer>

  <!-- SCRIPT DEL MODAL -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // ====== LOGOUT ======
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

      // ====== ELIMINAR CUENTA ======
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