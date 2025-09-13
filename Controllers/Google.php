<?php

/********************************************
 * Archivo php Google.php
 * Controlador para la integración con Google API
 * VERSIÓN CORREGIDA PARA AUTHMANAGER (JWT)
 ********************************************/

// No es necesario requerir estos archivos si ya usas un autoload
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'Controllers/AuthManager.php'; // Asegúrate de que la ruta sea correcta

class Google extends Controller
{
    private $authManager;

    public function __construct()
    {
        parent::__construct();
        $this->authManager = new AuthManager(); // Usas tu AuthManager
    }

    /**
     * Inicia el proceso de autenticación con Google.
     */
    public function conectar()
    {
        // 1. Validar que el usuario esté autenticado usando AuthManager
        $validacion = $this->authManager->middleware(true); // Usamos tu método

        if (!$validacion['valido']) {
            header('Location: ' . BASE_URL . '?error=not_authenticated');
            exit();
        }

        // Obtenemos el ID del usuario autenticado
        $id_usuario = $validacion['id_usuario'];

        // 2. Crear una instancia del cliente de Google
        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $client->addScope('https://www.googleapis.com/auth/gmail.modify');
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        // 3. ¡LA PARTE CLAVE! Pasar el ID del usuario en el parámetro 'state'
        $client->setState($id_usuario);

        // 4. Generar la URL y redirigir
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit();
    }

    /**
     * Maneja el callback de Google después de la autorización.
     */
    public function oauth_callback()
    {
        if (isset($_GET['code']) && isset($_GET['state'])) {
            $client = new Google_Client();
            $client->setClientId(GOOGLE_CLIENT_ID);
            $client->setClientSecret(GOOGLE_CLIENT_SECRET);
            $client->setRedirectUri(GOOGLE_REDIRECT_URI);

            $id_usuario = $_GET['state'];
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']); // $token es un array completo

            if (!isset($token['error'])) {
                // ¡CAMBIO IMPORTANTE! Pasamos el array $token completo al modelo.
                $refreshToken = isset($token['refresh_token']) ? $token['refresh_token'] : null;

                if (!class_exists('GoogleModel')) {
                    require_once ROOT_PATH . 'Models/GoogleModel.php';
                }
                $googleModel = new GoogleModel();
                // Pasamos el array $token y el $refreshToken (si existe)
                $googleModel->guardarTokensGoogle($id_usuario, $token, $refreshToken);

                header('Location: ' . BASE_URL . 'usuarios/perfil?success=google_connected');
                exit();
            }
        }
        header('Location: ' . BASE_URL . 'usuarios/perfil?error=google_failed');
        exit();
    }
}
