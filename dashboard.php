<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- RECEPTOR AJAX PARA LAS VENTAS DEL DÍA (AHORA FILTRADO POR VENDEDOR) ---
if (isset($_GET['ajax_date'])) {
    require_once "app/config/connectionController.php";
    $conn = (new ConnectionController())->connect();
    $fecha_req = $_GET['ajax_date']; 
    $vendedor_req = (int) $_SESSION['usuario_id']; // ID del vendedor logueado
    
    $qAjax = "SELECT COUNT(DISTINCT c.compra_id) as total 
              FROM compra c
              INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
              INNER JOIN producto p ON d.producto_producto_id = p.producto_id
              WHERE p.vendedor_id = ? AND DATE(c.fecha) = ?";
    
    $stmt = $conn->prepare($qAjax);
    $stmt->bind_param("is", $vendedor_req, $fecha_req);
    $stmt->execute();
    $res = $stmt->get_result();
    
    echo $res->fetch_assoc()['total'] ?? 0;
    exit; 
}
// ----------------------------------------------------

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

require_once "app/config/connectionController.php";
require_once "app/controllers/cartController.php";

$usuario_id = (int) $_SESSION['usuario_id']; // Este es el ID del vendedor
$cart = new CartController();
$cartCount = $cart->getCartCount($usuario_id);

$conn = (new ConnectionController())->connect();

// ==========================================
// EXTRACCIÓN DE DATOS REALES FILTRADOS POR VENDEDOR
// ==========================================

// 1. Total Clientes (Últimos 30 días, que compraron SUS productos)
$qClientes = "SELECT COUNT(DISTINCT c.usuario_usuario_id) as total 
              FROM compra c
              INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
              INNER JOIN producto p ON d.producto_producto_id = p.producto_id
              WHERE p.vendedor_id = $usuario_id AND c.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$totalClientes = $conn->query($qClientes)->fetch_assoc()['total'] ?? 0;

// 2. Total Órdenes (Últimos 30 días, que incluyen SUS productos)
$qOrdenes = "SELECT COUNT(DISTINCT c.compra_id) as total 
             FROM compra c
             INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
             INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             WHERE p.vendedor_id = $usuario_id AND c.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$totalOrdenes = $conn->query($qOrdenes)->fetch_assoc()['total'] ?? 0;

// 3. En Camino (Últimos 30 días, de SUS órdenes) 
$enCamino = 0;
try {
    $qCamino = "SELECT COUNT(DISTINCT s.seguimiento_id) as total 
                FROM seguimiento_envio s
                INNER JOIN detalle_compra d ON s.compra_id = d.compra_compra_id
                INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                WHERE p.vendedor_id = $usuario_id AND s.estado_envio = 'En camino' AND s.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $resCamino = $conn->query($qCamino);
    if ($resCamino) $enCamino = $resCamino->fetch_assoc()['total'] ?? 0;
} catch (mysqli_sql_exception $e) {}

// 4. Ingreso Total (Últimos 30 días, descontando el 10% de comisión de Raíz Viva)
// Usamos d.subtotal porque c.total es toda la compra (la cual podría incluir productos de otros vendedores)
$qIngreso = "SELECT SUM(d.subtotal * 0.90) as total 
             FROM detalle_compra d
             INNER JOIN compra c ON d.compra_compra_id = c.compra_id
             INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             WHERE p.vendedor_id = $usuario_id AND c.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$resIngreso = $conn->query($qIngreso);
$ingresoTotal = number_format($resIngreso->fetch_assoc()['total'] ?? 0, 2);

// 5. Ventas del día de hoy (SUS ventas)
$qHoy = "SELECT COUNT(DISTINCT c.compra_id) as total 
         FROM compra c
         INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
         INNER JOIN producto p ON d.producto_producto_id = p.producto_id
         WHERE p.vendedor_id = $usuario_id AND DATE(c.fecha) = CURDATE()";
$ventas_hoy = $conn->query($qHoy)->fetch_assoc()['total'] ?? 0;
$fecha_hoy = date('Y-m-d');


// ==========================================
// DATOS PARA LA GRÁFICA (Últimos 7 días)
// ==========================================
$fechas_grafica = [];
$ventas_grafica_temp = [];

for ($i = 6; $i >= 0; $i--) {
    $dateString = date('Y-m-d', strtotime("-$i days"));
    $fechas_grafica[] = date('d/m/y', strtotime("-$i days"));
    $ventas_grafica_temp[$dateString] = 0;
}

$qGrafica = "SELECT DATE(c.fecha) as dia, COUNT(DISTINCT c.compra_id) as total_ventas 
             FROM compra c
             INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
             INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             WHERE p.vendedor_id = $usuario_id AND c.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
             GROUP BY DATE(c.fecha)";
$resGrafica = $conn->query($qGrafica);
if ($resGrafica) {
    while ($row = $resGrafica->fetch_assoc()) {
        $ventas_grafica_temp[$row['dia']] = (int) $row['total_ventas'];
    }
}
$ventas_grafica = array_values($ventas_grafica_temp);


// ==========================================
// PRODUCTOS MÁS VENDIDOS (Top 8 histórico de ESTE vendedor)
// ==========================================
$productos_top = [];
try {
    $qTop = "SELECT p.nombre, p.imagen as img, SUM(d.cantidad) as total_vendido
             FROM detalle_compra d
             INNER JOIN producto p ON d.producto_producto_id = p.producto_id
             WHERE p.vendedor_id = $usuario_id
             GROUP BY p.producto_id
             ORDER BY total_vendido DESC
             LIMIT 8";
    $resTop = $conn->query($qTop);
    if ($resTop) {
        $productos_top = $resTop->fetch_all(MYSQLI_ASSOC);
    }
} catch (mysqli_sql_exception $e) {}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css">
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

        .daily-sales-container {
            text-align: center;
        }

        .daily-sales-container h3 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            text-align: left;
        }

        .date-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: inherit;
            margin-bottom: 2rem;
            box-sizing: border-box;
            color: #555;
            background-color: #fcfbf9;
        }

        .daily-value {
            font-size: 5rem;
            font-weight: bold;
            color: #6a8c6a;
            line-height: 1;
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
            <a class="brand" href="index.php"><img src="Assets/img/logo.png" alt="Raíz Viva"></a>

            <div class="nav-dropdown">
                <button class="nav-dropbtn">Productos
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path d="M6 9l6 6 6-6" stroke="currentColor" fill="none" stroke-width="2"></path>
                    </svg>
                </button>
                <nav class="nav-menu" hidden></nav>
            </div>

            <form class="search" role="search">
                <input type="search" placeholder="Buscar productos">
                <button type="submit">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#586a58" stroke-width="2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </button>
            </form>

            <div class="actions">
                <a href="mi-cuenta.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#fff" fill="none" stroke-width="2">
                        <path d="M20 21a8 8 0 1 0-16 0"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Mi cuenta</span>
                </a>
                <a href="carrito.php" class="action">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#fff" fill="none" stroke-width="2">
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

        <div
            style="background-color: #f5efe6; padding: 1.2rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h1 style="margin: 0; font-size: 2.2rem; color: #111; font-weight: bold; text-transform: uppercase;">
                Dashboard</h1>
        </div>

        <div class="dash-container">

            <section class="kpi-grid">
                <div class="dash-card">
                    <div class="kpi-content">
                        <div class="kpi-text">
                            <h3>Total Clientes</h3>
                            <small>Últimos 30 días</small>
                        </div>
                        <div class="kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
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
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                </path>
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
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
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
                            <h3>Ingreso Total</h3>
                            <small>Últimos 30 días</small>
                        </div>
                        <div class="kpi-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
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

                <div class="dash-card" style="grid-column: span 2;">
                    <div class="chart-header">
                        <h3>Ventas diarias</h3>
                        <small>Últimos 7 días</small>
                    </div>
                    <div style="position: relative; height: 160px; width: 100%;">
                        <canvas id="ventasChart"></canvas>
                    </div>
                </div>

                <div class="dash-card daily-sales-container">
                    <h3>Total de ventas del día</h3>
                    <input type="date" id="fecha-ventas" class="date-input" value="<?php echo $fecha_hoy; ?>"
                        style="cursor: pointer;">
                    <div id="valor-ventas-dia" class="daily-value" style="transition: opacity 0.3s ease;">
                        <?php echo $ventas_hoy; ?></div>
                </div>

                <a href="reportes.php" class="btn-reportes">
                    <div class="report-icon-box">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <polyline points="8 13 11 16 16 11"></polyline>
                        </svg>
                    </div>
                    <h3>Reportes</h3>
                </a>

            </section>

            <section class="dash-card products-card">
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
            </section>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('ventasChart').getContext('2d');

            const labels = <?php echo json_encode($fechas_grafica); ?>;
            const data = <?php echo json_encode($ventas_grafica); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas',
                        data: data,
                        backgroundColor: '#6a8c6a',
                        barPercentage: 0.5,
                        borderRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#555',
                                font: { size: 10 }
                            },
                            grid: { color: '#e0e0e0' }
                        },
                        x: {
                            ticks: {
                                color: '#555',
                                font: { size: 9 }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        });

        // --- NUEVO: LÓGICA PARA CAMBIAR LA FECHA ---
        const fechaInput = document.getElementById('fecha-ventas');
        const valorVentas = document.getElementById('valor-ventas-dia');

        if (fechaInput && valorVentas) {
            fechaInput.addEventListener('change', function () {
                const fechaSeleccionada = this.value;

                // Efecto visual: desvanecemos el número
                valorVentas.style.opacity = 0;

                // Hacemos la consulta al mismo archivo PHP enviando la fecha por la URL
                fetch('dashboard.php?ajax_date=' + fechaSeleccionada)
                    .then(response => response.text())
                    .then(data => {
                        // Cambiamos el número y lo volvemos a mostrar
                        setTimeout(() => {
                            valorVentas.innerText = data;
                            valorVentas.style.opacity = 1;
                        }, 200); // 200ms de retraso para que se note la transición
                    })
                    .catch(error => {
                        console.error('Error al consultar las ventas:', error);
                        valorVentas.style.opacity = 1;
                    });
            });
        }
    </script>
</body>

</html>