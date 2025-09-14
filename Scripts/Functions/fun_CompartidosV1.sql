-- =====================================================
-- FUNCIONES MODELO COMPARTIDOS 
-- =====================================================

-- 1. getUsuario
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

-- 2. getArchivosCompartidosConmigoDirecto
-- Esta función retorna los archivos compartidos con un usuario específico, obteniendo detalles de las tablas de solicitudes, archivos y usuarios y validando el correo electrónico del usuario.
CREATE OR REPLACE FUNCTION fun_archivoscompartidos(
    p_correo_usuario usuarios.correo%TYPE
)
RETURNS TABLE (
    id                 solicitudes_compartidos.id%TYPE,
    fecha_add          solicitudes_compartidos.fecha_add%TYPE,
    correo             solicitudes_compartidos.correo%TYPE,
    estado             solicitudes_compartidos.estado%TYPE,
    id_archivo         archivos.id%TYPE,
    nombre_archivo     archivos.nombre%TYPE,
    tipo               archivos.tipo%TYPE,
    tamano             archivos.tamano%TYPE,
    propietario        usuarios.nombre%TYPE,
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

-- 3. getDetalleArchivoCompartido
--Esta función retorna los detalles completos de un archivo compartido específico, combinando datos de las tablas de solicitudes, archivos, usuarios y carpetas, y filtrando por el ID del detalle del compartido y el correo del usuario receptor.
CREATE OR REPLACE FUNCTION fun_obtenerdetallearchivocompartido(
    p_id_detalle          solicitudes_compartidos.id%TYPE,
    p_correo_usuario      usuarios.correo%TYPE
)

RETURNS TABLE (
    id                    solicitudes_compartidos.id%TYPE,
    fecha_add             solicitudes_compartidos.fecha_add%TYPE,
    correo                solicitudes_compartidos.correo%TYPE,
    estado                solicitudes_compartidos.estado%TYPE,
    id_archivo            archivos.id%TYPE,
    id_usuario            archivos.id_usuario%TYPE,    
    id_carpeta            archivos.id_carpeta%TYPE,   
    nombre_archivo        archivos.nombre%TYPE,
    tipo                  archivos.tipo%TYPE,
    tamano                archivos.tamano%TYPE,
    propietario           usuarios.nombre%TYPE,
    apellido_propietario  usuarios.apellido%TYPE,
    avatar                usuarios.avatar%TYPE,
    carpeta_nombre        carpetas.nombre%TYPE
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
        a.id_usuario,                      
        a.id_carpeta,                      
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

-- 4.verificarArchivoCompartidoConUsuario
-- Esta función verifica si un usuario tiene acceso a un archivo compartido, combinando datos de solicitudes y archivos, y filtrando por el ID del detalle del compartido y el correo del usuario.
CREATE OR REPLACE FUNCTION fun_verificararchivocompartidousuario(
    p_id_detalle       solicitudes_compartidos.id%TYPE,
    p_correo_usuario   usuarios.correo%TYPE
)
RETURNS TABLE (
    id                 solicitudes_compartidos.id%TYPE,
    correo             solicitudes_compartidos.correo%TYPE,
    nombre             archivos.nombre%TYPE
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

-- 5. cambiarEstado
-- Esta función actualiza el estado de una solicitud de archivo compartido, validando la existencia del registro y actualizando el estado según los parámetros de ID de detalle y nuevo estado proporcionados.
CREATE OR REPLACE FUNCTION fun_cambiarestado(
    p_estado     solicitudes_compartidos.estado%TYPE,
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

-- 6. verificarEstado
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

-- 7. getArchivoCompleto
CREATE OR REPLACE FUNCTION fun_obtenerarchivocompartido(
    p_id_archivo archivos.id%TYPE
) 
RETURNS TABLE (
    id                                archivos.id%TYPE,
    nombre                            archivos.nombre%TYPE,
    tipo                              archivos.tipo%TYPE,
    fecha_create                      archivos.fecha_create%TYPE,
    estado                            archivos.estado%TYPE,
    id_carpeta                        archivos.id_carpeta%TYPE,
    id_usuario                        archivos.id_usuario%TYPE,
    tamano                            archivos.tamano%TYPE,
    carpeta_nombre                    carpetas.nombre%TYPE,
    usuario_nombre                    usuarios.nombre%TYPE,
    usuario_apellido                  usuarios.apellido%TYPE,
    usuario_correo                    usuarios.correo%TYPE
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