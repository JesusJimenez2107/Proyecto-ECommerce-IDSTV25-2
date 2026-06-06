<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Para el header: Declarar $logged antes de usarlo en el HTML
$logged = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);

if (!$logged) {
    header("Location: login.php?error=login_required");
    exit;
}

require_once "app/config/connectionController.php";
require_once "app/controllers/cartController.php";

$usuario_id = (int) $_SESSION['usuario_id'];
$isAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

$cart = new CartController();
$cartCount = $cart->getCartCount($usuario_id);

$conn = (new ConnectionController())->connect();

// ==========================================
// EXTRACCIÓN DE DATOS REALES (Híbrido)
// ==========================================

if ($isAdmin) {
    // 1. Total Clientes (Admin)
    $qClientes = "SELECT COUNT(DISTINCT usuario_usuario_id) as total FROM compra WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $totalClientes = $conn->query($qClientes)->fetch_assoc()['total'] ?? 0;

    // 2. Total Órdenes (Admin)
    $qOrdenes = "SELECT COUNT(compra_id) as total FROM compra WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $totalOrdenes = $conn->query($qOrdenes)->fetch_assoc()['total'] ?? 0;

    // 3. En Camino (Admin)
    $enCamino = 0;
    try {
        $qCamino = "SELECT COUNT(seguimiento_id) as total FROM seguimiento_envio WHERE estado_envio = 'En camino' AND fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $resCamino = $conn->query($qCamino);
        if ($resCamino)
            $enCamino = $resCamino->fetch_assoc()['total'] ?? 0;
    } catch (mysqli_sql_exception $e) {
    }

    // 4. Ingreso Total (Admin - Suma de todos los totales)
    $qIngreso = "SELECT SUM(total) as total FROM compra WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $resIngreso = $conn->query($qIngreso);
    $ingresoTotal = number_format($resIngreso->fetch_assoc()['total'] ?? 0, 2);

    // Gráfica de Ingresos Diarios (Admin) - CAMBIADO DE COUNT A SUM(total)
    $qGrafica = "SELECT DATE(fecha) as dia, SUM(total) as ingresos FROM compra WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(fecha)";

    // Top Productos (Admin)
    $qTop = "SELECT p.nombre, p.imagen as img, SUM(d.cantidad) as total_vendido
             FROM detalle_compra d
             INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             GROUP BY p.producto_id
             ORDER BY total_vendido DESC LIMIT 8";

    // Ingresos por Categoría (Admin - Últimos 30 días) - CAMBIADO DE cantidad a subtotal
    $qCategorias = "SELECT p.categoria_categoria_id as cat_id, SUM(d.subtotal) as ingresos
                    FROM detalle_compra d
                    INNER JOIN compra c ON d.compra_compra_id = c.compra_id
                    INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                    WHERE c.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY p.categoria_categoria_id";

} else {
    // 1. Total Clientes (Vendedor)
    $qClientes = "SELECT COUNT(DISTINCT c.usuario_usuario_id) as total 
                  FROM compra c INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                  WHERE p.usuario_id = $usuario_id AND c.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $totalClientes = $conn->query($qClientes)->fetch_assoc()['total'] ?? 0;

    // 2. Total Órdenes (Vendedor)
    $qOrdenes = "SELECT COUNT(DISTINCT c.compra_id) as total 
                 FROM compra c INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                 WHERE p.usuario_id = $usuario_id AND c.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $totalOrdenes = $conn->query($qOrdenes)->fetch_assoc()['total'] ?? 0;

    // 3. En Camino (Vendedor)
    $enCamino = 0;
    try {
        $qCamino = "SELECT COUNT(DISTINCT s.seguimiento_id) as total 
                    FROM seguimiento_envio s INNER JOIN detalle_compra d ON s.compra_id = d.compra_compra_id INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                    WHERE p.usuario_id = $usuario_id AND s.estado_envio = 'En camino' AND s.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $resCamino = $conn->query($qCamino);
        if ($resCamino)
            $enCamino = $resCamino->fetch_assoc()['total'] ?? 0;
    } catch (mysqli_sql_exception $e) {
    }

    // 4. Ingreso Total (Vendedor - 90% de sus ventas)
    $qIngreso = "SELECT SUM(d.subtotal * 0.90) as total 
                 FROM detalle_compra d INNER JOIN compra c ON d.compra_compra_id = c.compra_id INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                 WHERE p.usuario_id = $usuario_id AND c.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $resIngreso = $conn->query($qIngreso);
    $ingresoTotal = number_format($resIngreso->fetch_assoc()['total'] ?? 0, 2);

    // Gráfica de Ingresos Diarios (Vendedor) - CAMBIADO DE COUNT A SUM(subtotal * 0.90)
    $qGrafica = "SELECT DATE(c.fecha) as dia, SUM(d.subtotal * 0.90) as ingresos 
                 FROM compra c INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                 WHERE p.usuario_id = $usuario_id AND c.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(c.fecha)";

    // Top Productos (Vendedor)
    $qTop = "SELECT p.nombre, p.imagen as img, SUM(d.cantidad) as total_vendido
             FROM detalle_compra d INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             WHERE p.usuario_id = $usuario_id GROUP BY p.producto_id ORDER BY total_vendido DESC LIMIT 8";

    // Ingresos por Categoría (Vendedor - Últimos 30 días) - CAMBIADO DE cantidad a subtotal * 0.90
    $qCategorias = "SELECT p.categoria_categoria_id as cat_id, SUM(d.subtotal * 0.90) as ingresos
                    FROM detalle_compra d
                    INNER JOIN compra c ON d.compra_compra_id = c.compra_id
                    INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                    WHERE p.usuario_id = $usuario_id AND c.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY p.categoria_categoria_id";
}

// ==========================================
// PROCESAR GRÁFICA DE BARRAS Y TOP PRODUCTOS
// ==========================================
$fechas_grafica = [];
$ventas_grafica_temp = [];

for ($i = 6; $i >= 0; $i--) {
    $dateString = date('Y-m-d', strtotime("-$i days"));
    $fechas_grafica[] = date('d/m/y', strtotime("-$i days"));
    $ventas_grafica_temp[$dateString] = 0;
}

$resGrafica = $conn->query($qGrafica);
if ($resGrafica) {
    while ($row = $resGrafica->fetch_assoc()) {
        $ventas_grafica_temp[$row['dia']] = (float) $row['ingresos']; // Ahora es float por ser dinero
    }
}
$ventas_grafica = array_values($ventas_grafica_temp);

$productos_top = [];
try {
    $resTop = $conn->query($qTop);
    if ($resTop) {
        $productos_top = $resTop->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {
}

// ==========================================
// PROCESAR GRÁFICA DE ANILLO (CATEGORÍAS)
// ==========================================
$nombres_categorias = [
    1 => 'Interior',
    2 => 'Exterior',
    3 => 'Bajo mant.',
    4 => 'Aromáticas',
    5 => 'Macetas',
    6 => 'Cuidados'
];

$labels_categorias = [];
$datos_categorias = [];

$resCategorias = $conn->query($qCategorias);
if ($resCategorias) {
    while ($row = $resCategorias->fetch_assoc()) {
        $catId = (int)$row['cat_id'];
        $labels_categorias[] = $nombres_categorias[$catId] ?? "Otros";
        $datos_categorias[] = (float)$row['ingresos']; // Ahora es float
    }
}

// Si no hay ventas, mostramos un gráfico vacío en gris
if (empty($datos_categorias)) {
    $labels_categorias = ["Sin ventas"];
    $datos_categorias = [1]; 
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dash-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .dash-card {
            background-color: #fcfbf9;
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .kpi-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .kpi-text h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #111;
        }

        .kpi-text small {
            color: #777;
            font-size: 0.8rem;
        }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #6a8c6a;
            margin-top: 0.5rem;
        }

        .kpi-icon {
            color: #6a8c6a;
            background: #e9efe9;
            padding: 8px;
            border-radius: 8px;
            display: flex;
        }

        .middle-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .chart-header {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .chart-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .chart-header small {
            color: #777;
            font-size: 0.8rem;
        }

        .btn-reportes {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #fcfbf9;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: #111;
            transition: transform 0.2s;
            height: 100%;
        }

        .btn-reportes:hover {
            transform: translateY(-5px);
        }

        .report-icon-box {
            background-color: #e07a5f;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 15px;
        }

        .btn-reportes h3 {
            margin: 0;
            font-size: 1.4rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .products-card h3 {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
        }

        .products-scroll {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .products-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .products-scroll::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }

        .prod-item {
            min-width: 120px;
            background: #fff;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            border: 1px solid #eee;
        }

        .prod-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .prod-item p {
            margin: 0;
            font-size: 0.8rem;
            font-weight: bold;
            line-height: 1.2;
            color: #333;
        }

        .prod-item small {
            color: #888;
            font-size: 0.75rem;
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
            <a href="mi-cuenta.php">Mi Cuenta</a> › <span>Dashboard</span>
        </nav>

        <div style="background-color: #f5efe6; padding: 1.2rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h1 style="margin: 0; font-size: 2.2rem; color: #111; font-weight: bold; text-transform: uppercase;">Dashboard</h1>
        </div>

        <div class="dash-container">

            <section class="kpi-grid">
                <div class="dash-card">
                    <div class="kpi-content">
                        <div class="kpi-text">
                            <h3>Clientes Activos</h3>
                            <small>Últimos 30 días</small>
                        </div>
                        <div class="kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                        </div>
                    </div>
                    <div class="kpi-value"><?php echo $totalClientes; ?></div>
                </div>

                <div class="dash-card">
                    <div class="kpi-content">
                        <div class="kpi-text">
                            <h3>Total Ordenes</h3>
                            <small>Últimos 30 días</small>
                        </div>
                        <div class="kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="kpi-value"><?php echo $totalOrdenes; ?></div>
                </div>

                <div class="dash-card">
                    <div class="kpi-content">
                        <div class="kpi-text">
                            <h3>En Camino</h3>
                            <small>Últimos 30 días</small>
                        </div>
                        <div class="kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="3" width="15" height="13"></rect>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="kpi-value"><?php echo $enCamino; ?></div>
                </div>

                <div class="dash-card">
                    <div class="kpi-content">
                        <div class="kpi-text">
                            <h3>Ingreso Neto Total</h3>
                            <small>Últimos 30 días</small>
                        </div>
                        <div class="kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                <circle cx="12" cy="12" r="2"></circle>
                                <path d="M6 12h.01M18 12h.01"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="kpi-value">$<?php echo $ingresoTotal; ?></div>
                </div>
            </section>

            <section class="middle-grid">

                <div class="dash-card" style="grid-column: span 2; display: flex; flex-direction: column;">
                    <div class="chart-header">
                        <h3>Ingresos Netos Diarios</h3>
                        <small>Últimos 7 días</small>
                    </div>
                    <div style="position: relative; flex-grow: 1; min-height: 180px; width: 100%;">
                        <canvas id="ventasChart"></canvas>
                    </div>
                </div>

                <div class="dash-card" style="grid-column: span 2; text-align: center; display: flex; flex-direction: column;">
                    <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; text-align: left;">Ingresos Netos por Categoría</h3>
                    <small style="display: block; text-align: left; color: #777; margin-bottom: 10px;">Últimos 30 días</small>
                    <div style="position: relative; flex-grow: 1; min-height: 180px; width: 100%;">
                        <canvas id="categoriasChart"></canvas>
                    </div>
                </div>

            </section>

            <section style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                
                <div class="dash-card products-card" style="grid-column: span 3; display: flex; flex-direction: column; justify-content: center;">
                    <h3>Productos más vendidos</h3>
                    <div class="products-scroll">
                        <?php if (!empty($productos_top)): ?>
                            <?php foreach ($productos_top as $prod): ?>
                                <div class="prod-item">
                                    <img src="<?php echo htmlspecialchars($prod['img']); ?>"
                                        alt="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                        onerror="this.src='Assets/img/placeholder.jpg'">
                                    <p><?php echo htmlspecialchars($prod['nombre']); ?></p>
                                    <small><?php echo (int) $prod['total_vendido']; ?> pza</small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #777; font-size: 0.9rem;">Aún no hay registros de ventas para mostrar aquí.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="reportes.php" class="btn-reportes" style="grid-column: span 1;">
                    <div class="report-icon-box">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <polyline points="8 13 11 16 16 11"></polyline>
                        </svg>
                    </div>
                    <h3>Reportes</h3>
                </a>

            </section>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // Función de formateo de moneda global para las gráficas
            const formatter = new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            });

            // 1. Gráfica de Barras (Ingresos Diarios)
            const ctxVentas = document.getElementById('ventasChart').getContext('2d');
            const labelsVentas = <?php echo json_encode($fechas_grafica); ?>;
            const dataVentas = <?php echo json_encode($ventas_grafica); ?>;

            new Chart(ctxVentas, {
                type: 'bar',
                data: {
                    labels: labelsVentas,
                    datasets: [{
                        label: 'Ingresos',
                        data: dataVentas,
                        backgroundColor: '#6a8c6a',
                        barPercentage: 0.5,
                        borderRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        // Modificar el tooltip para que muestre el símbolo de dinero ($)
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + formatter.format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { 
                                // Modificar el eje Y para mostrar el símbolo de $
                                callback: function(value) {
                                    return '$' + value;
                                },
                                color: '#555', 
                                font: { size: 10 } 
                            },
                            grid: { color: '#e0e0e0' }
                        },
                        x: {
                            ticks: { color: '#555', font: { size: 9 } },
                            grid: { display: false }
                        }
                    }
                }
            });

            // 2. Gráfica de Anillo (Ingresos por Categorías)
            const ctxCat = document.getElementById('categoriasChart').getContext('2d');
            const labelsCat = <?php echo json_encode($labels_categorias); ?>;
            const dataCat = <?php echo json_encode($datos_categorias); ?>;
            
            // Verificamos si no hay ventas reales para pintar la gráfica de gris
            const isEmpty = (labelsCat[0] === "Sin ventas");

            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: labelsCat,
                    datasets: [{
                        data: dataCat,
                        backgroundColor: isEmpty 
                            ? ['#e0e0e0'] // Gris si no hay ventas
                            : ['#6a8c6a', '#e07a5f', '#f4a261', '#3d405b', '#81b29a', '#e9c46a'], // Colores de tu paleta Raíz Viva
                        borderWidth: 1,
                        borderColor: '#fcfbf9'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%', // Grosor del anillo
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 10, family: "'Quicksand', sans-serif" },
                                color: '#555',
                                padding: 10
                            }
                        },
                        tooltip: {
                            enabled: !isEmpty, // Ocultar tooltips si no hay ventas
                            callbacks: {
                                // Modificamos el tooltip del anillo para mostrar dinero ($)
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += formatter.format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

        });
    </script>
</body>
</html>