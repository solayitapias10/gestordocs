<?php
/********************************************
 * Archivo php GmailReaderService.php
 * SERVICIO PARA LEER CORREOS Y ADJUNTOS DE GMAIL
 * VERSIÓN FINAL Y ESTABLE
 ********************************************/

require_once ROOT_PATH .'vendor/autoload.php';

class GmailReaderService
{
    private $userModel;
    private $logFunctionExists;

    public function __construct()
    {
        $this->logFunctionExists = function_exists('write_log');

        if (!class_exists('UsuariosModel')) {
            if (defined('ROOT_PATH')) {
                require_once ROOT_PATH . 'Models/UsuariosModel.php';
            }
        }
        $this->userModel = new UsuariosModel();
    }
    
    private function log($message) {
        if ($this->logFunctionExists) {
            write_log($message);
        }
    }

    private function getAuthenticatedClient($id_usuario)
    {
        $usuario = $this->userModel->getUsuario($id_usuario);
        if (empty($usuario) || empty($usuario['google_refresh_token'])) {
            $this->log("GMAIL_SERVICE: ERROR - No se encontró un refresh_token para el usuario ID: $id_usuario.");
            return null;
        }

        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $client->setAccessType('offline');

        $accessToken = json_decode($usuario['google_access_token'], true);
        $client->setAccessToken($accessToken);
        
        if ($client->isAccessTokenExpired()) {
            $this->log("GMAIL_SERVICE: Access Token expirado para usuario ID: $id_usuario. Renovando...");
            try {
                $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                if (isset($newAccessToken['error'])) {
                     $this->log("GMAIL_SERVICE: ERROR al renovar token: " . json_encode($newAccessToken));
                     return null;
                }
                $this->userModel->actualizarAccessToken($id_usuario, json_encode($newAccessToken));
                $client->setAccessToken($newAccessToken);
                $this->log("GMAIL_SERVICE: Token renovado con éxito.");
            } catch (Exception $e) {
                $this->log("GMAIL_SERVICE: EXCEPCIÓN al renovar token: " . $e->getMessage());
                return null;
            }
        }
        return $client;
    }

    public function buscarCorreos($id_usuario, $query)
    {
        $client = $this->getAuthenticatedClient($id_usuario);
        if (!$client) return [];

        try {
            $service = new Google_Service_Gmail($client);
            // Usamos 'me' para referirnos al usuario autenticado
            $response = $service->users_messages->listUsersMessages('me', ['q' => $query]);
            return $response->getMessages();
        } catch (Exception $e) {
            $this->log("BUSCAR_CORREOS: EXCEPCIÓN: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerAdjuntosDeCorreo($id_usuario, $messageId)
    {
        $client = $this->getAuthenticatedClient($id_usuario);
        if (!$client) return [];

        $adjuntos = [];
        try {
            $service = new Google_Service_Gmail($client);
            // Usamos 'me' para referirnos al usuario autenticado
            $message = $service->users_messages->get('me', $messageId);
            $parts = $message->getPayload()->getParts();

            foreach ($parts as $part) {
                if ($part->getFilename() && $part->getBody() && $part->getBody()->getAttachmentId()) {
                    $attachment = $service->users_messages_attachments->get('me', $messageId, $part->getBody()->getAttachmentId());
                    $data = strtr($attachment->getData(), '-_', '+/');
                    $adjuntos[] = [
                        'filename' => $part->getFilename(),
                        'data' => base64_decode($data)
                    ];
                }
            }
        } catch (Exception $e) {
            $this->log("OBTENER_ADJUNTOS: EXCEPCIÓN para mensaje $messageId: " . $e->getMessage());
        }
        return $adjuntos;
    }

    public function marcarCorreoComoLeido($id_usuario, $messageId)
    {
        $client = $this->getAuthenticatedClient($id_usuario);
        if (!$client) return;

        try {
            $service = new Google_Service_Gmail($client);
            $mods = new Google_Service_Gmail_ModifyMessageRequest();
            $mods->setRemoveLabelIds(['UNREAD']);
            // Usamos 'me' para referirnos al usuario autenticado
            $service->users_messages->modify('me', $messageId, $mods);
        } catch (Exception $e) {
            $this->log("MARCAR_LEIDO: EXCEPCIÓN para mensaje $messageId: " . $e->getMessage());
        }
    }
}