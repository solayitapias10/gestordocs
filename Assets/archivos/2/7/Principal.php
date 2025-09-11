<?php

/********************************************
Archivo php Principal.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/
require_once './Config/Config.php';
require_once 'vendor/autoload.php';
require_once 'AuthManager.php';


class Principal extends Controller
{
    private $authManager;

    public function __construct()
    {
        parent::__construct();
        $this->authManager = new AuthManager(SECRET_KEY);
    }

    // Muestra la vista principal para iniciar sesión
    public function index()
    {
        $data['title'] = 'Iniciar Sesión';
        $this->views->getView('principal', 'index', $data);
    }

    // Redirecciona a la página de registro
    public function registro()
    {
        $data['title'] = 'Crear Cuenta';
        $this->views->getView('', 'registro', $data);
    }

    // Valida las credenciales del usuario y genera un token JWT
    public function validar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = $_POST['correo'];
            $clave = $_POST['clave'];
            $data = $this->model->getUsuario($correo);

            if (!empty($data)) {
                if (password_verify($clave, $data['clave'])) {
                    // VERIFICAR si necesita cambiar contraseña
                    $requiereCambio = is_null($data['fecha_ultimo_cambio_clave']);

                    $jwt = $this->authManager->generarToken(
                        $data['id'],
                        $data['correo'],
                        3600, // 1 hour expiracion
                        $data['rol'] ?? 2
                    );

                    $res = array(
                        'tipo' => 'success',
                        'mensaje' => $requiereCambio ? 'Debe cambiar su contraseña temporal' : 'Bienvenido al sistema',
                        'token' => $jwt,
                        'expires_at' => time() + 3600,
                        'rol' => $data['rol'] ?? 2,
                        'requiere_cambio_clave' => $requiereCambio, // CAMPO CRÍTICO
                        'usuario_id' => $data['id'] // Información adicional
                    );

                    // Log para debug
                    error_log("Login - Usuario: {$data['correo']}, Requiere cambio: " . ($requiereCambio ? 'SÍ' : 'NO') . ", Fecha último cambio: " . ($data['fecha_ultimo_cambio_clave'] ?? 'NULL'));
                } else {
                    $res = array('tipo' => 'warning', 'mensaje' => 'Contraseña incorrecta');
                }
            } else {
                $res = array('tipo' => 'warning', 'mensaje' => 'El correo no existe');
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }


    //Cambiar clave si es usuario nuevo

    public function cambiarClaveInicial()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token
            $validacion = $this->authManager->validarToken();
            if (!$validacion['valido']) {
                $res = array('tipo' => 'error', 'mensaje' => 'Sesión inválida');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $claveActual = $_POST['claveActual'];
            $claveNueva = $_POST['claveNueva'];
            $claveConfirmar = $_POST['claveConfirmar'];

            // Validaciones
            if (empty($claveActual) || empty($claveNueva) || empty($claveConfirmar)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Todos los campos son requeridos');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if ($claveNueva !== $claveConfirmar) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Las contraseñas nuevas no coinciden');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (strlen($claveNueva) < 8) {
                $res = array('tipo' => 'warning', 'mensaje' => 'La nueva contraseña debe tener al menos 8 caracteres');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Verificar contraseña actual
            $usuario = $this->model->getUsuarioPorId($validacion['id_usuario']);
            if (!password_verify($claveActual, $usuario['clave'])) {
                $res = array('tipo' => 'warning', 'mensaje' => 'La contraseña actual es incorrecta');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Verificar que realmente necesite cambiar contraseña
            if (!is_null($usuario['fecha_ultimo_cambio_clave'])) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Su contraseña ya fue actualizada previamente');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Actualizar contraseña
            $hashNueva = password_hash($claveNueva, PASSWORD_DEFAULT);
            $resultado = $this->model->cambiarClaveInicial($validacion['id_usuario'], $hashNueva);

            if ($resultado) {
                $res = array(
                    'tipo' => 'success',
                    'mensaje' => 'Contraseña actualizada correctamente. Puede continuar usando el sistema.'
                );
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'Error al actualizar la contraseña');
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Renueva el token de autenticación del usuario
    public function renovarToken()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newToken = $this->authManager->renovarToken(3600);

            if ($newToken) {
                $res = array(
                    'tipo' => 'success',
                    'mensaje' => 'Token renovado',
                    'token' => $newToken,
                    'expires_at' => time() + 3600
                );
            } else {
                $validacion = $this->authManager->validarToken();
                $res = array(
                    'tipo' => 'error',
                    'mensaje' => $validacion['mensaje']
                );
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Registra un nuevo usuario en el sistema
    public function registrar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $direccion = $_POST['direccion'];

            if (
                empty($nombre) || empty($apellido) || empty($correo) ||
                empty($telefono) || empty($direccion)
            ) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Todos los campos son requeridos');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Formato de correo electrónico inválido');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!preg_match('/^\d{7,15}$/', $telefono)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'El teléfono debe contener solo números (entre 7 y 15 dígitos)');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $existeUsuario = $this->model->getUsuario($correo);
            if (!empty($existeUsuario)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'El correo ya está registrado');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $resultado = $this->model->registrarSolicitud($nombre, $apellido, $correo, $telefono, $direccion);
                if ($resultado && $resultado['success']) {
                    error_log("Registro de solicitud exitoso - Usuario: $correo, ID Solicitud: " . $resultado['id_solicitud']);

                    $res = array(
                        'tipo' => 'success',
                        'mensaje' => $resultado['mensaje'], // Mensaje más claro del PostgreSQL
                        'id_solicitud' => $resultado['id_solicitud']
                        // ELIMINADO: 'clave_temporal' => $claveGenerada
                    );
                } else {
                    $mensaje_error = $resultado['mensaje'] ?? 'Error al enviar la solicitud';
                    $res = array('tipo' => 'warning', 'mensaje' => $mensaje_error);
                }
            } catch (Exception $e) {
                error_log("Error al procesar solicitud de registro: " . $e->getMessage());
                $res = array('tipo' => 'error', 'mensaje' => 'Error interno al procesar la solicitud');
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Cierra la sesión del usuario
    public function salir()
    {
        $res = array('tipo' => 'success', 'mensaje' => 'Sesión cerrada');
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time() - 1000);
                setcookie($name, '', time() - 1000, '/');
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        header('Location: ' . BASE_URL);
        die();
    }
}
