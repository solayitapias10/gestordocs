<?php

/********************************************
Archivo php RecuperarController.php                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/


require_once ROOT_PATH . 'Config/Config.php';
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'Controllers/AuthManager.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Recuperar extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // Muestra la vista para solicitar recuperación de contraseña
    public function index()
    {
        $data['title'] = 'Recuperar Contraseña';
        $this->views->getView('recuperar', 'solicitar', $data);
    }

    // Muestra la vista para restablecer contraseña con token
    public function restablecer($token = null)
    {
        if (empty($token)) {
            header('Location: ' . BASE_URL . 'principal/index');
            exit;
        }

        // Validar token
        $tokenData = $this->model->validarToken($token);
        if (!$tokenData) {
            $data['title'] = 'Token Inválido';
            $data['error'] = 'El enlace de recuperación no es válido o ha expirado.';
            $this->views->getView('recuperar', 'error', $data);
            return;
        }

        $data['title'] = 'Restablecer Contraseña';
        $data['token'] = $token;
        $data['usuario'] = $tokenData;
        $this->views->getView('recuperar', 'restablecer', $data);
    }

    // Procesa la solicitud de recuperación de contraseña
public function solicitar()
{
    // Si es GET, redirigir a la vista principal
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return $this->index(); // Mostrar el formulario
    }
    
    // Si es POST, procesar la solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $res = array('tipo' => 'error', 'mensaje' => 'Método no permitido');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    $correo = trim($_POST['correo'] ?? '');

    // Validaciones
    if (empty($correo)) {
        $res = array('tipo' => 'warning', 'mensaje' => 'El correo electrónico es requerido');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $res = array('tipo' => 'warning', 'mensaje' => 'Formato de correo electrónico inválido');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Verificar si el usuario existe
    $usuario = $this->model->getUsuarioCorreo($correo);
    if (!$usuario) {
        // Por seguridad, siempre devolver éxito aunque el correo no exista
        $res = array(
            'tipo' => 'success', 
            'mensaje' => 'Si el correo está registrado, recibirás instrucciones para recuperar tu contraseña'
        );
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Invalidar tokens anteriores del usuario
    $this->model->invalidarTokensUsuario($usuario['id']);

    // Generar nuevo token
    $token = bin2hex(random_bytes(32));
    $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 hora de validez

    // Guardar token en base de datos
    $ipSolicitud = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $tokenGuardado = $this->model->guardarToken(
        $usuario['id'], 
        $token, 
        $fechaExpiracion, 
        $ipSolicitud, 
        $userAgent
    );

    if (!$tokenGuardado) {
        $res = array('tipo' => 'error', 'mensaje' => 'Error interno. Inténtalo más tarde');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Enviar correo de recuperación
    $enlaceRecuperacion = BASE_URL . 'recuperar/restablecer/' . $token;
    $emailEnviado = $this->enviarCorreoRecuperacion($usuario, $enlaceRecuperacion);

    if ($emailEnviado) {
        $res = array(
            'tipo' => 'success',
            'mensaje' => 'Se han enviado las instrucciones de recuperación a tu correo electrónico'
        );
    } else {
        $res = array('tipo' => 'error', 'mensaje' => 'Error al enviar el correo. Inténtalo más tarde');
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    die();
}

    // Procesa el restablecimiento de contraseña
    public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $res = array('tipo' => 'error', 'mensaje' => 'Método no permitido');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        $token = trim($_POST['token'] ?? '');
        $claveNueva = $_POST['claveNueva'] ?? '';
        $claveConfirmar = $_POST['claveConfirmar'] ?? '';

        // Validaciones
        if (empty($token) || empty($claveNueva) || empty($claveConfirmar)) {
            $res = array('tipo' => 'warning', 'mensaje' => 'Todos los campos son requeridos');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($claveNueva !== $claveConfirmar) {
            $res = array('tipo' => 'warning', 'mensaje' => 'Las contraseñas no coinciden');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        if (strlen($claveNueva) < 8) {
            $res = array('tipo' => 'warning', 'mensaje' => 'La contraseña debe tener al menos 8 caracteres');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar token
        $tokenData = $this->model->validarToken($token);
        if (!$tokenData) {
            $res = array('tipo' => 'error', 'mensaje' => 'Token inválido o expirado');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Actualizar contraseña
        $hashNueva = password_hash($claveNueva, PASSWORD_DEFAULT);
        $resultado = $this->model->actualizarContrasena($tokenData['id_usuario'], $hashNueva);

        if ($resultado) {
            // Marcar token como usado
            $this->model->marcarTokenUsado($token);

            // Enviar notificación de cambio exitoso
            $this->enviarNotificacionCambio($tokenData);

            $res = array(
                'tipo' => 'success',
                'mensaje' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión'
            );
        } else {
            $res = array('tipo' => 'error', 'mensaje' => 'Error al actualizar la contraseña');
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Envía correo de recuperación de contraseña
    private function enviarCorreoRecuperacion($usuario, $enlace)
    {
        try {
            $mail = new PHPMailer(true);

            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            // Configuración del correo
            $mail->setFrom(SMTP_USERNAME, 'GestorDocs - Sistema de Archivos');
            $mail->addAddress($usuario['correo'], $usuario['nombre'] . ' ' . $usuario['apellido']);
            $mail->isHTML(true);

            $mail->Subject = 'Recuperación de Contraseña - GestorDocs';

            // Plantilla HTML del correo
            $mail->Body = $this->generarPlantillaRecuperacion($usuario, $enlace);

            // Texto alternativo
            $mail->AltBody = "Hola {$usuario['nombre']},\n\n" .
                "Has solicitado recuperar tu contraseña en GestorDocs.\n\n" .
                "Para restablecer tu contraseña, haz clic en el siguiente enlace:\n" .
                "{$enlace}\n\n" .
                "Este enlace es válido por 1 hora.\n\n" .
                "Si no solicitaste este cambio, puedes ignorar este correo.\n\n" .
                "Equipo GestorDocs";

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Error enviando correo de recuperación: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Envía notificación de cambio exitoso
    private function enviarNotificacionCambio($tokenData)
    {
        try {
            $usuario = $this->model->getUsuarioPorId($tokenData['id_usuario']);
            if (!$usuario) return false;

            $mail = new PHPMailer(true);

            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            // Configuración del correo
            $mail->setFrom(SMTP_USERNAME, 'GestorDocs - Sistema de Archivos');
            $mail->addAddress($usuario['correo'], $usuario['nombre'] . ' ' . $usuario['apellido']);
            $mail->isHTML(true);

            $mail->Subject = 'Contraseña Actualizada - GestorDocs';

            // Plantilla HTML del correo
            $mail->Body = $this->generarPlantillaConfirmacion($usuario);

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Error enviando notificación de cambio: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Genera la plantilla HTML para el correo de recuperación
    private function generarPlantillaRecuperacion($usuario, $enlace)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Recuperar Contraseña</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🔒 Recuperar Contraseña</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">GestorDocs - Sistema de Archivos</p>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h2 style="color: #333; margin-top: 0;">Hola ' . htmlspecialchars($usuario['nombre']) . ',</h2>
                
                <p>Has solicitado recuperar tu contraseña en <strong>GestorDocs</strong>.</p>
                
                <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $enlace . '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px;">
                        🔑 Restablecer Contraseña
                    </a>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <strong>⚠️ Importante:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Este enlace es válido por <strong>1 hora</strong></li>
                        <li>Solo puedes usarlo una vez</li>
                        <li>Si no solicitaste este cambio, ignora este correo</li>
                    </ul>
                </div>
                
                <p style="font-size: 14px; color: #666; margin-top: 30px;">
                    Si tienes problemas con el botón, copia y pega el siguiente enlace en tu navegador:<br>
                    <a href="' . $enlace . '" style="color: #667eea; word-break: break-all;">' . $enlace . '</a>
                </p>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="font-size: 12px; color: #999; text-align: center; margin: 0;">
                    Este correo fue enviado automáticamente desde GestorDocs<br>
                    Equipo GAES 1 - SENA CSET ADSO 2025
                </p>
            </div>
        </body>
        </html>';
    }

    // Genera la plantilla HTML para la confirmación de cambio
    private function generarPlantillaConfirmacion($usuario)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Contraseña Actualizada</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">✅ Contraseña Actualizada</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">GestorDocs - Sistema de Archivos</p>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h2 style="color: #333; margin-top: 0;">Hola ' . htmlspecialchars($usuario['nombre']) . ',</h2>
                
                <p>Tu contraseña ha sido <strong>actualizada exitosamente</strong> en GestorDocs.</p>
                
                <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;">
                    <strong>🎉 ¡Listo!</strong> Ya puedes iniciar sesión con tu nueva contraseña.
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . BASE_URL . 'principal/index" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px;">
                        🏠 Ir al Login
                    </a>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <strong>🔐 Por tu seguridad:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>No compartas tu contraseña con nadie</li>
                        <li>Usa contraseñas únicas y seguras</li>
                        <li>Si no hiciste este cambio, contacta soporte inmediatamente</li>
                    </ul>
                </div>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="font-size: 12px; color: #999; text-align: center; margin: 0;">
                    Fecha: ' . date('d/m/Y H:i:s') . '<br>
                    Este correo fue enviado automáticamente desde GestorDocs<br>
                    Equipo GAES 1 - SENA CSET ADSO 2025
                </p>
            </div>
        </body>
        </html>';
    }
}