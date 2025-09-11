-- =====================================================
-- FUNCIONES DE USUARIOS
-- =====================================================

-- 1. Obtener todos los usuarios
CREATE OR REPLACE FUNCTION obtener_todos_usuarios()
RETURNS TABLE (
    id usuarios.id%TYPE,
    nombre usuarios.nombre%TYPE,
    apellido usuarios.apellido%TYPE,
    correo usuarios.correo%TYPE,
    telefono usuarios.telefono%TYPE,
    direccion usuarios.direccion%TYPE,
    clave usuarios.clave%TYPE,
    rol usuarios.rol%TYPE,
    estado usuarios.estado%TYPE,
    fecha usuarios.fecha%TYPE
) AS $$
BEGIN
    RETURN QUERY 
    SELECT 
        u.id,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        u.direccion,
        u.clave,
        u.rol,
        u.estado,
        u.fecha
    FROM usuarios u
    ORDER BY u.fecha DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuarios: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 2. Registrar - Agregar usuarios 

CREATE OR REPLACE FUNCTION registrar_usuario(
    p_nombre usuarios.nombre%TYPE,
    p_apellido usuarios.apellido%TYPE,
    p_correo usuarios.correo%TYPE,
    p_telefono usuarios.telefono%TYPE,
    p_direccion usuarios.direccion%TYPE,
    p_clave usuarios.clave%TYPE,
    p_rol usuarios.rol%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    id_usuario_creado usuarios.id%TYPE
) AS $$
DECLARE
    v_correo_existente BOOLEAN := FALSE;
    v_telefono_existente BOOLEAN := FALSE;
    v_id_usuario_nuevo usuarios.id%TYPE;
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

    IF p_clave IS NULL OR TRIM(p_clave) = '' THEN
        RETURN QUERY SELECT FALSE, 'La contraseña no puede estar vacía'::TEXT, 0;
        RETURN;
    END IF;

    -- Validar rol
    IF p_rol IS NULL OR p_rol NOT IN (0, 1, 2) THEN
        RETURN QUERY SELECT FALSE, 'Rol inválido. Debe ser 0 (Usuario), 1 (Admin) o 2 (Super Admin)'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si el correo ya existe en usuarios activos
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE correo = LOWER(TRIM(p_correo)) AND estado = 1
    ) INTO v_correo_existente;

    IF v_correo_existente THEN
        RETURN QUERY SELECT FALSE, 'El correo ya está registrado en el sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si el teléfono ya existe en usuarios activos
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE telefono = TRIM(p_telefono) AND estado = 1
    ) INTO v_telefono_existente;

    IF v_telefono_existente THEN
        RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado en el sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Insertar nuevo usuario
    INSERT INTO usuarios (
        nombre, 
        apellido, 
        correo, 
        telefono, 
        direccion, 
        clave,
        rol,
        estado,
        fecha
    ) VALUES (
        TRIM(p_nombre),
        TRIM(p_apellido),
        LOWER(TRIM(p_correo)),
        TRIM(p_telefono),
        TRIM(p_direccion),
        p_clave,  -- Ya viene hasheada desde PHP
        p_rol,
        1,  -- Usuario activo por defecto
        CURRENT_TIMESTAMP
    ) RETURNING id INTO v_id_usuario_nuevo;

    -- Verificar que se insertó correctamente
    IF v_id_usuario_nuevo IS NULL OR v_id_usuario_nuevo <= 0 THEN
        RETURN QUERY SELECT FALSE, 'Error al crear el usuario'::TEXT, 0;
        RETURN;
    END IF;

    RAISE NOTICE 'Usuario registrado exitosamente. ID: %, Correo: %', v_id_usuario_nuevo, p_correo;

    -- Retornar resultado exitoso
    RETURN QUERY SELECT TRUE, 'Usuario registrado correctamente'::TEXT, v_id_usuario_nuevo;

EXCEPTION
    WHEN unique_violation THEN
        IF SQLERRM LIKE '%usuarios_correo_key%' THEN
            RETURN QUERY SELECT FALSE, 'El correo electrónico ya está registrado'::TEXT, 0;
        ELSIF SQLERRM LIKE '%usuarios_telefono_key%' THEN
            RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado'::TEXT, 0;
        ELSE
            RETURN QUERY SELECT FALSE, ('Error de duplicado: ' || SQLERRM)::TEXT, 0;
        END IF;
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al registrar usuario: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;


-- 3. Verificar existencia de campo único (correo, teléfono, etc.) con mensaje
CREATE OR REPLACE FUNCTION verificar_campo_usuario(
    p_campo VARCHAR(50),                    
    p_valor VARCHAR(255),                   
    p_id_excluir usuarios.id%TYPE DEFAULT 0
)
RETURNS TABLE (
    id usuarios.id%TYPE,
    existe BOOLEAN,
    mensaje TEXT
) AS $$
DECLARE
    v_id_encontrado usuarios.id%TYPE;
    v_mensaje TEXT;
    v_existe BOOLEAN;
BEGIN
    -- Validar parámetros de entrada
    IF p_campo IS NULL OR TRIM(p_campo) = '' THEN
        RAISE EXCEPTION 'El campo no puede estar vacío';
    END IF;

    IF p_valor IS NULL OR TRIM(p_valor) = '' THEN
        RAISE EXCEPTION 'El valor no puede estar vacío';
    END IF;

    -- Validar que el campo sea uno de los permitidos
    IF p_campo NOT IN ('correo', 'telefono') THEN
        RAISE EXCEPTION 'Campo no válido. Solo se permite: correo, telefono';
    END IF;

    -- Verificar existencia del campo
    IF p_id_excluir > 0 THEN
        -- Excluir el ID específico (para actualizaciones)
        IF p_campo = 'correo' THEN
            SELECT u.id INTO v_id_encontrado
            FROM usuarios u 
            WHERE u.correo = LOWER(TRIM(p_valor)) 
              AND u.id != p_id_excluir 
              AND u.estado = 1
            LIMIT 1;
        ELSIF p_campo = 'telefono' THEN
            SELECT u.id INTO v_id_encontrado
            FROM usuarios u 
            WHERE u.telefono = TRIM(p_valor) 
              AND u.id != p_id_excluir 
              AND u.estado = 1
            LIMIT 1;
        END IF;
    ELSE
        -- Sin exclusión (para nuevos registros)
        IF p_campo = 'correo' THEN
            SELECT u.id INTO v_id_encontrado
            FROM usuarios u 
            WHERE u.correo = LOWER(TRIM(p_valor)) 
              AND u.estado = 1
            LIMIT 1;
        ELSIF p_campo = 'telefono' THEN
            SELECT u.id INTO v_id_encontrado
            FROM usuarios u 
            WHERE u.telefono = TRIM(p_valor) 
              AND u.estado = 1
            LIMIT 1;
        END IF;
    END IF;

    -- Determinar el resultado y mensaje
    IF v_id_encontrado IS NOT NULL THEN
        v_existe := TRUE;
        IF p_id_excluir > 0 THEN
            v_mensaje := 'Ya existe otro usuario con este ' || p_campo || '. No se puede actualizar.';
        ELSE
            v_mensaje := 'Ya existe un usuario registrado con este ' || p_campo || '. No se puede crear.';
        END IF;
    ELSE
        v_existe := FALSE;
        IF p_id_excluir > 0 THEN
            v_mensaje := 'El ' || p_campo || ' está disponible para actualización.';
        ELSE
            v_mensaje := 'El ' || p_campo || ' está disponible para registro.';
        END IF;
        -- Para mantener compatibilidad, cuando no existe retornamos NULL en id
        v_id_encontrado := NULL;
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_id_encontrado, v_existe, v_mensaje;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar campo de usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 4 . Modificar el usuario
CREATE OR REPLACE FUNCTION modificar_usuario(
    p_id_usuario usuarios.id%TYPE,
    p_nombre usuarios.nombre%TYPE,
    p_apellido usuarios.apellido%TYPE,
    p_correo usuarios.correo%TYPE,
    p_telefono usuarios.telefono%TYPE,
    p_direccion usuarios.direccion%TYPE,
    p_rol usuarios.rol%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    id_usuario_modificado usuarios.id%TYPE
) AS $$
DECLARE
    v_correo_existente BOOLEAN := FALSE;
    v_telefono_existente BOOLEAN := FALSE;
    v_usuario_existe BOOLEAN := FALSE;
    v_filas_afectadas usuarios.id%TYPE := 0;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido'::TEXT, 0;
        RETURN;
    END IF;

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

    -- Validar rol
    IF p_rol IS NULL OR p_rol NOT IN (0, 1, 2) THEN
        RETURN QUERY SELECT FALSE, 'Rol inválido. Debe ser 0 (Usuario), 1 (Admin) o 2 (Super Admin)'::TEXT, 0;
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

    -- Verificar si el correo ya existe en otros usuarios activos (excluyendo el usuario actual)
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE correo = LOWER(TRIM(p_correo)) 
          AND estado = 1 
          AND id != p_id_usuario
    ) INTO v_correo_existente;

    IF v_correo_existente THEN
        RETURN QUERY SELECT FALSE, 'El correo ya está registrado en otro usuario del sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si el teléfono ya existe en otros usuarios activos (excluyendo el usuario actual)
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE telefono = TRIM(p_telefono) 
          AND estado = 1 
          AND id != p_id_usuario
    ) INTO v_telefono_existente;

    IF v_telefono_existente THEN
        RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado en otro usuario del sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar el usuario
    UPDATE usuarios 
    SET 
        nombre = TRIM(p_nombre),
        apellido = TRIM(p_apellido),
        correo = LOWER(TRIM(p_correo)),
        telefono = TRIM(p_telefono),
        direccion = TRIM(p_direccion),
        rol = p_rol
    WHERE id = p_id_usuario AND estado = 1;

    -- Verificar cuántas filas fueron afectadas
    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizó correctamente
    IF v_filas_afectadas = 0 THEN
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar el usuario. Verifique que existe y está activo'::TEXT, 0;
        RETURN;
    END IF;

    RAISE NOTICE 'Usuario modificado exitosamente. ID: %, Correo: %', p_id_usuario, p_correo;

    -- Retornar resultado exitoso
    RETURN QUERY SELECT TRUE, 'Usuario modificado correctamente'::TEXT, p_id_usuario;

EXCEPTION
    WHEN unique_violation THEN
        IF SQLERRM LIKE '%usuarios_correo_key%' THEN
            RETURN QUERY SELECT FALSE, 'El correo electrónico ya está registrado en otro usuario'::TEXT, 0;
        ELSIF SQLERRM LIKE '%usuarios_telefono_key%' THEN
            RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado en otro usuario'::TEXT, 0;
        ELSE
            RETURN QUERY SELECT FALSE, ('Error de duplicado: ' || SQLERRM)::TEXT, 0;
        END IF;
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al modificar usuario: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;

-- 5. Obtener usuario por ID
CREATE OR REPLACE FUNCTION obtener_usuario_por_id(
    p_id_usuario usuarios.id%TYPE
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
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Retornar los datos del usuario
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
        RAISE EXCEPTION 'Error al obtener usuario por ID: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 6. Cambiar estado de usuario (activo/inactivo)
CREATE OR REPLACE FUNCTION cambiar_estado_usuario(
    p_id_usuario usuarios.id%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    estado_anterior usuarios.estado%TYPE,
    estado_nuevo usuarios.estado%TYPE
) AS $$
DECLARE
    v_usuario_existe BOOLEAN := FALSE;
    v_estado_actual usuarios.estado%TYPE;
    v_nuevo_estado usuarios.estado%TYPE;
    v_filas_afectadas INTEGER := 0;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido'::TEXT, 0, 0;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y obtener su estado actual
    SELECT u.estado INTO v_estado_actual
    FROM usuarios u 
    WHERE u.id = p_id_usuario;

    -- Si no se encontró el usuario
    IF v_estado_actual IS NULL THEN
        RETURN QUERY SELECT FALSE, 'El usuario no existe'::TEXT, 0, 0;
        RETURN;
    END IF;

    -- Determinar el nuevo estado (alternar entre 0 y 1)
    v_nuevo_estado := CASE 
        WHEN v_estado_actual = 1 THEN 0 
        ELSE 1 
    END;

    -- Actualizar el estado del usuario
    UPDATE usuarios 
    SET estado = v_nuevo_estado
    WHERE id = p_id_usuario;

    -- Verificar cuántas filas fueron afectadas
    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizó correctamente
    IF v_filas_afectadas = 0 THEN
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar el estado del usuario'::TEXT, v_estado_actual, v_estado_actual;
        RETURN;
    END IF;

    -- Generar mensaje según el cambio realizado
    DECLARE
        v_mensaje TEXT;
    BEGIN
        IF v_nuevo_estado = 0 THEN
            v_mensaje := 'Usuario desactivado correctamente';
        ELSE
            v_mensaje := 'Usuario activado correctamente';
        END IF;

        RAISE NOTICE 'Estado de usuario cambiado - ID: %, Estado anterior: %, Estado nuevo: %', 
                     p_id_usuario, v_estado_actual, v_nuevo_estado;

        -- Retornar resultado exitoso
        RETURN QUERY SELECT TRUE, v_mensaje, v_estado_actual, v_nuevo_estado;
    END;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al cambiar estado del usuario: ' || SQLERRM)::TEXT, 0, 0;
END;
$$ LANGUAGE plpgsql;

-- 7. Contador de compartidos 

CREATE OR REPLACE FUNCTION verificar_estado_solicitudes_compartidos(
    p_correo usuarios.correo%TYPE
)
RETURNS TABLE (
    total INTEGER,
    correo usuarios.correo%TYPE,
    mensaje TEXT
) AS $$
DECLARE
    v_total_solicitudes INTEGER := 0;
    v_mensaje TEXT;
    v_correo_limpio usuarios.correo%TYPE;
BEGIN
    -- Validar parámetros de entrada
    IF p_correo IS NULL OR TRIM(p_correo) = '' THEN
        RETURN QUERY SELECT 0, ''::VARCHAR(100), 'El correo no puede estar vacío'::TEXT;
        RETURN;
    END IF;
    
    -- Limpiar correo
    v_correo_limpio := LOWER(TRIM(p_correo));
    
    -- Validar formato de correo
    IF v_correo_limpio !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        RETURN QUERY SELECT 0, v_correo_limpio, 'Formato de correo electrónico inválido'::TEXT;
        RETURN;
    END IF;
    
    -- Contar las solicitudes compartidas activas para el correo
    SELECT COUNT(sc.id) INTO v_total_solicitudes
    FROM solicitudes_compartidos sc 
    WHERE sc.correo = v_correo_limpio 
      AND sc.estado = 1;
    
    -- Generar mensaje informativo
    IF v_total_solicitudes = 0 THEN
        v_mensaje := 'No hay solicitudes de archivos compartidos para este correo';
    ELSIF v_total_solicitudes = 1 THEN
        v_mensaje := 'Hay 1 solicitud de archivo compartido pendiente';
    ELSE
        v_mensaje := 'Hay ' || v_total_solicitudes || ' solicitudes de archivos compartidos pendientes';
    END IF;
    
    -- Retornar resultado
    RETURN QUERY SELECT v_total_solicitudes, v_correo_limpio, v_mensaje;
    
EXCEPTION
    WHEN OTHERS THEN
        -- Retornamos una fila con error en lugar de hacer RAISE
        RETURN QUERY SELECT -1, COALESCE(v_correo_limpio, p_correo), ('Error: ' || SQLERRM)::TEXT;
END;
$$ LANGUAGE plpgsql;

-- 8. Actualizar perfil de usuario
CREATE OR REPLACE FUNCTION actualizar_perfil_usuario(
    p_id_usuario usuarios.id%TYPE,
    p_nombre usuarios.nombre%TYPE,
    p_apellido usuarios.apellido%TYPE,
    p_correo usuarios.correo%TYPE,
    p_telefono usuarios.telefono%TYPE,
    p_direccion usuarios.direccion%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    id_usuario_actualizado usuarios.id%TYPE
) AS $$
DECLARE
    v_correo_existente BOOLEAN := FALSE;
    v_telefono_existente BOOLEAN := FALSE;
    v_usuario_existe BOOLEAN := FALSE;
    v_filas_afectadas usuarios.id%TYPE := 0;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido'::TEXT, 0;
        RETURN;
    END IF;

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

    -- Verificar que el usuario existe y está activo
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_usuario AND estado = 1
    ) INTO v_usuario_existe;

    IF NOT v_usuario_existe THEN
        RETURN QUERY SELECT FALSE, 'El usuario no existe o está inactivo'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si el correo ya existe en otros usuarios activos (excluyendo el usuario actual)
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE correo = LOWER(TRIM(p_correo)) 
          AND estado = 1 
          AND id != p_id_usuario
    ) INTO v_correo_existente;

    IF v_correo_existente THEN
        RETURN QUERY SELECT FALSE, 'El correo ya está registrado en otro usuario del sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar si el teléfono ya existe en otros usuarios activos (excluyendo el usuario actual)
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE telefono = TRIM(p_telefono) 
          AND estado = 1 
          AND id != p_id_usuario
    ) INTO v_telefono_existente;

    IF v_telefono_existente THEN
        RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado en otro usuario del sistema'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar el perfil del usuario
    UPDATE usuarios 
    SET 
        nombre = TRIM(p_nombre),
        apellido = TRIM(p_apellido),
        correo = LOWER(TRIM(p_correo)),
        telefono = TRIM(p_telefono),
        direccion = TRIM(p_direccion)
    WHERE id = p_id_usuario AND estado = 1;

    -- Verificar cuántas filas fueron afectadas
    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizó correctamente
    IF v_filas_afectadas = 0 THEN
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar el perfil del usuario'::TEXT, 0;
        RETURN;
    END IF;

    RAISE NOTICE 'Perfil actualizado exitosamente. ID: %, Correo: %', p_id_usuario, p_correo;

    -- Retornar resultado exitoso
    RETURN QUERY SELECT TRUE, 'Perfil actualizado correctamente'::TEXT, p_id_usuario;

EXCEPTION
    WHEN unique_violation THEN
        IF SQLERRM LIKE '%usuarios_correo_key%' THEN
            RETURN QUERY SELECT FALSE, 'El correo electrónico ya está registrado en otro usuario'::TEXT, 0;
        ELSIF SQLERRM LIKE '%usuarios_telefono_key%' THEN
            RETURN QUERY SELECT FALSE, 'El número de teléfono ya está registrado en otro usuario'::TEXT, 0;
        ELSE
            RETURN QUERY SELECT FALSE, ('Error de duplicado: ' || SQLERRM)::TEXT, 0;
        END IF;
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al actualizar perfil: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;

-- 9. Cambiar contraseña de usuario
CREATE OR REPLACE FUNCTION cambiar_clave_usuario(
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

    -- Actualizar la contraseña del usuario
    UPDATE usuarios 
    SET 
        clave = p_nueva_clave,
        fecha_ultimo_cambio_clave = CURRENT_TIMESTAMP
    WHERE id = p_id_usuario AND estado = 1;

    -- Verificar cuántas filas fueron afectadas
    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizó correctamente
    IF v_filas_afectadas = 0 THEN
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar la contraseña del usuario'::TEXT, 0;
        RETURN;
    END IF;

    RAISE NOTICE 'Contraseña actualizada exitosamente para usuario ID: %', p_id_usuario;

    -- Retornar resultado exitoso
    RETURN QUERY SELECT TRUE, 'Contraseña actualizada correctamente'::TEXT, p_id_usuario;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al cambiar contraseña: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;

-- 10. Obtener todas las solicitudes de registro pendientes
CREATE OR REPLACE FUNCTION obtener_solicitudes_pendientes()
RETURNS TABLE (
    id solicitudes_registro.id%TYPE,
    nombre solicitudes_registro.nombre%TYPE,
    apellido solicitudes_registro.apellido%TYPE,
    correo solicitudes_registro.correo%TYPE,
    telefono solicitudes_registro.telefono%TYPE,
    direccion solicitudes_registro.direccion%TYPE,
    fecha solicitudes_registro.fecha_solicitud%TYPE
) AS $$
BEGIN
    RETURN QUERY 
    SELECT 
        sr.id,
        sr.nombre,
        sr.apellido,
        sr.correo,
        sr.telefono,
        sr.direccion,
        sr.fecha_solicitud
    FROM solicitudes_registro sr
    WHERE sr.estado = 0
    ORDER BY sr.fecha_solicitud DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener solicitudes pendientes: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 11. Funcion verificar la solicitud pendiente 
CREATE OR REPLACE FUNCTION verificar_solicitud_pendiente(
    p_id_solicitud solicitudes_registro.id%TYPE
)
RETURNS TABLE (
    id solicitudes_registro.id%TYPE,
    nombre solicitudes_registro.nombre%TYPE,
    apellido solicitudes_registro.apellido%TYPE,
    correo solicitudes_registro.correo%TYPE,
    telefono solicitudes_registro.telefono%TYPE,
    direccion solicitudes_registro.direccion%TYPE,
    clave solicitudes_registro.clave%TYPE,
    fecha_solicitud solicitudes_registro.fecha_solicitud%TYPE,
    estado solicitudes_registro.estado%TYPE,
    id_usuario_admin solicitudes_registro.id_usuario_admin%TYPE,
    fecha_procesado solicitudes_registro.fecha_procesado%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_solicitud IS NULL OR p_id_solicitud <= 0 THEN
        RAISE EXCEPTION 'ID de solicitud inválido';
    END IF;

    -- Retornar los datos de la solicitud si está pendiente (estado = 0)
    RETURN QUERY 
    SELECT 
        sr.id,
        sr.nombre,
        sr.apellido,
        sr.correo,
        sr.telefono,
        sr.direccion,
        sr.clave,
        sr.fecha_solicitud,
        sr.estado,
        sr.id_usuario_admin,
        sr.fecha_procesado
    FROM solicitudes_registro sr
    WHERE sr.id = p_id_solicitud 
      AND sr.estado = 0;  -- Solo solicitudes pendientes

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar solicitud pendiente: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 12 Función para actualizar el avatar de un usuario
CREATE OR REPLACE FUNCTION actualizar_avatar_usuario(
    p_id_usuario usuarios.id%TYPE,
    p_ruta_avatar usuarios.avatar%TYPE
)
RETURNS TABLE (
    success BOOLEAN,
    mensaje TEXT,
    filas_afectadas INTEGER
) AS $$
DECLARE
    v_filas_afectadas INTEGER;
    v_usuario_existe BOOLEAN;
    v_ruta_limpia VARCHAR(255);
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT FALSE, 'ID de usuario inválido'::TEXT, 0;
        RETURN;
    END IF;

    IF p_ruta_avatar IS NULL OR TRIM(p_ruta_avatar) = '' THEN
        RETURN QUERY SELECT FALSE, 'La ruta del avatar es requerida'::TEXT, 0;
        RETURN;
    END IF;

    -- Limpiar la ruta
    v_ruta_limpia := TRIM(p_ruta_avatar);

    -- Verificar que el usuario existe y está activo
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_usuario AND estado = 1
    ) INTO v_usuario_existe;

    IF NOT v_usuario_existe THEN
        RETURN QUERY SELECT FALSE, 'El usuario no existe o está inactivo'::TEXT, 0;
        RETURN;
    END IF;

    -- Actualizar el avatar del usuario
    UPDATE usuarios 
    SET avatar = v_ruta_limpia,
        fecha = CURRENT_TIMESTAMP
    WHERE id = p_id_usuario AND estado = 1;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar si se actualizó correctamente
    IF v_filas_afectadas > 0 THEN
        RETURN QUERY SELECT TRUE, 'Avatar actualizado correctamente'::TEXT, v_filas_afectadas;
    ELSE
        RETURN QUERY SELECT FALSE, 'No se pudo actualizar el avatar'::TEXT, 0;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al actualizar avatar: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;