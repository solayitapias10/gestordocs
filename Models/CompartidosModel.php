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

    // 1. Obtiene la información de un usuario por su ID.
    public function getUsuario($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporid(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // 2. Obtiene todos los archivos compartidos con un usuario.
    public function getArchivosCompartidosConmigoDirecto($correo_usuario)
    {
        $sql = "SELECT id, fecha_add, correo, estado, id_archivo, nombre_archivo, tipo, tamano, propietario, avatar_propietario FROM fun_archivoscompartidos(?)";
        return $this->selectAll($sql, [$correo_usuario]);
    }

    // 3.  Obtiene los detalles de un archivo compartido específico.
    public function getDetalleArchivoCompartido($id_detalle, $correo_usuario)
    {
        $sql = "SELECT id, fecha_add, correo, estado, id_archivo, id_usuario, id_carpeta, nombre_archivo, tipo, tamano, propietario, apellido_propietario, avatar, carpeta_nombre FROM fun_obtenerdetallearchivocompartido(?, ?)";
        $result = $this->select($sql, [$id_detalle, $correo_usuario]);
        if (!empty($result)) {
            $result['propietario'] = $result['propietario'] . ' ' . ($result['apellido_propietario'] ?? '');
            $result['compartido'] = $result['propietario'];
            $result['nombre'] = $result['nombre_archivo'];
        }
        return $result;
    }

    // 4. Verifica si un archivo compartido pertenece a un usuario.
    public function verificarArchivoCompartidoConUsuario($id_detalle, $correo_usuario)
    {
        $sql = "SELECT id, correo, nombre FROM fun_verificararchivocompartidousuario(?, ?)";
        return $this->select($sql, [$id_detalle, $correo_usuario]);
    }

    // 5. Cambia el estado de un archivo compartido.
    public function cambiarEstado($estado, $id_detalle)
    {
        $sql = "SELECT fun_cambiarestado(?, ?) as resultado";
        $result = $this->select($sql, [$estado, $id_detalle]);
        return $result['resultado'] ?? 0;
    }

    // 6. Cuenta los archivos compartidos de un usuario.
    public function verificarEstado($correo)
    {
        $sql = "SELECT total FROM fun_verificarestadosolicitudescompartidas(?)";
        $resultado = $this->select($sql, [$correo]);
        return $resultado;
    }

    // 7. Obtiene la información completa del archivo original.
    public function getArchivoCompleto($id_archivo)
    {
        $sql = "SELECT id, nombre, tipo, fecha_create, estado, elimina, id_carpeta, id_usuario, tamano FROM fun_obtenerarchivocompartido(?)";
        return $this->select($sql, [$id_archivo]);
    }
}
