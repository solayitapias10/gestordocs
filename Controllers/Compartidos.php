<?php

/********************************************
Archivo php Compartidos.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

// Controlador para gestionar archivos compartidos
require_once ROOT_PATH . 'Config/Config.php';
require_once ROOT_PATH . 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Compartidos extends Controller
{
    private $id_usuario, $correo;
    private $tokenValido = false;

    // Inicializa el controlador y valida el token
    public function __construct()
    {
        parent::__construct();
        $this->validarToken();
    }

    // Muestra la vista principal de archivos compartidos conmigo
    public function index()
    {
        if (!$this->tokenValido) {
            $this->redirigirLogin();
            return;
        }

        $data['title'] = 'Archivos Compartidos Conmigo';
        $data['script'] = 'compartidos.js';
        $data['menu'] = 'compartidos';
        $data['active'] = 'compartidos';

        $data['archivos'] = $this->model->getArchivosCompartidosConmigoDirecto($this->correo);

        $data['user'] = [
            'nombre' => $this->getNombreUsuario(),
            'correo' => $this->correo,
            'avatar' => $this->getAvatarUsuario(),
            'rol' => $this->getRolUsuario()
        ];

        $this->views->getView('admin', 'compartidos', $data);
    }

    // Obtiene archivos compartidos para solicitudes AJAX
    public function obtenerCompartidos()
    {
        if (!$this->tokenValido) {
            echo json_encode(['success' => false, 'message' => 'Sesión expirada'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $archivos = $this->model->getArchivosCompartidosConmigoDirecto($this->correo);
            echo json_encode(['success' => true, 'archivos' => $archivos], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error al obtener compartidos: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al cargar archivos'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Obtiene información de un archivo específico
    public function obtenerArchivo($id)
    {
        $archivo = $this->model->getArchivo($id, $this->id_usuario);
        if (!empty($archivo)) {
            // Construimos la URL base
            $url = BASE_URL . 'Assets/archivos/' . $this->id_usuario . '/';

            // Verificamos si el archivo está dentro de una carpeta o no
            if ($archivo['id_carpeta']) {
                // Si tiene id_carpeta, lo añadimos a la ruta
                $url .= $archivo['id_carpeta'] . '/';
            }

            // Finalmente, añadimos el nombre del archivo
            $url .= $archivo['nombre'];

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

    // Obtiene detalles de un archivo compartido con validación de permisos
    public function obtenerArchivoCompartido($id_detalle)
    {
        if (!$this->tokenValido) {
            $res = ['tipo' => 'error', 'mensaje' => 'Sesión expirada'];
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $detalle = $this->model->getDetalleArchivoCompartido($id_detalle, $this->correo);

            if (empty($detalle)) {
                $res = ['tipo' => 'error', 'mensaje' => 'Archivo no encontrado o sin permisos'];
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Validar que tenemos el id_archivo
            if (!isset($detalle['id_archivo']) || empty($detalle['id_archivo'])) {
                $res = ['tipo' => 'error', 'mensaje' => 'Datos del archivo compartido incompletos'];
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $archivo = $this->model->getArchivoCompleto($detalle['id_archivo']);

            if (empty($archivo)) {
                $res = ['tipo' => 'error', 'mensaje' => 'Archivo original no encontrado'];
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Construir la ruta del archivo correctamente
            $rutaRelativa = 'Assets/archivos/';

            // Agregar ID del usuario propietario del archivo
            $rutaRelativa .= $archivo['id_usuario'] . '/';

            // Si tiene carpeta, agregarla
            if (!empty($archivo['id_carpeta'])) {
                $rutaRelativa .= $archivo['id_carpeta'] . '/';
            }

            // Agregar el nombre del archivo
            $rutaRelativa .= $archivo['nombre'];

            // Ruta completa en el sistema de archivos
            $rutaCompleta = $_SERVER['DOCUMENT_ROOT'] . '/gestordocs/' . $rutaRelativa;

            // Verificar si el archivo físico existe
            if (!file_exists($rutaCompleta)) {
                // Intentar rutas alternativas comunes
                $rutasAlternativas = [
                    $_SERVER['DOCUMENT_ROOT'] . '/' . $rutaRelativa,
                    dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $rutaRelativa,
                    __DIR__ . '/../' . $rutaRelativa
                ];

                $archivoEncontrado = false;
                foreach ($rutasAlternativas as $rutaAlternativa) {
                    if (file_exists($rutaAlternativa)) {
                        $rutaCompleta = $rutaAlternativa;
                        $archivoEncontrado = true;
                        break;
                    }
                }

                if (!$archivoEncontrado) {
                    error_log("Archivo no encontrado en ninguna ruta:");
                    error_log("Ruta principal: " . $rutaCompleta);
                    foreach ($rutasAlternativas as $i => $ruta) {
                        error_log("Ruta alternativa " . ($i + 1) . ": " . $ruta);
                    }

                    $res = ['tipo' => 'error', 'mensaje' => 'El archivo físico no existe en el servidor'];
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $extension = strtolower(pathinfo($archivo['nombre'], PATHINFO_EXTENSION));
            $tipoVisualizacion = $this->determinarTipoVisualizacion($extension);

            // URL para el navegador
            $url = BASE_URL . $rutaRelativa;

            $res = [
                'tipo' => 'success',
                'mensaje' => 'Archivo encontrado',
                'archivo' => [
                    'id' => $archivo['id'],
                    'nombre' => $archivo['nombre'],
                    'tipo' => $archivo['tipo'],
                    'extension' => $extension,
                    'url' => $url,
                    'tamano' => $archivo['tamano'] ?? 0,
                    'ruta' => $rutaRelativa,
                    'tipo_visualizacion' => $tipoVisualizacion,
                    'propietario' => $detalle['propietario'] ?? 'Usuario desconocido',
                    'fecha_compartido' => $detalle['fecha_add'] ?? date('Y-m-d H:i:s')
                ]
            ];
        } catch (Exception $e) {
            error_log("Error al obtener archivo compartido: " . $e->getMessage());
            $res = ['tipo' => 'error', 'mensaje' => 'Error interno: ' . $e->getMessage()];
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Determina el tipo de visualización según la extensión del archivo
    private function determinarTipoVisualizacion($extension)
    {
        $tiposImagen = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $tiposPdf = ['pdf'];
        $tiposVideo = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
        $tiposAudio = ['mp3', 'wav', 'ogg', 'aac', 'm4a'];
        $tiposTexto = ['txt', 'json', 'xml', 'csv', 'log'];
        $tiposOficina = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (in_array($extension, $tiposImagen)) {
            return 'imagen';
        } elseif (in_array($extension, $tiposPdf)) {
            return 'pdf';
        } elseif (in_array($extension, $tiposVideo)) {
            return 'video';
        } elseif (in_array($extension, $tiposAudio)) {
            return 'audio';
        } elseif (in_array($extension, $tiposTexto)) {
            return 'texto';
        } elseif (in_array($extension, $tiposOficina)) {
            return 'oficina';
        } else {
            return 'descarga';
        }
    }

    // Obtiene detalles de un archivo compartido para visualización
    public function verDetalle($id_detalle)
    {
        if (!$this->tokenValido) {
            $res = array('mensaje' => 'Sesión expirada. Por favor, inicia sesión nuevamente.', 'tipo' => 'error');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            // Obtiene todos los detalles necesarios en una sola llamada a la base de datos
            $data = $this->model->getDetalleArchivoCompartido($id_detalle, $this->correo);

            if (!empty($data) && is_array($data)) {
                if (isset($data['fecha_add']) && !empty($data['fecha_add'])) {
                    $data['fecha'] = time_ago(strtotime($data['fecha_add']));
                } else {
                    $data['fecha'] = 'Fecha no disponible';
                }

                // Construye la URL del archivo de forma segura.
                $ruta_relativa = $data['id_usuario'] . '/';
                if (!empty($data['id_carpeta'])) {
                    $ruta_relativa .= $data['id_carpeta'] . '/';
                }
                $ruta_relativa .= $data['nombre'];

                $url = BASE_URL . 'Assets/archivos/' . $ruta_relativa;
                $extension = strtolower(pathinfo($data['nombre'], PATHINFO_EXTENSION));

                $res = [
                    'tipo' => 'success',
                    'mensaje' => 'Archivo encontrado',
                    'archivo' => [
                        'id_detalle' => $data['id'] ?? $id_detalle,
                        'nombre' => $data['nombre'] ?? '',
                        'tipo' => $data['tipo'] ?? 'Archivo',
                        'extension' => $extension,
                        'url' => $url,
                        'tamano' => $data['tamano'] ?? 0,
                        'propietario' => $data['propietario'] ?? 'Usuario desconocido',
                        'fecha_compartido' => $data['fecha_add'] ?? 'Fecha no disponible'
                    ]
                ];
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } else {
                $res = array(
                    'mensaje' => 'Archivo no encontrado o sin permisos para acceder',
                    'tipo' => 'error'
                );
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error en verDetalle: " . $e->getMessage());
            $res = array('mensaje' => 'Error interno del servidor', 'tipo' => 'error');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
    // Elimina un archivo compartido de la lista del usuario
    public function eliminar($id)
    {
        if (!$this->tokenValido) {
            $res = array('mensaje' => 'Sesión expirada. Por favor, inicia sesión nuevamente.', 'tipo' => 'error');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $archivo = $this->model->verificarArchivoCompartidoConUsuario($id, $this->correo);

            if (empty($archivo)) {
                $res = array('mensaje' => 'Archivo no encontrado o sin permisos', 'tipo' => 'error');
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $data = $this->model->cambiarEstado(0, $id);
            if ($data == 1) {
                $res = array('mensaje' => 'Archivo eliminado de tu lista', 'tipo' => 'success');
            } else {
                $res = array('mensaje' => 'Error al eliminar', 'tipo' => 'error');
            }
        } catch (Exception $e) {
            error_log("Error al eliminar: " . $e->getMessage());
            $res = array('mensaje' => 'Error interno del servidor', 'tipo' => 'error');
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Valida el token JWT para autenticación
    private function validarToken()
    {
        $token = null;

        $headers = apache_request_headers();
        if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            $token = str_replace('Bearer ', '', $authHeader);
        } else if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        } else if (isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
        } else if (isset($_POST['jwt_token'])) {
            $token = $_POST['jwt_token'];
        }

        if (empty($token)) {
            $this->tokenValido = false;
            return;
        }

        try {
            // CORREGIDO: Sintaxis para firebase/php-jwt v3.0.0
            $decoded = JWT::decode($token, SECRET_KEY, array('HS256'));

            $this->id_usuario = $decoded->sub ?? null;
            $this->correo = $decoded->correo ?? '';

            if (empty($this->id_usuario)) {
                $this->tokenValido = false;
                return;
            }

            $this->tokenValido = true;
        } catch (Exception $e) {
            $this->tokenValido = false;
            error_log("Error de token en Compartidos: " . $e->getMessage());
        }
    }

    // Redirige al login si el token no es válido
    private function redirigirLogin()
    {
        header('Location: ' . BASE_URL);
        exit;
    }

    // Obtiene el nombre completo del usuario
    private function getNombreUsuario()
    {
        if (!$this->tokenValido) return '';
        $usuario = $this->model->getUsuario($this->id_usuario);
        return $usuario['nombre'] . ' ' . $usuario['apellido'];
    }

    // Obtiene el avatar del usuario
    private function getAvatarUsuario()
    {
        if (!$this->tokenValido) return BASE_URL . 'Assets/images/avatar.jpg';
        $usuario = $this->model->getUsuario($this->id_usuario);
        return !empty($usuario['avatar']) ? $usuario['avatar'] : BASE_URL . 'Assets/images/avatar.jpg';
    }

    // Obtiene el rol del usuario
    private function getRolUsuario()
    {
        if (!$this->tokenValido) return '';
        $usuario = $this->model->getUsuario($this->id_usuario);
        return $usuario['rol'];
    }

    // Método para descargar un archivo compartido.
    public function descargar($id_detalle)
    {
        if (!$this->tokenValido) {
            $this->redirigirLogin();
            return;
        }

        $detalle_archivo = $this->model->getDetalleArchivoCompartido($id_detalle, $this->correo);
        if (empty($detalle_archivo)) {
            http_response_code(404);
            die("Archivo no encontrado o sin permisos.");
        }

        try {
            // Obtener datos del archivo
            $nombre_archivo = $detalle_archivo['nombre_archivo'] ?? '';
            $id_archivo = $detalle_archivo['id_archivo'] ?? '';
            $tipo_mime = $detalle_archivo['tipo'] ?? 'application/octet-stream';
            $tamano_archivo = $detalle_archivo['tamano'] ?? 0;

            if (empty($nombre_archivo) || empty($id_archivo)) {
                http_response_code(404);
                die("Datos del archivo incompletos.");
            }

            // PASO CRÍTICO: Obtener información completa del archivo original
            $archivo_completo = $this->model->getArchivoCompleto($id_archivo);
            if (empty($archivo_completo)) {
                http_response_code(404);
                die("No se pudo obtener información del archivo original.");
            }

            $id_propietario = $archivo_completo['id_usuario'] ?? '';
            $id_carpeta = $archivo_completo['id_carpeta'] ?? null;

            if (empty($id_propietario)) {
                http_response_code(404);
                die("No se pudo identificar el propietario del archivo.");
            }

            // Construir la ruta relativa CORRECTAMENTE
            $ruta_relativa = 'Assets' . DIRECTORY_SEPARATOR . 'archivos' . DIRECTORY_SEPARATOR . $id_propietario . DIRECTORY_SEPARATOR;

            if (!empty($id_carpeta)) {
                $ruta_relativa .= $id_carpeta . DIRECTORY_SEPARATOR;
            }

            $ruta_relativa .= $nombre_archivo;

            // Construir la ruta física completa SIN duplicar 'gestordocs'
            $ruta_fisica_completa = ROOT_PATH . DIRECTORY_SEPARATOR . $ruta_relativa;

            // Normalizar la ruta para evitar problemas con barras
            $ruta_fisica_completa = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta_fisica_completa);
            $ruta_fisica_completa = realpath($ruta_fisica_completa);

            // Verificar si el archivo físico existe
            if (!$ruta_fisica_completa || !file_exists($ruta_fisica_completa)) {
                // Intentar rutas alternativas
                $rutas_alternativas = [
                    ROOT_PATH . DIRECTORY_SEPARATOR . $ruta_relativa,
                    $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'gestordocs' . DIRECTORY_SEPARATOR . $ruta_relativa,
                    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $ruta_relativa
                ];

                $archivo_encontrado = false;
                foreach ($rutas_alternativas as $ruta_alternativa) {
                    $ruta_normalizada = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta_alternativa);
                    $ruta_real = realpath($ruta_normalizada);

                    if ($ruta_real && file_exists($ruta_real)) {
                        $ruta_fisica_completa = $ruta_real;
                        $archivo_encontrado = true;
                        break;
                    }
                }

                if (!$archivo_encontrado) {
                    error_log("=== DEBUG DESCARGA ===");
                    error_log("ID Detalle: $id_detalle");
                    error_log("Usuario: " . $this->correo);
                    error_log("Nombre archivo: $nombre_archivo");
                    error_log("ID Propietario: $id_propietario");
                    error_log("ID Carpeta: " . ($id_carpeta ?? 'null'));
                    error_log("ROOT_PATH: " . ROOT_PATH);
                    error_log("Ruta relativa construida: $ruta_relativa");
                    error_log("Ruta física intentada: " . (ROOT_PATH . DIRECTORY_SEPARATOR . $ruta_relativa));

                    foreach ($rutas_alternativas as $i => $ruta) {
                        error_log("Ruta alternativa " . ($i + 1) . ": " . $ruta);
                    }

                    http_response_code(404);
                    die("El archivo físico no existe en el servidor: " . $nombre_archivo);
                }
            }

            // Verificar que el archivo es legible
            if (!is_readable($ruta_fisica_completa)) {
                error_log("Archivo no legible: " . $ruta_fisica_completa);
                http_response_code(403);
                die("El archivo no se puede leer debido a permisos.");
            }

            // Obtener el tamaño real del archivo si no está en BD
            $tamano_real = filesize($ruta_fisica_completa);
            if (empty($tamano_archivo) || $tamano_archivo != $tamano_real) {
                $tamano_archivo = $tamano_real;
            }

            // Validación de seguridad: verificar que el archivo está dentro del directorio permitido
            $directorio_permitido = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'archivos');
            if (strpos($ruta_fisica_completa, $directorio_permitido) !== 0) {
                error_log("Intento de acceso fuera del directorio permitido: " . $ruta_fisica_completa);
                http_response_code(403);
                die("Acceso no autorizado al archivo.");
            }

            // Limpiar cualquier salida previa
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Establecer headers para la descarga
            header('Content-Type: ' . $tipo_mime);
            header('Content-Disposition: attachment; filename="' . basename($nombre_archivo) . '"');
            header('Content-Length: ' . $tamano_archivo);
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Transfer-Encoding: binary');

            // Evitar problemas de memoria con archivos grandes
            $chunk_size = 8192; // 8KB por chunk

            if ($handle = fopen($ruta_fisica_completa, 'rb')) {
                while (!feof($handle) && !connection_aborted()) {
                    $chunk = fread($handle, $chunk_size);
                    echo $chunk;

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
                fclose($handle);

                // Log exitoso
                error_log("Descarga exitosa - Usuario: " . $this->correo . ", Archivo: " . $nombre_archivo);
            } else {
                error_log("No se pudo abrir el archivo para lectura: " . $ruta_fisica_completa);
                http_response_code(500);
                echo "Error al leer el archivo.";
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error interno del servidor.";
        }
    }
}
