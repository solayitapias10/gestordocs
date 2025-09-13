<?php
class GoogleModel extends Query
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Guarda o actualiza los tokens de Google para un usuario específico.
     */
    public function guardarTokensGoogle($id_usuario, $accessTokenArray, $refreshToken)
    {
        // Convertimos el array del access token a un string JSON para guardarlo
        $accessTokenJson = json_encode($accessTokenArray);

        if ($refreshToken) {
            // Si obtenemos un nuevo refresh token, lo actualizamos también
            $sql = "UPDATE usuarios SET google_access_token = ?, google_refresh_token = ? WHERE id = ?";
            $datos = array($accessTokenJson, $refreshToken, $id_usuario);
        } else {
            // Si no, solo actualizamos el access token
            $sql = "UPDATE usuarios SET google_access_token = ? WHERE id = ?";
            $datos = array($accessTokenJson, $id_usuario);
        }
        return $this->save($sql, $datos);
    }
}
