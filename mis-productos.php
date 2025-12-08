<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no está logueado, lo mandamos a login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

require_once "app/config/connectionController.php";

// Si hay usuario, obtenemos el número de productos en carrito
if (isset($_SESSION['usuario_id'])) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount((int) $_SESSION['usuario_id']);
}

$conn = (new ConnectionController())->connect();
$usuario_id = (int) $_SESSION['usuario_id'];

// ======================
//    P A G I N A C I Ó N
// ======================
$perPage = 9; // 3x3
$page = 1;

if (isset($_GET['page']) && ctype_digit($_GET['page']) && (int) $_GET['page'] > 0) {
    $page = (int) $_GET['page'];
}

// 1) Contar total de productos del usuario
$sqlCount = "SELECT COUNT(*) AS total FROM producto WHERE usuario_id = ?";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("i", $usuario_id);
$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$totalProducts = (int) ($rowCount['total'] ?? 0);
$stmtCount->close();

$totalPages = $totalProducts > 0 ? ceil($totalProducts / $perPage) : 1;

// Si la página pedida es mayor que el total, la ajustamos
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// 2) Obtener solo los productos de la página actual
$query = "SELECT * 
          FROM producto 
          WHERE usuario_id = ?
          ORDER BY producto_id DESC
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $usuario_id, $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mis productos – Raíz Viva</title>

    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/mis-productos.css" />
</head>

<body>
    <!-- Topbar global -->
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php"><img src="Assets/img/logo.png" alt="Raíz Viva" /></a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn">Productos
                    <svg viewBox="0 0 24 24" width="16" height="16">
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
                <input type="search" placeholder="Buscar productos" aria-label="Buscar productos" />
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
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="mi-cuenta.php">Mi Cuenta</a>
            <span class="sep">›</span>
            <span>Mis productos</span>
        </nav>

        <div class="seller-head">
            <h1 class="seller-title">MIS PRODUCTOS</h1>
            <a class="btn-primary seller-add" href="agregar-producto.php">Agregar producto</a>
        </div>

        <!-- GRID -->
        <section class="seller-grid" aria-label="Listado de productos">
            <?php if ($totalProducts === 0): ?>
                <p>No tienes productos registrados todavía.</p>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <article class="seller-card">

                        <a class="thumb" href="editar-producto.php?id=<?php echo $row['producto_id']; ?>">
                            <img src="<?php echo htmlspecialchars($row['imagen']); ?>"
                                alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                        </a>

                        <h3 class="item-title"><?php echo htmlspecialchars($row['nombre']); ?></h3>

                        <div class="item-meta">
                            <span class="price">$<?php echo number_format($row['precio'], 2); ?></span>

                            <?php if ($row['stock'] <= 3): ?>
                                <span class="stock stock-low">Stock:
                                    <strong><?php echo (int) $row['stock']; ?></strong> disponibles</span>
                            <?php else: ?>
                                <span class="stock">Stock:
                                    <strong><?php echo (int) $row['stock']; ?></strong> disponibles</span>
                            <?php endif; ?>
                        </div>

                        <div class="item-actions">
                            <!-- ELIMINAR -->
                            <form action="app/controllers/productController.php" method="post" class="inline">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="producto_id" value="<?php echo $row['producto_id']; ?>">
                                <button type="submit" class="btn-danger">Eliminar</button>
                            </form>

                            <!-- EDITAR -->
                            <a href="editar-producto.php?id=<?php echo $row['producto_id']; ?>" class="btn-outline">Editar</a>
                        </div>

                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>

        <!-- Paginación real -->
        <?php if ($totalPages > 1): ?>
            <nav class="seller-pager" aria-label="Paginación">
                <?php if ($page > 1): ?>
                    <a class="pager-btn" href="?page=<?php echo $page - 1; ?>">Anterior</a>
                <?php else: ?>
                    <span class="pager-btn is-disabled" aria-disabled="true">Anterior</span>
                <?php endif; ?>

                <span class="pager-info">
                    Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a class="pager-btn" href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                <?php else: ?>
                    <span class="pager-btn is-disabled" aria-disabled="true">Siguiente</span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>

</html>