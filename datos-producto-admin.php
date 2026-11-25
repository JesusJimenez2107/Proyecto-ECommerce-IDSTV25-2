<?php
require_once __DIR__ . "/app/config/security.php";
requireAdmin();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: productos-admin.php");
    exit();
}
$productoId = (int) $_GET['id'];

require_once __DIR__ . "/app/config/connectionController.php";
$conn = (new ConnectionController())->connect();

$stmt = $conn->prepare(
    "SELECT p.producto_id,
            p.nombre,
            p.descripcion,
            p.precio,
            p.stock,
            p.estado,
            p.imagen,
            p.imagen_extra1,
            p.imagen_extra2,
            c.nombre AS categoria_nombre,
            u.nombre AS dueno_nombre,
            u.apellidos AS dueno_apellidos,
            u.email AS dueno_email
     FROM producto p
     LEFT JOIN categoria c ON p.categoria_categoria_id = c.categoria_id
     LEFT JOIN usuario u   ON p.usuario_id = u.usuario_id
     WHERE p.producto_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $productoId);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    header("Location: productos-admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Datos producto (Administrador) – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/account-profile.css" />
    <link rel="stylesheet" href="Assets/styles/admin-usuarios.css">
</head>

<body>
    <?php require_once "app/views/layouts/header-admin.php"; ?>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="panel-admin.php">Panel Administración</a> ›
            <a href="productos-admin.php">Productos</a> ›
            <span>Datos producto</span>
        </nav>

        <section class="profile-card">
            <header class="profile-card__header">
                <h1 class="profile-title">DATOS PRODUCTO</h1>
            </header>

            <?php if (!empty($producto['imagen'])): ?>
                <div class="profile-photo" style="margin-bottom: 1rem;">
                    <img src="<?php echo htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                        style="max-width: 220px; border-radius: 12px; object-fit: cover;">
                </div>
            <?php endif; ?>

            <dl class="profile-data">
                <div class="row">
                    <dt>ID</dt>
                    <dd><?php echo (int) $producto['producto_id']; ?></dd>
                </div>

                <div class="row">
                    <dt>Nombre</dt>
                    <dd><?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>

                <div class="row">
                    <dt>Categoría</dt>
                    <dd><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>

                <div class="row">
                    <dt>Precio</dt>
                    <dd>$<?php echo number_format($producto['precio'], 2); ?></dd>
                </div>

                <div class="row">
                    <dt>Stock</dt>
                    <dd><?php echo (int) $producto['stock']; ?></dd>
                </div>

                <div class="row">
                    <dt>Estado</dt>
                    <dd><?php echo ucfirst(htmlspecialchars($producto['estado'], ENT_QUOTES, 'UTF-8')); ?></dd>
                </div>

                <div class="row">
                    <dt>Vendedor / Dueño</dt>
                    <dd>
                        <?php if ($producto['dueno_nombre']): ?>
                            <?php echo htmlspecialchars($producto['dueno_nombre'] . ' ' . $producto['dueno_apellidos'], ENT_QUOTES, 'UTF-8'); ?>
                            (<?php echo htmlspecialchars($producto['dueno_email'], ENT_QUOTES, 'UTF-8'); ?>)
                        <?php else: ?>
                            Sin asignar
                        <?php endif; ?>
                    </dd>
                </div>

                <div class="row">
                    <dt>Descripción</dt>
                    <dd><?php echo nl2br(htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8')); ?></dd>
                </div>
            </dl>

            <?php if (!empty($producto['imagen_extra1']) || !empty($producto['imagen_extra2'])): ?>
                <div class="profile-extra-photos">
                    <?php if (!empty($producto['imagen_extra1'])): ?>
                        <img src="<?php echo htmlspecialchars($producto['imagen_extra1'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto extra 1"
                            style="max-width: 130px; border-radius: 10px; margin-right: 8px;">
                    <?php endif; ?>

                    <?php if (!empty($producto['imagen_extra2'])): ?>
                        <img src="<?php echo htmlspecialchars($producto['imagen_extra2'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto extra 2"
                            style="max-width: 130px; border-radius: 10px;">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="profile-actions">
                <a href="editar-producto-admin.php?id=<?php echo (int) $producto['producto_id']; ?>" class="btn-edit">
                    Editar
                </a>
            </div>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
</body>

</html>