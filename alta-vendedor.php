<?php
require_once __DIR__ . "/app/config/security.php";
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Alta de Vendedor – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css?v=2" />
    <style>
        .page-header-box {
            background-color: #f5efe6;
            padding: 1.2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .page-header-box h1 {
            margin: 0;
            font-size: 2.2rem;
            color: #111;
            font-weight: bold;
            text-transform: uppercase;
        }

        .seller-form-card {
            background-color: #f5efe6;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 1.3rem;
            text-transform: uppercase;
            font-weight: bold;
            color: #111;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }
        .section-title:first-child {
            margin-top: 0;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.5rem;
        }
        .form-grid-2 .form-group, .form-grid-3 .form-group {
            margin-bottom: 0;
        }

        .form-label {
            font-size: 0.95rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: #111;
            font-weight: 500;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .terms-text {
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
        }
        .btn-cancel {
            background: transparent;
            border: 2px solid #e74c3c;
            color: #e74c3c;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            cursor: pointer;
        }
        .btn-submit {
            background: #e07a5f;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            cursor: pointer;
        }
    </style>
</head>
<body>
    
    <?php require_once "app/views/layouts/header.php"; ?>

    <main class="page">
        <div class="page-header-box">
            <h1>Alta de Vendedor</h1>
        </div>

        <section class="seller-form-card">
            <form action="app/controllers/vendedorController.php" method="POST">
                
                <h2 class="section-title">Datos Personales y Fiscales</h2>
                <div class="form-group">
                    <label class="form-label" for="nombre_completo">Nombre completo</label>
                    <input class="form-input" type="text" id="nombre_completo" name="nombre_completo" placeholder="Tu nombre completo (nombre(s) apellido(s))" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="rfc">RFC</label>
                        <input class="form-input" type="text" id="rfc" name="rfc" required maxlength="13" 
                               pattern="[A-Z0-9]{12,13}" 
                               title="Debe contener 12 o 13 letras y números"
                               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="curp">CURP</label>
                        <input class="form-input" type="text" id="curp" name="curp" required maxlength="18" 
                               pattern="[A-Z0-9]{18}" 
                               title="Debe contener exactamente 18 letras y números"
                               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                    </div>
                </div>

                <h2 class="section-title">Dirección de Recolección (Para Paquetería)</h2>
                <div class="form-group">
                    <label class="form-label" for="calle_numero">Calle y número</label>
                    <input class="form-input" type="text" id="calle_numero" name="calle_numero" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="colonia">Colonia</label>
                    <input class="form-input" type="text" id="colonia" name="colonia" required>
                </div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="codigo_postal">Código Postal</label>
                        <input class="form-input" type="text" id="codigo_postal" name="codigo_postal" required maxlength="5"
                               pattern="\d{5}" 
                               title="Debe contener exactamente 5 números"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ciudad">Ciudad</label>
                        <input class="form-input" type="text" id="ciudad" name="ciudad" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado</label>
                        <input class="form-input" type="text" id="estado" name="estado" required>
                    </div>
                </div>

                <h2 class="section-title">Datos de Cobro</h2>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="titular_cuenta">Titular de la cuenta</label>
                        <input class="form-input" type="text" id="titular_cuenta" name="titular_cuenta" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="clabe">CLABE Interbancaria (18 dígitos)</label>
                        <input class="form-input" type="text" id="clabe" name="clabe" required maxlength="18" 
                               pattern="\d{18}" 
                               title="Debe contener exactamente 18 números"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>

                <h2 class="section-title">Acuerdos Comerciales</h2>
                <p class="terms-text">
                    Al registrarte como vendedor, aceptas que Raíz Viva actuará como intermediario en tus transacciones. 
                    Comprendes y aceptas el esquema de comisiones estándar, el cual consiste en una retención del 10% sobre el valor total de cada venta por uso de plataforma y procesamiento de pagos.
                </p>
                <label class="checkbox-group">
                    <input type="checkbox" name="acepta_terminos" required>
                    Acepto los términos y condiciones de vendedor
                </label>
                <label class="checkbox-group">
                    <input type="checkbox" name="acepta_comisiones" required>
                    Acepto el esquema de comisiones (10%)
                </label>

                <div class="form-actions">
                    <a href="mi-cuenta.php" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-submit">Darme de alta</button>
                </div>
            </form>
        </section>
    </main>

    <?php require_once "app/views/layouts/footer.php"; ?>
</body>
</html>