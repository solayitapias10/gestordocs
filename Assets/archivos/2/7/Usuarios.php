<?php

/********************************************
Archivo php Usuarios.php                         
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

class Usuarios extends Controller
{
    private $id_usuario, $correo;
    private $authManager;

    public function __construct()
    {
        parent::__construct();
        $this->authManager = new AuthManager(SECRET_KEY);
        $this->validarToken();
    }

    // Muestra la página de gestión de usuarios
    public function index()
    {
        $data['title'] = 'Gestión de Usuarios';
        $data['script'] = 'usuarios.js';
        $data['menu'] = 'usuarios';
        $data['active'] = 'usuarios';
        $data['shares'] = $this->model->verificarEstado($this->correo);
        $data['user'] = [
            'nombre' => $this->getNombreUsuario(),
            'correo' => $this->correo,
            'avatar' => $this->getAvatarUsuario(),
            'rol' => $this->getRolUsuario()
        ];
        $this->views->getView('usuarios', 'index', $data);
    }

    // Obtiene el nombre completo del usuario
    private function getNombreUsuario()
    {
        $usuario = $this->model->getUsuario($this->id_usuario);
        return $usuario['nombre'] . ' ' . $usuario['apellido'];
    }

    // Obtiene la ruta del avatar del usuario
    private function getAvatarUsuario()
    {
        $usuario = $this->model->getUsuario($this->id_usuario);
        $avatarPath = (!empty($usuario['avatar']) && $usuario['avatar'] !== 'Assets/images/avatar.jpg')
            ? BASE_URL . $usuario['avatar']
            : BASE_URL . 'Assets/images/avatar.jpg';

        return $avatarPath;
    }

    // Obtiene el rol del usuario
    private function getRolUsuario()
    {
        $usuario = $this->model->getUsuario($this->id_usuario);
        return $usuario['rol'];
    }

    // Obtiene y lista todos los usuarios en formato JSON
    public function listar()
    {
        $data = $this->model->getUsuarios();
        $usuarios = [];
        foreach ($data as $usuario) {
            $registro = [
                'id' => $usuario['id'],
                'nombres' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                'correo' => $usuario['correo'],
                'telefono' => $usuario['telefono'],
                'direccion' => $usuario['direccion'],
                'fecha' => $usuario['fecha'],
                'acciones' => ($usuario['id'] == 1)
                    ? 'Super Admin'
                    : '<div>
    <a href="#" class="btn btn-primary btn-style-light btn-sm" onclick="editar(' . $usuario['id'] . ')">
        <span class="material-icons">edit</span>
    </a>' .
                    ($usuario['estado'] == 1 ?
                        '<a href="#" class="btn btn-success btn-style-light btn-sm" onclick="eliminar(' . $usuario['id'] . ', 1)" title="Usuario Activo">
            <span class="material-icons">check_circle</span>
        </a>' :
                        '<a href="#" class="btn btn-danger btn-style-light btn-sm" onclick="eliminar(' . $usuario['id'] . ', 0)" title="Usuario Inactivo">
            <span class="material-icons">block</span>
        </a>'
                    ) . '</div>'
            ];
            $usuarios[] = $registro;
        }
        echo json_encode($usuarios, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Guarda un nuevo usuario o modifica uno existente
    public function guardar()
    {
        try {
            $nombre = $_POST['nombre'] ?? '';
            $apellido = $_POST['apellido'] ?? '';
            $correo = $_POST['correo'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $direccion = $_POST['direccion'] ?? '';
            $clave = $_POST['clave'] ?? '';
            $rol = $_POST['rol'] ?? '';
            $id_usuario = $_POST['id_usuario'] ?? '';

            if (empty($nombre) || empty($apellido) || empty($correo) || empty($telefono) || empty($direccion) || empty($rol)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Todos los campos son requeridos');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if ($id_usuario == '') {
                // Nuevo usuario
                if (empty($clave)) {
                    $res = array('tipo' => 'warning', 'mensaje' => 'La contraseña es requerida para usuarios nuevos');
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    die();
                }

                try {
                    $verificarCorreo = $this->model->getVerificar('correo', $correo, 0);
                    if ($verificarCorreo && isset($verificarCorreo['existe']) && $verificarCorreo['existe']) {
                        $res = array('tipo' => 'warning', 'mensaje' => $verificarCorreo['mensaje']);
                        echo json_encode($res, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $verificarTelefono = $this->model->getVerificar('telefono', $telefono, 0);
                    if ($verificarTelefono && isset($verificarTelefono['existe']) && $verificarTelefono['existe']) {
                        $res = array('tipo' => 'warning', 'mensaje' => $verificarTelefono['mensaje']);
                        echo json_encode($res, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $data = $this->model->registrar($nombre, $apellido, $correo, $telefono, $direccion, $hash, $rol);

                    if ($data && $data > 0) {
                        $res = array('tipo' => 'success', 'mensaje' => 'Usuario Registrado');
                    } else {
                        $res = array('tipo' => 'error', 'mensaje' => 'Error Al Registrar');
                    }
                } catch (Exception $e) {
                    error_log("Error al registrar usuario: " . $e->getMessage());
                    $res = array('tipo' => 'error', 'mensaje' => $e->getMessage());
                }
            } else {
                // Modificar usuario existente
                try {
                    $verificarCorreo = $this->model->getVerificar('correo', $correo, $id_usuario);
                    if ($verificarCorreo && isset($verificarCorreo['existe']) && $verificarCorreo['existe']) {
                        $res = array('tipo' => 'warning', 'mensaje' => $verificarCorreo['mensaje']);
                        echo json_encode($res, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $verificarTelefono = $this->model->getVerificar('telefono', $telefono, $id_usuario);
                    if ($verificarTelefono && isset($verificarTelefono['existe']) && $verificarTelefono['existe']) {
                        $res = array('tipo' => 'warning', 'mensaje' => $verificarTelefono['mensaje']);
                        echo json_encode($res, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $data = $this->model->modificar($nombre, $apellido, $correo, $telefono, $direccion, $rol, $id_usuario);
                    if ($data == 1) {
                        $res = array('tipo' => 'success', 'mensaje' => 'Usuario Modificado');
                    } else {
                        $res = array('tipo' => 'error', 'mensaje' => 'Error Al Modificar');
                    }
                } catch (Exception $e) {
                    error_log("Error al modificar usuario: " . $e->getMessage());
                    $res = array('tipo' => 'error', 'mensaje' => $e->getMessage());
                }
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        } catch (Exception $e) {
            error_log("Error general en guardar(): " . $e->getMessage());
            $res = array('tipo' => 'error', 'mensaje' => 'Error interno del sistema');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Desactiva o activa un usuario cambiando su estado
    public function delete($id)
    {
        $usuario = $this->model->getUsuario($id);
        $estadoActual = $usuario['estado'];

        $data = $this->model->delete($id);

        if ($data == 1) {
            if ($estadoActual == 1) {
                $res = array('tipo' => 'success', 'mensaje' => 'El usuario ha sido desactivado correctamente');
            } else {
                $res = array('tipo' => 'success', 'mensaje' => 'El usuario ha sido activado correctamente');
            }
        } else {
            if ($estadoActual == 1) {
                $res = array('tipo' => 'error', 'mensaje' => 'Error al desactivar el usuario');
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'Error al activar el usuario');
            }
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Busca y devuelve los datos de un usuario para su edición
    public function editar($id)
    {
        $data = $this->model->getUsuario($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Muestra la página de perfil del usuario
    public function perfil()
    {
        $data['title'] = 'Configuración';
        $data['script'] = 'perfil.js';
        $data['menu'] = 'perfil';
        $data['shares'] = $this->model->verificarEstado($this->correo);

        $data['usuario'] = $this->model->getUsuario($this->id_usuario);
        $data['user'] = [
            'nombre' => $this->getNombreUsuario(),
            'correo' => $this->correo,
            'avatar' => $this->getAvatarUsuario(),
            'rol' => $this->getRolUsuario()
        ];

        $this->views->getView('usuarios', 'perfil', $data);
    }

    // Devuelve los datos del perfil del usuario actual en formato JSON
    public function miPerfil()
    {
        if (empty($this->id_usuario)) {
            echo json_encode(array(
                'error' => true,
                'mensaje' => 'La sesión ha expirado. Por favor, inicie sesión nuevamente.'
            ), JSON_UNESCAPED_UNICODE);
            die();
        }

        $data = $this->model->getUsuario($this->id_usuario);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Actualiza los datos del perfil del usuario
    public function actualizarPerfil()
    {
        try {
            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $direccion = $_POST['direccion'];

            if (empty($nombre) || empty($apellido) || empty($correo) || empty($telefono) || empty($direccion)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Todos los campos son requeridos');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Remover las validaciones de correo y teléfono ya que ahora las maneja el procedimiento almacenado
            $data = $this->model->actualizarPerfil($nombre, $apellido, $correo, $telefono, $direccion, $id);

            if ($data == 1) {
                $this->correo = $correo;
                $res = array('tipo' => 'success', 'mensaje' => 'Perfil actualizado correctamente');
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'Error al actualizar el perfil');
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        } catch (Exception $e) {
            error_log("Error en actualizarPerfil(): " . $e->getMessage());
            $res = array('tipo' => 'error', 'mensaje' => $e->getMessage());
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Cambia la contraseña del usuario actual
    public function cambiarClave()
    {
        try {
            $claveActual = $_POST['claveActual'];
            $claveNueva = $_POST['claveNueva'];
            $claveConfirmar = $_POST['claveConfirmar'];

            if (empty($claveActual) || empty($claveNueva) || empty($claveConfirmar)) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Todos los campos son requeridos');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if ($claveNueva != $claveConfirmar) {
                $res = array('tipo' => 'warning', 'mensaje' => 'Las contraseñas no coinciden');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $usuario = $this->model->getUsuario($this->id_usuario);
            if (!password_verify($claveActual, $usuario['clave'])) {
                $res = array('tipo' => 'warning', 'mensaje' => 'La contraseña actual es incorrecta');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
            $data = $this->model->cambiarClave($hash, $this->id_usuario);

            if ($data == 1) {
                $res = array('tipo' => 'success', 'mensaje' => 'Contraseña actualizada correctamente');
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'Error al actualizar la contraseña');
            }

            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        } catch (Exception $e) {
            error_log("Error en cambiarClave(): " . $e->getMessage());
            $res = array('tipo' => 'error', 'mensaje' => $e->getMessage());
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Cambia la foto del perfil del usuario
    public function cambiarAvatar()
    {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $uploadDir = 'Assets/images/avatars/';
            $absoluteUploadDir = dirname(dirname(__FILE__)) . '/' . $uploadDir;

            if (!file_exists($absoluteUploadDir)) {
                if (!mkdir($absoluteUploadDir, 0777, true)) {
                    $res = array('tipo' => 'error', 'mensaje' => 'Error al crear el directorio para avatars.');
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $fileInfo = pathinfo($_FILES['avatar']['name']);
            $extension = strtolower($fileInfo['extension']);

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($extension, $allowedExtensions)) {
                $res = array('tipo' => 'error', 'mensaje' => 'Formato de archivo no permitido. Solo se aceptan JPG, JPEG, PNG y GIF.');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            if ($_FILES['avatar']['size'] > 2097152) {
                $res = array('tipo' => 'error', 'mensaje' => 'El archivo es demasiado grande. Tamaño máximo: 2MB.');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $filename = 'avatar_' . $this->id_usuario . '_' . time() . '.' . $extension;
            $filepath = $absoluteUploadDir . $filename;

            $dbFilepath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
                $resultado = $this->model->actualizarAvatar($this->id_usuario, $dbFilepath);

                if ($resultado == 1) {  
                    $res = array(
                        'tipo' => 'success',
                        'mensaje' => 'Avatar actualizado correctamente.',
                        'avatar' => BASE_URL . $dbFilepath
                    );
                } else {
                    $res = array('tipo' => 'error', 'mensaje' => 'Error al actualizar la información en la base de datos.');
                }
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'Error al subir el archivo. Inténtalo de nuevo.');
            }
        } else {
            $res = array('tipo' => 'error', 'mensaje' => 'No se ha seleccionado ningún archivo o ha ocurrido un error.');
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }


    // Muestra la página de solicitudes de usuarios para administradores
    public function solicitudes()
    {
        $data['title'] = 'Solicitudes de Usuarios';
        $data['script'] = 'solicitudes.js';
        $data['active'] = 'solicitudes';
        $data['menu'] = 'solicitudes';
        $data['user'] = $this->model->getUsuario($this->id_usuario);
        if (!isset($data['user']['rol']) || $data['user']['rol'] != 1) {
            header('Location: ' . BASE_URL);
            exit();
        }
        $data['solicitudes'] = $this->model->getUsuariosSolicitados();
        $data['shares'] = $this->model->verificarEstado($this->correo);
        $this->views->getView('usuarios', 'solicitudes', $data);
    }

    // Lista las solicitudes de usuarios en formato JSON
    public function listarSolicitudes()
    {
        $data = $this->model->getUsuariosSolicitados();
        $usuarios = [];
        foreach ($data as $solicitud) {
            $registro = [
                'id' => $solicitud['id'],
                'nombres' => $solicitud['nombre'] . ' ' . $solicitud['apellido'],
                'correo' => $solicitud['correo'],
                'telefono' => $solicitud['telefono'],
                'direccion' => $solicitud['direccion'],
                'fecha' => $solicitud['fecha'],
                'acciones' => '<div>
                <a href="#" class="btn btn-success btn-style-light btn-sm" onclick="aprobar(' . $solicitud['id'] . ')" title="Aprobar Solicitud">
                    <span class="material-icons">check_circle</span>
                </a>
                <a href="#" class="btn btn-danger btn-style-light btn-sm" onclick="rechazar(' . $solicitud['id'] . ')" title="Rechazar Solicitud">
                    <span class="material-icons">cancel</span>
                </a>
            </div>'
            ];
            $usuarios[] = $registro;
        }
        echo json_encode($usuarios, JSON_UNESCAPED_UNICODE);
        die();
    }


    // Aprueba una solicitud de usuario y lo registra
    public function aprobar($id)
    {
        // Verificar permisos del usuario actual
        $usuario_actual = $this->model->getUsuario($this->id_usuario);
        if (!isset($usuario_actual['rol']) || $usuario_actual['rol'] != 1) {
            $res = array(
                'tipo' => 'error',
                'mensaje' => 'No tienes permisos para realizar esta acción'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar ID de solicitud
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            $res = array(
                'tipo' => 'error',
                'mensaje' => 'ID de solicitud inválido'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Verificar que la solicitud existe y está pendiente
        $solicitudExistente = $this->model->verificarSolicitudPendiente($id);
        if (!$solicitudExistente) {
            $res = array(
                'tipo' => 'warning',
                'mensaje' => 'La solicitud no existe o ya ha sido procesada'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Llamar al modelo para aprobar la solicitud
        $resultado = $this->model->aprobarSolicitud($id, $this->id_usuario);

        // Procesar resultado
        if ($resultado['success']) {
            $res = array(
                'tipo' => 'success',
                'mensaje' => $resultado['mensaje']
            );

            // Log adicional para auditoria
            error_log("Solicitud aprobada - ID: {$id}, Admin: {$this->id_usuario}, Usuario creado: {$resultado['id_usuario_creado']}");
        } else {
            $res = array(
                'tipo' => 'error',
                'mensaje' => $resultado['mensaje']
            );

            // Log del error
            error_log("Error al aprobar solicitud - ID: {$id}, Admin: {$this->id_usuario}, Error: {$resultado['mensaje']}");
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }
    // Rechaza una solicitud de usuario
    public function rechazar($id)
    {
        // Verificar permisos del usuario actual
        $usuario_actual = $this->model->getUsuario($this->id_usuario);
        if (!isset($usuario_actual['rol']) || $usuario_actual['rol'] != 1) {
            $res = array(
                'tipo' => 'error',
                'mensaje' => 'No tienes permisos para realizar esta acción'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar ID de solicitud
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            $res = array(
                'tipo' => 'error',
                'mensaje' => 'ID de solicitud inválido'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Verificar que la solicitud existe y está pendiente
        $solicitudExistente = $this->model->verificarSolicitudPendiente($id);
        if (!$solicitudExistente) {
            $res = array(
                'tipo' => 'warning',
                'mensaje' => 'La solicitud no existe o ya ha sido procesada'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Llamar al modelo para rechazar la solicitud
        $resultado = $this->model->rechazarSolicitud($id, $this->id_usuario);

        // Procesar resultado
        if ($resultado['success']) {
            $res = array(
                'tipo' => 'success',
                'mensaje' => $resultado['mensaje']
            );

            // Log adicional para auditoria
            error_log("Solicitud rechazada - ID: {$id}, Admin: {$this->id_usuario}");
        } else {
            $res = array(
                'tipo' => 'error',
                'mensaje' => $resultado['mensaje']
            );

            // Log del error
            error_log("Error al rechazar solicitud - ID: {$id}, Admin: {$this->id_usuario}, Error: {$resultado['mensaje']}");
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Valida el token JWT del usuario para autenticación
    private function validarToken()
    {
        $resultado = $this->authManager->middleware(true);
        if ($resultado['valido']) {
            $this->id_usuario = $resultado['id_usuario'];
            $this->correo = $resultado['correo'];
        }
    }
}
