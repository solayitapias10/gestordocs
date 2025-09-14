<?php

/********************************************
Archivo php AdminModel.php
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

class AdminModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    // 1. Obtiene las carpetas principales de un usuario.
    public function getCarpetas($id_usuario)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetasprincipales(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // 2. Obtiene las subcarpetas de una carpeta específica para un usuario.
    public function getSubCarpetas($id_carpeta, $id_usuario)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenersubcarpetas(?, ?)";
        return $this->selectAll($sql, [$id_carpeta, $id_usuario]);
    }

    // 3. Crea una nueva carpeta.
    public function crearCarpeta($id_usuario, $nombre)
    {
        $sql = "SELECT fun_crearcarpeta(?, ?) AS id";
        $datos = array($id_usuario, $nombre);
        $resultado = $this->select($sql, $datos);
        return $resultado ? $resultado['id'] : 0;
    }

    // 4. Verifica si ya existe una carpeta con el mismo nombre.
    public function getVerificar($item, $nombre, $id_usuario, $id, $id_carpeta_padre = null)
    {
        // Validar entrada
        if (empty($item) || empty($nombre)) {
            return [];
        }
        if (empty($id_usuario) || !is_numeric($id_usuario) || $id_usuario <= 0) {
            return [];
        }
        if (!is_numeric($id) || $id < 0) {
            return [];
        }
        // Solo permitir el campo 'nombre' por seguridad
        if ($item !== 'nombre') {
            return [];
        }

        $sql = "SELECT id, existe, mensaje FROM fun_verificar(?, ?, ?, ?, ?)";
        $params = [$item, $nombre, $id_usuario, $id, $id_carpeta_padre];

        $resultado = $this->select($sql, $params);
        if (!empty($resultado) && $resultado['existe'] === true && !empty($resultado['id'])) {
            return ['id' => $resultado['id']];
        }

        return [];
    }

    // 5. Obtiene la información de un usuario por su ID.
    public function getUsuario($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporid(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // 6. Modifica la información de un usuario.
    public function modificar($nombre, $apellido, $correo, $telefono, $direccion, $rol, $id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, rol FROM fun_modificarusuario(?, ?, ?, ?, ?, ?, ?)";
        $datos = array($nombre, $apellido, $correo, $telefono, $direccion, $rol, $id);
        return $this->save($sql, $datos);
    }

    // 7. Sube un archivo a la base de datos.
    public function subirArchivo($nombre, $tipo, $tamano, $id_carpeta, $id_usuario)
    {
        $sql = "SELECT id_archivo_nuevo, mensaje, exito FROM fun_subirarchivo(?, ?, ?, ?, ?)";
        $params = [$nombre, $tipo, $tamano, $id_carpeta, $id_usuario];
        $resultado = $this->select($sql, $params);

        if (!empty($resultado)) {
            return $resultado;
        } else {
            return [
                'id_archivo_nuevo' => null,
                'mensaje' => 'Error al ejecutar la función de base de datos',
                'exito' => false
            ];
        }
    }

    // 8. Obtiene todos los archivos dentro de una carpeta.
    public function getArchivos($id_carpeta, $id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivosusuario(?, ?)";
        return $this->selectAll($sql, [$id_carpeta, $id_usuario]);
    }

    // 9. Obtiene los archivos compartidos de una carpeta.
    public function getArchivosCompartidos($id_carpeta)
    {
        $sql = "SELECT id, correo, estado, elimina, nombre FROM fun_obtenerarchivoscompartidos(?)";
        return $this->selectAll($sql, [$id_carpeta]);
    }

    // 10. Obtiene la información de una carpeta por su ID.
    public function getCarpeta($id)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetaporid(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }
    // 11. Verifica el estado de los archivos compartidos por un correo.
    public function verificarEstado($correo)
    {
        $sql = "SELECT total FROM fun_verificarestadosolicitudes(?)";
        $datos = array($correo);
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 12. Edita el nombre de una carpeta.
    public function editarCarpeta($nombre, $id, $id_usuario)
    {
        $sql = "SELECT filas_afectadas, mensaje, exito FROM fun_editarcarpeta(?, ?, ?)";
        $datos = array($nombre, $id, $id_usuario);
        $resultado = $this->select($sql, $datos);

        if (!empty($resultado)) {
            return $resultado['filas_afectadas'];
        } else {
            return 0;
        }
    }

    // 13. Obtiene todas las carpetas principales.
    public function getCarpetasAll()
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenertodascarpetas(?)";
        return $this->selectAll($sql, [6]);
    }

    // 14. Obtiene los archivos más recientes de todos los usuarios.
    public function getArchivosRecientesAll()
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivosrecientes(?)";
        return $this->selectAll($sql, [10]);
    }

    // 15. Obtiene el estado de todos los archivos compartidos.
    public function verificarEstadoAll()
    {
        $sql = "SELECT * FROM fun_actividadtodos()";
        return $this->select($sql);
    }

    // 16. Obtiene la actividad de archivos de los últimos 30 días.
    public function getActividadArchivosAll()
    {
        $sql = "SELECT fecha, cantidad FROM fun_obteneractividadarchivos(?)";
        return $this->selectAll($sql, [30]);
    }

    // 17. Obtiene estadísticas globales del sistema.
    public function getEstadisticasGlobales()
    {
        $sql = "SELECT total_carpetas, total_archivos, total_compartidos, total_usuarios, espacio_total, espacio_porcentaje, carpetas_ayer, archivos_ayer, compartidos_ayer, usuarios_ayer, tendencia_carpetas, tendencia_archivos, tendencia_compartidos, tendencia_usuarios FROM fun_obtenerestadisticas()";
        return $this->select($sql);
    }

    // 18. Obtiene el total de archivos por tipo.
    public function getTiposArchivos()
    {
        $sql = "SELECT tipo, cantidad FROM fun_obtenertiposarchivos(?)";
        $data = $this->selectAll($sql, [5]);
        $result = ['cantidades' => []];
        foreach ($data as $row) {
            $result['cantidades'][$row['tipo']] = $row['cantidad'];
        }
        return $result;
    }

    // 19. Obtiene los 5 usuarios más activos del sistema.
    public function getUsuariosActivos()
    {
        $sql = "SELECT nombre, cantidad FROM fun_obtenerusuariosactivos(?)";
        $data = $this->selectAll($sql, [5]);
        $result = ['cantidades' => []];
        foreach ($data as $row) {
            $result['cantidades'][$row['nombre']] = $row['cantidad'];
        }
        return $result;
    }

    // 20. Obtiene la actividad reciente del sistema.
    public function getActividadReciente()
    {
        $sql = "SELECT tipo, nombre, fecha, descripcion FROM fun_obteneractividadreciente(?)";
        $data = $this->selectAll($sql, [5]);
        return $data;
    }

    // 21. Obtiene la información de un archivo por su ID y el ID del usuario.
    public function getArchivo($id, $id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivoporidusuario(?, ?)";
        $archivo = $this->select($sql, [$id, $id_usuario]);
        if (!empty($archivo) && isset($archivo['tamano'])) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivo;
    }

    // 22. Obtiene la información de un usuario por su correo electrónico.
    public function getUsuarioPorCorreo($correo)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, fecha, estado, rol, avatar, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporcorreo(?)";
        return $this->select($sql, [$correo]);
    }

    // 23. Elimina una carpeta, marcándola como inactiva.
    public function eliminarCarpeta($id, $id_usuario)
    {
        $sql = "SELECT filas_afectadas, mensaje, exito FROM fun_eliminarcarpeta(?, ?)";
        $datos = array($id, $id_usuario);
        $resultado = $this->select($sql, $datos);
        if (!empty($resultado)) {
            return $resultado['filas_afectadas'];
        } else {
            return 0;
        }
    }

    // 24. Verifica si un archivo ya existe en una carpeta.
    public function getVerificarArchivo($nombre, $id_carpeta, $id_usuario)
    {
        $sql = "SELECT * FROM fun_verificararchivo(?, ?, ?)";
        $datos = array($nombre, $id_carpeta, $id_usuario);
        return $this->select($sql, $datos);
    }

    // 25. Obtiene los archivos compartidos más recientes.
    public function getArchivosCompartidosRecientes()
    {
        $sql = "SELECT id, nombre_archivo, usuario_propietario, fecha_add, correo, id_archivo, id_usuario FROM fun_obtenerarchivoscompartidosrecientes(?)";
        $data = $this->selectAll($sql, [2]);
        foreach ($data as &$row) {
            $row['fecha'] = time_ago(strtotime($row['fecha_add']));
            $row['descripcion'] = "Archivo compartido: {$row['nombre_archivo']} por {$row['usuario_propietario']}";
        }

        return $data;
    }

    // 26. Registra una notificación en la base de datos.
    public function registrarNotificacion($id_usuario, $id_carpeta, $nombre, $evento)
    {
        $sql = "SELECT id_notificacion_nueva, mensaje, exito FROM fun_registrarnotificacion(?, ?, ?, ?)";
        $datos = [$id_usuario, $id_carpeta, $nombre, $evento];
        $resultado = $this->select($sql, $datos);

        if (!empty($resultado)) {
            return $resultado;
        } else {
            return [
                'id_notificacion_nueva' => null,
                'mensaje' => 'Error al ejecutar la función de base de datos',
                'exito' => false
            ];
        }
    }

    // 27. Obtiene las notificaciones no leídas de un usuario.
    public function getNotificaciones($id_usuario)
    {
        $sql = "SELECT id, id_usuario, id_carpeta, id_solicitud, nombre, evento, fecha, leida FROM fun_obtenernotificaciones(?, ?)";
        return $this->selectAll($sql, [$id_usuario, 30]);
    }

    // 28. Marca una notificación como leída usando un procedimiento almacenado.
    public function marcarNotificacionLeida($id_notificacion, $id_usuario)
    {
        $sql = "SELECT fun_marcarNotificacionLeida(?, ?) AS exito";
        $datos = [$id_notificacion, $id_usuario];
        $resultado = $this->select($sql, $datos);
        return isset($resultado['exito']) && $resultado['exito'] === true;
    }

    // 29. Obtiene métricas del sistema como uso de memoria y CPU.
    public function getMetricasSistema()
    {
        $metricas = [];

        try {
            // 1. Obtener información de memoria
            $meminfo = shell_exec('free -m');
            if ($meminfo) {
                $lines = explode("\n", $meminfo);
                $mem_line = preg_split('/\s+/', $lines[1]);

                $memoria_total = (int) $mem_line[1]; // MB
                $memoria_usada = (int) $mem_line[2]; // MB
                $memoria_libre = (int) $mem_line[3]; // MB

                $metricas['memoria'] = [
                    'total' => $memoria_total,
                    'usada' => $memoria_usada,
                    'libre' => $memoria_libre,
                    'porcentaje' => round(($memoria_usada / $memoria_total) * 100, 1)
                ];
            }

            // 2. Obtener Load Average (CPU)
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $metricas['cpu'] = [
                    'load_1min' => round($load[0], 2),
                    'load_5min' => round($load[1], 2),
                    'load_15min' => round($load[2], 2),
                    'porcentaje' => min(round($load[0] * 100, 1), 100) // Estimación
                ];
            }

            // 3. Obtener Uptime del sistema
            $uptime_info = shell_exec('uptime');
            if ($uptime_info) {
                if (preg_match('/up\s+(\d+)\s+days?,\s+(\d+):(\d+)/', $uptime_info, $matches)) {
                    $dias = (int) $matches[1];
                    $horas = (int) $matches[2];
                    $minutos = (int) $matches[3];
                    $uptime_texto = $dias . "d " . $horas . "h " . $minutos . "m";
                } elseif (preg_match('/up\s+(\d+):(\d+)/', $uptime_info, $matches)) {
                    $horas = (int) $matches[1];
                    $minutos = (int) $matches[2];
                    $uptime_texto = $horas . "h " . $minutos . "m";
                } else {
                    $uptime_texto = "Desconocido";
                }

                $metricas['uptime'] = $uptime_texto;
            }

            // 4. Información de PHP
            $metricas['php'] = [
                'memoria_limite' => ini_get('memory_limit'),
                'memoria_usada' => round(memory_get_usage() / 1024 / 1024, 2), // MB
                'memoria_pico' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
                'version' => PHP_VERSION
            ];
        } catch (Exception $e) {
            $metricas = [
                'memoria' => ['total' => 0, 'usada' => 0, 'libre' => 0, 'porcentaje' => 0],
                'cpu' => ['load_1min' => 0, 'load_5min' => 0, 'load_15min' => 0, 'porcentaje' => 0],
                'uptime' => 'Error',
                'php' => [
                    'memoria_limite' => ini_get('memory_limit'),
                    'memoria_usada' => round(memory_get_usage() / 1024 / 1024, 2),
                    'memoria_pico' => round(memory_get_peak_usage() / 1024 / 1024, 2),
                    'version' => PHP_VERSION
                ]
            ];
        }

        return $metricas;
    }

    // 30. Obtiene el total de carpetas principales para calcular la paginación.
    public function getTotalCarpetas($id_usuario)
    {
        $sql = "SELECT total FROM fun_obtenertotalcarpetasusuario(?)";
        $resultado = $this->select($sql, [$id_usuario]);
        return $resultado['total'] ?? 0;
    }

    // 31. Obtiene carpetas y archivos con paginación.
    public function getCarpetasPaginado($id_usuario, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetaspaginado(?, ?, ?)";
        return $this->selectAll($sql, [$id_usuario, $limit, $offset]);
    }

    // 32. Obtiene archivos con paginación.
    public function getArchivosPaginado($id_usuario, $page = 1, $limit = 15)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, nombre, tipo, fecha_create, id_carpeta, tamano FROM fun_obtenerarchivospaginado(?, ?, ?)";
        return $this->selectAll($sql, [$id_usuario, $limit, $offset]);
    }

    // 33. Obtiene el total de archivos en una carpeta específica para calcular la paginación.
    public function getTotalArchivosCarpeta($id_carpeta)
    {
        $sql = "SELECT total FROM fun_obtenertotalarchivoscarpeta(?)";
        $resultado = $this->select($sql, [$id_carpeta]);
        return $resultado['total'] ?? 0;
    }

    // 34. Obtiene el total de archivos principales del usuario
    public function getTotalArchivos($id_usuario)
    {
        $sql = "SELECT total FROM fun_obtenertotalarchivosusuario(?)";
        $resultado = $this->select($sql, [$id_usuario]);
        return $resultado['total'] ?? 0;
    }
}
