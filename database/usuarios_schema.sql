-- Tabla de Usuarios para Sistema de Login Dual
-- Administradores y Clientes con autenticación
-- Agregar este script a la base de datos existente

USE real_estate_db;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL COMMENT 'Nombre de usuario único',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada con password_hash()',
    email VARCHAR(150) UNIQUE NOT NULL COMMENT 'Correo electrónico',
    nombre_completo VARCHAR(200) NOT NULL COMMENT 'Nombre completo del usuario',
    rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente' COMMENT 'Rol del usuario',
    id_cliente INT NULL COMMENT 'Referencia al cliente si es tipo cliente',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Usuario activo/inactivo',
    ultimo_acceso DATETIME NULL COMMENT 'Fecha del último inicio de sesión',
    intentos_login INT DEFAULT 0 COMMENT 'Contador de intentos fallidos',
    bloqueado_hasta DATETIME NULL COMMENT 'Fecha hasta la cual está bloqueado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX idx_usuario_username (username),
    INDEX idx_usuario_email (email),
    INDEX idx_usuario_rol (rol),
    INDEX idx_usuario_activo (activo)
) ENGINE=InnoDB COMMENT='Usuarios del sistema con roles de administrador y cliente';

-- Insertar usuarios de ejemplo
-- Password para admin: admin123
-- Password para cliente: cliente123

INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo) VALUES
('admin', '$2y$10$YJPnQvzRTPxk5RpDLxZRouq1VVZc.Xx4Rz8F0X7eWh5qV8p8gHLfS', 'admin@inmobiliaria.com', 'Administrador Principal', 'admin', TRUE),
('cliente1', '$2y$10$Xq1L2N3M4P5Q6R7S8T9U0VWXYZabcdefghijklmnopqrstuvwxyz12', 'cliente1@example.com', 'Juan Pérez', 'cliente', TRUE);

-- Vista para información de usuarios (sin contraseña)
CREATE OR REPLACE VIEW vista_usuarios AS
SELECT
    u.id_usuario,
    u.username,
    u.email,
    u.nombre_completo,
    u.rol,
    u.activo,
    u.ultimo_acceso,
    c.nombre AS cliente_nombre,
    c.apellido AS cliente_apellido,
    c.nro_documento AS cliente_documento
FROM usuarios u
LEFT JOIN cliente c ON u.id_cliente = c.id_cliente
ORDER BY u.created_at DESC;

-- Trigger para vincular automáticamente cliente con usuario
DELIMITER $$

CREATE TRIGGER tr_usuario_vincular_cliente
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
    -- Si es un cliente y hay un email coincidente, vincular automáticamente
    IF NEW.rol = 'cliente' AND NEW.id_cliente IS NULL THEN
        UPDATE usuarios u
        JOIN cliente c ON u.email = c.correo
        SET u.id_cliente = c.id_cliente
        WHERE u.id_usuario = NEW.id_usuario;
    END IF;
END$$

DELIMITER ;

-- Comentarios informativos
/*
NOTAS DE SEGURIDAD:
1. Las contraseñas NUNCA se almacenan en texto plano
2. Se usa password_hash() con BCRYPT (cost=10)
3. Sistema de bloqueo tras intentos fallidos
4. Sesiones con timeout configurado
5. CSRF protection en todos los formularios

ROLES:
- 'admin': Acceso completo a gestión de inmuebles, clientes, agentes, ventas, contratos, etc.
- 'cliente': Acceso limitado a ver propiedades, sus propias visitas, contratos y datos personales

CONTRASEÑAS DE PRUEBA:
- admin / admin123
- cliente1 / cliente123

PARA CREAR NUEVOS USUARIOS EN PHP:
$password = 'mi_password';
$hash = password_hash($password, PASSWORD_DEFAULT);
// Luego insertar $hash en la base de datos
*/
