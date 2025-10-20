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

-- Insertar usuarios de ejemplo con hashes BCRYPT válidos
-- Password para admin: admin123
-- Password para cliente: cliente123
-- NOTA: Los hashes fueron generados con password_hash('password', PASSWORD_DEFAULT) en PHP 8+

INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo) VALUES
('admin', '$2y$12$9OAUGRwdmdutmljpSwBZ..BZlTQ/Qg4HJoq9/3xCNpquDjiOgDhTG', 'admin@inmobiliaria.com', 'Administrador Principal', 'admin', TRUE),
('cliente1', '$2y$12$zMizpXl7K/aFPeMEEbTNluixm/eLRa8ypjJ9Hu2O07eZjtvgz9Psi', 'cliente1@example.com', 'Juan Pérez', 'cliente', TRUE);

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
2. Se usa password_hash() con BCRYPT (cost=12 automático en PHP 8+)
3. Sistema de bloqueo tras 5 intentos fallidos (15 minutos de bloqueo)
4. Sesiones con timeout configurado (1 hora por defecto)
5. CSRF protection en todos los formularios (30 minutos de expiración)

ROLES:
- 'admin': Acceso completo a gestión de inmuebles, clientes, agentes, ventas, contratos, arriendos y visitas
- 'cliente': Acceso limitado a ver propiedades disponibles (solo lectura, sin crear/editar/eliminar)

CREDENCIALES DE PRUEBA (Desarrollo):
┌──────────┬──────────┬────────────────────────────┐
│ Usuario  │ Password │ Rol                        │
├──────────┼──────────┼────────────────────────────┤
│ admin    │ admin123 │ admin (acceso completo)    │
│ cliente1 │ cliente123│ cliente (solo ver props)   │
└──────────┴──────────┴────────────────────────────┘

PARA CREAR NUEVOS USUARIOS:

Opción 1 - Usar generate_password_hash.php:
1. Acceder a: http://localhost/real-state-jose/generate_password_hash.php
2. Ingresar la contraseña deseada
3. Copiar el hash generado
4. Insertar en la base de datos:

INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
VALUES ('nuevo_usuario', 'HASH_COPIADO_AQUI', 'email@example.com', 'Nombre Completo', 'admin', TRUE);

Opción 2 - Desde línea de comandos PHP:
php -r "echo password_hash('tu_password', PASSWORD_DEFAULT);"

Opción 3 - Vincular con cliente existente:
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, id_cliente, activo)
VALUES ('cliente_user', 'HASH', 'email@example.com', 'Nombre', 'cliente', 123, TRUE);

DESBLOQUEAR CUENTA:
UPDATE usuarios SET intentos_login = 0, bloqueado_hasta = NULL WHERE username = 'usuario';

DESACTIVAR USUARIO:
UPDATE usuarios SET activo = FALSE WHERE username = 'usuario';
*/
