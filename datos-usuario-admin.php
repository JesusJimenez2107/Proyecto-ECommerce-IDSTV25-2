<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}
$usuarioId = (int) $_GET['id'];

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$stmtUser = $conn->prepare(
    "SELECT usuario_id, nombre, apellidos, email, direccion, telefono, rol 
     FROM usuario 
     WHERE usuario_id = ?"
);
$stmtUser->bind_param("i", $usuarioId);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$user) {
    header("Location: usuarios.php");
    exit();
}

$stmtOrders = $conn->prepare(
    "SELECT compra_id, fecha, total, nombre_envio, direccion_envio, ciudad_envio, telefono_envio
     FROM compra 
     WHERE usuario_usuario_id = ?
     ORDER BY fecha DESC"
);
$stmtOrders->bind_param("i", $usuarioId);
$stmtOrders->execute();
$resOrders = $stmtOrders->get_result();

$orders = [];
while ($row = $resOrders->fetch_assoc()) {
    $orders[] = $row;
}
$stmtOrders->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel Admin – Datos de Usuario</title>
    <link rel="stylesheet" href="Assets/styles/global.css">
    <link rel="stylesheet" href="Assets/styles/admin-user-detail.css">
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page user-detail-page">
        <nav class="breadcrumb">
            <a href="panel-admin.php">Panel Administración</a> ›
            <a href="usuarios.php">Usuarios</a> ›
            <span>Datos Usuario</span>
        </nav>

        <section class="user-block">
            <header class="user-block__header">
                <h1 class="user-block__title">Datos personales</h1>
                <a href="datos-usuario-admin-editar.php?id=<?php echo (int) $user['usuario_id']; ?>"
                    class="btn btn-secondary">Editar</a>
            </header>

            <div class="user-data">
                <div class="user-field">
                    <span class="user-label">Nombre</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Apellido(s)</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['apellidos'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Correo</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Teléfono</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['telefono'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="user-field user-field--wide">
                    <span class="user-label">Dirección</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['direccion'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Rol</span>
                    <p class="user-value"><?php echo ucfirst(htmlspecialchars($user['rol'], ENT_QUOTES, 'UTF-8')); ?></p>
                </div>
            </div>
        </section>

        <section class="user-block">
            <header class="user-block__header user-block__header--sub">
                <h2 class="user-block__title">Compras</h2>

                <form class="orders-search" method="GET">
                    <input type="hidden" name="id" value="<?php echo $usuarioId; ?>">
                    <label>Buscar por ID</label>
                    <input type="text" name="order_id" placeholder="ID compra"
                        value="<?php echo isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <button type="submit" class="btn btn-secondary">Buscar</button>
                </form>
            </header>

            <div class="orders-list">
                <?php
                $filter = isset($_GET['order_id']) && ctype_digit($_GET['order_id'])
                    ? (int) $_GET['order_id']
                    : null;

                $printed = false;

                foreach ($orders as $order):
                    if ($filter !== null && $filter !== (int) $order['compra_id']) {
                        continue;
                    }

                    $printed = true;
                    $compraId = (int) $order['compra_id'];
                    $fecha = $order['fecha'];

                    $stmtDet = $conn->prepare(
                        "SELECT dc.cantidad,
                                dc.precio_unitario,
                                dc.subtotal,
                                p.nombre,
                                p.imagen
                         FROM detalle_compra dc
                         INNER JOIN producto p ON p.producto_id = dc.producto_producto_id
                         WHERE dc.compra_compra_id = ?"
                    );
                    $stmtDet->bind_param("i", $compraId);
                    $stmtDet->execute();
                    $resDet = $stmtDet->get_result();

                    $items = [];
                    while ($i = $resDet->fetch_assoc()) {
                        $items[] = $i;
                    }
                    $stmtDet->close();
                    ?>

                    <article class="order-card">
                        <header class="order-card__header">
                            <div class="order-meta">
                                <span class="order-meta__label">Fecha</span>
                                <span class="order-meta__value"><?php echo htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>

                            <div class="order-meta">
                                <span class="order-meta__label">Total</span>
                                <span class="order-meta__value">$<?php echo number_format($order['total'], 2); ?></span>
                            </div>

                            <div class="order-meta">
                                <span class="order-meta__label">Pedido #</span>
                                <span class="order-meta__value"><?php echo $compraId; ?></span>
                            </div>

                            <div class="order-meta">
                                <span class="order-meta__label">Ciudad</span>
                                <span class="order-meta__value"><?php echo htmlspecialchars($order['ciudad_envio'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>

                            <div class="order-card__actions">
                                <button type="button" class="btn btn-danger open-delete-modal"
                                    data-compra="<?php echo $compraId; ?>">
                                    Eliminar
                                </button>

                                <a href="ticket-compra.php?id=<?php echo $compraId; ?>" class="btn btn-primary"
                                    target="_blank">
                                    Ticket
                                </a>
                            </div>
                        </header>

                        <div class="order-card__body">
                            <?php foreach ($items as $item): ?>
                                <figure class="order-product">
                                    <?php if (!empty($item['imagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php endif; ?>
                                    <figcaption>
                                        <p class="order-product__name"><?php echo htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="order-product__qty">Cantidad: <?php echo (int) $item['cantidad']; ?></p>
                                        <p class="order-product__subtotal">Subtotal: $<?php echo number_format($item['subtotal'], 2); ?></p>
                                    </figcaption>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </article>

                <?php endforeach;

                if (!$printed): ?>
                    <p>No hay compras registradas para este usuario o no coincide el ID.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>

    <div class="modal-backdrop" id="modalEliminarCompra" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">Eliminar compra</h2>
            <p class="modal-text">
                ¿Estás seguro de eliminar esta compra?<br>
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
            <form id="formEliminarCompra" method="POST" action="admin_eliminar_compra.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="compra_id" id="inputCompraId">
                <input type="hidden" name="usuario_id" value="<?php echo $usuarioId; ?>">

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelarEliminarCompra">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modal = document.getElementById("modalEliminarCompra");
            const cancelar = document.getElementById("cancelarEliminarCompra");
            const inputCompraId = document.getElementById("inputCompraId");

            document.querySelectorAll(".open-delete-modal").forEach(btn => {
                btn.addEventListener("click", () => {
                    const compraId = btn.getAttribute("data-compra");
                    inputCompraId.value = compraId;
                    modal.removeAttribute("hidden");
                });
            });

            cancelar.addEventListener("click", () => {
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