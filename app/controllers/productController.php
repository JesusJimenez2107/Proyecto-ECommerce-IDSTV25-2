<?php
session_start();
include "../config/connectionController.php";  

class ProductController {

    private $connection;

    public function __construct() {
        $this->connection = new ConnectionController();
    }

    public function createProduct($name, $description, $price, $stock, $category_id, $imagePath) {

        $conn = $this->connection->connect(); 

        $query = "INSERT INTO producto (nombre, descripcion, precio, stock, imagen, categoria_categoria_id)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);

        
        $category_id = intval($category_id);

        $stmt->bind_param("ssdisi", 
            $name,
            $description,
            $price,
            $stock,
            $imagePath,
            $category_id
        );

        if ($stmt->execute()) {
            header("Location: ../../mis-productos.php?msg=created");
            exit();
        } else {
            echo "Error al guardar: " . $stmt->error;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["action"] === "create_product") {

    $pc = new ProductController();

    
    $imagePath = null;

    if (!empty($_FILES["photo_main"]["name"])) {

        $uploads = "../../Assets/img/uploads/";

        if (!is_dir($uploads)) mkdir($uploads, 0777, true);

        $filename = uniqid() . "_" . basename($_FILES["photo_main"]["name"]);

        $destino = $uploads . $filename;

        if (move_uploaded_file($_FILES["photo_main"]["tmp_name"], $destino)) {
            $imagePath = "Assets/img/uploads/" . $filename;  
        }
    }

    
    $pc->createProduct(
        $_POST["name"],
        $_POST["description"],
        $_POST["price"],
        $_POST["stock"],
        intval($_POST["category"]), 
        $imagePath
    );

}
?>
