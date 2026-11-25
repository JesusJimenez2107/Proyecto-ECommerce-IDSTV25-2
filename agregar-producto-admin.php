<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$categorias = [];
$resCat = $conn->query("SELECT categoria_id, nombre FROM categoria ORDER BY nombre ASC");
while ($row = $resCat->fetch_assoc()) {
    $categorias[] = $row;
}
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
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Panel Administración</a>
            <span class="sep">›</span>
            <a href="productos-admin.php">Productos</a>
            <span class="sep">›</span>
            <span>Agregar producto</span>
        </nav>

        <section class="prod-form-card" aria-labelledby="pf-title">
            <h1 id="pf-title" class="pf-title">Agregar producto</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" style="color: #e74c3c; background: #fdecea; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="app/controllers/productController.php" method="post" enctype="multipart/form-data" class="pf">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="create_product">

                <fieldset class="pf-photos">
                    <legend>Máximo 3 fotos</legend>

                    <div class="pf-photo">
                        <span class="pf-photo__label">Principal</span>
                        <div class="pf-photo__frame">
                            <input id="photo_main" name="photo_main" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
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
                        <input id="name" name="name" type="text" required placeholder="Ej. Monstera Deliciosa">
                    </div>

                    <div class="pf-field">
                        <label for="category">Categoría</label>
                        <select id="category" name="category" required>
                            <option value="" hidden>Selecciona una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo (int) $cat['categoria_id']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pf-field pf-price">
                        <label for="price">Precio</label>
                        <div class="pf-money">
                            <span>$</span>
                            <input id="price" name="price" type="number" min="0" step="0.01" required placeholder="0.00">
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
                        <textarea id="description" name="description" rows="6" placeholder="Describe el producto…"></textarea>
                    </div>
                </fieldset>

                <div class="pf-actions">
                    <a href="productos-admin.php" class="btn-outline">Cancelar</a>
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                </div>
            </form>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
    <script src="Assets/js/validaciones.js"></script>
</body>

</html>