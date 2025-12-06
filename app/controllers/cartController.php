<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../config/connectionController.php";

class CartController
{
    private $connection;

    public function __construct()
    {
        $this->connection = new ConnectionController();
    }

    // Agregar al carrito (si ya existe el producto, solo suma cantidad)
    public function addToCart($usuario_id, $producto_id, $cantidad)
    {
        $conn = $this->connection->connect();

        // Ver si ya existe este producto en el carrito de este usuario
        $query = "SELECT carrito_id, cantidad 
                  FROM carrito 
                  WHERE usuario_usuario_id = ? AND producto_producto_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            // Ya existe → actualizar cantidad
            $nuevoTotal = $row['cantidad'] + $cantidad;
            $update = "UPDATE carrito 
                       SET cantidad = ? 
                       WHERE carrito_id = ?";
            $uStmt = $conn->prepare($update);
            $uStmt->bind_param("ii", $nuevoTotal, $row['carrito_id']);
            $uStmt->execute();
        } else {
            // No existe → insertar nuevo
            $insert = "INSERT INTO carrito 
                       (usuario_usuario_id, producto_producto_id, cantidad) 
                       VALUES (?, ?, ?)";
            $iStmt = $conn->prepare($insert);
            $iStmt->bind_param("iii", $usuario_id, $producto_id, $cantidad);
            $iStmt->execute();
        }
    }

    // Obtener items del carrito para mostrar en carrito.php
    public function getCartItems($usuario_id)
    {
        $conn = $this->connection->connect();

        $query = "SELECT c.carrito_id, c.cantidad,
                         p.producto_id, p.nombre, p.precio, p.imagen
                  FROM carrito c
                  INNER JOIN producto p 
                    ON c.producto_producto_id = p.producto_id
                  WHERE c.usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Obtener número total de productos en carrito (para iconito)
    public function getCartCount($usuario_id)
    {
        $conn = $this->connection->connect();

        $query = "SELECT COALESCE(SUM(cantidad),0) AS total
                  FROM carrito
                  WHERE usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return (int) $result['total'];
    }

    // Eliminar un ítem del carrito
    public function removeItem($carrito_id, $usuario_id)
    {
        $conn = $this->connection->connect();

        $query = "DELETE FROM carrito 
                  WHERE carrito_id = ? AND usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $carrito_id, $usuario_id);
        $stmt->execute();
    }

    // Vaciar carrito
    public function clearCart($usuario_id)
    {
        $conn = $this->connection->connect();

        $query = "DELETE FROM carrito WHERE usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
    }

    //Modificar cantidades
    public function updateQuantity($carrito_id, $usuario_id, $cantidad)
    {
        $conn = $this->connection->connect();

        // Aseguramos mínimo 1
        if ($cantidad < 1) {
            $cantidad = 1;
        }

        $query = "UPDATE carrito
              SET cantidad = ?
              WHERE carrito_id = ? AND usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $cantidad, $carrito_id, $usuario_id);
        $stmt->execute();
    }

    //finalizar compra
    public function checkout($usuario_id, $nombre, $direccion, $ciudad, $telefono)
    {
        $conn = $this->connection->connect();

        // 1) Traer items del carrito con stock y precio
        $query = "SELECT 
                c.carrito_id,
                c.cantidad,
                p.producto_id,
                p.precio,
                p.stock
              FROM carrito c
              INNER JOIN producto p 
                    ON c.producto_producto_id = p.producto_id
              WHERE c.usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);

        if (count($items) === 0) {
            return "empty";
        }

        // 2) Validar stock y calcular total
        $total = 0;
        foreach ($items as $item) {
            if ($item['cantidad'] > $item['stock']) {
                return "stock"; // stock insuficiente
            }
            $total += $item['cantidad'] * $item['precio'];
        }

        // 3) Iniciar transacción
        $conn->begin_transaction();

        try {
            // 4) Insertar compra
            $qCompra = "INSERT INTO compra 
                    (total, nombre_envio, direccion_envio, ciudad_envio, telefono_envio, usuario_usuario_id)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmtCompra = $conn->prepare($qCompra);
            $stmtCompra->bind_param(
                "dssssi",
                $total,
                $nombre,
                $direccion,
                $ciudad,
                $telefono,
                $usuario_id
            );
            $stmtCompra->execute();
            $compra_id = $conn->insert_id;

            // 5) Insertar detalles y actualizar stock
            $qDetalle = "INSERT INTO detalle_compra
                        (cantidad, precio_unitario, subtotal, compra_compra_id, producto_producto_id)
                     VALUES (?, ?, ?, ?, ?)";
            $stmtDetalle = $conn->prepare($qDetalle);

            $qStock = "UPDATE producto
                   SET stock = stock - ?
                   WHERE producto_id = ?";
            $stmtStock = $conn->prepare($qStock);

            foreach ($items as $item) {
                $cantidad = (int) $item['cantidad'];
                $precio = (float) $item['precio'];
                $subtotal = $cantidad * $precio;
                $producto_id = (int) $item['producto_id'];

                $stmtDetalle->bind_param(
                    "iddii",
                    $cantidad,
                    $precio,
                    $subtotal,
                    $compra_id,
                    $producto_id
                );
                $stmtDetalle->execute();

                $stmtStock->bind_param("ii", $cantidad, $producto_id);
                $stmtStock->execute();
            }

            // 6) Vaciar carrito
            $this->clearCart($usuario_id);

            // 7) Confirmamos
            $conn->commit();

            return "success";

        } catch (Exception $e) {
            $conn->rollback();
            return "error";
        }
    }

}

/* ===========================
 * ROUTER: acciones por POST
 * =========================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION['usuario_id'])) {
        // Si no está logueado lo mandamos a login
        header("Location: ../../login.php?error=login_required");
        exit;
    }

    $cart = new CartController();
    $usuario_id = (int) $_SESSION['usuario_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {

        $producto_id = isset($_POST['producto_id']) ? (int) $_POST['producto_id'] : 0;
        $cantidad = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 1;
        if ($cantidad < 1)
            $cantidad = 1;

        $cart->addToCart($usuario_id, $producto_id, $cantidad);

        // Redirigir de vuelta a la página desde donde se mandó el form
        $redirect = isset($_SERVER['HTTP_REFERER'])
            ? $_SERVER['HTTP_REFERER']
            : "../../productos.php";

        header("Location: " . $redirect);
        exit;
    } elseif ($action === 'remove_item') {

        $carrito_id = isset($_POST['carrito_id']) ? (int) $_POST['carrito_id'] : 0;
        $cart->removeItem($carrito_id, $usuario_id);

        header("Location: ../../carrito.php?msg=removed");
        exit;

    } elseif ($action === 'clear_cart') {

        $cart->clearCart($usuario_id);

        header("Location: ../../carrito.php?msg=cleared");
        exit;
    } elseif ($action === 'update_qty') {

        $carrito_id = isset($_POST['carrito_id']) ? (int) $_POST['carrito_id'] : 0;
        $cantidad = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 1;

        $cart->updateQuantity($carrito_id, $usuario_id, $cantidad);

        header("Location: ../../carrito.php?msg=updated");
        exit;
    } elseif ($action === 'checkout') {

        $nombre = $_POST['nombre'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $telefono = $_POST['telefono'] ?? '';

        $result = $cart->checkout($usuario_id, $nombre, $direccion, $ciudad, $telefono);

        if ($result === "success") {
            header("Location: ../../confirmar-compra.php?msg=success");
        } elseif ($result === "stock") {
            header("Location: ../../confirmar-compra.php?msg=stock");
        } else {
            header("Location: ../../confirmar-compra.php?msg=error");
        }
        exit;
    }
}