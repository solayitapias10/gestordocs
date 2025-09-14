-- =====================================================
-- FUNCIONES MODELO RECUPERAR V1
-- =====================================================

-- 1. getUsuarioCorreo
-- Devuelve la información de un usuario activo buscando por su correo electrónico.
CREATE OR REPLACE FUNCTION fun_obtenerusuarioporcorreo(
    p_identificador VARCHAR(255)
)
RETURNS TABLE (
    id            usuarios.id%TYPE,
    nombre        usuarios.nombre%TYPE,
    apellido      usuarios.apellido%TYPE,
    correo        usuarios.correo%TYPE,
    telefono      usuarios.telefono%TYPE,
    direccion     usuarios.direccion%TYPE,
    clave         usuarios.clave%TYPE,
    estado        usuarios.estado%TYPE,
    rol           usuarios.rol%TYPE,
    avatar        usuarios.avatar%TYPE,
    fecha         usuarios.fecha%TYPE,
    fecha_ultimo_cambio_clave usuarios.fecha_ultimo_cambio_clave%TYPE
) AS $$
BEGIN
    -- Validar parámetro de entrada
    IF p_identificador IS NULL OR TRIM(p_identificador) = '' THEN
        RAISE EXCEPTION 'El identificador no puede estar vacío';
    END IF;

    -- Si es un número, buscar por ID
    IF p_identificador ~ '^\d+$' THEN
        RETURN QUERY 
        SELECT 
            u.id, u.nombre, u.apellido, u.correo, u.telefono, u.direccion, 
            u.clave, u.estado, u.rol, u.avatar, u.fecha, u.fecha_ultimo_cambio_clave
        FROM usuarios u
        WHERE u.id = p_identificador::INTEGER;
    ELSE
        -- Si no es número, asumir que es correo
        -- Validar formato de correo solo si parece ser uno
        IF p_identificador LIKE '%@%' THEN
            IF p_identificador !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
                RAISE EXCEPTION 'Formato de correo electrónico inválido';
            END IF;
        END IF;

        RETURN QUERY 
        SELECT 
            u.id, u.nombre, u.apellido, u.correo, u.telefono, u.direccion, 
            u.clave, u.estado, u.rol, u.avatar, u.fecha, u.fecha_ultimo_cambio_clave
        FROM usuarios u
        WHERE u.correo = LOWER(TRIM(p_identificador))
          AND u.estado = 1;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuario por correo: %', SQLERRM;
END;

$$ LANGUAGE plpgsql;

-- 2. getUsuarioPorId
-- Esta función de PostgreSQL retorna los datos completos de un usuario según su ID, validando que el ID no sea nulo y que el usuario esté activo (estado = 1).
CREATE OR REPLACE FUNCTION fun_obtenerusuarioid(
    p_id_usuario IN usuarios.id%TYPE
)
RETURNS TABLE (
    id                          usuarios.id%TYPE,
    nombre                      usuarios.nombre%TYPE,
    apellido                    usuarios.apellido%TYPE,
    correo                      usuarios.correo%TYPE,
    telefono                    usuarios.telefono%TYPE,
    direccion                   usuarios.direccion%TYPE,
    clave                       usuarios.clave%TYPE,
    estado                      usuarios.estado%TYPE,
    rol                         usuarios.rol%TYPE,
    avatar                      usuarios.avatar%TYPE,
    fecha                       usuarios.fecha%TYPE,
    fecha_ultimo_cambio_clave   usuarios.fecha_ultimo_cambio_clave%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido.';
    END IF;

    -- Retornar los datos del usuario especificado
    RETURN QUERY
    SELECT
        u.id,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        u.direccion,
        u.clave,
        u.estado,
        u.rol,
        u.avatar,
        u.fecha,
        u.fecha_ultimo_cambio_clave
    FROM usuarios u
    WHERE u.id = p_id_usuario;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuario con ID %: %', p_id_usuario, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 3. guardarToken
-- Guarda un nuevo token de recuperación para un usuario, validando los datos de entrada.
CREATE OR REPLACE FUNCTION fun_guardartokenrecuperacion(
    p_id_usuario    IN tokens_recuperacion.id_usuario%TYPE,
    p_token         IN tokens_recuperacion.token%TYPE,
    p_fecha_exp     IN tokens_recuperacion.fecha_expiracion%TYPE,
    p_ip_solicitud  IN tokens_recuperacion.ip_solicitud%TYPE DEFAULT NULL,
    p_user_agent    IN tokens_recuperacion.user_agent%TYPE DEFAULT NULL
)
RETURNS TABLE (
    id_token_creado tokens_recuperacion.id%TYPE,
    success BOOLEAN,
    mensaje TEXT
) AS $$
DECLARE
    v_id_nuevo_token tokens_recuperacion.id%TYPE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido.';
    END IF;

    IF p_token IS NULL OR TRIM(p_token) = '' THEN
        RAISE EXCEPTION 'El token no puede estar vacío.';
    END IF;

    IF p_fecha_exp IS NULL THEN
        RAISE EXCEPTION 'La fecha de expiración es requerida.';
    END IF;

    -- Verificar que el usuario existe
    IF NOT EXISTS(SELECT 1 FROM usuarios WHERE id = p_id_usuario AND estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo.';
    END IF;

    -- Insertar el nuevo token de recuperación
    INSERT INTO tokens_recuperacion (id_usuario, token, fecha_expiracion, ip_solicitud, user_agent)
    VALUES (p_id_usuario, p_token, p_fecha_exp, p_ip_solicitud, p_user_agent)
    RETURNING id INTO v_id_nuevo_token;

    -- Retornar el resultado exitoso
    RETURN QUERY SELECT v_id_nuevo_token, TRUE, 'Token guardado correctamente.'::TEXT;

EXCEPTION
    WHEN unique_violation THEN
        RAISE EXCEPTION 'Error: El token generado ya existe, intente nuevamente.';
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al guardar el token de recuperación: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

--4. validarToken
-- Valida si un token de recuperación es válido (existe, no está usado, no ha expirado) y retorna los datos del token y del usuario asociado.
CREATE OR REPLACE FUNCTION fun_validartokenrecuperacion(
    p_token IN tokens_recuperacion.token%TYPE
)
RETURNS TABLE (
    id_token          tokens_recuperacion.id%TYPE,
    id_usuario        tokens_recuperacion.id_usuario%TYPE,
    token             tokens_recuperacion.token%TYPE,
    fecha_expiracion  tokens_recuperacion.fecha_expiracion%TYPE,
    usado             tokens_recuperacion.usado%TYPE,
    nombre_usuario    usuarios.nombre%TYPE,
    apellido_usuario  usuarios.apellido%TYPE,
    correo_usuario    usuarios.correo%TYPE
) AS $$
BEGIN
    -- Validar que el token no sea nulo o vacío
    IF p_token IS NULL OR TRIM(p_token) = '' THEN
        RAISE EXCEPTION 'El token es requerido.';
    END IF;

    -- Retornar los datos del token si es válido
    RETURN QUERY
    SELECT
        tr.id,
        tr.id_usuario,
        tr.token,
        tr.fecha_expiracion,
        tr.usado,
        u.nombre,
        u.apellido,
        u.correo
    FROM tokens_recuperacion tr
    INNER JOIN usuarios u ON tr.id_usuario = u.id
    WHERE tr.token = p_token
      AND tr.usado = FALSE -- No ha sido usado
      AND tr.fecha_expiracion > CURRENT_TIMESTAMP -- No ha expirado
      AND u.estado = 1 -- El usuario asociado está activo
    LIMIT 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al validar el token de recuperación: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 5. marcarTokenUsado
-- Marca un token de recuperación como usado, validando que el token exista y no haya sido usado previamente.
CREATE OR REPLACE FUNCTION fun_marcartokenusado(
    p_token IN tokens_recuperacion.token%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    filas_afectadas INTEGER
) AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
    v_token_existe BOOLEAN;
BEGIN
    -- Validar que el token no sea nulo o vacío
    IF p_token IS NULL OR TRIM(p_token) = '' THEN
        RETURN QUERY SELECT FALSE, 'El token es requerido.'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar que el token existe y no está usado
    SELECT EXISTS(SELECT 1 FROM tokens_recuperacion WHERE token = p_token AND usado = 0) INTO v_token_existe;

    IF NOT v_token_existe THEN
        RETURN QUERY SELECT FALSE, 'El token no es válido o ya fue utilizado.'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar el estado del token a 'usado'
    UPDATE tokens_recuperacion
    SET usado = 1
    WHERE token = p_token AND usado = 0;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Retornar el resultado
    RETURN QUERY SELECT TRUE, 'Token marcado como usado correctamente.'::TEXT, v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al marcar el token como usado: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 6. invalidarTokensUsuario
-- Invalida todos los tokens de recuperación activos y no usados para un usuario específico.
CREATE OR REPLACE FUNCTION fun_invalidartokensusuario(
    p_id_usuario IN tokens_recuperacion.id_usuario%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    tokens_invalidados INTEGER
) AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
BEGIN
    -- Validar que el ID de usuario no sea nulo o inválido
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido.'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar todos los tokens activos (no usados y no expirados) del usuario
    UPDATE tokens_recuperacion
    SET usado = 1
    WHERE id_usuario = p_id_usuario
      AND usado = 0
      AND fecha_expiracion > CURRENT_TIMESTAMP;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Retornar el resultado de la operación
    IF v_filas_afectadas > 0 THEN
        RETURN QUERY SELECT TRUE, 
                            'Se invalidaron ' || v_filas_afectadas || ' token(s) anterior(es).'::TEXT, 
                            v_filas_afectadas;
    ELSE
        RETURN QUERY SELECT TRUE, 
                            'No se encontraron tokens activos para invalidar.'::TEXT, 
                            0;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al invalidar los tokens del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 7. actualizarContrasena
-- Actualiza la contraseña de un usuario y la fecha del último cambio de clave.
CREATE OR REPLACE FUNCTION fun_actualizarcontrasena(
    p_id_usuario  IN usuarios.id%TYPE,
    p_hash_clave  IN usuarios.clave%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    filas_afectadas INTEGER
) AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido.'::TEXT, 0;
        RETURN;
    END IF;

    IF p_hash_clave IS NULL OR TRIM(p_hash_clave) = '' THEN
        RETURN QUERY SELECT FALSE, 'La contraseña no puede estar vacía.'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar la contraseña y la fecha de cambio
    UPDATE usuarios
    SET 
        clave = p_hash_clave,
        fecha_ultimo_cambio_clave = CURRENT_TIMESTAMP
    WHERE id = p_id_usuario AND estado = 1;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    IF v_filas_afectadas > 0 THEN
        RETURN QUERY SELECT TRUE, 'Contraseña actualizada correctamente.'::TEXT, v_filas_afectadas;
    ELSE
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar la contraseña. El usuario podría no existir o estar inactivo.'::TEXT, 0;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar la contraseña: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
