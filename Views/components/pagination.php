<?php
/********************************************
Archivo php components/pagination.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

/**
 * Componente de paginación reutilizable
 * 
 * @param array $pagination - Array con información de paginación
 * @param string $param_name - Nombre del parámetro GET para la página (default: 'page')
 * @param string $base_url - URL base para los enlaces (default: URL actual sin parámetros)
 * @param array $extra_params - Parámetros adicionales a mantener en la URL
 */

if (!isset($pagination) || !is_array($pagination)) {
    return;
}

$current_page = $pagination['current_page'] ?? 1;
$total_pages = $pagination['total_pages'] ?? 1;
$total_records = $pagination['total_records'] ?? 0;
$limit = $pagination['limit'] ?? 10;

$param_name = $param_name ?? 'page';
$base_url = $base_url ?? $_SERVER['REQUEST_URI'];

// Limpiar la URL base de parámetros de paginación existentes
$url_parts = parse_url($base_url);
$clean_url = $url_parts['path'] ?? '';

// Construir parámetros existentes (excluyendo el parámetro de página actual)
$existing_params = [];
if (isset($url_parts['query'])) {
    parse_str($url_parts['query'], $existing_params);
    unset($existing_params[$param_name]);
}

// Agregar parámetros extra si se proporcionan
if (isset($extra_params) && is_array($extra_params)) {
    $existing_params = array_merge($existing_params, $extra_params);
}

// Función para construir URL con parámetros
if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl($page, $base, $param, $existing)
    {
        $params = $existing;
        $params[$param] = $page;
        return $base . '?' . http_build_query($params);
    }
}

// No mostrar paginación si solo hay una página
if ($total_pages <= 1) {
    return;
}

// Calcular rango de registros mostrados
$start_record = (($current_page - 1) * $limit) + 1;
$end_record = min($current_page * $limit, $total_records);
?>

<div class="pagination-wrapper mt-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="pagination-info">
                <span class="text-muted">
                    Mostrando <?php echo $start_record; ?> a <?php echo $end_record; ?> de <?php echo $total_records; ?> registros
                </span>
            </div>
        </div>
        <div class="col-md-6">
            <nav aria-label="Navegación de páginas">
                <ul class="pagination justify-content-end mb-0">
                    
                    <!-- Botón Anterior -->
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($current_page - 1, $clean_url, $param_name, $existing_params); ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </span>
                        </li>
                    <?php endif; ?>

                    <?php
                    // Calcular rango de páginas a mostrar
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Ajustar para mostrar siempre 5 páginas cuando sea posible
                    if ($end_page - $start_page < 4) {
                        if ($start_page == 1) {
                            $end_page = min($total_pages, $start_page + 4);
                        } else {
                            $start_page = max(1, $end_page - 4);
                        }
                    }
                    ?>

                    <!-- Primera página si no está en el rango -->
                    <?php if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl(1, $clean_url, $param_name, $existing_params); ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Páginas en el rango -->
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <li class="page-item active" aria-current="page">
                                <span class="page-link"><?php echo $i; ?></span>
                            </li>
                        <?php else: ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($i, $clean_url, $param_name, $existing_params); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Última página si no está en el rango -->
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($total_pages, $clean_url, $param_name, $existing_params); ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Botón Siguiente -->
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($current_page + 1, $clean_url, $param_name, $existing_params); ?>" aria-label="Siguiente">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-label="Siguiente">
                                <span aria-hidden="true">&raquo;</span>
                            </span>
                        </li>
                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </div>
</div>

