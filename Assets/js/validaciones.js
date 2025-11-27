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

//dnskjndkdks

document.addEventListener("DOMContentLoaded", function () {

    
    const photoContainers = document.querySelectorAll(".pf-photo");

    photoContainers.forEach(container => {

        const input = container.querySelector(".pf-photo__input");
        const frame = container.querySelector(".pf-photo__frame");
        const removeBtn = container.querySelector(".pf-photo__remove");
        const label = container.querySelector(".pf-photo__drop");

        // para ver la imagen del producto enel recuadro antes de crear el producto
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
