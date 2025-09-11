-- =====================================================
-- FUNCIONES DEL CONTROLADOR PRINCIPAL
-- =====================================================

--1. Obtener usuario por correo (para login)
CREATE OR REPLACE FUNCTION obtener_usuario_por_correo(
    p_correo usuarios.correo%TYPE
)
RETURNS TABLE (
    id usuarios.id%TYPE,
    nombre usuarios.nombre%TYPE,
    apellido usuarios.apellido%TYPE,
    correo usuarios.correo%TYPE,
    telefono usuarios.telefono%TYPE,
    direccion usuarios.direccion%TYPE,
    clave usuarios.clave%TYPE,
    estado usuarios.estado%TYPE,
    rol usuarios.rol%TYPE,
    avatar usuarios.avatar%TYPE,
    fecha usuarios.fecha%TYPE,
    fecha_ultimo_cambio_clave usuarios.fecha_ultimo_cambio_clave%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_correo IS NULL OR TRIM(p_correo) = '' THEN
        RAISE EXCEPTION 'El correo no puede estar vacío';
    END IF;

    -- Validar formato de correo
    IF p_correo !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        RAISE EXCEPTION 'Formato de correo electrónico inválido';
    END IF;

    -- Retornar los datos del usuario activo
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
    WHERE u.correo = LOWER(TRIM(p_correo)) 
      AND u.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuario por correo: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;


--2. Cambiar contraseña inicial de usuario nuevo
CREATE OR REPLACE FUNCTION cambiar_clave_inicial_usuario(
    p_id_usuario usuarios.id%TYPE,
    p_nueva_clave usuarios.clave%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    id_usuario_actualizado usuarios.id%TYPE
) AS $$
DECLARE
    v_usuario_existe BOOLEAN := FALSE;
    v_ya_cambio_clave BOOLEAN := FALSE;
    v_filas_afectadas usuarios.id%TYPE := 0;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido'::TEXT, 0;
        RETURN;
    END IF;

    IF p_nueva_clave IS NULL OR TRIM(p_nueva_clave) = '' THEN
        RETURN QUERY SELECT FALSE, 'La nueva contraseña no puede estar vacía'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_usuario AND estado = 1
    ) INTO v_usuario_existe;

    IF NOT v_usuario_existe THEN
        RETURN QUERY SELECT FALSE, 'El usuario no existe o está inactivo'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar que el usuario aún no ha cambiado su contraseña (fecha_ultimo_cambio_clave IS NULL)
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_usuario 
          AND estado = 1 
          AND fecha_ultimo_cambio_clave IS NOT NULL
    ) INTO v_ya_cambio_clave;

    IF v_ya_cambio_clave THEN
        RETURN QUERY SELECT FALSE, 'El usuario ya cambió su contraseña inicial'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar la contraseña inicial del usuario
    UPDATE usuarios 
    SET 
        clave = p_nueva_clave,
        fecha_ultimo_cambio_clave = CURRENT_TIMESTAMP
    WHERE id = p_id_usuario 
      AND estado = 1 
      AND fecha_ultimo_cambio_clave IS NULL;

    -- Verificar cuántas filas fueron afectadas
    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizó correctamente
    IF v_filas_afectadas = 0 THEN
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar la contraseña inicial del usuario'::TEXT, 0;
        RETURN;
    END IF;

    RAISE NOTICE 'Contraseña inicial actualizada exitosamente para usuario ID: %', p_id_usuario;

    -- Retornar resultado exitoso
    RETURN QUERY SELECT TRUE, 'Contraseña inicial actualizada correctamente'::TEXT, p_id_usuario;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al cambiar contraseña inicial: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;