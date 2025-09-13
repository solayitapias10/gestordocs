<?php
require_once 'Config/Config.php';
require_once 'Config/App/Conexion.php';
require_once 'Config/App/Query.php';
require_once 'vendor/autoload.php';
require_once 'Models/UsuariosModel.php';

echo "=== DIAGNÓSTICO DE TOKENS DE GOOGLE ===\n";

$usuariosModel = new UsuariosModel();
$usuarios = $usuariosModel->getUsuariosConGoogleConectado();

if (empty($usuarios)) {
    echo "No hay usuarios con Google conectado.\n";
    exit;
}

foreach ($usuarios as $usuario) {
    echo "\n--- Usuario ID: {$usuario['id']} ---\n";
    echo "Email: {$usuario['email']}\n";

    // Verificar si tiene tokens
    $tieneAccessToken = !empty($usuario['google_access_token']);
    $tieneRefreshToken = !empty($usuario['google_refresh_token']);

    echo "Tiene Access Token: " . ($tieneAccessToken ? 'SÍ' : 'NO') . "\n";
    echo "Tiene Refresh Token: " . ($tieneRefreshToken ? 'SÍ' : 'NO') . "\n";

    if (!$tieneAccessToken) {
        echo "❌ SIN TOKENS - Usuario debe reconectar Google\n";
        continue;
    }

    // Intentar validar el token
    try {
        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);

        $accessTokenArray = json_decode($usuario['google_access_token'], true);
        if (!$accessTokenArray) {
            echo "❌ TOKEN INVÁLIDO - Formato JSON corrupto\n";
            continue;
        }

        $client->setAccessToken($accessTokenArray);

        if ($client->isAccessTokenExpired()) {
            echo "⚠️  TOKEN EXPIRADO\n";

            if ($tieneRefreshToken) {
                echo "Intentando renovar con refresh token...\n";

                try {
                    $newTokens = $client->fetchAccessTokenWithRefreshToken($usuario['google_refresh_token']);

                    if (isset($newTokens['error'])) {
                        echo "❌ ERROR RENOVANDO: {$newTokens['error_description']}\n";
                    } else {
                        echo "✅ TOKEN RENOVADO EXITOSAMENTE\n";

                        // Aquí podrías guardar el nuevo token si quisieras
                        // $googleModel->guardarTokensGoogle($usuario['id'], $newTokens, null);
                    }
                } catch (Exception $e) {
                    echo "❌ EXCEPCIÓN AL RENOVAR: " . $e->getMessage() . "\n";
                }
            } else {
                echo "❌ NO HAY REFRESH TOKEN - Usuario debe reconectar\n";
            }
        } else {
            echo "✅ TOKEN VÁLIDO Y ACTIVO\n";

            // Probar acceso a Gmail
            try {
                $service = new Google_Service_Gmail($client);
                $profile = $service->users->getProfile('me');
                echo "✅ ACCESO A GMAIL CONFIRMADO - " . $profile->getEmailAddress() . "\n";
            } catch (Exception $e) {
                echo "❌ ERROR ACCEDIENDO A GMAIL: " . $e->getMessage() . "\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
