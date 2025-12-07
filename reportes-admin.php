<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Solo admins pueden entrar
if (
  !isset($_SESSION['usuario_id']) ||
  !isset($_SESSION['rol']) ||
  $_SESSION['rol'] !== 'admin'
) {
  header("Location: index.php");
  exit;
}

require_once "app/config/connectionController.php";
require_once "app/controllers/cartController.php";

$conn = (new ConnectionController())->connect();

$adminId = (int) $_SESSION['usuario_id'];

// Para el header
$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;
if ($logged) {
  $cartCtrl = new CartController();
  $cartCount = $cartCtrl->getCartCount($adminId);
}

// --------- Parámetros de filtro ---------
$tipo = $_GET['tipo'] ?? 'ventas';           // ventas | productos | clientes
$inicio = $_GET['inicio'] ?? date('Y-m-01');     // primer día del mes
$fin = $_GET['fin'] ?? date('Y-m-d');      // hoy

$inicioParam = $inicio . ' 00:00:00';
$finParam = $fin . ' 23:59:59';

$rows = [];
$title = '';
$totalPeriodo = 0.0;

// --------- Consultas según el tipo (admin ve TODO) ---------
if ($tipo === 'ventas') {

  $title = "Ventas";
  $sql = "SELECT 
              c.fecha,
              c.compra_id,
              u.nombre AS cliente,
              c.total
          FROM compra c
          INNER JOIN usuario u 
              ON c.usuario_usuario_id = u.usuario_id
          WHERE c.fecha BETWEEN ? AND ?
          ORDER BY c.fecha DESC";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $inicioParam, $finParam);

} elseif ($tipo === 'productos') {

  $title = "Productos más vendidos";
  $sql = "SELECT 
              p.nombre,
              SUM(d.cantidad) AS unidades_vendidas,
              SUM(d.subtotal) AS total_vendido
          FROM compra c
          INNER JOIN detalle_compra d 
              ON d.compra_compra_id = c.compra_id
          INNER JOIN producto p 
              ON d.producto_producto_id = p.producto_id
          WHERE c.fecha BETWEEN ? AND ?
          GROUP BY p.producto_id, p.nombre
          ORDER BY unidades_vendidas DESC";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $inicioParam, $finParam);

} elseif ($tipo === 'clientes') {

  $title = "Clientes con más compras";
  $sql = "SELECT 
              u.nombre AS cliente,
              COUNT(c.compra_id) AS num_compras,
              SUM(c.total)       AS total_gastado
          FROM compra c
          INNER JOIN usuario u 
              ON c.usuario_usuario_id = u.usuario_id
          WHERE c.fecha BETWEEN ? AND ?
          GROUP BY u.usuario_id, u.nombre
          ORDER BY total_gastado DESC";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $inicioParam, $finParam);

} else {
  // fallback: ventas
  $tipo = 'ventas';
  $title = "Ventas";

  $sql = "SELECT 
              c.fecha,
              c.compra_id,
              u.nombre AS cliente,
              c.total
          FROM compra c
          INNER JOIN usuario u 
              ON c.usuario_usuario_id = u.usuario_id
          WHERE c.fecha BETWEEN ? AND ?
          ORDER BY c.fecha DESC";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $inicioParam, $finParam);
}

// Ejecutamos
$stmt->execute();
$result = $stmt->get_result();

while ($r = $result->fetch_assoc()) {
  $rows[] = $r;

  if ($tipo === 'ventas') {
    $totalPeriodo += (float) $r['total'];
  } elseif ($tipo === 'productos') {
    $totalPeriodo += (float) $r['total_vendido'];
  } elseif ($tipo === 'clientes') {
    $totalPeriodo += (float) $r['total_gastado'];
  }
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: login.php?error=admin_only");
  exit();
}

$adminName = $_SESSION['nombre'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reportes (Admin) – Raíz Viva</title>

  <link rel="stylesheet" href="Assets/styles/global.css" />
  <link rel="stylesheet" href="Assets/styles/reportes.css" />
</head>

<body>
  <!-- Header Admin -->
  <header class="topbar">
    <div class="topbar__inner">
      <a href="panel-admin.php" class="brand">
        <img src="Assets/img/logo.png" alt="Raíz Viva">
      </a>

      <div class="topbar__admin-center">
        <span class="topbar__admin-title">Panel de administración</span>

        <nav class="topbar__admin-nav">
          <a href="panel-admin.php">Inicio</a>
          <a href="usuarios.php">Usuarios</a>
          <a href="productos-admin.php">Productos</a>
          <a href="reportes-admin.php">Reportes</a>
        </nav>
      </div>

      <div class="actions">
        <span class="action action--text">
          <?php echo htmlspecialchars($adminName); ?>
        </span>
      </div>
    </div>
  </header>

  <main class="page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="panel-admin.php">Panel Administración</a> › <span>Reportes</span>
    </nav>

    <h1 class="rp-title">REPORTES</h1>

    <!-- Tabs -->
    <div class="rp-tabs" role="tablist" aria-label="Tipo de reporte">
      <button class="rp-tab <?php echo $tipo === 'ventas' ? 'is-active' : ''; ?>" data-report="ventas" role="tab"
        aria-selected="<?php echo $tipo === 'ventas' ? 'true' : 'false'; ?>">
        Ventas
      </button>

      <button class="rp-tab <?php echo $tipo === 'productos' ? 'is-active' : ''; ?>" data-report="productos" role="tab"
        aria-selected="<?php echo $tipo === 'productos' ? 'true' : 'false'; ?>">
        Productos más vendidos
      </button>

      <button class="rp-tab <?php echo $tipo === 'clientes' ? 'is-active' : ''; ?>" data-report="clientes" role="tab"
        aria-selected="<?php echo $tipo === 'clientes' ? 'true' : 'false'; ?>">
        Clientes con más compras
      </button>
    </div>

    <!-- Filtros -->
    <form class="rp-filters" action="reportes-admin.php" method="get" aria-label="Filtrar por periodo">
      <input type="hidden" name="tipo" id="rpTipo" value="<?php echo htmlspecialchars($tipo); ?>" />

      <label class="rp-filter">
        <span>Inicio</span>
        <input type="date" name="inicio" id="rpInicio" required value="<?php echo htmlspecialchars($inicio); ?>" />
      </label>

      <label class="rp-filter">
        <span>Fin</span>
        <input type="date" name="fin" id="rpFin" required value="<?php echo htmlspecialchars($fin); ?>" />
      </label>

      <button class="btn-primary" type="submit">Buscar</button>

      <div class="rp-export">
        <a class="btn-outline" id="expCsv" href="#">CSV</a>
        <a class="btn-outline" id="expPdf" href="#">PDF</a>
      </div>
    </form>

    <!-- Resultados -->
    <section class="rp-results" aria-live="polite">
      <div class="table-wrap">
        <table class="rp-table">
          <?php if ($tipo === 'ventas'): ?>
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Folio</th>
                <th>Cliente</th>
                <th class="num">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="4">No hay ventas en este periodo.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($r['compra_id']); ?></td>
                    <td><?php echo htmlspecialchars($r['cliente']); ?></td>
                    <td class="num">$<?php echo number_format($r['total'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="right">Total periodo</th>
                <th class="num">$<?php echo number_format($totalPeriodo, 2); ?></th>
              </tr>
            </tfoot>

          <?php elseif ($tipo === 'productos'): ?>
            <thead>
              <tr>
                <th>Producto</th>
                <th class="num">Unidades vendidas</th>
                <th class="num">Total vendido</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="3">No hay ventas de productos en este periodo.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                    <td class="num"><?php echo (int) $r['unidades_vendidas']; ?></td>
                    <td class="num">$<?php echo number_format($r['total_vendido'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="2" class="right">Total periodo</th>
                <th class="num">$<?php echo number_format($totalPeriodo, 2); ?></th>
              </tr>
            </tfoot>

          <?php elseif ($tipo === 'clientes'): ?>
            <thead>
              <tr>
                <th>Cliente</th>
                <th class="num">Número de compras</th>
                <th class="num">Total gastado</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="3">No hay compras de clientes en este periodo.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['cliente']); ?></td>
                    <td class="num"><?php echo (int) $r['num_compras']; ?></td>
                    <td class="num">$<?php echo number_format($r['total_gastado'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="2" class="right">Total periodo</th>
                <th class="num">$<?php echo number_format($totalPeriodo, 2); ?></th>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </section>
  </main>

  <footer class="footer">
    <p>© Raíz Viva</p>
  </footer>

  <script>
    const tabs = document.querySelectorAll('.rp-tab');
    const tipoInput = document.getElementById('rpTipo');
    const expCsv = document.getElementById('expCsv');
    const expPdf = document.getElementById('expPdf');
    const inicio = document.getElementById('rpInicio');
    const fin = document.getElementById('rpFin');
    const form = document.querySelector('.rp-filters');

    function updateExportLinks() {
      const tipo = tipoInput.value || 'ventas';
      const i = inicio.value ? `&inicio=${inicio.value}` : '';
      const f = fin.value ? `&fin=${fin.value}` : '';

      expCsv.href = `export-report-admin.php?format=csv&tipo=${tipo}${i}${f}`;
      expPdf.href = `export-report-admin.php?format=pdf&tipo=${tipo}${i}${f}`;
    }

    tabs.forEach(btn => {
      btn.addEventListener('click', () => {
        tabs.forEach(b => {
          b.classList.remove('is-active');
          b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('is-active');
        btn.setAttribute('aria-selected', 'true');

        const tipo = btn.dataset.report;
        tipoInput.value = tipo;

        updateExportLinks();
        form.submit();
      });
    });

    inicio.addEventListener('change', () => {
      fin.min = inicio.value || '';
      updateExportLinks();
    });

    fin.addEventListener('change', () => {
      if (inicio.value && fin.value && fin.value < inicio.value) {
        alert('La fecha FIN no puede ser menor que INICIO.');
        fin.value = '';
      }
      updateExportLinks();
    });

    document.addEventListener('DOMContentLoaded', updateExportLinks);
  </script>
</body>

</html>