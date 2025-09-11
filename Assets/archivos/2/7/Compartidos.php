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
require_once './Config/Config.php';
require_once 'vendor/autoload.php';

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
        if (!$this->tokenValido) {
            $res = ['tipo' => 'error', 'mensaje' => 'Sesión expirada'];
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $tieneAcceso = $this->model->verificarAccesoArchivo($id, $this->correo);

            if (!$tieneAcceso) {
                $res = ['tipo' => 'error', 'mensaje' => 'No tienes permisos para acceder a este archivo'];
            } else {
                $archivo = $this->model->getArchivoCompleto($id);

                if (!empty($archivo)) {
                    $url = BASE_URL . 'Assets/archivos/' . $archivo['id_carpeta'] . '/' . $archivo['nombre'];
                    $res = [
                        'tipo' => 'success',
                        'mensaje' => 'Archivo encontrado',
                        'archivo' => [
                            'id' => $archivo['id'],
                            'nombre' => $archivo['nombre'],
                            'tipo' => $archivo['tipo'],
                            'url' => $url,
                            'tamano' => $archivo['tamano'] ?? 0
                        ]
                    ];
                } else {
                    $res = ['tipo' => 'error', 'mensaje' => 'Archivo no encontrado en el sistema'];
                }
            }
        } catch (Exception $e) {
            error_log("Error al obtener archivo: " . $e->getMessage());
            $res = ['tipo' => 'error', 'mensaje' => 'Error interno del servidor'];
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

            $archivo = $this->model->getArchivoCompleto($detalle['id_archivo']);

            if (empty($archivo)) {
                $res = ['tipo' => 'error', 'mensaje' => 'Archivo original no encontrado'];
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $rutaArchivo = 'Assets/archivos/' . $archivo['id_carpeta'] . '/' . $archivo['nombre'];
            $rutaCompleta = $_SERVER['DOCUMENT_ROOT'] . '/gestordocs/' . $rutaArchivo;

            if (!file_exists($rutaCompleta)) {
                $res = ['tipo' => 'error', 'mensaje' => 'El archivo físico no existe: ' . $rutaCompleta];
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                die();
            }

            $extension = strtolower(pathinfo($archivo['nombre'], PATHINFO_EXTENSION));
            $tipoVisualizacion = $this->determinarTipoVisualizacion($extension);

            $url = BASE_URL . $rutaArchivo;

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
                    'ruta' => $rutaArchivo,
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
            $data = $this->model->getDetalleArchivoCompartido($id_detalle, $this->correo);

            if (!empty($data) && is_array($data)) {
                if (isset($data['fecha_add']) && !empty($data['fecha_add'])) {
                    $data['fecha'] = time_ago(strtotime($data['fecha_add']));
                } else {
                    $data['fecha'] = 'Fecha no disponible';
                }

                $data['id_detalle'] = $data['id'] ?? $id_detalle;
                $data['usuario'] = $data['correo'] ?? 'Usuario no disponible';
                $data['compartido'] = $data['propietario'] ?? 'Usuario no disponible';
                $data['tipo'] = $data['tipo'] ?? 'Archivo';
                $data['id_archivo'] = $data['id_archivo'];

                if (!isset($data['id_archivo']) || empty($data['id_archivo'])) {
                    $res = array('mensaje' => 'ID de archivo no válido', 'tipo' => 'error');
                    echo json_encode($res, JSON_UNESCAPED_UNICODE);
                    die();
                }

                echo json_encode($data, JSON_UNESCAPED_UNICODE);
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
            $key = new Key(SECRET_KEY, 'HS256');
            $decoded = JWT::decode($token, $key);

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


    public function descargar($id_detalle)
    {
        // Verificar autenticación
        if (!$this->tokenValido) {
            http_response_code(401);
            echo "Acceso no autorizado. Sesión expirada.";
            die();
        }

        try {
            // Obtener los detalles del archivo compartido
            $detalle_compartido = $this->model->getDetalleArchivoCompartido($id_detalle, $this->correo);

            if (empty($detalle_compartido)) {
                http_response_code(404);
                echo "El archivo no se encontró o no tienes permiso para descargarlo.";
                die();
            }

            // Obtener la información completa del archivo
            $archivo_completo = $this->model->getArchivoCompleto($detalle_compartido['id_archivo']);

            if (empty($archivo_completo)) {
                http_response_code(404);
                echo "La información del archivo no se encontró en el sistema.";
                die();
            }

            // Construir la ruta del archivo
            $id_carpeta = $archivo_completo['id_carpeta'];
            $nombre_archivo = $archivo_completo['nombre'];

            // Probar diferentes rutas posibles
            $rutas_posibles = [
                $_SERVER['DOCUMENT_ROOT'] . '/gestordocs/Assets/archivos/' . $id_carpeta . '/' . $nombre_archivo,
                $_SERVER['DOCUMENT_ROOT'] . '/Assets/archivos/' . $id_carpeta . '/' . $nombre_archivo,
                __DIR__ . '/../Assets/archivos/' . $id_carpeta . '/' . $nombre_archivo,
                __DIR__ . '/../../Assets/archivos/' . $id_carpeta . '/' . $nombre_archivo,
            ];

            $ruta_archivo = null;
            foreach ($rutas_posibles as $ruta) {
                if (file_exists($ruta)) {
                    $ruta_archivo = $ruta;
                    break;
                }
            }

            // Verificar si el archivo existe físicamente
            if (!$ruta_archivo || !file_exists($ruta_archivo)) {
                http_response_code(404);
                error_log("Archivo físico no encontrado. ID Carpeta: $id_carpeta, Nombre: $nombre_archivo");
                error_log("Rutas probadas: " . implode(', ', $rutas_posibles));
                echo "El archivo físico no se encontró en el servidor.";
                die();
            }

            // Obtener información del archivo
            $tipo_mime = $archivo_completo['tipo'] ?? 'application/octet-stream';
            $tamano_archivo = filesize($ruta_archivo);

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

            // Enviar el archivo
            if ($handle = fopen($ruta_archivo, 'rb')) {
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
                fclose($handle);
            } else {
                http_response_code(500);
                echo "Error al leer el archivo.";
            }
        } catch (Exception $e) {
            error_log("Error en descarga de archivo compartido: " . $e->getMessage());
            error_log("ID Detalle: $id_detalle, Usuario: " . $this->correo);
            http_response_code(500);
            echo "Error interno del servidor.";
        }

        die();
    }
}
