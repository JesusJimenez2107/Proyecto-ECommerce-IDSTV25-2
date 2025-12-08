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

include "app/config/connectionController.php";

$conn = (new ConnectionController())->connect();

// --------- Parámetros: categoría, búsqueda y página ---------
$categoria_id = isset($_GET['cat']) ? intval($_GET['cat']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$perPage = 9; // 3x3
$page = 1;

if (isset($_GET['page']) && ctype_digit($_GET['page']) && (int) $_GET['page'] > 0) {
    $page = (int) $_GET['page'];
}

// =========================
//   1) CONTAR RESULTADOS
// =========================
if ($search !== '') {
    $like = '%' . $search . '%';

    if ($categoria_id) {
        // Búsqueda + categoría
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM producto
                     WHERE estado = 'activo'
                       AND categoria_categoria_id = ?
                       AND (nombre LIKE ? OR descripcion LIKE ?)";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param("iss", $categoria_id, $like, $like);
    } else {
        // Solo búsqueda
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM producto
                     WHERE estado = 'activo'
                       AND (nombre LIKE ? OR descripcion LIKE ?)";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param("ss", $like, $like);
    }
} else {
    if ($categoria_id) {
        // Solo categoría
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM producto
                     WHERE estado = 'activo'
                       AND categoria_categoria_id = ?";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param("i", $categoria_id);
    } else {
        // Todos los productos
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM producto
                     WHERE estado = 'activo'";
        $stmtCount = $conn->prepare($sqlCount);
    }
}

$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$totalProducts = (int) ($rowCount['total'] ?? 0);
$stmtCount->close();

$totalPages = $totalProducts > 0 ? ceil($totalProducts / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// =========================
//   2) OBTENER PRODUCTOS
// =========================
if ($search !== '') {
    $like = '%' . $search . '%';

    if ($categoria_id) {
        // Búsqueda + categoría
        $query = "SELECT producto.*
                  FROM producto
                  WHERE producto.estado = 'activo'
                    AND producto.categoria_categoria_id = ?
                    AND (producto.nombre LIKE ? OR producto.descripcion LIKE ?)
                  ORDER BY producto.producto_id DESC
                  LIMIT ?, ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issii", $categoria_id, $like, $like, $offset, $perPage);
    } else {
        // Solo búsqueda
        $query = "SELECT producto.*
                  FROM producto
                  WHERE producto.estado = 'activo'
                    AND (producto.nombre LIKE ? OR producto.descripcion LIKE ?)
                  ORDER BY producto.producto_id DESC
                  LIMIT ?, ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $like, $like, $offset, $perPage);
    }

} else {
    if ($categoria_id) {
        // Solo categoría
        $query = "SELECT producto.*
                  FROM producto
                  WHERE producto.estado = 'activo'
                    AND producto.categoria_categoria_id = ?
                  ORDER BY producto.producto_id DESC
                  LIMIT ?, ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $categoria_id, $offset, $perPage);
    } else {
        // Todos los productos
        $query = "SELECT producto.*
                  FROM producto
                  WHERE producto.estado = 'activo'
                  ORDER BY producto.producto_id DESC
                  LIMIT ?, ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $offset, $perPage);
    }
}

$stmt->execute();
$result = $stmt->get_result();

// --------- Título ---------
$titulo = "Todos los productos";

if ($search !== '') {
    $titulo = 'Resultados para: "' . $search . '"';
} elseif ($categoria_id) {
    $cat_query = "SELECT nombre FROM categoria WHERE categoria_id = ?";
    $cat_stmt = $conn->prepare($cat_query);
    $cat_stmt->bind_param("i", $categoria_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
        $titulo = $cat_row['nombre'];
    }
    $cat_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($titulo); ?> – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/products.css" />
</head>

<body>

    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php"><img src="Assets/img/logo.png" alt="Raíz Viva" /></a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn">
                    Productos
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round"></path>
                    </svg>
                </button>
                <nav class="nav-menu" hidden>
                    <a href="productos.php?cat=1" class="nav-menu__item">Plantas de interior</a>
                    <a href="productos.php?cat=2" class="nav-menu__item">Plantas de exterior</a>
                    <a href="productos.php?cat=3" class="nav-menu__item">Bajo mantenimiento</a>
                    <a href="productos.php?cat=4" class="nav-menu__item">Aromáticas y comestibles</a>
                    <a href="productos.php?cat=5" class="nav-menu__item">Macetas y accesorios</a>
                    <a href="productos.php?cat=6" class="nav-menu__item">Cuidados y bienestar</a>
                </nav>
            </div>

            <!-- Buscador: hace búsqueda en productos.php -->
            <form class="search" role="search" action="productos.php" method="GET">
                <?php if ($categoria_id): ?>
                    <!-- Mantener la categoría al buscar -->
                    <input type="hidden" name="cat" value="<?php echo $categoria_id; ?>">
                <?php endif; ?>

                <input type="search" name="search" placeholder="Buscar productos"
                    value="<?php echo htmlspecialchars($search); ?>" />
                <button type="submit">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </button>
            </form>

            <div class="actions">
                <?php if ($logged): ?>
                    <a href="mi-cuenta.php" class="action">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M20 21a8 8 0 1 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Mi cuenta</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="action">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M20 21a8 8 0 1 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
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

        <nav class="breadcrumb">
            <a href="index.php">Inicio</a> › <span><?php echo htmlspecialchars($titulo); ?></span>
        </nav>

        <header class="products-header">
            <h1 class="page-title"><?php echo htmlspecialchars($titulo); ?></h1>
        </header>

        <!-- PRODUCTOS -->
        <section class="products-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($producto = $result->fetch_assoc()): ?>
                    <article class="product-card">
                        <a class="product-thumb" href="producto.php?id=<?php echo $producto['producto_id']; ?>">
                            <?php if (!empty($producto['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($producto['imagen']); ?>"
                                    alt="<?php echo htmlspecialchars($producto['nombre']); ?>" />
                            <?php else: ?>
                                <img src="Assets/img/placeholder.jpg" alt="Sin imagen" />
                            <?php endif; ?>
                        </a>
                        <h3 class="product-name">
                            <a href="producto.php?id=<?php echo $producto['producto_id']; ?>">
                                <?php echo htmlspecialchars($producto['nombre']); ?>
                            </a>
                        </h3>
                        <p class="product-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                        <div class="product-actions">
                            <a class="btn" href="producto.php?id=<?php echo $producto['producto_id']; ?>">
                                Más información
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty">
                    <?php if ($search !== ''): ?>
                        No se encontraron productos para "<?php echo htmlspecialchars($search); ?>".
                    <?php else: ?>
                        No hay productos en esta categoría.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- Paginación -->
        <?php if ($totalPages > 1 && $totalProducts > 0): ?>
            <nav class="seller-pager" aria-label="Paginación">
                <?php
                // Construir base de query (cat + search, sin page)
                $baseParams = [];
                if ($categoria_id) {
                    $baseParams['cat'] = $categoria_id;
                }
                if ($search !== '') {
                    $baseParams['search'] = $search;
                }

                function buildUrlProductos($pageNum, $baseParams)
                {
                    $baseParams['page'] = $pageNum;
                    return 'productos.php?' . http_build_query($baseParams);
                }
                ?>

                <?php if ($page > 1): ?>
                    <a class="pager-btn" href="<?php echo buildUrlProductos($page - 1, $baseParams); ?>">Anterior</a>
                <?php else: ?>
                    <span class="pager-btn is-disabled" aria-disabled="true">Anterior</span>
                <?php endif; ?>

                <span class="pager-info">
                    Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a class="pager-btn" href="<?php echo buildUrlProductos($page + 1, $baseParams); ?>">Siguiente</a>
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