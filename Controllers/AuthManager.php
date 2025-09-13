<?php
/********************************************
Archivo php AuthManager.php
Creado por el equipo Gaes 1:
Anyi Solayi Tapias
Sharit Delgado Pinzón
Durly Yuranni Sánchez Carillo
Año: 2025
SENA - CSET - ADSO
 ********************************************/
require_once ROOT_PATH . 'vendor/autoload.php';
use Firebase\JWT\JWT;


class AuthManager extends Query {
    private $secret_key;
    private $id_usuario;
    private $correo;
    private $rol;
    private $token_validado = false;
    
    public function __construct($secret_key = SECRET_KEY) {
        parent::__construct(); // Inicializar Query para tener acceso a la BD
        $this->secret_key = $secret_key;
    }
    
    // Extrae el token de diferentes fuentes (headers, POST, cookies, session, GET)
    private function extraerToken() {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return str_replace('Bearer ', '', $headers['Authorization']);
            }
        }
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        }
        
        if (isset($_POST['token']) && !empty($_POST['token'])) {
            return $_POST['token'];
        }
        
        if (isset($_GET['token']) && !empty($_GET['token'])) {
            return $_GET['token'];
        }
        
        if (isset($_COOKIE['token']) && !empty($_COOKIE['token'])) {
            return $_COOKIE['token'];
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
            return $_SESSION['token'];
        }
        
        return null;
    }
    
    // Valida el token JWT y extrae la información del usuario
    public function validarToken() {
        if ($this->token_validado && !empty($this->id_usuario)) {
            return [
                'valido' => true,
                'id_usuario' => $this->id_usuario,
                'correo' => $this->correo,
                'rol' => $this->rol
            ];
        }
        
        $token = $this->extraerToken();
        
        if (empty($token)) {
            return [
                'valido' => false,
                'mensaje' => 'No se proporcionó token de autorización'
            ];
        }
        
        try {
            $decoded = JWT::decode($token, $this->secret_key, array('HS256'));
            
            if (empty($decoded->sub)) {
                return [
                    'valido' => false,
                    'mensaje' => 'Token no contiene un ID de usuario válido'
                ];
            }
            
            if (time() > $decoded->exp) {
                return [
                    'valido' => false,
                    'mensaje' => 'Token ha expirado'
                ];
            }
            
            $this->id_usuario = $decoded->sub;
            $this->correo = $decoded->correo ?? '';
            $this->rol = $decoded->rol ?? null;
            $this->token_validado = true;
            
            return [
                'valido' => true,
                'id_usuario' => $this->id_usuario,
                'correo' => $this->correo,
                'rol' => $this->rol,
                'datos_completos' => $decoded
            ];
            
        } catch (Exception $e) {
            $mensaje_error = $this->procesarErrorJWT($e->getMessage());
            return [
                'valido' => false,
                'mensaje' => $mensaje_error
            ];
        }
    }
    
    // NUEVO MÉTODO: Verificar si el usuario debe cambiar contraseña
    public function verificarCambioClaveRequerido($id_usuario = null)
    {
        $id = $id_usuario ?? $this->id_usuario;
        
        if (empty($id)) {
            return false;
        }
        
        $sql = "SELECT fecha_ultimo_cambio_clave FROM usuarios WHERE id = ? AND estado = 1";
        $usuario = $this->select($sql, [$id]);
        
        // Si no encuentra usuario o fecha_ultimo_cambio_clave es NULL, debe cambiar
        return empty($usuario) || is_null($usuario['fecha_ultimo_cambio_clave']);
    }
    
    // NUEVO MÉTODO: Middleware mejorado que verifica contraseña temporal
    public function middlewareConPasswordCheck($redirigir_automatico = true, $ruta_actual = '')
    {
        $validacion = $this->validarToken();
        
        if (!$validacion['valido']) {
            if ($redirigir_automatico) {
                $this->redirigirNoAutorizado($validacion['mensaje']);
            }
            return $validacion;
        }
        
        // Verificar si necesita cambiar contraseña
        if ($this->verificarCambioClaveRequerido($validacion['id_usuario'])) {
            // Rutas permitidas para usuarios con contraseña temporal
            $rutasPermitidas = [
                'principal/index',
                'principal/cambiarClaveInicial',
                'principal/validar',
                'principal/salir'
            ];
            
            // Si no está en una ruta permitida, redirigir al login
            if (!empty($ruta_actual) && !in_array($ruta_actual, $rutasPermitidas)) {
                if ($redirigir_automatico) {
                    $this->redirigirCambioClaveRequerido();
                }
                return [
                    'valido' => false,
                    'mensaje' => 'Debe cambiar su contraseña temporal antes de continuar',
                    'requiere_cambio_clave' => true
                ];
            }
            
            // Agregar flag de cambio requerido
            $validacion['requiere_cambio_clave'] = true;
        } else {
            $validacion['requiere_cambio_clave'] = false;
        }
        
        return $validacion;
    }
    
    // NUEVO MÉTODO: Redirigir cuando se requiere cambio de contraseña
    private function redirigirCambioClaveRequerido()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Establecer mensaje en sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['requiere_cambio_clave'] = true;
        $_SESSION['mensaje_cambio'] = 'Debe cambiar su contraseña temporal antes de continuar';
        
        header('Location: ' . BASE_URL);
        exit;
    }
    
    // NUEVO MÉTODO: Cambiar contraseña inicial
    public function cambiarClaveInicial($id_usuario, $nueva_clave)
    {
        // Verificar que realmente tenga contraseña temporal
        if (!$this->verificarCambioClaveRequerido($id_usuario)) {
            return false;
        }
        
        $sql = "UPDATE usuarios SET clave = ?, fecha_ultimo_cambio_clave = CURRENT_TIMESTAMP WHERE id = ? AND fecha_ultimo_cambio_clave IS NULL";
        $datos = [$nueva_clave, $id_usuario];
        return $this->save($sql, $datos);
    }
    
    // NUEVO MÉTODO: Obtener usuario con fecha de cambio de clave
    public function getUsuarioConFechaClave($id_usuario)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM usuarios WHERE id = ? AND estado = 1";
        return $this->select($sql, [$id_usuario]);
    }
    
    // Procesa los errores específicos de JWT
    private function procesarErrorJWT($mensaje_original) {
        switch ($mensaje_original) {
            case 'Expired token':
                return 'El token ha expirado';
            case 'Invalid token':
                return 'Token inválido';
            case 'Wrong number of segments':
                return 'Formato de token inválido';
            case 'Invalid signature':
                return 'Firma del token inválida';
            default:
                return 'Error al validar token: ' . $mensaje_original;
        }
    }
    
    // Valida el token y redirige si no es válido
    public function middleware($redirigir_automatico = true) {
        $resultado = $this->validarToken();
        
        if (!$resultado['valido']) {
            if ($redirigir_automatico) {
                $this->redirigirNoAutorizado($resultado['mensaje']);
            } else {
                return $resultado;
            }
        }
        
        return $resultado;
    }
    
    // Redirige al usuario a la página de inicio en caso de no estar autorizado
    public function redirigirNoAutorizado($mensaje = 'Acceso no autorizado') {
        if (ob_get_level()) {
            ob_clean();
        }
        
        error_log("Acceso no autorizado: " . $mensaje . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
        
        header('Location: ' . BASE_URL);
        exit;
    }
    
    // Genera un nuevo token JWT
    public function generarToken($id_usuario, $correo, $tiempo_expiracion = 3600, $rol = null) {
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + $tiempo_expiracion,
            'sub' => $id_usuario,
            'correo' => $correo,
            'rol' => $rol
        ];
        
        return JWT::encode($payload, $this->secret_key, 'HS256');
    }
    
    // Renueva un token existente
    public function renovarToken($tiempo_adicional = 3600) {
        $validacion = $this->validarToken();
        
        if (!$validacion['valido']) {
            return false;
        }
        
        return $this->generarToken(
            $this->id_usuario, 
            $this->correo, 
            $tiempo_adicional,
            $this->rol
        );
    }
    
    // Obtiene el ID del usuario
    public function getIdUsuario() {
        if (!$this->token_validado) {
            $validacion = $this->validarToken();
            if (!$validacion['valido']) {
                return null;
            }
        }
        return $this->id_usuario;
    }
    
    // Obtiene el correo del usuario
    public function getCorreo() {
        if (!$this->token_validado) {
            $validacion = $this->validarToken();
            if (!$validacion['valido']) {
                return null;
            }
        }
        return $this->correo;
    }
    
    // Obtiene el rol del usuario
    public function getRol() {
        if (!$this->token_validado) {
            $validacion = $this->validarToken();
            if (!$validacion['valido']) {
                return null;
            }
        }
        return $this->rol;
    }
    
    // Verifica si el usuario tiene un rol específico
    public function tieneRol($rol) {
        $usuario_rol = $this->getRol();
        
        if ($usuario_rol === null) {
            return false;
        }
        
        return (int)$usuario_rol === (int)$rol;
    }
    
    // Verifica si el usuario está autenticado
    public function estaAutenticado() {
        $validacion = $this->validarToken();
        return $validacion['valido'];
    }
    
    // Obtiene información del token para depuración
    public function debug() {
        $token = $this->extraerToken();
        $validacion = $this->validarToken();
        
        return [
            'token_encontrado' => !empty($token),
            'token_longitud' => strlen($token ?? ''),
            'validacion' => $validacion,
            'fuente_token' => $this->obtenerFuenteToken(),
            'datos_usuario' => [
                'id' => $this->id_usuario,
                'correo' => $this->correo,
                'rol' => $this->rol
            ],
            'requiere_cambio_clave' => $this->verificarCambioClaveRequerido()
        ];
    }
    
    // Determina de dónde se obtuvo el token
    private function obtenerFuenteToken() {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) return 'Authorization Header';
        }
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) return 'HTTP_AUTHORIZATION';
        if (isset($_POST['token'])) return 'POST';
        if (isset($_GET['token'])) return 'GET';
        if (isset($_COOKIE['token'])) return 'Cookie';
        if (isset($_SESSION['token'])) return 'Session';
        
        return 'No encontrado';
    }
}
?>