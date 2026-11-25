<?php
require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/connectionController.php";

class CartController
{
    private ConnectionController $connection;

    public function __construct()
    {
        $this->connection = new ConnectionController();
    }

    public function addToCart(int $usuario_id, int $producto_id, int $cantidad): void
    {
        $conn = $this->connection->connect();

        $sStmt = $conn->prepare("SELECT stock FROM producto WHERE producto_id = ?");
        $sStmt->bind_param("i", $producto_id);
        $sStmt->execute();
        $resStock = $sStmt->get_result()->fetch_assoc();
        $stockDisponible = $resStock ? (int) $resStock['stock'] : 0;

        if ($stockDisponible <= 0) {
            return;
        }

        $stmt = $conn->prepare("SELECT carrito_id, cantidad FROM carrito WHERE usuario_usuario_id = ? AND producto_producto_id = ?");
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $nuevoTotal = min($row['cantidad'] + $cantidad, $stockDisponible);
            $uStmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE carrito_id = ?");
            $uStmt->bind_param("ii", $nuevoTotal, $row['carrito_id']);
            $uStmt->execute();
        } else {
            $cantidadFinal = min($cantidad, $stockDisponible);
            $iStmt = $conn->prepare("INSERT INTO carrito (usuario_usuario_id, producto_producto_id, cantidad) VALUES (?, ?, ?)");
            $iStmt->bind_param("iii", $usuario_id, $producto_id, $cantidadFinal);
            $iStmt->execute();
        }
    }

    public function getCartItems(int $usuario_id): array
    {
        $conn = $this->connection->connect();
        $query = "SELECT c.carrito_id, c.cantidad, p.producto_id, p.nombre, p.precio, p.imagen, p.stock
                  FROM carrito c
                  INNER JOIN producto p ON c.producto_producto_id = p.producto_id
                  WHERE c.usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCartCount(int $usuario_id): int
    {
        $conn = $this->connection->connect();
        $stmt = $conn->prepare("SELECT COALESCE(SUM(cantidad), 0) AS total FROM carrito WHERE usuario_usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int) ($result['total'] ?? 0);
    }

    public function removeItem(int $carrito_id, int $usuario_id): void
    {
        $conn = $this->connection->connect();
        $stmt = $conn->prepare("DELETE FROM carrito WHERE carrito_id = ? AND usuario_usuario_id = ?");
        $stmt->bind_param("ii", $carrito_id, $usuario_id);
        $stmt->execute();
    }

    public function clearCart(int $usuario_id): void
    {
        $conn = $this->connection->connect();
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
    }

    public function updateQuantity(int $carrito_id, int $usuario_id, int $cantidad): void
    {
        $conn = $this->connection->connect();
        $cantidad = max(1, $cantidad);

        $qStock = "SELECT p.stock FROM carrito c INNER JOIN producto p ON c.producto_producto_id = p.producto_id WHERE c.carrito_id = ? AND c.usuario_usuario_id = ?";
        $stmtStock = $conn->prepare($qStock);
        $stmtStock->bind_param("ii", $carrito_id, $usuario_id);
        $stmtStock->execute();
        $resStock = $stmtStock->get_result()->fetch_assoc();

        if ($resStock && $cantidad > $resStock['stock']) {
            $cantidad = (int) $resStock['stock'];
        }

        $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE carrito_id = ? AND usuario_usuario_id = ?");
        $stmt->bind_param("iii", $cantidad, $carrito_id, $usuario_id);
        $stmt->execute();
    }

    public function checkout($usuario_id, $nombre, $direccion, $ciudad, $telefono, $correo, $titular, $tarjeta, $dir_facturacion, $ciudad_fact, $cp_fact, $pais_fact): string
    {
        $conn = $this->connection->connect();
        $conn->begin_transaction();

        try {
            // Bloqueo FOR UPDATE dentro de la transacción para evitar condiciones de carrera
            $query = "SELECT c.carrito_id, c.cantidad, p.producto_id, p.precio, p.stock
                      FROM carrito c
                      INNER JOIN producto p ON c.producto_producto_id = p.producto_id
                      WHERE c.usuario_usuario_id = ? FOR UPDATE";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if (count($items) === 0) {
                $conn->rollback();
                return "empty";
            }

            $total = 0;
            foreach ($items as $item) {
                if ($item['cantidad'] > $item['stock']) {
                    $conn->rollback();
                    return "stock";
                }
                $total += $item['cantidad'] * $item['precio'];
            }

            $qCompra = "INSERT INTO compra (total, nombre_envio, direccion_envio, ciudad_envio, telefono_envio, usuario_usuario_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtCompra = $conn->prepare($qCompra);
            $stmtCompra->bind_param("dssssi", $total, $nombre, $direccion, $ciudad, $telefono, $usuario_id);
            $stmtCompra->execute();
            $compra_id = $conn->insert_id;

            $stmtDetalle = $conn->prepare("INSERT INTO detalle_compra (cantidad, precio_unitario, subtotal, compra_compra_id, producto_producto_id) VALUES (?, ?, ?, ?, ?)");
            $stmtReduceStock = $conn->prepare("UPDATE producto SET stock = stock - ? WHERE producto_id = ?");

            foreach ($items as $item) {
                $cantidad = (int) $item['cantidad'];
                $precio = (float) $item['precio'];
                $subtotal = $cantidad * $precio;
                $producto_id = (int) $item['producto_id'];

                $stmtDetalle->bind_param("iddii", $cantidad, $precio, $subtotal, $compra_id, $producto_id);
                $stmtDetalle->execute();

                $stmtReduceStock->bind_param("ii", $cantidad, $producto_id);
                $stmtReduceStock->execute();
            }

            $tarjetaLimpia = str_replace(' ', '', $tarjeta);
            $ultimos_digitos = substr($tarjetaLimpia, -4);

            $qPago = "INSERT INTO pago (compra_compra_id, correo, titular_tarjeta, ultimos_digitos, dir_facturacion, ciudad_fact, cp_fact, pais_fact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtPago = $conn->prepare($qPago);
            $stmtPago->bind_param("isssssss", $compra_id, $correo, $titular, $ultimos_digitos, $dir_facturacion, $ciudad_fact, $cp_fact, $pais_fact);
            $stmtPago->execute();

            $this->clearCart($usuario_id);
            $conn->commit();
            return "success";
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error en checkout: " . $e->getMessage());
            return "error";
        }
    }

    /**
     * Cancelación de pedido blindada contra IDOR y protegida con transacción ACID
     */
    public function cancelOrder(int $compra_id, int $usuario_id): bool
    {
        if ($compra_id <= 0) {
            return false;
        }

        $conn = $this->connection->connect();
        $conn->begin_transaction();

        try {
            // 1. Verificar PRIMERO que la compra pertenezca al usuario autenticado (Mitigación IDOR)
            $stmtCheck = $conn->prepare("SELECT compra_id FROM compra WHERE compra_id = ? AND usuario_usuario_id = ? FOR UPDATE");
            $stmtCheck->bind_param("ii", $compra_id, $usuario_id);
            $stmtCheck->execute();
            if (!$stmtCheck->get_result()->fetch_assoc()) {
                $conn->rollback();
                return false;
            }

            // 2. Obtener detalles y restaurar stock
            $stmtDetalles = $conn->prepare("SELECT producto_producto_id, cantidad FROM detalle_compra WHERE compra_compra_id = ?");
            $stmtDetalles->bind_param("i", $compra_id);
            $stmtDetalles->execute();
            $resultado = $stmtDetalles->get_result();

            $stmtRestaurar = $conn->prepare("UPDATE producto SET stock = stock + ? WHERE producto_id = ?");
            while ($fila = $resultado->fetch_assoc()) {
                $stmtRestaurar->bind_param("ii", $fila['cantidad'], $fila['producto_producto_id']);
                $stmtRestaurar->execute();
            }

            // 3. Eliminar registros dependientes en orden referencial
            $stmtBorrarPago = $conn->prepare("DELETE FROM pago WHERE compra_compra_id = ?");
            $stmtBorrarPago->bind_param("i", $compra_id);
            $stmtBorrarPago->execute();

            $stmtBorrarDetalles = $conn->prepare("DELETE FROM detalle_compra WHERE compra_compra_id = ?");
            $stmtBorrarDetalles->bind_param("i", $compra_id);
            $stmtBorrarDetalles->execute();

            $stmtBorrarCompra = $conn->prepare("DELETE FROM compra WHERE compra_id = ? AND usuario_usuario_id = ?");
            $stmtBorrarCompra->bind_param("ii", $compra_id, $usuario_id);
            $stmtBorrarCompra->execute();

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error al cancelar orden: " . $e->getMessage());
            return false;
        }
    }
}

/* ===========================
 * ROUTER: acciones por POST
 * =========================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../../login.php?error=login_required");
        exit();
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Error de seguridad: Token CSRF inválido.");
    }

    $cart = new CartController();
    $usuario_id = (int) $_SESSION['usuario_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $producto_id = (int) ($_POST['producto_id'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
        $cart->addToCart($usuario_id, $producto_id, $cantidad);

        // Validar HTTP_REFERER local (evita Open Redirect)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $parsedHost = parse_url($referer, PHP_URL_HOST);
        $redirect = ($referer && ($parsedHost === null || $parsedHost === $_SERVER['HTTP_HOST']))
            ? $referer
            : "../../productos.php";

        header("Location: " . $redirect);
        exit();
    } elseif ($action === 'remove_item') {
        $cart->removeItem((int) ($_POST['carrito_id'] ?? 0), $usuario_id);
        header("Location: ../../carrito.php?msg=removed");
        exit();
    } elseif ($action === 'clear_cart') {
        $cart->clearCart($usuario_id);
        header("Location: ../../carrito.php?msg=cleared");
        exit();
    } elseif ($action === 'update_qty') {
        $cart->updateQuantity((int) ($_POST['carrito_id'] ?? 0), $usuario_id, (int) ($_POST['cantidad'] ?? 1));
        header("Location: ../../carrito.php?msg=updated");
        exit();
    } elseif ($action === 'checkout') {
        $result = $cart->checkout(
            $usuario_id,
            trim($_POST['nombre'] ?? ''),
            trim($_POST['direccion'] ?? ''),
            trim($_POST['ciudad'] ?? ''),
            trim($_POST['telefono'] ?? ''),
            trim($_POST['correo'] ?? ''),
            trim($_POST['titular'] ?? ''),
            trim($_POST['tarjeta'] ?? ''),
            trim($_POST['dir_facturacion'] ?? ''),
            trim($_POST['ciudad_fact'] ?? ''),
            trim($_POST['cp_fact'] ?? ''),
            trim($_POST['pais'] ?? '')
        );

        if ($result === "success") {
            header("Location: ../../pago.php?msg=success");
        } elseif ($result === "stock") {
            header("Location: ../../pago.php?msg=stock");
        } else {
            header("Location: ../../pago.php?msg=error");
        }
        exit();
    } elseif ($action === 'cancel_order') {
        $ok = $cart->cancelOrder((int) ($_POST['compra_id'] ?? 0), $usuario_id);
        header("Location: ../../mis-compras.php?msg=" . ($ok ? "canceled" : "error"));
        exit();
    }
}