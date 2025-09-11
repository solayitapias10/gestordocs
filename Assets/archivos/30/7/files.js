/********************************************
Script Files.js
Creado por el equipo Gaes 1:
Anyi Solayi Tapias
Sharit Delgado Pinzón
Durly Yuranni Sánchez Carillo
Año: 2025
SENA - CSET - ADSO
 ********************************************/

// Intercepta las peticiones de red para gestionar el token de autenticación.
const originalOpen = XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function (method, url, async) {
  // Maneja la renovación del token JWT antes de enviar la petición.
  this.addEventListener("readystatechange", function () {
    if (this.readyState === 1) {
      let token = localStorage.getItem("token");
      const expiresAt = localStorage.getItem("expires_at");
      const currentTime = Math.floor(Date.now() / 1000);

      if (token && expiresAt && currentTime > expiresAt - 300) {
        renovarToken().then((newToken) => {
          if (newToken) {
            token = newToken;
            this.setRequestHeader("Authorization", "Bearer " + token);
          } else {
            localStorage.removeItem("token");
            localStorage.removeItem("expires_at");
            window.location = base_url;
          }
        });
      } else if (token) {
        this.setRequestHeader("Authorization", "Bearer " + token);
      } else {
        localStorage.removeItem("token");
        localStorage.removeItem("expires_at");
        window.location = base_url;
      }
    }
  });
  // Redirige al login si la sesión ha expirado (código 401).
  this.addEventListener("readystatechange", function () {
    if (this.readyState === 4 && this.status === 401) {
      localStorage.removeItem("token");
      localStorage.removeItem("expires_at");
      window.location = base_url;
    }
  });
  originalOpen.apply(this, arguments);
};

// Renueva el token de autenticación del usuario.
function renovarToken() {
  return new Promise((resolve) => {
    const http = new XMLHttpRequest();
    const url = base_url + "principal/renovarToken";
    http.open("POST", url, true);
    http.setRequestHeader(
      "Authorization",
      "Bearer " + localStorage.getItem("token")
    );
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState == 4) {
        if (this.status == 200) {
          try {
            const res = JSON.parse(this.responseText);
            if (res.tipo === "success") {
              localStorage.setItem("token", res.token);
              localStorage.setItem("expires_at", res.expires_at);
              resolve(res.token);
            } else {
              resolve(null);
            }
          } catch (e) {
            resolve(null);
          }
        } else {
          resolve(null);
        }
      }
    };
  });
}

// Muestra los archivos de una carpeta en el modal de compartir.
function verArchivos() {
  const idCarpeta = document.querySelector("#id_carpeta").value;
  if (!idCarpeta || idCarpeta === "") {
    console.error("id_carpeta está vacío.");
    alertaPerzonalizada("error", "ID de carpeta inválido.");
    return;
  }

  const http = new XMLHttpRequest();
  const url = base_url + "archivos/verArchivos/" + idCarpeta;
  http.open("GET", url, true);
  http.setRequestHeader(
    "Authorization",
    "Bearer " + localStorage.getItem("token")
  );
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState == 4) {
      if (this.status == 200) {
        try {
          const res = JSON.parse(this.responseText);
          if (res.tipo === "success") {
            let html = "";
            const content_acordeon = document.querySelector("#accordionFlushExample");
            const container_archivos = document.querySelector("#container-archivos");
            const myModalUser = new bootstrap.Modal(document.querySelector("#modalUsuarios"));

            if (res.data.length > 0) {
              content_acordeon.classList.remove("d-none");
              res.data.forEach((archivo) => {
                html += `<div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${archivo.id}" name="archivos[]"
                            id="flexCheckDefault_${archivo.id}">
                            <label class="form-check-label" for="flexCheckDefault_${archivo.id}">
                                ${archivo.nombre}
                            </label>
                        </div>`;
              });
            } else {
              html = `<div class="alert alert-custom alert-indicator-right indicator-warning" role="alert">
                            <div class="alert-content">
                                <span class="alert-title">¡Advertencia!</span>
                                <span class="alert-text">La carpeta está vacía</span>
                            </div>
                        </div>`;
            }
            container_archivos.innerHTML = html;
            myModalUser.show();
          } else {
            console.error("Error en respuesta:", res.mensaje);
            alertaPerzonalizada(
              "error",
              res.mensaje || "Error al cargar los archivos."
            );
          }
        } catch (e) {
          console.error(
            "Error parseando verArchivos:",
            e.message,
            "Respuesta:",
            this.responseText
          );
          alertaPerzonalizada("error", "Respuesta inválida del servidor.");
        }
      } else {
        console.error(
          "Error en verArchivos:",
          this.status,
          "Respuesta:",
          this.responseText
        );
        alertaPerzonalizada(
          "error",
          `Error ${this.status}: No se pudo cargar los archivos.`
        );
      }
    }
  };
}

// Función para compartir un archivo o carpeta.
function compartirArchivo(id) {
  const http = new XMLHttpRequest();
  const url = base_url + "archivos/buscarCarpeta/" + id;
  http.open("GET", url, true);
  http.setRequestHeader("Authorization", "Bearer " + localStorage.getItem("token"));
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState == 4) {
      if (this.status == 200) {
        try {
          const res = JSON.parse(this.responseText);
          const id_carpeta = document.querySelector("#id_carpeta");
          const myModalUser = new bootstrap.Modal(document.querySelector("#modalUsuarios"));
          const content_acordeon = document.querySelector("#accordionFlushExample");
          const container_archivos = document.querySelector("#container-archivos");

          if (res.tipo === "success" || res.id_carpeta !== undefined) {
            if (res.id_carpeta && res.id_carpeta !== "1") {
              id_carpeta.value = res.id_carpeta;
              verArchivos();
            } else {
              id_carpeta.value = res.id_carpeta || "1";
              content_acordeon.classList.add("d-none");
              const fileName = document
                .querySelector(`.compartir[data-id="${id}"]`)
                ?.closest(".file-manager-recent-item")
                ?.querySelector(".ver-archivo")?.textContent || "Archivo seleccionado";
              container_archivos.innerHTML = `
                  <div class="form-check">
                      <input class="form-check-input" type="checkbox" value="${id}" name="archivos[]" id="flexCheckDefault_${id}" checked>
                      <label class="form-check-label" for="flexCheckDefault_${id}">
                          ${fileName}
                      </label>
                  </div>`;
              myModalUser.show();
            }
          } else {
            console.error("Error en respuesta:", res.mensaje);
            alertaPerzonalizada("error", res.mensaje || "Archivo o carpeta no encontrado.");
          }
        } catch (e) {
          console.error("Error parseando respuesta:", e.message, "Respuesta:", this.responseText);
          alertaPerzonalizada("error", "Respuesta inválida del servidor.");
        }
      } else {
        console.error("Error en solicitud:", this.status, "Respuesta:", this.responseText);
        alertaPerzonalizada("error", `Error ${this.status}: No se pudo cargar la carpeta.`);
      }
    }
  };
}

// Muestra un archivo en un modal para su visualización.
function visualizarArchivo(id, controlador) {
  const http = new XMLHttpRequest();
  const url = base_url + `${controlador}/obtenerArchivo/` + id;
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState == 4) {
      if (this.status === 200) {
        try {
          const res = JSON.parse(this.responseText);
          if (res.tipo === "success") {
            const { nombre, tipo, url } = res.archivo;
            const contenidoVisualizador = document.querySelector("#contenidoVisualizador");
            const modalVisualizadorLabel = document.querySelector("#modalVisualizadorLabel");
            const myModalVisualizador = new bootstrap.Modal(document.querySelector("#modalVisualizador"));

            let contenido = "";
            if (tipo.includes("image")) {
              contenido = `<img src="${url}" class="img-fluid" alt="${nombre}" style="max-height: 500px;">`;
            } else if (tipo.includes("pdf")) {
              contenido = `<iframe src="${url}" width="100%" height="500px" style="border: none;"></iframe>`;
            } else if (tipo.includes("text")) {
              contenido = `<pre class="p-3">${res.contenido || "Vista previa no disponible"
                }</pre>`;
            } else {
              contenido = `<p class="text-muted">Vista previa no disponible para este tipo de archivo. <a href="${url}" download="${nombre}">Descargar</a></p>`;
            }
            contenidoVisualizador.innerHTML = contenido;
            modalVisualizadorLabel.textContent = `Visualizar: ${nombre}`;
            myModalVisualizador.show();
          } else {
            alertaPerzonalizada(res.tipo, res.mensaje);
          }
        } catch (e) {
          alertaPerzonalizada(
            "error",
            "Respuesta inválida del servidor: " + this.responseText
          );
        }
      } else {
        alertaPerzonalizada("error", "Error en la solicitud: " + this.status);
      }
    }
  };
}

// Actualiza los contadores de estadísticas en el dashboard.
function actualizarEstadisticas() {
  const http = new XMLHttpRequest();
  const url = base_url + "admin/estadisticas";
  http.open("GET", url, true);
  http.send();
  http.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
      const res = JSON.parse(this.responseText);
      document.querySelector(".widget-stats-amount.carpetas").textContent =
        res.carpetas;
      document.querySelector(".widget-stats-amount.archivos").textContent =
        res.archivos;
      document.querySelector(".widget-stats-amount.compartidos").textContent =
        res.compartidos;
      document.querySelector(".widget-stats-amount.usuarios").textContent =
        res.usuarios;
    }
  };
}

// Carga y muestra los datos del usuario en la interfaz.
function cargarDatosUsuario() {
  const token = localStorage.getItem("token");
  if (token) {
    const http = new XMLHttpRequest();
    const url = base_url + "usuarios/miPerfil";
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        const user = JSON.parse(this.responseText);
        if (!user.error) {
          document.querySelector(
            ".user-info-text"
          ).innerHTML = `${user.nombre} ${user.apellido}<br><span class="user-state-info">${user.correo}</span>`;
          document.querySelector(".sidebar-user-switcher img").src =
            user.avatar
              ? base_url + user.avatar
              : base_url + "Assets/images/avatar.jpg";
        }
      }
    };
  }
}

// Consulta y muestra las notificaciones del usuario.
function consultarNotificaciones() {
  const xhr = new XMLHttpRequest();
  const url = base_url + "admin/getNotificaciones";
  xhr.open("GET", url, true);
  xhr.setRequestHeader(
    "Authorization",
    "Bearer " + localStorage.getItem("token")
  );
  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4 && xhr.status == 200) {
      const res = JSON.parse(xhr.responseText);
      const notificacionesDropdown = document.getElementById(
        "notificacionesDropdownList"
      );
      const badge = document.getElementById("notificacionesBadge");

      notificacionesDropdown.innerHTML = "";

      if (res.tipo === "success" && res.notificaciones.length > 0) {
        badge.textContent = res.notificaciones.length;
        badge.style.display = "inline";

        res.notificaciones.forEach((notif) => {
          const dropdownItem = document.createElement("a");
          dropdownItem.className =
            "dropdown-item d-flex align-items-center py-2 px-3 border-radius-sm mb-1";
          dropdownItem.href = "#";
          dropdownItem.innerHTML = `
                      <div class="me-3">
                          <span class="avatar-circle bg-success-light text-center">
                              <i class="material-icons text-success">notifications</i>
                          </span>
                      </div>
                      <div class="d-flex flex-column">
                          <span class="fw-medium">${notif.evento}</span>
                          <small class="text-muted">${notif.nombre} a las ${notif.fecha}</small>
                      </div>
                  `;
          dropdownItem.onclick = () => marcarNotificacionLeida(notif.id);
          notificacionesDropdown.appendChild(dropdownItem);
        });
      } else {
        badge.style.display = "none";
        const noNotifItem = document.createElement("div");
        noNotifItem.className = "dropdown-item text-center py-2";
        noNotifItem.textContent = "No hay notificaciones nuevas";
        notificacionesDropdown.appendChild(noNotifItem);
      }
    } else if (xhr.readyState == 4) {
    }
  };
  xhr.send();
}

// Marca una notificación como leída.
function marcarNotificacionLeida(id_notificacion) {
  const xhr = new XMLHttpRequest();
  const url = base_url + "admin/marcarNotificacionLeida";
  xhr.open("POST", url, true);
  xhr.setRequestHeader(
    "Authorization",
    "Bearer " + localStorage.getItem("token")
  );
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4 && this.status == 200) {
      const res = JSON.parse(this.responseText);
      if (res.tipo === "success") {
        consultarNotificaciones();
      }
    }
  };
  xhr.send(`id_notificacion=${id_notificacion}`);
}

// Función para confirmar y ejecutar el vaciado de la papelera.
function confirmarVaciarPapelera() {
  Swal.fire({
    title: "¿Estás seguro de vaciar la papelera?",
    text: "Todos los archivos y carpetas se eliminarán permanentemente.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, vaciar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const token = localStorage.getItem("token");

      if (!token) {
        alertaPerzonalizada(
          "error",
          "No se encontró token de autenticación. Por favor, inicia sesión nuevamente."
        );
        setTimeout(() => {
          window.location.href = base_url || "<?php echo BASE_URL; ?>";
        }, 1500);
        return;
      }
      const http = new XMLHttpRequest();
      const url =
        (typeof base_url !== "undefined"
          ? base_url
          : "<?php echo BASE_URL; ?>") + "archivos/vaciarPapelera";

      http.open("POST", url, true);
      http.setRequestHeader("Content-Type", "application/json");
      http.setRequestHeader("Authorization", "Bearer " + token);

      http.onreadystatechange = function () {
        if (this.readyState == 4) {
          if (this.status == 200) {
            try {
              const responseText = this.responseText.trim();
              if (
                responseText.startsWith("<") ||
                responseText.startsWith("<!DOCTYPE")
              ) {
                alertaPerzonalizada(
                  "error",
                  "Error del servidor. Por favor, revisa la consola."
                );
                return;
              }

              const res = JSON.parse(responseText);
              alertaPerzonalizada(res.tipo, res.mensaje || res.mesaje);

              if (res.tipo === "success") {
                setTimeout(() => {
                  window.location.reload();
                }, 1500);
              }
            } catch (error) {
              alertaPerzonalizada(
                "error",
                "Error al procesar la respuesta del servidor."
              );
            }
          } else if (this.status == 401) {
            alertaPerzonalizada(
              "error",
              "Sesión expirada. Inicia sesión nuevamente."
            );
            setTimeout(() => {
              window.location.href = base_url || "<?php echo BASE_URL; ?>";
            }, 1500);
          } else {
            alertaPerzonalizada(
              "error",
              "Error de conexión. Código: " + this.status
            );
          }
        }
      };
      http.send();
    }
  });
}


/********************************************
 * Selección de elementos del DOM          *
 ********************************************/
const btnCrearCarpeta = document.querySelector("#btnCrearCarpeta");
const btnSubirArchivoHome = document.querySelector("#btnSubirArchivoHome");
const modalFile = document.querySelector("#modalFile");
const myModal = modalFile ? new bootstrap.Modal(modalFile) : null;
const modalCarpeta = document.querySelector("#modalCarpeta");
const myModal1 = modalCarpeta ? new bootstrap.Modal(modalCarpeta) : null;
const frmCarpeta = document.querySelector("#frmCarpeta");
const btnSubirArchivo = document.querySelector("#btnSubirArchivo");
const btnSubirCarpeta = document.querySelector("#btnSubirCarpeta");
const file = document.querySelector("#file");
const folder = document.querySelector("#folder");
const id_carpeta = document.querySelector("#id_carpeta");
const carpetas = document.querySelectorAll(".carpetas");
const btnSubir = document.querySelector("#btnSubir");
const btnVer = document.querySelector("#btnVer");
const compartir = document.querySelectorAll(".compartir");
const modalUsuarios = document.querySelector("#modalUsuarios");
const myModalUser = modalUsuarios ? new bootstrap.Modal(modalUsuarios) : null;
const frmCompartir = document.querySelector("#frmCompartir");
const usuarios = document.querySelector("#usuarios");
const container_archivos = document.querySelector("#container-archivos");
const btnverDetalle = document.querySelector("#btnverDetalle");
const content_acordeon = document.querySelector("#accordionFlushExample");
const eliminar = document.querySelectorAll(".eliminar");
const compartirCarpeta = document.querySelectorAll(".compartir-carpeta");
const editarCarpeta = document.querySelectorAll(".editar-carpeta");
const btnCarpetaSubmit = document.querySelector("#btnCarpetaSubmit");
const modalVisualizador = document.querySelector("#modalVisualizador");
const myModalVisualizador = modalVisualizador
  ? new bootstrap.Modal(modalVisualizador)
  : null;
const contenidoVisualizador = document.querySelector("#contenidoVisualizador");
const btnVaciarPapelera = document.querySelector("#btnVaciarPapelera");
const uploadStatus = document.querySelector("#upload-status");

// Función para subir un archivo individual con seguimiento de progreso
function uploadSingleFile(file, token, onProgress, onComplete) {
  const data = new FormData();
  let carpetaValue = null;

  const id_carpeta = document.querySelector("#id_carpeta");
  if (id_carpeta && id_carpeta.value && id_carpeta.value.trim() !== "") {
    carpetaValue = id_carpeta.value.trim();
  } else {
    carpetaValue = "1";
  }

  data.append("id_carpeta", carpetaValue);
  data.append("files[]", file);

  const xhr = new XMLHttpRequest();
  const url = base_url + "admin/subirArchivo";

  // Configurar seguimiento de progreso
  xhr.upload.addEventListener("progress", function (e) {
    if (e.lengthComputable) {
      const percentComplete = (e.loaded / e.total) * 100;
      onProgress(percentComplete);
    }
  });

  xhr.addEventListener("load", function () {
    console.log("Response status:", this.status);
    console.log("Response text:", this.responseText);

    if (this.status === 200) {
      try {
        // Limpiar posibles caracteres no deseados antes del JSON
        const cleanResponse = this.responseText.trim();

        if (!cleanResponse) {
          console.error("Respuesta vacía del servidor");
          onComplete(false, "Respuesta vacía del servidor");
          alertaPerzonalizada("error", "Respuesta vacía del servidor");
          return;
        }

        const res = JSON.parse(cleanResponse);
        console.log("Parsed response:", res);

        if (res.tipo === "success") {
          onComplete(true);
          //actualizarEstadisticas();

          // Recargar página después de que todos los archivos terminen
          setTimeout(() => {
            if (id_carpeta && id_carpeta.value && id_carpeta.value.trim() !== "" && id_carpeta.value !== "1") {
              window.location = base_url + "admin/ver/" + id_carpeta.value;
            } else {
              window.location.reload();
            }
          }, 2000);
        } else {
          console.error("Error en respuesta del servidor:", res.mensaje);
          onComplete(false, res.mensaje);
          alertaPerzonalizada(res.tipo, res.mensaje);
        }
      } catch (e) {
        console.error("Error al parsear la respuesta JSON del servidor:", e);
        console.error("Respuesta del servidor (texto crudo):", this.responseText);
        onComplete(false, "Respuesta inválida del servidor. Consulta la consola para más detalles.");
        alertaPerzonalizada("error", "Respuesta inválida del servidor. Consulta la consola para más detalles.");
      }
    } else if (this.status === 401) {
      onComplete(false, "Sesión expirada");
      alertaPerzonalizada("error", "Sesión expirada. Inicia sesión nuevamente.");
      setTimeout(() => {
        window.location = base_url;
      }, 1500);
    } else {
      console.error("Error del servidor:", this.status, this.responseText);
      onComplete(false, "Error en el servidor: " + this.status);
      alertaPerzonalizada("error", "Error en el servidor: " + this.status);
    }
  });

  xhr.addEventListener("error", function () {
    console.error("Error de conexión");
    onComplete(false, "Error de conexión");
    alertaPerzonalizada("error", "Error de conexión");
  });

  xhr.addEventListener("abort", function () {
    console.log("Subida cancelada");
    onComplete(false, "Subida cancelada");
  });

  xhr.open("POST", url, true);
  xhr.setRequestHeader("Authorization", "Bearer " + token);
  xhr.send(data);

  // Retornar el objeto xhr para poder cancelar la subida
  return xhr;
}

// Función modificada para subir carpetas con seguimiento de progreso
function uploadFolder(files, token, onProgress, onComplete) {
  const data = new FormData();
  let carpetaValue = null;

  const id_carpeta = document.querySelector("#id_carpeta");
  if (id_carpeta && id_carpeta.value && id_carpeta.value.trim() !== "") {
    carpetaValue = id_carpeta.value.trim();
  } else {
    carpetaValue = "1";
  }

  data.append("id_carpeta", carpetaValue);
  
  // Agregar información de ubicación según la página actual
  if (window.location.pathname.includes("admin") && !window.location.pathname.includes("admin/ver")) {
    data.append("from_home", "true");
  }
  
  // Agregar archivos y rutas
  for (let i = 0; i < files.length; i++) {
    data.append("files[]", files[i]);
    data.append("paths[]", files[i].webkitRelativePath);
  }

  const xhr = new XMLHttpRequest();
  const url = base_url + "admin/subirCarpeta";

  // Configurar seguimiento de progreso
  xhr.upload.addEventListener("progress", function (e) {
    if (e.lengthComputable) {
      const percentComplete = (e.loaded / e.total) * 100;
      onProgress(percentComplete);
    }
  });

  xhr.addEventListener("load", function () {
    console.log("Response status:", this.status);
    console.log("Response text:", this.responseText);

    if (this.status === 200) {
      try {
        const cleanResponse = this.responseText.trim();

        if (!cleanResponse) {
          console.error("Respuesta vacía del servidor");
          onComplete(false, "Respuesta vacía del servidor");
          alertaPerzonalizada("error", "Respuesta vacía del servidor");
          return;
        }

        const res = JSON.parse(cleanResponse);
        console.log("Parsed response:", res);

        if (res.tipo === "success") {
          onComplete(true);
          
          // Recargar página después de completar
          setTimeout(() => {
            if (id_carpeta && id_carpeta.value && id_carpeta.value.trim() !== "" && id_carpeta.value !== "1") {
              window.location = base_url + "admin/ver/" + id_carpeta.value;
            } else {
              window.location.reload();
            }
          }, 2000);
        } else {
          console.error("Error en respuesta del servidor:", res.mensaje);
          onComplete(false, res.mensaje);
          alertaPerzonalizada(res.tipo, res.mensaje);
        }
      } catch (e) {
        console.error("Error al parsear la respuesta JSON del servidor:", e);
        console.error("Respuesta del servidor (texto crudo):", this.responseText);
        onComplete(false, "Respuesta inválida del servidor. Consulta la consola para más detalles.");
        alertaPerzonalizada("error", "Respuesta inválida del servidor. Consulta la consola para más detalles.");
      }
    } else if (this.status === 401) {
      onComplete(false, "Sesión expirada");
      alertaPerzonalizada("error", "Sesión expirada. Inicia sesión nuevamente.");
      setTimeout(() => {
        window.location = base_url;
      }, 1500);
    } else {
      console.error("Error del servidor:", this.status, this.responseText);
      onComplete(false, "Error en el servidor: " + this.status);
      alertaPerzonalizada("error", "Error en el servidor: " + this.status);
    }
  });

  xhr.addEventListener("error", function () {
    console.error("Error de conexión");
    onComplete(false, "Error de conexión");
    alertaPerzonalizada("error", "Error de conexión");
  });

  xhr.addEventListener("abort", function () {
    console.log("Subida cancelada");
    onComplete(false, "Subida cancelada");
  });

  xhr.open("POST", url, true);
  xhr.setRequestHeader("Authorization", "Bearer " + token);
  xhr.send(data);

  // Retornar el objeto xhr para poder cancelar la subida
  return xhr;
}

/********************************************
 * Event Listener Principal                *
 ********************************************/
// Espera a que el DOM esté cargado para ejecutar el código.
document.addEventListener("DOMContentLoaded", function () {
  cargarDatosUsuario();

  // Muestra el modal para crear o editar una carpeta.
  if (btnCrearCarpeta) {
    btnCrearCarpeta.addEventListener("click", function (e) {
      e.preventDefault();
      if (myModal1) {
        document.querySelector("#title-carpeta").textContent = "Nueva Carpeta";
        document.querySelector("#nombre").value = "";
        document.querySelector("#frmCarpeta").removeAttribute("data-id");
        btnCarpetaSubmit.textContent = "Crear";
        myModal1.show();
      } else {
        alertaPerzonalizada(
          "error",
          "No se pudo abrir el modal para crear carpeta"
        );
      }
    });
  }

  // Muestra el modal para subir archivos.
  if (btnSubirArchivoHome) {
    btnSubirArchivoHome.addEventListener("click", function (e) {
      e.preventDefault();
      if (myModal) {
        myModal.show();
      } else {
        alertaPerzonalizada(
          "error",
          "No se pudo abrir el modal para subir archivo"
        );
      }
    });
  }

  // Maneja el envío del formulario para crear o editar carpetas.
  if (frmCarpeta) {
    frmCarpeta.addEventListener("submit", function (e) {
      e.preventDefault();
      const nombre = document.querySelector("#nombre").value;
      if (nombre === "") {
        alertaPerzonalizada("warning", "El nombre de la carpeta es requerido");
        return;
      }

      const data = new FormData(frmCarpeta);
      const id = this.getAttribute("data-id");
      let url = "";

      if (id) {
        url = base_url + "admin/editarCarpeta";
        data.append("id", id);
      } else {
        url = base_url + "admin/crearCarpeta";
        if (
          id_carpeta &&
          id_carpeta.value &&
          window.location.pathname.includes("admin/ver")
        ) {
          data.append("id_carpeta_padre", id_carpeta.value);
        }
        if (
          window.location.pathname.includes("admin") &&
          !window.location.pathname.includes("admin/ver")
        ) {
          data.append("from_home", "true");
        }
      }

      const token = localStorage.getItem("token");
      if (!token) {
        alertaPerzonalizada(
          "error",
          "Sesión expirada. Por favor, inicia sesión nuevamente."
        );
        setTimeout(() => {
          window.location = base_url;
        }, 1500);
        return;
      }

      const http = new XMLHttpRequest();
      http.open("POST", url, true);
      http.setRequestHeader("Authorization", "Bearer " + token);
      http.send(data);
      http.onreadystatechange = function () {
        if (this.readyState == 4) {
          if (this.status == 200) {
            try {
              const res = JSON.parse(this.responseText);
              alertaPerzonalizada(res.tipo, res.mensaje);
              if (res.tipo === "success") {
                myModal1.hide();
                //actualizarEstadisticas();
                setTimeout(() => {
                  window.location.reload();
                }, 1500);
              }
            } catch (e) {
              alertaPerzonalizada("error", "Respuesta inválida del servidor");
            }
          } else if (this.status == 401) {
            alertaPerzonalizada(
              "error",
              "Sesión expirada. Inicia sesión nuevamente."
            );
            setTimeout(() => {
              window.location = base_url;
            }, 1500);
          } else {
            alertaPerzonalizada(
              "error",
              "Error en el servidor: " + this.status
            );
          }
        }
      };
    });
  }

  // Simula un clic en el input de tipo 'file' para subir archivos individuales.
  if (btnSubirArchivo) {
    btnSubirArchivo.addEventListener("click", function (e) {
      e.preventDefault();
      file.click();
    });
  }

  // Maneja la subida de archivos individuales al seleccionar un archivo.
  if (file) {
    file.addEventListener("change", async function (e) {
      const files = e.target.files;
      const fileList = document.querySelector("#file-list");
      const uploadStatus = document.querySelector("#upload-status");

      if (files.length === 0) {
        alertaPerzonalizada("warning", "No se seleccionaron archivos");
        if (fileList) fileList.innerHTML = "";
        if (uploadStatus) uploadStatus.classList.add("d-none");
        return;
      }

      // Validaciones de tamaño y cantidad de archivos
      const totalSizeMB = Array.from(files).reduce((total, file) => total + file.size, 0) / (1024 * 1024);
      if (totalSizeMB > 50) {
        alertaPerzonalizada("warning", "Los archivos exceden el límite de 50MB. Selecciona archivos más pequeños.");
        e.target.value = "";
        return;
      }

      // Mostrar archivos seleccionados
      if (fileList) {
        let html = "<ul class='list-group'>";
        for (let i = 0; i < files.length; i++) {
          html += `<li class='list-group-item'><i class='material-icons me-2'>insert_drive_file</i>${files[i].name}</li>`;
        }
        html += "</ul>";
        fileList.innerHTML = html;
      }

      // Mostrar mensaje de estado
      if (uploadStatus) {
        uploadStatus.classList.remove("d-none");
      }

      // Obtener y renovar token si es necesario
      const expiresAt = localStorage.getItem("expires_at");
      const currentTime = Math.floor(Date.now() / 1000);
      let token = localStorage.getItem("token");

      if (token && expiresAt && currentTime > expiresAt - 300) {
        token = await renovarToken();
      }

      if (!token) {
        alertaPerzonalizada("error", "Sesión expirada. Por favor, inicia sesión nuevamente.");
        if (uploadStatus) uploadStatus.classList.add("d-none");
        setTimeout(() => {
          window.location = base_url;
        }, 1500);
        return;
      }

      // Agregar archivos a la cola de progreso
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        uploadQueue.addFile(file, (item, onProgress, onComplete) => {
          uploadSingleFile(file, token, onProgress, onComplete);
        });
      }

      // Cerrar modal después de agregar archivos a la cola
      if (myModal) {
        myModal.hide();
      }

      // Limpiar formulario
      e.target.value = "";
      if (fileList) fileList.innerHTML = "";
      if (uploadStatus) uploadStatus.classList.add("d-none");
    });
  }

  // Verifica si el usuario está en la carpeta raíz.
  function isRootFolder() {
    const id_carpeta = document.querySelector("#id_carpeta");
    return (
      !id_carpeta ||
      !id_carpeta.value ||
      id_carpeta.value === "1" ||
      id_carpeta.value === ""
    );
  }

  // Obtiene el ID de la carpeta actual.
  function getFolderId() {
    const id_carpeta = document.querySelector("#id_carpeta");
    if (
      !id_carpeta ||
      !id_carpeta.value ||
      id_carpeta.value === "1" ||
      id_carpeta.value === ""
    ) {
      return null;
    }
    return id_carpeta.value;
  }

  // Simula un clic en el input de tipo 'file' para subir carpetas.
  if (btnSubirCarpeta) {
    btnSubirCarpeta.addEventListener("click", function (e) {
      e.preventDefault();
      if (!folder || !folder.hasAttribute("webkitdirectory")) {
        alertaPerzonalizada(
          "warning",
          "La subida de carpetas no es compatible con este navegador. Usa Chrome o Edge."
        );
        return;
      }
      folder.click();
    });
  }

  // Maneja la subida de carpetas al seleccionar una.
  if (folder) {
    folder.addEventListener("change", async function (e) {
      const files = e.target.files;
      const fileList = document.querySelector("#file-list");
      const uploadStatus = document.querySelector("#upload-status");
      
      if (files.length === 0) {
        alertaPerzonalizada("warning", "No se seleccionaron archivos en la carpeta");
        if (fileList) fileList.innerHTML = "";
        if (uploadStatus) uploadStatus.classList.add("d-none");
        return;
      }

      const totalSizeMB = Array.from(files).reduce((total, file) => total + file.size, 0) / (1024 * 1024);
      if (totalSizeMB > 50 || files.length > 100) {
        alertaPerzonalizada("warning", "La carpeta excede los límites (50MB o 100 archivos). Selecciona una carpeta más pequeña.");
        if (uploadStatus) uploadStatus.classList.add("d-none");
        if (btnSubirCarpeta) btnSubirCarpeta.disabled = false;
        e.target.value = "";
        return;
      }

      // Mostrar archivos seleccionados
      if (fileList) {
        const paths = new Set();
        for (let i = 0; i < files.length; i++) {
          const relativePath = files[i].webkitRelativePath;
          const folderPath = relativePath.substring(0, relativePath.lastIndexOf("/"));
          if (folderPath) paths.add(folderPath);
        }
        let html = "<ul class='list-group'>";
        paths.forEach((path) => {
          html += `<li class='list-group-item'><i class='material-icons me-2'>folder</i>${path}</li>`;
        });
        for (let i = 0; i < files.length; i++) {
          html += `<li class='list-group-item ms-3'>${files[i].name}</li>`;
        }
        html += "</ul>";
        fileList.innerHTML = html;
      }

      if (uploadStatus) {
        uploadStatus.classList.remove("d-none");
      }

      // Obtener nombre de la carpeta principal
      const folderName = files[0].webkitRelativePath.split('/')[0];

      // Validar y renovar token si es necesario
      const expiresAt = localStorage.getItem("expires_at");
      const currentTime = Math.floor(Date.now() / 1000);
      let token = localStorage.getItem("token");

      if (token && expiresAt && currentTime > expiresAt - 300) {
        token = await renovarToken();
      }

      if (!token) {
        alertaPerzonalizada("error", "Sesión expirada. Por favor, inicia sesión nuevamente.");
        if (uploadStatus) uploadStatus.classList.add("d-none");
        if (btnSubirCarpeta) btnSubirCarpeta.disabled = false;
        setTimeout(() => {
          window.location = base_url;
        }, 1500);
        return;
      }

      // Crear un objeto "archivo" virtual para la carpeta
      const folderAsFile = {
        name: folderName + " (carpeta)",
        size: totalSizeMB * 1024 * 1024, // Tamaño total en bytes
        type: "folder"
      };

      // Agregar la carpeta completa a la cola de progreso
      uploadQueue.addFile(folderAsFile, (item, onProgress, onComplete) => {
        const xhr = uploadFolder(files, token, onProgress, onComplete);
        item.xhr = xhr; // Guardar referencia para poder cancelar
      });

      // Cerrar modal después de agregar a la cola
      if (myModal) {
        myModal.hide();
      }

      // Limpiar formulario
      e.target.value = "";
      if (fileList) fileList.innerHTML = "";
      if (uploadStatus) uploadStatus.classList.add("d-none");
    });
  }

  // Realiza una búsqueda en tiempo real de archivos y carpetas.
  const searchInput = document.querySelector("#inputBusqueda");
  const searchResultsContainer = document.querySelector("#container-result");
  if (searchInput && searchResultsContainer) {
    searchInput.addEventListener("input", function () {
      const query = searchInput.value.trim();
      if (query.length < 2) {
        searchResultsContainer.innerHTML = "";
        searchResultsContainer.classList.remove("show");
        return;
      }

      const xhr = new XMLHttpRequest();
      const url = base_url + "admin/buscar?q=" + encodeURIComponent(query);
      xhr.open("GET", url, true);
      xhr.setRequestHeader(
        "Authorization",
        "Bearer " + localStorage.getItem("token")
      );
      xhr.send();
      xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
          if (xhr.status == 200) {
            try {
              const res = JSON.parse(xhr.responseText);
              if (res.tipo === "success") {
                let html = "<ul class='list-group'>";
                res.results.forEach((result) => {
                  const icon =
                    result.tipo === "carpeta"
                      ? "folder"
                      : result.tipo === "web"
                        ? "public"
                        : "description";
                  html += `<li class='list-group-item d-flex align-items-center'>
                                    <i class='material-icons me-2'>${icon}</i>
                                    <a href="${result.url}" class="text-decoration-none">${result.nombre}</a>
                                    <small class='text-muted ms-2'>(${result.tipo}) - ${result.fuente}</small>
                                </li>`;
                });
                html += "</ul>";
                searchResultsContainer.innerHTML = html;
                searchResultsContainer.classList.add("show");
              } else {
                alertaPerzonalizada(res.tipo, res.mensaje);
                searchResultsContainer.innerHTML = "";
                searchResultsContainer.classList.remove("show");
              }
            } catch (e) {
              alertaPerzonalizada("error", "Respuesta inválida del servidor");
              searchResultsContainer.classList.remove("show");
            }
          } else if (xhr.status == 401) {
            alertaPerzonalizada(
              "error",
              "Sesión expirada. Inicia sesión nuevamente."
            );
            setTimeout(() => {
              window.location = base_url;
            }, 1500);
          } else {
            alertaPerzonalizada("error", "Error en el servidor: " + xhr.status);
            searchResultsContainer.classList.remove("show");
          }
        }
      };
    });
    document.addEventListener("click", function (event) {
      if (
        !searchInput.contains(event.target) &&
        !searchResultsContainer.contains(event.target)
      ) {
        searchResultsContainer.innerHTML = "";
        searchResultsContainer.classList.remove("show");
      }
    });
  }

  // Navega a la carpeta seleccionada.
  carpetas.forEach((carpeta) => {
    carpeta.addEventListener("click", function (e) {
      e.preventDefault();
      id_carpeta.value = e.target.id;
      window.location = base_url + "admin/ver/" + id_carpeta.value;
    });
  });

  // Abre el selector de archivos desde el modal.
  if (btnSubir) {
    btnSubir.addEventListener("click", function () {
      myModal.hide();
      file.click();
    });
  }

  // Navega a la vista de la carpeta actual.
  if (btnVer) {
    btnVer.addEventListener("click", function () {
      window.location = base_url + "admin/ver/" + id_carpeta.value;
    });
  }

  // Inicializa la librería Select2 para la búsqueda de usuarios.
  if ($(".js-states").length) {
    $(".js-states").select2({
      theme: "bootstrap-5",
      placeholder: "Buscar Usuarios",
      maximumSelectionLength: 5,
      minimumInputLength: 2,
      dropdownParent: $("#modalUsuarios"),
      ajax: {
        url: base_url + "archivos/getUsuarios",
        dataType: "json",
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return { results: data };
        },
        cache: true,
      },
    });
  }

  // Maneja el envío del formulario para compartir archivos.
  if (frmCompartir) {
    frmCompartir.addEventListener("submit", function (e) {
      e.preventDefault();
      if (usuarios.value === "") {
        alertaPerzonalizada("warning", "Todos los campos son requeridos");
      } else {
        const data = new FormData(frmCompartir);
        const http = new XMLHttpRequest();
        const url = base_url + "archivos/compartir";
        http.open("POST", url, true);
        http.send(data);
        http.onreadystatechange = function () {
          if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            alertaPerzonalizada(res.tipo, res.mensaje);
            if (res.tipo === "success") {
              $(".js-states").val(null).trigger("change");
              myModalUser.hide();
            }
          }
        };
      }
    });
  }

  // Delega los eventos de clic para compartir archivos y carpetas.
  document.addEventListener("click", function (e) {
    if (
      e.target.classList.contains("compartir") ||
      e.target.classList.contains("compartir-carpeta")
    ) {
      e.preventDefault();
      const id = e.target.getAttribute("data-id");
      if (!id || id === "null") {
        console.error("ID de archivo/carpeta no válido:", id);
        alertaPerzonalizada(
          "error",
          "No se pudo identificar el archivo o carpeta."
        );
        return;
      }
      compartirArchivo(id);
    }
  });

  // Configura el modal para editar una carpeta.
  editarCarpeta.forEach((enlace) => {
    enlace.addEventListener("click", function (e) {
      e.preventDefault();
      const id = e.target.getAttribute("data-id");
      const nombreActual = e.target.getAttribute("data-nombre");
      document.querySelector("#title-carpeta").textContent = "Editar Carpeta";
      document.querySelector("#nombre").value = nombreActual;
      document.querySelector("#frmCarpeta").setAttribute("data-id", id);
      btnCarpetaSubmit.textContent = "Actualizar";
      myModal1.show();
    });
  });

  // Navega a la vista de detalles de una carpeta.
  if (btnverDetalle) {
    btnverDetalle.addEventListener("click", function () {
      window.location = base_url + "admin/verdetalle/" + id_carpeta.value;
    });
  }

  // Maneja la eliminación de archivos.
  eliminar.forEach((enlace) => {
    enlace.addEventListener("click", function (e) {
      e.preventDefault();
      let id = e.target.getAttribute("data-id");
      const url = base_url + "archivos/eliminar/" + id;
      eliminarRegistro(
        "¿Estás seguro de eliminar?",
        "El archivo se eliminará de forma permanente en 30 días",
        "Sí, eliminar",
        url,
        null
      );
    });
  });

  // Configura los clics para visualizar archivos.
  const verArchivoLinks = document.querySelectorAll(".ver-archivo");
  verArchivoLinks.forEach((enlace) => {
    enlace.addEventListener("click", function (e) {
      e.preventDefault();
      const idArchivo = e.target.getAttribute("data-id");
      const controlador = window.location.pathname.includes("admin")
        ? "admin"
        : "archivos";
      visualizarArchivo(idArchivo, controlador);
    });
  });

  // Alterna entre la vista de cuadrícula y la vista de lista.
  const toggleViewButton = document.querySelector(".toggle-view");
  const foldersContainer = document.querySelector("#folders-container");
  const filesContainer = document.querySelector("#files-container");
  let currentView = "grid";

  if (toggleViewButton) {
    toggleViewButton.addEventListener("click", function (e) {
      e.preventDefault();
      if (currentView === "grid") {
        currentView = "list";
        this.querySelector("i").textContent = "grid_view";
        foldersContainer.classList.remove("row");
        foldersContainer.classList.add("view-list");
        filesContainer.classList.remove("row");
        filesContainer.classList.add("view-list");
      } else {
        currentView = "grid";
        this.querySelector("i").textContent = "list";
        foldersContainer.classList.remove("view-list");
        foldersContainer.classList.add("row");
        filesContainer.classList.remove("view-list");
        filesContainer.classList.add("row");
      }
      this.classList.toggle("text-muted");
      this.classList.toggle("text-primary");
    });
    toggleViewButton.classList.add("text-primary");
  }

  // Maneja la eliminación de carpetas.
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("eliminar-carpeta")) {
      e.preventDefault();
      let id = e.target.getAttribute("data-id");
      const controlador = window.location.pathname.includes("admin")
        ? "admin"
        : "archivos";
      const url = base_url + controlador + "/eliminarCarpeta/" + id;
      eliminarRegistro(
        "¿Estás seguro de eliminar esta carpeta?",
        "La carpeta se ocultará y se eliminará permanentemente en 30 días",
        "Sí, eliminar",
        url,
        null
      );
    }
  });

  // Maneja la restauración de archivos y carpetas desde la papelera.
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("restaurar")) {
      e.preventDefault();
      let id = e.target.getAttribute("data-id");
      let tipo = e.target.getAttribute("data-tipo");
      const url = base_url + "archivos/restaurar/" + id + "/" + tipo;
      eliminarRegistro(
        "¿Estás seguro de restaurar este " + tipo + "?",
        "El " + tipo + " volverá a su ubicación original",
        "Sí, restaurar",
        url,
        null
      );
    }
  });

  // Maneja la eliminación permanente de archivos y carpetas.
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("eliminar-permanente")) {
      e.preventDefault();
      let id = e.target.getAttribute("data-id");
      let tipo = e.target.getAttribute("data-tipo");
      const url = base_url + "archivos/eliminarPermanente/" + id + "/" + tipo;
      eliminarRegistro(
        "¿Estás seguro de eliminar este " + tipo + " permanentemente?",
        "Esta acción no se puede deshacer",
        "Sí, eliminar",
        url,
        null
      );
    }
  });

  // Consulta las notificaciones cada 10 segundos.
  setInterval(consultarNotificaciones, 10000);
  consultarNotificaciones();

  // Muestra un modal de confirmación para vaciar la papelera.
  if (btnVaciarPapelera) {
    btnVaciarPapelera.addEventListener("click", function (e) {
      e.preventDefault();
      confirmarVaciarPapelera();
    });
  }
});