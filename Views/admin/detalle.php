<?php
/********************************************
Archivo php admin/detalle.php                         
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

<div class="app-content">
    <?php 
    // Incluye el menú de navegación, como en home.php
    include_once 'Views/components/menus.php'; 
    ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="page-description d-flex align-items-center">
                        <div class="page-description-content flex-grow-1">
                            <!-- Título de la sección con el nombre de la carpeta -->
                            <h1>Detalles de <?php echo htmlspecialchars($data['carpeta']['nombre']); ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla para mostrar detalles, integrada en el diseño de home.php -->
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover display nowrap" style="width:100%" id="tblDetalle">
                                    <thead>
                                        <tr>
                                            <th></th> 
                                            <th>Usuario</th> 
                                            <th>Archivo</th> 
                                            <th>Estado</th> 
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

            <!-- Campo oculto para el ID de la carpeta -->
            <input type="hidden" id="id_carpeta" value="<?php echo htmlspecialchars($data['id_carpeta']); ?>">
        </div>
    </div>
</div>

<?php 
// Incluye el pie de página
include_once 'Views/template/footer.php'; 
?>