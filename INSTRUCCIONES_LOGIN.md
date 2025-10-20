# Sistema de Login Dual - Instrucciones de Configuración

## 📋 Resumen del Sistema

Se ha implementado un sistema de autenticación completo con dos tipos de usuarios:

### Roles de Usuario
1. **Administrador (admin)**: Acceso completo a todas las funcionalidades del sistema
2. **Cliente (cliente)**: Acceso limitado solo a ver propiedades disponibles

---

## 🚀 Pasos de Instalación

### 1. Crear la Tabla de Usuarios

Ejecuta el script SQL para crear la tabla de usuarios:

```bash
# Opción A: Desde línea de comandos MySQL
mysql -u root -p real_estate_db < database/usuarios_schema.sql

# Opción B: Desde phpMyAdmin
# - Abre phpMyAdmin (http://localhost/phpmyadmin)
# - Selecciona la base de datos "real_estate_db"
# - Ve a la pestaña "SQL"
# - Copia y pega el contenido de database/usuarios_schema.sql
# - Presiona "Ejecutar"
```

### 2. Verificar la Instalación

La tabla `usuarios` debe contener:
- Campos: id_usuario, username, password_hash, email, nombre_completo, rol, id_cliente, activo, etc.
- 2 usuarios de prueba (admin y cliente1)

---

## 🔐 Credenciales de Prueba

### Usuario Administrador
- **Usuario**: `admin`
- **Contraseña**: `admin123`
- **Email**: admin@inmobiliaria.com
- **Acceso**: Panel completo de administración (todos los módulos CRUD)

### Usuario Cliente
- **Usuario**: `cliente1`
- **Contraseña**: `cliente123`
- **Email**: cliente1@example.com
- **Acceso**: Solo visualización de propiedades (read-only)

**IMPORTANTE:** Los hashes de contraseña en el SQL ya están actualizados y son válidos. Si reinstalaras la base de datos con `usuarios_schema.sql`, estas credenciales funcionarán inmediatamente.

---

## 📂 Estructura del Sistema

```
/
├── login.php                    # Página de login unificada
├── index.php                    # Requiere autenticación, redirige según rol
│
├── admin/                       # Área de Administrador
│   ├── dashboard.php           # Panel principal admin
│   └── logout.php              # Cerrar sesión admin
│
├── cliente/                     # Área de Cliente
│   ├── dashboard.php           # Panel principal cliente
│   └── logout.php              # Cerrar sesión cliente
│
├── modules/                     # Módulos funcionales
│   ├── properties/             # Inmuebles
│   ├── clients/                # Clientes
│   ├── agents/                 # Agentes
│   ├── sales/                  # Ventas
│   ├── contracts/              # Contratos
│   ├── rentals/                # Arriendos
│   └── visits/                 # Visitas
│
└── database/
    └── usuarios_schema.sql     # Script de creación de usuarios
```

---

## 🔄 Flujo de Autenticación

### 1. Login
- Usuario accede a `login.php`
- Ingresa username/email y contraseña
- Sistema valida credenciales
- Redirige según rol:
  - **Admin** → `admin/dashboard.php`
  - **Cliente** → `cliente/dashboard.php`

### 2. Protección de Rutas
- `index.php` requiere autenticación
- Si no está logueado → redirige a `login.php`
- Si es cliente → solo puede ver módulo `properties` (lista y detalles)
- Si es admin → acceso completo a todos los módulos

### 3. Logout
- Click en "Cerrar Sesión"
- Destruye la sesión
- Redirige a `login.php`

---

## 👤 Diferencias entre Roles

### Administrador (admin)
✅ Ver todas las propiedades
✅ Crear, editar, eliminar propiedades
✅ Gestionar clientes
✅ Gestionar agentes
✅ Registrar ventas
✅ Crear contratos
✅ Gestionar arriendos
✅ Programar visitas
✅ Acceso a estadísticas completas
✅ Gestionar usuarios del sistema

### Cliente (cliente)
✅ Ver propiedades disponibles
✅ Ver detalles de propiedades
❌ No puede crear/editar/eliminar propiedades
❌ No tiene acceso a otros módulos (clientes, agentes, ventas, etc.)
❌ No puede ver estadísticas del sistema

---

## 🔧 Funciones de Seguridad Implementadas

### 1. Hashing de Contraseñas
```php
// Las contraseñas se guardan con password_hash()
$hash = password_hash($password, PASSWORD_DEFAULT);

// Se verifican con password_verify()
if (password_verify($password, $hash)) {
    // Login exitoso
}
```

### 2. Protección contra Fuerza Bruta
- Máximo 5 intentos de login fallidos
- Bloqueo de cuenta por 15 minutos tras 5 intentos
- Contador de intentos se resetea tras login exitoso

### 3. Gestión de Sesiones
- Sesión segura con regeneración de ID
- Timeout de sesión (1 hora por defecto)
- Verificación de actividad del usuario

### 4. Autorización por Roles
- Middleware verifica permisos en cada request
- Redireccionamiento automático según privilegios
- Mensajes de error si intenta acceder sin permisos

### 5. CSRF Protection
- Tokens CSRF en todos los formularios
- Validación de tokens en requests POST
- Expiración de tokens (30 minutos)

---

## 📝 Crear Nuevos Usuarios

### Opción 1: Desde phpMyAdmin

```sql
-- Crear nuevo usuario administrador
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
VALUES (
    'nuevo_admin',
    '$2y$12$...',  -- Hash generado con password_hash('tu_password', PASSWORD_DEFAULT) en PHP 8+
    'admin@example.com',
    'Nuevo Administrador',
    'admin',
    TRUE
);

-- Crear nuevo usuario cliente
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, id_cliente, activo)
VALUES (
    'nuevo_cliente',
    '$2y$12$...',  -- Hash generado (BCRYPT cost=12)
    'cliente@example.com',
    'Nuevo Cliente',
    'cliente',
    1,  -- ID del cliente en la tabla 'cliente' (opcional, puede ser NULL)
    TRUE
);
```

**Notas importantes sobre hashes:**
- PHP 8+ usa automáticamente cost=12 para BCRYPT (más seguro que cost=10 de versiones anteriores)
- Los hashes generados son únicos cada vez, incluso para la misma contraseña
- Un hash válido de BCRYPT siempre empieza con `$2y$12$` (en PHP 8+) o `$2y$10$` (versiones anteriores)

### Opción 2: Usar generate_password_hash.php

El proyecto incluye una utilidad web para generar hashes:

```bash
# 1. Inicia el servidor PHP
php -S localhost:8000

# 2. Accede en tu navegador a:
# http://localhost:8000/generate_password_hash.php

# 3. Ingresa la contraseña deseada
# 4. Copia el hash generado
# 5. Úsalo en tu INSERT SQL
```

**IMPORTANTE:** Elimina `generate_password_hash.php` en producción por seguridad.

### Opción 3: Generar Hash desde Línea de Comandos

```bash
# Generar hash directamente
php -r "echo password_hash('tu_password', PASSWORD_DEFAULT);"

# Ejemplo de salida:
# $2y$12$AbC1234567890XyZaBcDe.FgHiJkLmNoPqRsTuVwXyZaBcDeFgHiJk

# Copiar el hash completo (incluye $2y$12$ al inicio)
```

---

## 🎨 Personalización

### Cambiar Rutas de Redirección

Edita `includes/functions.php`:

```php
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php'); // Cambia la ruta aquí
        exit;
    }
}
```

### Modificar Permisos de Cliente

Edita `index.php` para permitir más módulos:

```php
// Actualmente solo permite 'properties'
if ($module !== 'properties') {
    // Agregar más módulos permitidos:
    // if (!in_array($module, ['properties', 'visits'])) {
}
```

### Cambiar Timeout de Sesión

Edita `config/constants.php`:

```php
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
// Cambiar a 7200 para 2 horas, etc.
```

---

## 🐛 Solución de Problemas

### Error: "Token inválido"
- **Causa**: Token CSRF expirado o inválido
- **Solución**: Recargar la página y volver a intentar

### Error: "Cuenta bloqueada"
- **Causa**: 5 intentos de login fallidos
- **Solución**: Esperar 15 minutos o desbloquear manualmente:
```sql
UPDATE usuarios SET intentos_login = 0, bloqueado_hasta = NULL WHERE username = 'usuario';
```

### No puedo crear nuevos usuarios
- **Causa**: Solo administradores pueden gestionar usuarios
- **Solución**: Iniciar sesión como admin o crear desde SQL

### Cliente ve página en blanco
- **Causa**: Permisos insuficientes
- **Solución**: Verificar que el cliente solo accede a módulos permitidos

---

## 📊 Tablas de Base de Datos

### Tabla: usuarios

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id_usuario | INT | ID único (PK) |
| username | VARCHAR(50) | Nombre de usuario único |
| password_hash | VARCHAR(255) | Contraseña encriptada |
| email | VARCHAR(150) | Correo electrónico único |
| nombre_completo | VARCHAR(200) | Nombre completo |
| rol | ENUM('admin','cliente') | Rol del usuario |
| id_cliente | INT | FK a tabla cliente (nullable) |
| activo | BOOLEAN | Usuario activo/inactivo |
| ultimo_acceso | DATETIME | Último login |
| intentos_login | INT | Contador intentos fallidos |
| bloqueado_hasta | DATETIME | Fecha de bloqueo |

---

## 📞 Soporte

Para más información, consulta:
- `CLAUDE.md` - Documentación del proyecto
- `ENVIRONMENT_SETUP.md` - Configuración del entorno
- `database/schema.sql` - Esquema completo de base de datos

---

## ✅ Checklist de Verificación

- [ ] Base de datos `real_estate_db` creada
- [ ] Tabla `usuarios` creada con `usuarios_schema.sql`
- [ ] Usuarios de prueba (admin/cliente1) insertados
- [ ] XAMPP/servidor web ejecutándose
- [ ] Puedo acceder a `login.php`
- [ ] Login con admin funciona → redirige a `admin/dashboard.php`
- [ ] Login con cliente1 funciona → redirige a `cliente/dashboard.php`
- [ ] Cliente no puede acceder a módulos restringidos
- [ ] Admin tiene acceso completo
- [ ] Logout funciona correctamente
- [ ] Sesiones expiran después del timeout

---

## 🎉 ¡Listo!

El sistema de login dual está completamente configurado. Ahora tienes:

✅ Autenticación segura con hashing de contraseñas
✅ Dos roles diferenciados (admin y cliente)
✅ Dashboards separados con funcionalidades específicas
✅ Protección contra ataques de fuerza bruta
✅ Gestión de sesiones segura
✅ Middleware de autorización por roles

**¡Disfruta tu sistema de gestión inmobiliaria con login dual!** 🏠🔐
