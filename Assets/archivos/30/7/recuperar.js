/********************************************
Script recuperar.js - Solicitar recuperación                   
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

const frm = document.querySelector("#formularioRecuperar");
const correo = document.querySelector("#correo");
const btnRecuperar = document.querySelector("#btnRecuperar");

// Se ejecuta cuando el DOM está completamente cargado
document.addEventListener("DOMContentLoaded", function () {
    frm.addEventListener("submit", function (e) {
        e.preventDefault();
        
        if (correo.value.trim() === "") {
            alertaPerzonalizada("warning", "El correo electrónico es requerido");
            correo.focus();
            return;
        }

        if (!validarEmail(correo.value.trim())) {
            alertaPerzonalizada("warning", "Por favor, ingresa un correo electrónico válido");
            correo.focus();
            return;
        }

        enviarSolicitudRecuperacion();
    });

    // Validación en tiempo real del email
    correo.addEventListener("input", function() {
        const email = this.value.trim();
        
        if (email.length > 0) {
            if (validarEmail(email)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        } else {
            this.classList.remove('is-valid', 'is-invalid');
        }
    });
});

// Valida formato de email
function validarEmail(email) {
    const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
}

// Envía la solicitud de recuperación al servidor
function enviarSolicitudRecuperacion() {
    // Deshabilitar botón y mostrar loading
    btnRecuperar.disabled = true;
    btnRecuperar.innerHTML = '<i class="material-icons me-2">hourglass_empty</i>Enviando...';

    const data = new FormData(frm);
    const http = new XMLHttpRequest();
    const url = base_url + "recuperar/solicitar";

    http.open("POST", url, true);
    
    http.onreadystatechange = function () {
        if (this.readyState === 4) {
            // Restaurar botón
            btnRecuperar.disabled = false;
            btnRecuperar.innerHTML = '<i class="material-icons me-2">send</i>Enviar Instrucciones';

            if (this.status === 200) {
                try {
                    // Debug: Mostrar la respuesta en consola
                    console.log("Respuesta del servidor:", this.responseText);
                    
                    // Verificar si la respuesta parece ser JSON
                    const responseText = this.responseText.trim();
                    if (responseText.startsWith('<') || responseText.includes('Fatal error') || responseText.includes('<br />')) {
                        // La respuesta es HTML (probablemente un error de PHP)
                        console.error("El servidor devolvió HTML en lugar de JSON:", responseText);
                        
                        // Mostrar el error específico si es visible
                        if (responseText.includes('Fatal error')) {
                            const errorMatch = responseText.match(/Fatal error:([^<]*)/);
                            const errorMessage = errorMatch ? errorMatch[1].trim() : "Error fatal en el servidor";
                            alertaPerzonalizada("error", `Error del servidor: ${errorMessage}`);
                        } else {
                            alertaPerzonalizada("error", "El servidor devolvió un error. Revisa la consola para más detalles.");
                        }
                        
                        // Mostrar modal con información técnica para debug
                        Swal.fire({
                            title: 'Error del Servidor',
                            html: `
                                <div class="text-left">
                                    <p><strong>Se detectó un error en el servidor PHP.</strong></p>
                                    <p>Posibles causas:</p>
                                    <ul class="text-left">
                                        <li>Error en RecuperarModel.php (método setTimezone)</li>
                                        <li>Error en la clase Query</li>
                                        <li>Problema de configuración de base de datos</li>
                                    </ul>
                                    <details class="mt-3">
                                        <summary>Ver respuesta del servidor (para desarrolladores)</summary>
                                        <pre class="text-left bg-light p-2 mt-2" style="max-height: 200px; overflow-y: auto; font-size: 10px;">${responseText.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                                    </details>
                                </div>
                            `,
                            icon: 'error',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#dc3545'
                        });
                        return;
                    }
                    
                    // Intentar parsear como JSON
                    const res = JSON.parse(responseText);
                    
                    if (res.tipo === "success") {
                        Swal.fire({
                            title: '¡Correo Enviado!',
                            html: `
                                <div class="text-center">
                                    <i class="material-icons text-success" style="font-size: 4rem;">mark_email_read</i>
                                    <p class="mt-3">${res.mensaje}</p>
                                    <div class="alert alert-info mt-3">
                                        <strong>Revisa tu bandeja de entrada</strong><br>
                                        <small>Si no ves el correo, revisa tu carpeta de spam</small>
                                    </div>
                                </div>
                            `,
                            icon: null,
                            confirmButtonText: 'Ir al Login',
                            confirmButtonColor: '#007bff',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = base_url + 'principal/index';
                            }
                        });

                        frm.reset();
                        correo.classList.remove('is-valid', 'is-invalid');
                        
                    } else {
                        alertaPerzonalizada(res.tipo, res.mensaje);
                    }
                } catch (error) {
                    console.error("Error parsing response:", error);
                    console.error("Response text:", this.responseText);
                    alertaPerzonalizada("error", "Error al procesar la respuesta del servidor. Revisa la consola para más detalles.");
                }
            } else {
                console.error(`Error HTTP ${this.status}:`, this.responseText);
                alertaPerzonalizada("error", `Error en la solicitud: ${this.status}`);
            }
        }
    };

    http.onerror = function () {
        btnRecuperar.disabled = false;
        btnRecuperar.innerHTML = '<i class="material-icons me-2">send</i>Enviar Instrucciones';
        alertaPerzonalizada("error", "Error de red. Verifique su conexión.");
    };

    http.send(data);
}