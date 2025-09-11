/********************************************
Script detalle.js                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// ID de la carpeta
const id_carpeta = document.querySelector("#id_carpeta");
let tbl;
document.addEventListener("DOMContentLoaded", function () {
    const token = localStorage.getItem('token') || '';

    if (!token) {
        console.error("No se encontró un token de autorización");
        alert("Por favor, inicia sesión nuevamente.");
        window.location.href = base_url + "login";
        return;
    }

    tbl = $("#tblDetalle").DataTable({
        ajax: {
            url: base_url + "admin/listardetalle/" + id_carpeta.value,
            dataSrc: "",
            type: "GET",
            headers: {
                "Authorization": "Bearer " + token
            },
            error: function (xhr, error, thrown) {
                if (xhr.status === 401) {
                    alert("Sesión expirada. Por favor, inicia sesión nuevamente.");
                    window.location.href = base_url + "login";
                } else {
                    console.error("Error al cargar los datos: ", xhr.responseText);
                }
            }
        },
        columns: [
            { data: "acciones" },
            { data: "correo" },
            { data: "nombre" },
            { data: "estado" }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/2.2.2/i18n/es-ES.json"
        },
        responsive: true,
        destroy: true,
        order: [[1, "desc"]]
    });
});

// Muestra un cuadro de diálogo para confirmar la eliminación de un archivo compartido.
function eliminarDetalle(id) {
    const token = localStorage.getItem('token') || '';
    const url = base_url + "archivos/eliminarCompartido/" + id;

    Swal.fire({
        title: "¿Estás seguro de eliminar el archivo compartido?",
        text: "El archivo se eliminará de forma permanente en 30 días",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(url, {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                }
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 401) {
                        throw new Error("No autorizado. Sesión expirada.");
                    }
                    throw new Error("Error en la solicitud");
                }
                return response.json();
            })
            .then(data => {
                if (data.tipo === "success") {
                    Swal.fire("¡Éxito!", data.mensaje, "success");
                    tbl.ajax.reload();
                } else {
                    Swal.fire("Error", data.mensaje, "error");
                }
            })
            .catch(error => {
                console.error("Error al eliminar: ", error);
                if (error.message.includes("No autorizado")) {
                    alert("Sesión expirada. Por favor, inicia sesión nuevamente.");
                    window.location.href = base_url + "login";
                } else {
                    Swal.fire("Error", "No se pudo eliminar el archivo", "error");
                }
            });
        }
    });
}