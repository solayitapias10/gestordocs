<?php
/********************************************
Archivo php archivos/papelera.php                        
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
                            <h1>Papelera</h1>
                            <span>Elementos eliminados que puedes restaurar o eliminar permanentemente</span>
                        </div>
                        <div class="page-description-actions">
                            <button class="btn btn-danger rounded-3 d-inline-flex align-items-center" id="btnVaciarPapelera">
                                <i class="material-icons me-2">delete_forever</i>
                                <span>Vaciar Papelera</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Carpetas -->
            <div class="section-description">
                <h1>Carpetas</h1>
            </div>
            <div class="row folders-container" id="folders-container">
                <?php if (empty($data['carpetas'])) { ?>
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <i class="material-icons" style="font-size: 48px; opacity: 0.5;">folder_off</i>
                            <p class="mt-2">No hay carpetas en la papelera.</p>
                        </div>
                    </div>
                <?php } else { ?>
                    <?php foreach ($data['carpetas'] as $carpeta) { ?>
                        <div class="col-md-4 col-12 folder-item">
                            <div class="card file-manager-group" style="min-height: 90px;">
                                <div class="card-body d-flex align-items-center" style="overflow: hidden;">
                                    <i class="material-icons folder-icon" style="color:#<?php echo htmlspecialchars($carpeta['color']); ?>; flex-shrink: 0; margin-right: 15px;">folder</i>
                                    <div class="file-manager-group-info" style="flex: 1; min-width: 0; margin-right: 10px;">
                                        <span class="file-manager-group-title" 
                                              style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" 
                                              title="<?php echo htmlspecialchars($carpeta['nombre']); ?>">
                                            <?php echo htmlspecialchars($carpeta['nombre']); ?>
                                        </span>
                                        <span class="file-manager-group-about" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; font-size: 0.875rem;">
                                            Eliminada: <?php echo htmlspecialchars($carpeta['fecha']); ?>
                                        </span>
                                    </div>
                                    <a href="#" class="dropdown-toggle dropdown-toggle-no-caret" style="flex-shrink: 0; color: #67748e; font-size: 20px;" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons" style="font-size: 20px; opacity: 0.6;">more_vert</i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item restaurar" href="<?php echo BASE_URL; ?>archivos/restaurar/<?php echo htmlspecialchars($carpeta['id']); ?>/carpeta" data-id="<?php echo htmlspecialchars($carpeta['id']); ?>" data-tipo="carpeta">
                                            <i class="material-icons me-2" style="font-size: 18px;">restore</i>Restaurar
                                        </a></li>
                                        <li><a class="dropdown-item eliminar-permanente text-danger" href="<?php echo BASE_URL; ?>archivos/eliminarPermanente/<?php echo htmlspecialchars($carpeta['id']); ?>/carpeta" data-id="<?php echo htmlspecialchars($carpeta['id']); ?>" data-tipo="carpeta">
                                            <i class="material-icons me-2" style="font-size: 18px;">delete_forever</i>Eliminar Permanentemente
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <!-- Sección de Archivos -->
            <div class="section-description">
                <h1>Documentos</h1>
            </div>
            <div class="row files-container" id="files-container">
                <?php if (empty($data['archivos'])) { ?>
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <i class="material-icons" style="font-size: 48px; opacity: 0.5;">description_off</i>
                            <p class="mt-2">No hay archivos en la papelera.</p>
                        </div>
                    </div>
                <?php } else { ?>
                    <?php foreach ($data['archivos'] as $archivo) { ?>
                        <div class="col-md-6 col-12 file-item">
                            <div class="card file-manager-recent-item" style="min-height: 80px;">
                                <div class="card-body d-flex align-items-center" style="overflow: hidden;">
                                    <i class="material-icons-outlined text-danger align-middle m-r-sm file-icon" style="flex-shrink: 0;">description</i>
                                    <div class="file-manager-recent-item-title" style="flex: 1; min-width: 0; margin-right: 10px;">
                                        <span style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" 
                                              title="<?php echo htmlspecialchars($archivo['nombre']); ?>">
                                            <?php echo htmlspecialchars($archivo['nombre']); ?>
                                        </span>
                                    </div>
                                    <span class="p-h-sm" style="flex-shrink: 0; margin-right: 10px; font-size: 0.875rem;"><?php echo htmlspecialchars($archivo['tamano_formateado']); ?></span>
                                    <span class="p-h-sm text-muted" style="flex-shrink: 0; margin-right: 10px; font-size: 0.875rem;">
                                        Eliminado: <?php echo htmlspecialchars($archivo['fecha']); ?>
                                    </span>
                                    <a href="#" class="dropdown-toggle file-manager-recent-file-actions" style="flex-shrink: 0;" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons">more_vert</i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item restaurar" href="<?php echo BASE_URL; ?>archivos/restaurar/<?php echo htmlspecialchars($archivo['id']); ?>/archivo" data-id="<?php echo htmlspecialchars($archivo['id']); ?>" data-tipo="archivo">
                                            <i class="material-icons me-2" style="font-size: 18px;">restore</i>Restaurar
                                        </a></li>
                                        <li><a class="dropdown-item eliminar-permanente text-danger" href="<?php echo BASE_URL; ?>archivos/eliminarPermanente/<?php echo htmlspecialchars($archivo['id']); ?>/archivo" data-id="<?php echo htmlspecialchars($archivo['id']); ?>" data-tipo="archivo">
                                            <i class="material-icons me-2" style="font-size: 18px;">delete_forever</i>Eliminar Permanentemente
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php
include_once 'Views/components/modal.php';
include_once 'Views/template/footer.php';
?>