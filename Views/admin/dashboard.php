<?php

/********************************************
Archivo php admin/dashboard.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Incluye la cabecera principal
include_once 'Views/template/header.php';
?>

<div class="app-content">
    <?php 
    // Incluye el menú de navegación
    include_once 'Views/components/menus.php'; 
    ?>
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Encabezado principal del dashboard -->
            <div class="row mb-4">
                <div class="col">
                    <div class="page-description d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="fw-bold">Dashboard</h1>
                            <span class="text-muted">Resumen del sistema - Actualizado al <?php echo date('d M Y, H:i'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de estadísticas principales -->
            <div class="row g-4">
                <!-- Tarjeta de Carpetas -->
                <div class="col-xl-3 col-md-6">
                    <div class="card widget widget-stats animate__animated animate__fadeIn h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="widget-stats-container d-flex w-100">
                                <div class="widget-stats-icon widget-stats-icon-primary">
                                    <i class="material-icons-outlined">folder</i>
                                </div>
                                <div class="widget-stats-content flex-fill">
                                    <span class="widget-stats-title">Carpetas</span>
                                    <span class="widget-stats-amount carpetas fw-bold fs-2"><?php echo $data['stats']['total_carpetas']; ?></span>
                                    <span class="widget-stats-info"><?php echo $data['stats']['tendencia_carpetas']; ?>% vs ayer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Archivos -->
                <div class="col-xl-3 col-md-6">
                    <div class="card widget widget-stats animate__animated animate__fadeIn h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="widget-stats-container d-flex w-100">
                                <div class="widget-stats-icon widget-stats-icon-warning">
                                    <i class="material-icons-outlined">description</i>
                                </div>
                                <div class="widget-stats-content flex-fill">
                                    <span class="widget-stats-title">Archivos</span>
                                    <span class="widget-stats-amount archivos fw-bold fs-2"><?php echo $data['stats']['total_archivos']; ?></span>
                                    <span class="widget-stats-info"><?php echo $data['stats']['tendencia_archivos']; ?>% vs ayer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Archivos Compartidos -->
                <div class="col-xl-3 col-md-6">
                    <div class="card widget widget-stats animate__animated animate__fadeIn h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="widget-stats-container d-flex w-100">
                                <div class="widget-stats-icon widget-stats-icon-danger">
                                    <i class="material-icons-outlined">share</i>
                                </div>
                                <div class="widget-stats-content flex-fill">
                                    <span class="widget-stats-title">Archivos Compartidos</span>
                                    <span class="widget-stats-amount compartidos fw-bold fs-2"><?php echo $data['stats']['total_compartidos']; ?></span>
                                    <span class="widget-stats-info"><?php echo $data['stats']['tendencia_compartidos']; ?>% vs ayer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Usuarios Activos -->
                <div class="col-xl-3 col-md-6">
                    <div class="card widget widget-stats animate__animated animate__fadeIn h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="widget-stats-container d-flex w-100">
                                <div class="widget-stats-icon widget-stats-icon-success">
                                    <i class="material-icons-outlined">people</i>
                                </div>
                                <div class="widget-stats-content flex-fill">
                                    <span class="widget-stats-title">Usuarios Activos</span>
                                    <span class="widget-stats-amount usuarios fw-bold fs-2"><?php echo $data['stats']['total_usuarios']; ?></span>
                                    <span class="widget-stats-info"><?php echo $data['stats']['tendencia_usuarios']; ?>% vs ayer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de gráficos principales -->
            <div class="row g-4 mt-4">
                <!-- Gráfico de actividad de archivos -->
                <div class="col-xl-6">
                    <div class="card widget widget-stats-large animate__animated animate__fadeInUp h-100">
                        <div class="card-header">
                            <h5 class="card-title">Actividad de Archivos<span class="badge badge-light badge-style-light">Últimos 30 días</span></h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div id="apex-activity" class="flex-fill" style="min-height: 400px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Sección de métricas del sistema -->
                <div class="col-xl-6">
                    <div class="row g-4 h-100">
                        <!-- Espacio utilizado -->
                        <div class="col-12">
                            <div class="card widget animate__animated animate__fadeInUp">
                                <div class="card-header">
                                    <h5 class="card-title">Espacio Utilizado</h5>
                                </div>
                                <div class="card-body text-center">
                                    <h3 class="fw-bold"><?php echo formatBytes($data['stats']['espacio_total']); ?></h3>
                                    <p class="text-muted mb-3">de espacio total en el sistema</p>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $data['stats']['espacio_porcentaje']; ?>%;" aria-valuenow="<?php echo $data['stats']['espacio_porcentaje']; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $data['stats']['espacio_porcentaje']; ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rendimiento del sistema -->
                        <div class="col-12">
                            <div class="card widget widget-list animate__animated animate__fadeInUp">
                                <div class="card-header">
                                    <h5 class="card-title">Rendimiento del Sistema<span class="badge badge-info badge-style-light">En tiempo real</span></h5>
                                </div>
                                <div class="card-body">
                                    <!-- CPU -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold">
                                                <i class="material-icons-outlined">memory</i>
                                                CPU Load
                                            </span>
                                            <span class="badge badge-<?php echo $data['metricas_sistema']['cpu']['porcentaje'] > 80 ? 'danger' : ($data['metricas_sistema']['cpu']['porcentaje'] > 60 ? 'warning' : 'success'); ?>">
                                                <?php echo $data['metricas_sistema']['cpu']['load_1min']; ?>
                                            </span>
                                        </div>
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $data['metricas_sistema']['cpu']['porcentaje'] > 80 ? 'danger' : ($data['metricas_sistema']['cpu']['porcentaje'] > 60 ? 'warning' : 'success'); ?>"
                                                role="progressbar"
                                                style="width: <?php echo min($data['metricas_sistema']['cpu']['porcentaje'], 100); ?>%;">
                                            </div>
                                        </div>
                                        <small class="text-muted">1m: <?php echo $data['metricas_sistema']['cpu']['load_1min']; ?> | 5m: <?php echo $data['metricas_sistema']['cpu']['load_5min']; ?> | 15m: <?php echo $data['metricas_sistema']['cpu']['load_15min']; ?></small>
                                    </div>

                                    <!-- Memoria del sistema -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold">
                                                <i class="material-icons-outlined">storage</i>
                                                Memoria Sistema
                                            </span>
                                            <span class="badge badge-<?php echo $data['metricas_sistema']['memoria']['porcentaje'] > 85 ? 'danger' : ($data['metricas_sistema']['memoria']['porcentaje'] > 70 ? 'warning' : 'info'); ?>">
                                                <?php echo $data['metricas_sistema']['memoria']['porcentaje']; ?>%
                                            </span>
                                        </div>
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $data['metricas_sistema']['memoria']['porcentaje'] > 85 ? 'danger' : ($data['metricas_sistema']['memoria']['porcentaje'] > 70 ? 'warning' : 'info'); ?>"
                                                role="progressbar"
                                                style="width: <?php echo $data['metricas_sistema']['memoria']['porcentaje']; ?>%;">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $data['metricas_sistema']['memoria']['usada']; ?>MB / <?php echo $data['metricas_sistema']['memoria']['total']; ?>MB</small>
                                    </div>

                                    <!-- Memoria PHP -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold">
                                                <i class="material-icons-outlined">code</i>
                                                Memoria PHP
                                            </span>
                                            <span class="badge badge-secondary">
                                                <?php echo $data['metricas_sistema']['php']['memoria_usada']; ?>MB
                                            </span>
                                        </div>
                                        <small class="text-muted">Límite: <?php echo $data['metricas_sistema']['php']['memoria_limite']; ?> | Pico: <?php echo $data['metricas_sistema']['php']['memoria_pico']; ?>MB</small>
                                    </div>

                                    <!-- Uptime del servidor -->
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">
                                                <i class="material-icons-outlined">schedule</i>
                                                Uptime del Servidor
                                            </span>
                                            <span class="badge badge-success">
                                                <?php echo $data['metricas_sistema']['uptime']; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Versión PHP -->
                                    <div class="text-center mt-3 pt-2" style="border-top: 1px solid #e9ecef;">
                                        <small class="text-muted">PHP <?php echo $data['metricas_sistema']['php']['version']; ?> | Actualizado: <?php echo date('H:i:s'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de gráficos adicionales -->
            <div class="row g-4 mt-4">
                <!-- Tipos de archivos -->
                <div class="col-xl-4">
                    <div class="card widget animate__animated animate__fadeInUp h-100">
                        <div class="card-header">
                            <h5 class="card-title">Tipos de Archivos</h5>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="apex-file-types" style="width: 100%; height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Usuarios más activos -->
                <div class="col-xl-4">
                    <div class="card widget widget-list animate__animated animate__fadeInUp h-100">
                        <div class="card-header">
                            <h5 class="card-title">Usuarios Más Activos<span class="badge badge-info">Top 5</span></h5>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div id="apex-users-activity" style="width: 100%; height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Actividad reciente -->
                <div class="col-xl-4">
                    <div class="card widget widget-list animate__animated animate__fadeInUp h-100">
                        <div class="card-header">
                            <h5 class="card-title">Actividad Reciente<span class="badge badge-warning badge-style-light"><?php echo count($data['actividad_reciente']); ?> eventos</span></h5>
                        </div>
                        <div class="card-body">
                            <ul class="widget-list-content list-unstyled" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($data['actividad_reciente'] as $evento) { ?>
                                    <li class="widget-list-item <?php echo $evento['tipo'] == 'carpeta' ? 'widget-list-item-blue' : 'widget-list-item-green'; ?>">
                                        <span class="widget-list-item-icon">
                                            <i class="material-icons-outlined"><?php echo $evento['tipo'] == 'carpeta' ? 'folder' : 'description'; ?></i>
                                        </span>
                                        <span class="widget-list-item-description">
                                            <span class="widget-list-item-description-title"><?php echo $evento['descripcion']; ?></span>
                                            <span class="widget-list-item-description-subtitle"><?php echo $evento['fecha']; ?></span>
                                        </span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluye el modal y el pie de página
include_once 'Views/components/modal.php';
include_once 'Views/template/footer.php';
?>

<!-- CSS externo para animaciones -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

<!-- Datos para JavaScript -->
<script>
    // Pasar datos de PHP a JavaScript de forma segura
    window.dashboardDataFromPHP = {
        actividad: {
            cantidades: <?php echo json_encode($data['actividad']['cantidades']); ?>,
            fechas: <?php echo json_encode($data['actividad']['fechas']); ?>
        },
        tipos_archivos: {
            cantidades: <?php echo json_encode($data['tipos_archivos']['cantidades']); ?>
        },
        usuarios_activos: {
            cantidades: <?php echo json_encode($data['usuarios_activos']['cantidades']); ?>
        }
    };
</script>

<!-- Script principal del dashboard -->
<script src="<?php echo BASE_URL . 'Assets/js/pages/dashboard.js'; ?>"></script>