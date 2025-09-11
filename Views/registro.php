<?php

/********************************************
Archivo php views/registro.php                         
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
    <title>Registro</title>

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

    <style>

    </style>
</head>

<body>
    <div class="app app-auth-sign-up align-content-stretch d-flex flex-wrap justify-content-end">
        <div class="app-auth-background"></div>
        <div class="app-auth-container">

            <div class="text-center">
                <img class="img" src="<?php echo BASE_URL . 'Assets/images/logo.png'; ?>"
                    alt="" width="110">
                    <hr>
                    <span class="fs-3 fw-bold text-dark">Registro</span>
            </div>


            <!-- Mensaje con enlace para iniciar sesión -->
            <p class="auth-description mt-2">Por favor, ingresa tus datos para crear una cuenta.<br>¿Ya tienes una cuenta? <a href="<?php echo BASE_URL . 'principal/index'; ?>">Inicia Sesión</a></p>

            <!-- Formulario de registro optimizado -->
            <form id="formularioRegistro" class="row g-2" autocomplete="off">

                <!-- Fila 1: Nombre y Apellido -->
                <div class="col-6">
                    <label for="nombre" class="form-label">Nombre<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                        placeholder="Tu nombre"
                        pattern="^[A-Za-zÀÉÍÓÚáéíóúÑñ\s]+$"
                        title="Solo se permiten letras y espacios">
                    <div class="invalid-feedback">
                        Solo letras y espacios.
                    </div>
                </div>
                <!-- Fila 1: Nombre y Apellido -->
                <div class="col-6">
                    <label for="apellido" class="form-label">Apellido<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="apellido" name="apellido"
                        placeholder="Tu apellido"
                        pattern="^[A-Za-zÀÉÍÓÚáéíóúÑñ\s]+$"
                        title="Solo se permiten letras y espacios">
                    <div class="invalid-feedback">
                        Solo letras y espacios.
                    </div>
                </div>
                <!-- Fila 2: Correo -->
                <div class="col-12">
                    <label for="correo" class="form-label">Correo Electrónico<span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="correo" name="correo"
                        placeholder="correo@ejemplo.com"
                        pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                        title="Ingresa un correo electrónico válido">
                    <div class="invalid-feedback">
                        Correo electrónico inválido.
                    </div>
                </div>
                <!-- Fila 3: Teléfono y Contraseña -->
                <div class="col-6">
                    <label for="telefono" class="form-label">Teléfono<span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">+57</span>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                            placeholder="3001234567"
                            pattern="^[3][0-9]{9}$"
                            title="Celular colombiano (10 dígitos con 3)"
                            maxlength="10">
                    </div>
                    <div class="form-text small">
                        Celular colombiano
                    </div>
                    <div class="invalid-feedback">
                        Celular colombiano inválido.
                    </div>
                </div>
                <!-- Fila 5: Dirección -->
                <div class="col-6">
                    <label for="direccion" class="form-label">Dirección<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="direccion" name="direccion"
                        placeholder="Calle 123 #45-67, Barrio, Ciudad"
                        minlength="10"
                        title="Dirección completa (mínimo 10 caracteres)">
                    <div class="form-text">
                        Dirección completa (calle, número, barrio, ciudad)
                    </div>
                    <div class="invalid-feedback">
                        Dirección muy corta (mín. 10 caracteres).
                    </div>
                </div>

                <!-- Términos y condiciones -->
                <div class="col-12 mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="aceptarTerminos" required>
                        <label class="form-check-label" for="aceptarTerminos">
                            Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos">términos y condiciones</a>
                            y las <a href="#" data-bs-toggle="modal" data-bs-target="#modalPoliticas">políticas de tratamiento de datos</a>
                            <span class="text-danger">*</span>
                        </label>
                        <div class="invalid-feedback">
                            Debes aceptar los términos y condiciones.
                        </div>
                    </div>
                </div>

                <!-- Botón de registro (más compacto) -->
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary w-100" id="btnRegistro">
                        <i class="material-icons me-2">person_add</i>
                        Registrarse
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Términos y Condiciones -->
    <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTerminosLabel">
                        <i class="material-icons me-2">description</i>
                        Términos y Condiciones de Uso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                    <div id="contenidoTerminos">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="aceptarTerminosBtn">Aceptar Términos</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Políticas de Tratamiento de Datos -->
    <div class="modal fade" id="modalPoliticas" tabindex="-1" aria-labelledby="modalPoliticasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPoliticasLabel">
                        <i class="material-icons me-2">privacy_tip</i>
                        Políticas de Tratamiento de Datos Personales
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                    <div id="contenidoPoliticas">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="aceptarPoliticasBtn">Aceptar Políticas</button>
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
    <!-- Carga el script específico para la página de registro -->
    <script src="<?php echo BASE_URL . 'Assets/js/pages/registro.js'; ?>"></script>
    <!-- Carga el script para los términos y condiciones -->
    <script src="<?php echo BASE_URL . 'Assets/js/pages/terminoscondiciones.js'; ?>"></script>
</body>

</html>