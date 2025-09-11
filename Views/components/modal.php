<?php

/********************************************
Archivo php components/modal.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/
?>

<!-- Ventana para subir archivos o carpetas -->
<div id="modalFile" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title">Subir Archivos o Carpetas</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <!-- Campo oculto para seleccionar archivos -->
                    <input type="file" id="file" class="d-none" name="file" multiple>
                    <!-- Campo oculto para seleccionar carpetas -->
                    <input type="file" id="folder" class="d-none" webkitdirectory directory multiple>
                    <!-- Contenedor para mostrar archivos/carpetas seleccionados -->
                    <div id="upload-status" class="mt-3 d-none">
                        <div class="alert alert-info">
                            <i class="material-icons me-2">cloud_upload</i>
                            Los archivos se están subiendo. Puedes ver el progreso en la esquina
                            inferior derecha.
                        </div>
                    </div>
                    <!-- Botón para subir archivos -->
                    <button type="button" id="btnSubirArchivo" class="btn btn-success btn-style-light"><i class="material-icons">upload_file</i>Subir Archivos</button>
                    <!-- Botón para subir carpetas -->
                    <button type="button" id="btnSubirCarpeta" class="btn btn-primary btn-style-light"><i class="material-icons">create_new_folder</i>Subir Carpeta</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ventana para crear una nueva carpeta -->
<div id="modalCarpeta" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-carpeta">Nueva Carpeta</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="frmCarpeta" autocomplete="off">
                <div class="modal-body">
                    <div class="input-group">
                        <span class="input-group-text"><i class="material-icons">folder</i></span>
                        <input class="form-control" type="text" name="nombre" id="nombre" placeholder="Nombre">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary btn-style-light" type="submit" id="btnCarpetaSubmit">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ventana para opciones de compartir -->
<div id="modalCompartir" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-compartir"></h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Campo oculto para el ID de la carpeta -->
                <input type="hidden" id="id_carpeta">
                <div class="d-grid">
                    <!-- Botón para ver la carpeta -->
                    <a href="#" id="btnVer" class="btn btn-dark btn-style-light m-r-xs"><i class="material-icons">visibility</i>Abrir Carpeta</a>
                    <hr>
                    <!-- Botón para subir un archivo -->
                    <button type="button" id="btnSubir" class="btn btn-primary btn-style-light m-r-xs"><i class="material-icons">upload_file</i>Subir Archivo</button>
                    <hr>
                    <!-- Botón para compartir -->
                    <button type="button" id="btnCompartir" class="btn btn-success btn-style-light m-r-xs"><i class="material-icons">share</i>Compartir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ventana para agregar usuarios al compartir -->

<div id="modalUsuarios" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-usuarios">Agregar Usuarios a compartir</h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="frmCompartir">
                <div class="modal-body">
                    <input type="hidden" id="id_archivo" name="id_archivo">
                    <!-- Lista para seleccionar usuarios -->
                    <select class="js-states form-control" id="usuarios" name="usuarios[]" tabindex="-1" style="display: none; width: 100%" multiple="multiple">
                    </select>
                    <hr>
                    <!-- Sección para seleccionar archivos -->
                    <div class="accordion accordion-flush mb-3" id="accordionFlushExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne"
                                    aria-expanded="false" aria-controls="flush-collapseOne">
                                    Seleccionar Archivos a Compartir
                                </button>
                            </h2>
                            <div id="flush-collapseOne" class="accordion-collapse collapse"
                                aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">
                                    <!-- Contenedor para los archivos -->
                                    <div id="container-archivos">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <!-- Botón para ver detalles -->
                    <div class="text-center">
                        <a class="btn btn-dark btn-style-light" id="btnverDetalle" href="#">
                            <i class="material-icons">visibility</i> Ver Detalle
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Botón para cancelar -->
                    <button class="btn btn-danger btn-style-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <!-- Botón para compartir -->
                    <button class="btn btn-primary btn-style-light" type="submit">Compartir</button>
                </div>
            </form>
        </div>
    </div>
</div>