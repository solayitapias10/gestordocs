<?php

/********************************************
Archivo php RecuperarModel.php - COMPATIBLE CON QUERY.PHP ACTUAL                       
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

class RecuperarModel extends Query
{
    public function __construct()
    {
        parent::__construct();
        $this->setTimezone();
    }

    // 1.  Obtiene un usuario por su correo si su estado es 1
    public function getUsuarioCorreo($correo)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporcorreop(?)";
        $datos = [$correo];
        return $this->select($sql, $datos);
    }

    // 2. Obtiene un usuario por su ID si su estado es 1
    public function getUsuarioPorId($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioid(?)";
        $datos = [$id];
        return $this->select($sql, $datos);
    }
    // 3. Guarda un token de recuperación en la base de datos
    public function guardarToken($idUsuario, $token, $fechaExpiracion, $ipSolicitud = null, $userAgent = null) 
    {
        $sql = "SELECT id_token_creado, success, mensaje FROM fun_guardartokenrecuperacion(?, ?, ?, ?, ?)";
        $datos = [$idUsuario, $token, $fechaExpiracion, $ipSolicitud, $userAgent];
        $resultado = $this->select($sql, $datos);
        return ($resultado && $resultado['success'] === true) ? $resultado['id_token_creado'] : false;
    }

    // 4. Valida un token de recuperación
    public function validarToken($token)
    {
        $sql = "SELECT id_token, id_usuario, token, fecha_expiracion, usado, nombre_usuario, apellido_usuario, correo_usuario
                FROM fun_validartokenrecuperacion(?)";
        $tokenData = $this->select($sql, [$token]);
        
        if (!$tokenData) {
            error_log("RecuperarModel::validarToken - Token inválido, expirado, usado o no encontrado: " . $token);
            return false;
        }
        error_log("RecuperarModel::validarToken - Token VÁLIDO: " . $token);
        return $tokenData;
    }

    // 5. Marca un token como usado
    public function marcarTokenUsado($token)
    {
        $sql = "SELECT success, mensaje, filas_afectadas FROM fun_marcartokenusado(?)";
        $datos = [$token];
        $resultado = $this->select($sql, $datos);
        return ($resultado && $resultado['success'] === true && $resultado['filas_afectadas'] > 0);
    }

    // 6. Invalida todos los tokens activos de un usuario
    public function invalidarTokensUsuario($idUsuario)
    {
        $sql = "SELECT success, mensaje, tokens_invalidados FROM fun_invalidartokensusuario(?)";
        $datos = [$idUsuario];
        $resultado = $this->select($sql, $datos);
        return ($resultado && $resultado['success'] === true);
    }

    // 7. Actualiza la contraseña de un usuario
    public function actualizarContrasena($idUsuario, $hashClave)
    {
        $sql = "SELECT success, mensaje, filas_afectadas FROM fun_actualizarcontrasena(?, ?)";
        $datos = [$idUsuario, $hashClave];
        $resultado = $this->select($sql, $datos);
        return ($resultado && $resultado['success'] === true && $resultado['filas_afectadas'] > 0);
    }
}