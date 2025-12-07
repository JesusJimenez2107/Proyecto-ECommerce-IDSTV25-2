<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debe estar logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

require_once "app/config/connectionController.php";

$usuario_id = (int) $_SESSION['usuario_id'];
$compra_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';

if ($compra_id <= 0) {
    echo "ID de compra inválido.";
    exit;
}

$conn = (new ConnectionController())->connect();

/* ============================
 * 1) OBTENER DATOS DE LA COMPRA
 *    - Cliente: solo sus compras
 *    - Admin: cualquier compra
 * ============================ */
if ($isAdmin) {
    // Admin puede ver cualquier compra
    $sqlCompra = "SELECT c.compra_id, c.fecha, c.total,
                         c.nombre_envio, c.direccion_envio,
                         c.ciudad_envio, c.telefono_envio,
                         u.nombre AS nombre_usuario,
                         u.apellidos AS apellidos_usuario,
                         u.email AS email_usuario
                  FROM compra c
                  INNER JOIN usuario u
                      ON c.usuario_usuario_id = u.usuario_id
                  WHERE c.compra_id = ?";
    $stmt = $conn->prepare($sqlCompra);
    $stmt->bind_param("i", $compra_id);

} else {
    // Cliente: solo si la compra es suya
    $sqlCompra = "SELECT c.compra_id, c.fecha, c.total,
                         c.nombre_envio, c.direccion_envio,
                         c.ciudad_envio, c.telefono_envio,
                         u.nombre AS nombre_usuario,
                         u.apellidos AS apellidos_usuario,
                         u.email AS email_usuario
                  FROM compra c
                  INNER JOIN usuario u
                      ON c.usuario_usuario_id = u.usuario_id
                  WHERE c.compra_id = ? AND c.usuario_usuario_id = ?";
    $stmt = $conn->prepare($sqlCompra);
    $stmt->bind_param("ii", $compra_id, $usuario_id);
}

$stmt->execute();
$res = $stmt->get_result();
$compra = $res->fetch_assoc();
$stmt->close();

if (!$compra) {
    echo $isAdmin
        ? "No se encontró la compra."
        : "No se encontró la compra o no pertenece a tu cuenta.";
    exit;
}

// Formatear fecha
$fechaCompra = date("d/m/Y H:i", strtotime($compra['fecha']));

/* ============================
 * 2) OBTENER ITEMS DE LA COMPRA
 * ============================ */
$sqlItems = "SELECT d.cantidad,
                    d.precio_unitario,
                    d.subtotal,
                    p.nombre
             FROM detalle_compra d
             INNER JOIN producto p
                 ON d.producto_producto_id = p.producto_id
             WHERE d.compra_compra_id = ?";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $compra_id);
$stmtItems->execute();
$resItems = $stmtItems->get_result();
$items = $resItems->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Ticket #<?php echo $compra['compra_id']; ?> – Raíz Viva</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 20px;
        }

        .ticket {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 20px 24px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 16px;
        }

        .ticket-header h1 {
            margin: 6px 0 0;
            font-size: 22px;
        }

        .ticket-header small {
            color: #555;
        }

        .ticket-info {
            margin-bottom: 16px;
            font-size: 14px;
        }

        .ticket-info h2 {
            font-size: 16px;
            margin: 0 0 6px;
        }

        .ticket-info p {
            margin: 0 0 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 16px;
            font-size: 14px;
        }

        th,
        td {
            border-bottom: 1px solid #e0e0e0;
            padding: 6px 4px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
            font-weight: 700;
        }

        tfoot td {
            border-top: 2px solid #333;
            font-weight: 700;
        }

        .ticket-footer {
            text-align: center;
            font-size: 13px;
            color: #555;
            margin-top: 10px;
        }

        .actions {
            text-align: center;
            margin-bottom: 15px;
        }

        .btn-print {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            border: none;
            background: #4caf50;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-print:hover {
            background: #43a047;
        }

        /* Para impresión: ocultar botón en papel */
        @media print {
            .actions {
                display: none;
            }

            body {
                background: #ffffff;
                padding: 0;
            }

            .ticket {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }
        }
    </style>
</head>

<body>

    <div class="ticket">
        <div class="ticket-header">
            <!-- Puedes usar también:
                 <img src="Assets/img/logo.png" alt="Raíz Viva" height="40">
            -->
            <h1>Raíz Viva</h1>
            <small>Ticket de compra #<?php echo $compra['compra_id']; ?></small><br>
            <small>Fecha: <?php echo $fechaCompra; ?></small>
        </div>

        <!-- Datos del usuario (útil para admin) -->
        <div class="ticket-info">
            <h2>Datos del cliente</h2>
            <p><strong>Nombre:</strong>
                <?php echo htmlspecialchars($compra['nombre_usuario'] . ' ' . $compra['apellidos_usuario']); ?>
            </p>
            <p><strong>Correo:</strong>
                <?php echo htmlspecialchars($compra['email_usuario']); ?>
            </p>
        </div>

        <div class="ticket-info">
            <h2>Datos de envío</h2>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($compra['nombre_envio']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($compra['direccion_envio']); ?></p>
            <p><strong>Ciudad:</strong> <?php echo htmlspecialchars($compra['ciudad_envio']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($compra['telefono_envio']); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cant.</th>
                    <th style="text-align:right;">P. unitario</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                        <td style="text-align:center;"><?php echo (int) $it['cantidad']; ?></td>
                        <td style="text-align:right;">
                            $<?php echo number_format($it['precio_unitario'], 2); ?>
                        </td>
                        <td style="text-align:right;">
                            $<?php echo number_format($it['subtotal'], 2); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;">Total:</td>
                    <td style="text-align:right;">
                        $<?php echo number_format($compra['total'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="ticket-footer">
            <p>¡Gracias por tu compra! 🌿</p>
            <p>Puedes imprimir este ticket o guardarlo como PDF.</p>
        </div>
    </div>

    <div class="actions">
        <button class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
    </div>

</body>

</html>