<?php
/**
 * Conexión a la base de datos mediante PDO.
 * 
 * Usa credenciales por defecto de XAMPP (usuario 'root' sin contraseña).
 * Ajusta $username y $password si usas otro entorno o has configurado claves.
 */

$host = 'localhost';                // Host del servidor MySQL/MariaDB
$dbname = 'alaska';                 // Nombre de la base de datos
$username = 'root';                 // Usuario por defecto de XAMPP
$password = '';                     // Sin contraseña por defecto en XAMPP

try {
    // Se especifica charset UTF-8 para evitar problemas con acentos y caracteres especiales
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Lanza excepciones en errores para poder capturarlos y depurar
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Migración ligera: asegurar que la tabla usuarios tenga columnas clave (username/email/password)
    try {
        $columnsStmt = $pdo->query("SHOW COLUMNS FROM usuarios");
        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_map(fn($col) => $col['Field'], $columns);

        if (!in_array('username', $columnNames, true)) {
            if (in_array('usuario', $columnNames, true)) {
                $pdo->exec("ALTER TABLE usuarios CHANGE usuario username VARCHAR(50) NOT NULL");
            } elseif (in_array('nick', $columnNames, true)) {
                $pdo->exec("ALTER TABLE usuarios CHANGE nick username VARCHAR(50) NOT NULL");
            } else {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN username VARCHAR(50) NULL AFTER nombre");
                $pdo->exec("UPDATE usuarios SET username = CONCAT('usuario_', id) WHERE username IS NULL OR username = ''");
            }
            $idxUsername = $pdo->query("SHOW INDEX FROM usuarios WHERE Key_name = 'unique_username'");
            if ($idxUsername->rowCount() === 0) {
                $pdo->exec("ALTER TABLE usuarios ADD UNIQUE KEY unique_username (username)");
            }
        }

        if (!in_array('email', $columnNames, true)) {
            if (in_array('correo', $columnNames, true)) {
                // Reutiliza la columna existente 'correo'
                $pdo->exec("ALTER TABLE usuarios CHANGE correo email VARCHAR(100) NOT NULL");
            } else {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN email VARCHAR(100) NULL AFTER username");
                // Generar emails temporales para registros antiguos
                $pdo->exec("UPDATE usuarios SET email = CONCAT(username, '@alaska.local') WHERE email IS NULL OR email = ''");
                // Aplicar restricción única si no existe
                $indexes = $pdo->query("SHOW INDEX FROM usuarios WHERE Key_name = 'unique_email'");
                if ($indexes->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE usuarios ADD UNIQUE KEY unique_email (email)");
                }
            }

            if (!in_array('password', $columnNames, true)) {
                $renamed = false;
                $alternativas = ['contrasena', 'contraseña', 'clave', 'pass', 'password_hash'];
                foreach ($alternativas as $alt) {
                    if (in_array($alt, $columnNames, true)) {
                        $pdo->exec("ALTER TABLE usuarios CHANGE `$alt` password VARCHAR(255) NOT NULL");
                        $renamed = true;
                        break;
                    }
                }
                if (!$renamed) {
                    try {
                        $pdo->exec("ALTER TABLE usuarios ADD COLUMN password VARCHAR(255) NULL AFTER email");
                    } catch (PDOException $e) {
                        if ($e->getCode() !== '42S21' && $e->getCode() !== '1060') {
                            throw $e;
                        }
                    }
                }
                $defaultHash = password_hash('alaska123', PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET password = :hash WHERE password IS NULL OR password = ''")
                    ->execute([':hash' => $defaultHash]);
            }
        }
    } catch (PDOException $e) {
        error_log('Migración columnas usuarios falló: ' . $e->getMessage());
    }
    
    // Migración ligera: crear tablas nuevas si no existen (para evitar errores si no se importó el SQL actualizado)
    // Nota: Estas tablas complementan la funcionalidad de notificaciones, guardados, listas, mensajes y conversaciones.
    // Si ya existen, CREATE TABLE IF NOT EXISTS no realizará cambios.
    $migraciones = [
        // Publicaciones guardadas
        "CREATE TABLE IF NOT EXISTS guardados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            publicacion_id INT NOT NULL,
            fecha_guardado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
            UNIQUE KEY unique_guardado (usuario_id, publicacion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Listas
        "CREATE TABLE IF NOT EXISTS listas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            es_privada BOOLEAN DEFAULT FALSE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Miembros de lista
        "CREATE TABLE IF NOT EXISTS miembros_lista (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lista_id INT NOT NULL,
            usuario_id INT NOT NULL,
            fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lista_id) REFERENCES listas(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_miembro (lista_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Notificaciones
        "CREATE TABLE IF NOT EXISTS notificaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo ENUM('like', 'comentario', 'seguimiento', 'mencion') NOT NULL,
            actor_id INT NOT NULL,
            publicacion_id INT,
            comentario_id INT,
            leida BOOLEAN DEFAULT FALSE,
            fecha_notificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (actor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
            FOREIGN KEY (comentario_id) REFERENCES comentarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Mensajes
        "CREATE TABLE IF NOT EXISTS mensajes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            remitente_id INT NOT NULL,
            destinatario_id INT NOT NULL,
            contenido TEXT NOT NULL,
            leido BOOLEAN DEFAULT FALSE,
            fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Conversaciones (par único sin importar orden)
        "CREATE TABLE IF NOT EXISTS conversaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario1_id INT NOT NULL,
            usuario2_id INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario1_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario2_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_conversacion (usuario1_id, usuario2_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Reportes de contenido / usuarios
        "CREATE TABLE IF NOT EXISTS reportes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reportante_id INT NOT NULL,
            tipo_objeto ENUM('publicacion','usuario','comentario') NOT NULL,
            objeto_id INT NOT NULL,
            motivo VARCHAR(100) NOT NULL,
            descripcion TEXT,
            estado ENUM('pendiente','revisado','accionado') DEFAULT 'pendiente',
            fecha_reporte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reportante_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Advertencias a usuarios (moderación)
        "CREATE TABLE IF NOT EXISTS advertencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            motivo VARCHAR(150) NOT NULL,
            descripcion TEXT,
            fecha_advertencia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Emergencias (mascotas perdidas o en adopción)
        "CREATE TABLE IF NOT EXISTS mascotas_emergencia (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo ENUM('perdida','adopcion') NOT NULL,
            nombre_mascota VARCHAR(100),
            descripcion TEXT,
            ubicacion VARCHAR(255),
            imagen VARCHAR(255),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Comunidades
        "CREATE TABLE IF NOT EXISTS comunidades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            ubicacion VARCHAR(255) NOT NULL,
            descripcion TEXT,
            creador_id INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (creador_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS miembros_comunidad (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comunidad_id INT NOT NULL,
            usuario_id INT NOT NULL,
            rol ENUM('miembro','moderador','administrador') DEFAULT 'miembro',
            fecha_union TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_miembro (comunidad_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS publicaciones_comunidad (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comunidad_id INT NOT NULL,
            usuario_id INT NOT NULL,
            contenido TEXT NOT NULL,
            imagen VARCHAR(255),
            fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (comunidad_id) REFERENCES comunidades(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // 2FA
        "CREATE TABLE IF NOT EXISTS autenticacion_dos_factores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            secret_key VARCHAR(255) NOT NULL,
            activo BOOLEAN DEFAULT FALSE,
            fecha_activacion TIMESTAMP NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Servicios profesionales
        "CREATE TABLE IF NOT EXISTS categorias_servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            icono VARCHAR(50) NOT NULL,
            activo BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS servicios (
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
            FOREIGN KEY (categoria_id) REFERENCES categorias_servicios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS calificaciones_servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            servicio_id INT NOT NULL,
            usuario_id INT NOT NULL,
            calificacion INT NOT NULL,
            comentario TEXT,
            fecha_calificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_calificacion (servicio_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // Donaciones
        "CREATE TABLE IF NOT EXISTS campanas_donacion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emergencia_id INT NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            descripcion TEXT,
            meta DECIMAL(10,2) NOT NULL,
            total_donado DECIMAL(10,2) DEFAULT 0.00,
            activo BOOLEAN DEFAULT TRUE,
            fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_fin TIMESTAMP NULL,
            FOREIGN KEY (emergencia_id) REFERENCES mascotas_emergencia(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS donaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            campana_id INT NOT NULL,
            donante_id INT NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            metodo_pago ENUM('stripe','paypal','transferencia') NOT NULL,
            estado ENUM('pendiente','completado','fallido') DEFAULT 'pendiente',
            referencia_pago VARCHAR(255),
            fecha_donacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (campana_id) REFERENCES campanas_donacion(id) ON DELETE CASCADE,
            FOREIGN KEY (donante_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    ];
    foreach ($migraciones as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* ignorar si falla una por FK desordenada */ }
    }

    // Asegurar columna fotos_mascotas en usuarios (si no existe)
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS fotos_mascotas TEXT DEFAULT NULL");
    } catch (PDOException $e) { /* ignorar si no soporta IF NOT EXISTS, intentamos fallback */
        try {
            // Fallback: comprobar existencia rudimentaria
            $result = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'fotos_mascotas'");
            if ($result->rowCount() === 0) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN fotos_mascotas TEXT DEFAULT NULL");
            }
        } catch (PDOException $e2) { /* ignorar */ }
    }

    // Crear carpetas de subida si no existen
    $uploadDirs = [
        __DIR__ . '/../uploads',
        __DIR__ . '/../uploads/perfiles',
        __DIR__ . '/../uploads/mascotas',
    ];
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    }

    // Asegurar nuevas columnas para tipos de publicaciones
    try {
        $pdo->exec("ALTER TABLE publicaciones ADD COLUMN IF NOT EXISTS tipo ENUM('general','mascota','evento','consejo') DEFAULT 'general'");
    } catch (PDOException $e) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM publicaciones LIKE 'tipo'");
            if ($col->rowCount() === 0) {
                $pdo->exec("ALTER TABLE publicaciones ADD COLUMN tipo ENUM('general','mascota','evento','consejo') DEFAULT 'general'");
            }
        } catch (PDOException $e2) { /* ignorar */ }
    }
    try {
        $pdo->exec("ALTER TABLE publicaciones ADD COLUMN IF NOT EXISTS nombre_mascota VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM publicaciones LIKE 'nombre_mascota'");
            if ($col->rowCount() === 0) {
                $pdo->exec("ALTER TABLE publicaciones ADD COLUMN nombre_mascota VARCHAR(100) DEFAULT NULL");
            }
        } catch (PDOException $e2) { /* ignorar */ }
    }

    // Nuevas columnas solicitadas: ubicacion en publicaciones y mascotas_guardadas en usuarios
    try {
        $pdo->exec("ALTER TABLE publicaciones ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM publicaciones LIKE 'ubicacion'");
            if ($col->rowCount() === 0) {
                $pdo->exec("ALTER TABLE publicaciones ADD COLUMN ubicacion VARCHAR(255) DEFAULT NULL");
            }
        } catch (PDOException $e2) { /* ignorar */ }
    }
    try {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS mascotas_guardadas TEXT DEFAULT NULL");
    } catch (PDOException $e) {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'mascotas_guardadas'");
            if ($col->rowCount() === 0) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN mascotas_guardadas TEXT DEFAULT NULL");
            }
        } catch (PDOException $e2) { /* ignorar */ }
    }
} catch(PDOException $e) {
    // Si falla la conexión, detenemos la ejecución mostrando el mensaje de error
    die("Error de conexión: " . $e->getMessage());
}
?>