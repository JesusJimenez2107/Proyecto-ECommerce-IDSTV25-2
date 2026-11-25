<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();

require_once __DIR__ . "/app/config/connectionController.php";

$conn = (new ConnectionController())->connect();

$usuario_id = (int) $_SESSION['usuario_id'];
$isAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

$tipo = $_GET['tipo'] ?? 'ventas';
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-d');

$inicioParam = $inicio . ' 00:00:00';
$finParam = $fin . ' 23:59:59';

$rows = [];
$totalPeriodo = 0.0;

if ($tipo === 'ventas') {
    if ($isAdmin) {
        $sql = "SELECT c.fecha, c.compra_id, u.nombre AS cliente, c.total
                FROM compra c
                INNER JOIN usuario u ON c.usuario_usuario_id = u.usuario_id
                WHERE c.fecha BETWEEN ? AND ?
                ORDER BY c.fecha DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $inicioParam, $finParam);
    } else {
        $sql = "SELECT c.fecha, c.compra_id, u.nombre AS cliente, SUM(d.subtotal) AS total
                FROM compra c
                INNER JOIN usuario u ON c.usuario_usuario_id = u.usuario_id
                INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
                INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                WHERE c.fecha BETWEEN ? AND ? AND p.usuario_id = ?
                GROUP BY c.compra_id, c.fecha, u.nombre
                ORDER BY c.fecha DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $inicioParam, $finParam, $usuario_id);
    }
} elseif ($tipo === 'productos') {
    if ($isAdmin) {
        $sql = "SELECT p.nombre, SUM(d.cantidad) AS unidades_vendidas, SUM(d.subtotal) AS total_vendido
                FROM compra c
                INNER JOIN detalle_compra d ON d.compra_compra_id = c.compra_id
                INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                WHERE c.fecha BETWEEN ? AND ?
                GROUP BY p.producto_id, p.nombre
                ORDER BY unidades_vendidas DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $inicioParam, $finParam);
    } else {
        $sql = "SELECT p.nombre, SUM(d.cantidad) AS unidades_vendidas, SUM(d.subtotal) AS total_vendido
                FROM compra c
                INNER JOIN detalle_compra d ON d.compra_compra_id = c.compra_id
                INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                WHERE c.fecha BETWEEN ? AND ? AND p.usuario_id = ?
                GROUP BY p.producto_id, p.nombre
                ORDER BY unidades_vendidas DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $inicioParam, $finParam, $usuario_id);
    }
} elseif ($tipo === 'clientes') {
    if ($isAdmin) {
        $sql = "SELECT u.nombre AS cliente, COUNT(c.compra_id) AS num_compras, SUM(c.total) AS total_gastado
                FROM compra c
                INNER JOIN usuario u ON c.usuario_usuario_id = u.usuario_id
                WHERE c.fecha BETWEEN ? AND ?
                GROUP BY u.usuario_id, u.nombre
                ORDER BY total_gastado DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $inicioParam, $finParam);
    } else {
        $sql = "SELECT u.nombre AS cliente, COUNT(DISTINCT c.compra_id) AS num_compras, SUM(d.subtotal) AS total_gastado
                FROM compra c
                INNER JOIN usuario u ON c.usuario_usuario_id = u.usuario_id
                INNER JOIN detalle_compra d ON c.compra_id = d.compra_compra_id
                INNER JOIN producto p ON d.producto_producto_id = p.producto_id
                WHERE c.fecha BETWEEN ? AND ? AND p.usuario_id = ?
                GROUP BY u.usuario_id, u.nombre
                ORDER BY total_gastado DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $inicioParam, $finParam, $usuario_id);
    }
} else {
    header("Location: reportes.php");
    exit();
}

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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reportes – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/reportes.css" />
</head>

<body>
    <?php require_once "app/views/layouts/header.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="mi-cuenta.php">Mi Cuenta</a> › <span>Reportes</span>
        </nav>

        <h1 class="rp-title">REPORTES</h1>

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

        <form class="rp-filters" action="reportes.php" method="get" aria-label="Filtrar por periodo">
            <input type="hidden" name="tipo" id="rpTipo" value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>" />

            <label class="rp-filter">
                <span>Inicio</span>
                <input type="date" name="inicio" id="rpInicio" required value="<?php echo htmlspecialchars($inicio, ENT_QUOTES, 'UTF-8'); ?>" />
            </label>

            <label class="rp-filter">
                <span>Fin</span>
                <input type="date" name="fin" id="rpFin" required value="<?php echo htmlspecialchars($fin, ENT_QUOTES, 'UTF-8'); ?>" />
            </label>

            <button class="btn-primary" type="submit">Buscar</button>

            <div class="rp-export">
                <a class="btn-outline" id="expCsv" href="#">CSV</a>
                <a class="btn-outline" id="expPdf" href="#">PDF</a>
            </div>
        </form>

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
                                <tr><td colspan="4">No hay ventas en este periodo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($r['compra_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($r['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                                <tr><td colspan="3">No hay ventas de productos en este periodo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                                <tr><td colspan="3">No hay compras de clientes en este periodo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
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

    <?php require_once "app/views/layouts/footer.php"; ?>

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

            expCsv.href = `export-report.php?format=csv&tipo=${tipo}${i}${f}`;
            expPdf.href = `export-report.php?format=pdf&tipo=${tipo}${i}${f}`;
        }

        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                tabs.forEach(b => {
                    b.classList.remove('is-active');
                    b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');

                tipoInput.value = btn.dataset.report;
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