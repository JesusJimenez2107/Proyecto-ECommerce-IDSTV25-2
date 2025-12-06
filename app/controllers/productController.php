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

        return $result->fetch_assoc(); // array o null si no existe
    }

    /* =========================================================
     * MÉTODOS PARA DUEÑO (MIS PRODUCTOS)
     * ========================================================= */

    // Crear
    public function createProduct(
        $name,
        $description,
        $price,
        $stock,
        $category_id,
        $imagePath,
        $imagePath2,
        $imagePath3,
        $usuario_id
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
            header("Location: ../../mis-productos.php?msg=created");
            exit();
        } else {
            echo "Error al guardar: " . $stmt->error;
        }
    }

    // Borrar
    public function deleteProduct($producto_id, $usuario_id)
    {
        $conn = $this->connection->connect();

        $query = "SELECT imagen, imagen_extra1, imagen_extra2 FROM producto 
                  WHERE producto_id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $producto_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();

        if ($producto['imagen'] && file_exists("../../" . $producto['imagen'])) {
            unlink("../../" . $producto['imagen']);
        }
        if ($producto['imagen_extra1'] && file_exists("../../" . $producto['imagen_extra1'])) {
            unlink("../../" . $producto['imagen_extra1']);
        }
        if ($producto['imagen_extra2'] && file_exists("../../" . $producto['imagen_extra2'])) {
            unlink("../../" . $producto['imagen_extra2']);
        }

        $query = "DELETE FROM producto WHERE producto_id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $producto_id, $usuario_id);

        if ($stmt->execute()) {
            header("Location: ../../mis-productos.php?msg=deleted");
            exit();
        } else {
            echo "Error al eliminar: " . $stmt->error;
        }
    }

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

    // Actualizar
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
        $imagePath3 = null
    ) {

        $conn = $this->connection->connect();

        $query = "SELECT imagen, imagen_extra1, imagen_extra2 FROM producto 
                  WHERE producto_id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $producto_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old = $result->fetch_assoc();

        if ($imagePath !== null && $old['imagen'] && file_exists("../../" . $old['imagen'])) {
            unlink("../../" . $old['imagen']);
        }
        if ($imagePath2 !== null && $old['imagen_extra1'] && file_exists("../../" . $old['imagen_extra1'])) {
            unlink("../../" . $old['imagen_extra1']);
        }
        if ($imagePath3 !== null && $old['imagen_extra2'] && file_exists("../../" . $old['imagen_extra2'])) {
            unlink("../../" . $old['imagen_extra2']);
        }

        $updates = ["nombre = ?", "descripcion = ?", "precio = ?", "stock = ?", "categoria_categoria_id = ?"];
        $types = "ssdii";
        $params = [$name, $description, $price, $stock, $category_id];

        if ($imagePath !== null) {
            $updates[] = "imagen = ?";
            $types .= "s";
            $params[] = $imagePath;
        } else {
            $params[] = $old['imagen'];
            $updates[] = "imagen = ?";
            $types .= "s";
        }

        if ($imagePath2 !== null) {
            $updates[] = "imagen_extra1 = ?";
            $types .= "s";
            $params[] = $imagePath2;
        } else {
            $params[] = $old['imagen_extra1'];
            $updates[] = "imagen_extra1 = ?";
            $types .= "s";
        }

        if ($imagePath3 !== null) {
            $updates[] = "imagen_extra2 = ?";
            $types .= "s";
            $params[] = $imagePath3;
        } else {
            $params[] = $old['imagen_extra2'];
            $updates[] = "imagen_extra2 = ?";
            $types .= "s";
        }

        $params[] = $producto_id;
        $params[] = $usuario_id;
        $types .= "ii";

        $query = "UPDATE producto SET " . implode(", ", $updates) . " WHERE producto_id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            header("Location: ../../mis-productos.php?msg=updated");
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
        if (!is_dir($uploads))
            mkdir($uploads, 0777, true);

        $filename = uniqid() . "_" . basename($_FILES[$fileInputName]["name"]);
        $destino = $uploads . $filename;

        if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $destino)) {
            return "Assets/img/uploads/" . $filename;
        }
    }
    return null;
}


/* =======================
 * ROUTER POST (MIS PROD)
 * ======================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION['usuario_id'])) {
        die("Error: usuario no autenticado.");
    }

    $pc = new ProductController();
    $usuario_id = $_SESSION['usuario_id'];

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
            $usuario_id
        );
    } elseif ($_POST["action"] === "delete_product") {
        $pc->deleteProduct(intval($_POST["producto_id"]), $usuario_id);
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
            $imagePath3
        );
    }
}
?>