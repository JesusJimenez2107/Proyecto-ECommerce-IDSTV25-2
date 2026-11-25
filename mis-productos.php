<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

require_once __DIR__ . "/app/config/connectionController.php";

$conn = (new ConnectionController())->connect();
$usuario_id = (int) $_SESSION['usuario_id'];

$perPage = 9;
$page = 1;

if (isset($_GET['page']) && ctype_digit($_GET['page']) && (int) $_GET['page'] > 0) {
    $page = (int) $_GET['page'];
}

$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM producto WHERE usuario_id = ?");
$stmtCount->bind_param("i", $usuario_id);
$stmtCount->execute();
$totalProducts = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

$totalPages = $totalProducts > 0 ? ceil($totalProducts / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare(
    "SELECT producto_id, nombre, precio, stock, imagen 
     FROM producto 
     WHERE usuario_id = ?
     ORDER BY producto_id DESC
     LIMIT ?, ?"
);
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
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/mis-productos.css" />
</head>

<body>
    <?php require_once "app/views/layouts/header.php"; ?>

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

        <section class="seller-grid" aria-label="Listado de productos">
            <?php if ($totalProducts === 0): ?>
                <p>No tienes productos registrados todavía.</p>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <article class="seller-card">
                        <a class="thumb" href="editar-producto.php?id=<?php echo (int) $row['producto_id']; ?>">
                            <img src="<?php echo htmlspecialchars($row['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                        </a>

                        <h3 class="item-title"><?php echo htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>

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
                            <form action="app/controllers/productController.php" method="post" class="inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="producto_id" value="<?php echo (int) $row['producto_id']; ?>">
                                <button type="submit" class="btn-danger">Eliminar</button>
                            </form>

                            <a href="editar-producto.php?id=<?php echo (int) $row['producto_id']; ?>" class="btn-outline">Editar</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>

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

    <?php require_once "app/views/layouts/footer.php"; ?>
</body>

</html>