<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raíz Viva – Inicio</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <link rel="stylesheet" href="Assets/styles/home.css">
</head>

<body>
    <?php require_once "app/views/layouts/header.php"; ?>

    <section class="banner">
        <img src="Assets/img/banner-new-arrivals.jpg" alt="New Plants Arrival">
    </section>

    <main class="page">
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

    <?php require_once "app/views/layouts/footer.php"; ?>
</body>

</html>