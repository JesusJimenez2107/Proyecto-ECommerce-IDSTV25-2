<?php
require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/connectionController.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === "login") {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    $auth = new AuthController();
    $auth->login($email, $password);
}

class AuthController
{
    private ConnectionController $connection;

    public function __construct()
    {
        $this->connection = new ConnectionController();
    }

    public function login(string $email, string $password): void
    {
        if (empty($email) || empty($password)) {
            header('Location: ../../login.php?error=credentials');
            exit();
        }

        try {
            $conn = $this->connection->connect();
            $query = "SELECT usuario_id, nombre, password, rol FROM usuario WHERE email = ? LIMIT 1";

            $prepared_query = $conn->prepare($query);
            $prepared_query->bind_param('s', $email);
            $prepared_query->execute();

            $results = $prepared_query->get_result();
            $user = $results->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Prevenir Fijación de Sesión
                session_regenerate_id(true);

                $_SESSION['email'] = $email;
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['usuario_id'] = (int) $user['usuario_id'];
                $_SESSION['nombre'] = $user['nombre'];

                // Regenerar token CSRF tras autenticarse
                unset($_SESSION['csrf_token']);
                getCsrfToken();

                if ($user['rol'] === "admin") {
                    header("Location: ../../panel-admin.php");
                } else {
                    header("Location: ../../index.php");
                }
                exit();
            }

            // Mensaje genérico para evitar enumeración de usuarios
            header('Location: ../../login.php?error=credentials');
            exit();

        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            header('Location: ../../login.php?error=db');
            exit();
        }
    }
}