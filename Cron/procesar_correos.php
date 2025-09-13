<?php
/***********************************************************
 * SCRIPT DE TAREAS PROGRAMADAS (CRON JOB)
 * VERSIÓN FINAL ESTABLE
 ***********************************************************/

// PASO 1: Cargar la configuración. Esto nos da ROOT_PATH y lo demás.
// Usamos una ruta absoluta desde este mismo archivo para encontrar Config.php
require_once dirname(__DIR__) . '/Config/Config.php';

// PASO 2: Cargar todas las demás dependencias usando la constante ROOT_PATH
require_once ROOT_PATH . 'Config/App/Conexion.php';
require_once ROOT_PATH . 'Config/App/Query.php';
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'Models/UsuariosModel.php';
require_once ROOT_PATH . 'Models/ArchivosModel.php';
require_once ROOT_PATH . 'Services/GmailReaderService.php';

// --- Configuración de Logs ---
$logFile = ROOT_PATH . 'Cron/cron_log.txt';
if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) { // 5 MB
    file_put_contents($logFile, '');
}
function write_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

write_log("===== INICIANDO PROCESO DE CORREOS =====");

// --- Instanciar Objetos ---
$usuariosModel = new UsuariosModel();
$archivosModel = new ArchivosModel();
$gmailService = new GmailReaderService();

// --- Obtener Usuarios con Gmail Conectado ---
$usuarios = $usuariosModel->getUsuariosConGoogleConectado();
if (empty($usuarios)) {
    write_log("No hay usuarios con cuentas de Gmail conectadas. Finalizando.");
    exit;
}

write_log("Se encontraron " . count($usuarios) . " usuarios para procesar.");

// --- Procesar cada Usuario ---
foreach ($usuarios as $usuario) {
    $id_usuario = $usuario['id'];
    write_log("\n--- Procesando usuario ID: $id_usuario ---");

    $query = 'subject:"FACTURA" has:attachment is:unread';
    $mensajes = $gmailService->buscarCorreos($id_usuario, $query);

    if (empty($mensajes)) {
        write_log("No se encontraron correos nuevos para este usuario.");
        continue;
    }

    write_log("Se encontraron " . count($mensajes) . " correos.");

    $nombreCarpeta = 'Facturas de Gmail';
    $carpetaDestino = $archivosModel->getCarpetaPorNombre($id_usuario, $nombreCarpeta);
    $idCarpetaDestino = !empty($carpetaDestino) ? $carpetaDestino['id'] : $archivosModel->crearCarpeta($id_usuario, $nombreCarpeta);

    foreach ($mensajes as $mensaje) {
        $messageId = $mensaje->getId();
        write_log("Procesando mensaje ID: $messageId");
        $adjuntos = $gmailService->obtenerAdjuntosDeCorreo($id_usuario, $messageId);

        foreach ($adjuntos as $adjunto) {
            if ($archivosModel->archivoYaExiste($messageId, $adjunto['filename'])) {
                write_log(" -> OMITIENDO: El adjunto '" . $adjunto['filename'] . "' ya fue registrado.");
                continue;
            }

            $rutaCarpeta = ROOT_PATH . 'Assets/archivos/' . $id_usuario . '/' . $idCarpetaDestino;
            if (!is_dir($rutaCarpeta)) {
                mkdir($rutaCarpeta, 0775, true);
            }

            $rutaCompleta = $rutaCarpeta . '/' . $adjunto['filename'];
            if (file_put_contents($rutaCompleta, $adjunto['data'])) {
                write_log(" -> Adjunto '" . $adjunto['filename'] . "' guardado.");
                $tipo = mime_content_type($rutaCompleta);
                $tamano = filesize($rutaCompleta);
                $archivosModel->registrarArchivo($adjunto['filename'], $tipo, $idCarpetaDestino, $id_usuario, $tamano, $messageId);
                write_log(" -> Adjunto registrado en la BD.");
            } else {
                write_log(" -> ERROR: No se pudo escribir el adjunto en '$rutaCompleta'.");
            }
        }
        $gmailService->marcarCorreoComoLeido($id_usuario, $messageId);
    }
}

write_log("===== PROCESO FINALIZADO =====\n");