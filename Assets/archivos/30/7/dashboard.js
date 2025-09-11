/**
 * Archivo: Assets/js/pages/dashboard.js
 * Dashboard de administración - Scripts principales
 * Creado por el equipo Gaes 1:
 * Anyi Solayi Tapias
 * Sharit Delgado Pinzón
 * Durly Yuranni Sánchez Carillo
 * Año: 2025
 * SENA - CSET - ADSO
 */

// Verificar que ApexCharts esté disponible
function checkDependencies() {
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts no está disponible. Asegúrate de incluir la librería.');
        return false;
    }
    return true;
}

// Variables globales para los gráficos
let activityChart, fileTypeChart, usersActivityChart;
let dashboardData = {};

/**
 * Inicializa todos los gráficos del dashboard
 */
function initializeDashboard(data) {
    console.log('Iniciando dashboard con datos:', data);
    
    if (!checkDependencies()) {
        return;
    }
    
    if (!data || !data.actividad || !data.tipos_archivos || !data.usuarios_activos) {
        console.error('Datos del dashboard incompletos:', data);
        return;
    }
    
    dashboardData = data;
    
    try {
        // Inicializar gráficos con un pequeño delay para asegurar que el DOM esté listo
        setTimeout(() => {
            initActivityChart();
            initFileTypeChart();
            initUsersActivityChart();
            console.log('Dashboard inicializado correctamente');
        }, 100);
    } catch (error) {
        console.error('Error al inicializar dashboard:', error);
    }
}

/**
 * Configuración del gráfico de actividad de archivos
 */
function initActivityChart() {
    const element = document.querySelector("#apex-activity");
    if (!element) {
        console.error('Elemento #apex-activity no encontrado');
        return;
    }
    
    const activityOptions = {
        chart: {
            type: 'line',
            height: '100%',
            toolbar: { show: true },
            animations: { enabled: true }
        },
        series: [{
            name: 'Archivos Subidos',
            data: dashboardData.actividad.cantidades || []
        }],
        xaxis: {
            categories: dashboardData.actividad.fechas || [],
            title: { text: 'Fecha' }
        },
        yaxis: { 
            title: { text: 'Cantidad' } 
        },
        colors: ['#00E396'],
        dataLabels: { enabled: true },
        stroke: { 
            curve: 'smooth',
            width: 3
        },
        grid: { 
            borderColor: '#e7e7e7',
            strokeDashArray: 3
        },
        tooltip: {
            theme: 'dark',
            x: {
                format: 'dd MMM yyyy'
            }
        }
    };
    
    activityChart = new ApexCharts(element, activityOptions);
    activityChart.render();
}

/**
 * Configuración del gráfico de tipos de archivos
 */
function initFileTypeChart() {
    const element = document.querySelector("#apex-file-types");
    if (!element) {
        console.error('Elemento #apex-file-types no encontrado');
        return;
    }
    
    const cantidades = dashboardData.tipos_archivos.cantidades || {};
    
    const fileTypeOptions = {
        chart: {
            type: 'pie',
            height: 300,
            animations: { enabled: true }
        },
        series: Object.values(cantidades),
        labels: Object.keys(cantidades),
        colors: ['#FF4560', '#00E396', '#FEB019', '#775DD0', '#008FFB'],
        legend: { 
            position: 'bottom',
            fontSize: '12px'
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(val) {
                    return val + " archivos";
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { width: 200 },
                legend: { position: 'bottom' }
            }
        }]
    };
    
    fileTypeChart = new ApexCharts(element, fileTypeOptions);
    fileTypeChart.render();
}

/**
 * Configuración del gráfico de usuarios activos
 */
function initUsersActivityChart() {
    const element = document.querySelector("#apex-users-activity");
    if (!element) {
        console.error('Elemento #apex-users-activity no encontrado');
        return;
    }
    
    const cantidades = dashboardData.usuarios_activos.cantidades || {};
    
    const usersActivityOptions = {
        chart: {
            type: 'bar',
            height: 300,
            animations: { enabled: true }
        },
        series: [{
            name: 'Archivos Subidos',
            data: Object.values(cantidades)
        }],
        xaxis: {
            categories: Object.keys(cantidades),
            title: { text: 'Usuarios' }
        },
        yaxis: { 
            title: { text: 'Archivos' } 
        },
        colors: ['#008FFB'],
        dataLabels: { 
            enabled: true,
            style: {
                colors: ['#fff']
            }
        },
        plotOptions: { 
            bar: { 
                horizontal: false,
                borderRadius: 4,
                dataLabels: {
                    position: 'top'
                }
            } 
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(val) {
                    return val + " archivos";
                }
            }
        }
    };
    
    usersActivityChart = new ApexCharts(element, usersActivityOptions);
    usersActivityChart.render();
}

/**
 * Actualiza todos los gráficos del dashboard
 */
function actualizarDashboard() {
    console.log('Actualizando dashboard...');
    
    // Actualizar estadísticas (función que debe existir en otro archivo)
    if (typeof actualizarEstadisticas === 'function') {
        actualizarEstadisticas();
    }
    
    // Actualizar gráficos con nuevos datos
    if (activityChart && dashboardData.actividad) {
        activityChart.updateSeries([{
            data: dashboardData.actividad.cantidades || []
        }]);
    }
    
    if (fileTypeChart && dashboardData.tipos_archivos) {
        fileTypeChart.updateSeries(Object.values(dashboardData.tipos_archivos.cantidades || {}));
    }
    
    if (usersActivityChart && dashboardData.usuarios_activos) {
        usersActivityChart.updateSeries([{
            data: Object.values(dashboardData.usuarios_activos.cantidades || {})
        }]);
    }
    
    console.log('Dashboard actualizado correctamente');
}

/**
 * Actualiza los datos del dashboard mediante AJAX
 */
function actualizarDatosAjax() {
    fetch('Controllers/DashboardController.php?action=getDashboardData')
        .then(response => response.json())
        .then(data => {
            dashboardData = data;
            actualizarDashboard();
        })
        .catch(error => {
            console.error('Error al actualizar datos:', error);
        });
}

/**
 * Redimensiona los gráficos cuando cambia el tamaño de la ventana
 */
function resizeCharts() {
    if (activityChart) activityChart.resize();
    if (fileTypeChart) fileTypeChart.resize();
    if (usersActivityChart) usersActivityChart.resize();
}

/**
 * Función principal de inicialización
 */
function initDashboard() {
    // Verificar que los datos estén disponibles
    if (typeof window.dashboardDataFromPHP !== 'undefined') {
        console.log('Datos del dashboard encontrados, inicializando...');
        initializeDashboard(window.dashboardDataFromPHP);
    } else {
        console.error('No se encontraron los datos del dashboard. Reintentando en 1 segundo...');
        // Reintentar después de 1 segundo
        setTimeout(initDashboard, 1000);
    }
}

// Event listeners
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    // El DOM ya está cargado
    initDashboard();
}

// Redimensionar gráficos al cambiar tamaño de ventana
window.addEventListener('resize', resizeCharts);

// Auto-actualización cada 5 minutos (comentado por defecto)
// setInterval(actualizarDatosAjax, 300000); // 5 minutos

// Exportar funciones para uso global (opcional)
window.dashboardFunctions = {
    initializeDashboard,
    actualizarDashboard,
    actualizarDatosAjax,
    resizeCharts
};

console.log('Dashboard.js cargado correctamente desde Assets/js/pages/');