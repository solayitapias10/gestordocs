<?php

/********************************************
Archivo php ArchivosModel.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

class ArchivosModel extends Query
{
    // Inicializa la clase.
    public function __construct()
    {
        parent::__construct();
    }

    // Obtiene todos los archivos de un usuario que no están en carpetas.
    public function getArchivos($id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario,tamano FROM obtener_archivos_usuario(?)";
        $archivos = $this->selectAll($sql, [$id_usuario]);

        foreach ($archivos as &$archivo) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivos;
    }

    // Obtiene las carpetas principales de un usuario.
    public function getCarpetas($id_usuario)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM obtener_carpetas_principales(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // Busca usuarios por nombre para compartir un archivo.
    public function getUsuarios($valor, $id_usuario)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, rol, estado, fecha,avatar FROM buscar_usuarios_por_correo(?, ?)";
        return $this->selectAll($sql, [$valor, $id_usuario]);
    }

    // Obtiene los datos de un usuario específico.
    public function getUsuario($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM obtener_usuario_por_id(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // Registra un archivo compartido.
    public function registrarDetalle($correo, $id_archivo, $id_usuario)
    {
        $sql = "SELECT registrar_solicitud_compartida(?, ?, ?) as id";
        $resultado = $this->select($sql, [$correo, $id_archivo, $id_usuario]);
        return $resultado['id'] ?? 0;
    }

    // Verifica si un archivo ya ha sido compartido con un correo.
    public function getDetalle($correo, $id_archivo)
    {
        $sql = "SELECT id, fecha_add, correo, estado, elimina, id_archivo, id_usuario, aceptado FROM obtener_detalle_solicitud_compartida(?, ?)";
        $datos = array($correo, $id_archivo);
        return $this->select($sql, $datos);
    }

    // Obtiene los archivos dentro de una carpeta específica.
    public function getArchivosCarpeta($id_carpeta)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM obtener_archivos_carpeta(?)";
        return $this->selectAll($sql, [$id_carpeta]);
    }

    // Marca un archivo compartido como eliminado.
    public function eliminarCompartido($fecha, $id)
    {
        $sql = "SELECT eliminar_solicitud_compartida(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$fecha, $id]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // Obtiene los datos de una carpeta.
    public function getCarpeta($id)
    {
        $sql = "SELECT * FROM carpetas WHERE id = ? AND estado = 1";
        return $this->select($sql, [$id]);
    }
    // Marca un archivo como eliminado.
    public function eliminar($fecha, $id)
    {
        $sql = "SELECT eliminar_archivo(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$fecha, $id]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // Verifica el estado de los archivos compartidos por un correo.
    public function verificarEstado($correo)
    {
        $sql = "SELECT total FROM verificar_estado_solicitudes_compartidas(?)";
        $resultado = $this->select($sql, [$correo]);
        return $resultado;
    }

    // Busca archivos por nombre dentro de las carpetas de un usuario.
    public function getBusqueda($valor, $id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM buscar_archivos_usuario(?, ?)";
        $archivos = $this->selectAll($sql, [$valor, $id_usuario]);

        // Formatear el tamaño de los archivos encontrados
        foreach ($archivos as &$archivo) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }

        return $archivos;
    }

    // Obtiene un archivo específico por ID y usuario.
    public function getArchivo($id, $id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM obtener_archivo_por_id_usuario(?, ?)";
        $archivo = $this->select($sql, [$id, $id_usuario]);

        // Formatear el tamaño del archivo si existe
        if (!empty($archivo) && isset($archivo['tamano'])) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }

        return $archivo;
    }

    // Marca una carpeta como eliminada y establece una fecha de eliminación.
    public function eliminarCarpeta($id, $id_usuario)
    {
        $sql = "SELECT filas_afectadas, mensaje, exito FROM eliminar_carpeta_usuario(?, ?)";
        $datos = array($id, $id_usuario);
        $resultado = $this->select($sql, $datos);

        if (!empty($resultado)) {
            return $resultado['filas_afectadas'];
        } else {
            return 0;
        }
    }

    // Obtiene las carpetas en la papelera de un usuario.
    public function getPapeleraCarpetas($id_usuario)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM obtener_carpetas_papelera(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // Obtiene los archivos en la papelera de un usuario.
    public function getPapeleraArchivos($id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM obtener_archivos_papelera(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // Restaura una carpeta de la papelera.
    public function restaurarCarpeta($id, $id_usuario)
    {
        $sql = "SELECT restaurar_carpeta(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$id, $id_usuario]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // Restaura un archivo de la papelera.
    public function restaurarArchivo($id, $id_usuario)
    {
        $sql = "SELECT restaurar_archivo(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$id, $id_usuario]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // Elimina una carpeta de forma permanente.
    public function eliminarCarpetaPermanente($id, $id_usuario = null)
    {
        $sql = "SELECT eliminar_carpeta_permanente(?, ?) as total_eliminados";
        $parametros = [$id, $id_usuario];

        $resultado = $this->select($sql, $parametros);
        return $resultado['total_eliminados'] ?? 0;
    }

    // Elimina un archivo de forma permanente.
    public function eliminarArchivoPermanente($id, $id_usuario)
    {
        $sql = "SELECT eliminar_archivo_permanente(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$id, $id_usuario]);
        return $resultado['filas_afectadas'] ?? 0;
    }
        // Registra una notificación en la base de datos.
    public function registrarNotificacion($id_usuario, $id_carpeta, $nombre, $evento)
    {
        $sql = "SELECT id_notificacion_nueva, mensaje, exito FROM registrar_notificacion(?, ?, ?, ?)";
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

    // MÉTODOS DE PAGINACIÓN PARA ARCHIVOS

    // Obtiene todos los archivos de un usuario paginados.
    public function getPaginatedArchivos($id_usuario, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano 
                FROM obtener_archivos_usuario_paginados(?, ?, ?)";
        $archivos = $this->selectAll($sql, [$id_usuario, $limit, $offset]);

        foreach ($archivos as &$archivo) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivos;
    }

    // Obtiene el total de archivos de un usuario para calcular la paginación.
    public function getTotalArchivos($id_usuario)
    {
        $sql = "SELECT COUNT(*) as total FROM archivos WHERE id_usuario = ? AND estado = 1 AND id_carpeta IS NULL";
        $resultado = $this->select($sql, [$id_usuario]);
        return $resultado['total'] ?? 0;
    }

    // Obtiene archivos de una carpeta específica paginados.
    public function getPaginatedArchivosCarpeta($id_carpeta, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano 
                FROM obtener_archivos_carpeta_paginados(?, ?, ?)";
        $archivos = $this->selectAll($sql, [$id_carpeta, $limit, $offset]);

        foreach ($archivos as &$archivo) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivos;
    }

    // Obtiene el total de archivos de una carpeta específica.
    public function getTotalArchivosCarpeta($id_carpeta)
    {
        $sql = "SELECT COUNT(*) as total FROM archivos WHERE id_carpeta = ? AND estado = 1";
        $resultado = $this->select($sql, [$id_carpeta]);
        return $resultado['total'] ?? 0;
    }
}
