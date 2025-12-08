function clearAllErrors() {
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => input.classList.remove('input-error'));
}

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_REGEX = /^\d{10}$/;

// Solo letras (incluye acentos y ñ)
const NAME_REGEX = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;

function validateLogin() {
    clearAllErrors();
    let isValid = true;

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (email === '' || !EMAIL_REGEX.test(email)) {
        document.getElementById('email').classList.add('input-error');
        isValid = false;
    }

    if (password === '') {
        document.getElementById('password').classList.add('input-error');
        isValid = false;
    }

    return isValid;
}

function validateRegister() {
    clearAllErrors();
    let isValid = true;

    const nombreInput = document.getElementById('nombre');
    const apellidosInput = document.getElementById('apellidos');
    const correoInput = document.getElementById('correo');
    const passwordInput = document.getElementById('password');
    const direccionInput = document.getElementById('direccion');
    const telefonoInput = document.getElementById('telefono');

    const nombre = nombreInput.value.trim();
    const apellidos = apellidosInput.value.trim();
    const correo = correoInput.value.trim();
    const password = passwordInput.value;
    const direccion = direccionInput.value.trim();
    const telefono = telefonoInput.value.trim();

    // NOMBRE
    if (nombre === '' || !NAME_REGEX.test(nombre)) {
        nombreInput.classList.add('input-error');
        isValid = false;
    }

    // APELLIDOS
    if (apellidos === '' || !NAME_REGEX.test(apellidos)) {
        apellidosInput.classList.add('input-error');
        isValid = false;
    }

    // DIRECCIÓN
    if (direccion === '') {
        direccionInput.classList.add('input-error');
        isValid = false;
    }

    // CONTRASEÑA
    if (password === '') {
        passwordInput.classList.add('input-error');
        isValid = false;
    }

    // CORREO
    if (correo === '' || !EMAIL_REGEX.test(correo)) {
        correoInput.classList.add('input-error');
        isValid = false;
    }

    // TELÉFONO
    if (telefono === '' || !PHONE_REGEX.test(telefono)) {
        telefonoInput.classList.add('input-error');
        isValid = false;
    }

    return isValid;
}

document.addEventListener("DOMContentLoaded", function () {

    //Limpiar en tiempo real nombre y apellidos (solo letras)
    const nombre = document.getElementById('nombre');
    const apellidos = document.getElementById('apellidos');
    const telefono = document.getElementById('telefono');

    function soloLetras(input) {
        input.value = input.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, "");
    }

    function soloNumeros(input) {
        // Elimina todo lo que no sea dígito y limita a 10 caracteres
        input.value = input.value.replace(/\D/g, "").slice(0, 10);
    }

    if (nombre) {
        nombre.addEventListener("input", function () {
            soloLetras(nombre);
        });
    }

    if (apellidos) {
        apellidos.addEventListener("input", function () {
            soloLetras(apellidos);
        });
    }

    //Teléfono solo números en tiempo real
    if (telefono) {
        telefono.addEventListener("input", function () {
            soloNumeros(telefono);
        });
    }

    //LÓGICA DE LAS FOTOS
    const photoContainers = document.querySelectorAll(".pf-photo");

    photoContainers.forEach(container => {

        const input = container.querySelector(".pf-photo__input");
        const frame = container.querySelector(".pf-photo__frame");
        const removeBtn = container.querySelector(".pf-photo__remove");
        const label = container.querySelector(".pf-photo__drop");

        // para ver la imagen del producto en el recuadro antes de crear el producto
        input.addEventListener("change", function () {
            const file = this.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    frame.style.backgroundImage = `url('${e.target.result}')`;
                    frame.style.backgroundSize = "cover";
                    frame.style.backgroundPosition = "center";
                    frame.style.border = "none";

                    label.style.display = "none";
                    removeBtn.disabled = false;
                };

                reader.readAsDataURL(file);
            }
        });

        // elimina la imagen del recuadro antes de crear el producto
        removeBtn.addEventListener("click", function () {

            input.value = "";                 
            frame.style.backgroundImage = ""; 
            frame.style.border = "";          
            label.style.display = "block";    

            removeBtn.disabled = true;
        });
    });

});
