<?php
/********************************************
Archivo php usuarios/index.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Incluye la cabecera de la página
include_once 'Views/template/header.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="page-description">
                <!-- Título de la página -->
                <title><?php echo $data['title']; ?></title>
                <div class="page-description-content d-flex align-items-center justify-content-between">
                    <!-- Título de la sección -->
                    <h1>Gestión De Usuarios</h1>
                    <!-- Botón para agregar un nuevo usuario -->
                    <button class="btn btn-dark" type="button" id="btnNuevo">
                        <i class="material-icons">person_add</i> Nuevo
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Tabla para mostrar usuarios -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover display nowrap" style="width:100%" id="tblUsuarios">
                            <thead>
                                <tr>
                                    <th></th> 
                                    <th>Id</th> 
                                    <th>Nombres</th> 
                                    <th>Correo</th> 
                                    <th>Telefono</th> 
                                    <th>Direccion</th> 
                                    <th>F. registro</th> 
                                </tr>
                            </thead>
                            <tbody>
                            </tbody> 
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ventana para registrar o editar usuario -->
<div id="modalRegistro" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title"></h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formulario" autocomplete="off">
                <input type="hidden" id="id_usuario" name="id_usuario">
                <div class="modal-body">
                    <div class="row">
                        <!-- Campo para el nombre -->
                        <div class="col-md-6">
                            <label for="nombre">Nombre</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">list</i></span>
                                <input class="form-control" type="text" id="nombre" name="nombre" placeholder="Nombre" required>
                            </div>
                        </div>
                        <!-- Campo para el apellido -->
                        <div class="col-md-6">
                            <label for="apellido">Apellido</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">list</i></span>
                                <input class="form-control" type="text" id="apellido" name="apellido" placeholder="Apellido" required>
                            </div>
                        </div>
                        <!-- Campo para el correo -->
                        <div class="col-md-6">
                            <label for="correo">Correo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">email</i></span>
                                <input class="form-control" type="email" id="correo" name="correo" placeholder="correo" required>
                            </div>
                        </div>
                        <!-- Campo para el teléfono -->
                        <div class="col-md-6">
                            <label for="telefono">Telefono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">phone</i></span>
                                <input class="form-control" type="number" id="telefono" name="telefono" placeholder="telefono" required>
                            </div>
                        </div>
                        <!-- Campo para la dirección -->
                        <div class="col-md-12">
                            <label for="direccion">Direccion</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">place</i></span>
                                <input class="form-control" type="text" id="direccion" name="direccion" placeholder="direccion">
                            </div>
                        </div>
                        <!-- Campo para la contraseña -->
                        <div class="col-md-6">
                            <label for="clave">Clave</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">lock</i></span>
                                <input class="form-control" type="password" id="clave" name="clave" placeholder="clave" required>
                            </div>
                        </div>
                        <!-- Campo para el rol -->
                        <div class="col-md-6">
                            <label for="rol">Rol</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="material-icons">assignment_ind</i></span>
                                <select name="rol" id="rol" class="form-control">
                                    <option value="1">Administrador</option>
                                    <option value="2">Usuario</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Botón para guardar -->
                    <button class="btn btn-primary btn-style-light" type="submit" id="btnRegistrar">
                        <i class="material-icons">save</i>Guardar
                    </button>
                    <!-- Botón para cancelar -->
                    <button class="btn btn-danger btn-style-light" type="button" data-bs-dismiss="modal">
                        <i class="material-icons">cancel</i>Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Incluye el pie de página
include_once 'Views/template/footer.php'; 
?>