# Guía de Configuración del Entorno de Desarrollo
## Sistema de Gestión Inmobiliaria con PHP y MySQL

Esta guía te ayudará a configurar paso a paso el entorno de desarrollo necesario para trabajar con el Sistema de Gestión Inmobiliaria usando PHP y MySQL.

---

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener:
- **Sistema Operativo**: Windows, macOS, o Linux
- **Espacio en disco**: Al menos 1GB libre
- **Conexión a internet**: Para descargar los componentes necesarios
- **Permisos de administrador**: Para instalar software

---

## 🚀 Paso 1: Instalación del Servidor Local

### Opción A: XAMPP (Recomendado para principiantes)

#### 1.1 Descarga XAMPP
1. Ve a [https://www.apachefriends.org](https://www.apachefriends.org)
2. Descarga la versión más reciente para tu sistema operativo
3. Selecciona la versión que incluya PHP 8.0 o superior

#### 1.2 Instalación en Windows
1. Ejecuta el archivo descargado como **Administrador**
2. Si aparece el "Windows Defender SmartScreen", haz clic en **"Más información"** → **"Ejecutar de todas formas"**
3. Sigue el asistente de instalación:
   - **Directorio de instalación**: Deja por defecto `C:\xampp` (recomendado)
   - **Componentes**: Asegúrate de seleccionar:
     - ✅ Apache
     - ✅ MySQL
     - ✅ PHP
     - ✅ phpMyAdmin
4. Completa la instalación

#### 1.3 Instalación en macOS
1. Abre el archivo `.dmg` descargado
2. Arrastra XAMPP a la carpeta Applications
3. Abre Terminal y ejecuta:
   ```bash
   sudo /Applications/XAMPP/xamppfiles/xampp start
   ```

#### 1.4 Instalación en Linux (Ubuntu/Debian)
```bash
# Descarga y ejecuta el instalador
wget https://www.apachefriends.org/xampp-files/8.2.4/xampp-linux-x64-8.2.4-0-installer.run
chmod +x xampp-linux-x64-8.2.4-0-installer.run
sudo ./xampp-linux-x64-8.2.4-0-installer.run
```

### Opción B: WAMP (Solo Windows)

#### 1.1 Descarga WAMP
1. Ve a [https://www.wampserver.com](https://www.wampserver.com)
2. Descarga WampServer (versión 64-bit recomendada)

#### 1.2 Instalación
1. Ejecuta el instalador como Administrador
2. Sigue las instrucciones del asistente
3. Instala en la ruta por defecto: `C:\wamp64`

---

## ⚙️ Paso 2: Configuración Inicial del Servidor

### 2.1 Iniciar los Servicios (XAMPP)

#### En Windows:
1. Abre **XAMPP Control Panel** desde el menú inicio
2. Inicia los siguientes servicios haciendo clic en **"Start"**:
   - **Apache** (servidor web)
   - **MySQL** (base de datos)

#### En macOS/Linux:
```bash
sudo /Applications/XAMPP/xamppfiles/xampp start apache
sudo /Applications/XAMPP/xamppfiles/xampp start mysql
```

### 2.2 Verificar la Instalación

1. Abre tu navegador web
2. Ve a: `http://localhost`
3. Deberías ver la página de bienvenida de XAMPP
4. Ve a: `http://localhost/phpmyadmin`
5. Deberías ver la interfaz de phpMyAdmin

### 2.3 Solución de Problemas Comunes

#### Puerto 80 ocupado (Windows):
Si Apache no inicia, es probable que el puerto 80 esté ocupado:

1. En XAMPP Control Panel, haz clic en **"Config"** junto a Apache
2. Selecciona **"httpd.conf"**
3. Busca las líneas:
   ```apache
   Listen 80
   ServerName localhost:80
   ```
4. Cámbialas por:
   ```apache
   Listen 8080
   ServerName localhost:8080
   ```
5. Guarda y reinicia Apache
6. Ahora accede via: `http://localhost:8080`

#### MySQL no inicia:
1. Verifica que no tengas otro MySQL instalado
2. En XAMPP Control Panel → Config → MySQL → my.ini
3. Cambia el puerto si es necesario:
   ```ini
   port = 3307
   ```

---

## 📁 Paso 3: Configuración de la Estructura del Proyecto

### 3.1 Crear la Carpeta del Proyecto

#### Para XAMPP:
```
Windows: C:\xampp\htdocs\real-estate-system\
macOS/Linux: /Applications/XAMPP/htdocs/real-estate-system/
```

#### Para WAMP:
```
Windows: C:\wamp64\www\real-estate-system\
```

### 3.2 Estructura de Carpetas
Crea la siguiente estructura dentro de tu carpeta del proyecto:

```
real-estate-system/
├── index.php
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   └── footer.php
├── modules/
│   ├── properties/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── functions.php
│   ├── clients/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── functions.php
│   ├── agents/
│   ├── sales/
│   ├── contracts/
│   ├── rentals/
│   └── visits/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── uploads/
│   ├── properties/
│   └── contracts/
└── database/
    ├── schema.sql
    └── sample_data.sql
```

### 3.3 Comando para Crear la Estructura

#### En Windows (usando Command Prompt):
```cmd
cd C:\xampp\htdocs
mkdir real-estate-system
cd real-estate-system
mkdir config includes modules assets uploads database
mkdir modules\properties modules\clients modules\agents modules\sales modules\contracts modules\rentals modules\visits
mkdir assets\css assets\js assets\images
mkdir uploads\properties uploads\contracts
```

#### En macOS/Linux:
```bash
cd /Applications/XAMPP/htdocs
mkdir -p real-estate-system/{config,includes,modules/{properties,clients,agents,sales,contracts,rentals,visits},assets/{css,js,images},uploads/{properties,contracts},database}
```

---

## 🗄️ Paso 4: Configuración de la Base de Datos

### 4.1 Crear la Base de Datos

1. Ve a `http://localhost/phpmyadmin`
2. Haz clic en **"Nueva"** en el panel izquierdo
3. Nombre de la base de datos: `real_estate_db`
4. Cotejamiento: `utf8_general_ci`
5. Haz clic en **"Crear"**

### 4.2 Crear el Archivo de Esquema

Crea el archivo `database/schema.sql` con el siguiente contenido:

```sql
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS real_estate_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE real_estate_db;

-- Tabla: clientes
CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    codigo_cliente VARCHAR(10) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    tipo_documento ENUM('CC', 'CE', 'TI', 'PP') NOT NULL,
    nro_documento VARCHAR(20) UNIQUE NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    direccion TEXT NOT NULL,
    tipo_cliente ENUM('Comprador', 'Arrendatario', 'Vendedor') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: agentes
CREATE TABLE agentes (
    id_agente INT AUTO_INCREMENT PRIMARY KEY,
    codigo_agente VARCHAR(10) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    asesor BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: inmuebles
CREATE TABLE inmuebles (
    id_inmueble INT AUTO_INCREMENT PRIMARY KEY,
    codigo_inmueble VARCHAR(10) UNIQUE NOT NULL,
    tipo_inmueble ENUM('Casa', 'Apartamento', 'Local', 'Oficina', 'Terreno') NOT NULL,
    direccion TEXT NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    precio DECIMAL(15,2) NOT NULL,
    estado ENUM('Disponible', 'Vendido', 'Arrendado') DEFAULT 'Disponible',
    descripcion TEXT,
    fotos TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: ventas
CREATE TABLE ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    codigo_venta VARCHAR(10) UNIQUE NOT NULL,
    fecha_venta DATE NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    id_inmueble INT NOT NULL,
    id_cliente INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inmueble) REFERENCES inmuebles(id_inmueble) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

-- Tabla: contratos
CREATE TABLE contratos (
    id_contrato INT AUTO_INCREMENT PRIMARY KEY,
    codigo_contrato VARCHAR(10) UNIQUE NOT NULL,
    tipo_contrato ENUM('Venta', 'Arriendo') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    archivo_contrato VARCHAR(255),
    id_inmueble INT NOT NULL,
    id_cliente INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inmueble) REFERENCES inmuebles(id_inmueble) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

-- Tabla: arriendos
CREATE TABLE arriendos (
    id_arriendo INT AUTO_INCREMENT PRIMARY KEY,
    codigo_arriendo VARCHAR(10) UNIQUE NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    canon_mensual DECIMAL(10,2) NOT NULL,
    estado ENUM('Activo', 'Finalizado', 'Suspendido') DEFAULT 'Activo',
    id_inmueble INT NOT NULL,
    id_cliente INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inmueble) REFERENCES inmuebles(id_inmueble) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

-- Tabla: visitas
CREATE TABLE visitas (
    id_visita INT AUTO_INCREMENT PRIMARY KEY,
    codigo_visita VARCHAR(10) UNIQUE NOT NULL,
    fecha_visita DATE NOT NULL,
    hora_visita TIME NOT NULL,
    id_inmueble INT NOT NULL,
    id_cliente INT NOT NULL,
    id_agente INT NOT NULL,
    estado ENUM('Programada', 'Realizada', 'Cancelada') DEFAULT 'Programada',
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inmueble) REFERENCES inmuebles(id_inmueble) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_agente) REFERENCES agentes(id_agente) ON DELETE CASCADE
);
```

### 4.3 Importar el Esquema

#### Método 1: Usando phpMyAdmin
1. Ve a `http://localhost/phpmyadmin`
2. Selecciona la base de datos `real_estate_db`
3. Haz clic en **"Importar"**
4. Selecciona el archivo `schema.sql`
5. Haz clic en **"Continuar"**

#### Método 2: Usando línea de comandos
```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysql -u root -p real_estate_db < "C:\xampp\htdocs\real-estate-system\database\schema.sql"

# macOS/Linux
cd /Applications/XAMPP/bin
./mysql -u root -p real_estate_db < /Applications/XAMPP/htdocs/real-estate-system/database/schema.sql
```

---

## 🔧 Paso 5: Configuración de PHP

### 5.1 Archivo de Conexión a la Base de Datos

Crea el archivo `config/database.php`:

```php
<?php
/**
 * Configuración de conexión a la base de datos
 * Sistema de Gestión Inmobiliaria
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = ''; // En XAMPP/WAMP por defecto no hay contraseña
$database = 'real_estate_db';
$charset = 'utf8';

// Configuración del DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

// Opciones de PDO para mejorar seguridad y rendimiento
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Crear conexión PDO
    $pdo = new PDO($dsn, $username, $password, $options);

    // Mensaje de éxito para desarrollo (quitar en producción)
    // echo "Conexión exitosa a la base de datos";

} catch (PDOException $e) {
    // Manejo de errores de conexión
    die("Error de conexión: " . $e->getMessage());
}

// Función auxiliar para generar códigos automáticos
function generateCode($pdo, $table, $prefix) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $result = $stmt->fetch();
        $number = $result['total'] + 1;
        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        throw new Exception("Error al generar código: " . $e->getMessage());
    }
}

?>
```

### 5.2 Archivo Principal del Sistema

Crea el archivo `index.php`:

```php
<?php
// Iniciar sesión para manejar mensajes
session_start();

// Incluir la configuración de la base de datos
require_once 'config/database.php';

// Obtener el módulo a mostrar (por defecto: properties)
$module = $_GET['module'] ?? 'properties';

// Módulos permitidos para seguridad
$allowed_modules = ['properties', 'clients', 'agents', 'sales', 'contracts', 'rentals', 'visits'];

// Validar que el módulo sea permitido
if (!in_array($module, $allowed_modules)) {
    $module = 'properties';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Inmobiliaria</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <?php
        // Cargar el módulo correspondiente
        $module_path = "modules/{$module}/index.php";
        if (file_exists($module_path)) {
            include $module_path;
        } else {
            echo "<div class='error'>Módulo no encontrado</div>";
        }
        ?>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
```

---

## 🧪 Paso 6: Pruebas del Entorno

### 6.1 Crear Archivo de Prueba

Crea un archivo `test_connection.php` en la carpeta raíz del proyecto:

```php
<?php
/**
 * Archivo de prueba para verificar la configuración del entorno
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Configuración</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #f5e8e8; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #e8f0f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧪 Prueba de Configuración del Entorno</h1>

    <!-- Prueba de PHP -->
    <h2>1. Información de PHP</h2>
    <div class="info">
        <strong>Versión de PHP:</strong> <?php echo phpversion(); ?><br>
        <strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?><br>
        <strong>Documento raíz:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
    </div>

    <!-- Prueba de conexión a la base de datos -->
    <h2>2. Conexión a la Base de Datos</h2>
    <?php
    try {
        require_once 'config/database.php';
        echo '<div class="success">✅ Conexión a MySQL exitosa</div>';

        // Probar consulta básica
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo '<div class="info">';
        echo '<strong>Tablas encontradas:</strong><br>';
        if (count($tables) > 0) {
            foreach ($tables as $table) {
                echo "- $table<br>";
            }
        } else {
            echo "No se encontraron tablas. Asegúrate de importar el schema.sql";
        }
        echo '</div>';

    } catch (Exception $e) {
        echo '<div class="error">❌ Error de conexión: ' . $e->getMessage() . '</div>';
    }
    ?>

    <!-- Prueba de estructura de carpetas -->
    <h2>3. Estructura de Carpetas</h2>
    <?php
    $required_folders = [
        'config', 'includes', 'modules', 'assets', 'uploads', 'database',
        'modules/properties', 'modules/clients', 'assets/css', 'assets/js'
    ];

    $missing_folders = [];
    foreach ($required_folders as $folder) {
        if (!is_dir($folder)) {
            $missing_folders[] = $folder;
        }
    }

    if (empty($missing_folders)) {
        echo '<div class="success">✅ Todas las carpetas necesarias están presentes</div>';
    } else {
        echo '<div class="error">❌ Faltan las siguientes carpetas:<br>';
        foreach ($missing_folders as $folder) {
            echo "- $folder<br>";
        }
        echo '</div>';
    }
    ?>

    <!-- Prueba de permisos -->
    <h2>4. Permisos de Escritura</h2>
    <?php
    $write_folders = ['uploads', 'uploads/properties', 'uploads/contracts'];
    $permission_errors = [];

    foreach ($write_folders as $folder) {
        if (!is_writable($folder)) {
            $permission_errors[] = $folder;
        }
    }

    if (empty($permission_errors)) {
        echo '<div class="success">✅ Permisos de escritura correctos</div>';
    } else {
        echo '<div class="error">❌ Las siguientes carpetas no tienen permisos de escritura:<br>';
        foreach ($permission_errors as $folder) {
            echo "- $folder<br>";
        }
        echo '</div>';
    }
    ?>

    <hr>
    <p><strong>Si todas las pruebas son exitosas, tu entorno está listo para el desarrollo.</strong></p>
    <p><a href="index.php">→ Ir al Sistema de Gestión Inmobiliaria</a></p>
</body>
</html>
```

### 6.2 Ejecutar las Pruebas

1. Ve a `http://localhost/real-estate-system/test_connection.php`
2. Verifica que todas las pruebas sean exitosas
3. Si hay errores, revisa los pasos anteriores

---

## 🔧 Paso 7: Configuración de Permisos (Solo para macOS/Linux)

```bash
# Ir al directorio del proyecto
cd /Applications/XAMPP/htdocs/real-estate-system

# Dar permisos de escritura a las carpetas de uploads
chmod -R 755 uploads/
chmod -R 755 uploads/properties/
chmod -R 755 uploads/contracts/

# Si es necesario, cambiar el propietario
sudo chown -R www-data:www-data uploads/ # Ubuntu/Debian
sudo chown -R _www:_www uploads/ # macOS
```

---

## 📝 Paso 8: Archivos de Configuración Adicionales

### 8.1 Archivo CSS Básico

Crea `assets/css/style.css`:

```css
/* Estilos básicos para el sistema */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f4f4f4;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Mensajes del sistema */
.success {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

/* Navegación básica */
nav ul {
    list-style: none;
    display: flex;
    background: #333;
    padding: 10px;
}

nav ul li {
    margin-right: 20px;
}

nav ul li a {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 5px;
}

nav ul li a:hover {
    background: #555;
}
```

### 8.2 Archivo Header

Crea `includes/header.php`:

```php
<header>
    <div class="container">
        <h1>🏠 Sistema de Gestión Inmobiliaria</h1>
        <nav>
            <ul>
                <li><a href="index.php?module=properties">Inmuebles</a></li>
                <li><a href="index.php?module=clients">Clientes</a></li>
                <li><a href="index.php?module=agents">Agentes</a></li>
                <li><a href="index.php?module=sales">Ventas</a></li>
                <li><a href="index.php?module=contracts">Contratos</a></li>
                <li><a href="index.php?module=rentals">Arriendos</a></li>
                <li><a href="index.php?module=visits">Visitas</a></li>
            </ul>
        </nav>
    </div>
</header>

<?php
// Mostrar mensajes del sistema
if (isset($_SESSION['message'])) {
    $message_type = $_SESSION['message_type'] ?? 'info';
    echo "<div class='container'>";
    echo "<div class='$message_type'>" . $_SESSION['message'] . "</div>";
    echo "</div>";

    // Limpiar mensaje después de mostrarlo
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
```

### 8.3 Archivo Footer

Crea `includes/footer.php`:

```php
<footer>
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Sistema de Gestión Inmobiliaria - Proyecto Educativo</p>
    </div>
</footer>
```

---

## ✅ Verificación Final

### Lista de Verificación Completa:

- [ ] ✅ XAMPP/WAMP instalado y funcionando
- [ ] ✅ Apache iniciado (puerto 80 o 8080)
- [ ] ✅ MySQL iniciado
- [ ] ✅ phpMyAdmin accesible
- [ ] ✅ Base de datos `real_estate_db` creada
- [ ] ✅ Esquema de base de datos importado
- [ ] ✅ Estructura de carpetas creada
- [ ] ✅ Archivo `config/database.php` configurado
- [ ] ✅ Archivo `index.php` creado
- [ ] ✅ Archivos de prueba funcionando
- [ ] ✅ Permisos de carpetas configurados
- [ ] ✅ CSS y archivos básicos creados

### Comandos de Verificación Final:

```bash
# Verificar que Apache está corriendo
curl http://localhost

# Verificar acceso al proyecto
curl http://localhost/real-estate-system/test_connection.php

# Verificar conexión a MySQL (desde línea de comandos)
mysql -u root -p -e "SHOW DATABASES;"
```

---

## 🚨 Solución de Problemas Comunes

### Problema: "Access denied for user 'root'"
**Solución:**
```sql
-- En phpMyAdmin o línea de comandos MySQL
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
```

### Problema: "Table doesn't exist"
**Solución:**
1. Ve a phpMyAdmin
2. Verifica que la base de datos `real_estate_db` exista
3. Re-importa el archivo `schema.sql`

### Problema: Apache no inicia
**Solución:**
1. Cambiar puerto en `httpd.conf`
2. Verificar que no haya otro servidor web corriendo
3. Ejecutar como administrador

### Problema: Permisos de archivo
**Solución (Linux/macOS):**
```bash
sudo chmod -R 755 /Applications/XAMPP/htdocs/real-estate-system/
sudo chown -R $(whoami) /Applications/XAMPP/htdocs/real-estate-system/
```

---

## 📚 Recursos Adicionales

- **Documentación oficial de XAMPP**: [https://www.apachefriends.org/docs/](https://www.apachefriends.org/docs/)
- **Manual de PHP**: [https://www.php.net/manual/es/](https://www.php.net/manual/es/)
- **Documentación de MySQL**: [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/)
- **Guía de PDO**: [https://www.php.net/manual/es/book.pdo.php](https://www.php.net/manual/es/book.pdo.php)

---

¡Felicidades! 🎉 Ahora tienes un entorno de desarrollo completo configurado para trabajar con el Sistema de Gestión Inmobiliaria usando PHP y MySQL. Puedes comenzar a desarrollar los módulos del sistema siguiendo las especificaciones del proyecto.