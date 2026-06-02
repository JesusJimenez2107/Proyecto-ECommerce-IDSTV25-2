<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

// Si no es una recarga con mensaje de éxito/error, exigimos los datos del POST
if (!isset($_GET['msg'])) {
    if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['nombre'])) {
        header("Location: confirmar-compra.php");
        exit;
    }
}

require_once "app/controllers/cartController.php";

$cart = new CartController();
$usuario_id = (int) $_SESSION['usuario_id'];
$items = $cart->getCartItems($usuario_id);

// Si el carrito está vacío, PERO el usuario no acaba de hacer una compra exitosa, lo regresamos
if (count($items) === 0 && (!isset($_GET['msg']) || $_GET['msg'] !== 'success')) {
    header("Location: carrito.php?msg=empty");
    exit;
}

// Calcular totales
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$envio = 0.00; // Puedes cambiar esto si en el futuro cobras envío
$total = $subtotal + $envio;

$logged = isset($_SESSION['email']) && !empty($_SESSION['email']);
$cartCount = $cart->getCartCount($usuario_id);

// Recibir los datos de envío del formulario anterior
$nombre_envio = $_POST['nombre'] ?? '';
$direccion_envio = $_POST['direccion'] ?? '';
$ciudad_envio = $_POST['ciudad'] ?? '';
$telefono_envio = $_POST['telefono'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detalles de Pago – Raíz Viva</title>
    <link rel="stylesheet" href="Assets/styles/global.css" />
    <link rel="stylesheet" href="Assets/styles/checkout.css" />
    <style>
        /* Estilos generales de layout */
        .payment-layout {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .payment-form-col {
            flex: 1.5;
        }

        .payment-summary-col {
            flex: 1;
        }

        /* MAGIA CSS PARA ARREGLAR LOS INPUTS (Figma Style) */
        .payment-form-col label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
        }

        .payment-form-col input,
        .payment-form-col select {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            /* Bordes redondeados del Figma */
            margin-bottom: 16px;
            font-family: inherit;
        }

        /* Grids para las columnas */
        .grid-3-cols {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 16px;
        }

        .grid-2-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 16px;
        }

        /* Quitar margen inferior dentro de los grids para alineación perfecta */
        .grid-3-cols input,
        .grid-2-cols input {
            margin-bottom: 0;
        }

        /* Estilos del resumen (Derecha) */
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .summary-total {
            border-top: 1px solid #ccc;
            padding-top: 1rem;
            font-weight: bold;
        }

        .btn-outline-danger {
            background: transparent;
            border: 2px solid #e74c3c;
            color: #e74c3c;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-pay {
            background: #e07a5f;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <main class="page">
        <h1 class="ck-title">DETALLES DE PAGO</h1>

        <div class="payment-layout">
            <section class="ck-card payment-form-col">
                <p>Ingrese los datos de su método de pago</p>
                <br>
                <form action="app/controllers/cartController.php" method="POST" id="final-checkout-form">
                    <input type="hidden" name="action" value="checkout">

                    <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($nombre_envio); ?>">
                    <input type="hidden" name="direccion" value="<?php echo htmlspecialchars($direccion_envio); ?>">
                    <input type="hidden" name="ciudad" value="<?php echo htmlspecialchars($ciudad_envio); ?>">
                    <input type="hidden" name="telefono" value="<?php echo htmlspecialchars($telefono_envio); ?>">

                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" placeholder="Tucorreo@ejemplo.com" required>

                    <label for="titular">Nombre en tarjeta</label>
                    <input type="text" id="titular" name="titular" placeholder="Nombre que aparece en tarjeta" required>

                    <div class="grid-3-cols">
                        <div>
                            <label for="tarjeta">Número de tarjeta</label>
                            <input type="text" id="tarjeta" name="tarjeta" placeholder="1111 1111 1111 1111" required
                                maxlength="19">
                        </div>
                        <div>
                            <label for="cvv">CVV</label>
                            <input type="password" id="cvv" name="cvv" placeholder="•••" required maxlength="3">
                        </div>
                        <div>
                            <label for="expiracion">Expiración</label>
                            <input type="text" id="expiracion" name="expiracion" placeholder="MM/AA" required
                                maxlength="5">
                        </div>
                    </div>

                    <label for="dir_facturacion">Dirección de facturación</label>
                    <input type="text" id="dir_facturacion" name="dir_facturacion" placeholder="Calle, numero" required>

                    <div class="grid-2-cols">
                        <div>
                            <label for="ciudad_fact">Ciudad</label>
                            <input type="text" id="ciudad_fact" name="ciudad_fact" placeholder="Ciudad" required>
                        </div>
                        <div>
                            <label for="cp_fact">Código Postal</label>
                            <input type="text" id="cp_fact" name="cp_fact" placeholder="00000" required maxlength="5"
                                pattern="\d{5}" title="Debe contener exactamente 5 números"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <label for="pais">País</label>
                    <select id="pais" name="pais" required>
                        <option value="México">México</option>
                        <option value="Estados Unidos">Estados Unidos</option>
                        <option value="Canadá">Canadá</option>
                    </select>
                </form>
            </section>

            <section class="ck-card payment-summary-col">
                <h2 class="ck-section-title">Resumen de pago</h2>
                <br>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Envío:</span>
                    <span>$<?php echo number_format($envio, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>IVA:</span>
                    <span>Incluido</span>
                </div>
                <div class="summary-row summary-total">
                    <span>TOTAL:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>

                <br><br>
                <div style="display: flex; justify-content: space-between;">
                    <a href="confirmar-compra.php" class="btn-outline-danger">Cancelar</a>
                    <button type="submit" form="final-checkout-form" class="btn-pay">Confirmar</button>
                </div>
            </section>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'success'): ?>
                <div id="success-modal" class="modal is-visible"
                    style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                    <div
                        style="background: white; padding: 30px; border-radius: 12px; text-align: center; max-width: 400px; width: 90%;">
                        <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="#4CAF50" stroke-width="2"
                            style="margin-bottom: 15px;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <h2 style="color: #333; margin-bottom: 10px;">¡Compra exitosa!</h2>
                        <p style="color: #666; margin-bottom: 20px;">Tu pedido ha sido procesado correctamente y ya estamos
                            preparando tus plantitas.</p>
                        <button id="modal-ok-btn" class="btn-pay" style="width: 100%;">Volver al inicio</button>
                    </div>
                </div>
            <?php elseif ($_GET['msg'] === 'stock'): ?>
                <div id="success-modal" class="modal is-visible"
                    style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                    <div
                        style="background: white; padding: 30px; border-radius: 12px; text-align: center; max-width: 400px; width: 90%;">
                        <h2 style="color: #e74c3c; margin-bottom: 10px;">¡Ups! Stock insuficiente</h2>
                        <p style="color: #666; margin-bottom: 20px;">Alguien compró uno de estos artículos justo antes que tú y
                            ya no tenemos suficientes existencias.</p>
                        <a href="carrito.php" class="btn-outline-danger"
                            style="display: block; width: 100%; box-sizing: border-box;">Revisar mi carrito</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>

    <footer class="footer">
        <p>© Raíz Viva</p>
    </footer>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // --- CÓDIGO DEL MODAL QUE YA TENÍAS ---
        const overlay = document.getElementById("success-modal");
        const okBtn = document.getElementById("modal-ok-btn");

        if (overlay && okBtn) {
            okBtn.addEventListener("click", function () {
                overlay.classList.remove("is-visible");
                document.body.classList.remove("modal-open");
                window.location.href = 'index.php';
            });
        }

        // --- NUEVO CÓDIGO PARA AUTO-FORMATO DE TARJETA ---

        // 1. Formato para el Número de Tarjeta (agrega espacios cada 4 números)
        const tarjetaInput = document.getElementById('tarjeta');
        if (tarjetaInput) {
            tarjetaInput.addEventListener('input', function (e) {
                // Borra todo lo que no sea un número
                let valor = e.target.value.replace(/\D/g, '');
                // Agrega un espacio después de cada grupo de 4 números
                valor = valor.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = valor;
            });
        }

        // 2. Formato y Validación para la Expiración (MM/AA)
        const expInput = document.getElementById('expiracion');
        if (expInput) {
            // Evento 1: Da el formato (la diagonal) mientras el usuario escribe
            expInput.addEventListener('input', function (e) {
                let valor = e.target.value.replace(/\D/g, '');
                if (valor.length >= 3) {
                    valor = valor.slice(0, 2) + '/' + valor.slice(2, 4);
                }
                e.target.value = valor;

                // Limpia cualquier error previo mientras el usuario corrige
                e.target.setCustomValidity("");
            });

            // Evento 2: Valida la fecha exacta cuando el usuario sale del campo
            expInput.addEventListener('blur', function (e) {
                const valor = e.target.value;

                // Solo validamos si el campo está completo (MM/AA son 5 caracteres)
                if (valor.length === 5) {
                    const mesIngresado = parseInt(valor.substring(0, 2), 10);
                    const anioIngresado = parseInt(valor.substring(3, 5), 10);

                    const fechaActual = new Date();
                    const mesActual = fechaActual.getMonth() + 1; // getMonth() devuelve 0-11, así que sumamos 1
                    const anioActual = parseInt(fechaActual.getFullYear().toString().slice(-2), 10); // Saca los últimos 2 dígitos del año

                    let esValido = true;

                    // a) Validar que el mes exista (01 a 12)
                    if (mesIngresado < 1 || mesIngresado > 12) {
                        esValido = false;
                    }
                    // b) Validar que el año no sea del pasado
                    else if (anioIngresado < anioActual) {
                        esValido = false;
                    }
                    // c) Validar que, si es el año actual, el mes no haya pasado
                    else if (anioIngresado === anioActual && mesIngresado < mesActual) {
                        esValido = false;
                    }

                    // Si falló alguna regla, activamos el error del navegador
                    if (!esValido) {
                        e.target.setCustomValidity("La fecha de expiración no es válida o la tarjeta ya expiró.");
                        e.target.reportValidity(); // Muestra el mensaje en pantalla
                    }
                } else if (valor.length > 0 && valor.length < 5) {
                    // Si lo dejó a medias (ej. "12/2")
                    e.target.setCustomValidity("Ingresa la fecha completa en formato MM/AA.");
                    e.target.reportValidity();
                }
            });
        }

        // 3. Formato para el Nombre (Solo letras, acentos, espacios, guiones y apóstrofes)
        const titularInput = document.getElementById('titular');
        if (titularInput) {
            titularInput.addEventListener('input', function (e) {
                // Esta expresión regular reemplaza por "nada" cualquier cosa que NO sea:
                // a-z, A-Z, vocales con acento, ñ, ü, espacios (\s), guiones (\-) o apóstrofes (')
                e.target.value = e.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-\']/g, '');
            });
        }
    });
</script>

</html>