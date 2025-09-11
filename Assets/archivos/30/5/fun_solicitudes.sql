-- =====================================================
-- FUNCIONES SOLICITUDES
-- =====================================================

-- 1. Registrar Solicitud
CREATE OR REPLACE FUNCTION registrar_solicitud_usuario(
    p_nombre solicitudes_registro.nombre%TYPE,
    p_apellido solicitudes_registro.apellido%TYPE,
    p_correo solicitudes_registro.correo%TYPE,
    p_telefono solicitudes_registro.telefono%TYPE,
    p_direccion solicitudes_registro.direccion%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    id_solicitud INTEGER
) AS $$
DECLARE
    v_correo_existente BOOLEAN := FALSE;
    v_telefono_existente BOOLEAN := FALSE;
    v_id_solicitud_nueva INTEGER;
BEGIN
    -- Validar parámetros de entrada
    IF p_nombre IS NULL OR TRIM(p_nombre) = '' THEN
        RETURN QUERY SELECT FALSE, 'El nombre no puede estar vacío'::TEXT, 0;
        RETURN;
    END IF;

    IF p_apellido IS NULL OR TRIM(p_apellido) = '' THEN
        RETURN QUERY SELECT FALSE, 'El apellido no puede estar vacío'::TEXT, 0;
        RETURN;
    END IF;

    IF p_correo IS NULL OR TRIM(p_correo) = '' THEN
        RETURN QUERY SELECT FALSE, 'El correo no puede estar vacío'::TEXT, 0;
        RETURN;
    END IF;

    -- Validar formato de correo
    IF p_correo !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        RETURN QUERY SELECT FALSE, 'Formato de correo electrónico inválido'::TEXT, 0;
        RETURN;
    END IF;

    IF p_telefono IS NULL OR TRIM(p_telefono) = '' THEN
        RETURN QUERY SELECT FALSE, 'El teléfono no puede estar vacío'::TEXT, 0;
        RETURN;
    END IF;

    -- Validar formato de teléfono (7-15 dígitos)
    IF p_telefono !~ '^\d{7,15}$' THEN
        RETURN QUERY SELECT FALSE, 'El teléfono debe contener solo números (entre 7 y 15 dígitos)'::TEXT, 0;
        RETURN;
    END IF;

    IF p_direccion IS NULL OR TRIM(p_direccion) = '' THEN
        RETURN QUERY SELECT FALSE, 'La dirección no puede estar vacía'::TEXT, 0;
        RETURN;
    END IF;


    -- Verificar si el correo ya existe en usuarios activos
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE correo = p_correo AND estado = 1
    ) INTO v_correo_existente;

    IF v_correo_existente THEN
        RETURN QUERY SELECT FALSE, 'El correo ya está registrado en el sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si el teléfono ya existe en usuarios activos
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE telefono = p_telefono AND estado = 1
    ) INTO v_telefono_existente;

    IF v_telefono_existente THEN
        RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado en el sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si ya existe una solicitud pendiente con el mismo correo
    SELECT EXISTS(
        SELECT 1 FROM solicitudes_registro 
        WHERE correo = p_correo AND estado = 0
    ) INTO v_correo_existente;

    IF v_correo_existente THEN
        RETURN QUERY SELECT FALSE, 'Ya existe una solicitud pendiente con este correo'::TEXT, 0;
        RETURN;
    END IF;

    -- Insertar nueva solicitud SIN contraseña
    INSERT INTO solicitudes_registro (
        nombre, 
        apellido, 
        correo, 
        telefono, 
        direccion, 
        estado,
        fecha_solicitud
    ) VALUES (
        TRIM(p_nombre),
        TRIM(p_apellido),
        LOWER(TRIM(p_correo)),
        TRIM(p_telefono),
        TRIM(p_direccion),
        0,
        CURRENT_TIMESTAMP
    ) RETURNING id INTO v_id_solicitud_nueva;

    -- Verificar que se insertó correctamente
    IF v_id_solicitud_nueva IS NULL OR v_id_solicitud_nueva <= 0 THEN
        RETURN QUERY SELECT FALSE, 'Error al crear la solicitud de registro'::TEXT, 0;
        RETURN;
    END IF;

    RAISE NOTICE 'Solicitud de registro creada exitosamente. ID: %, Correo: %', v_id_solicitud_nueva, p_correo;

    -- Retornar resultado exitoso SIN contraseña temporal
    RETURN QUERY SELECT TRUE, 'Solicitud enviada correctamente. Será notificado cuando sea aprobada por el administrador.'::TEXT, v_id_solicitud_nueva;

EXCEPTION
    WHEN unique_violation THEN
        IF SQLERRM LIKE '%solicitudes_registro_correo_key%' THEN
            RETURN QUERY SELECT FALSE, 'Ya existe una solicitud con este correo electrónico'::TEXT, 0;
        ELSIF SQLERRM LIKE '%solicitudes_registro_telefono_key%' THEN
            RETURN QUERY SELECT FALSE, 'Ya existe una solicitud con este número de teléfono'::TEXT, 0;
        ELSE
            RETURN QUERY SELECT FALSE, ('Error de duplicado: ' || SQLERRM)::TEXT, 0;
        END IF;
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al registrar solicitud: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;

-- 2. Función para aprobar solicitud de registro y crear usuario
CREATE OR REPLACE FUNCTION aprobar_solicitud_registro(
    p_id_solicitud solicitudes_registro.id%TYPE,
    p_id_admin usuarios.id%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    id_usuario_creado INTEGER,
    contrasena_temporal TEXT
) AS $$
DECLARE
    v_solicitud RECORD;
    v_usuario_existente INTEGER;
    v_telefono_existente INTEGER;
    v_contrasena_temporal TEXT;
    v_id_usuario_nuevo INTEGER;
    v_filas_afectadas INTEGER;
    v_admin_existe BOOLEAN := FALSE;
BEGIN
    -- Validaciones iniciales (sin cambios)
    IF p_id_solicitud IS NULL OR p_id_solicitud <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de solicitud inválido'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    IF p_id_admin IS NULL OR p_id_admin <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de administrador inválido'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    -- Verificar administrador
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_admin 
        AND estado = 1 
        AND rol = 1
    ) INTO v_admin_existe;

    IF NOT v_admin_existe THEN
        RETURN QUERY SELECT FALSE, 'El administrador no existe o no tiene permisos'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    -- Obtener solicitud
    SELECT * INTO v_solicitud 
    FROM solicitudes_registro 
    WHERE id = p_id_solicitud 
    AND estado = 0;

    IF v_solicitud IS NULL THEN
        RETURN QUERY SELECT FALSE, 'Solicitud no encontrada o ya procesada'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    -- Verificar duplicados
    SELECT COUNT(*) INTO v_usuario_existente 
    FROM usuarios 
    WHERE correo = v_solicitud.correo 
    AND estado = 1;

    IF v_usuario_existente > 0 THEN
        RETURN QUERY SELECT FALSE, 'Error: El correo electrónico ya está registrado en el sistema'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    SELECT COUNT(*) INTO v_telefono_existente 
    FROM usuarios 
    WHERE telefono = v_solicitud.telefono 
    AND estado = 1;

    IF v_telefono_existente > 0 THEN
        RETURN QUERY SELECT FALSE, 'Error: El número de teléfono ya está registrado en el sistema'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    -- Generar contraseña temporal 
    SELECT generar_contrasena_temporal(12) INTO v_contrasena_temporal;
    
    INSERT INTO usuarios (
        nombre, 
        apellido, 
        correo, 
        telefono, 
        direccion, 
        clave,  -- PLACEHOLDER - se actualizará desde PHP
        estado, 
        rol, 
        fecha_ultimo_cambio_clave,
        fecha
    ) VALUES (
        v_solicitud.nombre,
        v_solicitud.apellido,
        v_solicitud.correo,
        v_solicitud.telefono,
        v_solicitud.direccion,
        'TEMPORAL_PLACEHOLDER',  -- Placeholder temporal
        1,
        2,
        NULL,  -- Forzar cambio de contraseña en primer login
        CURRENT_TIMESTAMP
    ) RETURNING id INTO v_id_usuario_nuevo;

    IF v_id_usuario_nuevo IS NULL OR v_id_usuario_nuevo <= 0 THEN
        RETURN QUERY SELECT FALSE, 'Error al crear el usuario'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    -- Actualizar solicitud
    UPDATE solicitudes_registro 
    SET estado = 1, 
        id_usuario_admin = p_id_admin, 
        fecha_procesado = CURRENT_TIMESTAMP 
    WHERE id = p_id_solicitud;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    IF v_filas_afectadas = 0 THEN
        DELETE FROM usuarios WHERE id = v_id_usuario_nuevo;
        RETURN QUERY SELECT FALSE, 'Error al actualizar el estado de la solicitud'::TEXT, 0, ''::TEXT;
        RETURN;
    END IF;

    -- Crear notificación
    INSERT INTO notificaciones (
        id_usuario, 
        id_solicitud, 
        nombre, 
        evento, 
        fecha, 
        leida
    ) VALUES (
        v_id_usuario_nuevo,
        p_id_solicitud,
        v_solicitud.nombre || ' ' || v_solicitud.apellido,
        'SOLICITUD_APROBADA',
        CURRENT_TIMESTAMP,
        0
    );

    -- RETORNAR: success, mensaje, id_usuario_creado, contraseña_temporal_sin_hashear
    RETURN QUERY SELECT 
        TRUE, 
        'Solicitud aprobada correctamente. Se ha enviado un correo con las credenciales.'::TEXT, 
        v_id_usuario_nuevo, 
        v_contrasena_temporal;  -- Contraseña sin hashear

EXCEPTION
    WHEN unique_violation THEN
        IF SQLERRM LIKE '%usuarios_correo_key%' THEN
            RETURN QUERY SELECT FALSE, 'Error: El correo electrónico ya está registrado en el sistema'::TEXT, 0, ''::TEXT;
        ELSIF SQLERRM LIKE '%usuarios_telefono_key%' THEN
            RETURN QUERY SELECT FALSE, 'Error: El número de teléfono ya está registrado en el sistema'::TEXT, 0, ''::TEXT;
        ELSE
            RETURN QUERY SELECT FALSE, ('Error de unicidad: ' || SQLERRM)::TEXT, 0, ''::TEXT;
        END IF;
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al aprobar solicitud: ' || SQLERRM)::TEXT, 0, ''::TEXT;
END;
$$ LANGUAGE plpgsql;


-- 3. Función para rechazar solicitud de registro
CREATE OR REPLACE FUNCTION rechazar_solicitud_registro(
    p_id_solicitud solicitudes_registro.id%TYPE,
    p_id_admin usuarios.id%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT
) AS $$
DECLARE
    v_solicitud RECORD;
    v_filas_afectadas INTEGER;
    v_admin_existe BOOLEAN := FALSE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_solicitud IS NULL OR p_id_solicitud <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de solicitud inválido'::TEXT;
        RETURN;
    END IF;

    IF p_id_admin IS NULL OR p_id_admin <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de administrador inválido'::TEXT;
        RETURN;
    END IF;

    -- Verificar que el administrador existe y tiene permisos
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_admin 
        AND estado = 1 
        AND rol = 1
    ) INTO v_admin_existe;

    IF NOT v_admin_existe THEN
        RETURN QUERY SELECT FALSE, 'El administrador no existe o no tiene permisos'::TEXT;
        RETURN;
    END IF;

    -- Obtener datos de la solicitud antes de actualizar
    SELECT * INTO v_solicitud 
    FROM solicitudes_registro 
    WHERE id = p_id_solicitud 
    AND estado = 0;

    IF v_solicitud IS NULL THEN
        RETURN QUERY SELECT FALSE, 'Solicitud no encontrada o ya procesada'::TEXT;
        RETURN;
    END IF;

    -- Actualizar el estado de la solicitud a rechazada
    UPDATE solicitudes_registro 
    SET estado = 2, 
        id_usuario_admin = p_id_admin, 
        fecha_procesado = CURRENT_TIMESTAMP 
    WHERE id = p_id_solicitud 
    AND estado = 0;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    IF v_filas_afectadas = 0 THEN
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar la solicitud'::TEXT;
        RETURN;
    END IF;

    -- Crear notificación de rechazo
    INSERT INTO notificaciones (
        id_solicitud, 
        nombre, 
        evento, 
        fecha, 
        leida
    ) VALUES (
        p_id_solicitud,
        v_solicitud.nombre || ' ' || v_solicitud.apellido,
        'SOLICITUD_RECHAZADA',
        CURRENT_TIMESTAMP,
        0
    );

    RAISE NOTICE 'Solicitud rechazada exitosamente. ID: %, Correo: %', p_id_solicitud, v_solicitud.correo;

    -- Retornar resultado exitoso
    RETURN QUERY SELECT TRUE, 'Solicitud rechazada correctamente. Se ha enviado un correo de notificación.'::TEXT;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al rechazar solicitud: ' || SQLERRM)::TEXT;
END;
$$ LANGUAGE plpgsql;