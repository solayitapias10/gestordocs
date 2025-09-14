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
    public function guardarTokensGoogle($id_usuario, $accessTokenJson, $refreshToken)
    {
        $sql = "SELECT guardar_tokens_google_usuario(?, ?, ?)";
        $datos = array($id_usuario, $accessTokenJson, $refreshToken);
        return $this->save($sql, $datos);
    }
}
