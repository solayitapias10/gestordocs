/********************************************
Script registro.js                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Elementos del formulario
const frm = document.querySelector('#formularioRegistro');
const nombre = document.querySelector('#nombre');
const apellido = document.querySelector('#apellido');
const correo = document.querySelector('#correo');
const telefono = document.querySelector('#telefono');
const direccion = document.querySelector('#direccion');
const btnRegistro = document.querySelector('#btnRegistro');

// Expresiones Regulares para Validación
const regexNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,50}$/;
const regexCorreo = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
const regexTelefono = /^[3][0-9]{9}$/; 

// Funciones de Validación

// Valida que el nombre y apellido contengan solo letras y espacios.
function validarNombre(input) {
    const valor = input.value.trim();
    const esValido = regexNombre.test(valor) && valor.length >= 2;
    
    if (esValido) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    }
}

// Valida que el correo electrónico tenga un formato correcto.
function validarCorreo(input) {
    const valor = input.value.trim();
    const esValido = regexCorreo.test(valor);
    
    if (esValido) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    }
}

// Valida que el teléfono sea un número de celular colombiano.
function validarTelefono(input) {
    const valor = input.value.replace(/\D/g, ''); 
    const esValido = regexTelefono.test(valor);
    
    if (esValido) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    }
}

// Valida que la dirección tenga una longitud mínima.
function validarDireccion(input) {
    const valor = input.value.trim();
    const esValido = valor.length >= 10;
    
    if (esValido) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    }
}

// Formatea el campo de teléfono, eliminando caracteres no numéricos.
function formatearTelefono(input) {
    let valor = input.value.replace(/\D/g, ''); 
    if (valor.length > 10) valor = valor.substring(0, 10); 
    input.value = valor;
}

// Limpia el nombre o apellido, eliminando caracteres no deseados y formateando el texto.
function limpiarNombre(input) {
    let valor = input.value;
    valor = valor.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '');
    valor = valor.replace(/\s+/g, ' ');
    valor = valor.replace(/\b\w/g, l => l.toUpperCase());
    input.value = valor;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function () {
    
    nombre.addEventListener('input', function() {
        limpiarNombre(this);
        validarNombre(this);
    });
    
    apellido.addEventListener('input', function() {
        limpiarNombre(this);
        validarNombre(this);
    });
    
    correo.addEventListener('input', function() {
        validarCorreo(this);
    });
    
    telefono.addEventListener('input', function() {
        formatearTelefono(this);
        validarTelefono(this);
    });
    
    direccion.addEventListener('input', function() {
        validarDireccion(this);
    });
    
    frm.addEventListener("submit", function (e) {
        e.preventDefault();
        
        const nombreValido = validarNombre(nombre);
        const apellidoValido = validarNombre(apellido);
        const correoValido = validarCorreo(correo);
        const telefonoValido = validarTelefono(telefono);
        const direccionValida = validarDireccion(direccion);
        
        if (!nombreValido || !apellidoValido || !correoValido || !telefonoValido || !direccionValida) {
            alertaPerzonalizada("warning", "Por favor, corrige los errores en el formulario");
            return;
        }
        
        if (nombre.value.trim().length < 2) {
            alertaPerzonalizada("warning", "El nombre debe tener al menos 2 caracteres");
            nombre.focus();
            return;
        }
        
        if (apellido.value.trim().length < 2) {
            alertaPerzonalizada("warning", "El apellido debe tener al menos 2 caracteres");
            apellido.focus();
            return;
        }
        
        if (!regexCorreo.test(correo.value.trim())) {
            alertaPerzonalizada("warning", "Por favor, ingresa un correo electrónico válido");
            correo.focus();
            return;
        }
        
        if (!regexTelefono.test(telefono.value.replace(/\D/g, ''))) {
            alertaPerzonalizada("warning", "El teléfono debe ser un número de celular colombiano válido (10 dígitos comenzando con 3)");
            telefono.focus();
            return;
        }
        
        if (direccion.value.trim().length < 10) {
            alertaPerzonalizada("warning", "La dirección debe tener al menos 10 caracteres");
            direccion.focus();
            return;
        }
        
        btnRegistro.disabled = true;
        btnRegistro.innerHTML = '<i class="material-icons me-2">hourglass_empty</i>Procesando...';
        
        const data = new FormData(frm);
        data.set('telefono', telefono.value.replace(/\D/g, ''));
        
        const http = new XMLHttpRequest();
        const url = base_url + "principal/registrar";
        http.open("POST", url, true);
        http.send(data);

        http.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                btnRegistro.disabled = false;
                btnRegistro.innerHTML = '<i class="material-icons me-2">person_add</i>Enviar Solicitud';
                
                try {
                    if (!this.responseText || this.responseText.trim() === '') {
                        throw new Error('Respuesta vacía del servidor');
                    }

                    const responseText = this.responseText.trim();
                    if (!responseText.startsWith('{') && !responseText.startsWith('[')) {
                        console.error('Respuesta del servidor no es JSON válido:', responseText);
                        throw new Error('El servidor devolvió una respuesta inválida');
                    }

                    const res = JSON.parse(responseText);

                    if (!res.tipo || !res.mensaje) {
                        throw new Error('Respuesta del servidor con formato incorrecto');
                    }

                    alertaPerzonalizada(res.tipo, res.mensaje);

                    if (res.tipo == "success") {
                        Swal.fire({
                            title: '¡Solicitud Enviada!',
                            html: `
                                <div class="text-center">
                                    <i class="material-icons" style="font-size: 4rem; color: #28a745;">check_circle</i>
                                    <p class="mt-3">Tu solicitud de registro ha sido enviada exitosamente.</p>
                                    <p class="text-muted">Un administrador revisará tu solicitud y te notificará por correo electrónico cuando sea aprobada.</p>
                                    <p class="text-info"><strong>¡Mantente atento a tu correo!</strong></p>
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
                        document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                            el.classList.remove('is-valid', 'is-invalid');
                        });
                        
                    } else {
                        Swal.fire({
                            title: 'Error en el Registro',
                            text: res.mensaje,
                            icon: 'error',
                            confirmButtonText: 'Intentar de Nuevo',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } catch (error) {
                    console.error('Error procesando respuesta:', error);
                    console.error('Respuesta recibida:', this.responseText);

                    Swal.fire({
                        title: 'Error del Sistema',
                        text: 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo más tarde.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#dc3545'
                    });
                }
            } else if (this.readyState == 4) {
                btnRegistro.disabled = false;
                btnRegistro.innerHTML = '<i class="material-icons me-2">person_add</i>Enviar Solicitud';
                
                console.error('Error HTTP:', this.status, this.statusText);
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Verifica tu conexión a internet.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#dc3545'
                });
            }
        };
    });
});