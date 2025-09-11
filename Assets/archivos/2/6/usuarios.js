/********************************************
Script usuarios.js                         
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
// Botón para nuevo usuario
const btnNuevo = document.querySelector("#btnNuevo");
// Título del modal
const title = document.querySelector("#title");
// Modal de registro
const modalRegistro = document.querySelector("#modalRegistro");
const myModal = new bootstrap.Modal(modalRegistro);
// Variable para la tabla
let tblUsuarios;

/********************************************
 * Evento al Cargar Documento             *
 ********************************************/
document.addEventListener("DOMContentLoaded", function () {
    // Tabla de usuarios
    tblUsuarios = $('#tblUsuarios').DataTable({
        ajax: {
            url: base_url + 'usuarios/listar',
            dataSrc: '',
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

    // Botón nuevo usuario
    btnNuevo.addEventListener("click", function () {
        title.textContent = "Nuevo Usuario";
        frm.id_usuario.value = '';
        frm.reset();
        frm.clave.removeAttribute('readonly');
        myModal.show();
    });

    // Enviar formulario
    frm.addEventListener("submit", function (e) {
        e.preventDefault();
        if (
            frm.nombre.value == '' ||
            frm.apellido.value == '' ||
            frm.correo.value == '' ||
            frm.telefono.value == '' ||
            frm.direccion.value == '' ||
            frm.clave.value == '' ||
            frm.rol.value == ''
        ) {
            alertaPerzonalizada("warning", "Todos los campos son requeridos");
        } else {
            const data = new FormData(frm);
            const http = new XMLHttpRequest();
            const url = base_url + 'usuarios/guardar';
            http.open("POST", url, true);
            http.send(data);
            http.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    try {
                        // Verificar si la respuesta es HTML (error de PHP)
                        const responseText = this.responseText.trim();

                        if (responseText.startsWith('<')) {
                            console.error('Respuesta HTML recibida (posible error PHP):', responseText);
                            alertaPerzonalizada('error', 'Error interno del servidor. Revisa los logs.');
                            return;
                        }

                        // Intentar parsear JSON
                        const res = JSON.parse(responseText);

                        if (res && res.tipo && res.mensaje) {
                            alertaPerzonalizada(res.tipo, res.mensaje);
                            if (res.tipo == 'success') {
                                frm.reset();
                                myModal.hide();
                                tblUsuarios.ajax.reload();
                            }
                        } else {
                            console.error('Respuesta JSON inválida:', res);
                            alertaPerzonalizada('error', 'Respuesta del servidor inválida');
                        }
                    } catch (error) {
                        console.error('Error al parsear JSON:', error);
                        console.error('Respuesta recibida:', this.responseText);
                        alertaPerzonalizada('error', 'Error al procesar la respuesta del servidor');
                    }
                } else if (this.readyState == 4 && this.status != 200) {
                    console.error('Error HTTP:', this.status, this.statusText);
                    alertaPerzonalizada('error', 'Error de conexión con el servidor');
                }
            };
        }
    });
});

/********************************************
 * Habilitar o Inhabilitar un usuario       *
 ********************************************/
function eliminar(id, estado) {
    const url = base_url + 'usuarios/delete/' + id;

    if (estado == 1) {
        // Usuario está activo - se va a inhabilitar
        eliminarRegistro(
            '¿Estás seguro de inhabilitar el usuario?',
            'El usuario será desactivado pero no eliminado permanentemente',
            'Sí, inhabilitar',
            url,
            tblUsuarios
        );
    } else {
        // Usuario está inactivo - se va a habilitar
        eliminarRegistro(
            '¿Estás seguro de habilitar el usuario?',
            'El usuario será activado nuevamente',
            'Sí, habilitar',
            url,
            tblUsuarios
        );
    }
}

/********************************************
 * Editar Usuario                         *
 ********************************************/
function editar(id) {
    const http = new XMLHttpRequest();
    const url = base_url + 'usuarios/editar/' + id;
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            title.textContent = 'Editar Usuario';
            frm.id_usuario.value = res.id;
            frm.nombre.value = res.nombre;
            frm.apellido.value = res.apellido;
            frm.correo.value = res.correo;
            frm.telefono.value = res.telefono;
            frm.direccion.value = res.direccion;
            frm.clave.value = '0000000000';
            frm.clave.setAttribute('readonly', 'readonly');
            frm.rol.value = res.rol;
            myModal.show();
        }
    };
}