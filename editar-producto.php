<?php
session_start();


if (!isset($_GET['id'])) {
    header("Location: mis-productos.php");
    exit();
}

$producto_id = intval($_GET['id']);


include "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$query = "SELECT * FROM producto WHERE producto_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Raíz Viva – Editar producto</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/styles/global.css">
    <link rel="stylesheet" href="Assets/styles/product-new.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php">
                <img src="Assets/img/logo.png" alt="Raíz Viva">
            </a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn" aria-haspopup="true" aria-expanded="false" aria-controls="menuProductos">
                    Productos
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>
                </button>
                <nav class="nav-menu" id="menuProductos" hidden>
                    <a role="menuitem" href="/cat-interior" class="nav-menu__item">Plantas de interior</a>
                    <a role="menuitem" href="/cat-exterior" class="nav-menu__item">Plantas de exterior</a>
                    <a role="menuitem" href="/cat-bajo" class="nav-menu__item">Bajo mantenimiento</a>
                    <a role="menuitem" href="/cat-aromaticas" class="nav-menu__item">Aromáticas y comestibles</a>
                    <a role="menuitem" href="/cat-macetas" class="nav-menu__item">Macetas y accesorios</a>
                    <a role="menuitem" href="/cat-cuidados" class="nav-menu__item">Cuidados y bienestar</a>
                </nav>
            </div>

            <form class="search" role="search">
                <input type="search" placeholder="Buscar" aria-label="Buscar">
                <button type="submit" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </button>
            </form>

            <div class="actions">
                <a href="mi-cuenta.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                        <path d="M20 21a8 8 0 1 0-16 0" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span>Mi cuenta</span>
                </a>
                <a href="/carrito" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                        <circle cx="10" cy="20" r="1" />
                        <circle cx="18" cy="20" r="1" />
                        <path d="M2 2h3l2.2 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 6H6" />
                    </svg>
                    <span>0</span>
                </a>
            </div>
        </div>
    </header>

    <main class="page">
        <!-- Breadcrumbs -->
        <nav class="breadcrumb" aria-label="ruta">
            <a href="mi-cuenta.php">Mi Cuenta</a>
            <span class="sep">›</span>
            <a href="mis-productos.php">Mis Productos</a>
            <span class="sep">›</span>
            <span>Editar Producto</span>
        </nav>

        <section class="prod-form-card" aria-labelledby="pf-title">
            <h1 id="pf-title" class="pf-title">Editar producto</h1>

            <form class="pf" action="app/controllers/productController.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_product">    

                <input type="hidden" name="producto_id" value="<?php echo $producto['producto_id']; ?>">
                
                
                <fieldset class="pf-photos">
                    <legend>Máximo 3 fotos</legend>

                    <!-- FOTO PRINCIPAL -->
                    <div class="pf-photo">
                        <span class="pf-photo__label">Principal</span>
                        <div class="pf-photo__frame" <?php if (!empty($producto['imagen'])): ?>style="background-image: url('<?php echo htmlspecialchars($producto['imagen']); ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
                            <input id="photo_main" name="photo_main" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop" <?php if (!empty($producto['imagen'])): ?>style="display: none;"<?php endif; ?>>Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" <?php echo empty($producto['imagen']) ? 'disabled' : ''; ?>>Eliminar</button>
                    </div>

                    <!-- FOTO EXTRA 1 -->
                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 1</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra1" name="photo_extra1" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_extra1" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>

                    <!-- FOTO EXTRA 2 -->
                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 2</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra2" name="photo_extra2" type="file" accept="image/*" class="pf-photo__input">
                            <label for="photo_extra2" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>
                </fieldset>

                <!-- CAMPOS -->
                <fieldset class="pf-fields">
                    <div class="pf-field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required placeholder="Ej. Monstera Deliciosa">
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
                            <input id="price" name="price" type="number" step="0.01" min="0" value="<?php echo $producto['precio']; ?>" required placeholder="0.00">
                        </div>
                        <small class="pf-hint">Precio incluye IVA</small>
                    </div>

                    <div class="pf-field pf-stock">
                        <label for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0" step="1" value="<?php echo $producto['stock']; ?>" required>
                        <small class="pf-hint">Disponibles</small>
                    </div>

                    <div class="pf-field pf-desc">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="6" placeholder="Describe el producto…"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                </fieldset>

                <!-- ACCIONES -->
                <div class="pf-actions">
                    <a class="btn-outline" href="mis-productos.php">Cancelar</a>
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