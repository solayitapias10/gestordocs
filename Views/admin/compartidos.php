<?php

/********************************************
Archivo php admin/compartidos.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Incluye el encabezado de la página.
include_once 'Views/template/header.php';
?>
<div class="app-content">
    <?php
    // Incluye el menú de navegación.
    include_once 'Views/components/menus.php';
    ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="page-description d-flex align-items-center">
                        <div class="page-description-content flex-grow-1">
                            <h1>Archivos Compartidos Conmigo</h1>
                            <p class="page-description-text">Aquí se muestran todos los archivos que han sido compartidos contigo</p>
                        </div>
                        <div class="page-description-actions d-flex gap-3">

                        </div>
                    </div>
                </div>
            </div>

            <div class="row files-container" id="files-container">
                <?php
                // Verifica si hay archivos para mostrar.
                if (!empty($data['archivos'])):
                ?>
                    <?php
                    // Itera sobre cada archivo y muestra su información.
                    foreach ($data['archivos'] as $archivo):
                    ?>
                        <div class="col-md-6 col-12 file-item">
                            <div class="card file-manager-recent-item">
                                <div class="card-body d-flex align-items-center">
                                    <i class="material-icons-outlined text-primary align-middle m-r-sm file-icon">description</i>

                                    <div class="file-manager-recent-item-title flex-fill">
                                        <a href="#" class="ver-archivo-compartido" data-id="<?php echo $archivo['id']; ?>">
                                            <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                                        </a>

                                        <div class="d-flex align-items-center mt-1">
                                            <img src="<?php echo !empty($archivo['avatar_propietario']) ? $archivo['avatar_propietario'] : BASE_URL . 'Assets/images/avatar.jpg'; ?>"
                                                alt="Avatar"
                                                class="rounded-circle me-1"
                                                style="width: 16px; height: 16px;">
                                            <small class="text-muted">
                                                Compartido por: <?php echo htmlspecialchars($archivo['propietario']); ?>
                                            </small>
                                        </div>
                                    </div>

                                    <span class="p-h-sm text-muted">
                                        <?php echo $archivo['tipo']; ?>
                                    </span>

                                    <span class="p-h-sm text-muted">
                                        <?php
                                        $fecha = isset($archivo['fecha_add']) && !empty($archivo['fecha_add'])
                                            ? date('d/m/Y', strtotime($archivo['fecha_add']))
                                            : 'Fecha no disponible';
                                        echo $fecha;
                                        ?>
                                    </span>

                                    <a href="#" class="dropdown-toggle file-manager-recent-file-actions"
                                        id="file-manager-recent-<?php echo $archivo['id']; ?>"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="material-icons">more_vert</i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end"
                                        aria-labelledby="file-manager-recent-<?php echo $archivo['id']; ?>">
                                        <li>
                                            <a class="dropdown-item"
                                                href="<?= BASE_URL ?>compartidos/descargar/<?= $archivo['id'] ?>"
                                                download>
                                                <i class="material-icons me-2"></i>
                                                Descargar archivo
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger eliminar-compartido"
                                                href="#"
                                                data-id="<?php echo $archivo['id']; ?>">
                                                <i class="material-icons-outlined me-2"></i>
                                                Eliminar de mis archivos
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php
                    endforeach;
                    ?>
                <?php
                else:
                ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="material-icons-outlined text-muted mb-4" style="font-size: 4rem;">folder_shared</i>
                                <h5 class="text-muted mb-3">No tienes archivos compartidos</h5>
                                <p class="text-muted mb-4">
                                    Cuando otros usuarios compartan archivos contigo, aparecerán aquí automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVisualizadorCompartidos" tabindex="-1" aria-labelledby="modalVisualizadorCompartidosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizadorCompartidosLabel">Visualizar Archivo Compartido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoVisualizadorCompartidos" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<?php
// Incluye el modal y el pie de página.
include_once 'Views/components/modal.php';
include_once 'Views/template/footer.php';
?>