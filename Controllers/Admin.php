<?php

/********************************************
Archivo php Admin.php
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

class Admin extends Controller
{
    private $id_usuario, $correo;
    private $authManager;

    public function __construct()
    {
        parent::__construct();
        $this->authManager = new AuthManager(SECRET_KEY);
        $this->validarToken();
    }

    // Muestra la página principal con carpetas y archivos recientes del usuario.
    public function index()
    {
        $data['title'] = 'Gestordocs';
        $data['script'] = 'files.js';
        $data['active'] = 'recents';
        $data['menu'] = 'admin';
        $data['user'] = $this->model->getUsuario($this->id_usuario);

        // Parámetros de paginación
        $page_carpetas = isset($_GET['page_carpetas']) ? max(1, intval($_GET['page_carpetas'])) : 1;
        $page_archivos = isset($_GET['page_archivos']) ? max(1, intval($_GET['page_archivos'])) : 1;
        $limit_carpetas = 6;
        $limit_archivos = 4;

        // Obtener datos paginados
        $carpetas = $this->model->getPaginatedCarpetasAll($page_carpetas, $limit_carpetas);
        $archivos = $this->model->getPaginatedArchivosRecientesAll($page_archivos, $limit_archivos);

        // Calcular información de paginación
        $total_carpetas = $this->model->getTotalCarpetas();
        $total_archivos = $this->model->getTotalArchivosRecientes();
        
        $data['pagination_carpetas'] = [
            'current_page' => $page_carpetas,
            'total_pages' => ceil($total_carpetas / $limit_carpetas),
            'total_records' => $total_carpetas,
            'limit' => $limit_carpetas
        ];
        
        $data['pagination_archivos'] = [
            'current_page' => $page_archivos,
            'total_pages' => ceil($total_archivos / $limit_archivos),
            'total_records' => $total_archivos,
            'limit' => $limit_archivos
        ];

        for ($i = 0; $i < count($carpetas); $i++) {
            $carpetas[$i]['color'] = substr(md5($carpetas[$i]['id']), 0, 6);
            $carpetas[$i]['fecha'] = time_ago(strtotime($carpetas[$i]['fecha_create']));
        }

        for ($i = 0; $i < count($archivos); $i++) {
            $archivos[$i]['color'] = substr(md5($archivos[$i]['id']), 0, 6);
            $archivos[$i]['fecha'] = time_ago(strtotime($archivos[$i]['fecha_create']));
            $archivos[$i]['tamano_formateado'] = formatBytes($archivos[$i]['tamano']);
        }

        $data['carpetas'] = $carpetas;
        $data['archivos'] = $archivos;
        $data['shares'] = $this->model->verificarEstado($this->correo);
        $this->views->getView('admin', 'home', $data);
    }

    // Valida el token JWT del usuario.
    private function validarToken()
    {
        $resultado = $this->authManager->middleware(true);
        if ($resultado['valido']) {
            $this->id_usuario = $resultado['id_usuario'];
            $this->correo = $resultado['correo'];
        }
    }

    // Crea una nueva carpeta.
    public function crearcarpeta()
    {
        $nombre = $_POST['nombre'];
        $id_carpeta_padre = isset($_POST['from_home']) && $_POST['from_home'] === 'true' ? NULL : (isset($_POST['id_carpeta_padre']) && !empty($_POST['id_carpeta_padre']) ? intval($_POST['id_carpeta_padre']) : NULL);

        if (empty($nombre)) {
            $res = array('tipo' => 'warning', 'mensaje' => 'El nombre es requerido');
        } else {
            $verificarNombre = $this->model->getVerificar('nombre', $nombre, $this->id_usuario, 0, $id_carpeta_padre);
            if (empty($verificarNombre)) {
                $id_carpeta = $this->model->crearcarpeta($nombre, $this->id_usuario, $id_carpeta_padre);
                if ($id_carpeta > 0) {
                    $this->model->registrarNotificacion($this->id_usuario, $id_carpeta, $nombre, 'CREADA');
                    $res = array('tipo' => 'success', 'mensaje' => 'Carpeta Creada');
                } else {
                    $res = array('tipo' => 'error', 'mensaje' => 'Error al crear carpeta');
                }
            } else {
                $res = array('tipo' => 'error', 'mensaje' => 'La carpeta ya existe en este nivel');
            }
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Sube una carpeta con su estructura de archivos y subcarpetas.
    public function subirCarpeta()
    {
        $res = array('tipo' => 'error', 'mensaje' => 'Error al subir la carpeta');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files']) && isset($_POST['paths'])) {
            $id_carpeta = (empty($_POST['id_carpeta']) || $_POST['id_carpeta'] === '1') ? null : $_POST['id_carpeta'];
            $files = $_FILES['files'];
            $paths = $_POST['paths'];
            $id_usuario = $this->id_usuario;
            $success = true;
            $errors = [];

            if ($id_carpeta !== null) {
                $carpetaDestino = $this->model->getCarpeta($id_carpeta);
                if (!$carpetaDestino) {
                    $res['mensaje'] = 'Carpeta destino no encontrada';
                    echo json_encode($res);
                    return;
                }
            }

            $carpetasCreadas = array('' => $id_carpeta);

            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = "Error al subir {$files['name'][$i]}: " . $files['error'][$i];
                    $success = false;
                    continue;
                }

                $relativePath = $paths[$i];
                $fileName = $files['name'][$i];
                $fileTmp = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];
                $fileSize = $files['size'][$i];

                $folderPath = dirname($relativePath);
                $id_carpeta_archivo = $id_carpeta;

                if ($folderPath && $folderPath !== '.') {
                    $pathParts = explode('/', $folderPath);
                    $currentPath = '';
                    $parentId = $id_carpeta;

                    foreach ($pathParts as $part) {
                        if (empty($part))
                            continue;
                        $currentPath .= ($currentPath ? '/' : '') . $part;

                        if (!isset($carpetasCreadas[$currentPath])) {
                            $verificarNombre = $this->model->getVerificar('nombre', $part, $id_usuario, 0, $parentId);
                            if (empty($verificarNombre)) {
                                $id_nueva_carpeta = $this->model->crearcarpeta($part, $id_usuario, $parentId);

                                if ($id_nueva_carpeta) {
                                    $carpetasCreadas[$currentPath] = $id_nueva_carpeta;
                                    $parentId = $id_nueva_carpeta;
                                    $this->model->registrarNotificacion($id_usuario, $id_nueva_carpeta, $part, 'CREADA');
                                } else {
                                    $errors[] = "Error al crear carpeta: $part";
                                    $success = false;
                                    break;
                                }
                            } else {
                                $carpetasCreadas[$currentPath] = $verificarNombre['id'];
                                $parentId = $verificarNombre['id'];
                            }
                        } else {
                            $parentId = $carpetasCreadas[$currentPath];
                        }
                    }
                    $id_carpeta_archivo = $parentId;
                }

                $ruta = 'Assets/archivos/' . $id_usuario . '/' . ($id_carpeta_archivo ? $id_carpeta_archivo . '/' : '');
                if (!file_exists($ruta)) {
                    if (!mkdir($ruta, 0777, true)) {
                        $errors[] = "No se pudo crear el directorio: $ruta";
                        $success = false;
                        continue;
                    }
                }

                $rutaArchivo = $ruta . $fileName;
                $verificarArchivo = $this->model->getVerificarArchivo($fileName, $id_carpeta_archivo, $id_usuario);
                if (!empty($verificarArchivo)) {
                    $errors[] = "El archivo '$fileName' ya existe en esta carpeta";
                    $success = false;
                    continue;
                }

                if (move_uploaded_file($fileTmp, $rutaArchivo)) {
                    $data = $this->model->subirArchivo($fileName, $fileType, $fileSize, $id_carpeta_archivo, $id_usuario);
                    if ($data) {
                        $this->model->registrarNotificacion($id_usuario, $id_carpeta_archivo, $fileName, 'SUBIDA');
                    } else {
                        $errors[] = "Error al registrar $fileName en la base de datos";
                        $success = false;
                    }
                } else {
                    $errors[] = "Error al mover $fileName";
                    $success = false;
                }
            }

            if ($success) {
                $res = array('tipo' => 'success', 'mensaje' => 'Carpeta subida exitosamente');
            } else {
                $res['mensaje'] = 'Errores: ' . implode(', ', $errors);
            }
        } else {
            $res['mensaje'] = 'Solicitud inválida';
        }

        echo json_encode($res);
        die();
    }

    // Muestra el contenido de una carpeta específica.
    public function ver($id_carpeta)
    {
        // Valida que la entrada sea un número entero válido.
        if (!is_numeric($id_carpeta)) {
            http_response_code(404);
            require_once 'Views/error.php';
            exit;
        }

        $data['title'] = 'Listado de archivos';
        $data['script'] = 'files.js';
        $data['active'] = 'detail';
        $data['menu'] = 'admin';
        $data['id_carpeta'] = $id_carpeta;


        $data['user'] = $this->model->getUsuario($this->id_usuario);
        $data['carpeta'] = $this->model->getCarpeta($id_carpeta);
        $archivos = $this->model->getArchivos($id_carpeta, $this->id_usuario);
        try {
            $subcarpetas = $this->model->getSubCarpetas($id_carpeta, $this->id_usuario);
        } catch (Exception $e) {
            error_log("Error al obtener subcarpetas: " . $e->getMessage());
            $subcarpetas = [];
        }
        $data['shares'] = $this->model->verificarEstado($this->correo);

        for ($i = 0; $i < count($archivos); $i++) {
            $archivos[$i]['color'] = substr(md5($archivos[$i]['id']), 0, 6);
            $archivos[$i]['fecha'] = time_ago(strtotime($archivos[$i]['fecha_create']));
            $archivos[$i]['tamano_formateado'] = formatBytes($archivos[$i]['tamano']);
        }

        for ($i = 0; $i < count($subcarpetas); $i++) {
            $subcarpetas[$i]['color'] = substr(md5($subcarpetas[$i]['id']), 0, 6);
            $subcarpetas[$i]['fecha'] = time_ago(strtotime($subcarpetas[$i]['fecha_create']));
        }

        $data['archivos'] = $archivos;
        $data['subcarpetas'] = $subcarpetas;
        $this->views->getView('admin', 'archivos', $data);
    }

    // Edita el nombre de una carpeta existente.
    public function editarCarpeta()
    {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

            if (empty($nombre) || $id <= 0) {
                $res = array('tipo' => 'warning', 'mensaje' => 'El nombre y el ID son requeridos');
            } else {
                $carpeta = $this->model->getCarpeta($id);

                if (!$carpeta || $carpeta['id_usuario'] != $this->id_usuario) {
                    $res = array('tipo' => 'error', 'mensaje' => 'Carpeta no encontrada o no tienes permiso');
                } else {
                    $id_carpeta_padre = isset($_POST['id_carpeta_padre']) && !empty($_POST['id_carpeta_padre'])
                        ? intval($_POST['id_carpeta_padre'])
                        : $carpeta['id_carpeta_padre'];

                    $verificarNombre = $this->model->getVerificar('nombre', $nombre, $this->id_usuario, $id, $id_carpeta_padre);

                    if (empty($verificarNombre)) {
                        $data = $this->model->editarCarpeta($nombre, $id, $this->id_usuario);

                        if ($data > 0) {
                            $this->model->registrarNotificacion($this->id_usuario, $id, $nombre, 'EDITADA');
                            $res = array('tipo' => 'success', 'mensaje' => 'Carpeta Actualizada');
                        } else {
                            $res = array('tipo' => 'error', 'mensaje' => 'Error al actualizar carpeta');
                        }
                    } else {
                        $res = array('tipo' => 'error', 'mensaje' => 'El nombre ya existe en este nivel');
                    }
                }
            }
        } catch (Exception $e) {
            $res = array('tipo' => 'error', 'mensaje' => 'Error en el servidor: ' . $e->getMessage());
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }


    // Elimina una carpeta y registra la acción.
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

    // Sube uno o más archivos al sistema.
    public function subirArchivo()
    {
        // Limpiar cualquier output previo y establecer headers apropiados
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');

        try {
            $id_carpeta = (empty($_POST['id_carpeta']) || $_POST['id_carpeta'] === '1') ? null : $_POST['id_carpeta'];

            // 1. Validar que existan archivos para subir
            if (empty($_FILES['files']) || empty($_FILES['files']['name']) || $_FILES['files']['error'][0] === UPLOAD_ERR_NO_FILE) {
                $response = ['tipo' => 'warning', 'mensaje' => 'No se seleccionaron archivos'];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit();
            }

            $files = $_FILES['files'];
            // Normalizar a un array si se sube un solo archivo
            if (!is_array($files['name'])) {
                foreach ($files as $key => $value) {
                    $files[$key] = [$value];
                }
            }

            // 2. Definir y crear la ruta de destino de forma segura
            $carpeta_base_usuario = 'Assets/archivos/' . $this->id_usuario;
            $carpeta_final = $carpeta_base_usuario;

            if ($id_carpeta !== null) {
                $carpeta_final .= '/' . $id_carpeta;
            }

            // Crear el directorio final si no existe
            if (!file_exists($carpeta_final)) {
                if (!mkdir($carpeta_final, 0777, true)) {
                    throw new Exception("No se pudo crear el directorio de destino.");
                }
            }

            $success = true;
            $errors = [];
            $archivos_subidos = [];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                // Sanitizar el nombre del archivo
                $name_original = $files['name'][$i];
                $name = basename($name_original);
                $tmp = $files['tmp_name'][$i];
                $tipo = $files['type'][$i];
                $tamano = $files['size'][$i];
                $error_code = $files['error'][$i];

                if ($error_code !== UPLOAD_ERR_OK) {
                    $errors[] = "Error al subir el archivo '$name': código " . $error_code;
                    $success = false;
                    continue;
                }

                // Verificar si el archivo ya existe en la DB
                if (!empty($this->model->getVerificarArchivo($name, $id_carpeta, $this->id_usuario))) {
                    $carpetaNombre = $id_carpeta ? "esta carpeta" : "la raíz";
                    $errors[] = "El archivo '$name' ya existe en $carpetaNombre.";
                    $success = false;
                    continue;
                }

                // Registrar archivo en base de datos ANTES de mover el archivo físico
                $resultado_bd = $this->model->subirArchivo($name, $tipo, $tamano, $id_carpeta, $this->id_usuario);

                // Debug: Log del resultado de la BD
                error_log("Resultado BD para archivo $name: " . print_r($resultado_bd, true));

                if (!empty($resultado_bd) && is_array($resultado_bd)) {
                    $id_archivo_nuevo = $resultado_bd['id_archivo_nuevo'] ?? null;
                    $mensaje_bd = $resultado_bd['mensaje'] ?? 'Sin mensaje';
                    $exito_bd = $resultado_bd['exito'] ?? false;

                    // Convertir boolean text a boolean real si es necesario
                    if (is_string($exito_bd)) {
                        $exito_bd = ($exito_bd === 'true' || $exito_bd === 't' || $exito_bd === '1');
                    }

                    if ($exito_bd && $id_archivo_nuevo > 0) {
                        // Archivo registrado exitosamente en BD, ahora mover el archivo físico
                        $rutaDestino = $carpeta_final . '/' . $name;

                        if (move_uploaded_file($tmp, $rutaDestino)) {
                            $archivos_subidos[] = $name;

                            // Registrar notificación si está en una carpeta específica
                            if ($id_carpeta !== null) {
                                $this->model->registrarNotificacion($this->id_usuario, $id_carpeta, $name, 'SUBIDA');
                            }

                            error_log("Archivo $name subido exitosamente");
                        } else {
                            $errors[] = "No se pudo mover el archivo '$name' a su destino.";
                            $success = false;
                            error_log("Error moviendo archivo $name a $rutaDestino");
                        }
                    } else {
                        $errors[] = "Error en BD para '$name': $mensaje_bd";
                        $success = false;
                        error_log("Error en BD para archivo $name: $mensaje_bd");
                    }
                } else {
                    $errors[] = "Error al procesar '$name': respuesta de BD inválida.";
                    $success = false;
                    error_log("Respuesta BD inválida para archivo $name: " . print_r($resultado_bd, true));
                }
            }
            error_log("Resultado BD raw: " . print_r($resultado_bd, true));

            // 4. Respuesta mejorada con más información de debug
            if ($success && empty($errors)) {
                $ubicacion = $id_carpeta !== null ? "en la carpeta seleccionada" : "en la raíz";
                $cantidad = count($archivos_subidos);
                $response = [
                    'tipo' => 'success',
                    'mensaje' => "$cantidad archivo(s) subido(s) correctamente $ubicacion.",
                    'archivos_subidos' => $archivos_subidos,
                    'cantidad' => $cantidad
                ];
                error_log("Upload exitoso: " . $cantidad . " archivos subidos");
            } else {
                $response = [
                    'tipo' => 'error',
                    'mensaje' => 'Ocurrieron algunos errores al subir los archivos.',
                    'detalles' => $errors,
                    'archivos_subidos' => $archivos_subidos,
                    'parcial_success' => !empty($archivos_subidos)
                ];
                error_log("Upload con errores: " . implode(', ', $errors));
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Excepción en subirArchivo: " . $e->getMessage() . " en línea " . $e->getLine());

            http_response_code(500);
            $response = [
                'tipo' => 'error',
                'mensaje' => 'Error interno del servidor: ' . $e->getMessage(),
                'archivo' => basename(__FILE__),
                'linea' => $e->getLine()
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }

        exit();
    }

    // Obtiene la información de un archivo específico.
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


    // Comparte archivos con otros usuarios.
    public function compartirArchivo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archivos']) && isset($_POST['usuarios'])) {
            $archivos = is_array($_POST['archivos']) ? $_POST['archivos'] : [$_POST['archivos']];
            $correos = is_array($_POST['usuarios']) ? $_POST['usuarios'] : [$_POST['usuarios']];
            $id_carpeta = isset($_POST['id_carpeta']) && !empty($_POST['id_carpeta']) ? intval($_POST['id_carpeta']) : null;

            $success = true;
            $errors = [];

            foreach ($archivos as $id_archivo) {
                $id_archivo = intval($id_archivo);
                $archivo = $this->model->getArchivo($id_archivo, $this->id_usuario);

                if ($archivo) {
                    foreach ($correos as $correo) {
                        $correo = trim($correo);
                        $usuario_destino = $this->model->getUsuarioPorCorreo($correo);
                        if (!$usuario_destino) {
                            $errors[] = "El correo $correo no está registrado";
                            $success = false;
                            continue;
                        }
                        $sql = "INSERT INTO solicitudes_compartidos (id_archivo, correo, id_usuario, estado, pendiente, fecha_add) VALUES (?, ?, ?, 2, 1, NOW())";
                        $datos = [$id_archivo, $correo, $this->id_usuario];
                        $result = $this->model->insertar($sql, $datos);

                        if (!$result) {
                            $errors[] = "Error al compartir el archivo ID $id_archivo con $correo";
                            $success = false;
                        }
                    }

                    $carpeta_para_notificacion = $id_carpeta ?? $archivo['id_carpeta'] ?? 0;
                    $this->model->registrarNotificacion(
                        $this->id_usuario,
                        $carpeta_para_notificacion,
                        $archivo['nombre'],
                        'COMPARTIDO'
                    );
                } else {
                    $errors[] = "Archivo ID $id_archivo no encontrado o no tienes permiso";
                    $success = false;
                }
            }

            $res = $success && empty($errors)
                ? ['tipo' => 'success', 'mensaje' => 'Archivos compartidos correctamente, pendiente de aceptación']
                : ['tipo' => 'error', 'mensaje' => 'Errores: ' . implode(', ', $errors)];
        } else {
            $res = ['tipo' => 'error', 'mensaje' => 'Solicitud inválida'];
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Muestra la vista de archivos compartidos en una carpeta.
    public function verdetalle($id_carpeta)
    {
        try {
            // Debug: Log de entrada
            error_log("=== DEBUG verdetalle() ===");
            error_log("ID carpeta recibido: " . var_export($id_carpeta, true));
            error_log("ID usuario actual: " . $this->id_usuario);

            // Validar que sea un número entero válido
            if (!is_numeric($id_carpeta)) {
                error_log("Error: ID carpeta no es numérico");
                header('Location: ' . BASE_URL . 'admin');
                exit;
            }

            $id_carpeta = intval($id_carpeta);
            error_log("ID carpeta convertido: " . $id_carpeta);

            $data['title'] = 'Archivos Compartidos';
            $data['id_carpeta'] = $id_carpeta;
            $data['script'] = 'detalle.js';

            // Si el id_carpeta es 1, tratarlo como carpeta raíz universal
            if ($id_carpeta == 1) {
                error_log("Es carpeta raíz universal (ID=1)");
                // Para la carpeta raíz, usar datos del usuario
                $data['carpeta'] = [
                    'id' => 1,
                    'nombre' => 'Carpeta Raíz',
                    'id_usuario' => $this->id_usuario
                ];
            } else {
                error_log("Buscando carpeta específica con ID: " . $id_carpeta);
                // Para carpetas específicas, verificar que exista y pertenezca al usuario
                $data['carpeta'] = $this->model->getCarpeta($id_carpeta, $this->id_usuario);
                error_log("Resultado getCarpeta: " . var_export($data['carpeta'], true));

                if (empty($data['carpeta'])) {
                    error_log("Error: Carpeta no encontrada o no pertenece al usuario");
                    header('Location: ' . BASE_URL . 'admin');
                    exit;
                }
            }

            $data['menu'] = 'admin';
            $data['user'] = $this->model->getUsuario($this->id_usuario);
            $data['shares'] = $this->model->verificarEstado($this->correo);

            error_log("Debug: Datos preparados correctamente, cargando vista");
            $this->views->getView('admin', 'detalle', $data);
        } catch (Exception $e) {
            error_log("EXCEPCIÓN en verdetalle(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Mostrar error en pantalla para debug
            echo "<h1>Error en verdetalle()</h1>";
            echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        }
    }


    // Lista los detalles de los archivos compartidos en una carpeta.
    public function listardetalle($id_carpeta)
    {
        try {
            error_log("=== DEBUG listardetalle() ===");
            error_log("ID carpeta recibido: " . var_export($id_carpeta, true));
            error_log("ID usuario actual: " . $this->id_usuario);

            $id_carpeta = intval($id_carpeta);
            error_log("ID carpeta convertido: " . $id_carpeta);

            // Si el id_carpeta es 1, tratarlo como carpeta raíz (null en BD)
            if ($id_carpeta == 1) {
                $id_carpeta_query = null;
                error_log("Usando carpeta raíz (null) para consulta - ID=1");
            } else {
                $id_carpeta_query = $id_carpeta;
                error_log("Usando carpeta específica: " . $id_carpeta_query);
            }

            error_log("Llamando a getArchivosCompartidos con: " . var_export($id_carpeta_query, true));
            $data = $this->model->getArchivosCompartidos($id_carpeta_query);
            error_log("Datos obtenidos: " . var_export($data, true));

            for ($i = 0; $i < count($data); $i++) {
                if ($data[$i]['estado'] == 0) {
                    $data[$i]['estado'] = '<span class="badge bg-warning">Se elimina ' . $data[$i]['elimina'] . '</span>';
                    $data[$i]['acciones'] = '';
                } else {
                    $data[$i]['estado'] = '<span class="badge bg-success">Compartido</span>';
                    $data[$i]['acciones'] = '<button class="btn btn-danger btn-sm" 
                onclick="eliminarDetalle(' . $data[$i]['id'] . ')">Eliminar</button>';
                }
            }

            error_log("Datos procesados final: " . var_export($data, true));
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("EXCEPCIÓN en listardetalle(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(500);
            echo json_encode([
                'error' => true,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
    // Muestra el dashboard del administrador con estadísticas globales.
    public function dashboard()
    {
        $data['title'] = 'Dashboard';
        $data['script'] = 'files.js';
        $data['active'] = 'dashboard';
        $data['menu'] = 'dashboard';
        $data['user'] = $this->model->getUsuario($this->id_usuario);

        $data['stats'] = $this->model->getEstadisticasGlobales();
        $data['carpetas'] = $this->model->getCarpetasAll();
        $data['archivos'] = $this->model->getArchivosRecientesAll();
        $data['shares'] = $this->model->verificarEstadoAll();
        $data['actividad'] = [
            'fechas' => array_column($this->model->getActividadArchivosAll(), 'fecha'),
            'cantidades' => array_column($this->model->getActividadArchivosAll(), 'cantidad')
        ];
        $data['tipos_archivos'] = $this->model->getTiposArchivos();
        $data['usuarios_activos'] = $this->model->getUsuariosActivos();
        $data['actividad_reciente'] = $this->model->getActividadReciente();
        $data['metricas_sistema'] = $this->model->getMetricasSistema();

        for ($i = 0; $i < count($data['carpetas']); $i++) {
            $data['carpetas'][$i]['color'] = substr(md5($data['carpetas'][$i]['id']), 0, 6);
            $data['carpetas'][$i]['fecha'] = time_ago(strtotime($data['carpetas'][$i]['fecha_create']));
        }
        for ($i = 0; $i < count($data['archivos']); $i++) {
            $data['archivos'][$i]['color'] = substr(md5($data['archivos'][$i]['id']), 0, 6);
            $data['archivos'][$i]['fecha'] = time_ago(strtotime($data['archivos'][$i]['fecha_create']));
            $data['archivos'][$i]['tamano_formateado'] = formatBytes($data['archivos'][$i]['tamano']);
        }
        for ($i = 0; $i < count($data['actividad_reciente']); $i++) {
            $data['actividad_reciente'][$i]['fecha'] = time_ago(strtotime($data['actividad_reciente'][$i]['fecha']));
        }

        $this->views->getView('admin', 'dashboard', $data);
    }

    // Obtiene estadísticas globales del sistema.
    public function estadisticas()
    {
        $stats = $this->model->getEstadisticasGlobales();
        $res = [
            'carpetas' => $stats['total_carpetas'],
            'archivos' => $stats['total_archivos'],
            'compartidos' => $stats['total_compartidos'],
            'usuarios' => $stats['total_usuarios'],
            'espacio_total' => formatBytes($stats['espacio_total']),
            'espacio_porcentaje' => $stats['espacio_porcentaje']
        ];
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Obtiene datos para el dashboard en formato JSON.
    public function dashboardData()
    {
        try {
            $data = [
                'actividad' => [
                    'fechas' => array_column($this->model->getActividadArchivosAll(), 'fecha'),
                    'cantidades' => array_column($this->model->getActividadArchivosAll(), 'cantidad')
                ],
                'tipos_archivos' => $this->model->getTiposArchivos(),
                'usuarios_activos' => $this->model->getUsuariosActivos(),
                'actividad_reciente' => $this->model->getActividadReciente()
            ];

            for ($i = 0; $i < count($data['actividad_reciente']); $i++) {
                $data['actividad_reciente'][$i]['fecha'] = time_ago(strtotime($data['actividad_reciente'][$i]['fecha']));
            }

            header('Content-Type: application/json');
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
        }
        die();
    }

    // Realiza una búsqueda de archivos, carpetas y en la web.
    public function buscar()
    {
        // Asegurar que la respuesta sea JSON
        header('Content-Type: application/json; charset=UTF-8');

        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        if (empty($query)) {
            $res = ['tipo' => 'warning', 'mensaje' => 'Ingresa un término de búsqueda'];
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $results = [];
            $search_term = "%$query%";

            // 1. Búsqueda en archivos
            try {
                $sql_archivos = "SELECT id, nombre, tipo, id_carpeta, 'archivo' AS fuente 
                            FROM archivos 
                            WHERE (nombre LIKE ? OR tipo LIKE ?) 
                            AND id_usuario = ? AND estado = 1
                            ORDER BY nombre ASC
                            LIMIT 10";

                $archivos = $this->model->selectAll($sql_archivos, [$search_term, $search_term, $this->id_usuario]);

                if (!empty($archivos)) {
                    foreach ($archivos as $archivo) {
                        $ruta_archivo = '';
                        if (!empty($archivo['id_carpeta'])) {
                            $ruta_archivo = BASE_URL . 'Assets/archivos/' . $archivo['id_carpeta'] . '/' . $archivo['nombre'];
                        } else {
                            $ruta_archivo = BASE_URL . 'Assets/archivos/' . $archivo['nombre'];
                        }

                        $results[] = [
                            'id' => $archivo['id'],
                            'nombre' => $archivo['nombre'],
                            'tipo' => $archivo['tipo'],
                            'url' => $ruta_archivo,
                            'fuente' => 'archivo',
                            'descripcion' => 'Archivo: ' . $archivo['nombre'],
                            'icono' => 'description'
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Error en búsqueda de archivos: " . $e->getMessage());
            }

            // 2. Búsqueda en carpetas
            try {
                $sql_carpetas = "SELECT id, nombre, 'carpeta' AS tipo, id_carpeta_padre AS id_carpeta, 'carpeta' AS fuente 
                            FROM carpetas 
                            WHERE nombre LIKE ? 
                            AND id_usuario = ? AND estado = 1
                            ORDER BY nombre ASC
                            LIMIT 10";

                $carpetas = $this->model->selectAll($sql_carpetas, [$search_term, $this->id_usuario]);

                if (!empty($carpetas)) {
                    foreach ($carpetas as $carpeta) {
                        $results[] = [
                            'id' => $carpeta['id'],
                            'nombre' => $carpeta['nombre'],
                            'tipo' => 'carpeta',
                            'url' => BASE_URL . 'admin/ver/' . $carpeta['id'],
                            'fuente' => 'carpeta',
                            'descripcion' => 'Carpeta: ' . $carpeta['nombre'],
                            'icono' => 'folder'
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Error en búsqueda de carpetas: " . $e->getMessage());
            }

            // 3. Búsqueda web con Google Custom Search
            if (
                defined('GOOGLE_API_KEY') && defined('GOOGLE_CX_ID') &&
                !empty(GOOGLE_API_KEY) && !empty(GOOGLE_CX_ID)
            ) {

                try {
                    $api_key = GOOGLE_API_KEY;
                    $cx = GOOGLE_CX_ID;
                    $google_url = "https://www.googleapis.com/customsearch/v1?key=" . $api_key .
                        "&cx=" . $cx . "&q=" . urlencode($query) . "&num=5";

                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 10,
                            'method' => 'GET',
                            'header' => 'User-Agent: GestorDocs/1.0'
                        ]
                    ]);

                    $google_response = @file_get_contents($google_url, false, $context);

                    if ($google_response !== false) {
                        $google_results = json_decode($google_response, true);

                        if (isset($google_results['items']) && is_array($google_results['items'])) {
                            foreach (array_slice($google_results['items'], 0, 3) as $item) {
                                $results[] = [
                                    'id' => null,
                                    'nombre' => isset($item['title']) ? htmlspecialchars($item['title']) : 'Sin título',
                                    'tipo' => 'web',
                                    'url' => isset($item['link']) ? $item['link'] : '#',
                                    'fuente' => 'web',
                                    'descripcion' => isset($item['snippet']) ?
                                        htmlspecialchars(substr($item['snippet'], 0, 100)) . '...' :
                                        'Resultado web',
                                    'icono' => 'language'
                                ];
                            }
                        }
                    } else {
                        error_log("Error al conectar con Google API o respuesta vacía");
                    }
                } catch (Exception $web_error) {
                    error_log("Error en búsqueda web: " . $web_error->getMessage());
                }
            }

            // Preparar respuesta
            if (empty($results)) {
                $res = [
                    'tipo' => 'info',
                    'mensaje' => 'No se encontraron resultados para "' . htmlspecialchars($query) . '"',
                    'results' => [],
                    'total' => 0
                ];
            } else {
                $res = [
                    'tipo' => 'success',
                    'mensaje' => 'Se encontraron ' . count($results) . ' resultados',
                    'results' => $results,
                    'total' => count($results),
                    'query' => htmlspecialchars($query)
                ];
            }
        } catch (Exception $e) {
            error_log("Error general en búsqueda: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            $res = [
                'tipo' => 'error',
                'mensaje' => 'Error en la búsqueda. Por favor, inténtalo de nuevo.',
                'results' => [],
                'error_detail' => $e->getMessage()
            ];
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Obtiene las notificaciones del usuario.
    public function getNotificaciones()
    {
        $notificaciones = $this->model->getNotificaciones($this->id_usuario);
        echo json_encode(['tipo' => 'success', 'notificaciones' => $notificaciones], JSON_UNESCAPED_UNICODE);
        die();
    }

    // Marca una notificación como leída.
    public function marcarNotificacionLeida()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_notificacion'])) {
            $id_notificacion = intval($_POST['id_notificacion']);
            $result = $this->model->marcarNotificacionLeida($id_notificacion, $this->id_usuario);
            if ($result) {
                echo json_encode(['tipo' => 'success', 'mensaje' => 'Notificación marcada como leída']);
            } else {
                echo json_encode(['tipo' => 'error', 'mensaje' => 'Error al marcar la notificación']);
            }
        } else {
            echo json_encode(['tipo' => 'error', 'mensaje' => 'Solicitud inválida']);
        }
        die();
    }
}
