-- =====================================================
-- FUNCIONES AUXILIARES
-- =====================================================

-- 1. Función para generar contraseña temporal automática
CREATE OR REPLACE FUNCTION generar_contrasena_temporal(
    p_longitud INTEGER DEFAULT 12
)
RETURNS TEXT AS $$
DECLARE
    v_caracteres TEXT := 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    v_contrasena TEXT := '';
    v_total_caracteres INTEGER;
    v_contador INTEGER := 0;
    v_posicion_aleatoria INTEGER;
    v_caracter_seleccionado CHAR(1);
BEGIN
    -- Validar parámetros de entrada
    IF p_longitud IS NULL OR p_longitud < 4 THEN
        RAISE EXCEPTION 'La longitud debe ser al menos 4 caracteres, valor recibido: %', p_longitud;
    END IF;

    IF p_longitud > 255 THEN
        RAISE EXCEPTION 'La longitud no puede ser mayor a 255 caracteres, valor recibido: %', p_longitud;
    END IF;

    -- Obtener longitud total de caracteres disponibles
    v_total_caracteres := LENGTH(v_caracteres);

    -- Generar contraseña carácter por carácter
    WHILE v_contador < p_longitud LOOP
        -- Generar posición aleatoria (1 to length)
        v_posicion_aleatoria := (RANDOM() * (v_total_caracteres - 1))::INTEGER + 1;
        
        -- Obtener el carácter en esa posición
        v_caracter_seleccionado := SUBSTR(v_caracteres, v_posicion_aleatoria, 1);
        
        -- Agregar el carácter a la contraseña
        v_contrasena := v_contrasena || v_caracter_seleccionado;
        
        -- Incrementar contador
        v_contador := v_contador + 1;
    END LOOP;

    -- Verificar que se generó la contraseña correctamente
    IF LENGTH(v_contrasena) != p_longitud THEN
        RAISE EXCEPTION 'Error interno: longitud de contraseña generada (%) no coincide con la solicitada (%)', LENGTH(v_contrasena), p_longitud;
    END IF;

    -- Verificar que la contraseña no esté vacía
    IF v_contrasena = '' THEN
        RAISE EXCEPTION 'Error interno: se generó una contraseña vacía';
    END IF;

    RAISE NOTICE 'Contraseña temporal generada exitosamente con % caracteres', LENGTH(v_contrasena);
    RETURN v_contrasena;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al generar contraseña temporal: %', SQLERRM;
END;
$$ LANGUAGE plpgsql;

-- 2. Función auxiliar para validar fortaleza de contraseña
CREATE OR REPLACE FUNCTION validar_fortaleza_contrasena(
    p_contrasena TEXT
)
RETURNS TABLE (
    es_valida BOOLEAN,
    mensaje_validacion TEXT,
    puntuacion_fortaleza INTEGER
) AS $$
DECLARE
    v_longitud INTEGER;
    v_tiene_mayuscula BOOLEAN := FALSE;
    v_tiene_minuscula BOOLEAN := FALSE;
    v_tiene_numero BOOLEAN := FALSE;
    v_tiene_simbolo BOOLEAN := FALSE;
    v_puntuacion INTEGER := 0;
    v_mensaje TEXT := '';
BEGIN
    -- Validar parámetros de entrada
    IF p_contrasena IS NULL OR p_contrasena = '' THEN
        RETURN QUERY SELECT FALSE, 'La contraseña no puede estar vacía'::TEXT, 0;
        RETURN;
    END IF;

    v_longitud := LENGTH(p_contrasena);

    -- Verificar longitud mínima
    IF v_longitud < 8 THEN
        RETURN QUERY SELECT FALSE, 'La contraseña debe tener al menos 8 caracteres'::TEXT, 0;
        RETURN;
    END IF;

    -- Verificar componentes de la contraseña
    v_tiene_mayuscula := p_contrasena ~ '[A-Z]';
    v_tiene_minuscula := p_contrasena ~ '[a-z]';
    v_tiene_numero := p_contrasena ~ '[0-9]';
    v_tiene_simbolo := p_contrasena ~ '[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]';

    -- Calcular puntuación de fortaleza
    v_puntuacion := 0;
    IF v_longitud >= 8 THEN v_puntuacion := v_puntuacion + 1; END IF;
    IF v_longitud >= 12 THEN v_puntuacion := v_puntuacion + 1; END IF;
    IF v_tiene_mayuscula THEN v_puntuacion := v_puntuacion + 1; END IF;
    IF v_tiene_minuscula THEN v_puntuacion := v_puntuacion + 1; END IF;
    IF v_tiene_numero THEN v_puntuacion := v_puntuacion + 1; END IF;
    IF v_tiene_simbolo THEN v_puntuacion := v_puntuacion + 1; END IF;

    -- Determinar mensaje según puntuación
    CASE 
        WHEN v_puntuacion >= 5 THEN v_mensaje := 'Contraseña fuerte';
        WHEN v_puntuacion >= 3 THEN v_mensaje := 'Contraseña moderada';
        ELSE v_mensaje := 'Contraseña débil';
    END CASE;

    -- Agregar recomendaciones si es necesario
    IF NOT v_tiene_mayuscula OR NOT v_tiene_minuscula OR NOT v_tiene_numero OR NOT v_tiene_simbolo THEN
        v_mensaje := v_mensaje || ' - Se recomienda incluir mayúsculas, minúsculas, números y símbolos';
    END IF;

    RETURN QUERY SELECT TRUE, v_mensaje, v_puntuacion;

EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, ('Error al validar contraseña: ' || SQLERRM)::TEXT, 0;
END;
$$ LANGUAGE plpgsql;