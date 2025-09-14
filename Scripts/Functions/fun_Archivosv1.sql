-- =====================================================
-- FUNCIONES MODELO ARCHIVOS
-- =====================================================

-- 1. getCarpeta
-- Devuelve los datos de una carpeta específica por su ID solo si está activa.
CREATE OR REPLACE FUNCTION fun_obtenercarpetaporid(
    p_id_carpeta IN carpetas.id%TYPE
)
RETURNS TABLE (
    id              carpetas.id%TYPE,
    nombre          carpetas.nombre%TYPE,
    fecha_create    carpetas.fecha_create%TYPE,
    estado          carpetas.estado%TYPE,
    elimina         carpetas.elimina%TYPE,
    id_usuario      carpetas.id_usuario%TYPE,
    id_carpeta_padre carpetas.id_carpeta_padre%TYPE
) AS $$
BEGIN
    -- Retorna los datos de una carpeta específica si está activa
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
        c.id = p_id_carpeta
        AND c.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener la carpeta con ID %: %', p_id_carpeta, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 2. getArchivos
-- Esta función retorna los archivos activos de un usuario específico que no están en carpetas, validando que el usuario exista y esté activo.
CREATE OR REPLACE FUNCTION fun_obtenerarchivosusuario(
    p_id_usuario usuarios.id % TYPE
    )
    RETURNS TABLE (
    id               archivos.id % TYPE,
    nombre           archivos.nombre % TYPE,
    tipo             archivos.tipo % TYPE,
    fecha_create     archivos.fecha_create % TYPE,
    estado           archivos.estado % TYPE,
    elimina          archivos.elimina % TYPE,
    id_carpeta       archivos.id_carpeta % TYPE,
    id_usuario       archivos.id_usuario % TYPE,
    tamano           archivos.tamano % TYPE
) AS $$ 
BEGIN 

IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario invalido';

END IF;

-- Verificar que el usuario existe y está activo
IF NOT EXISTS(
    SELECT
        1
    FROM
        usuarios u
    WHERE
        u.id = p_id_usuario
        AND u.estado = 1
) THEN RAISE EXCEPTION 'El usuario no existe o está inactivo';
END IF;

-- Retornar archivos que no estan en carpetas, ordenados por ID DESC
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

-- 3. getCarpetas
-- Esta función retorna las carpetas principales (sin carpeta padre) de un usuario específico que estén activas.
CREATE OR REPLACE FUNCTION fun_obtenercarpetasprincipales(
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

-- 4. getUsuarios
-- Busca usuarios activos por coincidencia parcial o exacta de correo electrónico, excluyendo al usuario actual.
CREATE OR REPLACE FUNCTION fun_buscarusuariosporcorreo(
    p_valor_busqueda VARCHAR(100),
    p_id_usuario_actual usuarios.id % TYPE
) RETURNS TABLE (
    id                usuarios.id % TYPE,
    nombre            usuarios.nombre % TYPE,
    apellido          usuarios.apellido % TYPE,
    correo            usuarios.correo % TYPE,
    telefono          usuarios.telefono % TYPE,
    direccion         usuarios.direccion % TYPE,
    rol               usuarios.rol % TYPE,
    estado            usuarios.estado % TYPE,
    fecha             usuarios.fecha % TYPE,
    avatar            usuarios.avatar % TYPE
) AS $$ BEGIN 
IF p_id_usuario_actual IS NULL
OR p_id_usuario_actual <= 0 THEN RAISE EXCEPTION 'ID de usuario actual invalido';

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
) THEN RAISE EXCEPTION 'El usuario actual no existe o está inactivo';

END IF;

-- Buscar usuarios por correo electrónico (case insensitive)
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

-- 5. getUsuario
-- Esta función de PostgreSQL retorna los datos completos de un usuario según su ID, validando que el ID sea válido.
CREATE OR REPLACE FUNCTION fun_obtenerusuarioporid(
    p_id_usuario usuarios.id%TYPE
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
    avatar       usuarios.avatar%TYPE,
    fecha         usuarios.fecha%TYPE,
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

-- 6. registrarDetalle
-- Esta función registra una nueva solicitud de compartir un archivo, validando los parámetros y devolviendo el ID generado.
CREATE OR REPLACE FUNCTION fun_registrarsolicitudcompartida(
    p_correo     solicitudes_compartidos.correo % TYPE,
    p_id_archivo solicitudes_compartidos.id_archivo % TYPE,
    p_id_usuario solicitudes_compartidos.id_usuario % TYPE
) RETURNS solicitudes_compartidos.id % TYPE AS $$ DECLARE v_id_solicitud_nueva solicitudes_compartidos.id % TYPE;

BEGIN 
IF p_correo IS NULL
OR TRIM(p_correo) = '' THEN RAISE EXCEPTION 'El correo no puede estar vacío';

END IF;

IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo inválido';

END IF;

IF p_id_usuario IS NULL
OR p_id_usuario <= 0 THEN RAISE EXCEPTION 'ID de usuario inválido';

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

-- 7. getDetalle
-- Esta función retorna el detalle de una solicitud compartida para un archivo y correo específicos, validando la existencia del archivo y los parámetros recibidos.
CREATE OR REPLACE FUNCTION fun_obtenerdetallesolicitudcompartida(
    p_correo solicitudes_compartidos.correo % TYPE,
    p_id_archivo solicitudes_compartidos.id_archivo % TYPE
) RETURNS TABLE (
    id           solicitudes_compartidos.id % TYPE,
    fecha_add    solicitudes_compartidos.fecha_add % TYPE,
    correo       solicitudes_compartidos.correo % TYPE,
    estado       solicitudes_compartidos.estado % TYPE,
    elimina      solicitudes_compartidos.elimina % TYPE,
    id_archivo   solicitudes_compartidos.id_archivo % TYPE,
    id_usuario   solicitudes_compartidos.id_usuario % TYPE,
    aceptado     solicitudes_compartidos.aceptado % TYPE
) AS $$ BEGIN 
IF p_correo IS NULL
OR TRIM(p_correo) = '' THEN RAISE EXCEPTION 'El correo no puede estar vacío';

END IF;

IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo inválido';

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

-- 8. getArchivosCarpeta
-- Esta función retorna los archivos activos de una carpeta específica, validando que la carpeta exista y esté activa.
CREATE OR REPLACE FUNCTION fun_obtenerarchivoscarpeta(
    p_id_carpeta carpetas.id % TYPE
    ) 
    RETURNS TABLE (
    id           archivos.id % TYPE,
    nombre       archivos.nombre % TYPE,
    tipo         archivos.tipo % TYPE,
    fecha_create archivos.fecha_create % TYPE,
    estado       archivos.estado % TYPE,
    elimina      archivos.elimina % TYPE,
    id_carpeta   archivos.id_carpeta % TYPE,
    id_usuario   archivos.id_usuario % TYPE,
    tamano       archivos.tamano % TYPE
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

--9. eliminarCompartido
-- Esta función elimina lógicamente una solicitud compartida en la tabla solicitudes_compartidos, actualizando su estado y fecha de eliminación si la solicitud existe y está activa.
CREATE OR REPLACE FUNCTION fun_eliminarsolicitudcompartida(
    p_fecha_elimina solicitudes_compartidos.elimina % TYPE,
    p_id_solicitud  solicitudes_compartidos.id % TYPE
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

-- 10. eliminar
-- Esta función elimina lógicamente un archivo en la tabla 'archivos', actualizando su estado y fecha de eliminación, validando previamente su existencia y parámetros.
CREATE OR REPLACE FUNCTION fun_eliminararchivo(
    p_fecha_elimina archivos.elimina % TYPE,
    p_id_archivo    archivos.id % TYPE
) RETURNS INTEGER AS $$ DECLARE v_filas_afectadas INTEGER := 0;

BEGIN 
IF p_id_archivo IS NULL
OR p_id_archivo <= 0 THEN RAISE EXCEPTION 'ID de archivo invalido';

END IF;

IF p_fecha_elimina IS NULL THEN RAISE EXCEPTION 'Fecha de eliminacion no puede ser nula';

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
) THEN RAISE EXCEPTION 'El archivo no existe o ya esta eliminado';

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

-- Solo actualizar si esta activo
GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

RETURN v_filas_afectadas;

EXCEPTION
WHEN OTHERS THEN RAISE EXCEPTION 'Error al eliminar archivo: %',
SQLERRM;

END;

$$ LANGUAGE plpgsql;

-- 11. verificarEstado
-- Esta función verifica y retorna el número total de solicitudes compartidas activas asociadas a un correo electrónico válido.
CREATE OR REPLACE FUNCTION fun_verificarestadosolicitudescompartidas(
    p_correo solicitudes_compartidos.correo % TYPE
) RETURNS TABLE (total BIGINT) AS $$ BEGIN 
IF p_correo IS NULL
OR TRIM(p_correo) = '' THEN RAISE EXCEPTION 'El correo no puede estar vacio';

END IF;

-- Validar formato básico de correo
IF p_correo !~ '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$' THEN RAISE EXCEPTION 'Formato de correo inválido';

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

-- 12. getBusqueda
-- Esta función busca archivos por nombre pertenecientes a un usuario específico, validando que el usuario exista y esté activo, y retorna hasta 10 resultados ordenados por relevancia y fecha de creación.
CREATE OR REPLACE FUNCTION fun_buscararchivosusuario(
    p_valor_busqueda VARCHAR(255),
    p_id_usuario usuarios.id % TYPE
) RETURNS TABLE (
    id            archivos.id % TYPE,
    nombre        archivos.nombre % TYPE,
    tipo          archivos.tipo % TYPE,
    fecha_create  archivos.fecha_create % TYPE,
    estado        archivos.estado % TYPE,
    elimina       archivos.elimina % TYPE,
    id_carpeta    archivos.id_carpeta % TYPE,
    id_usuario    archivos.id_usuario % TYPE,
    tamano        archivos.tamano % TYPE
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

-- 13. getArchivo
-- Esta función retorna la información de un archivo específico si pertenece a un usuario dado y ambos están activos.
CREATE OR REPLACE FUNCTION fun_obtenerarchivoporidusuario(
    p_id_archivo archivos.id % TYPE,
    p_id_usuario archivos.id_usuario % TYPE
) RETURNS TABLE (
    id              archivos.id % TYPE,
    nombre          archivos.nombre % TYPE,
    tipo            archivos.tipo % TYPE,
    fecha_create    archivos.fecha_create % TYPE,
    estado          archivos.estado % TYPE,
    elimina         archivos.elimina % TYPE,
    id_carpeta      archivos.id_carpeta % TYPE,
    id_usuario      archivos.id_usuario % TYPE,
    tamano          archivos.tamano % TYPE
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

-- 14. eliminarCarpeta
-- Esta función elimina lógicamente una carpeta asociada a un usuario, marcándola como eliminada y programando su eliminación permanente en 30 días.
CREATE OR REPLACE FUNCTION fun_eliminarcarpeta(
    p_id         carpetas.id%TYPE,
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

-- 15. getPapeleraCarpetas
-- Esta función retorna las carpetas en la papelera (estado = 0) de un usuario específico, validando que el usuario exista y esté activo.
CREATE OR REPLACE FUNCTION fun_obtenercarpetaspapelera(
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS TABLE (
    id                   carpetas.id%TYPE,
    nombre               carpetas.nombre%TYPE,
    fecha_create         carpetas.fecha_create%TYPE,
    estado               carpetas.estado%TYPE,
    elimina              carpetas.elimina%TYPE,
    id_usuario           carpetas.id_usuario%TYPE,
    id_carpeta_padre     carpetas.id_carpeta_padre%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
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

--16. getPapeleraArchivos
-- Esta función retorna los archivos en la papelera (estado = 0) de un usuario específico, validando que el usuario exista y esté activo.
CREATE OR REPLACE FUNCTION fun_obtenerarchivospapelera(
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS TABLE (
    id                 archivos.id%TYPE,
    nombre             archivos.nombre%TYPE,
    tipo               archivos.tipo%TYPE,
    fecha_create       archivos.fecha_create%TYPE,
    estado             archivos.estado%TYPE,
    elimina            archivos.elimina%TYPE,
    id_carpeta         archivos.id_carpeta%TYPE,
    id_usuario         archivos.id_usuario%TYPE,
    tamano             archivos.tamano%TYPE
) AS $$
BEGIN
    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario invalido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
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

-- 17. restaurarCarpeta
-- Esta función restaura una carpeta eliminada (cambia su estado a activo) para un usuario específico en la base de datos.
CREATE OR REPLACE FUNCTION fun_restaurar_carpeta(
    p_id_carpeta carpetas.id%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
    v_carpeta_existe carpetas%ROWTYPE;
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

    -- Verificar que la carpeta existe, está eliminada y pertenece al usuario
    SELECT * INTO v_carpeta_existe 
    FROM carpetas 
    WHERE id = p_id_carpeta 
      AND id_usuario = p_id_usuario;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'La carpeta no existe o no pertenece al usuario';
    END IF;

    IF v_carpeta_existe.estado = 1 THEN
        RAISE EXCEPTION 'La carpeta ya está activa (no está en la papelera)';
    END IF;

    -- Restaurar la carpeta (cambiar estado a activo y limpiar fecha de eliminación)
    UPDATE carpetas 
    SET estado = 1, 
        elimina = NULL
    WHERE id = p_id_carpeta 
      AND id_usuario = p_id_usuario
      AND estado = 0; -- Solo restaurar si está eliminada

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualiza al menos una fila
    IF v_filas_afectadas = 0 THEN
        RAISE EXCEPTION 'No se pudo restaurar la carpeta. Verifique permisos y estado';
    END IF;

    RETURN v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al restaurar carpeta del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 18. restaurarArchivo
-- Esta función restaura un archivo eliminado (cambia su estado a activo) para un usuario específico, validando que ambos existan y estén en condiciones adecuadas.
CREATE OR REPLACE FUNCTION fun_restaurar_archivo(
    p_id_archivo archivos.id%TYPE,
    p_id_usuario archivos.id_usuario%TYPE
)
RETURNS INTEGER AS $$
DECLARE
    v_filas_afectadas INTEGER := 0;
    v_archivo_existe archivos%ROWTYPE;
BEGIN
    -- Validar parámetros de entrada
    IF p_id_archivo IS NULL OR p_id_archivo <= 0 THEN
        RAISE EXCEPTION 'ID de archivo inválido';
    END IF;

    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        RAISE EXCEPTION 'ID de usuario inválido';
    END IF;

    -- Verificar que el usuario existe y está activo
    IF NOT EXISTS(SELECT 1 FROM usuarios u WHERE u.id = p_id_usuario AND u.estado = 1) THEN
        RAISE EXCEPTION 'El usuario no existe o está inactivo';
    END IF;

    -- Verificar que el archivo existe, está eliminado y pertenece al usuario
    SELECT * INTO v_archivo_existe 
    FROM archivos 
    WHERE id = p_id_archivo 
      AND id_usuario = p_id_usuario;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'El archivo no existe o no pertenece al usuario';
    END IF;

    IF v_archivo_existe.estado = 1 THEN
        RAISE EXCEPTION 'El archivo ya está activo (no está en la papelera)';
    END IF;

    -- Restaurar el archivo (cambiar estado a activo y limpiar fecha de eliminación)
    UPDATE archivos 
    SET estado = 1, 
        elimina = NULL
    WHERE id = p_id_archivo 
      AND id_usuario = p_id_usuario
      AND estado = 0; -- Solo restaurar si estÃ¡ eliminado

    GET DIAGNOSTICS v_filas_afectadas = ROW_COUNT;

    -- Verificar que se actualizó al menos una fila
    IF v_filas_afectadas = 0 THEN
        RAISE EXCEPTION 'No se pudo restaurar el archivo. Verifique permisos y estado';
    END IF;

    RETURN v_filas_afectadas;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al restaurar archivo del usuario: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 19. eliminarCarpetaPermanente
-- Esta función elimina permanentemente una carpeta y todo su contenido (subcarpetas y archivos) de forma recursiva en PostgreSQL, validando permisos de usuario si se especifica.
CREATE OR REPLACE FUNCTION fun_eliminarcarpetapermanente(
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
    -- Validar parametros de entrada
    IF p_id_carpeta IS NULL OR p_id_carpeta <= 0 THEN
        RAISE EXCEPTION 'ID de carpeta inválido';
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

    -- Verificar si se eliminó la carpeta
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

-- 20. eliminarArchivoPermanente
-- Esta función elimina permanentemente un archivo y sus dependencias asociadas, validando la propiedad del usuario si se indica.
CREATE OR REPLACE FUNCTION fun_eliminararchivopermanente(
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

-- 21. registrarNotificacion
-- Esta función registra una notificación para un usuario en una carpeta específica, validando los datos y retornando el ID de la notificación creada, un mensaje y el estado de éxito.
CREATE OR REPLACE FUNCTION fun_registrarnotificacion(
    p_id_usuario          notificaciones.id_usuario%TYPE,
    p_id_carpeta          notificaciones.id_carpeta%TYPE,
    p_nombre              notificaciones.nombre%TYPE,
    p_evento              notificaciones.evento%TYPE
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

-- 22. getTotalArchivosCarpeta
-- Esta función retorna el total de archivos activos en una carpeta específica, validando que la carpeta exista y esté activa.
CREATE OR REPLACE FUNCTION fun_obtenertotalarchivoscarpeta(
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

--23. getArchivosPaginado
-- Esta función retorna una lista paginada de archivos activos pertenecientes a un usuario específico, validando la existencia y estado del usuario.
CREATE OR REPLACE FUNCTION fun_obtenerarchivospaginado(
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

-- 24. getTotalArchivos
-- Devuelve el total de archivos activos asociados a un usuario específico, validando que el usuario exista y esté activo.
CREATE OR REPLACE FUNCTION fun_obtenertotalarchivosusuario(
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

-- 25. getCarpetasPaginado
-- Esta función retorna las carpetas principales de un usuario específico en formato paginado, validando los parámetros y que el usuario esté activo.
CREATE OR REPLACE FUNCTION fun_obtenercarpetaspaginado(
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

--26. getTotalCarpetas
-- Esta función retorna el total de carpetas principales activas asociadas a un usuario específico, validando que el usuario exista y esté activo.
CREATE OR REPLACE FUNCTION fun_obtenertotalcarpetasusuario(
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

-- 27. getCarpetaPorNombre
-- Esta función retorna los datos de una carpeta específica, identificada por nombre y usuario, solo si está activa.
CREATE OR REPLACE FUNCTION fun_obtenercarpetapornombre(
    p_id_usuario IN carpetas.id_usuario%TYPE,
    p_nombre_carpeta IN carpetas.nombre%TYPE
)
RETURNS TABLE (
    id               carpetas.id%TYPE,
    nombre           carpetas.nombre%TYPE,
    fecha_create     carpetas.fecha_create%TYPE,
    estado           carpetas.estado%TYPE,
    elimina          carpetas.elimina%TYPE,
    id_usuario       carpetas.id_usuario%TYPE,
    id_carpeta_padre carpetas.id_carpeta_padre%TYPE
) AS $$
BEGIN
    -- Retorna los datos de una carpeta específica si está activa
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
        AND c.nombre = p_nombre_carpeta
        AND c.estado = 1;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener carpeta por nombre para el usuario ID %: %', p_id_usuario, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 28. crearCarpeta
-- Esta función crea una nueva carpeta para un usuario dado y retorna el ID de la carpeta creada.
CREATE OR REPLACE FUNCTION fun_crearcarpeta(
    p_id_usuario IN carpetas.id_usuario%TYPE,
    p_nombre_carpeta IN carpetas.nombre%TYPE
)
RETURNS carpetas.id%TYPE AS $$
DECLARE
    v_nueva_carpeta_id carpetas.id%TYPE;
BEGIN
    -- Inserta la nueva carpeta y recupera el ID generado
    INSERT INTO carpetas (nombre, id_usuario)
    VALUES (p_nombre_carpeta, p_id_usuario)
    RETURNING id INTO v_nueva_carpeta_id;

    -- Devuelve el ID de la nueva carpeta
    RETURN v_nueva_carpeta_id;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear la carpeta "%" para el usuario ID %: %', p_nombre_carpeta, p_id_usuario, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 29. registrarArchivo
-- Esta función registra un nuevo archivo en la tabla 'archivos' y devuelve el ID del archivo creado.
CREATE OR REPLACE FUNCTION fun_registrararchivo(
    p_nombre           IN archivos.nombre%TYPE,
    p_tipo             IN archivos.tipo%TYPE,
    p_id_carpeta       IN archivos.id_carpeta%TYPE,
    p_id_usuario       IN archivos.id_usuario%TYPE,
    p_tamano           IN archivos.tamano%TYPE,
    p_gmail_message_id IN archivos.gmail_message_id%TYPE
)
RETURNS archivos.id%TYPE AS $$
DECLARE
    v_nuevo_archivo_id archivos.id%TYPE;
BEGIN
    -- Inserta el nuevo registro del archivo
    INSERT INTO archivos (nombre, tipo, id_carpeta, id_usuario, tamano, gmail_message_id)
    VALUES (p_nombre, p_tipo, p_id_carpeta, p_id_usuario, p_tamano, p_gmail_message_id)
    RETURNING id INTO v_nuevo_archivo_id;

    -- Devuelve el ID del archivo recién creado
    RETURN v_nuevo_archivo_id;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al registrar el archivo "%": %', p_nombre, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 30. archivoYaExiste
-- Esta función verifica si ya existe un archivo en la tabla 'archivos' con el mismo 'gmail_message_id' y 'nombre', retornando TRUE si existe y FALSE en caso contrario.
CREATE OR REPLACE FUNCTION fun_archivoyaexiste(
    p_gmail_message_id IN archivos.gmail_message_id%TYPE,
    p_nombre_archivo IN archivos.nombre%TYPE
)
RETURNS BOOLEAN AS $$
BEGIN
    -- Retorna TRUE si existe un archivo con el mismo messageId y nombre, de lo contrario FALSE
    RETURN EXISTS(
        SELECT 1
        FROM archivos a
        WHERE a.gmail_message_id = p_gmail_message_id
          AND a.nombre = p_nombre_archivo
    );

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar la existencia del archivo "%": %', p_nombre_archivo, SQLERRM;
END;
$$ LANGUAGE plpgsql;


