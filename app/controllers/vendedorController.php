<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/connectionController.php";

class VendedorController
{
    private $connection;

    public function __construct()
    {
        $this->connection = new ConnectionController();
    }

    public function registrarVendedor($usuario_id, $rfc, $curp, $calle, $colonia, $cp, $ciudad, $estado, $titular, $clabe, $terminos)
    {
        $conn = $this->connection->connect();

        $qCheck = "SELECT rol FROM usuario WHERE usuario_id = ?";
        $stmtCheck = $conn->prepare($qCheck);
        $stmtCheck->bind_param("i", $usuario_id);
        $stmtCheck->execute();
        $res = $stmtCheck->get_result()->fetch_assoc();
        
        if ($res && ($res['rol'] === 'vendedor' || $res['rol'] === 'admin')) {
            return "already_vendedor";
        }

        $conn->begin_transaction();

        try {
            $qInsert = "INSERT INTO datos_vendedor 
                        (vendedor_id, rfc, curp, calle_numero, colonia, codigo_postal, ciudad, estado, titular_cuenta, clabe_interbancaria, acepta_terminos) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($qInsert);
            $stmtInsert->bind_param("isssssssssi", $usuario_id, $rfc, $curp, $calle, $colonia, $cp, $ciudad, $estado, $titular, $clabe, $terminos);
            $stmtInsert->execute();

            $qUpdate = "UPDATE usuario SET rol = 'vendedor' WHERE usuario_id = ?";
            $stmtUpdate = $conn->prepare($qUpdate);
            $stmtUpdate->bind_param("i", $usuario_id);
            $stmtUpdate->execute();

            $conn->commit();
            return "success";
        } catch (Exception $e) {
            $conn->rollback();
            return "error";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../../login.php?error=login_required");
        exit;
    }

    $usuario_id = (int) $_SESSION['usuario_id'];
    
    $rfc = $_POST['rfc'] ?? '';
    $curp = $_POST['curp'] ?? '';
    $calle = $_POST['calle_numero'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $cp = $_POST['codigo_postal'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $titular = $_POST['titular_cuenta'] ?? '';
    $clabe = $_POST['clabe'] ?? '';
    
    $acepta_terminos = isset($_POST['acepta_terminos']) ? 1 : 0;
    $acepta_comisiones = isset($_POST['acepta_comisiones']) ? 1 : 0;

    if (!$acepta_terminos || !$acepta_comisiones) {
        header("Location: ../../alta-vendedor.php?msg=terminos_required");
        exit;
    }

    $vendedorCtrl = new VendedorController();
    $result = $vendedorCtrl->registrarVendedor(
        $usuario_id, $rfc, $curp, $calle, $colonia, $cp, $ciudad, $estado, $titular, $clabe, $acepta_terminos
    );

    if ($result === "success") {
        header("Location: ../../mi-cuenta.php?msg=vendedor_success");
    } elseif ($result === "already_vendedor") {
        header("Location: ../../mi-cuenta.php?msg=already_vendedor");
    } else {
        header("Location: ../../alta-vendedor.php?msg=error");
    }
    exit;
}