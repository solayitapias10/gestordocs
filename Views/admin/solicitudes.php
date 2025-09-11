<?php
/********************************************
Archivo php solicitudes.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
********************************************/

// Incluye cabecera principal
include_once 'Views/template/header.php';
?>

<div class="app-content">
    <?php include_once 'Views/components/menus.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Titulo de la sección -->
            <div class="row mb-4">
                <div class="col">
                    <div class="page-description">
                        <h1>Gestión de Solicitudes de Registro</h1>
                    </div>
                </div>
            </div>

            <!-- Tabla de solicitudes -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover display nowrap" style="width:100%" id="tblSolicitudes">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Apellido</th>
                                            <th>Correo</th>
                                            <th>Teléfono</th>
                                            <th>Dirección</th>
                                            <th>Fecha Solicitud</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['solicitudes'] as $solicitud) { ?>
                                            <tr>
                                                <td><?php echo $solicitud['id']; ?></td>
                                                <td><?php echo $solicitud['nombre']; ?></td>
                                                <td><?php echo $solicitud['apellido']; ?></td>
                                                <td><?php echo $solicitud['correo']; ?></td>
                                                <td><?php echo $solicitud['telefono']; ?></td>
                                                <td><?php echo $solicitud['direccion']; ?></td>
                                                <td><?php echo $solicitud['fecha_solicitud']; ?></td>
                                                <td>
                                                    <button class="btn btn-success btn-sm btnAprobar" data-id="<?php echo $solicitud['id']; ?>">
                                                        <i class="material-icons">check</i> Aprobar
                                                    </button>
                                                    <button class="btn btn-danger btn-sm btnRechazar" data-id="<?php echo $solicitud['id']; ?>">
                                                        <i class="material-icons">close</i> Rechazar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'Views/template/footer.php'; ?>