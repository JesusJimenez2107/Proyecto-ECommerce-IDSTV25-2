<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Si no está logueado, lo mandamos a login
if (!isset($_SESSION['usuario_id'])) {
  header("Location: login.php?error=login_required");
  exit;
}

require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$usuario_id = (int) $_SESSION['usuario_id'];

// =============================
// 1) SI VIENE POR POST → ACTUALIZAR
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $apellidos = trim($_POST['apellidos'] ?? '');
  $email = trim($_POST['correo'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');

  if ($nombre !== '' && $apellidos !== '' && $email !== '') {
    $update = "UPDATE usuario 
                   SET nombre = ?, apellidos = ?, email = ?, direccion = ?, telefono = ?
                   WHERE usuario_id = ?";
    $stmtUp = $conn->prepare($update);
    $stmtUp->bind_param(
      "sssssi",
      $nombre,
      $apellidos,
      $email,
      $direccion,
      $telefono,
      $usuario_id
    );
    $stmtUp->execute();

    $_SESSION['email'] = $email;

    header("Location: cuenta-datos.php?msg=updated");
    exit;
  }
}

// =============================
// 2) SI VIENE POR GET → CARGAR DATOS
// =============================
$query = "SELECT nombre, apellidos, email, direccion, telefono 
          FROM usuario
          WHERE usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$nombre = $user['nombre'] ?? '';
$apellidos = $user['apellidos'] ?? '';
$email = $user['email'] ?? '';
$direccion = $user['direccion'] ?? '';
$telefono = $user['telefono'] ?? '';

// Para header
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);

// Contador carrito
$cartCount = 0;
if ($logged) {
  require_once "app/controllers/cartController.php";
  $cartCtrl = new CartController();
  $cartCount = $cartCtrl->getCartCount($usuario_id);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar datos – Raíz Viva</title>

  <link rel="stylesheet" href="Assets/styles/global.css" />
  <link rel="stylesheet" href="Assets/styles/account-edit.css" />
</head>

<body>
  <!-- Topbar global -->
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
          <a class="nav-menu__item" href="productos.php?cat=1">Plantas de interior</a>
          <a class="nav-menu__item" href="productos.php?cat=2">Plantas de exterior</a>
          <a class="nav-menu__item" href="productos.php?cat=3">Bajo mantenimiento</a>
          <a class="nav-menu__item" href="productos.php?cat=4">Aromáticas y comestibles</a>
          <a class="nav-menu__item" href="productos.php?cat=5">Macetas y accesorios</a>
          <a class="nav-menu__item" href="productos.php?cat=6">Cuidados y bienestar</a>
        </nav>
      </div>

      <form class="search" role="search">
        <input type="search" placeholder="Buscar productos" aria-label="Buscar productos" />
        <button type="submit" aria-label="Buscar">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
          </svg>
        </button>
      </form>

      <div class="actions">
        <?php if ($logged): ?>
          <a href="mi-cuenta.php" class="action">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
              <path d="M20 21a8 8 0 1 0-16 0" />
              <circle cx="12" cy="7" r="4" />
            </svg>
            <span>Mi cuenta</span>
          </a>
        <?php else: ?>
          <a href="login.php" class="action">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
              <path d="M20 21a8 8 0 1 0-16 0" />
              <circle cx="12" cy="7" r="4" />
            </svg>
            <span>Ingresar</span>
          </a>
        <?php endif; ?>

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
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="mi-cuenta.php">Mi Cuenta</a> ›
      <a href="cuenta-datos.php">Datos Personales</a> ›
      <span>Editar Datos</span>
    </nav>

    <section class="profile-card">
      <h1 class="profile-title">DATOS PERSONALES</h1>

      <!-- Formulario editable -->
      <form class="profile-form" action="cuenta-datos-editar.php" method="post" novalidate id="profile-form">
        <label class="pf-label" for="nombre">Nombre</label>
        <input class="pf-input" type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>"
          required minlength="2" maxlength="60" />

        <label class="pf-label" for="apellidos">Apellido(s)</label>
        <input class="pf-input" type="text" id="apellidos" name="apellidos"
          value="<?php echo htmlspecialchars($apellidos); ?>" required minlength="2" maxlength="80" />

        <label class="pf-label" for="correo">Correo</label>
        <input class="pf-input" type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($email); ?>"
          required />

        <label class="pf-label" for="direccion">Dirección</label>
        <input class="pf-input" type="text" id="direccion" name="direccion"
          value="<?php echo htmlspecialchars($direccion); ?>" maxlength="120" />

        <label class="pf-label" for="telefono">Teléfono</label>
        <input class="pf-input" type="tel" id="telefono" name="telefono"
          value="<?php echo htmlspecialchars($telefono); ?>" inputmode="numeric" pattern="[0-9\s+-]{8,15}"
          placeholder="Ej. 612 123 4567" />

        <div class="pf-actions">
          <a class="btn-cancel" href="cuenta-datos.php">Cancelar</a>
          <!-- IMPORTANTE: type="button" para NO enviar aún -->
          <button class="btn-confirm" type="button" id="open-confirm-modal">
            Confirmar
          </button>
        </div>
      </form>
    </section>
  </main>

  <!-- MODAL DE CONFIRMACIÓN -->
  <div class="modal-overlay" id="modal-editar">
    <div class="modal-box">
      <h2>Confirmar cambios</h2>
      <p>¿Deseas guardar los cambios en tus datos personales?</p>

      <div class="modal-actions">
        <button type="button" id="btn-close-modal" class="modal-btn-cancel">
          Cancelar
        </button>
        <button type="button" id="btn-confirmar-modal" class="modal-btn-confirm">
          Sí, guardar cambios
        </button>
      </div>
    </div>
  </div>
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


  <footer class="footer">
    <p>© Raíz Viva</p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const openBtn = document.getElementById('open-confirm-modal');
      const overlay = document.getElementById('edit-confirm-modal');
      const cancelBtn = document.getElementById('edit-cancel-btn');
      const confirmBtn = document.getElementById('edit-confirm-btn');
      const form = document.getElementById('profile-form');

      function openModal() {
        document.body.classList.add('modal-open');
        overlay.classList.add('is-visible');
      }

      function closeModal() {
        overlay.classList.remove('is-visible');
        document.body.classList.remove('modal-open');
      }

      openBtn.addEventListener('click', () => {
        openModal();
      });

      cancelBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal();
      });

      // Cerrar al hacer click fuera de la caja
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          closeModal();
        }
      });

      confirmBtn.addEventListener('click', () => {
        closeModal();
        form.submit();
      });
    });
  </script>

  <script src="Assets/js/validaciones.js"></script>
</body>

</html>