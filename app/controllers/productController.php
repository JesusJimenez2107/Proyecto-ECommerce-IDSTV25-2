<?php
include __DIR__ . "/../config/connectionController.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ProductController
{
    private $connection;

    public function __construct()
    {
        $this->connection = new ConnectionController();
    }

    /* =========================================================
     * MÉTODOS PÚBLICOS (TIENDA)
     * ========================================================= */

    // Listar productos públicos (opcionalmente filtrando por categoría)
    public function getPublicProducts($categoria_id = null)
    {
        $conn = $this->connection->connect();

        if ($categoria_id) {
            $query = "SELECT p.*
                      FROM producto p
                      WHERE p.estado = 'activo'
                        AND p.categoria_categoria_id = ?
                      ORDER BY p.producto_id DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $categoria_id);
        } else {
            $query = "SELECT p.*
                      FROM producto p
                      WHERE p.estado = 'activo'
                      ORDER BY p.producto_id DESC";
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Obtener un producto público por ID (para producto.php)
    public function getPublicProductById($producto_id)
    {
        $conn = $this->connection->connect();

        $query = "SELECT p.*, c.nombre AS categoria_nombre
                  FROM producto p
                  LEFT JOIN categoria c ON p.categoria_categoria_id = c.categoria_id
                  WHERE p.producto_id = ? AND p.estado = 'activo'
                  LIMIT 1";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /* =========================================================
     * MÉTODOS PARA DUEÑO / ADMIN
     * ========================================================= */

    // Crear producto
    public function createProduct(
        $name,
        $description,
        $price,
        $stock,
        $category_id,
        $imagePath,
        $imagePath2,
        $imagePath3,
        $usuario_id,
        $context = 'user' // 'user' o 'admin'
    ) {
        $conn = $this->connection->connect();

        $query = "INSERT INTO producto 
                (nombre, descripcion, precio, stock, imagen, imagen_extra1, imagen_extra2, 
                 categoria_categoria_id, usuario_id, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')";

        $stmt = $conn->prepare($query);

        $stmt->bind_param(
            "ssdisssii",
            $name,
            $description,
            $price,
            $stock,
            $imagePath,
            $imagePath2,
            $imagePath3,
            $category_id,
            $usuario_id
        );

        if ($stmt->execute()) {
            $isAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') || $context === 'admin';

            if ($isAdmin) {
                header("Location: ../../productos-admin.php?msg=created");
            } else {
                header("Location: ../../mis-productos.php?msg=created");
            }
            exit();
        } else {
            echo "Error al guardar: " . $stmt->error;
        }
    }

    // Eliminar producto (user o admin)
    public function deleteProduct($producto_id, $usuario_id, $context = 'user')
    {
        $conn = $this->connection->connect();
        $isAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') || $context === 'admin';

        // 1) Obtener info de imágenes
        if ($isAdmin) {
            $query = "SELECT imagen, imagen_extra1, imagen_extra2 FROM producto 
                      WHERE producto_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $producto_id);
        } else {
            $query = "SELECT imagen, imagen_extra1, imagen_extra2 FROM producto 
                      WHERE producto_id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $producto_id, $usuario_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();

        if ($producto) {
            if ($producto['imagen'] && file_exists("../../" . $producto['imagen'])) {
                unlink("../../" . $producto['imagen']);
            }
            if ($producto['imagen_extra1'] && file_exists("../../" . $producto['imagen_extra1'])) {
                unlink("../../" . $producto['imagen_extra1']);
            }
            if ($producto['imagen_extra2'] && file_exists("../../" . $producto['imagen_extra2'])) {
                unlink("../../" . $producto['imagen_extra2']);
            }
        }

        // 2) Borrar registro
        if ($isAdmin) {
            $query = "DELETE FROM producto WHERE producto_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $producto_id);
        } else {
            $query = "DELETE FROM producto WHERE producto_id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $producto_id, $usuario_id);
        }

        if ($stmt->execute()) {
            if ($isAdmin) {
                header("Location: ../../productos-admin.php?msg=deleted");
            } else {
                header("Location: ../../mis-productos.php?msg=deleted");
            }
            exit();
        } else {
            echo "Error al eliminar: " . $stmt->error;
        }
    }

    // Obtener producto por ID limitado al dueño (para "mis productos")
    public function getProductById($producto_id, $usuario_id)
    {
        $conn = $this->connection->connect();

        $query = "SELECT p.*, c.nombre AS categoria_nombre 
                  FROM producto p
                  LEFT JOIN categoria c ON p.categoria_categoria_id = c.categoria_id
                  WHERE p.producto_id = ? AND p.usuario_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $producto_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    // *** NUEVO *** Obtener producto por ID para ADMIN (sin filtrar por usuario)
    public function getProductByIdAdmin($producto_id)
    {
        $conn = $this->connection->connect();

        $query = "SELECT p.*, c.nombre AS categoria_nombre 
                  FROM producto p
                  LEFT JOIN categoria c ON p.categoria_categoria_id = c.categoria_id
                  WHERE p.producto_id = ?
                  LIMIT 1";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    // Actualizar producto (user o admin)
    public function updateProduct(
        $producto_id,
        $usuario_id,
        $name,
        $description,
        $price,
        $stock,
        $category_id,
        $imagePath = null,
        $imagePath2 = null,
        $imagePath3 = null,
        $context = 'user'
    ) {
        $conn = $this->connection->connect();
        $isAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') || $context === 'admin';

        // 1) Obtener imágenes actuales
        if ($isAdmin) {
            $query = "SELECT imagen, imagen_extra1, imagen_extra2 FROM producto 
                      WHERE producto_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $producto_id);
        } else {
            $query = "SELECT imagen, imagen_extra1, imagen_extra2 FROM producto 
                      WHERE producto_id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $producto_id, $usuario_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $old = $result->fetch_assoc();

        if ($old) {
            if ($imagePath !== null && $old['imagen'] && file_exists("../../" . $old['imagen'])) {
                unlink("../../" . $old['imagen']);
            }
            if ($imagePath2 !== null && $old['imagen_extra1'] && file_exists("../../" . $old['imagen_extra1'])) {
                unlink("../../" . $old['imagen_extra1']);
            }
            if ($imagePath3 !== null && $old['imagen_extra2'] && file_exists("../../" . $old['imagen_extra2'])) {
                unlink("../../" . $old['imagen_extra2']);
            }
        }

        // 2) Construir UPDATE dinámico
        $updates = ["nombre = ?", "descripcion = ?", "precio = ?", "stock = ?", "categoria_categoria_id = ?"];
        $types = "ssdii";
        $params = [$name, $description, $price, $stock, $category_id];

        if ($imagePath !== null) {
            $updates[] = "imagen = ?";
            $types .= "s";
            $params[] = $imagePath;
        } else {
            $updates[] = "imagen = ?";
            $types .= "s";
            $params[] = $old['imagen'] ?? null;
        }

        if ($imagePath2 !== null) {
            $updates[] = "imagen_extra1 = ?";
            $types .= "s";
            $params[] = $imagePath2;
        } else {
            $updates[] = "imagen_extra1 = ?";
            $types .= "s";
            $params[] = $old['imagen_extra1'] ?? null;
        }

        if ($imagePath3 !== null) {
            $updates[] = "imagen_extra2 = ?";
            $types .= "s";
            $params[] = $imagePath3;
        } else {
            $updates[] = "imagen_extra2 = ?";
            $types .= "s";
            $params[] = $old['imagen_extra2'] ?? null;
        }

        // WHERE
        if ($isAdmin) {
            $query = "UPDATE producto SET " . implode(", ", $updates) . " WHERE producto_id = ?";
            $types .= "i";
            $params[] = $producto_id;
        } else {
            $query = "UPDATE producto SET " . implode(", ", $updates) . " 
                      WHERE producto_id = ? AND usuario_id = ?";
            $types .= "ii";
            $params[] = $producto_id;
            $params[] = $usuario_id;
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            if ($isAdmin) {
                header("Location: ../../productos-admin.php?msg=updated");
            } else {
                header("Location: ../../mis-productos.php?msg=updated");
            }
            exit();
        } else {
            echo "Error al actualizar: " . $stmt->error;
        }
    }
}

/* =======================
 * FUNCIÓN PARA SUBIR IMG
 * ======================= */

function uploadImage($fileInputName)
{
    if (!empty($_FILES[$fileInputName]["name"])) {

        $uploads = "../../Assets/img/uploads/";
        if (!is_dir($uploads)) {
            mkdir($uploads, 0777, true);
        }

        $filename = uniqid() . "_" . basename($_FILES[$fileInputName]["name"]);
        $destino = $uploads . $filename;

        if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $destino)) {
            return "Assets/img/uploads/" . $filename;
        }
    }
    return null;
}

/* =======================
 * ROUTER POST
 * ======================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION['usuario_id'])) {
        die("Error: usuario no autenticado.");
    }

    $pc = new ProductController();
    $usuario_id = (int) $_SESSION['usuario_id'];
    $context = $_POST["context"] ?? 'user'; // 'admin' o 'user'

    if ($_POST["action"] === "create_product") {

        $imagePath = uploadImage("photo_main");
        $imagePath2 = uploadImage("photo_extra1");
        $imagePath3 = uploadImage("photo_extra2");

        $pc->createProduct(
            $_POST["name"],
            $_POST["description"],
            $_POST["price"],
            $_POST["stock"],
            intval($_POST["category"]),
            $imagePath,
            $imagePath2,
            $imagePath3,
            $usuario_id,
            $context
        );

    } elseif ($_POST["action"] === "delete_product") {

        $pc->deleteProduct(
            intval($_POST["producto_id"]),
            $usuario_id,
            $context
        );

    } elseif ($_POST["action"] === "update_product") {

        $imagePath = uploadImage("photo_main");
        $imagePath2 = uploadImage("photo_extra1");
        $imagePath3 = uploadImage("photo_extra2");

        $pc->updateProduct(
            intval($_POST["producto_id"]),
            $usuario_id,
            $_POST["name"],
            $_POST["description"],
            $_POST["price"],
            $_POST["stock"],
            intval($_POST["category"]),
            $imagePath,
            $imagePath2,
            $imagePath3,
            $context
        );
    }
}
