<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: mis-productos.php");
    exit();
}

$producto_id = (int) $_GET['id'];
$usuario_id = (int) $_SESSION['usuario_id'];

require_once __DIR__ . "/app/controllers/productController.php";
$pc = new ProductController();
// Solo permite cargar productos que pertenezcan al usuario autenticado (Mitigación IDOR)
$producto = $pc->getProductById($producto_id, $usuario_id);

if (!$producto) {
    header("Location: mis-productos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Raíz Viva – Editar producto</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/styles/global.css?v=2">
    <link rel="stylesheet" href="Assets/styles/product-new.css">
</head>

<body>
    <?php require_once "app/views/layouts/header.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="ruta">
            <a href="mi-cuenta.php">Mi Cuenta</a>
            <span class="sep">›</span>
            <a href="mis-productos.php">Mis Productos</a>
            <span class="sep">›</span>
            <span>Editar Producto</span>
        </nav>

        <section class="prod-form-card" aria-labelledby="pf-title">
            <h1 id="pf-title" class="pf-title">Editar producto</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" style="color: #e74c3c; background: #fdecea; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form class="pf" action="app/controllers/productController.php" method="post" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="producto_id" value="<?php echo (int) $producto['producto_id']; ?>">

                <fieldset class="pf-photos">
                    <legend>Máximo 3 fotos</legend>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Principal</span>
                        <div class="pf-photo__frame" <?php if (!empty($producto['imagen'])): ?>style="background-image: url('<?php echo htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8'); ?>'); background-size: cover; background-position: center;"
                            <?php endif; ?>>
                            <input id="photo_main" name="photo_main" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop" <?php if (!empty($producto['imagen'])): ?>style="display: none;" <?php endif; ?>>Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" <?php echo empty($producto['imagen']) ? 'disabled' : ''; ?>>Eliminar</button>
                    </div>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 1</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra1" name="photo_extra1" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_extra1" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 2</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra2" name="photo_extra2" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_extra2" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>
                </fieldset>

                <fieldset class="pf-fields">
                    <div class="pf-field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text"
                            value="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required
                            placeholder="Ej. Monstera Deliciosa">
                    </div>

                    <div class="pf-field">
                        <label for="category">Categoría</label>
                        <select id="category" name="category" required>
                            <option value="" hidden>Selecciona una categoría</option>
                            <?php foreach ($navCategorias as $cat): ?>
                                <option value="<?php echo (int) $cat['categoria_id']; ?>" <?php echo $producto['categoria_categoria_id'] == $cat['categoria_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pf-field pf-price">
                        <label for="price">Precio</label>
                        <div class="pf-money">
                            <span>$</span>
                            <input id="price" name="price" type="number" min="0" max="99999.99" step="0.01"
                                value="<?php echo htmlspecialchars($producto['precio'], ENT_QUOTES, 'UTF-8'); ?>" required placeholder="0.00"
                                oninput="if(this.value.length > 8) this.value = this.value.slice(0, 8);">
                        </div>
                        <small class="pf-hint">Precio incluye IVA</small>
                    </div>

                    <div class="pf-field pf-stock">
                        <label for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0" max="9999" step="1"
                            value="<?php echo (int) $producto['stock']; ?>" required
                            oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4);">
                        <small class="pf-hint">Disponibles</small>
                    </div>

                    <div class="pf-field pf-desc">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="6"
                            placeholder="Describe el producto…"><?php echo htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </fieldset>

                <div class="pf-actions">
                    <a class="btn-outline" href="mis-productos.php">Cancelar</a>
                    <button class="btn-primary" type="submit">Guardar cambios</button>
                </div>
            </form>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
    <script src="Assets/js/validaciones.js"></script>
</body>

</html>