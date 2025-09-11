-- =====================================================
-- SISTEMA DE GESTIÓN DE ARCHIVOS Y USUARIOS
-- Versión: 1.0
-- Base de Datos: PostgreSQL
-- Descripción: Script completo para crear la estructura de BD
-- =====================================================

-- Configurar el esquema y verificar conexión
DO $$
BEGIN
    RAISE NOTICE 'Iniciando creación de base de datos del sistema de archivos...';
    RAISE NOTICE 'PostgreSQL versión: %', version();
END $$;

-- =====================================================
-- ELIMINACIÓN DE OBJETOS EXISTENTES (Si existen)
-- =====================================================

-- Eliminar triggers
DROP TRIGGER IF EXISTS trigger_nueva_solicitud ON solicitudes_registro;
DROP TRIGGER IF EXISTS update_usuarios_fecha ON usuarios;
DROP TRIGGER IF EXISTS update_carpetas_modtime ON carpetas;

-- Eliminar funciones
DROP FUNCTION IF EXISTS notificar_solicitud_registro();
DROP FUNCTION IF EXISTS update_usuario_fecha();
DROP FUNCTION IF EXISTS update_modified_column();

-- Eliminar tablas en orden correcto (dependencias)
DROP TABLE IF EXISTS notificaciones CASCADE;
DROP TABLE IF EXISTS solicitudes_compartidos CASCADE;
DROP TABLE IF EXISTS compartidos CASCADE;
DROP TABLE IF EXISTS archivos CASCADE;
DROP TABLE IF EXISTS carpetas CASCADE;
DROP TABLE IF EXISTS solicitudes_registro CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;

-- =====================================================
-- CREACIÓN DE TABLAS
-- =====================================================

-- TABLA: usuarios
-- Gestiona los usuarios del sistema con roles y autenticación
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(15) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    clave VARCHAR(200) NOT NULL,
    fecha TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado INTEGER NOT NULL DEFAULT 1,
    rol INTEGER NOT NULL,
    avatar VARCHAR(255) DEFAULT 'Assets/images/avatar.jpg',
    fecha_ultimo_cambio_clave TIMESTAMP WITH TIME ZONE,
    
    -- Constraints adicionales
    CONSTRAINT chk_usuarios_estado CHECK (estado IN (0, 1)),
    CONSTRAINT chk_usuarios_rol CHECK (rol IN (0, 1, 2)) -- 0: Usuario, 1: Admin, 2: Super Admin
);

-- TABLA: solicitudes_registro
-- Gestiona las solicitudes de registro pendientes de aprobación
CREATE TABLE solicitudes_registro (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    clave VARCHAR(200) NOT NULL,
    fecha_solicitud TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado INTEGER NOT NULL DEFAULT 0,
    id_usuario_admin INTEGER,
    fecha_procesado TIMESTAMP WITH TIME ZONE,
    
    -- Constraints
    CONSTRAINT chk_solicitudes_estado CHECK (estado IN (0, 1, 2)), -- 0: Pendiente, 1: Aprobada, 2: Rechazada
    CONSTRAINT fk_solicitudes_admin 
        FOREIGN KEY (id_usuario_admin) 
        REFERENCES usuarios(id) 
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- TABLA: carpetas
-- Estructura jerárquica de carpetas del sistema
CREATE TABLE carpetas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    fecha_create TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado INTEGER NOT NULL DEFAULT 1,
    elimina TIMESTAMP WITH TIME ZONE,
    id_usuario INTEGER NOT NULL,
    id_carpeta_padre INTEGER,
    
    -- Constraints
    CONSTRAINT chk_carpetas_estado CHECK (estado IN (0, 1)),
    CONSTRAINT fk_carpetas_usuario 
        FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_carpetas_padre 
        FOREIGN KEY (id_carpeta_padre) 
        REFERENCES carpetas(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- TABLA: archivos
-- Gestiona los archivos del sistema
CREATE TABLE archivos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    fecha_create TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado INTEGER NOT NULL DEFAULT 1,
    elimina TIMESTAMP WITH TIME ZONE,
    id_carpeta INTEGER,
    id_usuario INTEGER NOT NULL,
    tamano BIGINT,
    
    -- Constraints
    CONSTRAINT chk_archivos_estado CHECK (estado IN (0, 1)),
    CONSTRAINT chk_archivos_tamano CHECK (tamano >= 0),
    CONSTRAINT fk_archivos_carpeta 
        FOREIGN KEY (id_carpeta) 
        REFERENCES carpetas(id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_archivos_usuario 
        FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- TABLA: compartidos (anteriormente archivos_compartidos_conmigo)
-- Gestiona archivos compartidos entre usuarios
CREATE TABLE compartidos (
    id SERIAL PRIMARY KEY,
    id_archivo_original INTEGER NOT NULL,
    id_usuario_propietario INTEGER NOT NULL,
    id_usuario_receptor INTEGER NOT NULL,
    fecha_aceptado TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nombre_personalizado VARCHAR(255),
    estado INTEGER NOT NULL DEFAULT 1,
    fecha_eliminado TIMESTAMP WITH TIME ZONE,
    
    -- Constraints
    CONSTRAINT chk_compartidos_estado CHECK (estado IN (0, 1)),
    CONSTRAINT fk_archivo_compartido_original 
        FOREIGN KEY (id_archivo_original) 
        REFERENCES archivos(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_usuario_propietario_compartido 
        FOREIGN KEY (id_usuario_propietario) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_usuario_receptor_compartido 
        FOREIGN KEY (id_usuario_receptor) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- Evitar que un usuario se comparta archivos a sí mismo
    CONSTRAINT chk_diferentes_usuarios CHECK (id_usuario_propietario != id_usuario_receptor)
);

-- TABLA: solicitudes_compartidos (anteriormente detalle_archivos)
-- Gestiona las solicitudes de compartir archivos
CREATE TABLE solicitudes_compartidos (
    id SERIAL PRIMARY KEY,
    fecha_add TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    correo VARCHAR(100) NOT NULL,
    estado INTEGER NOT NULL DEFAULT 1,
    elimina TIMESTAMP WITH TIME ZONE,
    id_archivo INTEGER NOT NULL,
    id_usuario INTEGER NOT NULL,
    aceptado SMALLINT DEFAULT 0,
    
    -- Constraints
    CONSTRAINT chk_solicitudes_estado CHECK (estado IN (0, 1)),
    CONSTRAINT chk_solicitudes_aceptado CHECK (aceptado IN (0, 1, 2)), -- 0: Pendiente, 1: Aceptado, 2: Rechazado
    CONSTRAINT fk_solicitudes_compartidos_archivo 
        FOREIGN KEY (id_archivo) 
        REFERENCES archivos(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_solicitudes_compartidos_usuario 
        FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- TABLA: notificaciones
-- Sistema de notificaciones del aplicativo
CREATE TABLE notificaciones (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER,
    id_carpeta INTEGER,
    id_solicitud INTEGER,
    nombre VARCHAR(255),
    evento VARCHAR(50),
    fecha TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leida SMALLINT DEFAULT 0,
    
    -- Constraints
    CONSTRAINT chk_notificaciones_leida CHECK (leida IN (0, 1)),
    CONSTRAINT fk_notificaciones_usuario 
        FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notificaciones_carpeta 
        FOREIGN KEY (id_carpeta) 
        REFERENCES carpetas(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notificaciones_solicitud 
        FOREIGN KEY (id_solicitud) 
        REFERENCES solicitudes_registro(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- =====================================================
-- TABLA: tokens_recuperacion
-- =====================================================
CREATE TABLE tokens_recuperacion (
    id SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    fecha_creacion TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP WITH TIME ZONE NOT NULL,
    usado SMALLINT DEFAULT 0 CHECK (usado IN (0, 1)), -- 0: No usado, 1: Usado
    ip_solicitud INET,
    user_agent TEXT,
    
    -- Restricción de clave foránea
    CONSTRAINT fk_token_recuperacion_usuario 
        FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- =====================================================
-- CREACIÓN DE ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices para usuarios
CREATE INDEX idx_usuarios_correo ON usuarios(correo);
CREATE INDEX idx_usuarios_estado ON usuarios(estado);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_fecha_cambio_clave ON usuarios(fecha_ultimo_cambio_clave);

-- Índices para solicitudes_registro
CREATE INDEX idx_solicitudes_correo ON solicitudes_registro(correo);
CREATE INDEX idx_solicitudes_estado ON solicitudes_registro(estado);
CREATE INDEX idx_solicitudes_fecha ON solicitudes_registro(fecha_solicitud);

-- Índices para carpetas
CREATE INDEX idx_carpetas_usuario ON carpetas(id_usuario);
CREATE INDEX idx_carpetas_padre ON carpetas(id_carpeta_padre);
CREATE INDEX idx_carpetas_estado ON carpetas(estado);
CREATE INDEX idx_carpetas_fecha_create ON carpetas(fecha_create);

-- Índices para archivos
CREATE INDEX idx_archivos_carpeta ON archivos(id_carpeta);
CREATE INDEX idx_archivos_usuario ON archivos(id_usuario);
CREATE INDEX idx_archivos_estado ON archivos(estado);
CREATE INDEX idx_archivos_tipo ON archivos(tipo);
CREATE INDEX idx_archivos_fecha_create ON archivos(fecha_create);

-- Índices para compartidos
CREATE INDEX idx_archivo_original ON compartidos(id_archivo_original);
CREATE INDEX idx_usuario_propietario ON compartidos(id_usuario_propietario);
CREATE INDEX idx_usuario_receptor ON compartidos(id_usuario_receptor);
CREATE INDEX idx_compartidos_estado ON compartidos(estado);

-- Índices para solicitudes_compartidos
CREATE INDEX idx_solicitudes_compartidos_archivo ON solicitudes_compartidos(id_archivo);
CREATE INDEX idx_solicitudes_compartidos_usuario ON solicitudes_compartidos(id_usuario);
CREATE INDEX idx_solicitudes_compartidos_aceptado ON solicitudes_compartidos(aceptado);
CREATE INDEX idx_solicitudes_compartidos_estado_aceptado ON solicitudes_compartidos(estado, aceptado);
CREATE INDEX idx_solicitudes_compartidos_correo ON solicitudes_compartidos(correo);

-- Índices para notificaciones
CREATE INDEX idx_notificaciones_usuario ON notificaciones(id_usuario) WHERE id_usuario IS NOT NULL;
CREATE INDEX idx_notificaciones_carpeta ON notificaciones(id_carpeta);
CREATE INDEX idx_notificaciones_solicitud ON notificaciones(id_solicitud);
CREATE INDEX idx_notificaciones_leida ON notificaciones(leida);
CREATE INDEX idx_notificaciones_fecha ON notificaciones(fecha);
CREATE INDEX idx_notificaciones_evento ON notificaciones(evento);

-- Índices para tokens_recuperacion
CREATE INDEX idx_tokens_recuperacion_token ON tokens_recuperacion(token);
CREATE INDEX idx_tokens_recuperacion_usuario ON tokens_recuperacion(id_usuario);
CREATE INDEX idx_tokens_recuperacion_expiracion ON tokens_recuperacion(fecha_expiracion);
CREATE INDEX idx_tokens_recuperacion_usado ON tokens_recuperacion(usado);

-- =====================================================
-- FUNCIONES DEL SISTEMA
-- =====================================================

-- Función para actualizar timestamp automáticamente en carpetas
CREATE OR REPLACE FUNCTION update_modified_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.fecha_create = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Función para actualizar fecha en usuarios
CREATE OR REPLACE FUNCTION update_usuario_fecha()
RETURNS TRIGGER AS $$
BEGIN
    NEW.fecha = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Función para notificar a administradores sobre nuevas solicitudes
CREATE OR REPLACE FUNCTION notificar_solicitud_registro()
RETURNS TRIGGER AS $$
BEGIN
    -- Notificar a todos los administradores (rol = 1) sobre la nueva solicitud
    INSERT INTO notificaciones (id_usuario, id_solicitud, nombre, evento, fecha, leida)
    SELECT id, NEW.id, NEW.nombre || ' ' || NEW.apellido, 'NUEVA_SOLICITUD', CURRENT_TIMESTAMP, 0
    FROM usuarios WHERE rol = 1;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- TRIGGERS DEL SISTEMA
-- =====================================================

-- Trigger para actualizar fecha en carpetas
CREATE TRIGGER update_carpetas_modtime 
    BEFORE UPDATE ON carpetas 
    FOR EACH ROW 
    EXECUTE FUNCTION update_modified_column();

-- Trigger para actualizar fecha en usuarios
CREATE TRIGGER update_usuarios_fecha 
    BEFORE UPDATE ON usuarios 
    FOR EACH ROW 
    EXECUTE FUNCTION update_usuario_fecha();

-- Trigger para notificar nuevas solicitudes de registro
CREATE TRIGGER trigger_nueva_solicitud
    AFTER INSERT ON solicitudes_registro
    FOR EACH ROW
    EXECUTE FUNCTION notificar_solicitud_registro();

