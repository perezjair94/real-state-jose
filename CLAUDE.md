# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Real Estate Management System (Sistema de Gestión Inmobiliaria) - A full-stack PHP/MySQL educational application for managing real estate operations. The system handles properties, clients, agents, sales, contracts, rentals, and property visits with modern design and secure database operations.

**Authentication System**: Dual-role system with admin (full access) and cliente (read-only property viewing) roles. All routes protected with session-based authentication and role-based authorization middleware.

## Architecture

### Current Implementation (PHP/MySQL)
- **Backend**: PHP 8.0+ with PDO for secure database operations
- **Database**: MySQL/MariaDB with normalized schema and foreign key relationships
- **Frontend**: Modern HTML5, CSS3 with Oswald font, vanilla JavaScript
- **Security**: 2024 best practices including PDO prepared statements, CSRF protection, input validation
- **Module Structure**: MVC-inspired modular architecture with separation of concerns

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

### Starting the Server

For XAMPP/WAMP development:
```bash
# Start Apache and MySQL services
# Windows XAMPP: Use XAMPP Control Panel
# Linux/macOS XAMPP:
sudo /opt/lampp/lampp start

# Access the application
# http://localhost/real-state-jose
```

For built-in PHP server (testing only):
```bash
# Start PHP server
php -m http.server 8000

# Or use Python for static files
python3 -m http.server 8000
```

### Database Management

```bash
# Access MySQL via command line
mysql -u root -p real_estate_db

# Import database schema (main tables)
mysql -u root -p real_estate_db < database/schema.sql

# Import authentication system (usuarios table)
mysql -u root -p real_estate_db < database/usuarios_schema.sql

# Import sample data
mysql -u root -p real_estate_db < database/seed.sql

# Access via phpMyAdmin
# http://localhost/phpmyadmin
```

### Authentication Setup

```bash
# Create usuarios table (required for login system)
mysql -u root -p real_estate_db < database/usuarios_schema.sql

# Generate password hash for new users
php generate_password_hash.php
# Then insert the hash into usuarios table

# Test credentials (development only - hashes are valid BCRYPT):
# Admin: admin / admin123 (email: admin@inmobiliaria.com)
# Client: cliente1 / cliente123 (email: cliente1@example.com)
# Los hashes en usuarios_schema.sql son válidos y funcionan inmediatamente
```

### Testing Database Connection

```bash
# Test database connectivity
php test_connection.php

# Debug connection issues
php debug_connection.php
```

## Project Structure

```
real-state-jose/
├── login.php                         # Unified login page (redirects by role)
├── index.php                         # Main entry point - requires auth, role middleware
├── index.html                        # Legacy single-page app (reference only)
├── generate_password_hash.php        # Utility to create password hashes (dev only)
│
├── admin/                            # Admin-only area (requireRole('admin'))
│   ├── dashboard.php                # Admin dashboard with full stats
│   └── logout.php                   # Admin logout handler
│
├── cliente/                          # Client-only area (requireRole('cliente'))
│   ├── dashboard.php                # Client dashboard (limited view)
│   └── logout.php                   # Client logout handler
│
├── config/
│   ├── database.php                 # PDO connection class (2024 security standards)
│   └── constants.php                # Application constants and configuration
│
├── includes/
│   ├── header.php                   # Common header with navigation
│   ├── footer.php                   # Common footer
│   └── functions.php                # Utility functions + auth functions (isLoggedIn, hasRole, etc.)
│
├── modules/                          # Feature modules (dual structure)
│   ├── properties/                  # Property management (English)
│   │   ├── list.php                # Display properties
│   │   ├── create.php              # Add new property (admin only)
│   │   ├── edit.php                # Update property (admin only)
│   │   ├── view.php                # Property details
│   │   └── ajax.php                # AJAX endpoints
│   ├── inmuebles/                   # Property management (Spanish - duplicate)
│   ├── clients/                     # Client management (English - admin only)
│   ├── clientes/                    # Client management (Spanish - admin only)
│   ├── agents/                      # Agents (admin only)
│   ├── sales/                       # Sales (admin only)
│   ├── contracts/                   # Contracts (admin only)
│   ├── rentals/                     # Rentals (admin only)
│   └── visits/                      # Visits (admin only)
│
├── assets/
│   ├── css/
│   │   └── style.css               # Main stylesheet with modern design
│   └── js/
│       └── main.js                 # Client-side JavaScript
│
├── img/                             # Property images
│
├── database/
│   ├── schema.sql                   # Main database structure
│   ├── usuarios_schema.sql          # Authentication tables (usuarios + triggers)
│   └── seed.sql                     # Sample data
│
├── documentation/
│   └── setup.md                     # Detailed setup instructions
│
├── INSTRUCCIONES_LOGIN.md           # Authentication system guide (Spanish)
│
└── PRPs/                            # Project Requirements Planning
    └── real-estate-php-mysql-conversion.md
```

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
- `correo` (UNIQUE), `direccion`, `tipo_cliente`
- Foreign keys: Referenced by venta, contrato, arriendo, visita, usuarios

**inmueble** - Property listings
- `id_inmueble` (PK), `tipo_inmueble`, `direccion`, `ciudad`, `precio`
- `estado`, `descripcion`, `fotos` (JSON), property details
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

## Security Implementation (2024 Standards)

### Authentication & Authorization
- **Password Hashing**: `password_hash()` with BCRYPT (never plain text)
- **Brute Force Protection**: 5 failed attempts = 15 minute account lockout
- **Session-Based Auth**: Secure session with timeout (1 hour default in constants.php)
- **Role-Based Access Control**: Middleware in index.php enforces permissions
  - Admin: Full CRUD access to all modules
  - Cliente: Read-only access to properties module only (list, view actions)
- **Route Protection**: All pages call `requireLogin()` or `requireRole($rol)`
- **Automatic Redirects**: Unauthorized access redirects to appropriate dashboard

### Database Security
- **PDO Prepared Statements**: ALWAYS used, NEVER concatenate SQL
- **Emulation Disabled**: `PDO::ATTR_EMULATE_PREPARES => false`
- **Exception Mode**: Proper error handling with `PDO::ERRMODE_EXCEPTION`

### Input Validation
- **Client-side**: JavaScript validation for UX
- **Server-side**: PHP validation with `filter_var()` and custom rules
- **Sanitization**: `htmlspecialchars()` to prevent XSS attacks

### Session Security
- **CSRF Protection**: Tokens generated and verified for all forms (30min expiry)
- **Session Initialization**: `initSession()` with regeneration and timeout checks
- **Session Variables**: user_id, user_role, username, nombre_completo, email, id_cliente

### Error Handling
- Development: Detailed errors logged to error_log
- Production: User-friendly messages without system details

## Authentication Flow

### Login Process
1. User visits `login.php` (or redirected from protected page)
2. Submits username/email + password
3. `login.php` validates credentials against `usuarios` table
4. On success: Sets session variables, updates `ultimo_acceso`, resets `intentos_login`
5. On failure: Increments `intentos_login`, blocks account after 5 attempts
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
- **Property Cards**: Modern card design with hover effects
- **Forms**: Two-column grid layout with `.form-row` and `.form-group`
- **Tables**: Striped tables with action buttons

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
- Property photos: Store in `img/` directory
- Contract documents: Store in `uploads/contracts/` directory
- Always validate file types and sizes

### Environment Configuration
- Development: Set `ENVIRONMENT = 'development'` in `config/constants.php`
- Debug mode: Enable `DEBUG_MODE` for detailed logging
- Production: Disable debug, use error logging only

### Character Encoding
- All files MUST use UTF-8 encoding
- Database uses `utf8mb4_unicode_ci` collation
- Always use `header('Content-Type: text/html; charset=utf-8')`

## Educational Context

This is an educational project demonstrating professional web development:
- Code includes comments explaining security decisions
- Follows 2024 PHP security best practices
- Suitable for student portfolios
- Real-world business application patterns

## Setup Requirements

See `ENVIRONMENT_SETUP.md` for detailed setup instructions including:
- XAMPP/WAMP installation
- Database configuration
- PHP version requirements (8.0+)
- Extension requirements (PDO, mbstring)
- Permission configuration

## Resources

- **Authentication guide**: `INSTRUCCIONES_LOGIN.md` - Complete setup and usage of dual-role login system
- **Full setup guide**: `ENVIRONMENT_SETUP.md` - XAMPP/WAMP installation and configuration
- **Database schema**: `database/schema.sql` - Main business tables
- **Authentication schema**: `database/usuarios_schema.sql` - User authentication tables
- **Project requirements**: `PRPs/real-estate-php-mysql-conversion.md`
- **Testing utilities**: `test_connection.php`, `debug_connection.php`
- **Password tool**: `generate_password_hash.php` - Generate BCRYPT hashes (dev only, remove in production)
