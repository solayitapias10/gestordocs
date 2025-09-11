<?php

/********************************************
Archivo php EmailService.php                         
Servicio de envío de correos electrónicos
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';
require_once './Config/Config.php';

class EmailService
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }

    /**
     * Configura los parámetros SMTP
     */
    private function configurarSMTP()
    {
        try {
            // Configuración del servidor
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = SMTP_AUTH;
            $this->mail->Username = SMTP_USERNAME;
            $this->mail->Password = SMTP_PASSWORD;
            $this->mail->SMTPSecure = SMTP_SECURE;
            $this->mail->Port = SMTP_PORT;

            // Configuración del correo
            $this->mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $this->mail->CharSet = MAIL_CHARSET;
            $this->mail->isHTML(MAIL_IS_HTML);

            // Para debugging (quitar en producción)
            // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;

        } catch (Exception $e) {
            error_log("Error configurando SMTP: " . $e->getMessage());
        }
    }

    /**
     * Envía email de solicitud aprobada
     */
    public function enviarSolicitudAprobada($destinatario, $nombre, $apellido, $correo, $contraseña)
    {
        try {
            // Destinatario
            $this->mail->addAddress($destinatario, $nombre . ' ' . $apellido);

            // Asunto
            $this->mail->Subject = '¡Tu solicitud ha sido aprobada!';

            // Cuerpo del mensaje
            $cuerpoHtml = $this->generarPlantillaAprobacion($nombre, $apellido, $correo, $contraseña);
            $this->mail->Body = $cuerpoHtml;

            // Versión texto plano
            $this->mail->AltBody = $this->generarTextoPlano($nombre, $apellido, $correo, $contraseña);

            // Enviar
            $resultado = $this->mail->send();

            // Limpiar destinatarios para próximo envío
            $this->mail->clearAddresses();

            return $resultado;
        } catch (Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera la plantilla HTML para solicitud aprobada
     */
    private function generarPlantillaAprobacion($nombre, $apellido, $correo, $contraseña)
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Solicitud Aprobada</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credentials { background-color: #e8f5e8; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #4CAF50; }
                .button { display: inline-block; background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Bienvenido al Sistema!</h1>
                    <p>Tu solicitud ha sido aprobada</p>
                </div>
                
                <div class='content'>
                    <h2>Hola {$nombre} {$apellido},</h2>
                    
                    <p>¡Excelentes noticias! Tu solicitud para acceder al Sistema de Gestión de Archivos ha sido <strong>aprobada</strong>.</p>
                    
                    <p>Ya puedes acceder al sistema utilizando las siguientes credenciales:</p>
                    
                    <div class='credentials'>
                        <h3>Tus credenciales de acceso:</h3>
                        <p><strong>Usuario (Correo):</strong> {$correo}</p>
                        <p><strong>Contraseña:</strong> {$contraseña}</p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong> Por seguridad, te recomendamos cambiar tu contraseña después del primer inicio de sesión.
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='" . BASE_URL . "' class='button'>Acceder al Sistema</a>
                    </p>
                    
                    <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
                    
                    <p>¡Bienvenido a bordo!</p>
                </div>
                
                <div class='footer'>
                    <p>Este correo fue enviado automáticamente. Por favor, no respondas a este mensaje.</p>
                    <p>&copy; " . date('Y') . " Sistema de Gestión de Archivos - SENA</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Genera versión texto plano del email
     */
    private function generarTextoPlano($nombre, $apellido, $correo, $contraseña)
    {
        return "
¡SOLICITUD APROBADA!

Hola {$nombre} {$apellido},

¡Excelentes noticias! Tu solicitud para acceder al Sistema de Gestión de Archivos ha sido APROBADA.

TUS CREDENCIALES DE ACCESO:
Usuario (Correo): {$correo}
Contraseña: {$contraseña}

IMPORTANTE: Por seguridad, te recomendamos cambiar tu contraseña después del primer inicio de sesión.

Puedes acceder al sistema en: " . BASE_URL . "

Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.

¡Bienvenido a bordo!

---
Este correo fue enviado automáticamente. Por favor, no respondas a este mensaje.
© " . date('Y') . " Sistema de Gestión de Archivos - SENA
        ";
    }

    /**
     * Envía email de solicitud rechazada
     */
    public function enviarSolicitudRechazada($destinatario, $nombre, $apellido)
    {
        try {
            $this->mail->addAddress($destinatario, $nombre . ' ' . $apellido);
            $this->mail->Subject = 'Actualización sobre tu solicitud';

            $cuerpoHtml = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f44336; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Actualización de Solicitud</h1>
                    </div>
                    <div class='content'>
                        <h2>Hola {$nombre} {$apellido},</h2>
                        <p>Lamentamos informarte que tu solicitud para acceder al Sistema de Gestión de Archivos no ha sido aprobada en esta ocasión.</p>
                        <p>Si deseas más información sobre esta decisión, puedes contactarnos.</p>
                        <p>Gracias por tu interés.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Sistema de Gestión de Archivos - SENA</p>
                    </div>
                </div>
            </body>
            </html>";

            $this->mail->Body = $cuerpoHtml;
            $resultado = $this->mail->send();
            $this->mail->clearAddresses();

            return $resultado;
        } catch (Exception $e) {
            error_log("Error enviando email de rechazo: " . $e->getMessage());
            return false;
        }
    }
}
