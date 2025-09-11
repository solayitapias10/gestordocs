<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Restablecer contraseña - GestorDocs">
    <title><?php echo $data['title']; ?></title>
    
    <!-- Estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/plugins/bootstrap/css/bootstrap.min.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/css/main.min.css'; ?>" rel="stylesheet">
    <link href="<?php echo BASE_URL . 'Assets/css/custom.css'; ?>" rel="stylesheet">
    <link rel="icon" href="<?php echo BASE_URL . 'Assets/images/favicon.ico'; ?>">
</head>

<body>
    <div class="app app-auth-sign-in align-content-stretch d-flex flex-wrap justify-content-end">
        <div class="app-auth-background"></div>
        <div class="app-auth-container">
            <!-- Logo y título -->
            <div class="logo d-flex align-items-center mb-0">
                <img src="<?php echo BASE_URL . 'Assets/images/logo.png'; ?>" alt="GestorDocs Logo" class="me-2" style="width: 32px; height: 32px;">
                <span class="fs-3 fw-bold text-dark">Restablecer Contraseña</span>
            </div>
            
            <!-- Mensaje de bienvenida personalizado -->
            <p class="auth-description mt-2">
                Hola <strong><?php echo htmlspecialchars($data['usuario']['nombre']); ?></strong>, 
                ingresa tu nueva contraseña.
            </p>

            <!-- Formulario de restablecimiento -->
            <form id="formularioRestablecer" autocomplete="off">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token']); ?>">
                
                <div class="auth-credentials m-b-xxl">
                    <!-- Nueva contraseña -->
                    <label for="claveNueva" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">
                            <i class="material-icons">lock</i>
                        </span>
                        <input type="password" class="form-control" id="claveNueva" name="claveNueva" 
                               placeholder="Mínimo 8 caracteres" minlength="8" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('claveNueva', this)">
                            <i class="material-icons">visibility</i>
                        </button>
                    </div>

                    <!-- Confirmar contraseña -->
                    <label for="claveConfirmar" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">
                            <i class="material-icons">lock_outline</i>
                        </span>
                        <input type="password" class="form-control" id="claveConfirmar" name="claveConfirmar" 
                               placeholder="Repite la contraseña" minlength="8" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('claveConfirmar', this)">
                            <i class="material-icons">visibility</i>
                        </button>
                    </div>

                    <!-- Indicador de fortaleza de contraseña -->
                    <div id="passwordStrength" class="mb-3" style="display: none;">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="form-text text-muted mt-1" id="strengthText"></small>
                    </div>

                    <!-- Botón para restablecer -->
                    <div class="auth-submit">
                        <button type="submit" class="btn btn-success w-100" id="btnRestablecer">
                            <i class="material-icons me-2">security</i>
                            Actualizar Contraseña
                        </button>
                    </div>
                </div>
            </form>

            <!-- Información de seguridad -->
            <div class="alert alert-warning mt-3" role="alert">
                <i class="material-icons me-2">security</i>
                <strong>Recomendaciones:</strong>
                <ul class="mb-0 mt-2">
                    <li>Usa al menos 8 caracteres</li>
                    <li>Combina letras, números y símbolos</li>
                    <li>No uses información personal</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/jquery/jquery-3.5.1.min.js'; ?>"></script>
    <script src="<?php echo BASE_URL . 'Assets/plugins/bootstrap/js/bootstrap.min.js'; ?>"></script>
    <script src="<?php echo BASE_URL . 'Assets/js/sweetalert2@11.js'; ?>"></script>
    <script src="<?php echo BASE_URL . 'Assets/js/custom.js'; ?>"></script>
    <script>const base_url = '<?php echo BASE_URL; ?>';</script>
    <script src="<?php echo BASE_URL . 'Assets/js/pages/restablecer.js'; ?>"></script>
</body>
</html>
