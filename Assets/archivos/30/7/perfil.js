/********************************************
Script perfil.js
Creado por el Gaes 1:
Anyi Solayi Tapias
Sharit Delgado Pinzón
Durly Yuranni Sánchez Carillo
Año: 2025
SENA - CSET - ADSO
 ********************************************/

// Obtiene referencias a los formularios de la página.
const formUsuario = document.querySelector("#formUsuario");
const formClave = document.querySelector("#formClave");
const formAvatar = document.querySelector("#formAvatar");

// Cargar datos iniciales del usuario al iniciar la página.
cargarDatosUsuario();

// Maneja la actualización de la información personal del usuario.
formUsuario.addEventListener("submit", function (e) {
    e.preventDefault();
    if (
        formUsuario.nombre.value == '' ||
        formUsuario.apellido.value == '' ||
        formUsuario.correo.value == '' ||
        formUsuario.telefono.value == '' ||
        formUsuario.direccion.value == ''
    ) {
        alertaPerzonalizada("warning", "Todos los campos son requeridos");
        return;
    }
    const data = new FormData(formUsuario);
    const http = new XMLHttpRequest();
    const url = base_url + 'usuarios/actualizarPerfil';
    http.open("POST", url, true);
    http.send(data);
    http.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            alertaPerzonalizada(res.tipo, res.mensaje);
            if (res.tipo == 'success') {
                cargarDatosUsuario();
            }
        }
    };
});

// Maneja el cambio de contraseña del usuario.
formClave.addEventListener("submit", function (e) {
    e.preventDefault();
    if (
        formClave.claveActual.value == '' ||
        formClave.claveNueva.value == '' ||
        formClave.claveConfirmar.value == ''
    ) {
        alertaPerzonalizada("warning", "Todos los campos son requeridos");
        return;
    }
    if (formClave.claveNueva.value !== formClave.claveConfirmar.value) {
        alertaPerzonalizada("warning", "Las contraseñas no coinciden");
        return;
    }
    const data = new FormData(formClave);
    const http = new XMLHttpRequest();
    const url = base_url + 'usuarios/cambiarClave';
    http.open("POST", url, true);
    http.send(data);
    http.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            alertaPerzonalizada(res.tipo, res.mensaje);
            if (res.tipo == 'success') {
                formClave.reset();
            }
        }
    };
});

// Maneja el cambio de avatar del usuario.
if (formAvatar) {
    formAvatar.addEventListener("submit", function (e) {
        e.preventDefault();
        if (!formAvatar.avatar.files.length) {
            alertaPerzonalizada("warning", "Selecciona una imagen para el avatar");
            return;
        }
        const data = new FormData(formAvatar);
        const http = new XMLHttpRequest();
        const url = base_url + 'usuarios/cambiarAvatar';
        http.open("POST", url, true);
        http.send(data);
        http.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                alertaPerzonalizada(res.tipo, res.mesaje);
                if (res.tipo == 'success') {
                    document.querySelector(".avatar-preview img").src = res.avatar;
                }
            }
        };
    });
}

// Obtiene y carga los datos del perfil del usuario en el formulario.
function cargarDatosUsuario() {
    const http = new XMLHttpRequest();
    const url = base_url + 'usuarios/miPerfil';
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function () {
        if (this.readyState == 4) {
            if (this.status == 200) {
                try {
                    const res = JSON.parse(this.responseText);
                    formUsuario.id.value = res.id;
                    formUsuario.nombre.value = res.nombre;
                    formUsuario.apellido.value = res.apellido;
                    formUsuario.correo.value = res.correo;
                    formUsuario.telefono.value = res.telefono;
                    formUsuario.direccion.value = res.direccion;
                    if (formUsuario.perfil) {
                        formUsuario.perfil.value = res.perfil || '';
                    }
                } catch (e) {
                    console.error("Error al analizar JSON:", e);
                    alertaPerzonalizada("error", "Error al cargar los datos del usuario. Respuesta no válida del servidor.");
                    console.log("Texto de respuesta:", this.responseText.substring(0, 500));
                }
            } else {
                alertaPerzonalizada("error", "Error al cargar los datos del usuario. Código de estado: " + this.status);
            }
        }
    };
}