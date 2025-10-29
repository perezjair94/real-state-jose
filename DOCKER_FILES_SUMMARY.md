# Docker Configuration Files Summary

## Archivos Creados para Ejecutar con Docker

Este documento resume todos los archivos de configuración Docker que han sido creados para tu proyecto.

### 1. **Dockerfile**
**Ubicación**: `/Dockerfile`

Archivo principal que define la imagen Docker con:
- Base: `php:8.2-apache`
- Instalación de todas las extensiones PHP necesarias (PDO, MySQL, GD, intl, mbstring, zip, opcache)
- Configuración de Apache (mod_rewrite, mod_headers)
- Copia de archivos de aplicación
- Health check automático

### 2. **docker-compose.yml**
**Ubicación**: `/docker-compose.yml`

Archivo de orquestación que define 3 servicios:

#### **web** - Contenedor PHP/Apache
- Puerto: `8080` → `80`
- Volúmenes: Código fuente compartido
- Ambiente: Variables de base de datos
- Depende de: `database`

#### **database** - MySQL 8.0
- Puerto: `3306` → `3306`
- Variables: Credenciales de BD
- Volúmenes: Datos persistentes + scripts SQL iniciales
- Health check: Verifica conectividad de MySQL

#### **phpmyadmin** - Interfaz web para MySQL
- Puerto: `8081` → `80`
- Conecta automáticamente a la base de datos
- Usuario: `real_estate_user` / contraseña: `real_estate_pass`

**Red**: `real-estate-network` para comunicación entre contenedores

### 3. **config/php.ini**
**Ubicación**: `/config/php.ini`

Configuración de PHP con:
- Modo desarrollo: `display_errors = On`
- Límites: `memory_limit = 256M`, `upload_max_filesize = 20M`
- Sesiones: Seguras con `httponly`, `samesite = Lax`
- Timezone: `America/Bogota`
- Opcache: Habilitado para mejor rendimiento

### 4. **config/apache.conf**
**Ubicación**: `/config/apache.conf`

Configuración de Apache Virtual Host con:
- Rewrite rules para enrutamiento
- Seguridad: Headers anti-XSS, anti-clickjacking
- Compresión GZIP
- Protección de archivos sensibles (`.env`, `.git`, etc.)
- Logs de error y acceso

### 5. **health.php**
**Ubicación**: `/health.php`

Endpoint de verificación de salud usado por Docker:
- Verifica conexión a base de datos
- Retorna JSON con estado de aplicación
- Usado por el HEALTHCHECK de Docker

### 6. **docker-helpers.sh**
**Ubicación**: `/docker-helpers.sh` (ejecutable)

Script bash con funciones de utilidad:
- `start` - Inicia los contenedores
- `stop` - Detiene los contenedores
- `restart` - Reinicia los contenedores
- `logs` - Ve los logs
- `status` - Estado de contenedores
- `shell-web` - Acceso bash al web
- `shell-db` - Acceso MySQL al database
- `backup-db` - Crea backup de BD
- `restore-db` - Restaura backup
- `reset` - Reset completo (destructivo)

**Uso**:
```bash
./docker-helpers.sh help    # Ver todas las opciones
./docker-helpers.sh start   # Iniciar
./docker-helpers.sh status  # Ver estado
```

### 7. **docker-entrypoint.sh**
**Ubicación**: `/docker-entrypoint.sh` (ejecutable)

Script de punto de entrada que:
- Espera a que MySQL esté listo
- Crea directorios necesarios
- Configura permisos
- Inicia Apache

### 8. **.dockerignore**
**Ubicación**: `/.dockerignore`

Archivos ignorados en la imagen Docker:
- Documentación (*.md)
- Control de versiones (.git, .gitignore)
- Variables de entorno (.env.example)
- Editor files (.vscode, .idea)

### 9. **.env.example**
**Ubicación**: `/.env.example`

Template de variables de entorno:
- Credenciales MySQL
- Puertos de servicio
- Configuración de PHP
- URL de aplicación

**Uso**:
```bash
cp .env.example .env
# Editar .env con tus valores si es necesario
```

### 10. **Modificación de config/database.php**
**Ubicación**: `/config/database.php` (modificado)

Se agregó un constructor `__construct()` que lee variables de entorno:
```php
$this->host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$this->db_name = getenv('MYSQL_DATABASE') ?: 'real_estate_db';
$this->username = getenv('MYSQL_USER') ?: 'root';
$this->password = getenv('MYSQL_PASSWORD') ?: '';
```

Esto permite que la aplicación funcione tanto en Docker como localmente.

## Documentación Incluida

### **QUICK_START.md**
Guía rápida de 3 pasos para empezar

### **DOCKER_SETUP.md**
Guía completa con:
- Requisitos previos
- Instalación paso a paso
- Troubleshooting detallado
- Configuración avanzada
- Backups y restauración

### **DOCKER_RUNNING.md**
Estado actual con:
- URLs de acceso
- Credenciales
- Comandos útiles
- Solución de problemas

## Flujo de Datos

```
Docker Compose
├── Web Container (Port 8080)
│   ├── Apache + PHP 8.2
│   ├── Scripts PHP del proyecto
│   └── Conecta a Database via red
│
├── Database Container (Port 3306)
│   ├── MySQL 8.0
│   ├── Volumen persistente (db_data)
│   ├── Scripts de inicialización:
│   │   ├── schema.sql
│   │   ├── usuarios_schema.sql
│   │   └── seed.sql
│   └── Health check
│
└── phpMyAdmin (Port 8081)
    ├── Interfaz web
    └── Conecta a Database via red
```

## Volúmenes Persistentes

**db_data**: Almacena datos de MySQL
- Nombre: `real-state-jose_db_data`
- Ubicación en host: `/var/lib/docker/volumes/real-state-jose_db_data/_data`
- Persiste datos entre reinicios

## Variables de Entorno

Configuradas en `docker-compose.yml`:

```yaml
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=real_estate_db
MYSQL_USER=real_estate_user
MYSQL_PASSWORD=real_estate_pass
MYSQL_HOST=database
MYSQL_PORT=3306
```

Accesibles en PHP via `getenv()`:
```php
$host = getenv('MYSQL_HOST'); // 'database'
$user = getenv('MYSQL_USER'); // 'real_estate_user'
```

## Seguridad

Implementado en la configuración:
- Credenciales separadas por rol (root vs usuario)
- Restricción de acceso de archivos en Apache
- Headers de seguridad (X-Frame-Options, Content-Security-Policy)
- MySQL con usuario limitado (no root para la app)
- HTTPS ready (infraestructura)

## Performance

Optimizaciones incluidas:
- Opcache habilitado en PHP
- GZIP compression en Apache
- Persistent MySQL connections
- Memory limit apropiado (256M)
- Índices en base de datos

## Próximos Pasos

1. **Verificar**: `docker-compose ps`
2. **Acceder**: http://localhost:8080
3. **Logs**: `docker-compose logs -f`
4. **Guardar cambios**: Los volúmenes hacen backup automático

## Comandos Rápidos

```bash
# Iniciar
docker-compose up -d

# Ver estado
docker-compose ps

# Logs
docker-compose logs -f web

# Detener
docker-compose stop

# Limpiar todo
docker-compose down -v

# Acceso shell web
docker exec -it real-estate-web bash

# Acceso MySQL
docker exec -it real-estate-db mysql -u root -prootpassword real_estate_db
```

---

**Nota**: Todos los archivos están listos para uso inmediato. La aplicación está totalmente containerizada y lista para desarrollo.
