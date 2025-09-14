-- =====================================================
-- FUNCIONES MODELO ADMIN
-- =====================================================

-- 1. getCarpetas
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

-- 2. getSubCarpetas
-- Esta función retorna las subcarpetas activas de una carpeta padre específica, validando que pertenezcan a un usuario y que tanto el usuario como la carpeta estén activos.
CREATE OR REPLACE FUNCTION fun_obtenersubcarpetas(
    p_id_carpeta carpetas.id_carpeta_padre%TYPE,
    p_id_usuario carpetas.id_usuario%TYPE
)
RETURNS TABLE (
    id                    carpetas.id%TYPE,
    nombre                carpetas.nombre%TYPE,
    fecha_create          carpetas.fecha_create%TYPE,
    estado                carpetas.estado%TYPE,
    elimina               carpetas.elimina%TYPE,
    id_usuario            carpetas.id_usuario%TYPE,
    id_carpeta_padre      carpetas.id_carpeta_padre%TYPE
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

-- 3. crearCarpeta
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

-- 4. getVerificar
-- Verifica si existe una carpeta con el mismo nombre para un usuario y ubicación dada, evitando duplicados al crear o actualizar carpetas.
CREATE OR REPLACE FUNCTION fun_verificar(
    p_campo VARCHAR(50),                    
    p_valor VARCHAR(255),                   
    p_id_usuario                 carpetas.id_usuario%TYPE,  
    p_id_excluir                 carpetas.id%TYPE DEFAULT 0, 
    p_id_carpeta_padre           carpetas.id_carpeta_padre%TYPE DEFAULT NULL 
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

--6. modificar
-- Esta función de PostgreSQL actualiza los datos de un usuario en la tabla 'usuarios', validando que los campos sean correctos y únicos, y retorna el resultado de la operación.
CREATE OR REPLACE FUNCTION fun_modificarusuario(
    p_id_usuario     usuarios.id%TYPE,
    p_nombre         usuarios.nombre%TYPE,
    p_apellido       usuarios.apellido%TYPE,
    p_correo         usuarios.correo%TYPE,
    p_telefono       usuarios.telefono%TYPE,
    p_direccion      usuarios.direccion%TYPE,
    p_rol            usuarios.rol%TYPE
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

-- 7. subirArchivo
-- Esta función de PostgreSQL valida y sube un archivo, asegurando que no exista duplicado y que usuario y carpeta sean válidos, retornando el ID del nuevo archivo, un mensaje y si tuvo éxito.
CREATE OR REPLACE FUNCTION fun_subirarchivo(
    p_nombre     archivos.nombre%TYPE,
    p_tipo       archivos.tipo%TYPE,
    p_tamano     archivos.tamano%TYPE,
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

-- 8. getArchivos
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

-- 9. getArchivosCompartidos
CREATE OR REPLACE FUNCTION fun_obtenerarchivoscompartidos(
    p_id_carpeta IN archivos.id_carpeta%TYPE
)
RETURNS TABLE (
    id      solicitudes_compartidos.id%TYPE,
    correo  solicitudes_compartidos.correo%TYPE,
    estado  solicitudes_compartidos.estado%TYPE,
    elimina solicitudes_compartidos.elimina%TYPE,
    nombre  archivos.nombre%TYPE
) AS $$
BEGIN
    -- Retorna los archivos que han sido compartidos dentro de una carpeta específica
    RETURN QUERY
    SELECT
        sc.id,
        sc.correo,
        sc.estado,
        sc.elimina,
        a.nombre
    FROM
        solicitudes_compartidos sc
    INNER JOIN
        archivos a ON sc.id_archivo = a.id
    WHERE
        a.id_carpeta = p_id_carpeta;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos compartidos para la carpeta ID %: %', p_id_carpeta, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 10. getCarpeta
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

-- 11. verificarEstado
-- Esta función retorna el número total de solicitudes de archivos compartidos pendientes (estado = 0) para un correo electrónico dado.
CREATE OR REPLACE FUNCTION fun_verificarestadosolicitudes(
    p_correo IN solicitudes_compartidos.correo%TYPE
)
RETURNS TABLE (
    total BIGINT
) AS $$
BEGIN
    -- Retorna el número total de solicitudes de archivos compartidos pendientes (estado = 0) para un correo electrónico.
    RETURN QUERY
    SELECT
        COUNT(sc.id)
    FROM
        solicitudes_compartidos sc
    WHERE
        sc.correo = p_correo
        AND sc.estado = 0; -- 0 para pendiente

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar el estado de las solicitudes para el correo %: %', p_correo, SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 12. editarCarpeta
-- Esta función actualiza el nombre de una carpeta para un usuario, validando permisos, existencia y unicidad del nombre en PostgreSQL.
CREATE OR REPLACE FUNCTION fun_editarcarpeta(
    p_nombre            carpetas.nombre%TYPE,
    p_id                carpetas.id%TYPE,
    p_id_usuario        carpetas.id_usuario%TYPE
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

-- 13. getCarpetasAll
-- Devuelve una lista limitada de carpetas principales activas desde la tabla 'carpetas', permitiendo especificar un límite de resultados.
CREATE OR REPLACE FUNCTION fun_obtenertodascarpetas(
    p_limite INTEGER DEFAULT 6
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

-- 14. getArchivosRecientesAll
-- Devuelve una lista de archivos recientes de todos los usuarios.
CREATE OR REPLACE FUNCTION fun_obtenerarchivosrecientes(
    p_limite INTEGER DEFAULT 10
)
RETURNS TABLE (
    id               archivos.id%TYPE,
    nombre           archivos.nombre%TYPE,
    tipo             archivos.tipo%TYPE,
    fecha_create     archivos.fecha_create%TYPE,
    estado           archivos.estado%TYPE,
    elimina          archivos.elimina%TYPE,
    id_carpeta      archivos.id_carpeta%TYPE,
    id_usuario       archivos.id_usuario%TYPE,
    tamano           archivos.tamano%TYPE
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

    -- Retornar los archivos recientes
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
    WHERE a.fecha_create >= NOW() - INTERVAL '30 days'
    ORDER BY a.fecha_create DESC
    LIMIT p_limite;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener archivos recientes: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 15. verificarEstadoAll
-- Devuelve la cantidad de archivos creados por día, agrupados por fecha, con un límite configurable de resultados.
CREATE OR REPLACE FUNCTION fun_actividadtodos(
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

-- 16. getActividadArchivosAll
-- Esta función retorna la cantidad de archivos creados por día en los últimos N días, donde N es un parámetro limitado entre 1 y 365.
CREATE OR REPLACE FUNCTION fun_obteneractividadarchivos(
    p_limite_dias IN INTEGER DEFAULT 30
)
RETURNS TABLE (
    fecha DATE,
    cantidad BIGINT
) AS $$
DECLARE
    v_limite_seguro INTEGER;
BEGIN
    IF p_limite_dias IS NULL OR p_limite_dias <= 0 THEN
        v_limite_seguro := 30; 
    ELSIF p_limite_dias > 365 THEN
        v_limite_seguro := 365; 
    ELSE
        v_limite_seguro := p_limite_dias;
    END IF;
    RETURN QUERY
    SELECT
        DATE(a.fecha_create) AS fecha_actividad,
        COUNT(a.id) AS total_archivos
    FROM
        archivos a
    WHERE
        a.estado = 1
        AND a.fecha_create >= CURRENT_DATE - INTERVAL '1 day' * v_limite_seguro
    GROUP BY
        fecha_actividad
    ORDER BY
        fecha_actividad ASC;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener la actividad de todos los archivos: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 17. getEstadisticasGlobales
-- Devuelve estadísticas resumidas del sistema, incluyendo totales, incrementos de ayer y tendencias porcentuales de carpetas, archivos, compartidos, usuarios y uso de espacio.
CREATE OR REPLACE FUNCTION fun_obtenerestadisticas()
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

-- 18. getTiposArchivos
-- Esta función retorna los tipos de archivos más frecuentes y su cantidad, limitando el resultado según el parámetro dado.
CREATE OR REPLACE FUNCTION fun_obtenertiposarchivos(
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

-- 19. getUsuariosActivos
-- Esta función retorna una tabla con los usuarios más activos y la cantidad de archivos asociados, limitando el resultado según el parámetro dado.
CREATE OR REPLACE FUNCTION fun_obtenerusuariosactivos(
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

-- 20. getActividadReciente
-- Esta función retorna la actividad reciente (carpetas creadas y archivos subidos) en el sistema, combinando ambos tipos y limitando la cantidad de resultados según el parámetro dado.
CREATE OR REPLACE FUNCTION fun_obteneractividadreciente(
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

-- 21. getArchivo
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

-- 22. getUsuarioPorCorreo
-- Esta función retorna la información de un usuario activo buscando por su correo electrónico, validando el formato del correo.
CREATE OR REPLACE FUNCTION fun_obtenerusuarioporcorreo(
    p_correo usuarios.correo%TYPE
)
RETURNS TABLE (
    id                        usuarios.id%TYPE,
    nombre                    usuarios.nombre%TYPE,
    apellido                  usuarios.apellido%TYPE,
    correo                    usuarios.correo%TYPE,
    telefono                  usuarios.telefono%TYPE,
    direccion                 usuarios.direccion%TYPE,
    clave                     usuarios.clave%TYPE,
    fecha                     usuarios.fecha%TYPE,
    estado                    usuarios.estado%TYPE,
    rol                       usuarios.rol%TYPE,
    avatar                    usuarios.avatar%TYPE,
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

-- 23. eliminarCarpeta
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

-- 24. verificarArchivo
-- Esta función verifica si existe un archivo con el mismo nombre, id_carpeta e id_usuario, retorna TRUE si existe y FALSE en caso contrario.
CREATE OR REPLACE FUNCTION fun_verificararchivo(
    p_nombre VARCHAR(255),
    p_id_carpeta  archivos.id_carpeta%TYPE,
    p_id_usuario  archivos.id_usuario%TYPE
)
RETURNS BOOLEAN AS $$
BEGIN
    IF p_id_carpeta IS NULL THEN
        -- Buscar en la raíz
        RETURN EXISTS(
            SELECT 1
            FROM archivos
            WHERE nombre = p_nombre
              AND id_carpeta IS NULL
              AND id_usuario = p_id_usuario
              AND estado = 1
        );
    ELSE
        -- Buscar dentro de la carpeta
        RETURN EXISTS(
            SELECT 1
            FROM archivos
            WHERE nombre = p_nombre
              AND id_carpeta = p_id_carpeta
              AND id_usuario = p_id_usuario
              AND estado = 1
        );
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al verificar archivo: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

--25. getArchivosCompartidosRecientes
-- Esta función retorna los archivos compartidos más recientes, uniendo datos de solicitudes, archivos y usuarios, limitando la cantidad de resultados según el parámetro dado.
CREATE OR REPLACE FUNCTION fun_obtenerarchivoscompartidosrecientes(
    p_limite INTEGER DEFAULT 2
)
RETURNS TABLE (
    id                   solicitudes_compartidos.id%TYPE,
    nombre_archivo       archivos.nombre%TYPE,
    usuario_propietario  usuarios.nombre%TYPE,
    fecha_add            solicitudes_compartidos.fecha_add%TYPE,
    correo               solicitudes_compartidos.correo%TYPE,
    id_archivo           solicitudes_compartidos.id_archivo%TYPE,
    id_usuario           solicitudes_compartidos.id_usuario%TYPE
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


-- 26. registrarNotificacion
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

-- 27. getNotificaciones
-- Esta función retorna las notificaciones no leídas de un usuario, validando la existencia del usuario y limitando el número de notificaciones retornadas.
CREATE OR REPLACE FUNCTION fun_obtenernotificaciones(
    p_id_usuario notificaciones.id_usuario%TYPE,
    p_limite INTEGER DEFAULT 10
)
RETURNS TABLE (
    id                    notificaciones.id%TYPE,
    id_usuario            notificaciones.id_usuario%TYPE,
    id_carpeta            notificaciones.id_carpeta%TYPE,
    id_solicitud          notificaciones.id_solicitud%TYPE,
    nombre                notificaciones.nombre%TYPE,
    evento                notificaciones.evento%TYPE,
    fecha                 notificaciones.fecha%TYPE,
    leida                 notificaciones.leida%TYPE
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

-- 28. marcarNotificacionLeida
-- Esta función marca una notificación específica como leída para un usuario dado, validando la existencia de la notificación y la pertenencia al usuario.
CREATE OR REPLACE FUNCTION fun_marcarNotificacionLeida(
    p_id_notificacion    notificaciones.id%TYPE,
    p_id_usuario         notificaciones.id_usuario%TYPE
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

--30. getTotalCarpetas
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

-- 31. getCarpetasPaginado
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

--32. getArchivosPaginado
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

-- 33. getTotalArchivosCarpeta
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
