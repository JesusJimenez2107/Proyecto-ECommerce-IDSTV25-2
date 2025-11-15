<?php


include "../models/userModel.php"; 

if (isset($_POST['action']) && $_POST['action'] == "create_user") {

 	$name = $_POST['nombre'];
 	$lastname = $_POST['apellidos'];
 	$email = $_POST['correo'];
	$password = $_POST['password'];	
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

	$user = new UsersController();
	$user->create($name, $lastname, $email, $password, $direccion, $telefono);
} 

class UsersController{

	private $User;

	public function __construct() {
	 	$this->User = new UserModel();
	}

	public function create($name, $lastname, $email, $password, $direccion, $telefono)
	{ 	
		
		if ($this->User->create($name, $lastname, $email, $password, $direccion, $telefono)) {
			
			header('Location: ../../login.html?status=ok'); 

		}else
			
			header('Location: ../../registro.html?status=error'); 

	}

}
?>