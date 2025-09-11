<?php

/********************************************
Archivo php views/index.php                         
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
    <meta name="description"
        content="GestorDocs: Sistema de gestión de archivos y carpetas en línea, seguro y fácil de usar">
    <meta name="keywords" content="gestión de archivos, carpetas, documentos, sistema en línea, gestor docs">
    <meta name="author" content="Gaes 1 - Anyi Tapias, Sharit Delgado, Durly Sánchez">
    <!-- Estas etiquetas deben ir primero en el head -->
    <!-- Título de la página -->
    <title><?php echo $data['title']; ?></title>
    <!-- Estilos -->
    <!-- Fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Iconos de Material Design -->
    <link
        href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp"
        rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="<?php echo BASE_URL . 'Assets/plugins/bootstrap/css/bootstrap.min.css'; ?>" rel="stylesheet">
    <!-- Perfect Scrollbar CSS -->
    <link href="<?php echo BASE_URL . 'Assets/plugins/perfectscroll/perfect-scrollbar.css'; ?>" rel="stylesheet">
    <!-- Pace CSS para indicadores de carga -->
    <link href="<?php echo BASE_URL . 'Assets/plugins/pace/pace.css'; ?>" rel="stylesheet">
    <!-- Estilos del tema -->
    <link href="<?php echo BASE_URL . 'Assets/css/main.min.css'; ?>" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="<?php echo BASE_URL . 'Assets/css/custom.css'; ?>" rel="stylesheet">
    <!-- Icono de la página -->
    <link rel="icon" href="<?php echo BASE_URL . 'Assets/images/favicon.ico'; ?>">
    <!-- Soporte para IE8 -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <div class="app app-auth-sign-in align-content-stretch d-flex flex-wrap justify-content-end">
        <div class="app-auth-background">
            <!-- Fondo de la página de inicio de sesión -->
        </div>
        <div class="app-auth-container">
            <div class="text-center">
                <img class="img" src="<?php echo BASE_URL . 'Assets/images/logo.png'; ?>" alt="" width="110">
                <hr>
                <span class="fs-3 fw-bold text-dark">Iniciar Sesión</span>
            </div>
            <!-- Mensaje de bienvenida -->
            <p class="auth-description mt-2">Bienvenido a tu sistema de gestión de archivos<br>¿No tienes una cuenta? <a
                    href="<?php echo BASE_URL . 'principal/registro'; ?>">Registrate</a></p>

            <!-- Formulario de inicio de sesión -->
            <form id="formulario" autocomplete="off">
                <div class="auth-credentials m-b-xxl">
                    <!-- Campo para el correo -->
                    <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control m-b-md" id="correo" name="correo" aria-describedby="correo"
                        placeholder="correo@gmail.com">

                    <!-- Campo para la contraseña -->
                    <label for="clave" class="form-label">Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="clave" name="clave" aria-describedby="clave"
                            placeholder="contraseña">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('clave', this)">
                            <i class="material-icons">visibility</i>
                        </button>
                    </div>
                    <br>
                    <div class="auth-submit">
                        <!-- Botón para iniciar sesión -->
                        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        <!-- Enlace para registrarse -->
                        <a href="<?php echo BASE_URL . 'recuperar/solicitar'; ?>"
                            class="auth-forgot-password float-end">Olvidaste tu Contraseña?</a>
                    </div>
                </div>
            </form>

            <!-- Sección Acerca de -->
            <div class="auth-footer mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                         &copy; GestorDocs V.1 - 2025 
                    </div>
                    <div>
                        <a href="#" class="text-decoration-none small me-3" data-bs-toggle="modal"
                            data-bs-target="#aboutModal">
                            <i class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">info</i>
                            Acerca de
                        </a>
                        <a href="<?php echo BASE_URL . 'docs/Manuales/manualtecnico.pdf'; ?>"
                            class="text-decoration-none small" target="_blank" rel="noopener noreferrer"
                            title="Descargar Manual Técnico">
                            <i class="material-icons-outlined"
                                style="font-size: 16px; vertical-align: middle;">description</i>
                            Manual PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Acerca de -->
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="aboutModalLabel">
                        <i class="material-icons me-2" style="vertical-align: middle;">info</i>
                        Acerca del Sistema
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo BASE_URL . 'Assets/images/logo.png'; ?>" alt="GestorDocs Logo"
                                    class="me-2" style="width: 40px; height: 40px;">
                                <h6 class="fw-bold mb-0">GestorDocs - Sistema de Gestión</h6>
                            </div>
                            <p class="text-muted mb-3">
                                Sistema de gestión de archivos y carpetas desarrollado por el equipo GAES 1 como parte
                                del programa de formación
                                en Análisis y Desarrollo de Software del SENA - Centro de Servicios Empresariales y
                                Turísticos.
                            </p>

                            <h6 class="fw-bold mb-2">
                                <i class="material-icons me-1"
                                    style="vertical-align: middle; font-size: 18px;">group</i>
                                Equipo de Desarrollo:
                            </h6>
                            <ul class="list-unstyled mb-3">
                                <li class="mb-1"><i class="material-icons text-primary me-2"
                                        style="vertical-align: middle; font-size: 18px;">person</i>Anyi Solayi Tapias
                                </li>
                                <li class="mb-1"><i class="material-icons text-primary me-2"
                                        style="vertical-align: middle; font-size: 18px;">person</i>Sharit Delgado Pinzón
                                </li>
                                <li class="mb-1"><i class="material-icons text-primary me-2"
                                        style="vertical-align: middle; font-size: 18px;">person</i>Durly Yuranni Sánchez
                                    Carillo</li>
                            </ul>

                            <div class="alert alert-info d-flex align-items-start" role="alert">
                                <i class="material-icons text-info me-2" style="font-size: 20px;">download</i>
                                <div>
                                    <strong>Manual Técnico</strong><br>
                                    <small>Descarga el manual técnico completo del sistema para obtener ayuda detallada
                                        sobre todas las funcionalidades disponibles.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 bg-light rounded">
                                <i class="material-icons text-danger mb-3" style="font-size: 48px;">picture_as_pdf</i>
                                <h6>Manual Técnico</h6>
                                <a href="<?php echo BASE_URL . 'docs/Manuales/manualtecnico.pdf'; ?>"
                                    class="btn btn-danger btn-sm" target="_blank" rel="noopener noreferrer">
                                    <i class="material-icons me-1"
                                        style="vertical-align: middle; font-size: 16px;">download</i>
                                    Descargar PDF
                                </a>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="material-icons"
                                            style="vertical-align: middle; font-size: 14px;">calendar_today</i>
                                        Versión 2.0 - 2025
                                    </small>
                                </div>
                            </div>

                            <!-- Información adicional del sistema -->
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="text-muted">
                                    <div class="mb-1">
                                        <i class="material-icons"
                                            style="vertical-align: middle; font-size: 14px;">code</i>
                                        PHP + Bootstrap
                                    </div>
                                    <div class="mb-1">
                                        <i class="material-icons"
                                            style="vertical-align: middle; font-size: 14px;">storage</i>
                                        PostgreSQL Database
                                    </div>
                                    <div>
                                        <i class="material-icons"
                                            style="vertical-align: middle; font-size: 14px;">security</i>
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

    <!-- Javascripts -->
    <!-- Carga la librería jQuery -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/jquery/jquery-3.5.1.min.js'; ?>"></script>
    <!-- Carga Bootstrap JS -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/bootstrap/js/bootstrap.min.js'; ?>"></script>
    <!-- Carga Perfect Scrollbar JS -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/perfectscroll/perfect-scrollbar.min.js'; ?>"></script>
    <!-- Carga Pace JS para indicadores de carga -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/pace/pace.min.js'; ?>"></script>
    <!-- Carga el script principal -->
    <script src="<?php echo BASE_URL . 'Assets/js/main.min.js'; ?>"></script>
    <!-- Carga SweetAlert2 para alertas -->
    <script src="<?php echo BASE_URL . 'Assets/js/sweetalert2@11.js'; ?>"></script>
    <!-- Carga el script personalizado -->
    <script src="<?php echo BASE_URL . 'Assets/js/custom.js'; ?>"></script>
    <!-- Define la variable base_url para usarla en JavaScript -->
    <script>
        const base_url = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Script para el modal y tracking -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tracking para descarga del manual
            const manualLinks = document.querySelectorAll('a[href*="manualtecnico.pdf"]');
            manualLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    console.log('Manual técnico GestorDocs descargado');
                    // Aquí puedes agregar tracking analytics si lo necesitas
                });
            });

            // Información del sistema en consola
            console.log('%cGestorDocs v1.0', 'font-weight: bold; font-size: 16px; color: #0d6efd;');
            console.log('Desarrollado por el equipo GAES 1 - SENA CSET ADSO 2025');
        });
    </script>

    <!-- Carga el script específico para la página de login -->
    <script src="<?php echo BASE_URL . 'Assets/js/pages/login.js'; ?>"></script>
</body>

</html>