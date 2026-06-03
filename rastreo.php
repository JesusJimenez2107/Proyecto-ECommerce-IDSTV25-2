<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: mis-compras.php");
    exit;
}

require_once "app/config/connectionController.php";
require_once "app/controllers/cartController.php";

$usuario_id = (int) $_SESSION['usuario_id'];
$compra_id = (int) $_GET['id'];

// Conexión
$conn = (new ConnectionController())->connect();

// Obtener los datos de la compra (Fecha y Dirección de envío)
$sqlCompra = "SELECT fecha, direccion_envio, ciudad_envio 
              FROM compra 
              WHERE compra_id = ? AND usuario_usuario_id = ?";
$stmt = $conn->prepare($sqlCompra);
$stmt->bind_param("ii", $compra_id, $usuario_id);
$stmt->execute();
$resCompra = $stmt->get_result();
$compra = $resCompra->fetch_assoc();

// Si el pedido no existe o no es de este usuario, lo regresamos
if (!$compra) {
    header("Location: mis-compras.php");
    exit;
}

// Simulamos los tiempos basados en la fecha de compra real
$fecha_compra = strtotime($compra['fecha']);
$time_preparacion = strtotime("+2 hours", $fecha_compra);
$time_camino = strtotime("+1 day", $fecha_compra);

$str_confirmada = date("d M H:i", $fecha_compra);
$str_preparacion = date("d M H:i", $time_preparacion);
$str_camino = date("d M H:i", $time_camino);

// Verificamos si aún está a tiempo de cancelar (antes de que pase 1 día)
$ahora = time();
$puede_cancelar = ($ahora < $time_camino);

// Variables para el header
$logged = true;
$cart = new CartController();
$cartCount = $cart->getCartCount($usuario_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rastreo de Envío – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2">
    <style>
        /* ESTILOS ESPECÍFICOS PARA LA PANTALLA DE RASTREO (Estilo Figma) */
        .tracking-layout {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            margin-top: 2rem;
        }
        .tracking-card {
            background-color: #f5efe6; 
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .col-left { flex: 1; }
        .col-right { flex: 1.2; } 

        /* Títulos */
        .track-section-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 1.5rem;
            margin-top: 0;
        }

        /* Línea de tiempo vertical (Izquierda) */
        .vertical-timeline {
            border-left: 3px solid #586a58;
            margin-left: 10px;
            padding-left: 25px;
            position: relative;
        }
        .timeline-step {
            margin-bottom: 2rem;
            position: relative;
        }
        .timeline-step:last-child { margin-bottom: 0; border-left: 3px solid transparent; }
        
        .timeline-step::before {
            content: '';
            position: absolute;
            left: -32.5px;
            top: 0;
            width: 12px;
            height: 12px;
            background-color: #586a58;
            border-radius: 50%;
        }
        .step-title {
            font-weight: bold;
            color: #333;
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }
        .step-desc {
            margin: 0 0 5px 0;
            color: #555;
            font-size: 0.9rem;
        }
        .step-date {
            margin: 0;
            color: #777;
            font-size: 0.85rem;
        }

        /* Tarjeta Interna de Orden (Derecha) */
        .order-inner-card {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .order-header {
            background-color: #6a8c6a;
            padding: 1.5rem;
            color: white;
            border-radius: 12px 12px 0 0;
            position: relative;
        }
        .order-header::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 25px;
            bottom: 25px;
            border-left: 2px solid rgba(255,255,255,0.5);
        }
        .route-point {
            position: relative;
            padding-left: 15px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        .route-point:last-child { margin-bottom: 0; }
        .route-point::before {
            content: '';
            position: absolute;
            left: -14px;
            top: 4px;
            width: 8px;
            height: 8px;
            background-color: white;
            border-radius: 50%;
        }

        /* Progreso Horizontal */
        .horizontal-progress {
            padding: 2.5rem 2rem;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 12px 12px;
        }
        .progress-bar-container {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #ccc;
            z-index: 1;
            transform: translateY(-50%);
        }
        .progress-line-active {
            position: absolute;
            top: 50%;
            left: 0;
            width: 50%;
            height: 4px;
            background-color: #586a58;
            z-index: 2;
            transform: translateY(-50%);
        }
        
        /* Iconos de progreso */
        .progress-icon {
            width: 45px;
            height: 45px;
            background-color: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            position: relative;
            border: 3px solid white;
        }
        .progress-icon.active {
            background-color: #8fa88f;
        }
        .progress-icon svg {
            stroke: #222;
        }

        /* Plazos de entrega */
        .delivery-terms {
            display: flex;
            justify-content: space-between;
            padding: 0 1rem;
            background-color: transparent;
        }
        .term-box {
            text-align: center;
        }
        .term-label {
            font-size: 0.75rem;
            color: #777;
            display: block;
            margin-bottom: 5px;
        }
        .term-value {
            font-weight: bold;
            color: #333;
            font-size: 1.1rem;
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
                <button class="nav-dropbtn" id="btnProductos" aria-haspopup="true" aria-expanded="false" aria-controls="menuProductos">
                    Productos
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <nav class="nav-menu" id="menuProductos" role="menu" hidden>
                    <a role="menuitem" href="productos.php?cat=1" class="nav-menu__item">Plantas de interior</a>
                    <a role="menuitem" href="productos.php?cat=2" class="nav-menu__item">Plantas de exterior</a>
                    <a role="menuitem" href="productos.php?cat=3" class="nav-menu__item">Bajo mantenimiento</a>
                    <a role="menuitem" href="productos.php?cat=4" class="nav-menu__item">Aromáticas y comestibles</a>
                    <a role="menuitem" href="productos.php?cat=5" class="nav-menu__item">Macetas y accesorios</a>
                    <a role="menuitem" href="productos.php?cat=6" class="nav-menu__item">Cuidados y bienestar</a>
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
                                if (isset($_SESSION['nombre']) && !empty($_SESSION['nombre'])) {
                                    $primerNombre = explode(' ', trim($_SESSION['nombre']))[0];
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
        <nav class="breadcrumb">
            <a href="mi-cuenta.php">Mi Cuenta</a> › <a href="mis-compras.php">Mis Compras</a> › <span>Rastreo</span>
        </nav>

        <div style="background-color: #f5efe6; padding: 1.2rem; border-radius: 12px; text-align: center; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h1 style="margin: 0; font-size: 2rem; color: #111; font-weight: bold; text-transform: uppercase;">Rastreo de envío</h1>
        </div>

        <div class="tracking-layout">
            <section class="tracking-card col-left">
                <h2 class="track-section-title">Estatus de envío</h2>
                
                <div class="vertical-timeline">
                    <div class="timeline-step">
                        <p class="step-title">Compra confirmada</p>
                        <p class="step-desc">Procesamos tu compra</p>
                        <p class="step-date"><?php echo $str_confirmada; ?></p>
                    </div>
                    <div class="timeline-step">
                        <p class="step-title">En preparación</p>
                        <p class="step-desc">El vendedor está preparando tu compra</p>
                        <p class="step-date"><?php echo $str_preparacion; ?></p>
                    </div>
                    <div class="timeline-step">
                        <p class="step-title">En camino</p>
                        <p class="step-desc">El vendedor despachó tu compra.</p>
                        <p class="step-date"><?php echo $str_camino; ?></p>
                    </div>
                </div>
            </section>

            <section class="tracking-card col-right">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 class="track-section-title" style="margin: 0;">Orden #<?php echo $compra_id; ?></h2>
                    
                    <?php if ($puede_cancelar): ?>
                        <form action="app/controllers/cartController.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta orden? El dinero no se ha cobrado y los productos volverán a la tienda.');">
                            <input type="hidden" name="action" value="cancel_order">
                            <input type="hidden" name="compra_id" value="<?php echo $compra_id; ?>">
                            <button type="submit" style="background: transparent; border: 2px solid #e74c3c; color: #e74c3c; padding: 6px 15px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#ffd7d7'" onmouseout="this.style.background='transparent'">
                                Cancelar orden
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="order-inner-card">
                    <div class="order-header">
                        <div class="route-point">Vivero Raíz Viva, La Paz, Baja California Sur</div>
                        <div class="route-point"><?php echo htmlspecialchars($compra['direccion_envio'] . ', ' . $compra['ciudad_envio']); ?></div>
                    </div>

                    <div class="horizontal-progress">
                        <div class="progress-bar-container">
                            <div class="progress-line"></div>
                            <div class="progress-line-active"></div> 
                            <div class="progress-icon active">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                            </div>
                            <div class="progress-icon active">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M5 22h14"></path><path d="M5 2h14"></path><path d="M17 22V2"></path><path d="M7 22V2"></path></svg>
                            </div>
                            <div class="progress-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            </div>
                            <div class="progress-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            </div>
                        </div>
                    </div>
                </div> 

                <div class="delivery-terms">
                    <div class="term-box">
                        <span class="term-label">Plazo de preparación</span>
                        <span class="term-value">1 - 2 hrs</span>
                    </div>
                    <div class="term-box">
                        <span class="term-label">Envío estimado</span>
                        <span class="term-value">1 día</span>
                    </div>
                    <div class="term-box">
                        <span class="term-label">Entrega final</span>
                        <span class="term-value">7 días</span>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>
</html>