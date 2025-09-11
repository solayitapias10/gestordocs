
/********************************************
Archivo php compartidos.js                        
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

document.addEventListener('DOMContentLoaded', function () {
    // Configura los clics para visualizar archivos compartidos.
    document.querySelectorAll('.ver-archivo-compartido').forEach(function (element) {
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const idDetalle = this.getAttribute('data-id');
            visualizarArchivoCompartido(idDetalle);
        });
    });

    // Configura los clics para eliminar archivos compartidos.
    document.querySelectorAll('.eliminar-compartido').forEach(function (element) {
        element.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            eliminarArchivoCompartido(id);
        });
    });
});

// Función que muestra un modal para visualizar un archivo.
function visualizarArchivoCompartido(idDetalle) {
    const modal = new bootstrap.Modal(document.getElementById('modalVisualizadorCompartidos'));
    const contenido = document.getElementById('contenidoVisualizadorCompartidos');
    contenido.innerHTML = `
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando archivo...</span>
            </div>
        </div>`;
    modal.show();

    fetch(BASE_URL + 'compartidos/obtenerArchivoCompartido/' + idDetalle)
        .then(response => response.json())
        .then(data => {
            if (data.tipo === 'success') {
                const archivo = data.archivo;
                document.getElementById('modalVisualizadorCompartidosLabel').textContent = archivo.nombre;
                contenido.innerHTML = generarVisualizadorCompartido(archivo);
            } else {
                contenido.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="material-icons-outlined mb-2">error_outline</i>
                        <p class="mb-0">${data.mensaje}</p>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contenido.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="material-icons-outlined mb-2">error_outline</i>
                    <p class="mb-0">Error al cargar el archivo</p>
                </div>`;
        });
}

// Genera el HTML para la vista previa del archivo según su tipo.
function generarVisualizadorCompartido(archivo) {
    const { tipo_visualizacion, url, nombre } = archivo;
    let contenidoVisualizador = '';

    switch (tipo_visualizacion) {
        case 'imagen':
            contenidoVisualizador = `
                <div class="text-center">
                    <img src="${url}" alt="${nombre}" class="img-fluid rounded shadow w-100" 
                         style="max-height: 70vh; object-fit: contain;">
                </div>`;
            break;
        case 'pdf':
            contenidoVisualizador = `
                <div class="embed-responsive ratio ratio-16x9">
                    <iframe src="${url}" class="embed-responsive-item rounded" 
                            title="Visualizador PDF">
                    </iframe>
                </div>`;
            break;
        case 'video':
            contenidoVisualizador = `
                <div class="text-center">
                    <video controls class="rounded shadow w-100" style="max-height: 60vh;">
                        <source src="${url}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>`;
            break;
        case 'audio':
            contenidoVisualizador = `
                <div class="text-center p-3 p-md-4">
                    <i class="material-icons-outlined mb-3 text-primary" style="font-size: 3rem;">music_note</i>
                    <audio controls class="w-100" style="max-width: 400px;">
                        <source src="${url}" type="audio/mpeg">
                        Tu navegador no soporta la reproducción de audio.
                    </audio>
                </div>`;
            break;
        case 'texto':
            contenidoVisualizador = `
                <div id="contenido-texto" class="p-2 p-md-3 bg-light rounded" style="height: 50vh; overflow-y: auto;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando contenido...</span>
                        </div>
                    </div>
                </div>`;

            setTimeout(() => {
                fetch(url)
                    .then(response => response.text())
                    .then(texto => {
                        document.getElementById('contenido-texto').innerHTML =
                            `<pre class="text-start" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;">${escapeHtml(texto)}</pre>`;
                    })
                    .catch(() => {
                        document.getElementById('contenido-texto').innerHTML =
                            '<p class="text-muted">No se pudo cargar el contenido del archivo</p>';
                    });
            }, 100);
            break;
        case 'oficina':
            contenidoVisualizador = `
                <div class="text-center p-3 p-md-4">
                    <i class="material-icons-outlined mb-3 text-primary" style="font-size: 3rem;">description</i>
                    <h5 class="h6 h-md-5">Documento de Office</h5>
                    <p class="text-muted mb-0 small">Este tipo de archivo requiere una aplicación específica para visualizarse.</p>
                </div>`;
            break;
        default:
            contenidoVisualizador = `
                <div class="text-center p-3 p-md-4">
                    <i class="material-icons-outlined mb-3 text-muted" style="font-size: 3rem;">insert_drive_file</i>
                    <h5 class="h6 h-md-5">Vista previa no disponible</h5>
                    <p class="text-muted mb-0 small">Este tipo de archivo no se puede visualizar en el navegador.</p>
                </div>`;
    }
    return contenidoVisualizador;
}

// Función para eliminar un archivo compartido después de una confirmación.
function eliminarArchivoCompartido(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Estás seguro de que deseas eliminar este archivo de tu lista? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + 'compartidos/eliminar/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    alertaPerzonalizada(data.tipo, data.mensaje);
                    if (data.tipo === 'success') {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertaPerzonalizada('error', 'Error al eliminar el archivo');
                });
        }
    });
}
// Función auxiliar para convertir el tamaño de un archivo a un formato legible.
function formatearTamano(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Función auxiliar para dar formato a una fecha y hora.
function formatearFecha(fecha) {
    if (!fecha) return 'Fecha no disponible';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

//Funcion descargar Archivo 
function descargarArchivoCompartido(idDetalle, nombreArchivo) {
    // Crear un enlace temporal para la descarga
    const enlaceDescarga = document.createElement('a');
    enlaceDescarga.href = BASE_URL + 'compartidos/descargar/' + idDetalle;
    enlaceDescarga.download = nombreArchivo || 'archivo';
    enlaceDescarga.style.display = 'none';

    // Agregar al DOM, hacer clic y remover
    document.body.appendChild(enlaceDescarga);
    enlaceDescarga.click();
    document.body.removeChild(enlaceDescarga);
}

// Función auxiliar para escapar caracteres HTML de un texto.
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
