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
        // Establecer timezone
        $this->setTimezone();
    }

    // Obtiene un usuario por su correo si su estado es 1
    public function getUsuario($correo)
    {
        return $this->select("SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM usuarios WHERE correo = ? AND estado = 1", [$correo]);
    }

    // Obtiene un usuario por su ID si su estado es 1
    public function getUsuarioPorId($id)
    {
        return $this->select("SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM usuarios WHERE id = ? AND estado = 1", [$id]);
    }

    // Guarda un token de recuperación en la base de datos
    public function guardarToken($idUsuario, $token, $fechaExpiracion, $ipSolicitud = null, $userAgent = null)
    {
        $sql = "INSERT INTO tokens_recuperacion (id_usuario, token, fecha_expiracion, ip_solicitud, user_agent) VALUES (?, ?, ?, ?, ?)";
        $datos = [$idUsuario, $token, $fechaExpiracion, $ipSolicitud, $userAgent];
        return $this->insertar($sql, $datos);
    }

    // Valida un token de recuperación
    public function validarToken($token)
    {
        // Primera consulta: obtener el token con información detallada
        $sqlToken = "SELECT tr.*, u.nombre, u.apellido, u.correo,
                     tr.fecha_expiracion,
                     NOW() as fecha_actual,
                     EXTRACT(EPOCH FROM tr.fecha_expiracion) as expiracion_timestamp,
                     EXTRACT(EPOCH FROM NOW()) as actual_timestamp
                     FROM tokens_recuperacion tr 
                     INNER JOIN usuarios u ON tr.id_usuario = u.id 
                     WHERE tr.token = ? 
                     AND u.estado = 1";
        
        $tokenData = $this->select($sqlToken, [$token]);
        
        if (!$tokenData) {
            error_log("RecuperarModel::validarToken - Token no encontrado: " . $token);
            return false;
        }
        
        // Log para debug
        $usado = $this->normalizarBoolean($tokenData['usado']);
        error_log("RecuperarModel::validarToken - Token encontrado - Usado: " . ($usado ? 'true' : 'false') . 
                  ", Expira: " . $tokenData['fecha_expiracion'] . 
                  ", Actual: " . $tokenData['fecha_actual']);
        
        // Verificar si ya fue usado
        if ($usado) {
            error_log("RecuperarModel::validarToken - Token ya usado: " . $token);
            return false;
        }
        
        // Verificar expiración usando timestamps para mayor precisión
        $actual = floatval($tokenData['actual_timestamp']);
        $expiracion = floatval($tokenData['expiracion_timestamp']);
        
        if ($actual > $expiracion) {
            error_log("RecuperarModel::validarToken - Token expirado - Actual: " . date('Y-m-d H:i:s', $actual) . 
                      ", Expira: " . date('Y-m-d H:i:s', $expiracion));
            return false;
        }
        
        error_log("RecuperarModel::validarToken - Token VÁLIDO: " . $token);
        return $tokenData;
    }

    // Método alternativo más permisivo para debug
    public function validarTokenDebug($token)
    {
        $sql = "SELECT tr.*, u.nombre, u.apellido, u.correo,
                tr.fecha_expiracion,
                NOW() as fecha_actual,
                EXTRACT(EPOCH FROM (tr.fecha_expiracion - NOW()))/60 as minutos_restantes,
                CASE 
                    WHEN tr.usado = 1 THEN 'USADO'
                    WHEN tr.fecha_expiracion < NOW() THEN 'EXPIRADO'
                    ELSE 'ACTIVO'
                END as estado_token
                FROM tokens_recuperacion tr 
                INNER JOIN usuarios u ON tr.id_usuario = u.id 
                WHERE tr.token = ? 
                AND u.estado = 1
                ORDER BY tr.fecha_creacion DESC
                LIMIT 1";
        
        $result = $this->select($sql, [$token]);
        
        if ($result) {
            $usado = $this->normalizarBoolean($result['usado']);
            error_log("RecuperarModel::validarTokenDebug - Estado: {$result['estado_token']}, Usado: " . ($usado ? 'true' : 'false') . ", Minutos restantes: {$result['minutos_restantes']}");
        } else {
            error_log("RecuperarModel::validarTokenDebug - Token no encontrado: " . $token);
        }
        
        return $result;
    }

    // Normaliza valores boolean de PostgreSQL
    private function normalizarBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $value === 't' || $value === 'true' || $value === '1';
        }
        if (is_numeric($value)) {
            return intval($value) === 1;
        }
        return false;
    }

    // Marca un token como usado
    public function marcarTokenUsado($token)
    {
        $sql = "UPDATE tokens_recuperacion SET usado = 1 WHERE token = ?";
        return $this->save($sql, [$token]);
    }

    // Invalida todos los tokens activos de un usuario
    public function invalidarTokensUsuario($idUsuario)
    {
        $sql = "UPDATE tokens_recuperacion 
                SET usado = 1 
                WHERE id_usuario = ? 
                AND usado = 0 
                AND fecha_expiracion > NOW()";
        
        return $this->save($sql, [$idUsuario]);
    }

    // Actualiza la contraseña de un usuario
    public function actualizarContrasena($idUsuario, $hashClave)
    {
        $sql = "UPDATE usuarios 
                SET clave = ?, fecha_ultimo_cambio_clave = NOW() 
                WHERE id = ? AND estado = 1";
        
        return $this->save($sql, [$hashClave, $idUsuario]);
    }

    // Método para verificar si un token existe (sin validaciones)
    public function existeToken($token)
    {
        $sql = "SELECT COUNT(*) as total FROM tokens_recuperacion WHERE token = ?";
        $result = $this->select($sql, [$token]);
        return $result && $result['total'] > 0;
    }

    // Obtener información completa de un token para debug
    public function getInfoToken($token)
    {
        $sql = "SELECT tr.*, u.nombre, u.apellido, u.correo,
                TO_CHAR(tr.fecha_creacion, 'YYYY-MM-DD HH24:MI:SS') as creacion_formateada,
                TO_CHAR(tr.fecha_expiracion, 'YYYY-MM-DD HH24:MI:SS') as expiracion_formateada,
                TO_CHAR(NOW(), 'YYYY-MM-DD HH24:MI:SS') as actual_formateada,
                CASE 
                    WHEN tr.usado = 1 THEN 'USADO'
                    WHEN tr.fecha_expiracion < NOW() THEN 'EXPIRADO'
                    ELSE 'ACTIVO'
                END as estado_token,
                EXTRACT(EPOCH FROM (tr.fecha_expiracion - NOW()))/60 as minutos_restantes
                FROM tokens_recuperacion tr 
                LEFT JOIN usuarios u ON tr.id_usuario = u.id 
                WHERE tr.token = ?";
        
        return $this->select($sql, [$token]);
    }

    // Método de limpieza para tokens expirados
    public function limpiarTokensExpirados()
    {
        $sql = "DELETE FROM tokens_recuperacion WHERE fecha_expiracion < NOW()";
        return $this->save($sql, []);
    }

    // Obtiene estadísticas de tokens para un usuario
    public function getEstadisticasTokens($idUsuario)
    {
        $sql = "SELECT 
                    COUNT(*) as total_tokens,
                    COUNT(CASE WHEN usado = 1 THEN 1 END) as tokens_usados,
                    COUNT(CASE WHEN fecha_expiracion < NOW() AND usado = 0 THEN 1 END) as tokens_expirados,
                    COUNT(CASE WHEN fecha_expiracion > NOW() AND usado = 0 THEN 1 END) as tokens_activos,
                    MAX(fecha_creacion) as ultimo_token
                FROM tokens_recuperacion 
                WHERE id_usuario = ?";
        
        return $this->select($sql, [$idUsuario]);
    }

    // Verifica si un usuario tiene tokens activos
    public function tieneTokensActivos($idUsuario)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM tokens_recuperacion 
                WHERE id_usuario = ? 
                AND usado = 0 
                AND fecha_expiracion > NOW()";
        
        $resultado = $this->select($sql, [$idUsuario]);
        return $resultado && $resultado['total'] > 0;
    }

    // Método para validar si un usuario puede solicitar un nuevo token (rate limiting)
    public function puedeSolicitarToken($idUsuario, $minutosBloqueado = 5)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM tokens_recuperacion 
                WHERE id_usuario = ? 
                AND fecha_creacion > (NOW() - INTERVAL '{$minutosBloqueado} minutes')";
        
        $resultado = $this->select($sql, [$idUsuario]);
        
        // Si hay menos de 3 solicitudes en los últimos X minutos, puede solicitar
        return $resultado && $resultado['total'] < 3;
    }
}