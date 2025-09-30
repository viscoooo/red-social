-- ------------------------------------------------------------
-- ALASKA - Red social para el cuidado de animales
-- Script de migración idempotente para normalizar el esquema
-- Fecha: 2025-09-30
-- Requisitos: MySQL 8.0+ o MariaDB 10.4+ (por compatibilidad con ENUM y ALTER)
-- ------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS alaska CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alaska;

DELIMITER $$

DROP PROCEDURE IF EXISTS aplicar_migracion_alaska $$
CREATE PROCEDURE aplicar_migracion_alaska ()
BEGIN
    -- Asegurar columna username en usuarios
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'username'
    ) THEN
        IF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'usuario'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `usuario` `username` VARCHAR(50) NOT NULL;
        ELSEIF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'nick'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `nick` `username` VARCHAR(50) NOT NULL;
        ELSE
            ALTER TABLE usuarios ADD COLUMN `username` VARCHAR(50) NULL AFTER `nombre`;
        END IF;
    END IF;

    UPDATE usuarios
       SET username = CONCAT('usuario_', id)
     WHERE (username IS NULL OR username = '');

    ALTER TABLE usuarios MODIFY COLUMN `username` VARCHAR(50) NOT NULL;

    -- Índice único para username
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND INDEX_NAME = 'unique_username'
    ) THEN
        ALTER TABLE usuarios ADD UNIQUE KEY `unique_username` (`username`);
    END IF;

    -- Asegurar columna email
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'email'
    ) THEN
        IF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'correo'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `correo` `email` VARCHAR(100) NOT NULL;
        ELSE
            ALTER TABLE usuarios ADD COLUMN `email` VARCHAR(100) NULL AFTER `username`;
        END IF;
    END IF;

    UPDATE usuarios
       SET email = CONCAT(username, '@alaska.local')
     WHERE (email IS NULL OR email = '');

    ALTER TABLE usuarios MODIFY COLUMN `email` VARCHAR(100) NOT NULL;

    -- Índice único para email
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND INDEX_NAME = 'unique_email'
    ) THEN
        ALTER TABLE usuarios ADD UNIQUE KEY `unique_email` (`email`);
    END IF;

    -- Asegurar columna password
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'password'
    ) THEN
        IF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'contrasena'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `contrasena` `password` VARCHAR(255) NOT NULL;
        ELSEIF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'contraseña'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `contraseña` `password` VARCHAR(255) NOT NULL;
        ELSEIF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'clave'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `clave` `password` VARCHAR(255) NOT NULL;
        ELSEIF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'pass'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `pass` `password` VARCHAR(255) NOT NULL;
        ELSEIF EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'password_hash'
        ) THEN
            ALTER TABLE usuarios CHANGE COLUMN `password_hash` `password` VARCHAR(255) NOT NULL;
        ELSE
            ALTER TABLE usuarios ADD COLUMN `password` VARCHAR(255) NULL AFTER `email`;
        END IF;
    END IF;

    UPDATE usuarios
       SET password = '$2y$12$TtrKYdTALqFwmRUE55fvu.J6XYsVO/2vPuVUWHrZFiEfUOLcy5pEK'
     WHERE (password IS NULL OR password = '');

    ALTER TABLE usuarios MODIFY COLUMN `password` VARCHAR(255) NOT NULL;

    -- Columnas extra de usuarios
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'fotos_mascotas'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN `fotos_mascotas` TEXT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'usuarios'
          AND COLUMN_NAME = 'mascotas_guardadas'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN `mascotas_guardadas` TEXT NULL;
    END IF;

    -- Columnas extra de publicaciones
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'publicaciones'
          AND COLUMN_NAME = 'tipo'
    ) THEN
        ALTER TABLE publicaciones ADD COLUMN `tipo` ENUM('general','mascota','evento','consejo') DEFAULT 'general';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'publicaciones'
          AND COLUMN_NAME = 'nombre_mascota'
    ) THEN
        ALTER TABLE publicaciones ADD COLUMN `nombre_mascota` VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'publicaciones'
          AND COLUMN_NAME = 'ubicacion'
    ) THEN
        ALTER TABLE publicaciones ADD COLUMN `ubicacion` VARCHAR(255) NULL;
    END IF;
END $$

DELIMITER ;

CALL aplicar_migracion_alaska();
DROP PROCEDURE IF EXISTS aplicar_migracion_alaska;

-- Normalizar contenido existente con el nuevo nombre "ALASKA"
UPDATE usuarios
     SET biografia = REPLACE(biografia, 'PatasVerdes', 'ALASKA')
 WHERE biografia LIKE '%PatasVerdes%';

UPDATE publicaciones
     SET contenido = REPLACE(contenido, 'PatasVerdes', 'ALASKA')
 WHERE contenido LIKE '%PatasVerdes%';

UPDATE usuarios
     SET username = REPLACE(username, 'patasverdes', 'alaska')
 WHERE username LIKE '%patasverdes%';

-- Tablas de soporte (creadas sólo si no existen)
CREATE TABLE IF NOT EXISTS guardados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    publicacion_id INT NOT NULL,
    fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_guardado (usuario_id, publicacion_id),
    CONSTRAINT fk_guardados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_guardados_publicacion FOREIGN KEY (publicacion_id) REFERENCES publicaciones (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    es_privada BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_listas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS miembros_lista (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_miembro_lista (lista_id, usuario_id),
    CONSTRAINT fk_miembros_lista_lista FOREIGN KEY (lista_id) REFERENCES listas (id) ON DELETE CASCADE,
    CONSTRAINT fk_miembros_lista_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('like','comentario','seguimiento','mencion') NOT NULL,
    actor_id INT NOT NULL,
    publicacion_id INT,
    comentario_id INT,
    leida BOOLEAN DEFAULT FALSE,
    fecha_notificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_notificaciones_actor FOREIGN KEY (actor_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_notificaciones_publicacion FOREIGN KEY (publicacion_id) REFERENCES publicaciones (id) ON DELETE CASCADE,
    CONSTRAINT fk_notificaciones_comentario FOREIGN KEY (comentario_id) REFERENCES comentarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    leido BOOLEAN DEFAULT FALSE,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensajes_remitente FOREIGN KEY (remitente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_mensajes_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario1_id INT NOT NULL,
    usuario2_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_conversacion (usuario1_id, usuario2_id),
    CONSTRAINT fk_conversaciones_usuario1 FOREIGN KEY (usuario1_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_conversaciones_usuario2 FOREIGN KEY (usuario2_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reportante_id INT NOT NULL,
    tipo_objeto ENUM('publicacion','usuario','comentario') NOT NULL,
    objeto_id INT NOT NULL,
    motivo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente','revisado','accionado') DEFAULT 'pendiente',
    fecha_reporte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reportes_usuario FOREIGN KEY (reportante_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS advertencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    motivo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha_advertencia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_advertencias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mascotas_emergencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('perdida','adopcion') NOT NULL,
    nombre_mascota VARCHAR(100),
    descripcion TEXT,
    ubicacion VARCHAR(255),
    imagen VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mascotas_emergencia_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comunidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    descripcion TEXT,
    creador_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    CONSTRAINT fk_comunidades_creador FOREIGN KEY (creador_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS miembros_comunidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    usuario_id INT NOT NULL,
    rol ENUM('miembro','moderador','administrador') DEFAULT 'miembro',
    fecha_union TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_miembro_comunidad (comunidad_id, usuario_id),
    CONSTRAINT fk_miembros_comunidad_comunidad FOREIGN KEY (comunidad_id) REFERENCES comunidades (id) ON DELETE CASCADE,
    CONSTRAINT fk_miembros_comunidad_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS publicaciones_comunidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunidad_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(255),
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_publicaciones_comunidad FOREIGN KEY (comunidad_id) REFERENCES comunidades (id) ON DELETE CASCADE,
    CONSTRAINT fk_publicaciones_comunidad_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS autenticacion_dos_factores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    secret_key VARCHAR(255) NOT NULL,
    activo BOOLEAN DEFAULT FALSE,
    fecha_activacion TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario_2fa (usuario_id),
    CONSTRAINT fk_autenticacion_dos_factores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    icono VARCHAR(50) NOT NULL,
    activo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    ubicacion VARCHAR(255) NOT NULL,
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8),
    telefono VARCHAR(20),
    email VARCHAR(100),
    sitio_web VARCHAR(255),
    calificacion DECIMAL(2,1) DEFAULT 0.0,
    total_calificaciones INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_servicios_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_servicios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calificaciones_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    usuario_id INT NOT NULL,
    calificacion INT NOT NULL,
    comentario TEXT,
    fecha_calificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_calificacion_servicio (servicio_id, usuario_id),
    CONSTRAINT fk_calificaciones_servicio FOREIGN KEY (servicio_id) REFERENCES servicios (id) ON DELETE CASCADE,
    CONSTRAINT fk_calificaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campanas_donacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emergencia_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    meta DECIMAL(10,2) NOT NULL,
    total_donado DECIMAL(10,2) DEFAULT 0.00,
    activo BOOLEAN DEFAULT TRUE,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,
    CONSTRAINT fk_campanas_donacion_emergencia FOREIGN KEY (emergencia_id) REFERENCES mascotas_emergencia (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS donaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campana_id INT NOT NULL,
    donante_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('stripe','paypal','transferencia') NOT NULL,
    estado ENUM('pendiente','completado','fallido') DEFAULT 'pendiente',
    referencia_pago VARCHAR(255),
    fecha_donacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donaciones_campana FOREIGN KEY (campana_id) REFERENCES campanas_donacion (id) ON DELETE CASCADE,
    CONSTRAINT fk_donaciones_donante FOREIGN KEY (donante_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fin del script
