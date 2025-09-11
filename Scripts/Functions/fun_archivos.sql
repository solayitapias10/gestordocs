-- =====================================================
-- FUNCIONES CONTROLADOR ARCHIVOS
-- =====================================================
-- 1. Funcion para obtener todos los archivos de un usuario que no estan en carpetas
CREATE
OR REPLACE FUNCTION obtener_archivos_usuario(p_id_usuario usuarios.id % TYPE) RETURNS TABLE (
    id archivos.id % TYPE,
    nombre archivos.nombre % TYPE,
    tipo archivos.tipo % TYPE,
    fecha_create archivos.fecha_create % TYPE,
    estado archivos.estado % TYPE,
    elimina archivos.elimina % TYPE,
    id_carpeta archivos.id_carpeta % TYPE,
    id_usuario archivos.id_usuario % TYPE,
    tamano archivos.tamano % TYPE
) AS $$ BEGIN 
IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invÃ¡lido';

END IF;

-- Verificar que el usuario existe y estÃ¡ activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario no existe o estÃ¡ inactivo';

END IF;

-- Retornar archivos que no estÃ¡n en carpetas, ordenados por ID DESC
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
FROM
    archivos a
WHERE
    a.id_usuario = p_id_usuario
    AND a.id_carpeta IS NULL
    AND a.estado = 1
ORDER BY
    a.id DESC;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al obtener archivos del usuario: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 2. Obtener carpertas del usuario
CREATE
OR REPLACE FUNCTION obtener_carpetas_principales_usuario(p_id_usuario usuarios.id % TYPE) RETURNS TABLE (
    id carpetas.id % TYPE,
    nombre carpetas.nombre % TYPE,
    fecha_create carpetas.fecha_create % TYPE,
    estado carpetas.estado % TYPE,
    elimina carpetas.elimina % TYPE,
    id_usuario carpetas.id_usuario % TYPE,
    id_carpeta_padre carpetas.id_carpeta_padre % TYPE
) AS $$ BEGIN 
IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invÃ¡lido';

END IF;

-- Verificar que el usuario existe y estÃ¡ activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario no existe o estÃ¡ inactivo';

END IF;

-- Retornar carpetas principales (sin carpeta padre) del usuario activas
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
    c.fecha_create DESC;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al obtener carpetas del usuario: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 3. Busqueda de usuarios para compartir busqueda por correo minimo 2 caracteres para buscar 
CREATE
OR REPLACE FUNCTION buscar_usuarios_por_correo(
    p_valor_busqueda VARCHAR(100),
    p_id_usuario_actual usuarios.id % TYPE
) RETURNS TABLE (
    id usuarios.id % TYPE,
    nombre usuarios.nombre % TYPE,
    apellido usuarios.apellido % TYPE,
    correo usuarios.correo % TYPE,
    telefono usuarios.telefono % TYPE,
    direccion usuarios.direccion % TYPE,
    rol usuarios.rol % TYPE,
    estado usuarios.estado % TYPE,
    fecha usuarios.fecha % TYPE,
    avatar usuarios.avatar % TYPE
) AS $$ BEGIN 
IF p_id_usuario_actual IS NULL
OR p_id_usuario_actual <= 0 THEN RAISE EXCEPTION 'ID de usuario actual invÃ¡lido';

END IF;

IF p_valor_busqueda IS NULL THEN p_valor_busqueda := '';

END IF;

-- Verificar que el usuario actual existe y estÃ¡ activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario_actual
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario actual no existe o estÃ¡ inactivo';

END IF;

-- Buscar usuarios por correo electrÃ³nico (case insensitive)
RETURN QUERY
SELECT
    u.id,
    u.nombre,
    u.apellido,
    u.correo,
    u.telefono,
    u.direccion,
    u.rol,
    u.estado,
    u.fecha,
    u.avatar
FROM
    usuarios u
WHERE
    u.correo ILIKE '%' || TRIM(p_valor_busqueda) || '%'
    AND u.id != p_id_usuario_actual
    AND u.estado = 1
ORDER BY
    -- Priorizar coincidencias exactas, luego que empiecen con el valor, luego el resto
    CASE
        WHEN LOWER(u.correo) = LOWER(TRIM(p_valor_busqueda)) THEN 1
        WHEN LOWER(u.correo) LIKE LOWER(TRIM(p_valor_busqueda)) || '%' THEN 2
        ELSE 3
    END,
    u.correo ASC
LIMIT
    10;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al buscar usuarios por correo: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 4. Registrar solicitud de compartido
CREATE
OR REPLACE FUNCTION registrar_solicitud_compartida(
    p_correo solicitudes_compartidos.correo % TYPE,
    p_id_archivo solicitudes_compartidos.id_archivo % TYPE,
    p_id_usuario solicitudes_compartidos.id_usuario % TYPE
) RETURNS solicitudes_compartidos.id % TYPE AS $$ DECLARE v_id_solicitud_nueva solicitudes_compartidos.id % TYPE;

BEGIN 
IF p_correo IS NULL
OR TRIM(p_correo) = '' THEN RAISE EXCEPTION 'El correo no puede estar vacÃ­o';

END IF;

IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo invÃ¡lido';

END IF;

IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invÃ¡lido';

END IF;

-- Insertar nueva solicitud compartida
INSERT INTO
    solicitudes_compartidos (
        correo,
        id_archivo,
        id_usuario
    )
VALUES
    (
        TRIM(p_correo),
        p_id_archivo,
        p_id_usuario
    ) RETURNING id INTO v_id_solicitud_nueva;

RETURN v_id_solicitud_nueva;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al registrar solicitud compartida: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 5. Obtener detalle de solicitud compartida
CREATE
OR REPLACE FUNCTION obtener_detalle_solicitud_compartida(
    p_correo solicitudes_compartidos.correo % TYPE,
    p_id_archivo solicitudes_compartidos.id_archivo % TYPE
) RETURNS TABLE (
    id solicitudes_compartidos.id % TYPE,
    fecha_add solicitudes_compartidos.fecha_add % TYPE,
    correo solicitudes_compartidos.correo % TYPE,
    estado solicitudes_compartidos.estado % TYPE,
    elimina solicitudes_compartidos.elimina % TYPE,
    id_archivo solicitudes_compartidos.id_archivo % TYPE,
    id_usuario solicitudes_compartidos.id_usuario % TYPE,
    aceptado solicitudes_compartidos.aceptado % TYPE
) AS $$ BEGIN 
IF p_correo IS NULL
OR TRIM(p_correo) = '' THEN RAISE EXCEPTION 'El correo no puede estar vacio';

END IF;

IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo invÃ¡lido';

END IF;

-- Verificar que el archivo existe
IF NOT EXISTS(
    SELECT
        1
    FROM
        archivos a
    WHERE
        a.id = p_id_archivo
) THEN RAISE EXCEPTION 'El archivo especificado no existe';

END IF;

-- Retornar detalle de la solicitud compartida si existe
RETURN QUERY
SELECT
    sc.id,
    sc.fecha_add,
    sc.correo,
    sc.estado,
    sc.elimina,
    sc.id_archivo,
    sc.id_usuario,
    sc.aceptado
FROM
    solicitudes_compartidos sc
WHERE
    sc.correo = TRIM(p_correo)
    AND sc.id_archivo = p_id_archivo
LIMIT
    1;

-- Evitar multiples resultados si hay duplicados
EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al obtener detalle de solicitud compartida: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 6. Obtener archivos dentro de una carpeta especifica
CREATE
OR REPLACE FUNCTION obtener_archivos_carpeta(p_id_carpeta carpetas.id % TYPE) RETURNS TABLE (
    id archivos.id % TYPE,
    nombre archivos.nombre % TYPE,
    tipo archivos.tipo % TYPE,
    fecha_create archivos.fecha_create % TYPE,
    estado archivos.estado % TYPE,
    elimina archivos.elimina % TYPE,
    id_carpeta archivos.id_carpeta % TYPE,
    id_usuario archivos.id_usuario % TYPE,
    tamano archivos.tamano % TYPE
) AS $$ BEGIN 
IF p_id_carpeta IS NULL
OR p_id_carpeta <= 0 THEN RAISE EXCEPTION 'ID de carpeta invalido';

END IF;

-- Verificar que la carpeta existe y estÃ¡ activa
IF NOT EXISTS(
    SELECT
        1
    FROM
        carpetas c
    WHERE
        c.id = p_id_carpeta
        AND c.estado = 1
) THEN RAISE EXCEPTION 'La carpeta no existe o estÃ¡ inactiva';

END IF;

-- Retornar archivos de la carpeta especÃ­fica, ordenados por fecha de creaciÃ³n DESC
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
FROM
    archivos a
WHERE
    a.id_carpeta = p_id_carpeta
    AND a.estado = 1
ORDER BY
    a.fecha_create DESC;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al obtener archivos de la carpeta: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 7. Eliminar archivo compartido (marcar como eliminado)
CREATE
OR REPLACE FUNCTION eliminar_solicitud_compartida(
    p_fecha_elimina solicitudes_compartidos.elimina % TYPE,
    p_id_solicitud solicitudes_compartidos.id % TYPE
) RETURNS INTEGER AS $$ DECLARE v_filas_afectadas INTEGER := 0;

BEGIN 
IF p_id_solicitud IS NULL
OR p_id_solicitud <= 0 THEN RAISE EXCEPTION 'ID de solicitud invalido';

END IF;

IF p_fecha_elimina IS NULL THEN RAISE EXCEPTION 'Fecha de eliminacion no puede ser nula';

END IF;

-- Verificar que la solicitud existe y estÃ¡ activa
IF NOT EXISTS(
    SELECT
        1
    FROM
        solicitudes_compartidos sc
    WHERE
        sc.id = p_id_solicitud
        AND sc.estado = 1
) THEN RAISE EXCEPTION 'La solicitud compartida no existe o ya esta eliminada';

END IF;

-- Actualizar el estado y fecha de eliminaciÃ³n
UPDATE
    solicitudes_compartidos
SET
    estado = 0,
    elimina = p_fecha_elimina
WHERE
    id = p_id_solicitud
    AND estado = 1;

GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

RETURN v_filas_afectadas;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al eliminar solicitud compartida: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 8. Eliminar archivo (marcar como eliminado)
CREATE
OR REPLACE FUNCTION eliminar_archivo(
    p_fecha_elimina archivos.elimina % TYPE,
    p_id_archivo archivos.id % TYPE
) RETURNS INTEGER AS $$ DECLARE v_filas_afectadas INTEGER := 0;

BEGIN 
IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo invalido';

END IF;

IF p_fecha_elimina IS NULL THEN RAISE EXCEPTION 'Fecha de eliminaciÃ³n no puede ser nula';

END IF;

-- Verificar que el archivo existe y estÃ¡ activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        archivos a
    WHERE
        a.id = p_id_archivo
        AND a.estado = 1
) THEN RAISE EXCEPTION 'El archivo no existe o ya estÃ¡ eliminado';

END IF;

-- Actualizar el estado y fecha de eliminaciÃ³n
UPDATE
    archivos
SET
    estado = 0,
    elimina = p_fecha_elimina
WHERE
    id = p_id_archivo
    AND estado = 1;

-- Solo actualizar si estÃ¡ activo
GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

RETURN v_filas_afectadas;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al eliminar archivo: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 9. Verificar estado de solicitudes compartidas por correo
CREATE
OR REPLACE FUNCTION verificar_estado_solicitudes_compartidas(
    p_correo solicitudes_compartidos.correo % TYPE
) RETURNS TABLE (total BIGINT) AS $$ BEGIN 
IF p_correo IS NULL
OR TRIM(p_correo) = '' THEN RAISE EXCEPTION 'El correo no puede estar vacio';

END IF;

-- Validar formato bÃ¡sico de correo
IF p_correo !~ '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$' THEN RAISE EXCEPTION 'Formato de correo invÃ¡lido';

END IF;

-- Retornar conteo de solicitudes compartidas activas para el correo
RETURN QUERY
SELECT
    COUNT(sc.id) as total
FROM
    solicitudes_compartidos sc
WHERE
    sc.correo = TRIM(p_correo)
    AND sc.estado = 1;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al verificar estado de solicitudes compartidas: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 10. Buscar archivos por nombre en carpetas del usuario
CREATE
OR REPLACE FUNCTION buscar_archivos_usuario(
    p_valor_busqueda VARCHAR(255),
    p_id_usuario usuarios.id % TYPE
) RETURNS TABLE (
    id archivos.id % TYPE,
    nombre archivos.nombre % TYPE,
    tipo archivos.tipo % TYPE,
    fecha_create archivos.fecha_create % TYPE,
    estado archivos.estado % TYPE,
    elimina archivos.elimina % TYPE,
    id_carpeta archivos.id_carpeta % TYPE,
    id_usuario archivos.id_usuario % TYPE,
    tamano archivos.tamano % TYPE
) AS $$ BEGIN 
IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invalido';

END IF;

-- Verificar que el usuario existe y estÃ¡ activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario no existe o esta inactivo';

END IF;

IF p_valor_busqueda IS NULL THEN p_valor_busqueda := '';

END IF;

-- Limpiar y validar el valor de bÃºsqueda
p_valor_busqueda := TRIM(p_valor_busqueda);

-- Buscar archivos por nombre en carpetas del usuario
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
FROM
    archivos a
    INNER JOIN carpetas c ON a.id_carpeta = c.id
WHERE
    a.nombre ILIKE '%' || p_valor_busqueda || '%'
    AND c.id_usuario = p_id_usuario
    AND a.estado = 1
    AND c.estado = 1
ORDER BY
    CASE
        WHEN LOWER(a.nombre) = LOWER(p_valor_busqueda) THEN 1
        WHEN LOWER(a.nombre) LIKE LOWER(p_valor_busqueda) || '%' THEN 2
        ELSE 3
    END,
    a.fecha_create DESC
LIMIT
    10;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al buscar archivos del usuario: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 11. Obtener archivo especifico por ID y usuario
CREATE
OR REPLACE FUNCTION obtener_archivo_por_id_usuario(
    p_id_archivo archivos.id % TYPE,
    p_id_usuario archivos.id_usuario % TYPE
) RETURNS TABLE (
    id archivos.id % TYPE,
    nombre archivos.nombre % TYPE,
    tipo archivos.tipo % TYPE,
    fecha_create archivos.fecha_create % TYPE,
    estado archivos.estado % TYPE,
    elimina archivos.elimina % TYPE,
    id_carpeta archivos.id_carpeta % TYPE,
    id_usuario archivos.id_usuario % TYPE,
    tamano archivos.tamano % TYPE
) AS $$ BEGIN 
IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo invalido';

END IF;

IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invalido';

END IF;

-- Verificar que el usuario existe y esta activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario no existe o esta inactivo';

END IF;

-- Retornar el archivo si pertenece al usuario y esta activo
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
FROM
    archivos a
WHERE
    a.id = p_id_archivo
    AND a.id_usuario = p_id_usuario
    AND a.estado = 1;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al obtener archivo del usuario: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 12. Eliminar carpeta de forma logica con validacion de usuario
CREATE OR REPLACE FUNCTION eliminar_carpeta(
    p_id_carpeta carpetas.id % TYPE,
    p_id_usuario carpetas.id_usuario % TYPE,
	    p_dias_eliminacion INTEGER DEFAULT 30
) RETURNS INTEGER AS $$ DECLARE v_filas_afectadas INTEGER := 0;

v_fecha_elimina TIMESTAMP WITH TIME ZONE;

v_carpeta_existe carpetas % ROWTYPE;

BEGIN -- Validar parÃ¡metros de entrada
IF p_id_carpeta IS NULL
OR p_id_carpeta <= 0 THEN RAISE EXCEPTION 'ID de carpeta invÃ¡lido';

END IF;

IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invÃ¡lido';

END IF;

IF p_dias_eliminacion IS NULL
OR p_dias_eliminacion < 1 THEN RAISE EXCEPTION 'DÃ­as de eliminaciÃ³n debe ser mayor a 0';

END IF;

-- Verificar que el usuario existe y estÃ¡ activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario no existe o estÃ¡ inactivo';

END IF;

-- Verificar que la carpeta existe, estÃ¡ activa y pertenece al usuario
SELECT
    * INTO v_carpeta_existe
FROM
    carpetas
WHERE
    id = p_id_carpeta
    AND id_usuario = p_id_usuario;

IF NOT FOUND THEN RAISE EXCEPTION 'La carpeta no existe o no pertenece al usuario';

END IF;

IF v_carpeta_existe.estado = 0 THEN RAISE EXCEPTION 'La carpeta ya estÃ¡ marcada como eliminada';

END IF;

-- Calcular fecha de eliminaciÃ³n
v_fecha_elimina := CURRENT_TIMESTAMP + (p_dias_eliminacion || ' days') :: INTERVAL;

-- Actualizar el estado de la carpeta a eliminada
UPDATE
    carpetas
SET
    estado = 0,
    elimina = v_fecha_elimina
WHERE
    id = p_id_carpeta
    AND id_usuario = p_id_usuario
    AND estado = 1;

GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

-- Verificar que se actualizÃ³ al menos una fila
IF v_filas_afectadas = 0 THEN RAISE EXCEPTION 'No se pudo eliminar la carpeta. Verifique permisos y estado';

END IF;

RETURN v_filas_afectadas;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al eliminar carpeta del usuario: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 13. Obtener carpetas en la papelera de un usuario
CREATE OR REPLACE FUNCTION obtener_carpetas_papelera(
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
    -- Validar parÃ¡metros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario invÃ¡lido';
    END IF;

    -- Verificar que el usuario existe y estÃ¡ activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o estÃ¡ inactivo';
    END IF;

    -- Retornar carpetas en la papelera del usuario (estado = 0)
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
      AND c.estado = 0
    ORDER BY c.elimina DESC, c.fecha_create DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener carpetas de la papelera: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 14. Obtener archivos en la papelera de un usuario
CREATE OR REPLACE FUNCTION obtener_archivos_papelera(
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
    -- Validar parÃ¡metros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario invalido';
    END IF;

    -- Verificar que el usuario existe y estÃ¡ activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o esta inactivo';
    END IF;

    -- Retornar archivos en la papelera del usuario (estado = 0)
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
      AND a.estado = 0
    ORDER BY a.elimina DESC, a.fecha_create DESC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos de la papelera: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 15. Restaurar carpeta desde la papelera con validaciÃ³n de usuario
CREATE OR REPLACE FUNCTION restaurar_carpeta(
    p_id_carpeta carpetas.id%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
    v_carpeta_existe carpetas%ROWTYPE;
BEGIN
    -- Validar parÃ¡metros de entrada
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta invÃ¡lido';
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario invÃ¡lido';
    END IF;

    -- Verificar que el usuario existe y estÃ¡ activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o estÃ¡ inactivo';
    END IF;

    -- Verificar que la carpeta existe, estÃ¡ eliminada y pertenece al usuario
    SELECT * INTO v_carpeta_existe 
    FROM carpetas 
    WHERE id = p_id_carpeta 
      AND id_usuario = p_id_usuario;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'La carpeta no existe o no pertenece al usuario';
    END IF;

    IF v_carpeta_existe.estado = 1 THEN
        RAISE EXCEPTION 'La carpeta ya estÃ¡ activa (no estÃ¡ en la papelera)';
    END IF;

    -- Restaurar la carpeta (cambiar estado a activo y limpiar fecha de eliminaciÃ³n)
    UPDATE carpetas 
    SET estado = 1, 
        elimina = NULL
    WHERE id = p_id_carpeta 
      AND id_usuario = p_id_usuario
      AND estado = 0; -- Solo restaurar si estÃ¡ eliminada

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizÃ³ al menos una fila
    IF v_filas_afectadas = 0 THEN
        RAISE EXCEPTION 'No se pudo restaurar la carpeta. Verifique permisos y estado';
    END IF;

    RETURN v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al restaurar carpeta del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 16. Restaurar archivo desde la papelera con validaciÃ³n de usuario
CREATE OR REPLACE FUNCTION restaurar_archivo(
    p_id_archivo archivos.id%TYPE,
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
    v_archivo_existe archivos%ROWTYPE;
BEGIN
    -- Validar parÃ¡metros de entrada
    IF p_id_archivo IS NULL OR p_id_archivo <= 0 THEN
        RAISE EXCEPTION 'ID de archivo invÃ¡lido';
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario invÃ¡lido';
    END IF;

    -- Verificar que el usuario existe y estÃ¡ activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o estÃ¡ inactivo';
    END IF;

    -- Verificar que el archivo existe, estÃ¡ eliminado y pertenece al usuario
    SELECT * INTO v_archivo_existe 
    FROM archivos 
    WHERE id = p_id_archivo 
      AND id_usuario = p_id_usuario;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'El archivo no existe o no pertenece al usuario';
    END IF;

    IF v_archivo_existe.estado = 1 THEN
        RAISE EXCEPTION 'El archivo ya estÃ¡ activo (no estÃ¡ en la papelera)';
    END IF;

    -- Restaurar el archivo (cambiar estado a activo y limpiar fecha de eliminaciÃ³n)
    UPDATE archivos 
    SET estado = 1, 
        elimina = NULL
    WHERE id = p_id_archivo 
      AND id_usuario = p_id_usuario
      AND estado = 0; -- Solo restaurar si estÃ¡ eliminado

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizÃ³ al menos una fila
    IF v_filas_afectadas = 0 THEN
        RAISE EXCEPTION 'No se pudo restaurar el archivo. Verifique permisos y estado';
    END IF;

    RETURN v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al restaurar archivo del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 17. Eliminar carpeta de forma permanente
CREATE OR REPLACE FUNCTION eliminar_carpeta_permanente(
    p_id_carpeta carpetas.id%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    v_carpeta_existe carpetas%ROWTYPE;
    v_subcarpeta RECORD;
    v_archivo RECORD;
    v_total_eliminados INTEGER := 0;
    v_resultado INTEGER;
BEGIN
    -- Validar parÃ¡metros de entrada
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta invÃ¡lido';
    END IF;

    -- Verificar que la carpeta existe
    SELECT * INTO v_carpeta_existe 
    FROM carpetas 
    WHERE id = p_id_carpeta;
    
    IF NOT FOUND THEN
        RETURN 0; -- Carpeta no existe
    END IF;

    -- Validar propiedad si se especifica usuario
    IF p_id_usuario IS NOT NULL AND v_carpeta_existe.id_usuario != p_id_usuario THEN
        RAISE EXCEPTION 'No tienes permiso para eliminar esta carpeta';
    END IF;

    -- Eliminar recursivamente todas las subcarpetas
    FOR v_subcarpeta IN 
        SELECT id 
        FROM carpetas 
        WHERE id_carpeta_padre = p_id_carpeta
    LOOP
        -- Llamada recursiva para eliminar subcarpeta
        SELECT eliminar_carpeta_permanente(v_subcarpeta.id, p_id_usuario) INTO v_resultado;
        v_total_eliminados := v_total_eliminados + v_resultado;
    END LOOP;

    -- Eliminar todos los archivos de esta carpeta
    FOR v_archivo IN 
        SELECT id 
        FROM archivos 
        WHERE id_carpeta = p_id_carpeta
    LOOP
        -- Eliminar notificaciones relacionadas con el archivo
        DELETE FROM notificaciones WHERE id = v_archivo.id;
        
        -- Eliminar solicitudes compartidas del archivo
        DELETE FROM solicitudes_compartidos WHERE id_archivo = v_archivo.id;
        
        -- Eliminar registros de archivos compartidos
        DELETE FROM compartidos WHERE id_archivo_original = v_archivo.id;
    END LOOP;

    -- Eliminar todos los archivos de la carpeta
    DELETE FROM archivos WHERE id_carpeta = p_id_carpeta;

    -- Eliminar notificaciones relacionadas con la carpeta
    DELETE FROM notificaciones WHERE id_carpeta = p_id_carpeta;

    -- Finalmente eliminar la carpeta
    DELETE FROM carpetas WHERE id = p_id_carpeta;
    
    -- Verificar si se eliminÃ³ la carpeta
    GET DIAGNOSTICS v_resultado = ROW_COUNT;
    
    IF v_resultado > 0 THEN
        v_total_eliminados := v_total_eliminados + 1;
    END IF;

    RETURN v_total_eliminados;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar carpeta permanentemente: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 18. Eliminar archivo de forma permanente con validaciÃ³n de usuario

CREATE OR REPLACE FUNCTION eliminar_archivo_permanente(
    p_id_archivo archivos.id%TYPE,
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    v_archivo_existe archivos%ROWTYPE;
    v_filas_afectadas INTEGER := 0;
    v_resultado INTEGER;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_archivo IS NULL OR p_id_archivo <= 0 THEN
        RAISE EXCEPTION 'ID de archivo inválido';
    END IF;
    
    -- Si p_id_usuario es NULL, no validamos propiedad (para compatibilidad)
    -- Si p_id_usuario tiene valor, validamos que sea correcto
    IF p_id_usuario IS NOT NULL AND p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el archivo existe
    SELECT * INTO v_archivo_existe 
    FROM archivos 
    WHERE id = p_id_archivo;
    
    IF NOT FOUND THEN
        RETURN 0; -- Archivo no existe
    END IF;

    -- Validar propiedad si se especifica usuario
    IF p_id_usuario IS NOT NULL AND v_archivo_existe.id_usuario != p_id_usuario THEN
        RAISE EXCEPTION 'El archivo no existe o no tienes permiso para eliminarlo';
    END IF;

    -- Eliminar en orden: dependencias primero
    -- 1. Eliminar notificaciones relacionadas con el archivo
    DELETE FROM notificaciones WHERE id = p_id_archivo;
    
    -- 2. Eliminar solicitudes compartidas del archivo
    DELETE FROM solicitudes_compartidos WHERE id_archivo = p_id_archivo;
    
    -- 3. Eliminar registros de archivos compartidos
    DELETE FROM compartidos WHERE id_archivo_original = p_id_archivo;

    -- 4. Finalmente eliminar el archivo
    DELETE FROM archivos 
    WHERE id = p_id_archivo;
    
    -- Verificar si se eliminó el archivo
    GET DIAGNOSTICS v_resultado = ROW_COUNT;
    
    IF v_resultado > 0 THEN
        v_filas_afectadas := 1;
    END IF;

    RETURN v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar archivo permanentemente: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;