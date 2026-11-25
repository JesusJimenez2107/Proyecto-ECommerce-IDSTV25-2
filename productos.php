<?php
require_once __DIR__ . "/app/config/security.php";
require_once __DIR__ . "/app/config/connectionController.php";

$conn = (new ConnectionController())->connect();

$categoria_id = isset($_GET['cat']) ? intval($_GET['cat']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$perPage = 9;
$page = 1;

if (isset($_GET['page']) && ctype_digit($_GET['page']) && (int) $_GET['page'] > 0) {
    $page = (int) $_GET['page'];
}

if ($search !== '') {
    $like = '%' . $search . '%';
    if ($categoria_id) {
        $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM producto WHERE estado = 'activo' AND categoria_categoria_id = ? AND (nombre LIKE ? OR descripcion LIKE ?)");
        $stmtCount->bind_param("iss", $categoria_id, $like, $like);
    } else {
        $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM producto WHERE estado = 'activo' AND (nombre LIKE ? OR descripcion LIKE ?)");
        $stmtCount->bind_param("ss", $like, $like);
    }
} else {
    if ($categoria_id) {
        $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM producto WHERE estado = 'activo' AND categoria_categoria_id = ?");
        $stmtCount->bind_param("i", $categoria_id);
    } else {
        $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM producto WHERE estado = 'activo'");
    }
}

$stmtCount->execute();
$totalProducts = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

$totalPages = $totalProducts > 0 ? ceil($totalProducts / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

if ($search !== '') {
    $like = '%' . $search . '%';
    if ($categoria_id) {
        $stmt = $conn->prepare("SELECT producto_id, nombre, precio, imagen FROM producto WHERE estado = 'activo' AND categoria_categoria_id = ? AND (nombre LIKE ? OR descripcion LIKE ?) ORDER BY producto_id DESC LIMIT ?, ?");
        $stmt->bind_param("issii", $categoria_id, $like, $like, $offset, $perPage);
    } else {
        $stmt = $conn->prepare("SELECT producto_id, nombre, precio, imagen FROM producto WHERE estado = 'activo' AND (nombre LIKE ? OR descripcion LIKE ?) ORDER BY producto_id DESC LIMIT ?, ?");
        $stmt->bind_param("ssii", $like, $like, $offset, $perPage);
    }
} else {
    if ($categoria_id) {
        $stmt = $conn->prepare("SELECT producto_id, nombre, precio, imagen FROM producto WHERE estado = 'activo' AND categoria_categoria_id = ? ORDER BY producto_id DESC LIMIT ?, ?");
        $stmt->bind_param("iii", $categoria_id, $offset, $perPage);
    } else {
        $stmt = $conn->prepare("SELECT producto_id, nombre, precio, imagen FROM producto WHERE estado = 'activo' ORDER BY producto_id DESC LIMIT ?, ?");
        $stmt->bind_param("ii", $offset, $perPage);
    }
}

$stmt->execute();
$result = $stmt->get_result();

$titulo = "Todos los productos";
if ($search !== '') {
    $titulo = 'Resultados para: "' . $search . '"';
} elseif ($categoria_id) {
    $cat_stmt = $conn->prepare("SELECT nombre FROM categoria WHERE categoria_id = ?");
    $cat_stmt->bind_param("i", $categoria_id);
    $cat_stmt->execute();
    if ($cat_row = $cat_stmt->get_result()->fetch_assoc()) {
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
    <title><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?> – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/products.css" />
</head>

<body>

    <?php require_once "app/views/layouts/header.php"; ?>

    <main class="page">
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a> › <span><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <header class="products-header">
            <h1 class="page-title"><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
        </header>

        <section class="products-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($producto = $result->fetch_assoc()): ?>
                    <article class="product-card">
                        <a class="product-thumb" href="producto.php?id=<?php echo (int) $producto['producto_id']; ?>">
                            <?php if (!empty($producto['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" />
                            <?php else: ?>
                                <img src="Assets/img/placeholder.jpg" alt="Sin imagen" />
                            <?php endif; ?>
                        </a>
                        <h3 class="product-name">
                            <a href="producto.php?id=<?php echo (int) $producto['producto_id']; ?>">
                                <?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h3>
                        <p class="product-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                        <div class="product-actions">
                            <a class="btn" href="producto.php?id=<?php echo (int) $producto['producto_id']; ?>">
                                Más información
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty">
                    <?php if ($search !== ''): ?>
                        No se encontraron productos para "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>".
                    <?php else: ?>
                        No hay productos en esta categoría.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </section>

        <?php if ($totalPages > 1 && $totalProducts > 0): ?>
            <nav class="seller-pager" aria-label="Paginación">
                <?php
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

    <?php require_once "app/views/layouts/footer.php"; ?>

</body>

</html>