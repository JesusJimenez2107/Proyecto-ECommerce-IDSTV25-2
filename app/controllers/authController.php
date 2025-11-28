<?php

session_start();   
include "../config/connectionController.php"; 


if (isset($_POST['action']) && $_POST['action'] == "login") {

 	$email = strip_tags($_POST['email']);
    $email = htmlspecialchars($email);
    $email = htmlentities($email);

    $password = strip_tags($_POST['password']);
    $password = htmlspecialchars($password);
    $password = htmlentities($password);

	$auth = new AuthController();
	$auth->login($email,$password);
} 

class AuthController{ 

	private $connection;

	public function __construct() {
	 	$this->connection = new ConnectionController();
	}

	function login($email, $password)
	{

		$conn = $this->connection->connect();
		if (!$conn->connect_error) {
			
			
			$query = "SELECT usuario_id, password, rol FROM usuario WHERE email = ?";

			$prepared_query = $conn->prepare($query);
			$prepared_query->bind_param('s', $email);
			$prepared_query->execute();

			$results = $prepared_query->get_result();
			
           
			$users = $results->fetch_all(MYSQLI_ASSOC); 

			if (count($users) > 0) {
               
                $hashed_password = $users[0]['password'];
                
                if (password_verify($password, $hashed_password)) {
                    
                    $_SESSION['email'] = $email;
                    $_SESSION['rol'] = $users[0]['rol'];
					$_SESSION['usuario_id'] = $users[0]['usuario_id'];
                    
                    if ($users[0]['rol'] == "admin") {
                        header("Location: ../../panel-admin.php");
                    } else {
                        header("Location: ../../index.php");
                    }
                    
                } else {
                    
                    header('Location: ../../login.html?error=password');
                }
			}else{
                
				header('Location: ../../login.html?error=user');
            }
			
		}else
			
			header('Location: ../../login.html?error=db'); 
	} 
}
?>