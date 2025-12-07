<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo admins pueden entrar
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$adminId = (int) $_SESSION['usuario_id'];

// Validar id del usuario a consultar
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}
$usuarioId = (int) $_GET['id'];

// ================== CONEXIÓN BD ==================
require_once "app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

// ================== OBTENER DATOS DEL USUARIO ==================
$stmtUser = $conn->prepare(
    "SELECT usuario_id, nombre, apellidos, email, direccion, telefono, rol 
     FROM usuario 
     WHERE usuario_id = ?"
);
$stmtUser->bind_param("i", $usuarioId);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$user = $resUser->fetch_assoc();
$stmtUser->close();

if (!$user) {
    header("Location: usuarios.php");
    exit();
}

// ================== OBTENER COMPRAS DEL USUARIO ==================
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

// ====== CONTADOR DEL CARRITO PARA EL HEADER ======
$logged = isset($_SESSION['email']);
$cartCount = 0;

if ($logged) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount($adminId);
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}

$adminName = $_SESSION['nombre'] ?? 'Admin';
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
    <!-- Header Admin -->
    <header class="topbar">
        <div class="topbar__inner">
            <a href="panel-admin.php" class="brand">
                <img src="Assets/img/logo.png" alt="Raíz Viva">
            </a>

            <div class="topbar__admin-center">
                <span class="topbar__admin-title">Panel de administración</span>

                <nav class="topbar__admin-nav">
                    <a href="panel-admin.php">Inicio</a>
                    <a href="usuarios.php">Usuarios</a>
                    <a href="productos-admin.php">Productos</a>
                    <a href="reportes-admin.php">Reportes</a>
                </nav>
            </div>

            <div class="actions">
                <span class="action action--text">
                    <?php echo htmlspecialchars($adminName); ?>
                </span>
            </div>
        </div>
    </header>

    <main class="page user-detail-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="panel-admin.php">Panel Administración</a> ›
            <a href="usuarios.php">Usuarios</a> ›
            <span>Datos Usuario</span>
        </nav>

        <!-- Datos personales -->
        <section class="user-block">
            <header class="user-block__header">
                <h1 class="user-block__title">Datos personales</h1>

                <a href="datos-usuario-admin-editar.php?id=<?php echo $user['usuario_id']; ?>"
                    class="btn btn-secondary">Editar</a>
            </header>

            <div class="user-data">
                <div class="user-field">
                    <span class="user-label">Nombre</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['nombre']); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Apellido(s)</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['apellidos']); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Correo</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Teléfono</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['telefono']); ?></p>
                </div>

                <div class="user-field user-field--wide">
                    <span class="user-label">Dirección</span>
                    <p class="user-value"><?php echo htmlspecialchars($user['direccion']); ?></p>
                </div>

                <div class="user-field">
                    <span class="user-label">Rol</span>
                    <p class="user-value"><?php echo ucfirst($user['rol']); ?></p>
                </div>
            </div>
        </section>

        <!-- Compras -->
        <section class="user-block">
            <header class="user-block__header user-block__header--sub">
                <h2 class="user-block__title">Compras</h2>

                <form class="orders-search" method="GET">
                    <input type="hidden" name="id" value="<?php echo $usuarioId; ?>">
                    <label>Buscar por ID</label>
                    <input type="text" name="order_id" placeholder="ID compra"
                        value="<?php echo isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : ''; ?>">
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
                    if ($filter !== null && $filter !== (int) $order['compra_id'])
                        continue;

                    $printed = true;
                    $compraId = $order['compra_id'];
                    $fecha = $order['fecha'];

                    // ================== CARGAR PRODUCTOS ==================
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
                                <span class="order-meta__value"><?php echo $fecha; ?></span>
                            </div>

                            <div class="order-meta">
                                <span class="order-meta__label">Total</span>
                                <span class="order-meta__value">
                                    $<?php echo number_format($order['total'], 2); ?>
                                </span>
                            </div>

                            <div class="order-meta">
                                <span class="order-meta__label">Pedido #</span>
                                <span class="order-meta__value"><?php echo $compraId; ?></span>
                            </div>

                            <div class="order-meta">
                                <span class="order-meta__label">Ciudad</span>
                                <span class="order-meta__value">
                                    <?php echo htmlspecialchars($order['ciudad_envio']); ?>
                                </span>
                            </div>

                            <div class="order-card__actions">
                                <!-- Eliminar compra -->
                                <button type="button" class="btn btn-danger open-delete-modal"
                                    data-compra="<?php echo $compraId; ?>">
                                    Eliminar
                                </button>

                                <!-- Ver ticket -->
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
                                        <img src="<?php echo htmlspecialchars($item['imagen']); ?>"
                                            alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                    <?php endif; ?>
                                    <figcaption>
                                        <p class="order-product__name">
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </p>
                                        <p class="order-product__qty">
                                            Cantidad: <?php echo $item['cantidad']; ?>
                                        </p>
                                        <p class="order-product__subtotal">
                                            Subtotal: $<?php echo number_format($item['subtotal'], 2); ?>
                                        </p>
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

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>

    <!-- MODAL ELIMINAR COMPRA -->
    <div class="modal-backdrop" id="modalEliminarCompra" hidden>
        <div class="modal-dialog">
            <h2 class="modal-title">Eliminar compra</h2>
            <p class="modal-text">
                ¿Estás seguro de eliminar esta compra?<br>
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
            <form id="formEliminarCompra" method="POST" action="admin_eliminar_compra.php">
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

            // Cerrar modal haciendo click fuera del diálogo
            modal.addEventListener("click", (e) => {
                if (e.target === modal) {
                    modal.setAttribute("hidden", true);
                }
            });
        });
    </script>


</body>

</html>