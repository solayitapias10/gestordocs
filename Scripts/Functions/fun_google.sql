-- =====================================================
-- FUNCIONES GOOGLE
-- =====================================================

-- 1. getUsuariosConGoogleConectado
-- Devuelve los usuarios que han vinculado su cuenta de Google, incluyendo sus tokens de acceso y actualización.
CREATE OR REPLACE FUNCTION obtener_usuarios_google_conectado()
RETURNS TABLE (
    id_usuario              usuarios.id%TYPE,
    google_access_token     usuarios.google_access_token%TYPE,
    google_refresh_token    usuarios.google_refresh_token%TYPE
) AS $$
BEGIN
    -- Retorna los usuarios que han vinculado su cuenta de Google
    RETURN QUERY
    SELECT
        u.id,
        u.google_access_token,
        u.google_refresh_token
    FROM
        usuarios u
    WHERE
        u.google_refresh_token IS NOT NULL;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuarios con Google conectado: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

--2 . actualizarAccessToken
-- Actualiza el token de acceso de Google para un usuario específico en la tabla usuarios.
CREATE OR REPLACE FUNCTION actualizar_token_acceso_usuario(
    p_id_usuario IN usuarios.id%TYPE,
    p_token_acceso_json IN usuarios.google_access_token%TYPE
)
RETURNS VOID AS $$
BEGIN
    UPDATE usuarios
    SET google_access_token = p_token_acceso_json
    WHERE id = p_id_usuario;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar el token de acceso para el usuario ID %: %', p_id_usuario, SQLERRM;
END;
$$ LANGUAGE plpgsql;

--3. guardarTokensGoogle
-- Esta función actualiza los tokens de acceso y refresco de Google para un usuario específico en la tabla usuarios.
CREATE OR REPLACE FUNCTION guardar_tokens_google_usuario(
    p_id_usuario IN usuarios.id%TYPE,
    p_token_acceso_json IN usuarios.google_access_token%TYPE,
    p_token_refresco IN usuarios.google_refresh_token%TYPE DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    -- Actualiza el token de acceso para el usuario especificado
    UPDATE usuarios
    SET google_access_token = p_token_acceso_json
    WHERE id = p_id_usuario;

    -- Si se proporciona un nuevo token de refresco, actualízalo también
    IF p_token_refresco IS NOT NULL THEN
        UPDATE usuarios
        SET google_refresh_token = p_token_refresco
        WHERE id = p_id_usuario;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al guardar los tokens de Google para el usuario ID %: %', p_id_usuario, SQLERRM;
END;
$$ LANGUAGE plpgsql;