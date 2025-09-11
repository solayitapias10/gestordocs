/********************************************
Script login.js                   
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

const frm = document.querySelector("#formulario");
const correo = document.querySelector("#correo");
const clave = document.querySelector("#clave");

// Se ejecuta cuando el DOM está completamente cargado.
document.addEventListener("DOMContentLoaded", function () {
  clearTokens();

  if (frm) {
    frm.addEventListener("submit", function (e) {
      e.preventDefault();
      if (correo.value === "" || clave.value === "") {
        alertaPerzonalizada("warning", "Todos los campos con * son requeridos");
      } else {
        enviarCredenciales();
      }
    });
  }
});


// Limpia los tokens de autenticación de diferentes ubicaciones de almacenamiento.
function clearTokens() {
  localStorage.removeItem("token");
  localStorage.removeItem("expires_at");
  localStorage.removeItem("token_user");
  document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
  sessionStorage.removeItem("token");
  sessionStorage.removeItem("expires_at");
  sessionStorage.removeItem("token_user");
  sessionStorage.removeItem("token_expiry");
  sessionStorage.removeItem("user_role");
}

// Envía las credenciales de inicio de sesión al servidor para su validación.
function enviarCredenciales() {
  const data = new FormData(frm);
  const http = new XMLHttpRequest();
  const url = base_url + "principal/validar";

  http.open("POST", url, true);
  
  // UN SOLO MANEJADOR DE RESPUESTA
  http.onreadystatechange = function () {
    if (this.readyState === 4) {
      if (this.status === 200) {
        try {
          const res = JSON.parse(this.responseText);
          procesarRespuestaLogin(res);
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
    alertaPerzonalizada("error", "Error de red. Verifique su conexión.");
  };

  http.send(data);
}

// Procesa la respuesta del servidor después de un intento de inicio de sesión.
function procesarRespuestaLogin(res) {
  console.log("Respuesta del servidor:", res); // Para debug
  
  // Mostrar alerta solo si hay error
  if (res.tipo !== "success") {
    alertaPerzonalizada(res.tipo, res.mensaje);
    return;
  }

  // Si el login es exitoso
  if (res.tipo === "success" && res.token) {
    // Guardar token de manera consistente
    localStorage.setItem("token", res.token);
    localStorage.setItem("token_user", res.token);
    sessionStorage.setItem("token", res.token);
    sessionStorage.setItem("token_user", res.token);
    
    // Almacenar tiempo de expiración
    if (res.expires_at) {
      localStorage.setItem("expires_at", res.expires_at);
      sessionStorage.setItem("expires_at", res.expires_at);
      sessionStorage.setItem("token_expiry", res.expires_at);
    }
    
    // Almacenar rol del usuario
    if (res.rol) {
      sessionStorage.setItem("user_role", res.rol);
    }

    // Establecer cookie
    const expirationTime = res.expires_at
      ? new Date(parseInt(res.expires_at) * 1000)
      : new Date(Date.now() + 60 * 60 * 1000);

    document.cookie = `token=${res.token}; path=/; expires=${expirationTime.toUTCString()}; SameSite=Strict;`;

    // VERIFICAR SI NECESITA CAMBIAR CONTRASEÑA
    if (res.requiere_cambio_clave === true) {
      console.log("Usuario requiere cambio de contraseña"); // Para debug
      mostrarModalCambioClaveInicial();
    } else {
      // Login normal - redirigir al dashboard
      console.log("Redirigiendo usuario, rol:", res.rol); // Para debug
      redirigirSegunRol(res.rol);
    }
  } else {
    alertaPerzonalizada("error", "No se recibió un token válido");
  }
}

// Función para redirigir según el rol
function redirigirSegunRol(rol) {
  let timerInterval;
  Swal.fire({
    title: 'Bienvenido al sistema',
    html: "Sera redirigido en <b></b> segundos.",
    timer: 2000,
    timerProgressBar: true,
    didOpen: () => {
      Swal.showLoading();
      const timer = Swal.getPopup().querySelector("b");
      timerInterval = setInterval(() => {
        timer.textContent = `${Math.ceil(Swal.getTimerLeft() / 1000)}`;
      }, 100);
    },
    willClose: () => {
      clearInterval(timerInterval);
    },
  }).then((result) => {
    if (result.dismiss === Swal.DismissReason.timer) {
      if (rol == 1) {
        window.location = base_url + "admin/dashboard";
      } else {
        window.location = base_url + "admin";
      }
    }
  });
}

// NUEVA FUNCIÓN: Mostrar modal para cambio de contraseña inicial
function mostrarModalCambioClaveInicial() {
  Swal.fire({
    title: 'Cambio de Contraseña Requerido',
    html: `
      <div class="text-start">
        <p class="mb-3 text-muted">Debe cambiar su contraseña temporal antes de continuar.</p>
        
        <div class="mb-3">
          <label for="claveActual" class="form-label">Contraseña Temporal Actual</label>
          <div class="input-group">
            <input type="password" class="form-control" id="claveActual" placeholder="Ingrese su contraseña temporal">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordSwal('claveActual', this)">
              <i class="material-icons">visibility</i>
            </button>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="claveNueva" class="form-label">Nueva Contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" id="claveNueva" placeholder="Mínimo 8 caracteres">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordSwal('claveNueva', this)">
              <i class="material-icons">visibility</i>
            </button>
          </div>
          <div class="form-text">Debe contener al menos 8 caracteres</div>
        </div>
        
        <div class="mb-3">
          <label for="claveConfirmar" class="form-label">Confirmar Nueva Contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" id="claveConfirmar" placeholder="Repita la nueva contraseña">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordSwal('claveConfirmar', this)">
              <i class="material-icons">visibility</i>
            </button>
          </div>
        </div>
      </div>
    `,
    icon: 'warning',
    showCancelButton: false,
    confirmButtonText: 'Cambiar Contraseña',
    confirmButtonColor: '#007bff',
    allowOutsideClick: false,
    allowEscapeKey: false,
    preConfirm: () => {
      const claveActual = document.getElementById('claveActual').value;
      const claveNueva = document.getElementById('claveNueva').value;
      const claveConfirmar = document.getElementById('claveConfirmar').value;

      if (!claveActual || !claveNueva || !claveConfirmar) {
        Swal.showValidationMessage('Todos los campos son requeridos');
        return false;
      }

      if (claveNueva.length < 8) {
        Swal.showValidationMessage('La nueva contraseña debe tener al menos 8 caracteres');
        return false;
      }

      if (claveNueva !== claveConfirmar) {
        Swal.showValidationMessage('Las contraseñas nuevas no coinciden');
        return false;
      }

      return { claveActual, claveNueva, claveConfirmar };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      cambiarClaveInicial(result.value.claveActual, result.value.claveNueva, result.value.claveConfirmar);
    }
  });
}

// NUEVA FUNCIÓN: Procesar cambio de contraseña inicial
function cambiarClaveInicial(claveActual, claveNueva, claveConfirmar) {
  const formData = new FormData();
  formData.append('claveActual', claveActual);
  formData.append('claveNueva', claveNueva);
  formData.append('claveConfirmar', claveConfirmar);

  const http = new XMLHttpRequest();
  const url = base_url + "principal/cambiarClaveInicial";
  http.open("POST", url, true);

  // Agregar token a headers
  const token = sessionStorage.getItem('token_user') || localStorage.getItem('token_user');
  if (token) {
    http.setRequestHeader('Authorization', 'Bearer ' + token);
  }

  http.onreadystatechange = function () {
    if (this.readyState == 4) {
      if (this.status == 200) {
        try {
          const res = JSON.parse(this.responseText);

          if (res.tipo === "success") {
            Swal.fire({
              title: '¡Contraseña Actualizada!',
              text: res.mensaje,
              icon: 'success',
              confirmButtonText: 'Continuar',
              allowOutsideClick: false,
              allowEscapeKey: false
            }).then(() => {
              // Redirigir según el rol
              const userRole = sessionStorage.getItem('user_role') || '2';
              redirigirSegunRol(parseInt(userRole));
            });
          } else {
            Swal.fire({
              title: 'Error',
              text: res.mensaje,
              icon: 'error',
              confirmButtonText: 'Reintentar'
            }).then(() => {
              // Volver a mostrar el modal
              mostrarModalCambioClaveInicial();
            });
          }
        } catch (error) {
          console.error('Error parsing response:', error);
          Swal.fire({
            title: 'Error',
            text: 'Error al procesar la respuesta del servidor',
            icon: 'error',
            confirmButtonText: 'Reintentar'
          }).then(() => {
            mostrarModalCambioClaveInicial();
          });
        }
      } else {
        Swal.fire({
          title: 'Error de Conexión',
          text: 'No se pudo procesar la solicitud. Inténtelo de nuevo.',
          icon: 'error',
          confirmButtonText: 'Cerrar Sesión'
        }).then(() => {
          // Limpiar sesión y redirigir al login
          clearTokens();
          window.location = base_url;
        });
      }
    }
  };

  http.send(formData);
}

// NUEVA FUNCIÓN: Toggle password en SweetAlert
function togglePasswordSwal(inputId, button) {
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

// Configurar encabezados para futuras peticiones
function configurarEncabezadosToken(token) {
  // Esta función ya no es necesaria con el enfoque actual
  console.log("Token configurado para futuras peticiones");
}

// Debug exports
window.loginDebug = {
  clearTokens,
  procesarRespuestaLogin,
  mostrarModalCambioClaveInicial,
  cambiarClaveInicial
};