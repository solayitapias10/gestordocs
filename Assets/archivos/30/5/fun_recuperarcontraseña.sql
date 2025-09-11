-- =====================================================
-- FUNCIONES RECUPERAR CONTRASEÑA
-- =====================================================

-- 1. Función para invalidar tokens de usuario
CREATE OR REPLACE FUNCTION invalidar_tokens_usuario(
    usuario_id usuarios.id%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    tokens_invalidados INTEGER;
    tokens_activos INTEGER;
    usuario_existe BOOLEAN := FALSE;
BEGIN
    -- Validar parámetros de entrada
    IF usuario_id IS NULL OR usuario_id <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido: %', usuario_id;
    END IF;

    -- Verificar que el usuario existe
    SELECT EXISTS(SELECT 1 FROM usuarios WHERE id = usuario_id) INTO usuario_existe;
    
    IF NOT usuario_existe THEN
        RAISE EXCEPTION 'Usuario no encontrado con ID: %', usuario_id;
    END IF;

    -- Contar tokens activos antes de invalidar
    SELECT COUNT(*) INTO tokens_activos 
    FROM tokens_recuperacion 
    WHERE id_usuario = usuario_id 
    AND usado = 0 
    AND fecha_expiracion > CURRENT_TIMESTAMP;

    -- Si no hay tokens activos, informar y retornar 0
    IF tokens_activos = 0 THEN
        RAISE NOTICE 'No se encontraron tokens activos para el usuario ID: %', usuario_id;
        RETURN 0;
    END IF;

    -- Invalidar tokens del usuario
    UPDATE tokens_recuperacion 
    SET usado = 1,
        fecha_invalidacion = CURRENT_TIMESTAMP  -- Asumiendo que existe este campo
    WHERE id_usuario = usuario_id 
    AND usado = 0 
    AND fecha_expiracion > CURRENT_TIMESTAMP;
    
    GET DIAGNOSTICS tokens_invalidados = ROW_COUNT;
    
    -- Verificar que la invalidación fue exitosa
    IF tokens_invalidados != tokens_activos THEN
        RAISE WARNING 'Se esperaba invalidar % tokens pero se invalidaron %', tokens_activos, tokens_invalidados;
    END IF;

    RAISE NOTICE 'Se invalidaron % tokens para el usuario ID: %', tokens_invalidados, usuario_id;
    RETURN tokens_invalidados;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al invalidar tokens del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;