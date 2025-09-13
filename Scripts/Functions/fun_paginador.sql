-- 1. Obtener el total de archivos en una carpeta específica
CREATE OR REPLACE FUNCTION obtener_total_archivos_carpeta(
    p_id_carpeta archivos.id_carpeta%TYPE
)
RETURNS TABLE (
    total BIGINT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta inválido';
    END IF;

    -- Verificar que la carpeta existe y está activa
    IF NOT EXISTS(
        SELECT 1 
        FROM carpetas c 
        WHERE c.id = p_id_carpeta 
        AND c.estado = 1
    ) THEN
        RAISE EXCEPTION 'La carpeta no existe o está inactiva';
    END IF;

    -- Retornar el total de archivos activos en la carpeta
    RETURN QUERY 
    SELECT 
        COUNT(a.id) as total
    FROM 
        archivos a
    WHERE 
        a.id_carpeta = p_id_carpeta 
        AND a.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener total de archivos en la carpeta: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 2. Obtener archivos paginados de un usuario
CREATE OR REPLACE FUNCTION obtener_archivos_paginado(
    p_id_usuario archivos.id_usuario%TYPE,
    p_limit INTEGER DEFAULT 15,
    p_offset INTEGER DEFAULT 0
)
RETURNS TABLE (
    id archivos.id%TYPE,
    nombre archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    fecha_create archivos.fecha_create%TYPE,
    id_carpeta archivos.id_carpeta%TYPE,
    tamano archivos.tamano%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    IF p_limit IS NULL OR p_limit <= 0 THEN
        RAISE EXCEPTION 'Límite debe ser mayor a 0';
    END IF;

    IF p_offset IS NULL OR p_offset < 0 THEN
        RAISE EXCEPTION 'Offset no puede ser negativo';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(
        SELECT 1 
        FROM usuarios u 
        WHERE u.id = p_id_usuario 
        AND u.estado = 1
    ) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Retornar archivos paginados del usuario
    RETURN QUERY 
    SELECT 
        a.id,
        a.nombre,
        a.tipo,
        a.fecha_create,
        a.id_carpeta,
        a.tamano
    FROM 
        archivos a
    WHERE 
        a.id_usuario = p_id_usuario 
        AND a.estado = 1
    ORDER BY 
        a.fecha_create DESC
    LIMIT p_limit
    OFFSET p_offset;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos paginados del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 3. Obtener el total de archivos de un usuario
CREATE OR REPLACE FUNCTION obtener_total_archivos_usuario(
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS TABLE (
    total BIGINT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(
        SELECT 1 
        FROM usuarios u 
        WHERE u.id = p_id_usuario 
        AND u.estado = 1
    ) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Retornar el total de archivos activos del usuario
    RETURN QUERY 
    SELECT 
        COUNT(a.id) as total
    FROM 
        archivos a
    WHERE 
        a.id_usuario = p_id_usuario 
        AND a.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener total de archivos del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

--4. Obtener carpetas principales paginadas de un usuario
CREATE OR REPLACE FUNCTION obtener_carpetas_paginado(
    p_id_usuario carpetas.id_usuario%TYPE,
    p_limit INTEGER DEFAULT 10,
    p_offset INTEGER DEFAULT 0
)
RETURNS TABLE (
    id carpetas.id%TYPE,
    nombre carpetas.nombre%TYPE,
    fecha_create carpetas.fecha_create%TYPE,
    estado carpetas.estado%TYPE,
    elimina carpetas.elimina%TYPE,
    id_usuario carpetas.id_usuario%TYPE,
    id_carpeta_padre carpetas.id_carpeta_padre%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    IF p_limit IS NULL OR p_limit <= 0 THEN
        RAISE EXCEPTION 'Límite debe ser mayor a 0';
    END IF;

    IF p_offset IS NULL OR p_offset < 0 THEN
        RAISE EXCEPTION 'Offset no puede ser negativo';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(
        SELECT 1 
        FROM usuarios u 
        WHERE u.id = p_id_usuario 
        AND u.estado = 1
    ) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Retornar carpetas principales paginadas del usuario
    RETURN QUERY 
    SELECT 
        c.id,
        c.nombre,
        c.fecha_create,
        c.estado,
        c.elimina,
        c.id_usuario,
        c.id_carpeta_padre
    FROM 
        carpetas c
    WHERE 
        c.id_usuario = p_id_usuario 
        AND c.id_carpeta_padre IS NULL 
        AND c.estado = 1
    ORDER BY 
        c.nombre ASC
    LIMIT p_limit
    OFFSET p_offset;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener carpetas paginadas del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 5. Obtener el total de carpetas principales de un usuario
CREATE OR REPLACE FUNCTION obtener_total_carpetas_usuario(
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS TABLE (
    total BIGINT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(
        SELECT 1 
        FROM usuarios u 
        WHERE u.id = p_id_usuario 
        AND u.estado = 1
    ) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Retornar el total de carpetas principales activas del usuario
    RETURN QUERY 
    SELECT 
        COUNT(c.id) as total
    FROM 
        carpetas c
    WHERE 
        c.id_usuario = p_id_usuario 
        AND c.id_carpeta_padre IS NULL 
        AND c.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener total de carpetas del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

