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


    // 1. Obtiene los datos de una carpeta.
    public function getCarpeta($id)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetaporid(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // 2. Obtiene todos los archivos de un usuario que no están en carpetas.
    public function getArchivos($id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario,tamano FROM fun_obtenerarchivosusuario(?)";
        $archivos = $this->selectAll($sql, [$id_usuario]);

        foreach ($archivos as &$archivo) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivos;
    }

    // 3. Obtiene las carpetas principales de un usuario.
    public function getCarpetas($id_usuario)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetasprincipales(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // 4. Busca usuarios por nombre para compartir un archivo.
    public function getUsuarios($valor, $id_usuario)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, rol, estado, fecha,avatar FROM fun_buscarusuariosporcorreo(?, ?)";
        return $this->selectAll($sql, [$valor, $id_usuario]);
    }

    // 5. Obtiene los datos de un usuario específico.
    public function getUsuario($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporid(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // 6. Registra un archivo compartido.
    public function registrarDetalle($correo, $id_archivo, $id_usuario)
    {
        $sql = "SELECT fun_registrarsolicitudcompartida(?, ?, ?) as id";
        $resultado = $this->select($sql, [$correo, $id_archivo, $id_usuario]);
        return $resultado['id'] ?? 0;
    }

    // 7. Verifica si un archivo ya ha sido compartido con un correo.
    public function getDetalle($correo, $id_archivo)
    {
        $sql = "SELECT id, fecha_add, correo, estado, elimina, id_archivo, id_usuario, aceptado FROM fun_obtenerdetallesolicitudcompartida(?, ?)";
        $datos = array($correo, $id_archivo);
        return $this->select($sql, $datos);
    }

    // 8. Obtiene los archivos dentro de una carpeta específica.
    public function getArchivosCarpeta($id_carpeta)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivoscarpeta(?)";
        return $this->selectAll($sql, [$id_carpeta]);
    }

    // 9. Marca un archivo compartido como eliminado.
    public function eliminarCompartido($fecha, $id)
    {
        $sql = "SELECT fun_eliminarsolicitudcompartida(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$fecha, $id]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // 10. Marca un archivo como eliminado.
    public function eliminar($fecha, $id)
    {
        $sql = "SELECT fun_eliminararchivo(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$fecha, $id]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // 11. Verifica el estado de los archivos compartidos por un correo.
    public function verificarEstado($correo)
    {
        $sql = "SELECT total FROM fun_verificarestadosolicitudescompartidas(?)";
        $resultado = $this->select($sql, [$correo]);
        return $resultado;
    }

    // 12. Busca archivos por nombre dentro de las carpetas de un usuario.
    public function getBusqueda($valor, $id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_buscararchivosusuario(?, ?)";
        $archivos = $this->selectAll($sql, [$valor, $id_usuario]);
        foreach ($archivos as &$archivo) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivos;
    }

    // 13. Obtiene un archivo específico por ID y usuario.
    public function getArchivo($id, $id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivoporidusuario(?, ?)";
        $archivo = $this->select($sql, [$id, $id_usuario]);
        if (!empty($archivo) && isset($archivo['tamano'])) {
            $archivo['tamano_formateado'] = formatearTamano($archivo['tamano']);
        }
        return $archivo;
    }

    // 14. Marca una carpeta como eliminada y establece una fecha de eliminación.
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

    // 15. Obtiene las carpetas en la papelera de un usuario.
    public function getPapeleraCarpetas($id_usuario)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetaspapelera(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // 16. Obtiene los archivos en la papelera de un usuario.
    public function getPapeleraArchivos($id_usuario)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivospapelera(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // 17.Restaura una carpeta de la papelera.
    public function restaurarCarpeta($id, $id_usuario)
    {
        $sql = "SELECT fun_restaurar_carpeta(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$id, $id_usuario]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // 18. Restaura un archivo de la papelera.
    public function restaurarArchivo($id, $id_usuario)
    {
        $sql = "SELECT fun_restaurar_archivo(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$id, $id_usuario]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // 19. Elimina una carpeta de forma permanente.
    public function eliminarCarpetaPermanente($id, $id_usuario = null)
    {
        $sql = "SELECT fun_eliminarcarpetapermanente(?, ?) as total_eliminados";
        $parametros = [$id, $id_usuario];

        $resultado = $this->select($sql, $parametros);
        return $resultado['total_eliminados'] ?? 0;
    }

    // 20. Elimina un archivo de forma permanente.
    public function eliminarArchivoPermanente($id, $id_usuario)
    {
        $sql = "SELECT fun_eliminararchivopermanente(?, ?) as filas_afectadas";
        $resultado = $this->select($sql, [$id, $id_usuario]);
        return $resultado['filas_afectadas'] ?? 0;
    }

    // 21. Registra una notificación en la base de datos.
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

    // 22. Obtiene el total de archivos de una carpeta específica.
    public function getTotalArchivosCarpeta($id_carpeta)
    {
        $sql = "SELECT total FROM fun_obtenertotalarchivoscarpeta(?)";
        $resultado = $this->select($sql, [$id_carpeta]);
        return $resultado['total'] ?? 0;
    }

    // 23. Obtiene los archivos de un usuario de forma paginada.
    public function getArchivosPaginado($id_usuario, $page = 1, $limit = 15)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, nombre, tipo, fecha_create, id_carpeta, tamano FROM fun_obtenerarchivospaginado(?, ?, ?)";
        return $this->selectAll($sql, [$id_usuario, $limit, $offset]);
    }

    // 24. Obtiene el total de archivos principales del usuario
    public function getTotalArchivos($id_usuario)
    {
        $sql = "SELECT total FROM fun_obtenertotalarchivosusuario(?)";
        $resultado = $this->select($sql, [$id_usuario]);
        return $resultado['total'] ?? 0;
    }

    // 25. Obtiene las carpetas del usuario de forma paginada
    public function getCarpetasPaginado($id_usuario, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetaspaginado(?, ?, ?)";
        return $this->selectAll($sql, [$id_usuario, $limit, $offset]);
    }

    // 26. Obtiene el total de carpetas principales del usuario
    public function getTotalCarpetas($id_usuario)
    {
        $sql = "SELECT total FROM fun_obtenertotalcarpetasusuario(?)";
        $resultado = $this->select($sql, [$id_usuario]);
        return $resultado['total'] ?? 0;
    }

    // 27. obtener carpeta por nombre y usuario
    public function getCarpetaPorNombre($id_usuario, $nombre)
    {
        $sql = "SELECT id, nombre, fecha_create, estado, elimina, id_usuario, id_carpeta_padre FROM fun_obtenercarpetapornombre(?, ?)";
        $datos = array($id_usuario, $nombre);
        return $this->select($sql, $datos);
    }

    // 28. Crear carpeta
    public function crearCarpeta($id_usuario, $nombre)
    {
        $sql = "SELECT fun_crearcarpeta(?, ?) AS id";
        $datos = array($id_usuario, $nombre);
        $resultado = $this->select($sql, $datos);
        return $resultado ? $resultado['id'] : 0;
    }

    // 29. Registrar archivo
    public function registrarArchivo($nombre, $tipo, $id_carpeta, $id_usuario, $tamano, $messageId)
    {
        $sql = "SELECT fun_registrararchivo(?, ?, ?, ?, ?, ?) AS id";
        $datos = array($nombre, $tipo, $id_carpeta, $id_usuario, $tamano, $messageId);
        $resultado = $this->select($sql, $datos);
        return $resultado ? $resultado['id'] : 0;
    }

    // 30. Verifica si un archivo ya existe para un mensaje y nombre dado
    public function archivoYaExiste($messageId, $nombreArchivo)
    {
        $sql = "SELECT fun_archivoyaexiste(?, ?) AS existe";
        $datos = array($messageId, $nombreArchivo);
        $resultado = $this->select($sql, $datos);
        return ($resultado && $resultado['existe']);
    }
}
