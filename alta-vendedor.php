<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

require_once "app/controllers/cartController.php";
$usuario_id = (int) $_SESSION['usuario_id'];
$cartCtrl = new CartController();
$cartCount = $cartCtrl->getCartCount($usuario_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Alta de Vendedor – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <style>
        .page-header-box {
            background-color: #f5efe6;
            padding: 1.2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .page-header-box h1 {
            margin: 0;
            font-size: 2.2rem;
            color: #111;
            font-weight: bold;
            text-transform: uppercase;
        }

        .seller-form-card {
            background-color: #f5efe6;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 1.3rem;
            text-transform: uppercase;
            font-weight: bold;
            color: #111;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }
        .section-title:first-child {
            margin-top: 0;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.5rem;
        }
        .form-grid-2 .form-group, .form-grid-3 .form-group {
            margin-bottom: 0;
        }

        .form-label {
            font-size: 0.95rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: #111;
            font-weight: 500;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .terms-text {
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
        }
        .btn-cancel {
            background: transparent;
            border: 2px solid #e74c3c;
            color: #e74c3c;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            cursor: pointer;
        }
        .btn-submit {
            background: #e07a5f;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            cursor: pointer;
        }
    </style>
</head>
<body>
    
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="index.php">
                <img src="Assets/img/logo.png" alt="Raíz Viva" />
            </a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn" id="btnProductos" aria-haspopup="true" aria-expanded="false"
                    aria-controls="menuProductos">
                    Productos
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>
                </button>

                <!-- Menú de categorías -->
                <nav class="nav-menu" id="menuProductos" role="menu" hidden>
                    <a role="menuitem" href="productos.php?cat=1" class="nav-menu__item">
                        Plantas de interior
                    </a>

                    <a role="menuitem" href="productos.php?cat=2" class="nav-menu__item">
                        Plantas de exterior
                    </a>

                    <a role="menuitem" href="productos.php?cat=3" class="nav-menu__item">
                        Bajo mantenimiento
                    </a>

                    <a role="menuitem" href="productos.php?cat=4" class="nav-menu__item">
                        Aromáticas y comestibles
                    </a>

                    <a role="menuitem" href="productos.php?cat=5" class="nav-menu__item">
                        Macetas y accesorios
                    </a>

                    <a role="menuitem" href="productos.php?cat=6" class="nav-menu__item">
                        Cuidados y bienestar
                    </a>
                </nav>
            </div>

            <form action="productos.php" method="GET" class="search" role="search">
                <input type="search" name="search" placeholder="Buscar" aria-label="Buscar"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </button>
            </form>

            <div class="actions">

                <?php if ($logged): ?>
                    <a href="mi-cuenta.php" class="action">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M20 21a8 8 0 1 0-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>
                            <?php
                            // Si existe el nombre en sesión, extraemos solo el primer nombre
                            if (isset($_SESSION['nombre']) && !empty($_SESSION['nombre'])) {
                                $primerNombre = explode(' ', trim($_SESSION['nombre']))[0];
                                // Ponemos la primera letra en mayúscula
                                echo htmlspecialchars(ucfirst(strtolower($primerNombre)));
                            } else {
                                echo 'Mi cuenta';
                            }
                            ?>
                        </span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="action">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M20 21a8 8 0 1 0-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Ingresar</span>
                    </a>
                <?php endif; ?>


                <a href="carrito.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2">
                        <circle cx="10" cy="20" r="1"></circle>
                        <circle cx="18" cy="20" r="1"></circle>
                        <path d="M2 2h3l2.2 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 6H6"></path>
                    </svg>
                    <span><?php echo $cartCount; ?></span>
                </a>
            </div>
        </div>
    </header>

    <main class="page">
        <div class="page-header-box">
            <h1>Alta de Vendedor</h1>
        </div>

        <section class="seller-form-card">
            <form action="app/controllers/vendedorController.php" method="POST">
                
                <h2 class="section-title">Datos Personales y Fiscales</h2>
                <div class="form-group">
                    <label class="form-label" for="nombre_completo">Nombre completo</label>
                    <input class="form-input" type="text" id="nombre_completo" name="nombre_completo" placeholder="Tu nombre completo (nombre(s) apellido(s))" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="rfc">RFC</label>
                        <input class="form-input" type="text" id="rfc" name="rfc" required maxlength="13" 
                               pattern="[A-Z0-9]{12,13}" 
                               title="Debe contener 12 o 13 letras y números"
                               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="curp">CURP</label>
                        <input class="form-input" type="text" id="curp" name="curp" required maxlength="18" 
                               pattern="[A-Z0-9]{18}" 
                               title="Debe contener exactamente 18 letras y números"
                               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                    </div>
                </div>

                <h2 class="section-title">Dirección de Recolección (Para Paquetería)</h2>
                <div class="form-group">
                    <label class="form-label" for="calle_numero">Calle y número</label>
                    <input class="form-input" type="text" id="calle_numero" name="calle_numero" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="colonia">Colonia</label>
                    <input class="form-input" type="text" id="colonia" name="colonia" required>
                </div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="codigo_postal">Código Postal</label>
                        <input class="form-input" type="text" id="codigo_postal" name="codigo_postal" required maxlength="5"
                               pattern="\d{5}" 
                               title="Debe contener exactamente 5 números"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ciudad">Ciudad</label>
                        <input class="form-input" type="text" id="ciudad" name="ciudad" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado</label>
                        <input class="form-input" type="text" id="estado" name="estado" required>
                    </div>
                </div>

                <h2 class="section-title">Datos de Cobro</h2>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="titular_cuenta">Titular de la cuenta</label>
                        <input class="form-input" type="text" id="titular_cuenta" name="titular_cuenta" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="clabe">CLABE Interbancaria (18 dígitos)</label>
                        <input class="form-input" type="text" id="clabe" name="clabe" required maxlength="18" 
                               pattern="\d{18}" 
                               title="Debe contener exactamente 18 números"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>

                <h2 class="section-title">Acuerdos Comerciales</h2>
                <p class="terms-text">
                    Al registrarte como vendedor, aceptas que Raíz Viva actuará como intermediario en tus transacciones. 
                    Comprendes y aceptas el esquema de comisiones estándar, el cual consiste en una retención del 10% sobre el valor total de cada venta por uso de plataforma y procesamiento de pagos.
                </p>
                <label class="checkbox-group">
                    <input type="checkbox" name="acepta_terminos" required>
                    Acepto los términos y condiciones de vendedor
                </label>
                <label class="checkbox-group">
                    <input type="checkbox" name="acepta_comisiones" required>
                    Acepto el esquema de comisiones (10%)
                </label>

                <div class="form-actions">
                    <a href="mi-cuenta.php" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-submit">Darme de alta</button>
                </div>
            </form>
        </section>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>
</html>