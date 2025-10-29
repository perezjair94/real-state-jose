# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Real Estate Management System (Sistema de Gestión Inmobiliaria) - A full-stack PHP/MySQL educational application for managing real estate operations. The system handles properties, clients, agents, sales, contracts, rentals, and property visits with modern design and secure database operations.

**Authentication System**: Dual-role system with admin (full access) and cliente (read-only property viewing) roles. All routes protected with session-based authentication and role-based authorization middleware.

## Architecture

### Current Implementation (PHP/MySQL)
- **Backend**: PHP 8.2 (Docker) or 8.0+ (XAMPP/WAMP) with PDO for secure database operations
- **Database**: MySQL 8.0 (Docker) or MariaDB with normalized schema and foreign key relationships
- **Frontend**: Modern HTML5, CSS3 with Oswald font, vanilla JavaScript
- **Security**: 2024 best practices including PDO prepared statements, CSRF protection, input validation
- **Module Structure**: MVC-inspired modular architecture with separation of concerns
- **Deployment**: Docker containerized (recommended) or traditional XAMPP/WAMP setup

### Application Entry Points
- **`login.php`** - Unauthenticated entry point, validates credentials, redirects by role
- **`index.php`** - Main router for modules, requires authentication, has role middleware
- **`admin/dashboard.php`** - Admin landing page, requires admin role
- **`cliente/dashboard.php`** - Cliente landing page, requires cliente role
- **`modules/{module}/{action}.php`** - Business logic pages, authorization enforced by index.php middleware

### Key Technologies
- **PHP PDO**: All database operations use prepared statements (NO direct queries)
- **UTF-8 (utf8mb4)**: Full Unicode support including emojis
- **Session Management**: Secure session handling with CSRF tokens and role-based access control
- **Authentication**: Password hashing with `password_hash()`, brute-force protection (5 attempts = 15min lockout)
- **AJAX**: Asynchronous operations for smooth user experience
- **Responsive Design**: Mobile-first CSS Grid/Flexbox layout

## Development Commands

### Docker Development (Recommended)

```bash
# Quick start (3 commands)
cd /home/cyb3r0801/projects/real-state-jose
./docker-helpers.sh start          # Start all services with initialization
# Wait 30 seconds for database to initialize

# Access application
# http://localhost:8080           # Main application
# http://localhost:8081           # phpMyAdmin
# Credentials: admin / admin123   # Default test user
```

**Helper Script Commands:**
```bash
./docker-helpers.sh start         # Start containers with database initialization
./docker-helpers.sh stop          # Stop all containers
./docker-helpers.sh restart       # Restart services
./docker-helpers.sh logs          # View real-time logs
./docker-helpers.sh status        # Show container status
./docker-helpers.sh shell-web     # SSH into web container (PHP console)
./docker-helpers.sh shell-db      # SSH into database container (MySQL CLI)
./docker-helpers.sh backup-db     # Backup database to SQL file
./docker-helpers.sh clean         # Remove all containers/volumes (resets everything)
```

**Docker Services:**
- **web** (PHP 8.2 + Apache 2.4) - Port 8080:80
- **database** (MySQL 8.0) - Port 3306:3306
- **phpmyadmin** - Port 8081:80

**Auto-initialized with:**
- Complete schema (schema.sql)
- Authentication system (usuarios_schema.sql with triggers)
- Sample data (seed.sql)
- Volumes for live code sync and persistent storage

### Traditional XAMPP/WAMP Development

```bash
# Start services
# Windows: Use XAMPP Control Panel to start Apache + MySQL
# Linux/macOS: sudo /opt/lampp/lampp start

# Access application
# http://localhost/real-state-jose
```

### Database Management

```bash
# Via Docker (when running)
./docker-helpers.sh shell-db      # Enter MySQL CLI

# Via command line (XAMPP/WAMP)
mysql -u root -p real_estate_db

# Import schemas manually
mysql -u root -p real_estate_db < database/schema.sql
mysql -u root -p real_estate_db < database/usuarios_schema.sql
mysql -u root -p real_estate_db < database/seed.sql
```

### Database Connection Testing

```bash
# Via Docker
./docker-helpers.sh shell-web
php test_connection.php
php debug_connection.php

# Via XAMPP/WAMP (in project root)
php test_connection.php
php debug_connection.php
```

### Authentication Setup

```bash
# Generate password hash for new users
php generate_password_hash.php
# Then insert the hash into usuarios table

# Default test credentials (pre-hashed in database)
# Admin: admin / admin123 (email: admin@inmobiliaria.com)
# Client: cliente1 / cliente123 (email: cliente1@example.com)
```

## Project Structure

```
real-state-jose/
├── Core Application
│   ├── login.php                         # Unified login page (redirects by role)
│   ├── index.php                         # Main entry point - requires auth, role middleware
│   ├── index.html                        # Legacy single-page app (reference only)
│   ├── health.php                        # Docker health check
│   ├── diagnose.php                      # Diagnostic utilities
│   └── generate_password_hash.php        # Password hash utility (dev only)
│
├── config/
│   ├── constants.php                     # Central configuration hub
│   ├── database.php                      # PDO connection class with environment support
│   ├── apache.conf                       # Apache configuration for Docker
│   └── php.ini                           # PHP settings
│
├── includes/
│   ├── functions.php                     # Utility functions + auth (732 lines)
│   ├── header.php                        # Navigation header
│   └── footer.php                        # Common footer
│
├── modules/                              # Feature modules (7 modules, 35 files, 4,335 lines)
│   ├── properties/                       # Property management (primary module)
│   │   ├── list.php                     # Display with carousel slider
│   │   ├── create.php                   # Add new property
│   │   ├── edit.php                     # Update property
│   │   ├── view.php                     # Property details
│   │   └── ajax.php                     # AJAX endpoints
│   ├── inmuebles/                        # Spanish version (duplicate)
│   ├── clients/                          # Client management (admin only)
│   ├── clientes/                         # Spanish version (admin only)
│   ├── agents/                           # Agent management
│   ├── sales/                            # Sales transactions
│   ├── contracts/                        # Contract management
│   ├── rentals/                          # Rental agreements
│   └── visits/                           # Property visit scheduling
│
├── admin/                                # Admin-only area
│   ├── dashboard.php                    # Admin landing page (full stats)
│   └── logout.php                       # Admin logout
│
├── cliente/                              # Client-only area
│   ├── dashboard.php                    # Client landing (limited view)
│   └── logout.php                       # Client logout
│
├── assets/
│   ├── css/style.css                    # Main stylesheet (responsive, Oswald font)
│   ├── js/
│   │   ├── app.js                       # Main application script
│   │   ├── ajax.js                      # AJAX utilities
│   │   └── validation.js                # Client-side validation
│   └── uploads/                         # User-uploaded files (contracts, images)
│
├── img/                                  # Property images
│
├── database/
│   ├── schema.sql                       # Main business tables schema
│   ├── usuarios_schema.sql              # Authentication tables + triggers
│   ├── seed.sql                         # Sample data
│   └── migrations/                      # Schema version control
│       ├── 000_initial_schema.sql
│       └── 001_remove_reservado_status.sql
│
├── Docker Configuration
│   ├── Dockerfile                       # PHP 8.2 + Apache multi-stage
│   ├── docker-compose.yml               # 3-service orchestration
│   ├── docker-entrypoint.sh             # Container initialization
│   └── docker-helpers.sh                # Helper commands
│
├── Documentation
│   ├── CLAUDE.md                        # This file - AI guidance
│   ├── QUICK_START.md                   # Fast Docker setup
│   ├── DOCKER_SETUP.md                  # Detailed Docker guide
│   ├── DOCKER_RUNNING.md                # Docker operations
│   ├── ENVIRONMENT_SETUP.md             # XAMPP/WAMP setup (Spanish)
│   ├── INSTRUCCIONES_LOGIN.md           # Authentication guide
│   ├── TODO.md                          # Development roadmap
│   └── propuesta-mejoramiento.md        # Improvement proposals
│
├── Development Tools
│   ├── test_connection.php              # Database connectivity test
│   ├── debug_connection.php             # Detailed diagnostics
│   ├── debug_base_url.php               # URL configuration debug
│   ├── fix_encoding.php                 # UTF-8 encoding fixes
│   └── diagnostico_imagenes.php         # Image handling diagnostics
│
├── .claude/
│   └── commands/                        # Claude Code CLI commands
│       ├── primer.md
│       ├── generate-prp.md
│       ├── execute-prp.md
│       ├── fix-github-issue.md
│       └── analyze-performance.md
│
└── Configuration Files
    ├── .env.example                     # Environment template
    ├── .gitignore
    ├── docker-compose.yml
    └── .git/
```

## Architecture & Design Patterns

### Router Pattern (index.php)
- Central entry point for authenticated users
- Module/action routing via `?module={module}&action={action}&id={id}`
- Role-based authorization middleware
- AJAX detection for JSON responses
- Whitelist validation of modules and actions

### Module Structure
- Consistent 5-file pattern per module: list, create, edit, view, ajax
- Each module handles own business logic independently
- AJAX endpoints return JSON for frontend consumption
- Admin-only modules blocked by `index.php` middleware for clientes

### Database Layer
- PDO with prepared statements (ALWAYS, NEVER direct SQL)
- Environment-variable based configuration
- Connection pooling and timeout support
- Transaction support for multi-step operations

### Authentication & Authorization
- Session-based with timeout enforcement
- Two roles: `admin` (full), `cliente` (properties read-only)
- Brute-force protection: 5 attempts = 15min lockout
- Password hashing with BCRYPT via `password_hash()`
- Role checks at router level prevent unauthorized access

### Security Implementation (2024 Standards)

#### Authentication & Authorization
- **Password Hashing**: `password_hash()` with BCRYPT (never plain text)
- **Brute Force Protection**: 5 failed attempts = 15 minute account lockout
- **Session-Based Auth**: Secure session with timeout (1 hour default)
- **Role-Based Access Control**: Middleware in index.php enforces permissions
  - Admin: Full CRUD access to all modules
  - Cliente: Read-only access to properties module only (list, view actions)
- **Route Protection**: All pages call `requireLogin()` or `requireRole($rol)`
- **Automatic Redirects**: Unauthorized access redirects to appropriate dashboard

#### Database Security
- **PDO Prepared Statements**: ALWAYS used, NEVER concatenate SQL
- **Emulation Disabled**: `PDO::ATTR_EMULATE_PREPARES => false`
- **Exception Mode**: Proper error handling with `PDO::ERRMODE_EXCEPTION`

#### Input Validation
- **Client-side**: JavaScript validation for UX
- **Server-side**: PHP validation with `filter_var()` and custom rules
- **Sanitization**: `htmlspecialchars()` to prevent XSS attacks

#### Session Security
- **CSRF Protection**: Tokens generated and verified for all forms (30min expiry)
- **Session Initialization**: `initSession()` with regeneration and timeout checks
- **Session Variables**: user_id, user_role, username, nombre_completo, email, id_cliente

#### Error Handling
- Development: Detailed errors logged to error_log
- Production: User-friendly messages without system details

## Database Schema

### Authentication Tables

**usuarios** - System users with role-based access
- `id_usuario` (PK), `username` (UNIQUE), `password_hash`, `email` (UNIQUE)
- `rol` ENUM('admin', 'cliente') - Determines access level
- `id_cliente` (FK to cliente) - Optional link to cliente record for cliente role
- `activo` BOOLEAN - Enable/disable account
- `intentos_login` INT - Failed login counter (resets on success)
- `bloqueado_hasta` DATETIME - Temporary lockout (15min after 5 failed attempts)
- `ultimo_acceso` DATETIME - Last successful login timestamp
- Indexes: username, email, rol, activo

**vista_usuarios** - View for user info without password_hash

**Trigger: tr_usuario_vincular_cliente** - Auto-links usuarios to cliente by matching email

### Core Business Tables

**cliente** - Customer information
- `id_cliente` (PK), `nombre`, `apellido`, `tipo_documento`, `nro_documento` (UNIQUE)
- `correo` (UNIQUE), `direccion`, `tipo_cliente` (Comprador, Vendedor, Arrendatario, Arrendador)
- Foreign keys: Referenced by venta, contrato, arriendo, visita, usuarios

**inmueble** - Property listings
- `id_inmueble` (PK), `tipo_inmueble`, `direccion`, `ciudad`, `precio`
- `estado` (Disponible, Vendido, Arrendado), `descripcion`, `fotos` (JSON array of filenames)
- `area_construida`, `area_lote`, `habitaciones`, `banos`, `garaje`
- Foreign keys: Referenced by venta, contrato, arriendo, visita

**agente** - Real estate agents
- `id_agente` (PK), `nombre`, `correo` (UNIQUE), `telefono`, `asesor`
- Foreign keys: Referenced by venta, contrato, visita

**venta** - Sales transactions
- Foreign keys: `id_inmueble`, `id_cliente`, `id_agente`
- Trigger: Updates inmueble.estado to 'Vendido' on insert

**contrato** - Legal contracts
- Foreign keys: `id_inmueble`, `id_cliente`, `id_agente`
- File handling: `archivo_contrato` field

**arriendo** - Rental agreements
- Foreign keys: `id_inmueble`, `id_cliente`
- Trigger: Updates inmueble.estado to 'Arrendado' on insert

**visita** - Property visits
- Foreign keys: `id_inmueble`, `id_cliente`, `id_agente`

### Important Relationships
- All foreign keys use `ON DELETE RESTRICT` or `ON DELETE SET NULL` for data integrity
- Character set: `utf8mb4_unicode_ci` for full UTF-8 support
- Timestamps: `created_at`, `updated_at` for audit trails
- Triggers automatically update inmueble status on venta/arriendo insert

## Authentication Flow

### Login Process
1. User visits `login.php` (or redirected from protected page)
2. Submits username/email + password
3. `login.php` validates credentials against `usuarios` table
4. On success: Sets session variables, updates `ultimo_acceso`, resets `intentos_login`
5. On failure: Increments `intentos_login`, blocks account after 5 attempts (15 min)
6. Redirects based on `rol`:
   - `admin` → `admin/dashboard.php`
   - `cliente` → `cliente/dashboard.php`

### Route Protection in index.php
```php
// 1. Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 2. Auto-redirect to dashboard if no module specified
if (!isset($_GET['module'])) {
    if (hasRole('admin')) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: cliente/dashboard.php');
    }
    exit;
}

// 3. Role-based authorization middleware
if (hasRole('cliente')) {
    // Cliente can only access properties module
    if ($module !== 'properties') {
        redirectWithMessage('cliente/dashboard.php', 'No tienes permisos...', 'error');
    }
    // Cliente can only list and view (no create/edit/delete)
    if (!in_array($action, ['list', 'view'])) {
        redirectWithMessage("?module={$module}&action=list", 'No tienes permisos...', 'error');
    }
}
// Admin has no restrictions
```

### Dashboard-Specific Protection
```php
// In admin/dashboard.php
requireRole('admin');  // Redirects cliente users to cliente/dashboard.php

// In cliente/dashboard.php
requireRole('cliente');  // Redirects admin users to admin/dashboard.php
```

## Module Pattern

Each module follows this structure:

```php
modules/{module}/
├── list.php     # Display records in table/card format
├── create.php   # Form for adding new records (admin only for most modules)
├── edit.php     # Form for updating existing records (admin only)
├── view.php     # Detailed view of single record
└── ajax.php     # AJAX endpoints for CRUD operations
```

### Module Access Pattern
```php
// URL structure
index.php?module=properties&action=list      // Cliente can access
index.php?module=properties&action=view&id=5 // Cliente can access
index.php?module=clients&action=create       // Admin only - cliente blocked by middleware
index.php?module=sales&action=edit&id=5      // Admin only - cliente blocked by middleware

// Validation in index.php ensures only allowed modules/actions
```

## Key Functions (includes/functions.php)

### Authentication Functions
- `isLoggedIn()` - Check if user has active session
- `hasRole($role)` - Check if user has specific role ('admin' or 'cliente')
- `requireLogin()` - Enforce authentication (redirect to login.php if not logged in)
- `requireRole($requiredRole)` - Enforce role requirement (redirect to appropriate dashboard if wrong role)
- `getCurrentUser()` - Get current user info array (id, username, nombre_completo, email, rol, id_cliente)
- `logout()` - Destroy session and redirect to login page

### Security Functions
- `initSession()` - Secure session initialization with regeneration and timeout
- `generateCSRFToken()` - Create CSRF tokens
- `verifyCSRFToken($token)` - Validate CSRF tokens
- `hashPassword($password)` - Wrapper for password_hash()
- `verifyPassword($password, $hash)` - Wrapper for password_verify()
- `sanitizeInput($data)` - Clean user input (XSS prevention)

### Navigation Functions
- `redirectWithMessage($url, $message, $type)` - Redirect with flash message
- `displayFlashMessage()` - Show session flash messages

### Validation Functions
- `validateEmail($email)` - Email validation
- `validateRequired($value)` - Required field check
- `validateNumeric($value)` - Numeric validation

### Database Helper Functions
- `executeQuery($sql, $params)` - Execute prepared statement with error handling
- `getRecord($table, $idField, $idValue)` - Fetch single record
- `insertRecord($table, $data)` - Insert with prepared statement
- `updateRecord($table, $data, $idField, $idValue)` - Update with prepared statement
- `deleteRecord($table, $idField, $idValue)` - Delete with prepared statement

### Logging Functions
- `logMessage($message, $level)` - Application logging

## Design System

### Color Scheme
- **Primary**: `#0a1931` (Navy blue - header/navbar)
- **Secondary**: `#00de55` (Green - buttons, active states)
- **Accent**: `#00aa41` (Dark green - logo text)
- **Sale Tag**: `#e94545` (Red)
- **Rental Tag**: `#5cb85c` (Light green)

### Typography
- **Font Family**: 'Oswald' (Google Fonts)
- **Weights**: 400 (regular), 600 (semi-bold), 700 (bold)

### Layout Components
- **Navbar**: Fixed navigation with logo and module links
- **Property Cards**: Modern card design with carousel slider
- **Forms**: Two-column grid layout with `.form-row` and `.form-group`
- **Tables**: Striped tables with action buttons
- **Responsive**: Mobile-first CSS Grid/Flexbox layout

## Common Development Tasks

### Creating a New User

```bash
# 1. Generate password hash
php generate_password_hash.php
# Enter password, copy the hash output

# 2. Insert into database (via phpMyAdmin or mysql CLI)
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
VALUES ('new_user', 'PASTE_HASH_HERE', 'user@example.com', 'Full Name', 'admin', TRUE);

# For cliente role with linked cliente record:
INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, id_cliente, activo)
VALUES ('new_client', 'HASH', 'client@example.com', 'Client Name', 'cliente', 123, TRUE);
```

### Protecting a New Page

```php
// At the top of any new PHP page:
define('APP_ACCESS', true);
require_once '../config/constants.php';
require_once '../includes/functions.php';

initSession();
requireLogin();  // Redirect to login if not authenticated

// For role-specific pages:
requireRole('admin');  // Only allow admin role
// OR
requireRole('cliente');  // Only allow cliente role

// For conditional content by role:
if (hasRole('admin')) {
    // Show admin-only content
}
```

### Adding a New Module

1. Create module directory: `modules/new_module/`
2. Create required files: `list.php`, `create.php`, `edit.php`, `view.php`, `ajax.php`
3. Add to `config/constants.php`: Update `AVAILABLE_MODULES` array
4. Add navigation link in `includes/header.php`
5. **Update authorization in `index.php`**: If cliente should NOT access, it's blocked by default (only properties allowed)
6. Create corresponding database table if needed

### Modifying Role Permissions

To allow cliente access to additional modules, edit `index.php`:

```php
// Current: Only properties module
if ($module !== 'properties') {
    redirectWithMessage(...);
}

// To allow properties AND visits:
if (!in_array($module, ['properties', 'visits'])) {
    redirectWithMessage(...);
}
```

To allow cliente to create/edit (not just view):

```php
// Current: Only list and view
if (!in_array($action, ['list', 'view'])) {
    redirectWithMessage(...);
}

// To add create:
if (!in_array($action, ['list', 'view', 'create'])) {
    redirectWithMessage(...);
}
```

### Database Migrations

When modifying schema:
1. Create migration file in `database/migrations/`
2. Test on development database first
3. Document changes in migration file
4. Update `schema.sql` or `usuarios_schema.sql` with new structure

### Form CSRF Protection

All forms MUST include CSRF token:
```php
<input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
```

All POST handlers MUST verify token:
```php
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWithMessage($url, 'Token inválido', 'error');
}
```

### Prepared Statement Pattern

ALWAYS use prepared statements:
```php
// CORRECT
$stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
$stmt->execute([$id]);

// WRONG - NEVER DO THIS
$result = $pdo->query("SELECT * FROM cliente WHERE id_cliente = $id");
```

### Unlocking a Blocked User Account

```sql
-- Reset login attempts and remove lockout
UPDATE usuarios
SET intentos_login = 0, bloqueado_hasta = NULL
WHERE username = 'username_here';
```

## Important Notes

### Duplicate Module Structure
- The project has BOTH English and Spanish module names (`properties`/`inmuebles`, `clients`/`clientes`, etc.)
- This is intentional for internationalization flexibility
- When working with modules, be aware both versions may exist

### Legacy HTML File
- `index.html` contains the original single-page application
- Keep as reference for design patterns but don't modify
- Active development uses PHP files

### File Uploads
- Property photos: Store in `assets/uploads/properties/`
- Contract documents: Store in `assets/uploads/contracts/`
- Always validate file types and sizes
- JSON storage: `inmueble.fotos` stores filenames as JSON array

### Environment Configuration
- Development: Set `ENVIRONMENT = 'development'` in `config/constants.php`
- Debug mode: Enable `DEBUG_MODE` for detailed logging
- Production: Disable debug, use error logging only
- Docker: Environment variables via docker-compose.yml

### Character Encoding
- All files MUST use UTF-8 encoding
- Database uses `utf8mb4_unicode_ci` collation
- Always use: `header('Content-Type: text/html; charset=utf-8')`

### Image Handling
- Property images stored in `assets/uploads/properties/`
- Apache configured to serve uploads directly
- URL paths use UPLOADS_URL constant (set in constants.php)
- Image detection: Check for 'img/' or 'casa' in filename for default images
- Carousel slider implemented with transform translateX for smooth navigation

## Educational Context

This is an educational project demonstrating professional web development:
- Code includes comments explaining security decisions
- Follows 2024 PHP security best practices
- Suitable for student portfolios
- Real-world business application patterns
- Complete implementation of 7 feature modules with 35+ PHP files

## Resources

- **Quick Start**: `QUICK_START.md` - Fast Docker setup (3 commands)
- **Docker Guide**: `DOCKER_SETUP.md` - Detailed Docker configuration
- **Docker Operations**: `DOCKER_RUNNING.md` - Docker troubleshooting
- **Authentication guide**: `INSTRUCCIONES_LOGIN.md` - Complete authentication system
- **XAMPP/WAMP Setup**: `ENVIRONMENT_SETUP.md` - Traditional development setup (Spanish)
- **Database schema**: `database/schema.sql` - Main business tables
- **Authentication schema**: `database/usuarios_schema.sql` - User authentication tables
- **Project requirements**: `PRPs/real-estate-php-mysql-conversion.md`
- **Testing utilities**: `test_connection.php`, `debug_connection.php`
- **Password tool**: `generate_password_hash.php` - Generate BCRYPT hashes (dev only)
