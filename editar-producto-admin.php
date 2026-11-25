<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: productos-admin.php");
    exit();
}
$producto_id = (int) $_GET['id'];

require_once __DIR__ . "/app/controllers/productController.php";
$pc = new ProductController();
$producto = $pc->getProductByIdAdmin($producto_id);

if (!$producto) {
    header("Location: productos-admin.php");
    exit();
}
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
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Ruta">
            <a href="panel-admin.php">Panel Administración</a>
            <span class="sep">›</span>
            <a href="productos-admin.php">Productos</a>
            <span class="sep">›</span>
            <span>Editar producto</span>
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
                        <div class="pf-photo__frame" <?php if (!empty($producto['imagen'])): ?> style="background-image: url('<?php echo htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8'); ?>');
                                       background-size: cover;
                                       background-position: center;" <?php endif; ?>>
                            <input id="photo_main" name="photo_main" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop" <?php if (!empty($producto['imagen'])): ?>style="display:none" <?php endif; ?>>
                                Subir / Reemplazar
                            </label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" <?php echo empty($producto['imagen']) ? 'disabled' : ''; ?>>
                            Eliminar
                        </button>
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
                        <input id="name" name="name" type="text" required
                            value="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="pf-field">
                        <label for="category">Categoría</label>
                        <select id="category" name="category" required>
                            <option value="" hidden>Selecciona una categoría</option>
                            <option value="1" <?php echo $producto['categoria_categoria_id'] == 1 ? 'selected' : ''; ?>>Plantas de interior</option>
                            <option value="2" <?php echo $producto['categoria_categoria_id'] == 2 ? 'selected' : ''; ?>>Plantas de exterior</option>
                            <option value="3" <?php echo $producto['categoria_categoria_id'] == 3 ? 'selected' : ''; ?>>Bajo mantenimiento</option>
                            <option value="4" <?php echo $producto['categoria_categoria_id'] == 4 ? 'selected' : ''; ?>>Aromáticas y comestibles</option>
                            <option value="5" <?php echo $producto['categoria_categoria_id'] == 5 ? 'selected' : ''; ?>>Macetas y accesorios</option>
                            <option value="6" <?php echo $producto['categoria_categoria_id'] == 6 ? 'selected' : ''; ?>>Cuidados y bienestar</option>
                        </select>
                    </div>

                    <div class="pf-field pf-price">
                        <label for="price">Precio</label>
                        <div class="pf-money">
                            <span>$</span>
                            <input id="price" name="price" type="number" step="0.01" min="0" required
                                value="<?php echo htmlspecialchars($producto['precio'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <small class="pf-hint">Precio incluye IVA</small>
                    </div>

                    <div class="pf-field pf-stock">
                        <label for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0" step="1" required
                            value="<?php echo (int) $producto['stock']; ?>">
                        <small class="pf-hint">Disponibles</small>
                    </div>

                    <div class="pf-field pf-desc">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="6"
                            placeholder="Describe el producto…"><?php echo htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </fieldset>

                <div class="pf-actions">
                    <a class="btn-outline" href="datos-producto-admin.php?id=<?php echo (int) $producto['producto_id']; ?>">
                        Cancelar
                    </a>
                    <button class="btn-primary" type="submit">Guardar cambios</button>
                </div>
            </form>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
    <script src="Assets/js/validaciones.js"></script>
</body>

</html>