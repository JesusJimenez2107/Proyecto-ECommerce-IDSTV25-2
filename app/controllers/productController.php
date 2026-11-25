<?php
require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/connectionController.php";

class ProductController
{
    private ConnectionController $connection;

    public function __construct()
    {
        $this->connection = new ConnectionController();
    }

    /* =========================================================
     * MÉTODOS PÚBLICOS (TIENDA)
     * ========================================================= */

    public function getPublicProducts($categoria_id = null): array
    {
        $conn = $this->connection->connect();

        if ($categoria_id) {
            $query = "SELECT p.* FROM producto p WHERE p.estado = 'activo' AND p.categoria_categoria_id = ? ORDER BY (p.stock = 0), p.producto_id DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $categoria_id);
        } else {
            $query = "SELECT p.* FROM producto p WHERE p.estado = 'activo' ORDER BY (p.stock = 0), p.producto_id DESC";
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPublicProductById(int $producto_id): ?array
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
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchProducts(string $texto, $categoria_id = null): array
    {
        $conn = $this->connection->connect();
        $like = "%" . $texto . "%";

        if ($categoria_id) {
            $query = "SELECT * FROM producto WHERE estado = 'activo' AND categoria_categoria_id = ? AND (nombre LIKE ? OR descripcion LIKE ?) ORDER BY (stock = 0), producto_id DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $categoria_id, $like, $like);
        } else {
            $query = "SELECT * FROM producto WHERE estado = 'activo' AND (nombre LIKE ? OR descripcion LIKE ?) ORDER BY (stock = 0), producto_id DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $like, $like);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* =========================================================
     * MÉTODOS PARA DUEÑO / ADMIN (RBAC ESTRICTO EN SESIÓN)
     * ========================================================= */

    private function isSessionAdmin(): bool
    {
        return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
    }

    public function createProduct(
        string $name,
        string $description,
        float $price,
        int $stock,
        int $category_id,
        ?string $imagePath,
        ?string $imagePath2,
        ?string $imagePath3,
        int $usuario_id
    ): void {
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
            $redirect = $this->isSessionAdmin()
                ? "../../productos-admin.php?msg=created"
                : "../../mis-productos.php?msg=created";
            header("Location: " . $redirect);
            exit();
        }
    }

    public function deleteProduct(int $producto_id, int $usuario_id): void
    {
        $conn = $this->connection->connect();
        $isAdmin = $this->isSessionAdmin();
        $baseDir = dirname(__DIR__, 2) . "/";

        // 1) Verificar propiedad y obtener rutas de imágenes
        if ($isAdmin) {
            $stmt = $conn->prepare("SELECT imagen, imagen_extra1, imagen_extra2 FROM producto WHERE producto_id = ?");
            $stmt->bind_param("i", $producto_id);
        } else {
            $stmt = $conn->prepare("SELECT imagen, imagen_extra1, imagen_extra2 FROM producto WHERE producto_id = ? AND usuario_id = ?");
            $stmt->bind_param("ii", $producto_id, $usuario_id);
        }

        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();

        if (!$producto) {
            header("Location: ../../index.php?error=unauthorized");
            exit();
        }

        // 2) Borrar registro primero en BD (evita borrar imágenes si falla llave foránea)
        if ($isAdmin) {
            $delStmt = $conn->prepare("DELETE FROM producto WHERE producto_id = ?");
            $delStmt->bind_param("i", $producto_id);
        } else {
            $delStmt = $conn->prepare("DELETE FROM producto WHERE producto_id = ? AND usuario_id = ?");
            $delStmt->bind_param("ii", $producto_id, $usuario_id);
        }

        if ($delStmt->execute() && $delStmt->affected_rows > 0) {
            foreach (['imagen', 'imagen_extra1', 'imagen_extra2'] as $campo) {
                if (!empty($producto[$campo]) && file_exists($baseDir . $producto[$campo])) {
                    @unlink($baseDir . $producto[$campo]);
                }
            }
        }

        $redirect = $isAdmin
            ? "../../productos-admin.php?msg=deleted"
            : "../../mis-productos.php?msg=deleted";
        header("Location: " . $redirect);
        exit();
    }

    public function getProductById(int $producto_id, int $usuario_id): ?array
    {
        $conn = $this->connection->connect();

        $query = "SELECT p.*, c.nombre AS categoria_nombre 
                  FROM producto p
                  LEFT JOIN categoria c ON p.categoria_categoria_id = c.categoria_id
                  WHERE p.producto_id = ? AND p.usuario_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $producto_id, $usuario_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProductByIdAdmin(int $producto_id): ?array
    {
        if (!$this->isSessionAdmin()) {
            return null;
        }

        $conn = $this->connection->connect();
        $query = "SELECT p.*, c.nombre AS categoria_nombre 
                  FROM producto p
                  LEFT JOIN categoria c ON p.categoria_categoria_id = c.categoria_id
                  WHERE p.producto_id = ?
                  LIMIT 1";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateProduct(
        int $producto_id,
        int $usuario_id,
        string $name,
        string $description,
        float $price,
        int $stock,
        int $category_id,
        ?string $imagePath = null,
        ?string $imagePath2 = null,
        ?string $imagePath3 = null
    ): void {
        $conn = $this->connection->connect();
        $isAdmin = $this->isSessionAdmin();
        $baseDir = dirname(__DIR__, 2) . "/";

        if ($isAdmin) {
            $stmt = $conn->prepare("SELECT imagen, imagen_extra1, imagen_extra2 FROM producto WHERE producto_id = ?");
            $stmt->bind_param("i", $producto_id);
        } else {
            $stmt = $conn->prepare("SELECT imagen, imagen_extra1, imagen_extra2 FROM producto WHERE producto_id = ? AND usuario_id = ?");
            $stmt->bind_param("ii", $producto_id, $usuario_id);
        }

        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();

        if (!$old) {
            header("Location: ../../index.php?error=unauthorized");
            exit();
        }

        if ($imagePath !== null && !empty($old['imagen']) && file_exists($baseDir . $old['imagen'])) {
            @unlink($baseDir . $old['imagen']);
        }
        if ($imagePath2 !== null && !empty($old['imagen_extra1']) && file_exists($baseDir . $old['imagen_extra1'])) {
            @unlink($baseDir . $old['imagen_extra1']);
        }
        if ($imagePath3 !== null && !empty($old['imagen_extra2']) && file_exists($baseDir . $old['imagen_extra2'])) {
            @unlink($baseDir . $old['imagen_extra2']);
        }

        $img1Final = $imagePath ?? ($old['imagen'] ?? null);
        $img2Final = $imagePath2 ?? ($old['imagen_extra1'] ?? null);
        $img3Final = $imagePath3 ?? ($old['imagen_extra2'] ?? null);

        if ($isAdmin) {
            $query = "UPDATE producto SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria_categoria_id = ?, imagen = ?, imagen_extra1 = ?, imagen_extra2 = ? WHERE producto_id = ?";
            $stmtUpd = $conn->prepare($query);
            $stmtUpd->bind_param("ssdiisssi", $name, $description, $price, $stock, $category_id, $img1Final, $img2Final, $img3Final, $producto_id);
        } else {
            $query = "UPDATE producto SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria_categoria_id = ?, imagen = ?, imagen_extra1 = ?, imagen_extra2 = ? WHERE producto_id = ? AND usuario_id = ?";
            $stmtUpd = $conn->prepare($query);
            $stmtUpd->bind_param("ssdiisssii", $name, $description, $price, $stock, $category_id, $img1Final, $img2Final, $img3Final, $producto_id, $usuario_id);
        }

        if ($stmtUpd->execute()) {
            $redirect = $isAdmin
                ? "../../productos-admin.php?msg=updated"
                : "../../mis-productos.php?msg=updated";
            header("Location: " . $redirect);
            exit();
        }
    }
}

/* =========================================================
 * FUNCIÓN SEGURA PARA SUBIDA DE IMÁGENES
 * ========================================================= */
function uploadImage(string $fileInputName): ?string
{
    if (empty($_FILES[$fileInputName]["name"]) || $_FILES[$fileInputName]["error"] !== UPLOAD_ERR_OK) {
        return null;
    }

    $fileTmp  = $_FILES[$fileInputName]["tmp_name"];
    $fileSize = (int) $_FILES[$fileInputName]["size"];
    $fileName = $_FILES[$fileInputName]["name"];

    // 1. Validar extensión en lista blanca estricta PRIMERO
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException("Extensión no permitida. Solo se aceptan imágenes JPG, PNG o WEBP.");
    }

    // 2. Validar tamaño máximo: 2 MB
    $maxBytes = 2097152;
    if ($fileSize <= 0 || $fileSize > $maxBytes) {
        throw new RuntimeException("El archivo excede el tamaño máximo permitido de 2 MB.");
    }

    // 3. Validar firma MIME real del contenido binario del archivo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileTmp);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new RuntimeException("El contenido del archivo no corresponde a una imagen válida.");
    }

    // 4. Crear directorio con permisos seguros 0755 y nombre aleatorio criptográfico
    $uploadsDir = dirname(__DIR__, 2) . "/Assets/img/uploads/";
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $safeFilename = bin2hex(random_bytes(16)) . "." . $ext;
    $destino = $uploadsDir . $safeFilename;

    if (move_uploaded_file($fileTmp, $destino)) {
        return "Assets/img/uploads/" . $safeFilename;
    }

    return null;
}

/* =========================================================
 * ROUTER POST PROTEGIDO
 * ========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../../login.php?error=login_required");
        exit();
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Error de seguridad: Token CSRF inválido o expirado.");
    }

    $pc = new ProductController();
    $usuario_id = (int) $_SESSION['usuario_id'];
    $action = $_POST["action"] ?? '';

    try {
        if ($action === "create_product") {
            $imagePath  = uploadImage("photo_main");
            $imagePath2 = uploadImage("photo_extra1");
            $imagePath3 = uploadImage("photo_extra2");

            $pc->createProduct(
                trim($_POST["name"] ?? ''),
                trim($_POST["description"] ?? ''),
                (float) ($_POST["price"] ?? 0),
                (int) ($_POST["stock"] ?? 0),
                (int) ($_POST["category"] ?? 0),
                $imagePath,
                $imagePath2,
                $imagePath3,
                $usuario_id
            );
        } elseif ($action === "delete_product") {
            $pc->deleteProduct((int) ($_POST["producto_id"] ?? 0), $usuario_id);
        } elseif ($action === "update_product") {
            $imagePath  = uploadImage("photo_main");
            $imagePath2 = uploadImage("photo_extra1");
            $imagePath3 = uploadImage("photo_extra2");

            $pc->updateProduct(
                (int) ($_POST["producto_id"] ?? 0),
                $usuario_id,
                trim($_POST["name"] ?? ''),
                trim($_POST["description"] ?? ''),
                (float) ($_POST["price"] ?? 0),
                (int) ($_POST["stock"] ?? 0),
                (int) ($_POST["category"] ?? 0),
                $imagePath,
                $imagePath2,
                $imagePath3
            );
        }
    } catch (RuntimeException $e) {
        $backPage = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin')
            ? "../../agregar-producto-admin.php?error=" . urlencode($e->getMessage())
            : "../../agregar-producto.php?error=" . urlencode($e->getMessage());
        header("Location: " . $backPage);
        exit();
    }
}