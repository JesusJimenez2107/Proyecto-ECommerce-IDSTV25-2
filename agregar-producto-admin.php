<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== SOLO ADMINS ===== */
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if ($logged) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount((int) $_SESSION['usuario_id']);
}

/* ===== OPCIONAL: CARGAR CATEGORÍAS DESDE BD ===== */
require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$categorias = [];
$resCat = $conn->query("SELECT categoria_id, nombre FROM categoria ORDER BY nombre ASC");
while ($row = $resCat->fetch_assoc()) {
    $categorias[] = $row;
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Admin – Agregar producto</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/styles/global.css">
    <link rel="stylesheet" href="Assets/styles/product-new.css">
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
        <!-- Breadcrumbs -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Panel Administración</a>
            <span class="sep">›</span>
            <a href="productos-admin.php">Productos</a>
            <span class="sep">›</span>
            <span>Agregar producto</span>
        </nav>

        <section class="prod-form-card" aria-labelledby="pf-title">
            <h1 id="pf-title" class="pf-title">Agregar producto</h1>

            <!-- IMPORTANTE: mantenemos la misma acción y los mismos names que usas para el cliente -->
            <form action="app/controllers/productController.php" method="post" enctype="multipart/form-data" class="pf">
                <input type="hidden" name="action" value="create_product">

                <!-- FOTOS -->
                <fieldset class="pf-photos">
                    <legend>Máximo 3 fotos</legend>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Principal</span>
                        <div class="pf-photo__frame">
                            <input id="photo_main" name="photo_main" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 1</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra1" name="photo_extra1" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_extra1" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 2</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra2" name="photo_extra2" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_extra2" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>
                </fieldset>

                <!-- DATOS -->
                <fieldset class="pf-fields">
                    <div class="pf-field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" required placeholder="Ej. Monstera Deliciosa">
                    </div>

                    <div class="pf-field">
                        <label for="category">Categoría</label>
                        <select id="category" name="category" required>
                            <option value="" hidden>Selecciona una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo (int) $cat['categoria_id']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pf-field pf-price">
                        <label for="price">Precio</label>
                        <div class="pf-money">
                            <span>$</span>
                            <input id="price" name="price" type="number" min="0" step="0.01" required
                                placeholder="0.00">
                        </div>
                        <small class="pf-hint">Precio incluye IVA</small>
                    </div>

                    <div class="pf-field pf-stock">
                        <label for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0" step="1" required value="0">
                        <small class="pf-hint">Disponibles</small>
                    </div>

                    <div class="pf-field pf-desc">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="6"
                            placeholder="Describe el producto…"></textarea>
                    </div>
                </fieldset>

                <!-- ACCIONES -->
                <div class="pf-actions">
                    <a href="productos-admin.php" class="btn-outline">Cancelar</a>
                    <button type="submit" class="btn-primary">Guardar cambios</button>
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