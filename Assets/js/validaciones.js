console.log("aplico js");

function clearAllErrors() {
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => input.classList.remove('input-error'));
}

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_REGEX = /^\d{10}$/;

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

    const nombre = document.getElementById('nombre').value.trim();
    const apellidos = document.getElementById('apellidos').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const password = document.getElementById('password').value;
    const direccion = document.getElementById('direccion').value.trim();
    const telefono = document.getElementById('telefono').value.trim();

    if (nombre === '') {
        document.getElementById('nombre').classList.add('input-error');
        isValid = false;
    }
    if (apellidos === '') {
        document.getElementById('apellidos').classList.add('input-error');
        isValid = false;
    }
    if (direccion === '') {
        document.getElementById('direccion').classList.add('input-error');
        isValid = false;
    }
    if (password === '') {
        document.getElementById('password').classList.add('input-error');
        isValid = false;
    }
    if (correo === '' || !EMAIL_REGEX.test(correo)) {
        document.getElementById('correo').classList.add('input-error');
        isValid = false;
    }
    if (telefono === '' || !PHONE_REGEX.test(telefono)) {
        document.getElementById('telefono').classList.add('input-error');
        isValid = false;
    }

    
    return isValid;
}
