<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Solo admins
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header("Location: index.php");
  exit();
}

// Validar ID recibido
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("Location: usuarios.php");
  exit();
}

$usuarioId = (int) $_GET['id'];

// Conexión BD
require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$errores = [];
$exito = "";

// =========================
//      CARGAR DATOS
// =========================
$stmt = $conn->prepare(
  "SELECT usuario_id, nombre, apellidos, email, direccion, telefono 
     FROM usuario 
     WHERE usuario_id = ?"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
  header("Location: usuarios.php");
  exit();
}

// Valores originales (tal como están en la BD)
$nombre = $user['nombre'];
$apellidos = $user['apellidos'];
$email = $user['email'];
$direccion = $user['direccion'];
$telefono = $user['telefono'];
$emailOriginal = $user['email'];   // <- IMPORTANTE, guardamos el correo original

// =========================
//      PROCESAR UPDATE
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nombre = trim($_POST['nombre']);
  $apellidos = trim($_POST['apellidos']);
  $email = trim($_POST['correo']);
  $direccion = trim($_POST['direccion']);
  $telefono = trim($_POST['telefono']);

  // Validaciones básicas
  if ($nombre === "")
    $errores[] = "El nombre es obligatorio.";
  if ($apellidos === "")
    $errores[] = "Los apellidos son obligatorios.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errores[] = "El correo no es válido.";
  if ($direccion === "")
    $errores[] = "La dirección es obligatoria.";
  if ($telefono === "")
    $errores[] = "El teléfono es obligatorio.";

  // ✅ SOLO verificamos correo duplicado si el correo cambió
  if ($email !== $emailOriginal && empty($errores)) {
    $stmt = $conn->prepare(
      "SELECT usuario_id FROM usuario 
             WHERE email = ? AND usuario_id <> ? LIMIT 1"
    );
    $stmt->bind_param("si", $email, $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->fetch_assoc()) {
      $errores[] = "Ya existe un usuario con ese correo.";
    }
    $stmt->close();
  }

  // Si no hay errores, actualizar
  if (empty($errores)) {
    $stmtUp = $conn->prepare(
      "UPDATE usuario 
             SET nombre=?, apellidos=?, email=?, direccion=?, telefono=?
             WHERE usuario_id=?"
    );
    $stmtUp->bind_param(
      "sssssi",
      $nombre,
      $apellidos,
      $email,
      $direccion,
      $telefono,
      $usuarioId
    );

    if ($stmtUp->execute()) {
      $exito = "Datos actualizados correctamente.";
      // Actualizamos el correo original por si vuelves a enviar el formulario
      $emailOriginal = $email;
    } else {
      $errores[] = "Error al actualizar la información.";
    }

    $stmtUp->close();
  }
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Usuario – Admin</title>

  <link rel="stylesheet" href="Assets/styles/global.css">
  <link rel="stylesheet" href="Assets/styles/account-edit.css">
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


  <!-- CONTENIDO -->
  <main class="page">
    <nav class="breadcrumb">
      <a href="panel-admin.php">Panel Administración</a> ›
      <a href="usuarios.php">Usuarios</a> ›
      <a href="datos-usuario-admin.php?id=<?php echo $usuarioId; ?>">Datos Usuario</a> ›
      <span>Editar</span>
    </nav>

    <section class="profile-card">
      <h1 class="profile-title">EDITAR DATOS DEL USUARIO</h1>

      <!-- Mensajes -->
      <?php if (!empty($exito)): ?>
        <div class="alert alert-success"><?php echo $exito; ?></div>
      <?php endif; ?>

      <?php if (!empty($errores)): ?>
        <div class="alert alert-error">
          <ul>
            <?php foreach ($errores as $e): ?>
              <li><?php echo $e; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="profile-form" method="POST">

        <label class="pf-label">Nombre</label>
        <input class="pf-input" type="text" id="nombre" name="nombre" required
          value="<?php echo htmlspecialchars($nombre); ?>">

        <label class="pf-label">Apellido(s)</label>
        <input class="pf-input" type="text" id="apellidos" name="apellidos" required
          value="<?php echo htmlspecialchars($apellidos); ?>">

        <label class="pf-label">Correo</label>
        <input class="pf-input" type="email" id="correo" name="correo" required
          value="<?php echo htmlspecialchars($email); ?>">

        <label class="pf-label">Dirección</label>
        <input class="pf-input" type="text" id="direccion" name="direccion" required
          value="<?php echo htmlspecialchars($direccion); ?>">

        <label class="pf-label">Teléfono</label>
        <input class="pf-input" type="text" id="telefono" name="telefono" required
          value="<?php echo htmlspecialchars($telefono); ?>">

        <div class="pf-actions">
          <a class="btn-cancel" href="datos-usuario-admin.php?id=<?php echo $usuarioId; ?>">Cancelar</a>
          <button class="btn-confirm" type="submit">Guardar cambios</button>
        </div>

      </form>
    </section>
  </main>

  <footer class="footer">
    <p>© Raíz Viva</p>
  </footer>
  <script src="Assets/js/validaciones.js"></script>
</body>

</html>