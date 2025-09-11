<?php
/********************************************
Archivo php usuarios/solicitudes.php                         
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
                    <h1>Solicitudes de Usuarios</h1>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Tabla para mostrar usuarios -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover display nowrap" style="width:100%" id="tblSolicitudes">
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
<?php 
// Incluye el pie de página
include_once 'Views/template/footer.php'; 

?>