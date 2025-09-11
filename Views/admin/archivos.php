<?php

/********************************************
Archivo php admin/archivos.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Incluye el header
include_once 'Views/template/header.php';
?>

<div class="app-content">
    <?php
    // Incluye el menú de navegación
    include_once 'Views/components/menus.php';
    ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="page-description d-flex align-items-center">
                        <div class="page-description-content flex-grow-1">
                            <h1><?php echo !empty($data['carpeta']) ? htmlspecialchars($data['carpeta']['nombre']) : 'Carpeta sin nombre'; ?></h1>
                        </div>
                        <div class="page-description-actions d-flex gap-3">
                            <a href="#" class="btn btn-primary rounded-3 d-inline-flex align-items-center" id="btnCrearCarpeta">
                                <i class="material-icons me-2">create_new_folder</i>
                                <span>Nueva Carpeta</span>
                            </a>
                            <a href="#" class="btn btn-success rounded-3 d-inline-flex align-items-center" id="btnSubirArchivoHome">
                                <i class="material-icons me-2">upload_file</i>
                                <span>Subir Archivo</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($data['subcarpetas'])) { ?>
                <div class="row">
                    <?php foreach ($data['subcarpetas'] as $subcarpeta) { ?>
                        <div class="col-md-4">
                            <div class="card file-manager-group" style="min-height: 90px;">
                                <div class="card-body d-flex align-items-center" style="overflow: hidden;">
                                    <i class="material-icons folder-icon" style="color:#<?php echo htmlspecialchars($subcarpeta['color']); ?>; flex-shrink: 0; margin-right: 15px;">folder</i>
                                    <div class="file-manager-group-info" style="flex: 1; min-width: 0; margin-right: 10px;">
                                        <a href="#" id="<?php echo htmlspecialchars($subcarpeta['id']); ?>" class="file-manager-group-title carpetas"
                                           style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;"
                                           title="<?php echo htmlspecialchars($subcarpeta['nombre']); ?>">
                                            <?php echo htmlspecialchars($subcarpeta['nombre']); ?>
                                        </a>
                                        <span class="file-manager-group-about" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; font-size: 0.875rem;"><?php echo htmlspecialchars($subcarpeta['fecha']); ?></span>
                                    </div>
                                    <a href="#" class="dropdown-toggle dropdown-toggle-no-caret" style="flex-shrink: 0; color: #67748e; font-size: 20px;" id="file-manager-folder-<?php echo htmlspecialchars($subcarpeta['id']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons" style="font-size: 20px; opacity: 0.6;">more_vert</i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="file-manager-folder-<?php echo htmlspecialchars($subcarpeta['id']); ?>">
                                        <li><a class="dropdown-item compartir-carpeta" href="#" data-id="<?php echo $subcarpeta['id']; ?>">Compartir</a></li>
                                        <li><a class="dropdown-item editar-carpeta" href="#" data-id="<?php echo htmlspecialchars($subcarpeta['id']); ?>" data-nombre="<?php echo htmlspecialchars($subcarpeta['nombre']); ?>">Editar nombre</a></li>
                                        <li><a class="dropdown-item eliminar-carpeta" href="#" data-id="<?php echo htmlspecialchars($subcarpeta['id']); ?>">Eliminar</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="section-description">
                <h1>Documentos</h1>
            </div>
            <div class="row">
                <?php foreach ($data['archivos'] as $archivo) { ?>
                    <div class="col-md-6">
                        <div class="card file-manager-recent-item" style="min-height: 80px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center" style="overflow: hidden;">
                                    <i class="material-icons-outlined text-danger align-middle m-r-sm" style="flex-shrink: 0;">description</i>
                                    <a href="#" class="file-manager-recent-item-title ver-archivo" data-id="<?php echo htmlspecialchars($archivo['id']); ?>"
                                       style="flex: 1; min-width: 0; margin-right: 10px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;"
                                       title="<?php echo htmlspecialchars($archivo['nombre']); ?>">
                                        <?php echo htmlspecialchars($archivo['nombre']); ?>
                                    </a>
                                    <span class="p-h-sm" style="flex-shrink: 0; margin-right: 10px; font-size: 0.875rem;"><?php echo htmlspecialchars($archivo['tamano_formateado']); ?></span>
                                    <span class="p-h-sm text-muted" style="flex-shrink: 0; margin-right: 10px; font-size: 0.875rem;"><?php echo htmlspecialchars($archivo['fecha']); ?></span>
                                    <a href="#" class="dropdown-toggle file-manager-recent-file-actions" style="flex-shrink: 0;" id="file-manager-recent-<?php echo htmlspecialchars($archivo['id']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons">more_vert</i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="file-manager-recent-<?php echo htmlspecialchars($archivo['id']); ?>">
                                        <li><a class="dropdown-item compartir" href="#" data-id="<?php echo $archivo['id']; ?>">Compartir</a></li>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL . 'Assets/archivos/' . htmlspecialchars($archivo['id_carpeta']) . '/' . htmlspecialchars($archivo['nombre']); ?>" download="<?php echo htmlspecialchars($archivo['nombre']); ?>">Descargar</a></li>
                                        <li><a class="dropdown-item eliminar" href="#" data-id="<?php echo htmlspecialchars($archivo['id']); ?>">Eliminar</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <input type="hidden" id="id_carpeta" value="<?php echo htmlspecialchars($data['id_carpeta']); ?>">
        </div>
    </div>
</div>

<div class="modal fade" id="modalVisualizador" tabindex="-1" aria-labelledby="modalVisualizadorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="modalVisualizadorLabel" style="flex: 1; min-width: 0; margin-right: 15px;">
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" title="">
                        Visualizar Archivo
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="flex-shrink: 0;"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoVisualizador" class="text-center">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
// Incluye el modal
include_once 'Views/components/modal.php';
// Incluye el pie de página
include_once 'Views/template/footer.php';
?>