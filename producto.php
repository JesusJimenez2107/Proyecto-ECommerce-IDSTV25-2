<?php
require_once __DIR__ . "/app/config/security.php";
require_once __DIR__ . "/app/controllers/productController.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: productos.php");
    exit();
}

$pc = new ProductController();
$producto_id = (int) $_GET['id'];
$producto = $pc->getPublicProductById($producto_id);

if ($producto) {
    $titulo = $producto['nombre'];
    $imagenes = array_values(array_filter([
        $producto['imagen'] ?? null,
        $producto['imagen_extra1'] ?? null,
        $producto['imagen_extra2'] ?? null,
    ]));
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
    <title><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?> – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/product-detail.css" />
</head>

<body>

    <?php require_once "app/views/layouts/header.php"; ?>

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
                <span><?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
            </nav>

            <section class="product-detail">
                <aside class="pd-gallery">
                    <div class="pd-thumbs">
                        <?php foreach ($imagenes as $i => $img): ?>
                            <button class="pd-thumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
                                aria-label="Vista <?php echo $i + 1; ?>">
                                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?> vista <?php echo $i + 1; ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <figure class="pd-main">
                        <img src="<?php echo htmlspecialchars($imagenes[0], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>
                </aside>

                <article class="pd-panel">
                    <header class="pd-head">
                        <h1 class="pd-title">
                            <?php echo strtoupper(htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8')); ?>
                        </h1>
                        <p class="pd-sub">
                            <?php
                            echo !empty($producto['categoria_nombre'])
                                ? htmlspecialchars($producto['categoria_nombre'], ENT_QUOTES, 'UTF-8')
                                : "Producto";
                            ?>
                        </p>
                    </header>

                    <p class="pd-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                    <p class="pd-tax-note">Precio incluye IVA.</p>

                    <p class="pd-desc">
                        <?php
                        if (!empty($producto['descripcion'])) {
                            echo nl2br(htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8'));
                        } else {
                            echo "Este producto aún no tiene descripción detallada.";
                        }
                        ?>
                    </p>

                    <p class="pd-stock">
                        <strong>Stock:</strong> <?php echo (int) $producto['stock']; ?> disponibles
                    </p>

                    <?php if ($producto['stock'] > 0): ?>
                        <div class="pd-cta">
                            <div class="pd-qty" role="group" aria-label="Cantidad">
                                <button type="button" class="qty-btn" data-action="minus" aria-label="Disminuir">−</button>
                                <input class="qty-input" type="number" value="1" min="1"
                                    max="<?php echo (int) $producto['stock']; ?>" aria-label="Cantidad">
                                <button type="button" class="qty-btn" data-action="plus" aria-label="Aumentar">+</button>
                            </div>

                            <form action="app/controllers/cartController.php" method="POST" class="pd-add-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="producto_id" value="<?php echo (int) $producto['producto_id']; ?>">
                                <input type="hidden" name="cantidad" value="1" class="qty-hidden">
                                <button type="submit" class="btn pd-add">Agregar al carrito</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="pd-cta" style="display: block; margin-top: 15px;">
                            <p style="color: #e24c4c; font-weight: bold; margin-bottom: 15px; font-size: 14px;">
                                Este producto se encuentra agotado por el momento.
                            </p>
                            <button class="btn pd-add" style="background-color: #ccc; cursor: not-allowed; width: 100%; border: none;" disabled>
                                Agotado
                            </button>
                        </div>
                    <?php endif; ?>

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

    <?php require_once "app/views/layouts/footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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

            const qtyContainer = document.querySelector('.pd-qty');
            const qtyInput = document.querySelector('.qty-input');
            const qtyHidden = document.querySelector('.qty-hidden');

            if (qtyContainer && qtyInput && qtyHidden) {
                const btns = qtyContainer.querySelectorAll('.qty-btn');
                const maxStock = parseInt(qtyInput.getAttribute('max'), 10) || 1;

                btns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        let current = parseInt(qtyInput.value, 10) || 1;

                        if (btn.dataset.action === 'minus') {
                            if (current > 1) current--;
                        } else if (btn.dataset.action === 'plus') {
                            if (current < maxStock) {
                                current++;
                            } else {
                                alert("Solo hay " + maxStock + " piezas disponibles de este producto.");
                            }
                        }

                        qtyInput.value = current;
                        qtyHidden.value = current;
                    });
                });

                qtyInput.addEventListener('input', function () {
                    let val = parseInt(qtyInput.value, 10);

                    if (isNaN(val) || val < 1) {
                        val = 1;
                    } else if (val > maxStock) {
                        val = maxStock;
                        alert("Solo hay " + maxStock + " piezas disponibles de este producto.");
                    }

                    qtyInput.value = val;
                    qtyHidden.value = val;
                });
            }
        });
    </script>
</body>

</html>