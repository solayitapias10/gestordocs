// ================================================
// FUNCIONES DE ALERTAS Y CONFIRMACIÓN
// ================================================

// Muestra una alerta personalizada utilizando SweetAlert2
function alertaPerzonalizada(type, mensaje) {
    Swal.fire({
        position: "top-end",
        icon: type,
        title: mensaje,
        showConfirmButton: false,
        timer: 1500
    });
}

// Muestra un modal de confirmación para eliminar un registro con método POST
function eliminarRegistro(title, text, accion, url, table) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: accion
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const http = new XMLHttpRequest();
            http.open("POST", url, true);
            
            http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            http.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            http.send();
            
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    Swal.close();
                    
                    try {
                        const responseText = this.responseText.trim();
                        
                        if (responseText.startsWith('<') || responseText.startsWith('<!DOCTYPE')) {
                            console.error('Error del servidor:', responseText);
                            alertaPerzonalizada('error', 'Error del servidor. Por favor, revisa la consola.');
                            return;
                        }
                        
                        const res = JSON.parse(responseText);
                        const mensaje = res.mensaje || res.mesaje || 'Operación completada';
                        alertaPerzonalizada(res.tipo, mensaje);
                        
                        if (res.tipo == 'success') {
                            if (table != null) {
                                table.ajax.reload();
                            } else {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }
                        }
                    } catch (error) {
                        console.error('Error al parsear JSON:', error);
                        console.error('Respuesta del servidor:', this.responseText);
                        alertaPerzonalizada('error', 'Error al procesar la respuesta del servidor.');
                    }
                } else if (this.readyState == 4) {
                    Swal.close();
                    
                    console.error('Error HTTP:', this.status, this.statusText);
                    console.error('Respuesta:', this.responseText);
                    
                    if (this.status === 404) {
                        alertaPerzonalizada('error', 'Recurso no encontrado (404)');
                    } else if (this.status === 500) {
                        alertaPerzonalizada('error', 'Error interno del servidor (500)');
                    } else {
                        alertaPerzonalizada('error', 'Error de conexión. Código: ' + this.status);
                    }
                }
            };
        }
    });
}

// Muestra un modal de confirmación para eliminar un registro con método GET
function eliminarRegistroGET(title, text, accion, url, table) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: accion
    }).then((result) => {
        if (result.isConfirmed) {
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    try {
                        const responseText = this.responseText.trim();
                        
                        if (responseText.startsWith('<') || responseText.startsWith('<!DOCTYPE')) {
                            console.error('Error del servidor:', responseText);
                            alertaPerzonalizada('error', 'Error del servidor. Por favor, revisa la consola.');
                            return;
                        }
                        
                        const res = JSON.parse(responseText);
                        const mensaje = res.mensaje || res.mesaje || 'Operación completada';
                        alertaPerzonalizada(res.tipo, mensaje);
                        
                        if (res.tipo == 'success') {
                            if (table != null) {
                                table.ajax.reload();
                            } else {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }
                        }
                    } catch (error) {
                        console.error('Error al parsear JSON:', error);
                        console.error('Respuesta del servidor:', this.responseText);
                        alertaPerzonalizada('error', 'Error al procesar la respuesta del servidor.');
                    }
                } else if (this.readyState == 4) {
                    alertaPerzonalizada('error', 'Error de conexión. Código: ' + this.status);
                }
            };
        }
    });
}

// ================================================
// FUNCIONES PARA EL PERFIL DE USUARIO Y REGISTRO
// ================================================

// Muestra u oculta la contraseña en un campo de input
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

// Inicializa las validaciones de los campos de contraseña en el perfil
function inicializarValidacionesPerfil() {
    const inputClaveNueva = document.getElementById('inputClaveNueva');
    if (inputClaveNueva) {
        inputClaveNueva.addEventListener('input', function() {
            const password = this.value;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasMinLength = password.length >= 8;
            
            if (hasUpperCase && hasMinLength) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (password.length > 0) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }

    const inputClaveConfirmar = document.getElementById('inputClaveConfirmar');
    if (inputClaveConfirmar) {
        inputClaveConfirmar.addEventListener('input', function() {
            const password = document.getElementById('inputClaveNueva').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
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
    }
}

// ================================================
// FUNCIONES ADICIONALES PARA VALIDACIONES
// ================================================

// Valida si un campo de texto contiene un formato de correo electrónico válido
function validarEmail(input) {
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const isValid = emailRegex.test(input.value.trim());
    
    if (isValid) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else if (input.value.length > 0) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    } else {
        input.classList.remove('is-valid', 'is-invalid');
        return false;
    }
}

// Valida si un campo de texto contiene un formato de teléfono colombiano válido
function validarTelefonoColombiano(input) {
    const telefonoRegex = /^[3][0-9]{9}$/;
    const numeroLimpio = input.value.replace(/\D/g, '');
    const isValid = telefonoRegex.test(numeroLimpio);
    
    if (isValid) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else if (input.value.length > 0) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    } else {
        input.classList.remove('is-valid', 'is-invalid');
        return false;
    }
}

// Valida si un campo de texto solo contiene letras y tiene una longitud mínima
function validarSoloLetras(input) {
    const letrasRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
    const isValid = letrasRegex.test(input.value.trim()) && input.value.trim().length >= 2;
    
    if (isValid) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else if (input.value.length > 0) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    } else {
        input.classList.remove('is-valid', 'is-invalid');
        return false;
    }
}

// Limpia el input de números y caracteres especiales, y capitaliza cada palabra
function formatearNombre(input) {
    let valor = input.value;
    valor = valor.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '');
    valor = valor.replace(/\s+/g, ' ');
    valor = valor.replace(/\b\w/g, l => l.toUpperCase());
    input.value = valor;
}

// Limpia el input de caracteres no numéricos y limita la longitud a 10 dígitos
function formatearTelefono(input) {
    let valor = input.value.replace(/\D/g, ''); 
    if (valor.length > 10) valor = valor.substring(0, 10); 
    input.value = valor;
}

// Calcula la fortaleza de una contraseña y devuelve un objeto con el resultado
function calcularFortalezaPassword(password) {
    let fortaleza = 0;
    let texto = '';
    let color = '';
    
    if (password.length >= 8) fortaleza += 25;
    if (/[a-z]/.test(password)) fortaleza += 25;
    if (/[A-Z]/.test(password)) fortaleza += 25;
    if (/\d/.test(password)) fortaleza += 15;
    if (/[^A-Za-z0-9]/.test(password)) fortaleza += 10; 
    if (fortaleza < 25) {
        texto = 'Muy débil';
        color = 'bg-danger';
    } else if (fortaleza < 50) {
        texto = 'Débil';
        color = 'bg-warning';
    } else if (fortaleza < 75) {
        texto = 'Media';
        color = 'bg-info';
    } else {
        texto = 'Fuerte';
        color = 'bg-success';
    }
    
    return { fortaleza, texto, color };
}

// ================================================
// FUNCIONES UTILITARIAS GENERALES
// ================================================

// Muestra un estado de carga en un botón y devuelve una función para restaurarlo
function mostrarLoadingBoton(boton, textoLoading = 'Procesando...') {
    const textoOriginal = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML = `<i class="material-icons me-2">hourglass_empty</i>${textoLoading}`;
    
    return function restaurarBoton() {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
    };
}

// Resetea un formulario y elimina las clases de validación
function limpiarFormulario(formulario) {
    formulario.reset();
    formulario.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
        el.classList.remove('is-valid', 'is-invalid');
    });
}

// Valida si todos los campos requeridos de un formulario están completos
function validarFormularioCompleto(formulario) {
    const camposRequeridos = formulario.querySelectorAll('[required]');
    let todosValidos = true;
    
    camposRequeridos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.classList.add('is-invalid');
            todosValidos = false;
        }
    });
    
    return todosValidos;
}

// Inicializa los listeners de eventos al cargar el DOM
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('inputClaveNueva') || document.getElementById('inputClaveConfirmar')) {
        inicializarValidacionesPerfil();
    }
    
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('input', () => validarEmail(input));
    });
    
    const nombreInputs = document.querySelectorAll('input[name="nombre"], input[name="apellido"]');
    nombreInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatearNombre(this);
            validarSoloLetras(this);
        });
    });
    
    const telefonoInputs = document.querySelectorAll('input[name="telefono"]');
    telefonoInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatearTelefono(this);
            validarTelefonoColombiano(this);
        });
    });
});