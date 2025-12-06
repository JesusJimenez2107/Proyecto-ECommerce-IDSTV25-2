<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

// Controllers
require_once "app/controllers/productController.php";

$pc = new ProductController();

if (isset($_SESSION['usuario_id'])) {
    require_once "app/controllers/cartController.php";
    $cartCtrl   = new CartController();
    $cartCount  = $cartCtrl->getCartCount((int)$_SESSION['usuario_id']);
}

// Validar ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: productos.php");
    exit;
}

$producto_id = (int) $_GET['id'];
$producto    = $pc->getPublicProductById($producto_id);

if ($producto) {
    $titulo   = $producto['nombre'];

    // Galería: armar arreglo de imágenes disponibles
    $imagenes = [];
    if (!empty($producto['imagen']))        $imagenes[] = $producto['imagen'];
    if (!empty($producto['imagen_extra1'])) $imagenes[] = $producto['imagen_extra1'];
    if (!empty($producto['imagen_extra2'])) $imagenes[] = $producto['imagen_extra2'];

    // Si no hay ninguna, usar placeholder
    if (empty($imagenes)) {
        $imagenes[] = "Assets/img/placeholder.jpg";
    }
} else {
    $titulo = "Producto no encontrado";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($titulo); ?> – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/product-detail.css" />
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
            <nav class="nav-menu" id="menuProductos" role="menu" hidden>
                <a role="menuitem" class="nav-menu__item" href="productos.php?cat=1">Plantas de interior</a>
                <a role="menuitem" class="nav-menu__item" href="productos.php?cat=2">Plantas de exterior</a>
                <a role="menuitem" class="nav-menu__item" href="productos.php?cat=3">Bajo mantenimiento</a>
                <a role="menuitem" class="nav-menu__item" href="productos.php?cat=4">Aromáticas y comestibles</a>
                <a role="menuitem" class="nav-menu__item" href="productos.php?cat=5">Macetas y accesorios</a>
                <a role="menuitem" class="nav-menu__item" href="productos.php?cat=6">Cuidados y bienestar</a>
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
    <?php if (!$producto): ?>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Inicio</a> ›
            <a href="productos.php">Productos</a> ›
            <span>Producto no encontrado</span>
        </nav>

        <h1 class="page-title">Producto no encontrado</h1>
        <p>El producto que buscas no existe o ya no está disponible.</p>
        <a href="productos.php" class="btn">Volver a productos</a>

    <?php else: ?>

        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Inicio</a> ›
            <a href="productos.php">Productos</a> ›
            <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
        </nav>

        <section class="product-detail">
            <aside class="pd-gallery">
                <div class="pd-thumbs">
                    <?php foreach ($imagenes as $i => $img): ?>
                        <button class="pd-thumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
                                aria-label="Vista <?php echo $i + 1; ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>"
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?> vista <?php echo $i + 1; ?>">
                        </button>
                    <?php endforeach; ?>
                </div>

                <figure class="pd-main">
                    <img src="<?php echo htmlspecialchars($imagenes[0]); ?>"
                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                </figure>
            </aside>

            <article class="pd-panel">
                <header class="pd-head">
                    <h1 class="pd-title">
                        <?php echo strtoupper(htmlspecialchars($producto['nombre'])); ?>
                    </h1>
                    <p class="pd-sub">
                        <?php
                        echo !empty($producto['categoria_nombre'])
                            ? htmlspecialchars($producto['categoria_nombre'])
                            : "Producto";
                        ?>
                    </p>
                </header>

                <p class="pd-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                <p class="pd-tax-note">Precio incluye IVA.</p>

                <p class="pd-desc">
                    <?php
                    if (!empty($producto['descripcion'])) {
                        echo nl2br(htmlspecialchars($producto['descripcion']));
                    } else {
                        echo "Este producto aún no tiene descripción detallada.";
                    }
                    ?>
                </p>

                <p class="pd-stock">
                    <strong>Stock:</strong> <?php echo (int)$producto['stock']; ?> disponibles
                </p>

                <div class="pd-cta">
                    <div class="pd-qty" role="group" aria-label="Cantidad">
                        <button type="button" class="qty-btn" data-action="minus" aria-label="Disminuir">−</button>
                        <input class="qty-input" type="number" value="1" min="1" aria-label="Cantidad">
                        <button type="button" class="qty-btn" data-action="plus" aria-label="Aumentar">+</button>
                    </div>

                    <form action="app/controllers/cartController.php" method="POST" class="pd-add-form">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="producto_id" value="<?php echo $producto['producto_id']; ?>">
                        <input type="hidden" name="cantidad" value="1" class="qty-hidden">
                        <button type="submit" class="btn pd-add">Agregar al carrito</button>
                    </form>
                </div>

                <ul class="pd-benefits" aria-label="Beneficios">
                    <li>
                        <img src="Assets/icons/shipping.svg" alt="" aria-hidden="true">
                        <span>Envío especializado</span>
                    </li>
                    <li>
                        <img src="Assets/icons/shield.svg" alt="" aria-hidden="true">
                        <span>Garantía de 15 días</span>
                    </li>
                    <li>
                        <img src="Assets/icons/secure.svg" alt="" aria-hidden="true">
                        <span>Pago seguro</span>
                    </li>
                </ul>
            </article>
        </section>

    <?php endif; ?>
</main>

<footer class="footer">
    <p>© Raíz Viva</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Galería
    const thumbs = document.querySelectorAll('.pd-thumb');
    const mainImg = document.querySelector('.pd-main img');

    if (thumbs.length && mainImg) {
        thumbs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const img = btn.querySelector('img');
                if (!img) return;

                mainImg.src = img.src;
                mainImg.alt = img.alt;

                thumbs.forEach(t => t.classList.remove('is-active'));
                btn.classList.add('is-active');
            });
        });
    }

    // Cantidad
    const qtyContainer = document.querySelector('.pd-qty');
    const qtyInput = document.querySelector('.qty-input');
    const qtyHidden = document.querySelector('.qty-hidden');

    if (qtyContainer && qtyInput && qtyHidden) {
        const btns = qtyContainer.querySelectorAll('.qty-btn');

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                let current = parseInt(qtyInput.value, 10) || 1;

                if (btn.dataset.action === 'minus') {
                    if (current > 1) current--;
                } else if (btn.dataset.action === 'plus') {
                    current++;
                }

                qtyInput.value = current;
                qtyHidden.value = current;
            });
        });

        qtyInput.addEventListener('input', function () {
            let val = parseInt(qtyInput.value, 10);
            if (isNaN(val) || val < 1) val = 1;
            qtyInput.value = val;
            qtyHidden.value = val;
        });
    }
});
</script>
</body>
</html>
