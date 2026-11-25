<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

require_once __DIR__ . "/app/controllers/cartController.php";

$cart = new CartController();
$usuario_id = (int) $_SESSION['usuario_id'];
$items = $cart->getCartItems($usuario_id);

$total = 0;
$hayErrorStock = false;

foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];

    if ($item['cantidad'] > $item['stock'] || $item['cantidad'] <= 0 || $item['stock'] <= 0) {
        $hayErrorStock = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Carrito – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/cart.css" />
</head>

<body>

    <?php require_once "app/views/layouts/header.php"; ?>

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
            <section class="cart-list" aria-label="Productos en el carrito">
                <?php foreach ($items as $item): ?>
                    <?php
                    $subtotal_item = $item['precio'] * $item['cantidad'];
                    $img = !empty($item['imagen']) ? $item['imagen'] : 'Assets/img/placeholder.jpg';
                    ?>
                    <article class="cart-item">
                        <figure class="cart-thumb">
                            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8'); ?>" />
                        </figure>

                        <div class="cart-info">
                            <p class="cart-label">Nombre:</p>
                            <h3 class="cart-name">
                                <?php echo htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>
                        </div>

                        <div class="cart-qty">
                            <p class="cart-label">Cantidad:</p>

                            <form action="app/controllers/cartController.php" method="POST" class="qty-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="update_qty">
                                <input type="hidden" name="carrito_id" value="<?php echo (int) $item['carrito_id']; ?>">

                                <div class="qty-group" role="group" aria-label="Cantidad">
                                    <input class="qty-input auto-update" type="number" name="cantidad"
                                        value="<?php echo (int) $item['cantidad']; ?>" min="1"
                                        max="<?php echo (int) $item['stock']; ?>" aria-label="Cantidad" style="width: 60px;" />
                                </div>
                                
                                <?php if ($item['cantidad'] > $item['stock'] && $item['stock'] > 0): ?>
                                    <p style="color: #e74c3c; font-size: 11px; margin-top: 6px; font-weight: bold; max-width: 120px;">
                                        Solo <?php echo (int) $item['stock']; ?> disp.
                                    </p>
                                <?php elseif ($item['stock'] <= 0): ?>
                                    <p style="color: #e74c3c; font-size: 11px; margin-top: 6px; font-weight: bold; max-width: 120px;">
                                        Agotado.
                                    </p>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="cart-price">
                            <p class="cart-label">Precio:</p>
                            <p class="cart-value">$<?php echo number_format($item['precio'], 2); ?></p>
                        </div>

                        <div class="cart-subtotal">
                            <p class="cart-label">Subtotal:</p>
                            <p class="cart-value">$<?php echo number_format($subtotal_item, 2); ?></p>
                        </div>

                        <form action="app/controllers/cartController.php" method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="remove_item">
                            <input type="hidden" name="carrito_id" value="<?php echo (int) $item['carrito_id']; ?>">
                            <button class="cart-remove" aria-label="Eliminar producto" type="submit">
                                <img src="Assets/icons/trash.svg" alt="" />
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="cart-actions">
                <div class="cart-actions__left">
                    <form action="app/controllers/cartController.php" method="POST" style="display:inline-block">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit" class="btn-outline-danger">Vaciar carrito</button>
                    </form>

                    <a href="productos.php" class="btn-outline">Seguir comprando</a>
                </div>

                <div class="cart-summary">
                    <span class="total-label">Total: <strong>$<?php echo number_format($total, 2); ?></strong></span>

                    <?php if ($total > 0 && !$hayErrorStock): ?>
                        <a href="confirmar-compra.php" class="btn-comprar">Comprar ahora</a>
                    <?php else: ?>
                        <button class="btn-comprar" style="background-color: #ccc; cursor: not-allowed;" disabled
                            title="Corrige los errores de stock en rojo antes de continuar">Comprar ahora</button>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>

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

                        let val = parseInt(input.value);
                        let maxVal = parseInt(input.max);

                        if (val < 1 || isNaN(val)) {
                            input.value = 1;
                        } else if (val > maxVal) {
                            input.value = maxVal;
                            alert("Solo hay " + maxVal + " piezas disponibles de este producto.");
                        }

                        form.submit();
                    }, 300);
                });
            });
        });
    </script>

</body>

</html>