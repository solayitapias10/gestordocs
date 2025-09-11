-- =====================================================
-- SISTEMA DE AUDITORÍA - fun_act_tab()
-- Solo triggers para registrar usr_insert, fec_insert, usr_update, fec_update
-- =====================================================

-- =====================================================
-- AGREGAR CAMPOS DE AUDITORÍA A TODAS LAS TABLAS
-- =====================================================

-- TABLA: usuarios
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: solicitudes_registro  
ALTER TABLE solicitudes_registro
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: carpetas
ALTER TABLE carpetas
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: archivos
ALTER TABLE archivos
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: compartidos
ALTER TABLE compartidos
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: solicitudes_compartidos
ALTER TABLE solicitudes_compartidos
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: notificaciones
ALTER TABLE notificaciones
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- TABLA: tokens_recuperacion
ALTER TABLE tokens_recuperacion
ADD COLUMN IF NOT EXISTS usr_insert VARCHAR(100) DEFAULT CURRENT_USER,
ADD COLUMN IF NOT EXISTS fec_insert TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS usr_update VARCHAR(100),
ADD COLUMN IF NOT EXISTS fec_update TIMESTAMP WITH TIME ZONE;

-- =====================================================
-- FUNCIÓN DE AUDITORÍA fun_act_tab()
-- =====================================================

CREATE OR REPLACE FUNCTION fun_act_tab() RETURNS TRIGGER AS
$$
BEGIN
    -- Para operaciones INSERT
    IF TG_OP = 'INSERT' THEN
        NEW.usr_insert = CURRENT_USER;
        NEW.fec_insert = CURRENT_TIMESTAMP;
        RETURN NEW;
    END IF;
    
    -- Para operaciones UPDATE
    IF TG_OP = 'UPDATE' THEN
        NEW.usr_update = CURRENT_USER;
        NEW.fec_update = CURRENT_TIMESTAMP;
        RETURN NEW;
    END IF;
    
    RETURN NEW;
END;
$$
LANGUAGE plpgsql;

-- =====================================================
-- CREAR TRIGGERS PARA TODAS LAS TABLAS
-- =====================================================

-- Trigger para USUARIOS
CREATE TRIGGER tri_act_usuarios BEFORE INSERT OR UPDATE ON usuarios
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para SOLICITUDES_REGISTRO
CREATE TRIGGER tri_act_solicitudes_registro BEFORE INSERT OR UPDATE ON solicitudes_registro
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para CARPETAS
CREATE TRIGGER tri_act_carpetas BEFORE INSERT OR UPDATE ON carpetas
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para ARCHIVOS
CREATE TRIGGER tri_act_archivos BEFORE INSERT OR UPDATE ON archivos
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para COMPARTIDOS
CREATE TRIGGER tri_act_compartidos BEFORE INSERT OR UPDATE ON compartidos
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para SOLICITUDES_COMPARTIDOS
CREATE TRIGGER tri_act_solicitudes_compartidos BEFORE INSERT OR UPDATE ON solicitudes_compartidos
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para NOTIFICACIONES
CREATE TRIGGER tri_act_notificaciones BEFORE INSERT OR UPDATE ON notificaciones
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- Trigger para TOKENS_RECUPERACION
CREATE TRIGGER tri_act_tokens_recuperacion BEFORE INSERT OR UPDATE ON tokens_recuperacion
FOR EACH ROW EXECUTE PROCEDURE fun_act_tab();

-- =====================================================
-- CONFIRMACIÓN
-- =====================================================

DO $$
BEGIN
    RAISE NOTICE 'Sistema de triggers fun_act_tab() instalado correctamente';
    RAISE NOTICE 'Se agregaron 4 campos a cada tabla: usr_insert, fec_insert, usr_update, fec_update';
    RAISE NOTICE 'Se crearon 8 triggers para auditoría automática';
END $$;