<?php

/********************************************
Archivo php views/error.php                         
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
    <title>Error</title>
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
    <div class="app app-error align-content-stretch d-flex flex-wrap">
        <div class="app-error-info">
            <h5>¡Oops!</h5>
            <span>Parece que la página que estás buscando ya no existe.<br>
                Intentaremos arreglar esto pronto.</span>
            <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? BASE_URL; ?>" class="btn btn-dark">Volver</a>
        </div>
        <div class="app-error-background"></div>
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
    <script src="<?php echo BASE_URL . 'Assets/js/pages/login.js'; ?>"></script>
</body>

</html>