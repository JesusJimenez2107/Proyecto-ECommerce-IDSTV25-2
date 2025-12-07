<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo admins pueden entrar
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$adminId = (int) $_SESSION['usuario_id'];

// Contador carrito para header
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if ($logged) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount($adminId);
}

// Validar ID producto
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: productos-admin.php");
    exit();
}
$producto_id = (int) $_GET['id'];

// Obtener producto desde el controller
require_once "app/controllers/productController.php";
$pc = new ProductController();
$producto = $pc->getProductByIdAdmin($producto_id);

if (!$producto) {
    header("Location: productos-admin.php");
    exit();
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Raíz Viva – Editar producto (Admin)</title>
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
        <nav class="breadcrumb" aria-label="Ruta">
            <a href="panel-admin.php">Panel Administración</a>
            <span class="sep">›</span>
            <a href="productos-admin.php">Productos</a>
            <span class="sep">›</span>
            <span>Editar producto</span>
        </nav>

        <section class="prod-form-card" aria-labelledby="pf-title">
            <h1 id="pf-title" class="pf-title">Editar producto</h1>

            <form class="pf" action="app/controllers/productController.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="context" value="admin">
                <input type="hidden" name="producto_id" value="<?php echo (int) $producto['producto_id']; ?>">

                <!-- FOTOS -->
                <fieldset class="pf-photos">
                    <legend>Máximo 3 fotos</legend>

                    <!-- Principal -->
                    <div class="pf-photo">
                        <span class="pf-photo__label">Principal</span>
                        <div class="pf-photo__frame" <?php if (!empty($producto['imagen'])): ?> style="background-image: url('<?php echo htmlspecialchars($producto['imagen']); ?>');
                                       background-size: cover;
                                       background-position: center;" <?php endif; ?>>
                            <input id="photo_main" name="photo_main" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop" <?php if (!empty($producto['imagen'])): ?>style="display:none" <?php endif; ?>>
                                Subir / Reemplazar
                            </label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" <?php echo empty($producto['imagen']) ? 'disabled' : ''; ?>>
                            Eliminar
                        </button>
                    </div>

                    <!-- Extra 1 -->
                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 1</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra1" name="photo_extra1" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_extra1" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>

                    <!-- Extra 2 -->
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

                <!-- CAMPOS -->
                <fieldset class="pf-fields">
                    <div class="pf-field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" required
                            value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    </div>

                    <div class="pf-field">
                        <label for="category">Categoría</label>
                        <select id="category" name="category" required>
                            <option value="" hidden>Selecciona una categoría</option>
                            <option value="1" <?php echo $producto['categoria_categoria_id'] == 1 ? 'selected' : ''; ?>>
                                Plantas de interior</option>
                            <option value="2" <?php echo $producto['categoria_categoria_id'] == 2 ? 'selected' : ''; ?>>
                                Plantas de exterior</option>
                            <option value="3" <?php echo $producto['categoria_categoria_id'] == 3 ? 'selected' : ''; ?>>
                                Bajo mantenimiento</option>
                            <option value="4" <?php echo $producto['categoria_categoria_id'] == 4 ? 'selected' : ''; ?>>
                                Aromáticas y comestibles</option>
                            <option value="5" <?php echo $producto['categoria_categoria_id'] == 5 ? 'selected' : ''; ?>>
                                Macetas y accesorios</option>
                            <option value="6" <?php echo $producto['categoria_categoria_id'] == 6 ? 'selected' : ''; ?>>
                                Cuidados y bienestar</option>
                        </select>
                    </div>

                    <div class="pf-field pf-price">
                        <label for="price">Precio</label>
                        <div class="pf-money">
                            <span>$</span>
                            <input id="price" name="price" type="number" step="0.01" min="0" required
                                value="<?php echo htmlspecialchars($producto['precio']); ?>">
                        </div>
                        <small class="pf-hint">Precio incluye IVA</small>
                    </div>

                    <div class="pf-field pf-stock">
                        <label for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0" step="1" required
                            value="<?php echo htmlspecialchars($producto['stock']); ?>">
                        <small class="pf-hint">Disponibles</small>
                    </div>

                    <div class="pf-field pf-desc">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="6"
                            placeholder="Describe el producto…"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                </fieldset>

                <!-- ACCIONES -->
                <div class="pf-actions">
                    <a class="btn-outline"
                        href="datos-producto-admin.php?id=<?php echo (int) $producto['producto_id']; ?>">
                        Cancelar
                    </a>
                    <button class="btn-primary" type="submit">Guardar cambios</button>
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