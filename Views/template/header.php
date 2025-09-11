<?php

/********************************************
Archivo php template/header.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Configuración básica de la página -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="GestorDocs: Sistema de gestión de archivos y carpetas en línea, seguro y fácil de usar">
    <meta name="keywords" content="gestión de archivos, carpetas, documentos, sistema en línea, gestor docs">
    <meta name="author" content="Gaes 1 - Anyi Tapias, Sharit Delgado, Durly Sánchez">

    <!-- Título de la página -->
    <title><?php echo $data['title']; ?></title>

    <!-- Estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/plugins/bootstrap/css/bootstrap.min.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/plugins/perfectscroll/perfect-scrollbar.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/plugins/pace/pace.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/css/select2.min.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/css/select2-bootstrap-5-theme.rtl.min.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/css/main.css'; ?>" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link id="dark-theme" rel="stylesheet" href="<?php echo BASE_URL; ?>Assets/css/darktheme.css" disabled>
    <link href="<?php echo BASE_URL . 'Assets/css/custom.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/css/upload-progress.css'; ?>" rel="stylesheet">

    <!-- Icono de la página -->
    <link rel="icon" href="<?php echo BASE_URL . 'Assets/images/favicon.ico'; ?>">

    <!-- Soporte para IE8 -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Librerías JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="app align-content-stretch d-flex flex-wrap">
        <div class="app-sidebar">
            <div class="logo" style="display: flex; align-items: center; justify-content: space-between;">
                <a href="#" class="logo-icon"><span class="logo-text">GestorDocs</span></a>
                <div class="sidebar-user-switcher user-activity-online">
                    <a href="<?php echo BASE_URL . 'usuarios/perfil'; ?>" style="display: flex; align-items: center;">
                        <span class="user-info-text">
                            <div style="font-size: 13px; font-weight: 500;"><?php echo $data['user']['nombre']; ?></div>
                            <div class="user-state-info"><?php echo $data['user']['correo']; ?></div>
                        </span>
                        <div style="position: relative;">
                            <img class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;"
                                src="<?php echo isset($data['user']['avatar'])
                                            ? (strpos($data['user']['avatar'], BASE_URL) === 0 ? $data['user']['avatar'] : BASE_URL . $data['user']['avatar'])
                                            : BASE_URL . 'Assets/images/avatar.jpg'; ?>"
                                alt="<?php echo $data['user']['nombre']; ?>">
                            <span class="activity-indicator" style="position: absolute; bottom: 2px; right: 2px; width: 10px; height: 10px; background: #28a745; border: 2px solid #fff; border-radius: 50%;"></span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-menu">
                <ul class="accordion-menu">
                    <li class="sidebar-title">Menu</li>


                    <?php if (isset($data['user']['rol']) && $data['user']['rol'] == 1) { ?>
                        <!-- Menú exclusivo para administradores -->
                        <li class="<?php echo ($data['menu'] == 'dashboard') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'admin/dashboard'; ?>" class="<?php echo ($data['menu'] == 'dashboard') ? 'active' : ''; ?>">
                                <i class="material-icons">dashboard</i>Dashboard
                            </a>
                        </li>
                        <li class="<?php echo ($data['menu'] == 'usuarios') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'usuarios'; ?>" class="<?php echo ($data['menu'] == 'usuarios') ? 'active' : ''; ?>">
                                <i class="material-icons">group_add</i>Gestión Usuarios
                            </a>
                        </li>

                        <li class="<?php echo ($data['menu'] == 'solicitudes') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'usuarios/solicitudes'; ?>" class="<?php echo ($data['menu'] == 'solicitudes') ? 'active' : ''; ?>">
                                <i class="material-icons">check_box</i>Solicitudes
                            </a>
                        </li>
                        <li class="<?php echo ($data['menu'] == 'perfil') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'usuarios/perfil'; ?>" class="<?php echo ($data['menu'] == 'perfil') ? 'active' : ''; ?>">
                                <i class="material-icons">settings</i>Configuración
                            </a>
                        </li>

                    <?php } else { ?>
                        <!-- Menú para usuarios regulares -->
                        <li class="<?php echo ($data['menu'] == 'archivos') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'archivos'; ?>" class="<?php echo ($data['menu'] == 'archivos') ? 'active' : ''; ?>">
                                <i class="material-icons-two-tone">home</i>Mi espacio
                            </a>
                        </li>
                        <li class="<?php echo ($data['menu'] == 'admin') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'admin'; ?>" class="<?php echo ($data['menu'] == 'admin') ? 'active' : ''; ?>">
                                <i class="material-icons-two-tone">update</i>Recientes
                            </a>
                        </li>

                        <li class="<?php echo ($data['menu'] == 'compartidos') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'compartidos'; ?>" class="<?php echo ($data['menu'] == 'compartidos') ? 'active' : ''; ?>">
                                <i class="material-icons-two-tone">folder_shared</i>Compartidos
                                <span class="badge rounded-pill badge-info float-end">
                                    <?php echo isset($data['shares']['total']) ? $data['shares']['total'] : 0; ?>
                                </span>
                            </a>
                        </li>

                        <li class="<?php echo ($data['menu'] == 'papelera') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'archivos/papelera'; ?>" class="<?php echo ($data['menu'] == 'papelera') ? 'active' : ''; ?>">
                                <i class="material-icons-two-tone">auto_delete</i>Papelera
                            </a>
                        </li>

                        <!-- Perfil - Disponible para todos -->
                        <li class="<?php echo ($data['menu'] == 'perfil') ? 'active-page' : ''; ?>">
                            <a href="<?php echo BASE_URL . 'usuarios/perfil'; ?>" class="<?php echo ($data['menu'] == 'perfil') ? 'active' : ''; ?>">
                                <i class="material-icons">settings</i>Configuración
                            </a>
                        </li>

                    <?php } ?>

                    <!-- Manual Técnico - Disponible para todos -->





                    <!-- Separador -->
                    <li class="sidebar-title" style="margin-top: 15px;">Sistema</li>

                    <!-- Salir - Disponible para todos -->
                    <li class="<?php echo ($data['menu'] == 'salir') ? 'active-page' : ''; ?>">
                        <a href="<?php echo BASE_URL . 'principal/salir'; ?>" class="<?php echo ($data['menu'] == 'salir') ? 'active' : ''; ?>">
                            <i class="material-icons">logout</i>Cerrar Sesión
                        </a>
                    </li>

                    <!-- Separador para sección de ayuda -->
                    <li class="sidebar-title" style="margin-top: 20px;">Ayuda</li>

                    <!-- Acerca de -->
                    <li>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">
                            <i class="material-icons-outlined">info</i>Acerca de
                        </a>
                    </li>
                </ul>

            </div>
        </div>
        <div class="app-container">
            <div class="search">
                <form>
                    <input class="form-control" id="inputBusqueda" type="text" placeholder="Buscar..." aria-label="Search">
                    <div id="container-result"></div>
                </form>
                <a href="#" class="toggle-search"><i class="material-icons">close</i></a>
            </div>
            <div class="app-header">
                <nav class="navbar navbar-light navbar-expand-lg">
                    <div class="container-fluid">
                        <div class="navbar-nav" id="navbarNav">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link hide-sidebar-toggle-button" href="#"><i class="material-icons">menu</i></a>
                                </li>
                            </ul>
                        </div>
                        <div class="d-flex">
                            <ul class="navbar-nav d-flex flex-row">
                                <!-- Búsqueda y modo oscuro juntos sin separación -->
                                <li class="nav-item">
                                    <a class="nav-link toggle-search" href="#"><i class="material-icons">search</i></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link toggle-theme" href="#" title="Alternar tema">
                                        <i class="material-icons theme-icon">dark_mode</i>
                                    </a>
                                </li>
                                <!-- Campana de notificaciones -->
                                <li class="nav-item hidden-on-mobile">
                                    <a class="nav-link" href="#" id="notificacionesDropDown" data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                                        <i class="material-icons">notifications</i>
                                        <span id="notificacionesBadge" class="badge rounded-pill badge-light position-absolute" style="display: none;">0</span>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end notifications-dropdown p-2" aria-labelledby="notificacionesDropDown">
                                        <h6 class="dropdown-header border-bottom pb-2 mb-2">Notificaciones</h6>
                                        <div id="notificacionesDropdownList" class="notifications-dropdown-list">
                                            <!-- Notificaciones se añadirán dinámicamente aquí -->
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
            <div class="app-content">
                <!-- Modal Acerca de (agregar antes del cierre de body) -->
                <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="aboutModalLabel">
                                    <i class="material-icons me-2" style="vertical-align: middle;">info</i>
                                    Acerca del Sistema
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="<?php echo BASE_URL . 'Assets/images/logo.png'; ?>" alt="GestorDocs Logo" class="me-2" style="width: 40px; height: 40px;">
                                            <h6 class="fw-bold mb-0">GestorDocs - Sistema de Gestión</h6>
                                        </div>
                                        <p class="text-muted mb-3">
                                            Sistema de gestión de archivos y carpetas desarrollado por el equipo GAES 1 como parte del programa de formación
                                            en Análisis y Desarrollo de Software del SENA - Centro de Servicios Empresariales y Turísticos.
                                        </p>

                                        <h6 class="fw-bold mb-2">
                                            <i class="material-icons me-1" style="vertical-align: middle; font-size: 18px;">group</i>
                                            Equipo de Desarrollo:
                                        </h6>
                                        <ul class="list-unstyled mb-3">
                                            <li class="mb-1"><i class="material-icons text-primary me-2" style="vertical-align: middle; font-size: 18px;">person</i>Anyi Solayi Tapias</li>
                                            <li class="mb-1"><i class="material-icons text-primary me-2" style="vertical-align: middle; font-size: 18px;">person</i>Sharit Delgado Pinzón</li>
                                            <li class="mb-1"><i class="material-icons text-primary me-2" style="vertical-align: middle; font-size: 18px;">person</i>Durly Yuranni Sánchez Carillo</li>
                                        </ul>

                                        <div class="alert alert-info d-flex align-items-start" role="alert">
                                            <i class="material-icons text-info me-2" style="font-size: 20px;">download</i>
                                            <div>
                                                <strong>Manual Técnico</strong><br>
                                                <small>Descarga el manual técnico completo del sistema para obtener ayuda detallada sobre todas las funcionalidades disponibles.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="p-3 bg-light rounded">
                                            <i class="material-icons text-danger mb-3" style="font-size: 48px;">picture_as_pdf</i>
                                            <h6>Manual Técnico</h6>
                                            <a href="<?php echo BASE_URL . 'docs/Manuales/manualtecnico.pdf'; ?>"
                                                class="btn btn-danger btn-sm"
                                                target="_blank"
                                                rel="noopener noreferrer">
                                                <i class="material-icons me-1" style="vertical-align: middle; font-size: 16px;">download</i>
                                                Descargar PDF
                                            </a>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="material-icons" style="vertical-align: middle; font-size: 14px;">calendar_today</i>
                                                    Versión 2.0 - 2025
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Información adicional del sistema -->
                                        <div class="mt-3 p-2 bg-light rounded">
                                            <small class="text-muted">
                                                <div class="mb-1">
                                                    <i class="material-icons" style="vertical-align: middle; font-size: 14px;">code</i>
                                                    PHP + Bootstrap
                                                </div>
                                                <div class="mb-1">
                                                    <i class="material-icons" style="vertical-align: middle; font-size: 14px;">storage</i>
                                                    PostgreSQL Database
                                                </div>
                                                <div>
                                                    <i class="material-icons" style="vertical-align: middle; font-size: 14px;">security</i>
                                                    Seguro y Confiable
                                                </div>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <div class="text-center w-100">
                                    <small class="text-muted">
                                        <i class="material-icons me-1" style="vertical-align: middle; font-size: 16px;">school</i>
                                        SENA - Centro de Servicios Empresariales y Turísticos - ADSO 2025
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Script para tracking del manual desde el menú lateral -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Tracking para descarga del manual desde cualquier enlace
                        const manualLinks = document.querySelectorAll('a[href*="manualtecnico.pdf"]');
                        manualLinks.forEach(function(link) {
                            link.addEventListener('click', function() {
                                console.log('Manual técnico GestorDocs descargado desde menú lateral');
                            });
                        });
                    });
                </script>
</body>

</html>