/********************************************
Script solicitudes.js                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

/********************************************
 * Elementos del DOM                      *
 ********************************************/
// Formulario de registro
const frm = document.querySelector("#formulario");

// Variable para la tabla
let tblSolicitudes;

/********************************************
 * Evento al Cargar Documento             *
 ********************************************/
document.addEventListener("DOMContentLoaded", function () {
    // Tabla de usuarios
    tblSolicitudes = $('#tblSolicitudes').DataTable({
        ajax: {
            url: base_url + 'usuarios/listarSolicitudes',
            dataSrc: '',
            // Manejo de errores
            error: function (xhr, error, code) {
                console.log('Error en AJAX:', error);
                console.log('Código:', code);
                console.log('Respuesta:', xhr.responseText);
            }
        },
        columns: [
            {
                data: 'acciones',
                orderable: false,
                searchable: false
            },
            { data: 'id' },
            { data: 'nombres' },
            { data: 'correo' },
            { data: 'telefono' },
            { data: 'direccion' },
            { data: 'fecha' }
        ],
        language: {
            "decimal": "",
            "emptyTable": "No hay información",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
            "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
            "infoFiltered": "(Filtrado de _MAX_ total entradas)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ Entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "Sin resultados encontrados",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        responsive: true,
        order: [[1, 'desc']],
        processing: true,
        serverSide: false,
        columnDefs: [
            {
                targets: 0,
                className: 'text-center'
            }
        ]
    });
});

/********************************************
 * Aprobar Solicitud                        *
 ********************************************/

function aprobar(id) {
    const url = base_url + 'usuarios/aprobar/' + id;

    eliminarRegistro(
        '¿Estás seguro de aprobar esta solicitud?',
        'El usuario será activado y podrá acceder al sistema',
        'Sí, aprobar',
        url,
        tblSolicitudes
    );
}

/********************************************
 * Rechazar Solicitud                        *
 ********************************************/
function rechazar(id) {
    const url = base_url + 'usuarios/rechazar/' + id;

    eliminarRegistro(
        '¿Estás seguro de rechazar esta solicitud?',
        'La solicitud será eliminada permanentemente',
        'Sí, rechazar',
        url,
        tblSolicitudes
    );
}