<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo admins pueden usar este exportador
if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'admin'
) {
    header("Location: index.php");
    exit;
}

require_once "app/config/connectionController.php";
require_once "app/lib/fpdf.php";  // asegúrate que esta ruta sea correcta

$conn = (new ConnectionController())->connect();

$admin_id = (int) $_SESSION['usuario_id'];

$format = $_GET['format'] ?? 'csv';    // csv | pdf
$tipo = $_GET['tipo'] ?? 'ventas'; // ventas | productos | clientes
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-d');

$inicioParam = $inicio . ' 00:00:00';
$finParam = $fin . ' 23:59:59';

$rows = [];
$totalPeriodo = 0.0;

/* =============================
 *   Consultas según el tipo
 *   (ADMIN ve TODA la tienda)
 * ============================= */
if ($tipo === 'ventas') {

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

/* =============================
 *       EXPORTAR A CSV
 * ============================= */
if ($format === 'csv') {

    $filename = "reporte_admin_{$tipo}_{$inicio}_{$fin}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($tipo === 'ventas') {

        fputcsv($output, ['Fecha', 'Folio', 'Cliente', 'Total']);
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['fecha'],
                $r['compra_id'],
                $r['cliente'],
                number_format($r['total'], 2, '.', '')
            ]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Total periodo', '', '', number_format($totalPeriodo, 2, '.', '')]);

    } elseif ($tipo === 'productos') {

        fputcsv($output, ['Producto', 'Unidades vendidas', 'Total vendido']);
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['nombre'],
                (int) $r['unidades_vendidas'],
                number_format($r['total_vendido'], 2, '.', '')
            ]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Total periodo', '', number_format($totalPeriodo, 2, '.', '')]);

    } elseif ($tipo === 'clientes') {

        fputcsv($output, ['Cliente', 'Número de compras', 'Total gastado']);
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['cliente'],
                (int) $r['num_compras'],
                number_format($r['total_gastado'], 2, '.', '')
            ]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Total periodo', '', number_format($totalPeriodo, 2, '.', '')]);
    }

    fclose($output);
    exit;
}

/* =============================
 *       EXPORTAR A PDF
 * ============================= */

$filename = "reporte_admin_{$tipo}_{$inicio}_{$fin}.pdf";

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('Raíz Viva - Reporte ADMIN ' . ucfirst($tipo)), 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, utf8_decode("Periodo: {$inicio} a {$fin}"), 0, 1, 'C');

$pdf->Ln(5);

if ($tipo === 'ventas') {

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(35, 7, 'Fecha', 1);
    $pdf->Cell(25, 7, 'Folio', 1);
    $pdf->Cell(80, 7, 'Cliente', 1);
    $pdf->Cell(30, 7, 'Total', 1, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $r) {
        $pdf->Cell(35, 6, $r['fecha'], 1);
        $pdf->Cell(25, 6, $r['compra_id'], 1);
        $pdf->Cell(80, 6, utf8_decode($r['cliente']), 1);
        $pdf->Cell(30, 6, number_format($r['total'], 2), 1, 1, 'R');
    }

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Ln(3);
    $pdf->Cell(140, 7, utf8_decode('Total periodo'), 1);
    $pdf->Cell(30, 7, number_format($totalPeriodo, 2), 1, 1, 'R');

} elseif ($tipo === 'productos') {

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(95, 7, 'Producto', 1);
    $pdf->Cell(35, 7, 'Unidades', 1, 0, 'R');
    $pdf->Cell(40, 7, 'Total vendido', 1, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $r) {
        $pdf->Cell(95, 6, utf8_decode($r['nombre']), 1);
        $pdf->Cell(35, 6, (int) $r['unidades_vendidas'], 1, 0, 'R');
        $pdf->Cell(40, 6, number_format($r['total_vendido'], 2), 1, 1, 'R');
    }

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Ln(3);
    $pdf->Cell(130, 7, utf8_decode('Total periodo'), 1);
    $pdf->Cell(40, 7, number_format($totalPeriodo, 2), 1, 1, 'R');

} elseif ($tipo === 'clientes') {

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 7, 'Cliente', 1);
    $pdf->Cell(40, 7, utf8_decode('Compras'), 1, 0, 'R');
    $pdf->Cell(45, 7, 'Total gastado', 1, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $r) {
        $pdf->Cell(80, 6, utf8_decode($r['cliente']), 1);
        $pdf->Cell(40, 6, (int) $r['num_compras'], 1, 0, 'R');
        $pdf->Cell(45, 6, number_format($r['total_gastado'], 2), 1, 1, 'R');
    }

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Ln(3);
    $pdf->Cell(120, 7, utf8_decode('Total periodo'), 1);
    $pdf->Cell(45, 7, number_format($totalPeriodo, 2), 1, 1, 'R');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$pdf->Output('D', $filename);
exit;
