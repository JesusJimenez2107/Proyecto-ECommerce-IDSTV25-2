<?php
require_once __DIR__ . "/../../config/security.php";
require_once __DIR__ . "/../../config/connectionController.php";

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if (isset($_SESSION['usuario_id'])) {
    require_once __DIR__ . "/../../controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount((int) $_SESSION['usuario_id']);
}

// Cargar categorías dinámicamente desde la base de datos (evita hardcodeo en 25+ archivos)
$navCategorias = [];
$dbConn = (new ConnectionController())->connect();
if ($dbConn) {
    $resNavCat = $dbConn->query("SELECT categoria_id, nombre FROM categoria ORDER BY categoria_id ASC");
    if ($resNavCat) {
        $navCategorias = $resNavCat->fetch_all(MYSQLI_ASSOC);
    }
}
?>
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
                <?php foreach ($navCategorias as $cat): ?>
                    <a role="menuitem" href="productos.php?cat=<?php echo (int) $cat['categoria_id']; ?>" class="nav-menu__item">
                        <?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <form action="productos.php" method="GET" class="search" role="search">
            <input type="search" name="search" placeholder="Buscar" aria-label="Buscar"
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : ''; ?>">
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
                        if (!empty($_SESSION['nombre'])) {
                            $primerNombre = explode(' ', trim($_SESSION['nombre']))[0];
                            echo htmlspecialchars(ucfirst(strtolower($primerNombre)), ENT_QUOTES, 'UTF-8');
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
                <span><?php echo (int) $cartCount; ?></span>
            </a>
        </div>
    </div>
</header>