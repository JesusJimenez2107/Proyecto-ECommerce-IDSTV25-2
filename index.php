<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = 0;

if (isset($_SESSION['usuario_id'])) {
    require_once "app/controllers/cartController.php";
    $cartCtrl = new CartController();
    $cartCount = $cartCtrl->getCartCount((int) $_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raíz Viva – Inicio</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/home.css">
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

            <form class="search" role="search">
                <input type="search" placeholder="Buscar" aria-label="Buscar">
                <button type="submit" aria-label="Buscar">
                    <!-- lupa -->
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
                        <span>Mi cuenta</span>
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

    <!-- banner -->
    <section class="banner">
        <img src="Assets/img/banner-new-arrivals.jpg" alt="New Plants Arrival">
    </section>

    <main class="page">

        <!-- CATEGORÍAS -->
        <section class="categories" aria-label="Categorías">
            <a class="card" href="productos.php?cat=1">
                <img src="Assets/img/cat-interior.png" alt="Plantas de interior">
                <h3>PLANTAS DE<br>INTERIOR</h3>
            </a>

            <a class="card" href="productos.php?cat=2">
                <img src="Assets/img/cat-exterior.png" alt="Plantas de exterior">
                <h3>PLANTAS DE<br>EXTERIOR</h3>
            </a>

            <a class="card" href="productos.php?cat=3">
                <img src="Assets/img/cat-bajo.png" alt="Bajo mantenimiento">
                <h3>BAJO<br>MANTENIMIENTO</h3>
            </a>

            <a class="card" href="productos.php?cat=4">
                <img src="Assets/img/cat-aromatica.png" alt="Aromáticas y comestibles">
                <h3>AROMÁTICAS Y<br>COMESTIBLES</h3>
            </a>

            <a class="card" href="productos.php?cat=5">
                <img src="Assets/img/cat-macetas.png" alt="Macetas y accesorios">
                <h3>MACETAS Y<br>ACCESORIOS</h3>
            </a>

            <a class="card" href="productos.php?cat=6">
                <img src="Assets/img/cat-cuidados.png" alt="Cuidados y bienestar">
                <h3>CUIDADOS Y<br>BIENESTAR</h3>
            </a>
        </section>

    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>

</html>