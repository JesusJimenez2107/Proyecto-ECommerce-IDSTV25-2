<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raíz Viva – Iniciar sesión</title>
    <link rel="stylesheet" href="Assets/styles/styles.css">
</head>
<body>
    <main class="login-container">

        <!-- SECCIÓN IZQUIERDA (FORMULARIO) -->
        <section class="form-section">

            <div class="brand">
                <img src="Assets/img/logo.png" alt="Logo Raíz Viva">
            </div>

            <h2>Inicio de sesión</h2>

            <!-- ALERTAS -->
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] == 'user'): ?>
                    <div class="alert alert-error">
                        El correo ingresado no está registrado.
                    </div>
                <?php elseif ($_GET['error'] == 'password'): ?>
                    <div class="alert alert-error">
                        La contraseña es incorrecta.
                    </div>
                <?php elseif ($_GET['error'] == 'db'): ?>
                    <div class="alert alert-error">
                        Ocurrió un problema con la base de datos. Inténtalo más tarde.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
                <div class="alert alert-success">
                    ¡Registro exitoso! Ahora puedes iniciar sesión.
                </div>
            <?php endif; ?>
            <!-- FIN ALERTAS -->

            <form action="./app/controllers/authController.php" method="POST" onsubmit="return validateLogin()">

                <label for="email">Correo</label>
                <input type="email" id="email" name="email" placeholder="Correo" required>

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Contraseña" required>

                <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>

                <button type="submit">INICIAR SESIÓN</button>

                <input type="hidden" name="action" value="login">

            </form>

        </section>

        <!-- SECCIÓN DERECHA (IMAGEN) -->
        <section class="image-section">
            <img src="Assets/img/img-logo.png" alt="Plantas decorativas">
        </section>

    </main>

    <script src="./Assets/js/validaciones.js"></script>
</body>
</html>
