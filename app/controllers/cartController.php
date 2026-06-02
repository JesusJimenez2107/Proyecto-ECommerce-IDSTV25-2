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

    // Agregar al carrito (Verificando que no sobrepase el stock)
    public function addToCart($usuario_id, $producto_id, $cantidad)
    {
        $conn = $this->connection->connect();

        // 1. Consultar el stock real disponible en la base de datos
        $qStock = "SELECT stock FROM producto WHERE producto_id = ?";
        $sStmt = $conn->prepare($qStock);
        $sStmt->bind_param("i", $producto_id);
        $sStmt->execute();
        $resStock = $sStmt->get_result()->fetch_assoc();
        $stockDisponible = $resStock ? (int) $resStock['stock'] : 0;

        // 2. Ver si ya existe este producto en el carrito de este usuario
        $query = "SELECT carrito_id, cantidad 
                  FROM carrito 
                  WHERE usuario_usuario_id = ? AND producto_producto_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            // Ya existe → sumar la cantidad nueva a la que ya tenía
            $nuevoTotal = $row['cantidad'] + $cantidad;

            // Si la suma de ambos supera el stock, lo topamos al máximo
            if ($nuevoTotal > $stockDisponible) {
                $nuevoTotal = $stockDisponible;
            }

            $update = "UPDATE carrito 
                       SET cantidad = ? 
                       WHERE carrito_id = ?";
            $uStmt = $conn->prepare($update);
            $uStmt->bind_param("ii", $nuevoTotal, $row['carrito_id']);
            $uStmt->execute();
        } else {
            // No existe → insertar nuevo

            // Si intenta meter de golpe más de lo que hay, lo topamos
            if ($cantidad > $stockDisponible) {
                $cantidad = $stockDisponible;
            }

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
                         p.producto_id, p.nombre, p.precio, p.imagen, p.stock
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

    //Modificar cantidades respetando el stock
    public function updateQuantity($carrito_id, $usuario_id, $cantidad)
    {
        $conn = $this->connection->connect();

        // Aseguramos mínimo 1
        if ($cantidad < 1) {
            $cantidad = 1;
        }

        // Consultar el stock máximo disponible de este producto
        $qStock = "SELECT p.stock 
                   FROM carrito c 
                   INNER JOIN producto p ON c.producto_producto_id = p.producto_id 
                   WHERE c.carrito_id = ? AND c.usuario_usuario_id = ?";
        $stmtStock = $conn->prepare($qStock);
        $stmtStock->bind_param("ii", $carrito_id, $usuario_id);
        $stmtStock->execute();
        $resStock = $stmtStock->get_result()->fetch_assoc();

        // Si la cantidad solicitada supera el stock, la topamos al máximo disponible
        if ($resStock && $cantidad > $resStock['stock']) {
            $cantidad = $resStock['stock'];
        }

        // Actualizamos
        $query = "UPDATE carrito
              SET cantidad = ?
              WHERE carrito_id = ? AND usuario_usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $cantidad, $carrito_id, $usuario_id);
        $stmt->execute();
    }

    // FINALIZAR COMPRA Y PROCESAR PAGO
    public function checkout($usuario_id, $nombre, $direccion, $ciudad, $telefono, $correo, $titular, $tarjeta, $dir_facturacion, $ciudad_fact, $cp_fact, $pais_fact)
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
            // 4) Insertar la compra (Datos de envío)
            $qCompra = "INSERT INTO compra 
                    (total, nombre_envio, direccion_envio, ciudad_envio, telefono_envio, usuario_usuario_id)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmtCompra = $conn->prepare($qCompra);
            // Usamos "s" para el total para evitar problemas de casting en DECIMAL/FLOAT
            $stmtCompra->bind_param(
                "sssssi",
                $total,
                $nombre,
                $direccion,
                $ciudad,
                $telefono,
                $usuario_id
            );
            $stmtCompra->execute();
            $compra_id = $conn->insert_id;

            // 5) Insertar detalles (Tus triggers en BD descontarán el stock automáticamente)
            $qDetalle = "INSERT INTO detalle_compra
                        (cantidad, precio_unitario, subtotal, compra_compra_id, producto_producto_id)
                     VALUES (?, ?, ?, ?, ?)";
            $stmtDetalle = $conn->prepare($qDetalle);

            foreach ($items as $item) {
                $cantidad = (int) $item['cantidad'];
                $precio = (float) $item['precio'];
                $subtotal = $cantidad * $precio;
                $producto_id = (int) $item['producto_id'];

                // Usamos 's' para los decimales (precio y subtotal) para mayor seguridad
                $stmtDetalle->bind_param(
                    "issii",
                    $cantidad,
                    $precio,
                    $subtotal,
                    $compra_id,
                    $producto_id
                );
                $stmtDetalle->execute();
            }

            // 6) Registrar el Pago en la nueva tabla
            $tarjetaLimpia = str_replace(' ', '', $tarjeta);
            $ultimos_digitos = substr($tarjetaLimpia, -4);

            $qPago = "INSERT INTO pago 
                     (compra_compra_id, correo, titular_tarjeta, ultimos_digitos, dir_facturacion, ciudad_fact, cp_fact, pais_fact) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtPago = $conn->prepare($qPago);
            $stmtPago->bind_param(
                "isssssss",
                $compra_id,
                $correo,
                $titular,
                $ultimos_digitos,
                $dir_facturacion,
                $ciudad_fact,
                $cp_fact,
                $pais_fact
            );
            $stmtPago->execute();

            // 7) Vaciar carrito
            $this->clearCart($usuario_id);

            // 8) Confirmamos la transacción completa
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

        // 1. Datos de envío (vienen ocultos desde la vista de pago)
        $nombre = $_POST['nombre'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $telefono = $_POST['telefono'] ?? '';

        // 2. Datos de pago (vienen del formulario visible de pago)
        $correo = $_POST['correo'] ?? '';
        $titular = $_POST['titular'] ?? '';
        $tarjeta = $_POST['tarjeta'] ?? '';
        $dir_facturacion = $_POST['dir_facturacion'] ?? '';
        $ciudad_fact = $_POST['ciudad_fact'] ?? '';
        $cp_fact = $_POST['cp_fact'] ?? '';
        $pais = $_POST['pais'] ?? '';

        // Ejecutamos la función con todas las variables
        $result = $cart->checkout(
            $usuario_id,
            $nombre,
            $direccion,
            $ciudad,
            $telefono,
            $correo,
            $titular,
            $tarjeta,
            $dir_facturacion,
            $ciudad_fact,
            $cp_fact,
            $pais
        );

        // Redirigimos a la página de pago para mostrar el resultado (modal)
        if ($result === "success") {
            header("Location: ../../pago.php?msg=success");
        } elseif ($result === "stock") {
            header("Location: ../../pago.php?msg=stock");
        } else {
            header("Location: ../../pago.php?msg=error");
        }
        exit;
    }
}