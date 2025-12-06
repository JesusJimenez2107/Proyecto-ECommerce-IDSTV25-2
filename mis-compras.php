<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

require_once "app/config/connectionController.php";
require_once "app/controllers/cartController.php";

$usuario_id = (int) $_SESSION['usuario_id'];

// Header
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cart = new CartController();
$cartCount = $cart->getCartCount($usuario_id);

// Conexión
$conn = (new ConnectionController())->connect();

/* ===============================
   1) OBTENER COMPRAS DEL USUARIO
   =============================== */
$sqlCompras = "SELECT compra_id, fecha, total
               FROM compra
               WHERE usuario_usuario_id = ?
               ORDER BY fecha DESC";
$stmt = $conn->prepare($sqlCompras);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resCompras = $stmt->get_result();
$compras = $resCompras->fetch_all(MYSQLI_ASSOC);

/* ===============================
   2) PREPARAR CONSULTA DE ITEMS
   =============================== */
$sqlItems = "SELECT d.cantidad, p.nombre, p.imagen
             FROM detalle_compra d
             INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             WHERE d.compra_compra_id = ?";
$stmtItems = $conn->prepare($sqlItems);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis compras – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css">
    <link rel="stylesheet" href="Assets/styles/purchases.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php"><img src="Assets/img/logo.png" alt="Raíz Viva"></a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn">Productos
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path d="M6 9l6 6 6-6" stroke="currentColor" fill="none" stroke-width="2"></path>
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
                <input type="search" placeholder="Buscar productos">
                <button type="submit">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </button>
            </form>

            <div class="actions">
                <a href="mi-cuenta.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#fff" fill="none" stroke-width="2">
                        <path d="M20 21a8 8 0 1 0-16 0"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Mi cuenta</span>
                </a>

                <a href="carrito.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#fff" fill="none" stroke-width="2">
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
        <nav class="breadcrumb">
            <a href="mi-cuenta.php">Mi Cuenta</a> › <span>Mis Compras</span>
        </nav>

        <h1 class="pc-title">MIS COMPRAS</h1>

        <section class="orders">
            <?php if (empty($compras)): ?>
                <p>No has realizado compras aún.</p>

            <?php else: ?>

                <?php foreach ($compras as $compra): ?>
                    <?php
                    $compraId = $compra['compra_id'];

                    // FORMATEO DE FECHA
                    $fecha = date("d/m/Y H:i", strtotime($compra['fecha']));

                    // Obtener items
                    $stmtItems->bind_param("i", $compraId);
                    $stmtItems->execute();
                    $resItems = $stmtItems->get_result();
                    $items = $resItems->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <article class="order-card">
                        <header class="order-head">
                            <div class="cell">
                                <span class="label">Pedido realizado</span>
                                <strong><?php echo $fecha; ?></strong>
                            </div>

                            <div class="cell">
                                <span class="label">Total</span>
                                <strong>$<?php echo number_format($compra['total'], 2); ?></strong>
                            </div>

                            <div class="cell">
                                <span class="label">Pedido #</span>
                                <strong><?php echo $compraId; ?></strong>
                            </div>

                            <div class="cell">
                                <span class="label">Estado</span>
                                <strong>Entregado</strong>
                            </div>

                            <a class="btn-ticket" href="ticket-compra.php?id=<?php echo $compraId; ?>" target="_blank">
                                Ver<br>Ticket
                            </a>
                        </header>

                        <ul class="order-items">
                            <?php foreach ($items as $it): ?>
                                <li class="item">
                                    <img src="<?php echo htmlspecialchars($it['imagen']); ?>"
                                        alt="<?php echo htmlspecialchars($it['nombre']); ?>">
                                    <div class="meta">
                                        <p class="name"><?php echo htmlspecialchars($it['nombre']); ?></p>
                                        <small class="qty">Cantidad: <?php echo $it['cantidad']; ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>

                <?php endforeach; ?>

            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>

</html>