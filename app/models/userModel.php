<?php

include "../config/connectionController.php"; 

class UserModel{

	private $connection;
    
    private $table = "usuario"; 

	public function __construct() {
	 	$this->connection = new ConnectionController();
	}

	
	public function create($name, $lastname, $email, $password, $direccion, $telefono)
	{

		$conn = $this->connection->connect();
        
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        
        $rol = 'cliente';
        
		// CÓDIGO CORREGIDO PARA LA LÍNEA 28
        $query = "INSERT INTO " . $this->table . " (nombre, apellidos, email, password, rol, direccion, telefono) VALUES (?,?,?,?,?,?,?)";
		$prepared_query = $conn->prepare($query);

        
		$prepared_query->bind_param('sssssss', $name, $lastname, $email, $hashed_password, $rol, $direccion, $telefono);

		$prepared_query->execute();

        
		if ($prepared_query->error) {
			return false;
		}else
			return true;
	}
    
    
}

?>