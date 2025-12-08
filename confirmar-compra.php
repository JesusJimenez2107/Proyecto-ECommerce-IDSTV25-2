<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no está logueado, mandar a login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

require_once "app/controllers/cartController.php";

$cart = new CartController();
$usuario_id = (int) $_SESSION['usuario_id'];

// Productos del carrito
$items = $cart->getCartItems($usuario_id);

// Bandera para saber si venimos de una compra exitosa
$isSuccess = isset($_GET['msg']) && $_GET['msg'] === 'success';

// Si no hay items y NO es éxito, mandar de vuelta al carrito
if (count($items) === 0 && !$isSuccess) {
    header("Location: carrito.php?msg=empty");
    exit;
}

// Total
$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = $cart->getCartCount($usuario_id);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Confirmar compra – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/checkout.css" />
</head>

<body class="<?php echo $isSuccess ? 'modal-open' : ''; ?>">

    <!-- Header -->
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php">
                <img src="Assets/img/logo.png" alt="Raíz Viva" />
            </a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn">Productos
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
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

        <h1 class="ck-title">CONFIRMAR COMPRA</h1>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'stock'): ?>
                <div class="alert alert-danger">No hay stock suficiente para completar la compra.</div>
            <?php elseif ($_GET['msg'] === 'error'): ?>
                <div class="alert alert-danger">Ocurrió un error al procesar tu compra. Intenta nuevamente.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Resumen -->
        <section class="ck-card">
            <h2 class="ck-section-title">Resumen de tu pedido</h2>

            <ul class="ck-summary">
                <?php foreach ($items as $item): ?>
                    <?php
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $img = !empty($item['imagen']) ? $item['imagen'] : 'Assets/img/placeholder.jpg';
                    ?>
                    <li class="ck-row">
                        <figure class="ck-thumb">
                            <img src="<?php echo htmlspecialchars($img); ?>"
                                alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                        </figure>
                        <div class="ck-info">
                            <p class="ck-name"><?php echo htmlspecialchars($item['nombre']); ?></p>
                            <small class="ck-qty">Cantidad: <?php echo (int) $item['cantidad']; ?></small>
                        </div>
                        <p class="ck-price">
                            $<?php echo number_format($subtotal, 2); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="ck-total">
                <span>Total:</span>
                <strong>$<?php echo number_format($total, 2); ?></strong>
            </div>
        </section>

        <!-- Información de envío -->
        <section class="ck-card">
            <h2 class="ck-section-title">Información de envío</h2>

            <form class="ck-form" action="app/controllers/cartController.php" method="POST" id="checkout-form">
                <input type="hidden" name="action" value="checkout">

                <label class="ck-label" for="nombre">Nombre</label>
                <input class="ck-input" type="text" id="nombre" name="nombre" placeholder="Tu nombre completo" required>

                <label class="ck-label" for="direccion">Dirección</label>
                <input class="ck-input" type="text" id="direccion" name="direccion" placeholder="Calle, número, colonia"
                    required>

                <label class="ck-label" for="ciudad">Ciudad</label>
                <input class="ck-input" type="text" id="ciudad" name="ciudad" placeholder="Ciudad" required>

                <label class="ck-label" for="telefono">Teléfono</label>
                <input class="ck-input" type="tel" id="telefono" name="telefono" placeholder="Ej. 612 123 4567"
                    required>
            </form>
        </section>

        <!-- Acciones -->
        <section class="ck-actions">
            <a href="carrito.php" class="btn-secondary">Cancelar</a>
            <button class="btn-primary" type="submit" form="checkout-form">Confirmar</button>
        </section>

    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>

    <!-- Modal de confirmación de compra -->
    <div class="modal-overlay <?php echo $isSuccess ? 'is-visible' : ''; ?>" id="success-modal">
        <div class="modal-box">
            <h2>¡Gracias por tu compra! 🌿</h2>
            <p>Tu pedido se ha realizado con éxito.</p>
            <button type="button" id="modal-ok-btn">Aceptar</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const overlay = document.getElementById("success-modal");
            const okBtn = document.getElementById("modal-ok-btn");

            if (!overlay || !okBtn) return;

            okBtn.addEventListener("click", function () {
                overlay.classList.remove("is-visible");
                document.body.classList.remove("modal-open");
            });
        });
    </script>
    <script src="Assets/js/validaciones.js"></script>

</body>

</html>