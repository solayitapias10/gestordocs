-- =====================================================
-- FUNCIONES COMPARTIDOS
-- =====================================================

-- 1. Función para obtener archivos compartidos conmigo
CREATE OR REPLACE FUNCTION obtener_archivos_compartidos_conmigo(
    p_correo_usuario usuarios.correo%TYPE
)
RETURNS TABLE (
    id solicitudes_compartidos.id%TYPE,
    fecha_add solicitudes_compartidos.fecha_add%TYPE,
    correo solicitudes_compartidos.correo%TYPE,
    estado solicitudes_compartidos.estado%TYPE,
    id_archivo archivos.id%TYPE,
    nombre_archivo archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    tamano archivos.tamano%TYPE,
    propietario usuarios.nombre%TYPE,
    avatar_propietario usuarios.avatar%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_correo_usuario IS NULL OR p_correo_usuario = '' THEN
        RAISE EXCEPTION 'El correo del usuario no puede estar vacío';
    END IF;

    -- Validar formato de email básico
    IF p_correo_usuario !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        RAISE EXCEPTION 'Formato de correo electrónico inválido: %', p_correo_usuario;
    END IF;

    RETURN QUERY
    SELECT 
        d.id,
        d.fecha_add,
        d.correo,
        d.estado,
        a.id as id_archivo,
        a.nombre as nombre_archivo,
        a.tipo,
        a.tamano,
        COALESCE(u_propietario.nombre, 'Usuario sin nombre') as propietario,
        u_propietario.avatar as avatar_propietario
    FROM solicitudes_compartidos d
    INNER JOIN archivos a ON d.id_archivo = a.id
    INNER JOIN usuarios u_propietario ON d.id_usuario = u_propietario.id
    WHERE d.correo = p_correo_usuario 
    AND d.estado IN (1, 2)
    AND a.estado = 1
    ORDER BY d.fecha_add DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos compartidos: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 2. Función para obtener detalles de un archivo compartido
CREATE OR REPLACE FUNCTION obtener_detalle_archivo_compartido(
    p_id_detalle solicitudes_compartidos.id%TYPE,
    p_correo_usuario usuarios.correo%TYPE
)
RETURNS TABLE (
    id solicitudes_compartidos.id%TYPE,
    fecha_add solicitudes_compartidos.fecha_add%TYPE,
    correo solicitudes_compartidos.correo%TYPE,
    estado solicitudes_compartidos.estado%TYPE,
    id_archivo archivos.id%TYPE,
    nombre_archivo archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    tamano archivos.tamano%TYPE,
    propietario usuarios.nombre%TYPE,
    apellido_propietario usuarios.apellido%TYPE,
    avatar usuarios.avatar%TYPE,
    carpeta_nombre carpetas.nombre%TYPE
) AS $$
DECLARE
    registro_existe BOOLEAN := FALSE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_detalle IS NULL OR p_id_detalle <= 0 THEN
        RAISE EXCEPTION 'ID de detalle inválido: %', p_id_detalle;
    END IF;

    IF p_correo_usuario IS NULL OR p_correo_usuario = '' THEN
        RAISE EXCEPTION 'El correo del usuario no puede estar vacío';
    END IF;

    -- Verificar si el registro existe antes de intentar devolverlo
    SELECT EXISTS(
        SELECT 1 FROM solicitudes_compartidos d
        INNER JOIN archivos a ON d.id_archivo = a.id
        WHERE d.id = p_id_detalle 
        AND d.correo = p_correo_usuario 
        AND d.estado IN (1, 2)
        AND a.estado = 1
    ) INTO registro_existe;

    IF NOT registro_existe THEN
        RAISE EXCEPTION 'No se encontró el archivo compartido o no tienes permisos para acceder a él';
    END IF;

    RETURN QUERY
    SELECT 
        d.id,
        d.fecha_add,
        d.correo,
        d.estado,
        a.id as id_archivo,
        a.nombre as nombre_archivo,
        a.tipo,
        a.tamano,
        COALESCE(u_propietario.nombre, 'Usuario sin nombre') as propietario,
        COALESCE(u_propietario.apellido, '') as apellido_propietario,
        u_propietario.avatar as avatar,
        COALESCE(c.nombre, 'Sin carpeta') as carpeta_nombre
    FROM solicitudes_compartidos d
    INNER JOIN archivos a ON d.id_archivo = a.id
    INNER JOIN usuarios u_propietario ON d.id_usuario = u_propietario.id
    LEFT JOIN carpetas c ON a.id_carpeta = c.id
    WHERE d.id = p_id_detalle 
    AND d.correo = p_correo_usuario 
    AND d.estado IN (1, 2)
    AND a.estado = 1
    LIMIT 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener detalle del archivo compartido: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 3. Función  para verificar permisos de archivo compartido
CREATE OR REPLACE FUNCTION verificar_archivo_compartido_usuario(
    p_id_detalle solicitudes_compartidos.id%TYPE,
    p_correo_usuario usuarios.correo%TYPE
)
RETURNS TABLE (
    id solicitudes_compartidos.id%TYPE,
    correo solicitudes_compartidos.correo%TYPE,
    nombre archivos.nombre%TYPE
) AS $$
DECLARE
    registro_encontrado INTEGER := 0;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_detalle IS NULL OR p_id_detalle <= 0 THEN
        RAISE EXCEPTION 'ID de detalle inválido: %', p_id_detalle;
    END IF;

    IF p_correo_usuario IS NULL OR p_correo_usuario = '' THEN
        RAISE EXCEPTION 'El correo del usuario no puede estar vacío';
    END IF;

    -- Contar registros que coinciden
    SELECT COUNT(*) INTO registro_encontrado
    FROM solicitudes_compartidos d
    INNER JOIN archivos a ON d.id_archivo = a.id
    WHERE d.id = p_id_detalle 
    AND d.correo = p_correo_usuario 
    AND d.estado IN (1, 2);

    -- Si no se encuentra, lanzar excepción
    IF registro_encontrado = 0 THEN
        RAISE EXCEPTION 'Archivo no encontrado o sin permisos de acceso para el usuario: %', p_correo_usuario;
    END IF;

    RETURN QUERY
    SELECT 
        d.id, 
        d.correo, 
        COALESCE(a.nombre, 'Archivo sin nombre') as nombre
    FROM solicitudes_compartidos d
    INNER JOIN archivos a ON d.id_archivo = a.id
    WHERE d.id = p_id_detalle 
    AND d.correo = p_correo_usuario 
    AND d.estado IN (1, 2)
    LIMIT 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar permisos del archivo: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 4. Función mejorada para cambiar estado de archivo compartido
CREATE OR REPLACE FUNCTION cambiar_estado_archivo_compartido(
    p_estado solicitudes_compartidos.estado%TYPE,
    p_id_detalle solicitudes_compartidos.id%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    filas_afectadas INTEGER;
    registro_existe BOOLEAN := FALSE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_detalle IS NULL OR p_id_detalle <= 0 THEN
        RAISE EXCEPTION 'ID de detalle inválido: %', p_id_detalle;
    END IF;

    -- Validar estados válidos (1=nuevo, 2=visto, 0=eliminado)
    IF p_estado NOT IN (0, 1, 2) THEN
        RAISE EXCEPTION 'Estado inválido: %. Los estados válidos son: 0=eliminado, 1=nuevo, 2=visto', p_estado;
    END IF;

    -- Verificar que el registro existe
    SELECT EXISTS(SELECT 1 FROM solicitudes_compartidos WHERE id = p_id_detalle) INTO registro_existe;
    
    IF NOT registro_existe THEN
        RAISE EXCEPTION 'No se encontró la solicitud de compartido con ID: %', p_id_detalle;
    END IF;

    -- Realizar la actualización
    UPDATE solicitudes_compartidos
    SET estado = p_estado,
        elimina = CURRENT_TIMESTAMP  
    WHERE id = p_id_detalle;
    
    GET DIAGNOSTICS filas_afectadas = ROW_COUNT;
    
    -- Verificar que se actualizó al menos un registro
    IF filas_afectadas = 0 THEN
        RAISE EXCEPTION 'No se pudo actualizar el estado del archivo compartido con ID: %', p_id_detalle;
    END IF;
    
    RETURN filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al cambiar estado del archivo compartido: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 5. Función para marcar archivo como visto
CREATE OR REPLACE FUNCTION marcar_archivo_como_visto(
    p_id_detalle solicitudes_compartidos.id%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    filas_afectadas INTEGER;
    estado_actual INTEGER;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_detalle IS NULL OR p_id_detalle <= 0 THEN
        RAISE EXCEPTION 'ID de detalle inválido: %', p_id_detalle;
    END IF;

    -- Verificar el estado actual del registro
    SELECT estado INTO estado_actual 
    FROM solicitudes_compartidos 
    WHERE id = p_id_detalle;

    -- Si no se encuentra el registro
    IF estado_actual IS NULL THEN
        RAISE EXCEPTION 'No se encontró la solicitud de compartido con ID: %', p_id_detalle;
    END IF;

    -- Si ya está visto, no hacer nada pero informar
    IF estado_actual = 2 THEN
        RAISE NOTICE 'El archivo ya estaba marcado como visto';
        RETURN 0;
    END IF;

    -- Si está eliminado, no permitir marcarlo como visto
    IF estado_actual = 0 THEN
        RAISE EXCEPTION 'No se puede marcar como visto un archivo eliminado';
    END IF;

    -- Actualizar solo si el estado es 1 (nuevo)
    UPDATE solicitudes_compartidos
    SET estado = 2,
        elimina = CURRENT_TIMESTAMP  
    WHERE id = p_id_detalle 
    AND estado = 1;
    
    GET DIAGNOSTICS filas_afectadas = ROW_COUNT;
    RETURN filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al marcar archivo como visto: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;


-- 6. Función para obtener estadísticas de compartidos de usuario
CREATE OR REPLACE FUNCTION obtener_estadisticas_compartidos_usuario(
    p_correo_usuario usuarios.correo%TYPE
)
RETURNS TABLE (
    total_recibidos BIGINT,
    recibidos_hoy BIGINT
) AS $$
DECLARE
    usuario_existe BOOLEAN := FALSE;
BEGIN
    -- Validar parámetros de entrada
    IF p_correo_usuario IS NULL OR p_correo_usuario = '' THEN
        RAISE EXCEPTION 'El correo del usuario no puede estar vacío';
    END IF;

    -- Verificar que el usuario existe
    SELECT EXISTS(SELECT 1 FROM usuarios WHERE correo = p_correo_usuario AND estado = 1) INTO usuario_existe;
    
    IF NOT usuario_existe THEN
        RAISE EXCEPTION 'Usuario no encontrado o inactivo con correo: %', p_correo_usuario;
    END IF;

    RETURN QUERY
    SELECT 
        COALESCE((SELECT COUNT(*) FROM solicitudes_compartidos WHERE correo = p_correo_usuario AND estado IN (1, 2)), 0)::BIGINT as total_recibidos,
        COALESCE((SELECT COUNT(*) FROM solicitudes_compartidos WHERE correo = p_correo_usuario AND estado IN (1, 2) AND DATE(fecha_add) = CURRENT_DATE), 0)::BIGINT as recibidos_hoy;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener estadísticas del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 7. Función para obtener estadísticas globales de compartidos
CREATE OR REPLACE FUNCTION obtener_estadisticas_compartidos_globales()
RETURNS TABLE (
    total_compartidos BIGINT,
    compartidos_hoy BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COALESCE((SELECT COUNT(*) FROM solicitudes_compartidos WHERE estado IN (1, 2)), 0)::BIGINT as total_compartidos,
        COALESCE((SELECT COUNT(*) FROM solicitudes_compartidos WHERE estado IN (1, 2) AND DATE(fecha_add) = CURRENT_DATE), 0)::BIGINT as compartidos_hoy;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener estadísticas globales: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 8 funcion valida solicitud del compartido
CREATE OR REPLACE FUNCTION validar_solicitud_compartido(
    p_id_archivo archivos.id%TYPE,
    p_correo_receptor usuarios.correo%TYPE,
    p_id_usuario_propietario usuarios.id%TYPE
)
RETURNS TABLE (
    es_valido BOOLEAN,
    mensaje_error TEXT
) AS $$
DECLARE
    archivo_existe BOOLEAN := FALSE;
    usuario_receptor_existe BOOLEAN := FALSE;
    propietario_existe BOOLEAN := FALSE;
    ya_compartido BOOLEAN := FALSE;
    es_propietario BOOLEAN := FALSE;
BEGIN
    -- Validar que el archivo existe y está activo
    SELECT EXISTS(
        SELECT 1 FROM archivos 
        WHERE id = p_id_archivo AND estado = 1
    ) INTO archivo_existe;

    IF NOT archivo_existe THEN
        RETURN QUERY SELECT FALSE, 'El archivo no existe o está inactivo';
        RETURN;
    END IF;

    -- Validar que el usuario receptor existe
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE correo = p_correo_receptor AND estado = 1
    ) INTO usuario_receptor_existe;

    IF NOT usuario_receptor_existe THEN
        RETURN QUERY SELECT FALSE, 'El usuario receptor no existe o está inactivo';
        RETURN;
    END IF;

    -- Validar que el propietario existe
    SELECT EXISTS(
        SELECT 1 FROM usuarios 
        WHERE id = p_id_usuario_propietario AND estado = 1
    ) INTO propietario_existe;

    IF NOT propietario_existe THEN
        RETURN QUERY SELECT FALSE, 'El propietario no existe o está inactivo';
        RETURN;
    END IF;

    -- Validar que el usuario es propietario del archivo
    SELECT EXISTS(
        SELECT 1 FROM archivos 
        WHERE id = p_id_archivo AND id_usuario = p_id_usuario_propietario
    ) INTO es_propietario;

    IF NOT es_propietario THEN
        RETURN QUERY SELECT FALSE, 'No tienes permisos para compartir este archivo';
        RETURN;
    END IF;

    -- Validar que no se está compartiendo consigo mismo
    IF (SELECT correo FROM usuarios WHERE id = p_id_usuario_propietario) = p_correo_receptor THEN
        RETURN QUERY SELECT FALSE, 'No puedes compartir un archivo contigo mismo';
        RETURN;
    END IF;

    -- Validar que no ya está compartido con ese usuario
    SELECT EXISTS(
        SELECT 1 FROM solicitudes_compartidos 
        WHERE id_archivo = p_id_archivo 
        AND correo = p_correo_receptor 
        AND estado IN (1, 2)
    ) INTO ya_compartido;

    IF ya_compartido THEN
        RETURN QUERY SELECT FALSE, 'El archivo ya está compartido con este usuario';
        RETURN;
    END IF;

    -- Si llega aquí, la solicitud es válida
    RETURN QUERY SELECT TRUE, 'Solicitud válida'::TEXT;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error de validación: ' || SQLERRM)::TEXT;
END;
$$ LANGUAGE plpgsql;


--9. Verifica si un usuario tiene acceso a un archivo compartido
CREATE OR REPLACE FUNCTION verificar_acceso_archivo(
    p_id_archivo archivos.id%TYPE,
    p_correo_usuario usuarios.correo%TYPE
)
RETURNS TABLE (
    tiene_acceso BOOLEAN,
    id_solicitud solicitudes_compartidos.id%TYPE,
    mensaje TEXT
) AS $$
DECLARE
    v_id_solicitud solicitudes_compartidos.id%TYPE;
    v_tiene_acceso BOOLEAN := FALSE;
    v_mensaje TEXT;
    v_correo_limpio usuarios.correo%TYPE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_archivo IS NULL OR p_id_archivo <= 0 THEN
        RETURN QUERY SELECT FALSE, NULL::INTEGER, 'ID de archivo inválido'::TEXT;
        RETURN;
    END IF;

    IF p_correo_usuario IS NULL OR TRIM(p_correo_usuario) = '' THEN
        RETURN QUERY SELECT FALSE, NULL::INTEGER, 'El correo del usuario no puede estar vacío'::TEXT;
        RETURN;
    END IF;

    -- Limpiar y validar formato del correo
    v_correo_limpio := LOWER(TRIM(p_correo_usuario));
    
    IF v_correo_limpio !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        RETURN QUERY SELECT FALSE, NULL::INTEGER, 'Formato de correo electrónico inválido'::TEXT;
        RETURN;
    END IF;

    -- Verificar acceso al archivo compartido
    SELECT sc.id INTO v_id_solicitud
    FROM solicitudes_compartidos sc
    INNER JOIN archivos a ON sc.id_archivo = a.id
    WHERE sc.id_archivo = p_id_archivo 
      AND sc.correo = v_correo_limpio 
      AND sc.estado = 1 
      AND sc.aceptado = 1
      AND a.estado = 1
    LIMIT 1;

    -- Determinar resultado y mensaje
    IF v_id_solicitud IS NOT NULL THEN
        v_tiene_acceso := TRUE;
        v_mensaje := 'El usuario tiene acceso al archivo compartido';
    ELSE
        -- Verificar si existe el archivo
        IF NOT EXISTS(SELECT 1 FROM archivos WHERE id = p_id_archivo AND estado = 1) THEN
            v_mensaje := 'El archivo no existe o está inactivo';
        -- Verificar si existe solicitud pero no está aceptada
        ELSIF EXISTS(SELECT 1 FROM solicitudes_compartidos WHERE id_archivo = p_id_archivo AND correo = v_correo_limpio AND estado = 1) THEN
            v_mensaje := 'Existe una solicitud pero no ha sido aceptada';
        ELSE
            v_mensaje := 'No hay solicitudes de acceso para este archivo y usuario';
        END IF;
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_tiene_acceso, v_id_solicitud, v_mensaje;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, NULL::INTEGER, ('Error al verificar acceso: ' || SQLERRM)::TEXT;
END;
$$ LANGUAGE plpgsql;

-- 10. Función para obtener información completa de un archivo
CREATE OR REPLACE FUNCTION obtener_archivo_completo(
    p_id_archivo archivos.id%TYPE
) 
RETURNS TABLE (
    id archivos.id%TYPE,
    nombre archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    fecha_create archivos.fecha_create%TYPE,
    estado archivos.estado%TYPE,
    id_carpeta archivos.id_carpeta%TYPE,
    id_usuario archivos.id_usuario%TYPE,
    tamano archivos.tamano%TYPE,
    carpeta_nombre carpetas.nombre%TYPE,
    usuario_nombre usuarios.nombre%TYPE,
    usuario_apellido usuarios.apellido%TYPE,
    usuario_correo usuarios.correo%TYPE
) 
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        arch.id,
        arch.nombre,
        arch.tipo,
        arch.fecha_create,
        arch.estado,
        arch.id_carpeta,
        arch.id_usuario,
        arch.tamano,
        COALESCE(carp.nombre, 'Sin carpeta'),
        COALESCE(usr.nombre, 'Usuario desconocido'),
        COALESCE(usr.apellido, ''),
        COALESCE(usr.correo, '')
    FROM archivos arch
    LEFT JOIN carpetas carp ON arch.id_carpeta = carp.id
    LEFT JOIN usuarios usr ON arch.id_usuario = usr.id
    WHERE arch.id = p_id_archivo 
    AND arch.estado = 1;
END;
$$ LANGUAGE plpgsql;


