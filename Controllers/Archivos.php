<?php

/********************************************
Archivo php Archivos.php
Creado por el equipo Gaes 1:
Anyi Solayi Tapias
Sharit Delgado Pinzón
Durly Yuranni Sánchez Carillo
Año: 2025
SENA - CSET - ADSO
 ********************************************/
require_once ROOT_PATH . 'Config/Config.php';
require_once ROOT_PATH . 'Config/Functions.php';
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'Controllers/AuthManager.php';

class Archivos extends Controller
{
    private $id_usuario, $correo, $rol;
    private $authManager;

    public function __construct()
    {
        parent::__construct();
        $this->authManager = new AuthManager(SECRET_KEY);
        $this->validarToken();
    }

    // Muestra la vista principal de archivos y carpetas del usuario.
public function index()
    {
        // 1. Definir variables para la plantilla
        $data['title'] = 'Documentos';
        $data['script'] = 'files.js';
        $data['active'] = 'archivos'; // Para resaltar el menú correcto
        $data['menu'] = 'archivos';
        
        // Se asume que $this->id_usuario y $this->correo ya están disponibles
        $data['user'] = $this->model->getUsuario($this->id_usuario);
        $data['shares'] = $this->model->verificarEstado($this->correo);

        // 2. Configuración de ambas paginaciones
        $page_carpetas = isset($_GET['page_carpetas']) ? max(1, intval($_GET['page_carpetas'])) : 1;
        $page_archivos = isset($_GET['page_archivos']) ? max(1, intval($_GET['page_archivos'])) : 1;
        $limit_carpetas = 6;
        $limit_archivos = 6;

        // 3. Obtener datos paginados
        $carpetas = $this->model->getCarpetasPaginado($this->id_usuario, $page_carpetas, $limit_carpetas);
        $total_carpetas = $this->model->getTotalCarpetas($this->id_usuario);
        $archivos = $this->model->getArchivosPaginado($this->id_usuario, $page_archivos, $limit_archivos);
        $total_archivos = $this->model->getTotalArchivos($this->id_usuario);

        // 4. Procesar los datos (¡ESTE PASO ES CLAVE!)
        for ($i = 0; $i < count($carpetas); $i++) {
            $carpetas[$i]['color'] = substr(md5($carpetas[$i]['id']), 0, 6);
            $carpetas[$i]['fecha'] = time_ago(strtotime($carpetas[$i]['fecha_create']));
        }

        for ($i = 0; $i < count($archivos); $i++) {
            $archivos[$i]['color'] = substr(md5($archivos[$i]['id']), 0, 6);
            $archivos[$i]['fecha'] = time_ago(strtotime($archivos[$i]['fecha_create']));
            $archivos[$i]['tamano_formateado'] = formatBytes($archivos[$i]['tamano']);
        }

        // 5. Preparar estructuras de paginación y datos finales para la vista
        $data['carpetas'] = $carpetas;
        $data['pagination_carpetas'] = [
            'current_page' => $page_carpetas,
            'total_pages' => ceil($total_carpetas / $limit_carpetas),
            'param_name' => 'page_carpetas'
        ];

        $data['archivos'] = $archivos;
        $data['pagination_archivos'] = [
            'current_page' => $page_archivos, 
            'total_pages' => ceil($total_archivos / $limit_archivos),
            'param_name' => 'page_archivos'
        ];
        
        // 6. Cargar la vista
        $this->views->getView('archivos', 'index', $data);
    }
    // Valida un token JWT para autenticar al usuario.
    private function validarToken()
    {
        $resultado = $this->authManager->middleware(true);

        if ($resultado['valido']) {
            $this->id_usuario = $resultado['id_usuario'];
            $this->correo = $resultado['correo'];

            if (isset($resultado['datos_completos']->rol)) {
                $this->rol = (int) $resultado['datos_completos']->rol;
            } else {
                $usuario = $this->model->getUsuario($this->id_usuario);
                $this->rol = isset($usuario['rol']) ? (int) $usuario['rol'] : 0;
            }
        }
    }

    // Busca usuarios para compartir archivos.
    public function getUsuarios($valor = null)
    {
        if ($valor === null) {
            $valor = isset($_GET['q']) ? $_GET['q'] : '';
        }

        $data = $this->model->getUsuarios($valor, $this->id_usuario);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['text'] = $data[$i]['correo'];
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Comparte archivos con los usuarios seleccionados.
    public function compartir()
    {
        $usuarios = $_POST['usuarios'];
        if (empty($_POST['archivos'])) {
            $res = array('tipo' => 'warning', 'mensaje' => 'Seleccione un archivo');
        } else {
            $archivos = $_POST['archivos'];
            $res = 0;
            for ($i = 0; $i < count($archivos); $i++) {
                for ($j = 0; $j < count($usuarios); $j++) {
                    $dato = $this->model->getUsuario($usuarios[$j]);
                    if (empty($result) || empty($result['id'])) {
                        $result = $this->model->getDetalle($dato['correo'], $archivos[$i]);

                        if (empty($result)) {
                            $res = $this->model->registrarDetalle($dato['correo'], $archivos[$i], $this->id_usuario);
                        } else {
                            $res = 1;
                        }
                    }
                }
            }
            if ($res > 0) {
                $res = array('tipo' => 'success', 'mensaje' => 'Archivos Compartidos con Éxito');
            } else {
                $res = array('tipo' => 'warning', 'mensaje' => 'Error al compartir');
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Muestra los archivos dentro de una carpeta específica.
    public function verArchivos($id_carpeta)
    {
        if (!is_numeric($id_carpeta) || $id_carpeta <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['tipo' => 'error', 'mensaje' => 'ID de carpeta inválido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $data = $this->model->getArchivosCarpeta($id_carpeta);
        header('Content-Type: application/json');
        echo json_encode(['tipo' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
        die();
    }

    // Busca una carpeta o archivo por su ID y devuelve su ID de carpeta.
    public function buscarCarpeta($id)
    {
        ob_start();
        try {
            if (!is_numeric($id) || $id <= 0) {
                throw new Exception("ID de archivo/carpeta inválido");
            }
            $archivo = $this->model->getArchivo($id, $this->id_usuario);
            if (!empty($archivo)) {
                $response = ['tipo' => 'success', 'id_carpeta' => $archivo['id_carpeta'] ?? null];
            } else {
                $data = $this->model->getCarpeta($id);
                if (!empty($data)) {
                    $response = ['tipo' => 'success', 'id_carpeta' => $data['id'] ?? null];
                } else {
                    $response = ['tipo' => 'error', 'mensaje' => 'Archivo o carpeta no encontrado', 'id_carpeta' => null];
                }
            }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['tipo' => 'error', 'mensaje' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Elimina un archivo, marcándolo para ser borrado permanentemente en 30 días.
    public function eliminar($id)
    {
        $fecha = date('Y-m-d H:i:s');
        $nueva = date("Y-m-d H:i:s", strtotime($fecha . '+30 days'));

        $data = $this->model->eliminar($nueva, $id);
        if ($data == 1) {
            $res = array('tipo' => 'success', 'mensaje' => 'Archivo dado de baja');
        } else {
            $res = array('tipo' => 'error', 'mensaje' => 'Error Al eliminar');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Elimina un archivo compartido, marcándolo para ser borrado permanentemente en 30 días.
    public function eliminarCompartido($id)
    {
        $fecha = date('Y-m-d H:i:s');
        $nueva = date("Y-m-d H:i:s", strtotime($fecha . '+30 days'));

        $data = $this->model->eliminarCompartido($nueva, $id);
        if ($data == 1) {
            $res = array('tipo' => 'success', 'mensaje' => 'Archivo dado de baja');
        } else {
            $res = array('tipo' => 'error', 'mensaje' => 'Error Al eliminar');
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Busca archivos que coincidan con un valor de búsqueda.
    public function busqueda($valor)
    {
        $data = $this->model->getBusqueda($valor, $this->id_usuario);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Obtiene el nombre completo del usuario actual.
    private function getNombreUsuario()
    {
        $usuario = $this->model->getUsuario($this->id_usuario);
        $nombre = isset($usuario['nombre']) ? $usuario['nombre'] : '';
        $apellido = isset($usuario['apellido']) ? $usuario['apellido'] : '';
        return $nombre . ' ' . $apellido;
    }

    // Obtiene la URL del avatar del usuario actual.
    private function getAvatarUsuario()
    {
        $usuario = $this->model->getUsuario($this->id_usuario);
        return !empty($usuario['avatar']) ? $usuario['avatar'] : BASE_URL . 'Assets/images/avatar.jpg';
    }

    // Obtiene el rol del usuario actual.
    private function getRolUsuario()
    {
        if (empty($this->rol)) {
            $usuario = $this->model->getUsuario($this->id_usuario);
            return isset($usuario['rol']) ? (int) $usuario['rol'] : 0;
        }
        return (int) $this->rol;
    }

    // Obtiene la información de un archivo específico por su ID.
    public function obtenerArchivo($id)
    {
        $archivo = $this->model->getArchivo($id, $this->id_usuario);
        if (!empty($archivo)) {
            $url = BASE_URL . 'Assets/archivos/' . $this->id_usuario . '/' .
                ($archivo['id_carpeta'] ? $archivo['id_carpeta'] . '/' : '') . $archivo['nombre'];

            $res = [
                'tipo' => 'success',
                'mensaje' => 'Archivo encontrado',
                'archivo' => [
                    'nombre' => $archivo['nombre'],
                    'tipo' => $archivo['tipo'],
                    'url' => $url
                ]
            ];
        } else {
            $res = [
                'tipo' => 'error',
                'mensaje' => 'Archivo no encontrado o no tienes permiso'
            ];
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Elimina una carpeta, marcándola para ser borrada permanentemente en 30 días.
    public function eliminarCarpeta($id)
    {
        if (empty($id)) {
            $res = array('tipo' => 'warning', 'mensaje' => 'ID de carpeta no proporcionado');
        } else {
            $carpeta = $this->model->getCarpeta($id);
            if ($carpeta && $carpeta['id_usuario'] == $this->id_usuario) {
                $data = $this->model->eliminarCarpeta($id, $this->id_usuario);
                if ($data > 0) {
                    $this->model->registrarNotificacion($this->id_usuario, $id, $carpeta['nombre'], 'ELIMINADA');
                    $res = array('tipo' => 'success', 'mensaje' => 'Carpeta eliminada. Se ocultará permanentemente en 30 días.');
                } else {
                    $res = array('tipo' => 'error', 'mensaje' => 'Error al eliminar la carpeta');
                }
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'No tienes permiso para eliminar esta carpeta');
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Muestra la vista de la papelera con los archivos y carpetas eliminados.
    public function papelera()
    {
        $data['title'] = 'Papelera';
        $data['script'] = 'files.js';
        $data['active'] = 'papelera';
        $data['menu'] = 'papelera';
        $usuario = $this->model->getUsuario($this->id_usuario);
        if (empty($usuario)) {
            die("Error: No se pudo obtener el usuario con ID: " . $this->id_usuario);
        }
        $data['user'] = [
            'nombre' => trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? '')),
            'correo' => $usuario['correo'] ?? $this->correo,
            'avatar' => !empty($usuario['avatar']) ? $usuario['avatar'] : BASE_URL . 'Assets/images/avatar.jpg',
            'rol' => $usuario['rol'] ?? $this->rol ?? 'usuario'
        ];
        $carpetas = $this->model->getPapeleraCarpetas($this->id_usuario);
        $archivos = $this->model->getPapeleraArchivos($this->id_usuario);
        if (!is_array($carpetas)) {
            die("Error: getPapeleraCarpetas() no devolvió un array. Resultado: " . var_export($carpetas, true));
        }
        if (!is_array($archivos)) {
            die("Error: getPapeleraArchivos() no devolvió un array. Resultado: " . var_export($archivos, true));
        }

        foreach ($carpetas as &$carpeta) {
            $carpeta['fecha'] = time_ago(strtotime($carpeta['elimina']));
            $carpeta['color'] = substr(md5($carpeta['id']), 0, 6);
        }
        foreach ($archivos as &$archivo) {
            $archivo['fecha'] = time_ago(strtotime($archivo['elimina']));
            $archivo['tamano_formateado'] = formatBytes($archivo['tamano']);
        }

        $data['carpetas'] = $carpetas;
        $data['archivos'] = $archivos;
        $data['shares'] = $this->model->verificarEstado($data['user']['correo']);

        $this->views->getView('archivos', 'papelera', $data);
    }

    // Restaura un archivo o carpeta desde la papelera.
    public function restaurar($id, $tipo)
    {
        ob_start();

        if (empty($id) || !is_numeric($id) || $id <= 0) {
            $res = ['tipo' => 'warning', 'mensaje' => 'ID no válido'];
        } elseif (empty($tipo) || !in_array($tipo, ['carpeta', 'archivo'])) {
            $res = ['tipo' => 'warning', 'mensaje' => 'Tipo de elemento no válido'];
        } else {
            try {
                if ($tipo == 'carpeta') {
                    // Usar la versión segura que valida la propiedad
                    $data = $this->model->restaurarCarpeta($id, $this->id_usuario);
                    $mensaje_exito = 'Carpeta restaurada con éxito';
                    $mensaje_error = 'Error al restaurar la carpeta';
                } else { // archivo
                    // Usar la versión segura que valida la propiedad
                    $data = $this->model->restaurarArchivo($id, $this->id_usuario);
                    $mensaje_exito = 'Archivo restaurado con éxito';
                    $mensaje_error = 'Error al restaurar el archivo';
                }

                if ($data > 0) {
                    $res = ['tipo' => 'success', 'mensaje' => $mensaje_exito];
                } else {
                    $res = ['tipo' => 'error', 'mensaje' => $mensaje_error];
                }
            } catch (Exception $e) {
                error_log("Error al restaurar $tipo ID: $id, Usuario: {$this->id_usuario}. Error: " . $e->getMessage());

                // Mensajes de error más específicos basados en la excepción
                if (strpos($e->getMessage(), 'no pertenece al usuario') !== false) {
                    $mensaje = "No tienes permiso para restaurar este $tipo";
                } elseif (strpos($e->getMessage(), 'ya está activ') !== false) {
                    $mensaje = "El $tipo ya está activo (no está en la papelera)";
                } else {
                    $mensaje = "Error al restaurar el $tipo. Verifica que esté en la papelera";
                }

                $res = ['tipo' => 'error', 'mensaje' => $mensaje];
            }
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Elimina permanentemente un archivo o carpeta de la papelera.
    public function eliminarPermanente($id, $tipo)
    {
        ob_start();

        if (empty($id) || !is_numeric($id) || $id <= 0) {
            $res = ['tipo' => 'warning', 'mensaje' => 'ID no válido'];
        } elseif (empty($tipo) || !in_array($tipo, ['carpeta', 'archivo'])) {
            $res = ['tipo' => 'warning', 'mensaje' => 'Tipo de elemento no válido'];
        } else {
            try {
                if ($tipo == 'carpeta') {
                    // Para carpetas: llamar la función con ambos parámetros
                    $data = $this->model->eliminarCarpetaPermanente($id, $this->id_usuario);
                    if ($data > 0) {
                        $res = ['tipo' => 'success', 'mensaje' => 'Carpeta eliminada permanentemente'];
                    } else {
                        $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar la carpeta o no tienes permiso'];
                    }
                } elseif ($tipo == 'archivo') {
                    // Para archivos: llamar la función con ambos parámetros
                    $data = $this->model->eliminarArchivoPermanente($id, $this->id_usuario);
                    if ($data > 0) {
                        $res = ['tipo' => 'success', 'mensaje' => 'Archivo eliminado permanentemente'];
                    } else {
                        $res = ['tipo' => 'error', 'mensaje' => 'Error al eliminar el archivo o no tienes permiso'];
                    }
                }
            } catch (Exception $e) {
                error_log("Error al eliminar permanentemente $tipo ID: $id, Usuario: {$this->id_usuario}. Error: " . $e->getMessage());

                // Mensajes más específicos basados en la excepción
                if (strpos($e->getMessage(), 'no tienes permiso') !== false) {
                    $mensaje = "No tienes permiso para eliminar este $tipo";
                } else {
                    $mensaje = "Error interno al eliminar el $tipo: " . $e->getMessage();
                }

                $res = ['tipo' => 'error', 'mensaje' => $mensaje];
            }
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Elimina permanentemente todos los archivos y carpetas de la papelera del usuario.
    public function vaciarPapelera()
    {
        ob_start();
        try {
            if (empty($this->id_usuario)) {
                throw new Exception("ID de usuario no definido");
            }
            $carpetas = $this->model->getPapeleraCarpetas($this->id_usuario);
            $archivos = $this->model->getPapeleraArchivos($this->id_usuario);
            $success = true;
            $errores = [];
            if (!empty($carpetas)) {
                foreach ($carpetas as $carpeta) {
                    try {
                        if (isset($carpeta['id_usuario']) && $carpeta['id_usuario'] == $this->id_usuario) {
                            $result = $this->model->eliminarCarpetaPermanente($carpeta['id']);
                            if ($result == 0) {
                                $success = false;
                                $errores[] = "Error al eliminar carpeta ID: " . $carpeta['id'];
                            }
                        } else {
                            $success = false;
                            $errores[] = "Acceso denegado a carpeta";
                        }
                    } catch (Exception $e) {
                        $success = false;
                        $errores[] = "Error en carpeta: " . $e->getMessage();
                    }
                }
            }

            if (!empty($archivos)) {
                foreach ($archivos as $archivo) {
                    try {
                        if (isset($archivo['id_usuario']) && $archivo['id_usuario'] == $this->id_usuario) {
                            $result = $this->model->eliminarArchivoPermanente($archivo['id'], $this->id_usuario);
                            if ($result == 0) {
                                $success = false;
                                $errores[] = "Error al eliminar archivo ID: " . $archivo['id'];
                            }
                        } else {
                            $success = false;
                            $errores[] = "Acceso denegado a archivo";
                        }
                    } catch (Exception $e) {
                        $success = false;
                        $errores[] = "Error en archivo: " . $e->getMessage();
                    }
                }
            }

            if ($success && (count($carpetas) > 0 || count($archivos) > 0)) {
                $totalEliminados = count($carpetas) + count($archivos);
                $res = [
                    'tipo' => 'success',
                    'mensaje' => "Papelera vaciada con éxito. {$totalEliminados} elementos eliminados permanentemente."
                ];
            } elseif (count($carpetas) == 0 && count($archivos) == 0) {
                $res = [
                    'tipo' => 'warning',
                    'mensaje' => 'La papelera ya está vacía'
                ];
            } else {
                $mensaje_error = 'Error al vaciar la papelera';
                if (!empty($errores)) {
                    $mensaje_error .= ': ' . implode(', ', array_slice($errores, 0, 2));
                    if (count($errores) > 2) {
                        $mensaje_error .= ' y ' . (count($errores) - 2) . ' más';
                    }
                }
                $res = [
                    'tipo' => 'error',
                    'mensaje' => $mensaje_error
                ];
            }
        } catch (Exception $e) {
            $res = [
                'tipo' => 'error',
                'mensaje' => 'Error interno: ' . $e->getMessage()
            ];
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }
}
