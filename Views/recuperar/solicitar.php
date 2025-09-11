<?php
/********************************************
Archivo php views/solicitar                        
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
    <!-- Estas etiquetas deben ir primero en el head -->
    <!-- Título de la página -->
    <title><?php echo $data['title']; ?></title>
    <!-- Estilos -->
    <!-- Fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Iconos de Material Design -->
    <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet">
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
        <div class="app-auth-background"></div>
        <div class="app-auth-container">
            <!-- Logo y título -->
            <div class="logo d-flex align-items-center mb-0">
                <img src="<?php echo BASE_URL . 'Assets/images/logo.png'; ?>" alt="GestorDocs Logo" class="me-2" style="width: 32px; height: 32px;">
                <span class="fs-3 fw-bold text-dark">Recuperar Contraseña</span>
            </div>

            <!-- Mensaje descriptivo -->
            <p class="auth-description mt-2">
                Ingresa tu correo electrónico y te enviaremos instrucciones para recuperar tu contraseña.
                <br><a href="<?php echo BASE_URL . 'principal/index'; ?>">← Volver al login</a>
            </p>

            <!-- Formulario de recuperación -->
            <form id="formularioRecuperar" autocomplete="off">
                <div class="auth-credentials m-b-xxl">
                    <!-- Campo para el correo -->
                    <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">
                            <i class="material-icons">email</i>
                        </span>
                        <input type="email" class="form-control" id="correo" name="correo"
                            placeholder="correo@ejemplo.com" required>
                    </div>

                    <!-- Botón para enviar -->
                    <div class="auth-submit">
                        <button type="submit" class="btn btn-primary w-100" id="btnRecuperar">
                            <i class="material-icons me-2">send</i>
                            Enviar Instrucciones
                        </button>
                    </div>
                </div>
            </form>

            <!-- Información adicional -->
            <div class="alert alert-info mt-3" role="alert">
                <i class="material-icons me-2">info</i>
                <strong>Nota:</strong> El enlace de recuperación será válido por 1 hora.
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
    <!-- Carga el script específico para la página de login -->
    <script src="<?php echo BASE_URL . 'Assets/js/pages/recuperar.js'; ?>"></script>
</body>

</html>