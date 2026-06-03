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
    <link rel="stylesheet" href="Assets/styles/global.css?v=2">
    <link rel="stylesheet" href="Assets/styles/product-new.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php">
                <img src="Assets/img/logo.png" alt="Raíz Viva" />
            </a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn" id="btnProductos" aria-haspopup="true" aria-expanded="false"
                    aria-controls="menuProductos">
                    Productos
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>
                </button>

                <!-- Menú de categorías -->
                <nav class="nav-menu" id="menuProductos" role="menu" hidden>
                    <a role="menuitem" href="productos.php?cat=1" class="nav-menu__item">
                        Plantas de interior
                    </a>

                    <a role="menuitem" href="productos.php?cat=2" class="nav-menu__item">
                        Plantas de exterior
                    </a>

                    <a role="menuitem" href="productos.php?cat=3" class="nav-menu__item">
                        Bajo mantenimiento
                    </a>

                    <a role="menuitem" href="productos.php?cat=4" class="nav-menu__item">
                        Aromáticas y comestibles
                    </a>

                    <a role="menuitem" href="productos.php?cat=5" class="nav-menu__item">
                        Macetas y accesorios
                    </a>

                    <a role="menuitem" href="productos.php?cat=6" class="nav-menu__item">
                        Cuidados y bienestar
                    </a>
                </nav>
            </div>

            <form action="productos.php" method="GET" class="search" role="search">
                <input type="search" name="search" placeholder="Buscar" aria-label="Buscar"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
                        <span>
                            <?php
                            // Si existe el nombre en sesión, extraemos solo el primer nombre
                            if (isset($_SESSION['nombre']) && !empty($_SESSION['nombre'])) {
                                $primerNombre = explode(' ', trim($_SESSION['nombre']))[0];
                                // Ponemos la primera letra en mayúscula
                                echo htmlspecialchars(ucfirst(strtolower($primerNombre)));
                            } else {
                                echo 'Mi cuenta';
                            }
                            ?>
                        </span>
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
                        <circle cx="10" cy="20" r="1"></circle>
                        <circle cx="18" cy="20" r="1"></circle>
                        <path d="M2 2h3l2.2 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 6H6"></path>
                    </svg>
                    <span><?php echo $cartCount; ?></span>
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
                        <div class="pf-photo__frame" <?php if (!empty($producto['imagen'])): ?>style="background-image: url('<?php echo htmlspecialchars($producto['imagen']); ?>'); background-size: cover; background-position: center;"
                            <?php endif; ?>>
                            <input id="photo_main" name="photo_main" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_main" class="pf-photo__drop" <?php if (!empty($producto['imagen'])): ?>style="display: none;" <?php endif; ?>>Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" <?php echo empty($producto['imagen']) ? 'disabled' : ''; ?>>Eliminar</button>
                    </div>

                    <!-- FOTO EXTRA 1 -->
                    <div class="pf-photo">
                        <span class="pf-photo__label">Extra 1</span>
                        <div class="pf-photo__frame">
                            <input id="photo_extra1" name="photo_extra1" type="file" accept="image/*"
                                class="pf-photo__input">
                            <label for="photo_extra1" class="pf-photo__drop">Subir / Reemplazar</label>
                        </div>
                        <button type="button" class="btn-danger pf-photo__remove" disabled>Eliminar</button>
                    </div>

                    <!-- FOTO EXTRA 2 -->
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
                        <input id="name" name="name" type="text"
                            value="<?php echo htmlspecialchars($producto['nombre']); ?>" required
                            placeholder="Ej. Monstera Deliciosa">
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
                            <input id="price" name="price" type="number" min="0" max="99999.99" step="0.01"
                                value="<?php echo $producto['precio']; ?>" required placeholder="0.00"
                                oninput="if(this.value.length > 8) this.value = this.value.slice(0, 8);">
                        </div>
                        <small class="pf-hint">Precio incluye IVA</small>
                    </div>

                    <div class="pf-field pf-stock">
                        <label for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0" max="9999" step="1"
                            value="<?php echo $producto['stock']; ?>" required
                            oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4);">
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