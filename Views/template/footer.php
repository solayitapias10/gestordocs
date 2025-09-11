<?php
/********************************************
Archivo php template/footer.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/
?>

    <!-- Cierra las etiquetas abiertas del contenido -->
    </div>
            </div>
        </div>
    </div>
    
    <!-- Javascripts -->
    <!-- Carga la librería jQuery -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/jquery/jquery-3.5.1.min.js'; ?>"></script>
    <!-- Carga Popper para tooltips y popovers -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/bootstrap/js/popper.min.js'; ?>"></script>
    <!-- Carga Bootstrap para estilos y componentes -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/bootstrap/js/bootstrap.bundle.min.js'; ?>"></script>
    <!-- Carga Perfect Scrollbar para barras de desplazamiento -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/perfectscroll/perfect-scrollbar.min.js'; ?>"></script>
    <!-- Carga Pace para indicadores de carga -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/pace/pace.min.js'; ?>"></script>
    <!-- Carga ApexCharts para gráficos -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/apexcharts/apexcharts.min.js'; ?>"></script>
    <!-- Carga el script principal -->
    <script src="<?php echo BASE_URL . 'Assets/js/main.min.js'; ?>"></script>
    <!-- Carga SweetAlert2 para alertas bonitas -->
    <script src="<?php echo BASE_URL . 'Assets/js/sweetalert2@11.js'; ?>"></script>
    <!-- Carga Select2 para listas desplegables -->
    <script src="<?php echo BASE_URL . 'Assets/js/select2.min.js'; ?>"></script>
    <!-- Carga DataTables para tablas dinámicas -->
    <script src="<?php echo BASE_URL . 'Assets/plugins/DataTables/datatables.min.js'; ?>"></script>
    <!-- Carga el script personalizado -->
    <script src="<?php echo BASE_URL . 'Assets/js/custom.js'; ?>"></script>
    <script src="<?php echo BASE_URL; ?>Assets/js/theme.js"></script>
    <script src="<?php echo BASE_URL; ?>Assets/js/upload-queue.js"></script>
    <!-- Define la variable base_url para usarla en JavaScript -->
    <script>
        const base_url = '<?php echo BASE_URL; ?>';
    </script>
    <?php if (!empty($data['script'])) { ?>
    <!-- Carga un script adicional si está definido -->
    <script src="<?php echo BASE_URL . 'Assets/js/pages/' . $data['script']; ?>"></script>
    <?php } ?>
</body>
</html>


