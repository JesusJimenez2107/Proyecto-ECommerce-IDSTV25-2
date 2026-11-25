<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

require_once __DIR__ . "/app/controllers/cartController.php";

$cart = new CartController();
$usuario_id = (int) $_SESSION['usuario_id'];
$items = $cart->getCartItems($usuario_id);

$isSuccess = isset($_GET['msg']) && $_GET['msg'] === 'success';

if (count($items) === 0 && !$isSuccess) {
    header("Location: carrito.php?msg=empty");
    exit();
}

$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Confirmar compra – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/checkout.css" />
</head>

<body class="<?php echo $isSuccess ? 'modal-open' : ''; ?>">

    <?php require_once "app/views/layouts/header.php"; ?>

    <main class="page">
        <h1 class="ck-title">CONFIRMAR COMPRA</h1>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'stock'): ?>
                <div class="alert alert-danger">No hay stock suficiente para completar la compra.</div>
            <?php elseif ($_GET['msg'] === 'error'): ?>
                <div class="alert alert-danger">Ocurrió un error al procesar tu compra. Intenta nuevamente.</div>
            <?php endif; ?>
        <?php endif; ?>

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
                            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                        </figure>
                        <div class="ck-info">
                            <p class="ck-name"><?php echo htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8'); ?></p>
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

        <section class="ck-card">
            <h2 class="ck-section-title">Información de envío</h2>

            <form class="ck-form" action="pago.php" method="POST" id="checkout-form">
                <?php echo csrfField(); ?>

                <label class="ck-label" for="nombre">Nombre</label>
                <input class="ck-input" type="text" id="nombre" name="nombre" placeholder="Tu nombre completo" required>

                <label class="ck-label" for="direccion">Dirección</label>
                <input class="ck-input" type="text" id="direccion" name="direccion" placeholder="Calle, número, colonia" required>

                <label class="ck-label" for="ciudad">Ciudad</label>
                <input class="ck-input" type="text" id="ciudad" name="ciudad" placeholder="Ciudad" required>

                <label class="ck-label" for="telefono">Teléfono</label>
                <input class="ck-input" type="tel" id="telefono" name="telefono" placeholder="Ej. 612 123 4567" required>
            </form>
        </section>

        <section class="ck-actions">
            <a href="carrito.php" class="btn-secondary">Cancelar</a>
            <button class="btn-primary" type="submit" form="checkout-form">Continuar</button>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>

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