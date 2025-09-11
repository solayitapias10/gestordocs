/********************************************
Script restablecer.js - Restablecer contraseña                   
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

const frmRestablecer = document.querySelector("#formularioRestablecer");
const claveNueva = document.querySelector("#claveNueva");
const claveConfirmar = document.querySelector("#claveConfirmar");
const btnRestablecer = document.querySelector("#btnRestablecer");
const passwordStrength = document.querySelector("#passwordStrength");
const strengthText = document.querySelector("#strengthText");

// Se ejecuta cuando el DOM está completamente cargado
document.addEventListener("DOMContentLoaded", function () {
    frmRestablecer.addEventListener("submit", function (e) {
        e.preventDefault();
        
        if (!validarFormulario()) {
            return;
        }

        restablecerContrasena();
    });

    // Validación en tiempo real de contraseñas
    claveNueva.addEventListener("input", function() {
        validarFortalezaClave(this.value);
        validarCoincidencia();
    });

    claveConfirmar.addEventListener("input", function() {
        validarCoincidencia();
    });
});

// Valida todo el formulario
function validarFormulario() {
    const clave = claveNueva.value;
    const confirmar = claveConfirmar.value;

    if (clave.length < 8) {
        alertaPerzonalizada("warning", "La contraseña debe tener al menos 8 caracteres");
        claveNueva.focus();
        return false;
    }

    if (clave !== confirmar) {
        alertaPerzonalizada("warning", "Las contraseñas no coinciden");
        claveConfirmar.focus();
        return false;
    }

    return true;
}

// Valida la fortaleza de la contraseña
function validarFortalezaClave(password) {
    const strengthBar = passwordStrength.querySelector('.progress-bar');
    let score = 0;
    let feedback = [];

    if (password.length === 0) {
        passwordStrength.style.display = 'none';
        return;
    }

    passwordStrength.style.display = 'block';

    // Criterios de fortaleza
    if (password.length >= 8) score += 20;
    if (password.length >= 12) score += 10;
    if (/[a-z]/.test(password)) score += 20;
    if (/[A-Z]/.test(password)) score += 20;
    if (/[0-9]/.test(password)) score += 20;
    if (/[^A-Za-z0-9]/.test(password)) score += 10;

    // Determinar color y texto
    let color, text;
    if (score < 40) {
        color = 'bg-danger';
        text = 'Muy débil';
    } else if (score < 60) {
        color = 'bg-warning';
        text = 'Débil';
    } else if (score < 80) {
        color = 'bg-info';
        text = 'Moderada';
    } else {
        color = 'bg-success';
        text = 'Fuerte';
    }

    // Actualizar barra de progreso
    strengthBar.className = `progress-bar ${color}`;
    strengthBar.style.width = `${score}%`;
    strengthText.textContent = `Fortaleza: ${text}`;
}

// Valida que las contraseñas coincidan
function validarCoincidencia() {
    const clave = claveNueva.value;
    const confirmar = claveConfirmar.value;

    if (confirmar.length === 0) {
        claveConfirmar.classList.remove('is-valid', 'is-invalid');
        return;
    }

    if (clave === confirmar) {
        claveConfirmar.classList.remove('is-invalid');
        claveConfirmar.classList.add('is-valid');
    } else {
        claveConfirmar.classList.remove('is-valid');
        claveConfirmar.classList.add('is-invalid');
    }
}

// Envía la nueva contraseña al servidor
function restablecerContrasena() {
    // Deshabilitar botón y mostrar loading
    btnRestablecer.disabled = true;
    btnRestablecer.innerHTML = '<i class="material-icons me-2">hourglass_empty</i>Actualizando...';

    const data = new FormData(frmRestablecer);
    const http = new XMLHttpRequest();
    const url = base_url + "recuperar/procesar";

    http.open("POST", url, true);
    
    http.onreadystatechange = function () {
        if (this.readyState === 4) {
            // Restaurar botón
            btnRestablecer.disabled = false;
            btnRestablecer.innerHTML = '<i class="material-icons me-2">security</i>Actualizar Contraseña';

            if (this.status === 200) {
                try {
                    const res = JSON.parse(this.responseText);
                    
                    if (res.tipo === "success") {
                        Swal.fire({
                            title: '¡Contraseña Actualizada!',
                            html: `
                                <div class="text-center">
                                    <i class="material-icons text-success" style="font-size: 4rem;">check_circle</i>
                                    <p class="mt-3">${res.mensaje}</p>
                                    <div class="alert alert-success mt-3">
                                        <strong>¡Perfecto!</strong><br>
                                        <small>Tu contraseña ha sido actualizada correctamente</small>
                                    </div>
                                </div>
                            `,
                            icon: null,
                            confirmButtonText: 'Iniciar Sesión',
                            confirmButtonColor: '#28a745',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = base_url + 'principal/index';
                            }
                        });
                        
                    } else {
                        alertaPerzonalizada(res.tipo, res.mensaje);
                    }
                } catch (error) {
                    console.error("Error parsing response:", error);
                    alertaPerzonalizada("error", "Error al procesar la respuesta del servidor");
                }
            } else {
                alertaPerzonalizada("error", `Error en la solicitud: ${this.status}`);
            }
        }
    };

    http.onerror = function () {
        btnRestablecer.disabled = false;
        btnRestablecer.innerHTML = '<i class="material-icons me-2">security</i>Actualizar Contraseña';
        alertaPerzonalizada("error", "Error de red. Verifique su conexión.");
    };

    http.send(data);
}

// Función para toggle de visibilidad de contraseña (reutilizar del login.js)
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}