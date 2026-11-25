<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Error de seguridad: Token CSRF inválido.");
    }

    $productoId = isset($_POST['producto_id']) ? (int) $_POST['producto_id'] : 0;

    if ($productoId <= 0) {
        $error = "Producto inválido.";
    } else {
        $stmt = $conn->prepare("SELECT imagen, imagen_extra1, imagen_extra2 FROM producto WHERE producto_id = ?");
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = "El producto no existe.";
        } else {
            try {
                $stmtDel = $conn->prepare("DELETE FROM producto WHERE producto_id = ?");
                $stmtDel->bind_param("i", $productoId);

                if ($stmtDel->execute() && $stmtDel->affected_rows > 0) {
                    foreach (['imagen', 'imagen_extra1', 'imagen_extra2'] as $campo) {
                        if (!empty($row[$campo]) && file_exists(__DIR__ . "/" . $row[$campo])) {
                            @unlink(__DIR__ . "/" . $row[$campo]);
                        }
                    }
                    $mensaje = "Producto eliminado correctamente.";
                } else {
                    $error = "No se pudo eliminar el producto.";
                }
                $stmtDel->close();
            } catch (mysqli_sql_exception $e) {
                $error = "No se pudo eliminar el producto porque está asociado a compras registradas.";
            }
        }
    }
}

$buscarId = isset($_GET['buscar_id']) ? trim($_GET['buscar_id']) : '';

if ($buscarId !== '' && ctype_digit($buscarId)) {
    $stmt = $conn->prepare(
        "SELECT p.producto_id, p.nombre, p.precio, p.stock, c.nombre AS categoria
         FROM producto p
         LEFT JOIN categoria c ON c.categoria_id = p.categoria_categoria_id
         WHERE p.producto_id = ?"
    );
    $idBuscarInt = (int) $buscarId;
    $stmt->bind_param("i", $idBuscarInt);
    $stmt->execute();
    $productosResult = $stmt->get_result();
    $stmt->close();
} else {
    $productosResult = $conn->query(
        "SELECT p.producto_id, p.nombre, p.precio, p.stock, c.nombre AS categoria
         FROM producto p
         LEFT JOIN categoria c ON c.categoria_id = p.categoria_categoria_id
         ORDER BY p.producto_id ASC"
    );
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel Admin – Productos</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/admin-usuarios.css" />
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Panel Administración</a>
            <span>›</span>
            <span>Productos</span>
        </nav>

        <header class="au-header">
            <h1>PRODUCTOS</h1>
        </header>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="au-toolbar" aria-label="Búsqueda de productos">
            <form class="au-search-group" method="GET" action="productos-admin.php">
                <label for="buscarId" class="au-label">Buscar por ID</label>
                <div class="au-search">
                    <input id="buscarId" name="buscar_id" type="text" placeholder="Ej. 3"
                        value="<?php echo htmlspecialchars($buscarId, ENT_QUOTES, 'UTF-8'); ?>" />
                    <button type="submit" class="btn-primary">Buscar</button>
                </div>
            </form>

            <a href="agregar-producto-admin.php">
                <button type="button" class="btn-secondary">Añadir producto</button>
            </a>
        </section>

        <section class="au-table" aria-label="Listado de productos">
            <div class="au-row au-row--head">
                <div class="au-col au-col-id">ID</div>
                <div class="au-col au-col-name">Nombre</div>
                <div class="au-col au-col-email">Categoría</div>
                <div class="au-col au-col-actions">Acciones</div>
            </div>

            <?php if ($productosResult && $productosResult->num_rows > 0): ?>
                <?php while ($p = $productosResult->fetch_assoc()): ?>
                    <div class="au-row">
                        <div class="au-col au-col-id"><?php echo (int) $p['producto_id']; ?></div>

                        <div class="au-col au-col-name">
                            <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            <span style="display:block;font-size:12px;color:#555;">
                                Precio: $<?php echo number_format($p['precio'], 2); ?> · Stock: <?php echo (int) $p['stock']; ?>
                            </span>
                        </div>

                        <div class="au-col au-col-email">
                            <?php echo htmlspecialchars($p['categoria'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <div class="au-col au-col-actions">
                            <button class="icon-btn info" type="button" aria-label="Ver información de producto"
                                onclick="window.location.href='datos-producto-admin.php?id=<?php echo (int) $p['producto_id']; ?>'">
                                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                    <rect x="4" y="4" width="16" height="16" rx="3" fill="currentColor" />
                                    <rect x="7" y="7" width="6" height="2" fill="#FFF8ED" />
                                    <rect x="7" y="11" width="10" height="2" fill="#FFF8ED" />
                                    <rect x="7" y="15" width="8" height="2" fill="#FFF8ED" />
                                </svg>
                            </button>

                            <button type="button" class="icon-btn danger btn-open-delete-product" aria-label="Eliminar producto"
                                data-producto="<?php echo (int) $p['producto_id']; ?>">
                                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                    <path d="M9 4h6l1 2h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M6 6h12l-1 12H7L6 6Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                    <path d="M10 10v6M14 10v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="au-row">
                    <div class="au-col au-col-id">–</div>
                    <div class="au-col au-col-name">No hay productos</div>
                    <div class="au-col au-col-email"></div>
                    <div class="au-col au-col-actions"></div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>

    <div class="modal-backdrop" id="modalEliminarProducto" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">Eliminar producto</h2>
            <p class="modal-text">
                ¿Estás seguro de eliminar este producto?<br>
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
            <form id="formEliminarProducto" method="POST" action="productos-admin.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="producto_id" id="inputProductoEliminar">

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelarEliminarProducto">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modal = document.getElementById("modalEliminarProducto");
            const inputProducto = document.getElementById("inputProductoEliminar");
            const btnCancelar = document.getElementById("cancelarEliminarProducto");

            document.querySelectorAll(".btn-open-delete-product").forEach(btn => {
                btn.addEventListener("click", () => {
                    inputProducto.value = btn.getAttribute("data-producto");
                    modal.removeAttribute("hidden");
                });
            });

            btnCancelar.addEventListener("click", () => {
                modal.setAttribute("hidden", true);
            });

            modal.addEventListener("click", (e) => {
                if (e.target === modal) {
                    modal.setAttribute("hidden", true);
                }
            });
        });
    </script>
</body>

</html>