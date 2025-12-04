<?php
session_start();

include "../models/userModel.php";

if (isset($_POST['action']) && $_POST['action'] == "create_user") {

    $name      = trim($_POST['nombre']);
    $lastname  = trim($_POST['apellidos']);
    $email     = trim($_POST['correo']);
    $password  = trim($_POST['password']);
    $direccion = trim($_POST['direccion']);
    $telefono  = trim($_POST['telefono']);

    $userModel = new UserModel();

    // VERIFICAR SI YA EXISTE EL CORREO
    $conn = (new ConnectionController())->connect();
    $query = "SELECT usuario_id FROM usuario WHERE email = ?";
    $prepared_query = $conn->prepare($query);
    $prepared_query->bind_param('s', $email);
    $prepared_query->execute();
    $result = $prepared_query->get_result();

    if ($result->num_rows > 0) {
        // Correo ya registrado
        header("Location: ../../registro.php?error=email");
        exit;
    }

    // Crear usuario
    $created = $userModel->create($name, $lastname, $email, $password, $direccion, $telefono);

    if ($created) {
        header("Location: ../../login.php?success=registered");
        exit;
    } else {
        header("Location: ../../registro.php?error=db");
        exit;
    }
}
?>
