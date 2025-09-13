<?php

/********************************************
Archivo php usuarios/perfil.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado PinzÃ³n               
Durly Yuranni SÃ¡nchez Carillo       
AÃ±o: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Incluye la cabecera de la pÃ¡gina
include_once 'Views/template/header.php';
?>

<div class="app-content">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="page-description page-description-tabbed">
                        <!-- TÃ­tulo de la pÃ¡gina -->
                        <h1><?php echo htmlspecialchars($data['title']); ?></h1>

                        <!-- PestaÃ±as de navegaciÃ³n -->
                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">Datos Personales</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button" role="tab" aria-controls="seguridad" aria-selected="false">Seguridad</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="avatar-tab" data-bs-toggle="tab" data-bs-target="#avatar" type="button" role="tab" aria-controls="avatar" aria-selected="false">Avatar</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="integraciones-tab" data-bs-toggle="tab" data-bs-target="#integraciones" type="button" role="tab" aria-controls="integraciones" aria-selected="false">Integraciones</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="tab-content" id="myTabContent">
                        <!-- PestaÃ±a de datos personales -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                            <div class="card">
                                <div class="card-body">
                                    <form id="formUsuario" method="post">
                                        <!-- Campo oculto para ID -->
                                        <input type="hidden" id="id" name="id" value="<?php echo htmlspecialchars($data['usuario']['id']); ?>">

                                        <div class="row">
                                            <!-- Campo para el nombre -->
                                            <div class="col-md-6">
                                                <label for="inputNombre" class="form-label">Nombre</label>
                                                <input type="text" class="form-control" id="inputNombre" name="nombre" value="<?php echo htmlspecialchars($data['usuario']['nombre']); ?>" required>
                                            </div>
                                            <!-- Campo para el apellido -->
                                            <div class="col-md-6">
                                                <label for="inputApellido" class="form-label">Apellido</label>
                                                <input type="text" class="form-control" id="inputApellido" name="apellido" value="<?php echo htmlspecialchars($data['usuario']['apellido']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row m-t-lg">
                                            <!-- Campo para el correo (SOLO LECTURA) -->
                                            <div class="col-md-6">
                                                <label for="inputCorreo" class="form-label">Correo electrÃ³nico</label>
                                                <input type="email" class="form-control form-control-solid-bordered" id="inputCorreo" name="correo" value="<?php echo htmlspecialchars($data['usuario']['correo']); ?>" readonly>
                                                <div class="form-text">El correo electrÃ³nico no se puede modificar por seguridad.</div>
                                            </div>
                                            <!-- Campo para el telÃ©fono -->
                                            <div class="col-md-6">
                                                <label for="inputTelefono" class="form-label">Telofono</label>
                                                <input type="tel" class="form-control" id="inputTelefono" name="telefono" value="<?php echo htmlspecialchars($data['usuario']['telefono']); ?>" placeholder="(xxx) xxx-xxxx" required>
                                            </div>
                                        </div>

                                        <div class="row m-t-lg">
                                            <!-- Campo para la direcciÃ³n -->
                                            <div class="col-md-12">
                                                <label for="inputDireccion" class="form-label">Dirección</label>
                                                <input type="text" class="form-control" id="inputDireccion" name="direccion" value="<?php echo htmlspecialchars($data['usuario']['direccion']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row m-t-lg">
                                            <div class="col">
                                                <!-- BotÃ³n para actualizar datos -->
                                                <button type="submit" class="btn btn-dark">
                                                    <i class="material-icons">save</i>
                                                    Actualizar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- PestaÃ±a de seguridad -->
                        <div class="tab-pane fade" id="seguridad" role="tabpanel" aria-labelledby="seguridad-tab">
                            <div class="card">
                                <div class="card-body">
                                    <form id="formClave" method="post">
                                        <div class="row">
                                            <!-- Campo para la contraseÃ±a actual -->
                                            <div class="col-md-6">
                                                <label for="inputClaveActual" class="form-label">ContraseÃ±a Actual</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="inputClaveActual" name="claveActual" required>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('inputClaveActual', this)">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row m-t-lg">
                                            <!-- Campo para la nueva contraseÃ±a -->
                                            <div class="col-md-6">
                                                <label for="inputClaveNueva" class="form-label">Nueva ContraseÃ±a</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="inputClaveNueva" name="claveNueva"
                                                        pattern="^(?=.*[A-Z]).{8,}$"
                                                        title="La contraseÃ±a debe tener al menos 8 caracteres y una letra mayÃºscula"
                                                        required>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('inputClaveNueva', this)">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                </div>
                                                <div class="form-text">
                                                    La contraseÃ±a debe tener al menos 8 caracteres y una letra mayÃºscula.
                                                </div>
                                            </div>
                                            <!-- Campo para confirmar la nueva contraseÃ±a -->
                                            <div class="col-md-6">
                                                <label for="inputClaveConfirmar" class="form-label">Confirmar Nueva ContraseÃ±a</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="inputClaveConfirmar" name="claveConfirmar" required>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('inputClaveConfirmar', this)">
                                                        <i class="material-icons">visibility</i>
                                                    </button>
                                                </div>
                                                <div class="form-text">
                                                    Debe coincidir con la nueva contraseÃ±a.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row m-t-lg">
                                            <div class="col">
                                                <!-- BotÃ³n para cambiar contraseÃ±a -->
                                                <button type="submit" class="btn btn-dark">
                                                    <i class="material-icons">key</i>
                                                    Cambiar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- PestaÃ±a de avatar -->
                        <div class="tab-pane fade" id="avatar" role="tabpanel" aria-labelledby="avatar-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Tu Avatar Actual</h5>
                                        </div>
                                        <div class="card-body text-center">
                                            <!-- Imagen del avatar actual -->
                                            <div class="avatar-preview mb-4">
                                                <img id="avatarActual" src="<?php echo !empty($data['usuario']['avatar']) ? htmlspecialchars(BASE_URL . $data['usuario']['avatar']) : BASE_URL . 'Assets/images/avatar.jpg'; ?>" alt="Avatar actual" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                            </div>
                                            <p class="text-muted">Esta imagen se muestra en tu perfil y en tus actividades dentro del sistema.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Cambiar Avatar</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- Formulario para cambiar avatar -->
                                            <form id="formAvatar" method="post" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <label for="avatar" class="form-label">Selecciona una imagen</label>
                                                    <input class="form-control" type="file" id="avatar" name="avatar" accept="image/*" required>
                                                    <div class="form-text">
                                                        <ul class="mb-0 ps-3">
                                                            <li>La imagen debe ser menor a 2MB.</li>
                                                            <li>Se recomienda una imagen cuadrada.</li>
                                                            <li>Formatos permitidos: JPG, JPEG, PNG, GIF</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <!-- BotÃ³n para actualizar avatar -->
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="material-icons">photo_camera</i>
                                                        Actualizar Avatar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PestaÃ±a de integraciones -->
                        <div class="tab-pane fade" id="integraciones" role="tabpanel" aria-labelledby="integraciones-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">Integraciones</h5>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">Conexión con Gmail</h5>
                                            <p class="card-text">
                                                Conecta tu cuenta de Gmail para permitir que GestorDocs lea y archive automáticamente los archivos adjuntos de correos importantes (como facturas).
                                            </p>
                                            <a href="<?php echo BASE_URL; ?>google/conectar" class="btn btn-primary">
                                                <img src="<?php echo BASE_URL; ?>Assets/images/icons/google.png" alt="Google" width="20" class="me-2">
                                                Conectar con Gmail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor para mensajes dinÃ¡micos -->
            <div class="row mt-3" id="mensajeContainer"></div>
        </div>
    </div>
</div>

<?php
// Incluye el pie de pÃ¡gina
include_once 'Views/template/footer.php';
?>