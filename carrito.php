<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no está logueado, mandar a login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

// Controller del carrito
require_once "app/controllers/cartController.php";

$cart = new CartController();
$usuario_id = (int) $_SESSION['usuario_id'];

// Productos del carrito
$items = $cart->getCartItems($usuario_id);

// Total
$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Datos para header
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = $cart->getCartCount($usuario_id);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Carrito – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/cart.css" />
</head>

<body>

    <!-- Header  -->
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
        <h1 class="cart-title">CARRITO</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">Producto agregado al carrito.</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'removed'): ?>
            <div class="alert alert-success">Producto eliminado del carrito.</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'cleared'): ?>
            <div class="alert alert-success">Carrito vaciado.</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Cantidad actualizada.</div>
        <?php endif; ?>


        <?php if (count($items) === 0): ?>

            <p>No tienes productos en tu carrito.</p>
            <a href="productos.php" class="btn-outline">Ver productos</a>

        <?php else: ?>

            <!-- Lista de ítems -->
            <section class="cart-list" aria-label="Productos en el carrito">

                <?php foreach ($items as $item): ?>
                    <?php
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $img = !empty($item['imagen']) ? $item['imagen'] : 'Assets/img/placeholder.jpg';
                    ?>
                    <article class="cart-item">
                        <figure class="cart-thumb">
                            <img src="<?php echo htmlspecialchars($img); ?>"
                                alt="<?php echo htmlspecialchars($item['nombre']); ?>" />
                        </figure>

                        <div class="cart-info">
                            <p class="cart-label">Nombre:</p>
                            <h3 class="cart-name">
                                <?php echo htmlspecialchars($item['nombre']); ?>
                            </h3>
                        </div>

                        <div class="cart-qty">
                            <p class="cart-label">Cantidad:</p>

                            <form action="app/controllers/cartController.php" method="POST" class="qty-form">
                                <input type="hidden" name="action" value="update_qty">
                                <input type="hidden" name="carrito_id" value="<?php echo $item['carrito_id']; ?>">

                                <div class="qty-group" role="group" aria-label="Cantidad">
                                    <input class="qty-input auto-update" type="number" name="cantidad"
                                        value="<?php echo (int) $item['cantidad']; ?>" min="1" aria-label="Cantidad"
                                        style="width: 60px;" />
                                </div>
                            </form>
                        </div>

                        <div class="cart-price">
                            <p class="cart-label">Precio:</p>
                            <p class="cart-value">$<?php echo number_format($item['precio'], 2); ?></p>
                        </div>

                        <div class="cart-subtotal">
                            <p class="cart-label">Subtotal:</p>
                            <p class="cart-value">$<?php echo number_format($subtotal, 2); ?></p>
                        </div>

                        <form action="app/controllers/cartController.php" method="POST">
                            <input type="hidden" name="action" value="remove_item">
                            <input type="hidden" name="carrito_id" value="<?php echo $item['carrito_id']; ?>">
                            <button class="cart-remove" aria-label="Eliminar producto" type="submit">
                                <img src="Assets/icons/trash.svg" alt="" />
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </section>

            <!-- Acciones inferiores -->
            <section class="cart-actions">
                <div class="cart-actions__left">
                    <form action="app/controllers/cartController.php" method="POST" style="display:inline-block">
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit" class="btn-outline-danger">Vaciar carrito</button>
                    </form>

                    <a href="productos.php" class="btn-outline">Seguir comprando</a>
                </div>

                <div class="cart-actions__right">
                    <div class="cart-total">
                        <span>Total:</span>
                        <strong>$<?php echo number_format($total, 2); ?></strong>
                    </div>
                    <a href="confirmar-compra.php" class="btn-buy">Comprar ahora</a>
                </div>
            </section>

        <?php endif; ?>

    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const inputs = document.querySelectorAll(".auto-update");

            inputs.forEach(input => {
                let timer = null;

                input.addEventListener("input", () => {
                    clearTimeout(timer);

                    timer = setTimeout(() => {
                        const form = input.closest("form");
                        if (!form) return;

                        // Asegurar mínimo 1
                        if (parseInt(input.value) < 1 || isNaN(parseInt(input.value))) {
                            input.value = 1;
                        }

                        form.submit();
                    }, 300); // 300 ms delay para evitar spam
                });
            });
        });
    </script>

</body>

</html>