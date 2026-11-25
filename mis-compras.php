<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

require_once __DIR__ . "/app/config/connectionController.php";

$usuario_id = (int) $_SESSION['usuario_id'];
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
$compras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <link rel="stylesheet" href="Assets/styles/global.css?v=2">
    <link rel="stylesheet" href="Assets/styles/purchases.css">
</head>

<body>
    <?php require_once "app/views/layouts/header.php"; ?>

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
                    $compraId = (int) $compra['compra_id'];
                    $fecha = date("d/m/Y H:i", strtotime($compra['fecha']));

                    $stmtItems->bind_param("i", $compraId);
                    $stmtItems->execute();
                    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <article class="order-card">
                        <header class="order-head">
                            <div class="cell">
                                <span class="label">Pedido realizado</span>
                                <strong><?php echo htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?></strong>
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
                            <a class="btn-track" href="rastreo.php?id=<?php echo $compraId; ?>" style="background: #586a58; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: bold; text-align: center; margin-left: 10px; display: inline-block;">
                                Rastrear pedido
                            </a>
                        </header>

                        <ul class="order-items">
                            <?php foreach ($items as $it): ?>
                                <li class="item">
                                    <img src="<?php echo htmlspecialchars($it['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars($it['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="meta">
                                        <p class="name"><?php echo htmlspecialchars($it['nombre'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <small class="qty">Cantidad: <?php echo (int) $it['cantidad']; ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
</body>

</html>