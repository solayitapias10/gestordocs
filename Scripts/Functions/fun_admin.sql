-- =====================================================
-- FUNCIONES CONTROLADOR ADMIN
-- =====================================================

-- 1. Obtiene las carpetas principales de un usuario
CREATE OR REPLACE FUNCTION obtener_carpetas_principales(
    p_id_usuario carpetas.id_usuario%TYPE
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

    -- Retornar las carpetas principales del usuario
    RETURN QUERY 
    SELECT 
        c.id,
        c.nombre,
        c.fecha_create,
        c.estado,
        c.elimina,
        c.id_usuario,
        c.id_carpeta_padre
    FROM carpetas c
    WHERE c.id_usuario = p_id_usuario 
      AND c.id_carpeta_padre IS NULL 
      AND c.estado = 1
    ORDER BY c.fecha_create DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener carpetas principales: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;


-- 2. Obtiene las subcarpetas de una carpeta específica para un usuario
CREATE OR REPLACE FUNCTION obtener_subcarpetas(
    p_id_carpeta carpetas.id_carpeta_padre%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE
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
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta padre inválido';
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Verificar que la carpeta padre existe, está activa y pertenece al usuario
    IF NOT EXISTS(
        SELECT 1 FROM carpetas cp 
        WHERE cp.id = p_id_carpeta 
          AND cp.id_usuario = p_id_usuario 
          AND cp.estado = 1
    ) THEN
        RAISE EXCEPTION 'La carpeta padre no existe, no está activa o no pertenece al usuario';
    END IF;

    -- Retornar las subcarpetas
    RETURN QUERY 
    SELECT 
        c.id,
        c.nombre,
        c.fecha_create,
        c.estado,
        c.elimina,
        c.id_usuario,
        c.id_carpeta_padre
    FROM carpetas c
    WHERE c.id_carpeta_padre = p_id_carpeta 
      AND c.id_usuario = p_id_usuario 
      AND c.estado = 1 
    ORDER BY c.id DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener subcarpetas: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;


-- 3. Verifica si ya existe una carpeta con el mismo nombre para evitar duplicados
CREATE OR REPLACE FUNCTION verificar_carpeta_existente(
    p_campo VARCHAR(50),                    
    p_valor VARCHAR(255),                   
    p_id_usuario carpetas.id_usuario%TYPE,  
    p_id_excluir carpetas.id%TYPE DEFAULT 0, 
    p_id_carpeta_padre carpetas.id_carpeta_padre%TYPE DEFAULT NULL 
)
RETURNS TABLE (
    id carpetas.id%TYPE,
    existe BOOLEAN,
    mensaje TEXT
) AS $$
DECLARE
    v_id_encontrado carpetas.id%TYPE;
    v_mensaje TEXT;
    v_existe BOOLEAN;
    v_campo_limpio VARCHAR(50);
    v_valor_limpio VARCHAR(255);
BEGIN
    -- Validar parámetros de entrada
    IF p_campo IS NULL OR TRIM(p_campo) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, FALSE, 'El campo no puede estar vacío'::TEXT;
        RETURN;
    END IF;

    IF p_valor IS NULL OR TRIM(p_valor) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, FALSE, 'El valor no puede estar vacío'::TEXT;
        RETURN;
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT NULL::INTEGER, FALSE, 'ID de usuario inválido'::TEXT;
        RETURN;
    END IF;

    -- Limpiar valores
    v_campo_limpio := TRIM(p_campo);
    v_valor_limpio := TRIM(p_valor);

    -- Validar que el campo sea permitido (por seguridad)
    IF v_campo_limpio NOT IN ('nombre') THEN
        RETURN QUERY SELECT NULL::INTEGER, FALSE, 'Campo no válido. Solo se permite: nombre'::TEXT;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RETURN QUERY SELECT NULL::INTEGER, FALSE, 'El usuario no existe o está inactivo'::TEXT;
        RETURN;
    END IF;

    -- Si se especifica carpeta padre, verificar que existe y pertenece al usuario
    IF p_id_carpeta_padre IS NOT NULL THEN
        IF NOT EXISTS(
            SELECT 1 FROM carpetas cp 
            WHERE cp.id = p_id_carpeta_padre 
              AND cp.id_usuario = p_id_usuario 
              AND cp.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, FALSE, 'La carpeta padre no existe o no pertenece al usuario'::TEXT;
            RETURN;
        END IF;
    END IF;

    -- Verificar existencia según los parámetros
    IF p_id_excluir > 0 THEN
        -- Para actualizaciones: excluir el ID específico
        IF p_id_carpeta_padre IS NULL THEN
            -- Carpeta en la raíz
            SELECT c.id INTO v_id_encontrado
            FROM carpetas c 
            WHERE c.nombre = v_valor_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre IS NULL 
              AND c.id != p_id_excluir 
              AND c.estado = 1
            LIMIT 1;
        ELSE
            -- Carpeta dentro de otra carpeta
            SELECT c.id INTO v_id_encontrado
            FROM carpetas c 
            WHERE c.nombre = v_valor_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre = p_id_carpeta_padre 
              AND c.id != p_id_excluir 
              AND c.estado = 1
            LIMIT 1;
        END IF;
    ELSE
        -- Para nuevos registros: sin exclusión
        IF p_id_carpeta_padre IS NULL THEN
            -- Carpeta en la raíz
            SELECT c.id INTO v_id_encontrado
            FROM carpetas c 
            WHERE c.nombre = v_valor_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre IS NULL 
              AND c.estado = 1
            LIMIT 1;
        ELSE
            -- Carpeta dentro de otra carpeta
            SELECT c.id INTO v_id_encontrado
            FROM carpetas c 
            WHERE c.nombre = v_valor_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre = p_id_carpeta_padre 
              AND c.estado = 1
            LIMIT 1;
        END IF;
    END IF;

    -- Determinar el resultado y mensaje
    IF v_id_encontrado IS NOT NULL THEN
        v_existe := TRUE;
        IF p_id_excluir > 0 THEN
            v_mensaje := 'Ya existe otra carpeta con este nombre en la misma ubicación. No se puede actualizar.';
        ELSE
            v_mensaje := 'Ya existe una carpeta con este nombre en la misma ubicación. No se puede crear.';
        END IF;
    ELSE
        v_existe := FALSE;
        IF p_id_excluir > 0 THEN
            v_mensaje := 'El nombre está disponible para actualización.';
        ELSE
            v_mensaje := 'El nombre está disponible para crear la carpeta.';
        END IF;
        -- Para mantener compatibilidad con el código original
        v_id_encontrado := NULL;
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_id_encontrado, v_existe, v_mensaje;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT NULL::INTEGER, TRUE, ('Error al verificar carpeta: ' || SQLERRM)::TEXT;
END;
$$ LANGUAGE plpgsql;

-- 4. Función para crear una nueva carpeta con validaciones completas
CREATE OR REPLACE FUNCTION crear_carpeta(
    p_nombre carpetas.nombre%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE,
    p_id_carpeta_padre carpetas.id_carpeta_padre%TYPE DEFAULT NULL
)
RETURNS TABLE (
    id_carpeta_nueva carpetas.id%TYPE,
    mensaje TEXT,
    exito BOOLEAN
) AS $$
DECLARE
    v_id_nueva_carpeta carpetas.id%TYPE;
    v_nombre_limpio VARCHAR(255);
    v_mensaje TEXT;
    v_exito BOOLEAN;
BEGIN
    -- Validar parámetros de entrada
    IF p_nombre IS NULL OR TRIM(p_nombre) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El nombre de la carpeta es requerido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'ID de usuario inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Limpiar el nombre
    v_nombre_limpio := TRIM(p_nombre);
    
    -- Validar longitud del nombre
    IF LENGTH(v_nombre_limpio) > 255 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El nombre de la carpeta es demasiado largo (máximo 255 caracteres)'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El usuario no existe o está inactivo'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Si se especifica carpeta padre, verificar que existe y pertenece al usuario
    IF p_id_carpeta_padre IS NOT NULL THEN
        IF NOT EXISTS(
            SELECT 1 FROM carpetas cp 
            WHERE cp.id = p_id_carpeta_padre 
              AND cp.id_usuario = p_id_usuario 
              AND cp.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'La carpeta padre no existe, no está activa o no pertenece al usuario'::TEXT, FALSE;
            RETURN;
        END IF;
    END IF;

    -- Verificar que no existe una carpeta con el mismo nombre en la misma ubicación
    IF p_id_carpeta_padre IS NULL THEN
        -- Carpeta en la raíz
        IF EXISTS(
            SELECT 1 FROM carpetas c 
            WHERE c.nombre = v_nombre_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre IS NULL 
              AND c.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'Ya existe una carpeta con este nombre en la raíz'::TEXT, FALSE;
            RETURN;
        END IF;
    ELSE
        -- Carpeta dentro de otra carpeta
        IF EXISTS(
            SELECT 1 FROM carpetas c 
            WHERE c.nombre = v_nombre_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre = p_id_carpeta_padre 
              AND c.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'Ya existe una carpeta con este nombre en esta ubicación'::TEXT, FALSE;
            RETURN;
        END IF;
    END IF;

    -- Crear la carpeta
    INSERT INTO carpetas (nombre, id_usuario, id_carpeta_padre, fecha_create, estado)
    VALUES (v_nombre_limpio, p_id_usuario, p_id_carpeta_padre, CURRENT_TIMESTAMP, 1)
    RETURNING id INTO v_id_nueva_carpeta;

    -- Verificar que se creó correctamente
    IF v_id_nueva_carpeta IS NOT NULL THEN
        v_exito := TRUE;
        v_mensaje := 'Carpeta creada exitosamente';
    ELSE
        v_exito := FALSE;
        v_mensaje := 'Error al crear la carpeta';
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_id_nueva_carpeta, v_mensaje, v_exito;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT NULL::INTEGER, ('Error al crear carpeta: ' || SQLERRM)::TEXT, FALSE;
END;
$$ LANGUAGE plpgsql;

-- 5 Función para marcar una carpeta como eliminada con validaciones
CREATE OR REPLACE FUNCTION eliminar_carpeta(
    p_id_carpeta carpetas.id%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    v_carpeta_existe carpetas%ROWTYPE;
    v_filas_afectadas INTEGER;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta inválido';
    END IF;

    -- Verificar que la carpeta existe
    SELECT * INTO v_carpeta_existe 
    FROM carpetas 
    WHERE id = p_id_carpeta;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'La carpeta no existe';
    END IF;

    -- Verificar que la carpeta no está ya eliminada
    IF v_carpeta_existe.estado = 0 THEN
        RAISE EXCEPTION 'La carpeta ya está eliminada';
    END IF;

    -- Si se proporciona id_usuario, verificar que sea el propietario
    IF p_id_usuario IS NOT NULL THEN
        IF v_carpeta_existe.id_usuario != p_id_usuario THEN
            RAISE EXCEPTION 'No tienes permiso para eliminar esta carpeta';
        END IF;
    END IF;

    -- Marcar la carpeta como eliminada con fecha de eliminación
    UPDATE carpetas 
    SET estado = 0, 
        elimina = CURRENT_TIMESTAMP + INTERVAL '30 days'
    WHERE id = p_id_carpeta;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Retornar las filas afectadas
    RETURN v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar carpeta: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 6. Procedimiento para subir un archivo con validaciones
CREATE OR REPLACE FUNCTION subir_archivo(
    p_nombre archivos.nombre%TYPE,
    p_tipo archivos.tipo%TYPE,
    p_tamano archivos.tamano%TYPE,
    p_id_carpeta archivos.id_carpeta%TYPE,
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS TABLE (
    id_archivo_nuevo archivos.id%TYPE,
    mensaje TEXT,
    exito BOOLEAN
) AS $$
DECLARE
    v_id_nuevo_archivo archivos.id%TYPE;
    v_nombre_limpio VARCHAR(255);
    v_mensaje TEXT;
    v_exito BOOLEAN;
BEGIN
    -- Validar parámetros de entrada
    IF p_nombre IS NULL OR TRIM(p_nombre) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El nombre del archivo es requerido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_tipo IS NULL OR TRIM(p_tipo) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El tipo del archivo es requerido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_tamano IS NULL OR p_tamano < 0 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El tamaño del archivo es inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'ID de usuario inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Limpiar el nombre
    v_nombre_limpio := TRIM(p_nombre);
    
    -- Validar longitud del nombre
    IF LENGTH(v_nombre_limpio) > 255 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El nombre del archivo es demasiado largo (máximo 255 caracteres)'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El usuario no existe o está inactivo'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Si se especifica carpeta, verificar que existe y pertenece al usuario
    IF p_id_carpeta IS NOT NULL THEN
        IF NOT EXISTS(
            SELECT 1 FROM carpetas c 
            WHERE c.id = p_id_carpeta 
              AND c.id_usuario = p_id_usuario 
              AND c.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'La carpeta no existe, no está activa o no pertenece al usuario'::TEXT, FALSE;
            RETURN;
        END IF;
    END IF;

    -- Verificar que no existe un archivo con el mismo nombre en la misma ubicación
    IF p_id_carpeta IS NULL THEN
        -- Archivo en la raíz
        IF EXISTS(
            SELECT 1 FROM archivos a 
            WHERE a.nombre = v_nombre_limpio 
              AND a.id_usuario = p_id_usuario 
              AND a.id_carpeta IS NULL 
              AND a.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'Ya existe un archivo con este nombre en la raíz'::TEXT, FALSE;
            RETURN;
        END IF;
    ELSE
        -- Archivo dentro de una carpeta
        IF EXISTS(
            SELECT 1 FROM archivos a 
            WHERE a.nombre = v_nombre_limpio 
              AND a.id_usuario = p_id_usuario 
              AND a.id_carpeta = p_id_carpeta 
              AND a.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'Ya existe un archivo con este nombre en esta carpeta'::TEXT, FALSE;
            RETURN;
        END IF;
    END IF;

    -- Insertar el archivo
    INSERT INTO archivos (nombre, tipo, tamano, id_carpeta, id_usuario, fecha_create, estado)
    VALUES (v_nombre_limpio, TRIM(p_tipo), p_tamano, p_id_carpeta, p_id_usuario, CURRENT_TIMESTAMP, 1)
    RETURNING id INTO v_id_nuevo_archivo;

    -- Verificar que se creó correctamente
    IF v_id_nuevo_archivo IS NOT NULL THEN
        v_exito := TRUE;
        v_mensaje := 'Archivo subido exitosamente';
    ELSE
        v_exito := FALSE;
        v_mensaje := 'Error al subir el archivo';
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_id_nuevo_archivo, v_mensaje, v_exito;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT NULL::INTEGER, ('Error al subir archivo: ' || SQLERRM)::TEXT, FALSE;
END;
$$ LANGUAGE plpgsql;

-- 7. Procedimiento para obtener archivos recientes de un usuario
CREATE OR REPLACE FUNCTION obtener_archivos_recientes(
    p_id_usuario archivos.id_usuario%TYPE,
    p_limite INTEGER DEFAULT 4
)
RETURNS TABLE (
    id archivos.id%TYPE,
    nombre archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    fecha_create archivos.fecha_create%TYPE,
    estado archivos.estado%TYPE,
    elimina archivos.elimina%TYPE,
    id_carpeta archivos.id_carpeta%TYPE,
    id_usuario archivos.id_usuario%TYPE,
    tamano archivos.tamano%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 4; -- Valor por defecto
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Retornar los archivos recientes del usuario (archivos en la raíz)
    RETURN QUERY 
    SELECT 
        a.id,
        a.nombre,
        a.tipo,
        a.fecha_create,
        a.estado,
        a.elimina,
        a.id_carpeta,
        a.id_usuario,
        a.tamano
    FROM archivos a
    WHERE a.id_usuario = p_id_usuario 
      AND a.id_carpeta IS NULL 
      AND a.estado = 1
    ORDER BY a.id DESC 
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos recientes: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 8. Procedimiento para obtener todos los archivos de una carpeta específica
CREATE OR REPLACE FUNCTION obtener_archivos_carpeta(
    p_id_carpeta archivos.id_carpeta%TYPE,
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS TABLE (
    id archivos.id%TYPE,
    nombre archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    fecha_create archivos.fecha_create%TYPE,
    estado archivos.estado%TYPE,
    elimina archivos.elimina%TYPE,
    id_carpeta archivos.id_carpeta%TYPE,
    id_usuario archivos.id_usuario%TYPE,
    tamano archivos.tamano%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta inválido';
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Verificar que la carpeta existe, está activa y pertenece al usuario
    IF NOT EXISTS(
        SELECT 1 FROM carpetas c 
        WHERE c.id = p_id_carpeta 
          AND c.id_usuario = p_id_usuario 
          AND c.estado = 1
    ) THEN
        RAISE EXCEPTION 'La carpeta no existe, no está activa o no pertenece al usuario';
    END IF;

    -- Retornar los archivos de la carpeta específica
    RETURN QUERY 
    SELECT 
        a.id,
        a.nombre,
        a.tipo,
        a.fecha_create,
        a.estado,
        a.elimina,
        a.id_carpeta,
        a.id_usuario,
        a.tamano
    FROM archivos a
    WHERE a.id_carpeta = p_id_carpeta 
      AND a.id_usuario = p_id_usuario 
      AND a.estado = 1 
    ORDER BY a.id DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos de la carpeta: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 9. Función para editar el nombre de una carpeta con validaciones completas
CREATE OR REPLACE FUNCTION editar_carpeta(
    p_nombre carpetas.nombre%TYPE,
    p_id carpetas.id%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS TABLE (
    filas_afectadas INTEGER,
    mensaje TEXT,
    exito BOOLEAN
) AS $$
DECLARE
    v_carpeta_existe carpetas%ROWTYPE;
    v_nombre_limpio VARCHAR(255);
    v_filas_afectadas INTEGER;
    v_mensaje TEXT;
    v_exito BOOLEAN;
BEGIN
    -- Validar parámetros de entrada
    IF p_nombre IS NULL OR TRIM(p_nombre) = '' THEN
        RETURN QUERY SELECT 0::INTEGER, 'El nombre de la carpeta es requerido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_id IS NULL OR p_id <= 0 THEN
        RETURN QUERY SELECT 0::INTEGER, 'ID de carpeta inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT 0::INTEGER, 'ID de usuario inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Limpiar el nombre
    v_nombre_limpio := TRIM(p_nombre);
    
    -- Validar longitud del nombre
    IF LENGTH(v_nombre_limpio) > 255 THEN
        RETURN QUERY SELECT 0::INTEGER, 'El nombre de la carpeta es demasiado largo (máximo 255 caracteres)'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RETURN QUERY SELECT 0::INTEGER, 'El usuario no existe o está inactivo'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Obtener información completa de la carpeta para validaciones
    SELECT * INTO v_carpeta_existe 
    FROM carpetas 
    WHERE id = p_id;

    IF NOT FOUND THEN
        RETURN QUERY SELECT 0::INTEGER, 'La carpeta no existe'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que la carpeta pertenece al usuario
    IF v_carpeta_existe.id_usuario != p_id_usuario THEN
        RETURN QUERY SELECT 0::INTEGER, 'No tienes permiso para editar esta carpeta'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que la carpeta está activa
    IF v_carpeta_existe.estado != 1 THEN
        RETURN QUERY SELECT 0::INTEGER, 'La carpeta está inactiva y no se puede editar'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que no existe otra carpeta con el mismo nombre en la misma ubicación
    IF v_carpeta_existe.id_carpeta_padre IS NULL THEN
        -- Carpeta en la raíz
        IF EXISTS(
            SELECT 1 FROM carpetas c 
            WHERE c.nombre = v_nombre_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre IS NULL 
              AND c.id != p_id
              AND c.estado = 1
        ) THEN
            RETURN QUERY SELECT 0::INTEGER, 'Ya existe otra carpeta con este nombre en la raíz'::TEXT, FALSE;
            RETURN;
        END IF;
    ELSE
        -- Carpeta dentro de otra carpeta
        IF EXISTS(
            SELECT 1 FROM carpetas c 
            WHERE c.nombre = v_nombre_limpio 
              AND c.id_usuario = p_id_usuario 
              AND c.id_carpeta_padre = v_carpeta_existe.id_carpeta_padre 
              AND c.id != p_id
              AND c.estado = 1
        ) THEN
            RETURN QUERY SELECT 0::INTEGER, 'Ya existe otra carpeta con este nombre en esta ubicación'::TEXT, FALSE;
            RETURN;
        END IF;
    END IF;

    -- Actualizar la carpeta
    UPDATE carpetas 
    SET nombre = v_nombre_limpio
    WHERE id = p_id 
      AND id_usuario = p_id_usuario 
      AND estado = 1;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Determinar el resultado
    IF v_filas_afectadas > 0 THEN
        v_exito := TRUE;
        v_mensaje := 'Carpeta actualizada exitosamente';
    ELSE
        v_exito := FALSE;
        v_mensaje := 'No se pudo actualizar la carpeta';
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_filas_afectadas, v_mensaje, v_exito;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT 0::INTEGER, ('Error al editar carpeta: ' || SQLERRM)::TEXT, FALSE;
END;
$$ LANGUAGE plpgsql;

-- 10. Obtiene todas las carpetas principales del sistema (para dashboard/administración)
CREATE OR REPLACE FUNCTION obtener_carpetas_principales_todas(
    p_limite INTEGER DEFAULT 6
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
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 6; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 100 THEN
        p_limite := 100;
    END IF;

    -- Retornar todas las carpetas principales activas
    RETURN QUERY 
    SELECT 
        c.id,
        c.nombre,
        c.fecha_create,
        c.estado,
        c.elimina,
        c.id_usuario,
        c.id_carpeta_padre
    FROM carpetas c
    WHERE c.id_carpeta_padre IS NULL 
      AND c.estado = 1
    ORDER BY c.id DESC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener carpetas principales: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
-- 11. Obtiene los archivos más recientes de todos los usuarios (para dashboard/administración)
CREATE OR REPLACE FUNCTION obtener_archivos_recientes_todos(
    p_limite INTEGER DEFAULT 10
)
RETURNS TABLE (
    id archivos.id%TYPE,
    nombre archivos.nombre%TYPE,
    tipo archivos.tipo%TYPE,
    fecha_create archivos.fecha_create%TYPE,
    estado archivos.estado%TYPE,
    elimina archivos.elimina%TYPE,
    id_carpeta archivos.id_carpeta%TYPE,
    id_usuario archivos.id_usuario%TYPE,
    tamano archivos.tamano%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 10; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 100 THEN
        p_limite := 100;
    END IF;

    -- Retornar los archivos más recientes de todos los usuarios
    RETURN QUERY 
    SELECT 
        a.id,
        a.nombre,
        a.tipo,
        a.fecha_create,
        a.estado,
        a.elimina,
        a.id_carpeta,
        a.id_usuario,
        a.tamano
    FROM archivos a
    WHERE a.estado = 1
    ORDER BY a.id DESC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos recientes: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 12. Obtiene el total de archivos compartidos activos del sistema (para dashboard/administración)
CREATE OR REPLACE FUNCTION verificar_estado_todos_compartidos()
RETURNS TABLE (
    total BIGINT
) AS $$
BEGIN
    -- Retornar el conteo total de solicitudes compartidas activas
    RETURN QUERY 
    SELECT 
        COUNT(sc.id)::BIGINT as total
    FROM solicitudes_compartidos sc
    WHERE sc.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar estado de compartidos: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
-- 13. Obtiene la actividad de archivos de los últimos días (para dashboard/administración)
CREATE OR REPLACE FUNCTION obtener_actividad_archivos_todos(
    p_limite INTEGER DEFAULT 30
)
RETURNS TABLE (
    fecha DATE,
    cantidad BIGINT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 30; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 365 THEN
        p_limite := 365;
    END IF;

    -- Retornar la actividad de archivos agrupada por fecha
    RETURN QUERY 
    SELECT 
        DATE(a.fecha_create) as fecha,
        COUNT(a.id)::BIGINT as cantidad
    FROM archivos a
    WHERE a.estado = 1
    GROUP BY DATE(a.fecha_create)
    ORDER BY DATE(a.fecha_create) ASC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener actividad de archivos: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 14. Obtiene estadísticas globales del sistema (versión realmente optimizada)
CREATE OR REPLACE FUNCTION obtener_estadisticas_globales()
RETURNS TABLE (
    total_carpetas BIGINT,
    total_archivos BIGINT,
    total_compartidos BIGINT,
    total_usuarios BIGINT,
    espacio_total BIGINT,
    espacio_porcentaje NUMERIC,
    carpetas_ayer BIGINT,
    archivos_ayer BIGINT,
    compartidos_ayer BIGINT,
    usuarios_ayer BIGINT,
    tendencia_carpetas NUMERIC,
    tendencia_archivos NUMERIC,
    tendencia_compartidos NUMERIC,
    tendencia_usuarios NUMERIC
) AS $$
DECLARE
    v_fecha_ayer DATE := CURRENT_DATE - INTERVAL '1 day';
    -- Constante para 10GB, evita overflow aritmético
    v_diez_gb CONSTANT NUMERIC := 10737418240;
    
    v_total_carpetas BIGINT;
    v_total_archivos BIGINT;
    v_total_compartidos BIGINT;
    v_total_usuarios BIGINT;
    v_espacio_total BIGINT;
    v_carpetas_ayer BIGINT;
    v_archivos_ayer BIGINT;
    v_compartidos_ayer BIGINT;
    v_usuarios_ayer BIGINT;
BEGIN
    -- Query única optimizada con subconsultas paralelas
    SELECT 
        -- Totales actuales
        (SELECT COUNT(*) FROM carpetas WHERE estado = 1),
        (SELECT COUNT(*) FROM archivos WHERE estado = 1),
        (SELECT COUNT(*) FROM solicitudes_compartidos WHERE estado = 1),
        (SELECT COUNT(*) FROM usuarios WHERE estado = 1),
        (SELECT COALESCE(SUM(tamano), 0) FROM archivos WHERE estado = 1),
        
        -- Totales de ayer
        (SELECT COUNT(*) FROM carpetas 
         WHERE estado = 1 AND fecha_create::date = v_fecha_ayer),
        (SELECT COUNT(*) FROM archivos 
         WHERE estado = 1 AND fecha_create::date = v_fecha_ayer),
        (SELECT COUNT(*) FROM solicitudes_compartidos 
         WHERE estado = 1 AND fecha_add::date = v_fecha_ayer),
        (SELECT COUNT(*) FROM usuarios 
         WHERE estado = 1 AND fecha::date = v_fecha_ayer)
    INTO 
        v_total_carpetas, v_total_archivos, v_total_compartidos, v_total_usuarios, v_espacio_total,
        v_carpetas_ayer, v_archivos_ayer, v_compartidos_ayer, v_usuarios_ayer;
    
    -- Retornar resultados con cálculos inline seguros
    RETURN QUERY
    SELECT 
        v_total_carpetas,
        v_total_archivos,
        v_total_compartidos,
        v_total_usuarios,
        v_espacio_total,
        
        -- Porcentaje de espacio (cálculo seguro sin overflow)
        CASE 
            WHEN v_espacio_total > 0 THEN 
                LEAST(100.0, ROUND((v_espacio_total::NUMERIC / v_diez_gb) * 100, 1))
            ELSE 0.0 
        END,
        
        v_carpetas_ayer,
        v_archivos_ayer,
        v_compartidos_ayer,
        v_usuarios_ayer,
        
        -- Tendencias (cálculos seguros sin división por cero)
        CASE WHEN v_total_carpetas > 0 THEN 
            ROUND((v_carpetas_ayer::NUMERIC / v_total_carpetas) * 100, 1) 
            ELSE 0.0 END,
        CASE WHEN v_total_archivos > 0 THEN 
            ROUND((v_archivos_ayer::NUMERIC / v_total_archivos) * 100, 1) 
            ELSE 0.0 END,
        CASE WHEN v_total_compartidos > 0 THEN 
            ROUND((v_compartidos_ayer::NUMERIC / v_total_compartidos) * 100, 1) 
            ELSE 0.0 END,
        CASE WHEN v_total_usuarios > 0 THEN 
            ROUND((v_usuarios_ayer::NUMERIC / v_total_usuarios) * 100, 1) 
            ELSE 0.0 END;

EXCEPTION
    WHEN OTHERS THEN
        -- Valores por defecto en caso de error
        RETURN QUERY SELECT 
            0::BIGINT, 0::BIGINT, 0::BIGINT, 0::BIGINT,
            0::BIGINT, 0.0::NUMERIC,
            0::BIGINT, 0::BIGINT, 0::BIGINT, 0::BIGINT,
            0.0::NUMERIC, 0.0::NUMERIC, 0.0::NUMERIC, 0.0::NUMERIC;
END;
$$ LANGUAGE plpgsql;
-- 15. Obtiene los tipos de archivos con su cantidad (para dashboard/estadísticas)
CREATE OR REPLACE FUNCTION obtener_tipos_archivos(
    p_limite INTEGER DEFAULT 5
)
RETURNS TABLE (
    tipo archivos.tipo%TYPE,
    cantidad BIGINT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 5; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 50 THEN
        p_limite := 50;
    END IF;

    -- Retornar los tipos de archivos con su cantidad
    RETURN QUERY 
    SELECT 
        a.tipo,
        COUNT(a.id)::BIGINT as cantidad
    FROM archivos a
    WHERE a.estado = 1
    GROUP BY a.tipo
    ORDER BY COUNT(a.id) DESC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener tipos de archivos: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
-- 16. Obtiene los usuarios más activos del sistema (para dashboard/estadísticas)
CREATE OR REPLACE FUNCTION obtener_usuarios_activos(
    p_limite INTEGER DEFAULT 5
)
RETURNS TABLE (
    nombre usuarios.nombre%TYPE,
    cantidad BIGINT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 5; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 50 THEN
        p_limite := 50;
    END IF;

    -- Retornar los usuarios más activos basados en la cantidad de archivos
    RETURN QUERY 
    SELECT 
        u.nombre,
        COUNT(a.id)::BIGINT as cantidad
    FROM usuarios u 
    LEFT JOIN archivos a ON u.id = a.id_usuario AND a.estado = 1
    WHERE u.estado = 1 
    GROUP BY u.id, u.nombre 
    ORDER BY COUNT(a.id) DESC 
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuarios activos: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 17. Obtiene la actividad reciente del sistema (carpetas y archivos)
CREATE OR REPLACE FUNCTION obtener_actividad_reciente(
    p_limite INTEGER DEFAULT 5
)
RETURNS TABLE (
    tipo VARCHAR(10),
    nombre VARCHAR(255),
    fecha TIMESTAMP WITH TIME ZONE,
    descripcion TEXT
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 5; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 100 THEN
        p_limite := 100;
    END IF;

    -- Retornar la actividad reciente combinando carpetas y archivos
    RETURN QUERY 
    SELECT 
        actividad.tipo::VARCHAR(10),
        actividad.nombre::VARCHAR(255),
        actividad.fecha,
        CASE 
            WHEN actividad.tipo = 'carpeta' THEN 
                ('Carpeta creada: ' || actividad.nombre)::TEXT
            ELSE 
                ('Archivo subido: ' || actividad.nombre)::TEXT
        END as descripcion
    FROM (
        SELECT 
            'carpeta'::TEXT as tipo, 
            c.nombre, 
            c.fecha_create as fecha 
        FROM carpetas c
        WHERE c.estado = 1 
        
        UNION ALL
        
        SELECT 
            'archivo'::TEXT as tipo, 
            a.nombre, 
            a.fecha_create as fecha 
        FROM archivos a
        WHERE a.estado = 1 
    ) as actividad
    ORDER BY actividad.fecha DESC 
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener actividad reciente: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
-- 18. Obtiene un usuario por su correo electrónico
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
    fecha usuarios.fecha%TYPE,
    estado usuarios.estado%TYPE,
    rol usuarios.rol%TYPE,
    avatar usuarios.avatar%TYPE,
    fecha_ultimo_cambio_clave usuarios.fecha_ultimo_cambio_clave%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_correo IS NULL OR TRIM(p_correo) = '' THEN
        RAISE EXCEPTION 'El correo electrónico es requerido';
    END IF;

    -- Validar formato básico de correo (contiene @)
    IF POSITION('@' IN p_correo) = 0 THEN
        RAISE EXCEPTION 'Formato de correo electrónico inválido';
    END IF;

    -- Retornar el usuario por correo electrónico
    RETURN QUERY 
    SELECT 
        u.id,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        u.direccion,
        u.clave,
        u.fecha,
        u.estado,
        u.rol,
        u.avatar,
        u.fecha_ultimo_cambio_clave
    FROM usuarios u
    WHERE u.correo = TRIM(p_correo) 
      AND u.estado = 1
    LIMIT 1; -- Asegurar que solo retorne un registro

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener usuario por correo: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 19. Procedimiento para eliminar una carpeta con validaciones completas
CREATE OR REPLACE FUNCTION eliminar_carpeta_usuario(
    p_id carpetas.id%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS TABLE (
    filas_afectadas INTEGER,
    mensaje TEXT,
    exito BOOLEAN
) AS $$
DECLARE
    v_carpeta_existe carpetas%ROWTYPE;
    v_filas_afectadas INTEGER;
    v_mensaje TEXT;
    v_exito BOOLEAN;
    v_fecha_elimina TIMESTAMP WITH TIME ZONE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id IS NULL OR p_id <= 0 THEN
        RETURN QUERY SELECT 0::INTEGER, 'ID de carpeta inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT 0::INTEGER, 'ID de usuario inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RETURN QUERY SELECT 0::INTEGER, 'El usuario no existe o está inactivo'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Obtener información completa de la carpeta para validaciones
    SELECT * INTO v_carpeta_existe 
    FROM carpetas 
    WHERE id = p_id;

    IF NOT FOUND THEN
        RETURN QUERY SELECT 0::INTEGER, 'La carpeta no existe'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que la carpeta pertenece al usuario
    IF v_carpeta_existe.id_usuario != p_id_usuario THEN
        RETURN QUERY SELECT 0::INTEGER, 'No tienes permiso para eliminar esta carpeta'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que la carpeta no está ya eliminada
    IF v_carpeta_existe.estado = 0 THEN
        RETURN QUERY SELECT 0::INTEGER, 'La carpeta ya está eliminada'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Calcular fecha de eliminación (30 días desde ahora)
    v_fecha_elimina := CURRENT_TIMESTAMP + INTERVAL '30 days';

    -- Marcar la carpeta como eliminada con fecha de eliminación
    UPDATE carpetas 
    SET estado = 0, 
        elimina = v_fecha_elimina
    WHERE id = p_id 
      AND id_usuario = p_id_usuario 
      AND estado = 1;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Determinar el resultado
    IF v_filas_afectadas > 0 THEN
        v_exito := TRUE;
        v_mensaje := 'Carpeta eliminada. Se ocultará permanentemente en 30 días.';
    ELSE
        v_exito := FALSE;
        v_mensaje := 'No se pudo eliminar la carpeta';
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_filas_afectadas, v_mensaje, v_exito;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT 0::INTEGER, ('Error al eliminar carpeta: ' || SQLERRM)::TEXT, FALSE;
END;
$$ LANGUAGE plpgsql;
-- 20. Obtiene los archivos compartidos más recientes del sistema
CREATE OR REPLACE FUNCTION obtener_archivos_compartidos_recientes(
    p_limite INTEGER DEFAULT 2
)
RETURNS TABLE (
    id solicitudes_compartidos.id%TYPE,
    nombre_archivo archivos.nombre%TYPE,
    usuario_propietario usuarios.nombre%TYPE,
    fecha_add solicitudes_compartidos.fecha_add%TYPE,
    correo solicitudes_compartidos.correo%TYPE,
    id_archivo solicitudes_compartidos.id_archivo%TYPE,
    id_usuario solicitudes_compartidos.id_usuario%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 2; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 100 THEN
        p_limite := 100;
    END IF;

    -- Retornar los archivos compartidos más recientes
    RETURN QUERY 
    SELECT 
        sc.id,
        a.nombre as nombre_archivo,
        u.nombre as usuario_propietario,
        sc.fecha_add,
        sc.correo,
        sc.id_archivo,
        sc.id_usuario
    FROM solicitudes_compartidos sc
    INNER JOIN archivos a ON sc.id_archivo = a.id
    INNER JOIN usuarios u ON sc.id_usuario = u.id
    WHERE sc.estado = 1
      AND a.estado = 1
      AND u.estado = 1
    ORDER BY sc.fecha_add DESC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos compartidos recientes: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
-- 21. Procedimiento para registrar una notificación con validaciones completas
CREATE OR REPLACE FUNCTION registrar_notificacion(
    p_id_usuario notificaciones.id_usuario%TYPE,
    p_id_carpeta notificaciones.id_carpeta%TYPE,
    p_nombre notificaciones.nombre%TYPE,
    p_evento notificaciones.evento%TYPE
)
RETURNS TABLE (
    id_notificacion_nueva notificaciones.id%TYPE,
    mensaje TEXT,
    exito BOOLEAN
) AS $$
DECLARE
    v_id_nueva_notificacion notificaciones.id%TYPE;
    v_nombre_limpio VARCHAR(255);
    v_evento_limpio VARCHAR(100);
    v_mensaje TEXT;
    v_exito BOOLEAN;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'ID de usuario inválido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_nombre IS NULL OR TRIM(p_nombre) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El nombre es requerido'::TEXT, FALSE;
        RETURN;
    END IF;

    IF p_evento IS NULL OR TRIM(p_evento) = '' THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El evento es requerido'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Limpiar valores
    v_nombre_limpio := TRIM(p_nombre);
    v_evento_limpio := TRIM(p_evento);
    
    -- Validar longitud de los campos
    IF LENGTH(v_nombre_limpio) > 255 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El nombre es demasiado largo (máximo 255 caracteres)'::TEXT, FALSE;
        RETURN;
    END IF;

    IF LENGTH(v_evento_limpio) > 100 THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El evento es demasiado largo (máximo 100 caracteres)'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RETURN QUERY SELECT NULL::INTEGER, 'El usuario no existe o está inactivo'::TEXT, FALSE;
        RETURN;
    END IF;

    -- Si se especifica carpeta, verificar que existe y pertenece al usuario
    IF p_id_carpeta IS NOT NULL THEN
        IF NOT EXISTS(
            SELECT 1 FROM carpetas c 
            WHERE c.id = p_id_carpeta 
              AND c.id_usuario = p_id_usuario 
              AND c.estado = 1
        ) THEN
            RETURN QUERY SELECT NULL::INTEGER, 'La carpeta no existe, no está activa o no pertenece al usuario'::TEXT, FALSE;
            RETURN;
        END IF;
    END IF;

    -- Insertar la notificación
    INSERT INTO notificaciones (id_usuario, id_carpeta, nombre, evento, fecha, leida)
    VALUES (p_id_usuario, p_id_carpeta, v_nombre_limpio, v_evento_limpio, CURRENT_TIMESTAMP, 0)
    RETURNING id INTO v_id_nueva_notificacion;

    -- Verificar que se creó correctamente
    IF v_id_nueva_notificacion IS NOT NULL THEN
        v_exito := TRUE;
        v_mensaje := 'Notificación registrada exitosamente';
    ELSE
        v_exito := FALSE;
        v_mensaje := 'Error al registrar la notificación';
    END IF;

    -- Retornar resultado
    RETURN QUERY SELECT v_id_nueva_notificacion, v_mensaje, v_exito;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT NULL::INTEGER, ('Error al registrar notificación: ' || SQLERRM)::TEXT, FALSE;
END;
$$ LANGUAGE plpgsql;
-- 22. Obtiene las notificaciones no leídas de un usuario específico
CREATE OR REPLACE FUNCTION obtener_notificaciones(
    p_id_usuario notificaciones.id_usuario%TYPE,
    p_limite INTEGER DEFAULT 10
)
RETURNS TABLE (
    id notificaciones.id%TYPE,
    id_usuario notificaciones.id_usuario%TYPE,
    id_carpeta notificaciones.id_carpeta%TYPE,
    id_solicitud notificaciones.id_solicitud%TYPE,
    nombre notificaciones.nombre%TYPE,
    evento notificaciones.evento%TYPE,
    fecha notificaciones.fecha%TYPE,
    leida notificaciones.leida%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    IF p_limite IS NULL OR p_limite <= 0 THEN
        p_limite := 10; -- Valor por defecto
    END IF;

    -- Limitar el número máximo para evitar consultas muy grandes
    IF p_limite > 100 THEN
        p_limite := 100;
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Retornar las notificaciones no leídas del usuario
    RETURN QUERY 
    SELECT 
        n.id,
        n.id_usuario,
        n.id_carpeta,
        n.id_solicitud,
        n.nombre,
        n.evento,
        n.fecha,
        n.leida
    FROM notificaciones n
    WHERE n.id_usuario = p_id_usuario 
      AND n.leida = 0
    ORDER BY n.fecha DESC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener notificaciones: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;
-- 23. Función para marcar una notificación como leída
CREATE OR REPLACE FUNCTION marcar_notificacion_leida(
    p_id_notificacion notificaciones.id%TYPE,
    p_id_usuario notificaciones.id_usuario%TYPE
)
RETURNS BOOLEAN AS $$
DECLARE
    v_filas_afectadas INTEGER;
BEGIN
    -- 1. Validar parámetros de entrada
    IF p_id_notificacion IS NULL OR p_id_notificacion <= 0 THEN
        RAISE EXCEPTION 'ID de notificación inválido';
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- 2. Verificar que la notificación existe y pertenece al usuario
    IF NOT EXISTS(
        SELECT 1 FROM notificaciones n
        WHERE n.id = p_id_notificacion AND n.id_usuario = p_id_usuario
    ) THEN
        RAISE EXCEPTION 'La notificación no existe o no pertenece al usuario.';
    END IF;

    -- 3. Marcar la notificación como leída
    UPDATE notificaciones 
    SET leida = 1 
    WHERE id = p_id_notificacion AND id_usuario = p_id_usuario;

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- 4. Retornar TRUE si se actualizó una fila, de lo contrario FALSE
    RETURN v_filas_afectadas > 0;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al marcar la notificación como leída: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;