<?php

/********************************************
Archivo php CompartidosModel.php - Versión Simplificada                      
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

class CompartidosModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    // Obtiene la información de un usuario por su ID.
    public function getUsuario($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha,fecha_ultimo_cambio_clave FROM obtener_usuario_por_id(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // Obtiene todos los archivos compartidos con un usuario.
    public function getArchivosCompartidosConmigoDirecto($correo_usuario)
    {
        $sql = "SELECT * FROM obtener_archivos_compartidos_conmigo(?)";
        return $this->selectAll($sql, [$correo_usuario]);
    }

    // Obtiene los detalles de un archivo compartido específico.
    public function getDetalleArchivoCompartido($id_detalle, $correo_usuario)
    {
        $sql = "SELECT * FROM obtener_detalle_archivo_compartido(?, ?)";
        $result = $this->select($sql, [$id_detalle, $correo_usuario]);

        if (!empty($result)) {
            $result['propietario'] = $result['propietario'] . ' ' . ($result['apellido_propietario'] ?? '');
            $result['compartido'] = $result['propietario'];
            $result['nombre'] = $result['nombre_archivo'];
        }

        return $result;
    }

    // Verifica si un archivo compartido pertenece a un usuario.
    public function verificarArchivoCompartidoConUsuario($id_detalle, $correo_usuario)
    {
        $sql = "SELECT * FROM verificar_archivo_compartido_usuario(?, ?)";
        return $this->select($sql, [$id_detalle, $correo_usuario]);
    }

    // Cambia el estado de un archivo compartido.
    public function cambiarEstado($estado, $id_detalle)
    {
        $sql = "SELECT cambiar_estado_archivo_compartido(?, ?) as resultado";
        $result = $this->select($sql, [$estado, $id_detalle]);
        return $result['resultado'] ?? 0;
    }

    // Marca un archivo como visto.
    public function marcarComoVisto($id_detalle)
    {
        $sql = "SELECT marcar_archivo_como_visto(?) as resultado";
        $result = $this->select($sql, [$id_detalle]);
        return $result['resultado'] ?? 0;
    }

    // Obtiene los archivos que el usuario actual ha compartido.
    public function getArchivosCompartidosPorMi($id_usuario)
    {
        $sql = "SELECT * FROM obtener_archivos_compartidos_por_mi(?)";
        return $this->selectAll($sql, [$id_usuario]);
    }

    // Obtiene estadísticas sobre archivos compartidos.
    public function getEstadisticasCompartidos($correo_usuario = null)
    {
        if ($correo_usuario) {
            $sql = "SELECT * FROM obtener_estadisticas_compartidos_usuario(?)";
            $result = $this->select($sql, [$correo_usuario]);

            return [
                'total_recibidos' => $result['total_recibidos'] ?? 0,
                'recibidos_hoy' => $result['recibidos_hoy'] ?? 0
            ];
        } else {
            $sql = "SELECT * FROM obtener_estadisticas_compartidos_globales()";
            $result = $this->select($sql);

            return [
                'total_compartidos' => $result['total_compartidos'] ?? 0,
                'compartidos_hoy' => $result['compartidos_hoy'] ?? 0
            ];
        }
    }

    // Busca archivos compartidos por un término de búsqueda.
    public function buscarArchivosCompartidos($correo_usuario, $termino_busqueda)
    {
        $sql = "SELECT * FROM buscar_archivos_compartidos(?, ?)";
        return $this->selectAll($sql, [$correo_usuario, $termino_busqueda]);
    }

    // Obtiene los archivos compartidos más recientes.
    public function getArchivosCompartidosRecientesSimplificado($limite = 5)
    {
        $sql = "SELECT * FROM obtener_archivos_compartidos_recientes(?)";
        $data = $this->selectAll($sql, [$limite]);

        foreach ($data as &$row) {
            $row['fecha'] = time_ago(strtotime($row['fecha_add']));
            $row['descripcion'] = "Archivo '{$row['nombre_archivo']}' compartido por {$row['propietario']} con {$row['receptor']}";
        }

        return $data;
    }

    // Cuenta los archivos compartidos de un usuario.
    public function verificarEstado($correo)
    {
        $sql = "SELECT total FROM contar_archivos_compartidos_usuario(?)";
        $resultado = $this->select($sql, [$correo]);
        return $resultado;
    }

    // Obtiene la información completa del archivo original.
    public function getArchivoCompleto($id_archivo)
    {
        $sql = "SELECT * FROM obtener_archivo_completo(?)";
        return $this->select($sql, [$id_archivo]);
    }

    // Busca los detalles de un archivo compartido.
    public function getDetalle($id_detalle)
    {
        $sql = "SELECT * FROM obtener_detalle_archivo_compartido(?, '')";
        $result = $this->select($sql, [$id_detalle]);

        if (!empty($result) && is_array($result)) {
            if (isset($result['fecha_add'])) {
                $result['fecha'] = $result['fecha_add'];
            }
            if (isset($result['propietario']) && isset($result['apellido_propietario'])) {
                $result['usuario'] = $result['propietario'];
                $result['usuario_apellido'] = $result['apellido_propietario'];
            }
            if (isset($result['nombre_archivo'])) {
                $result['nombre'] = $result['nombre_archivo'];
            }

            $result['tipo_compartido'] = 'directo';

            if (isset($result[0]) && is_array($result[0])) {
                return $result[0];
            }
            return $result;
        }

        return false;
    }

    // Verifica el acceso a un archivo compartido.

    public function verificarAccesoArchivo($id_archivo, $correo_usuario)
    {
        // Validación básica en PHP
        if (empty($id_archivo) || !is_numeric($id_archivo) || $id_archivo <= 0) {
            return false;
        }

        if (empty($correo_usuario) || !filter_var($correo_usuario, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Llamar al procedimiento almacenado
        $sql = "SELECT tiene_acceso, id_solicitud, mensaje FROM verificar_acceso_archivo(?, ?)";
        $resultado = $this->select($sql, [$id_archivo, $correo_usuario]);

        // Retornar boolean basado en el resultado del procedimiento
        return !empty($resultado) && $resultado['tiene_acceso'] === true;
    }

    // Obtiene los detalles de un archivo compartido con permisos.
    public function getDetalleConPermisos($id_detalle, $correo_usuario)
    {
        $result = $this->getDetalleArchivoCompartido($id_detalle, $correo_usuario);

        if (!empty($result)) {
            $result['usuario'] = $result['propietario'] ?? '';
            $result['usuario_apellido'] = $result['apellido_propietario'] ?? '';
            $result['compartido'] = $result['propietario'] ?? '';
            $result['compartido_apellido'] = $result['apellido_propietario'] ?? '';
            $result['correo_propietario'] = $correo_usuario;
        }

        return $result;
    }

    // Obtiene los detalles de un archivo compartido en una tabla diferente.
    public function getDetalleCompartidoConmigo($id_compartido, $id_usuario)
    {
        $sql = "SELECT 
                acc.id,
                acc.id_archivo_original,
                acc.fecha_aceptado,
                acc.nombre_personalizado,
                a.nombre as nombre_archivo,
                a.tipo,
                a.id_carpeta,
                a.tamano,
                a.fecha_create as fecha_archivo,
                CONCAT(u_prop.nombre, ' ', u_prop.apellido) as propietario,
                u_prop.correo as correo_propietario,
                u_prop.avatar as avatar_propietario,
                c.nombre as carpeta_nombre
            FROM compartidos acc
            INNER JOIN archivos a ON acc.id_archivo_original = a.id
            INNER JOIN usuarios u_prop ON acc.id_usuario_propietario = u_prop.id
            INNER JOIN carpetas c ON a.id_carpeta = c.id
            WHERE acc.id = ? 
            AND acc.id_usuario_receptor = ? 
            AND acc.estado = 1";

        return $this->select($sql, [$id_compartido, $id_usuario]);
    }
}
