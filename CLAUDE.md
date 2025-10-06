# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Real Estate Management System (Sistema de Gestión Inmobiliaria) - A full-stack PHP/MySQL educational application for managing real estate operations. The system handles properties, clients, agents, sales, contracts, rentals, and property visits with modern design and secure database operations.

## Architecture

### Current Implementation (PHP/MySQL)
- **Backend**: PHP 8.0+ with PDO for secure database operations
- **Database**: MySQL/MariaDB with normalized schema and foreign key relationships
- **Frontend**: Modern HTML5, CSS3 with Oswald font, vanilla JavaScript
- **Security**: 2024 best practices including PDO prepared statements, CSRF protection, input validation
- **Module Structure**: MVC-inspired modular architecture with separation of concerns

### Key Technologies
- **PHP PDO**: All database operations use prepared statements (NO direct queries)
- **UTF-8 (utf8mb4)**: Full Unicode support including emojis
- **Session Management**: Secure session handling with CSRF tokens
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

# Import database schema
mysql -u root -p real_estate_db < database/schema.sql

# Import sample data
mysql -u root -p real_estate_db < database/seed.sql

# Access via phpMyAdmin
# http://localhost/phpmyadmin
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
├── index.php                  # Main entry point with routing
├── index.html                 # Legacy single-page app (reference only)
├── config/
│   ├── database.php          # PDO connection class (2024 security standards)
│   └── constants.php         # Application constants and configuration
├── includes/
│   ├── header.php            # Common header with navigation
│   ├── footer.php            # Common footer
│   └── functions.php         # Utility functions and helpers
├── modules/                   # Feature modules (dual structure)
│   ├── properties/           # Property management (English)
│   │   ├── list.php         # Display properties
│   │   ├── create.php       # Add new property
│   │   ├── edit.php         # Update property
│   │   ├── view.php         # Property details
│   │   └── ajax.php         # AJAX endpoints
│   ├── inmuebles/           # Property management (Spanish - duplicate)
│   ├── clients/             # Client management (English)
│   ├── clientes/            # Client management (Spanish - duplicate)
│   └── [similar pattern for other modules]
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet with modern design
│   └── js/
│       └── main.js          # Client-side JavaScript
├── img/                     # Property images
│   ├── casa1.jpeg
│   ├── casa2.jpg
│   └── casa3.jpeg
├── database/
│   ├── schema.sql           # Database structure
│   └── seed.sql             # Sample data
├── documentation/
│   └── setup.md             # Detailed setup instructions
└── PRPs/                    # Project Requirements Planning
    └── real-estate-php-mysql-conversion.md
```

## Database Schema

### Core Tables

**cliente** - Customer information
- `id_cliente` (PK), `nombre`, `apellido`, `tipo_documento`, `nro_documento` (UNIQUE)
- `correo` (UNIQUE), `direccion`, `tipo_cliente`
- Foreign keys: Referenced by venta, contrato, arriendo, visita

**inmueble** - Property listings
- `id_inmueble` (PK), `tipo_inmueble`, `direccion`, `ciudad`, `precio`
- `estado`, `descripcion`, `fotos` (JSON), property details
- Foreign keys: Referenced by venta, contrato, arriendo, visita

**agente** - Real estate agents
- `id_agente` (PK), `nombre`, `correo` (UNIQUE), `telefono`, `asesor`
- Foreign keys: Referenced by venta, contrato, visita

**venta** - Sales transactions
- Foreign keys: `id_inmueble`, `id_cliente`, `id_agente`

**contrato** - Legal contracts
- Foreign keys: `id_inmueble`, `id_cliente`, `id_agente`
- File handling: `archivo_contrato` field

**arriendo** - Rental agreements
- Foreign keys: `id_inmueble`, `id_cliente`

**visita** - Property visits
- Foreign keys: `id_inmueble`, `id_cliente`, `id_agente`

### Important Relationships
- All foreign keys use `ON DELETE RESTRICT` or `ON DELETE SET NULL` for data integrity
- Character set: `utf8mb4_unicode_ci` for full UTF-8 support
- Timestamps: `created_at`, `updated_at` for audit trails

## Security Implementation (2024 Standards)

### Database Security
- **PDO Prepared Statements**: ALWAYS used, NEVER concatenate SQL
- **Emulation Disabled**: `PDO::ATTR_EMULATE_PREPARES => false`
- **Exception Mode**: Proper error handling with `PDO::ERRMODE_EXCEPTION`

### Input Validation
- **Client-side**: JavaScript validation for UX
- **Server-side**: PHP validation with `filter_var()` and custom rules
- **Sanitization**: `htmlspecialchars()` to prevent XSS attacks

### Session Security
- **CSRF Protection**: Tokens generated and verified for all forms
- **Session Initialization**: `initSession()` with secure settings

### Error Handling
- Development: Detailed errors logged to error_log
- Production: User-friendly messages without system details

## Module Pattern

Each module follows this structure:

```php
modules/{module}/
├── list.php     # Display records in table/card format
├── create.php   # Form for adding new records
├── edit.php     # Form for updating existing records
├── view.php     # Detailed view of single record
└── ajax.php     # AJAX endpoints for CRUD operations
```

### Module Access Pattern
```php
// URL structure
index.php?module=properties&action=list
index.php?module=clients&action=create
index.php?module=sales&action=edit&id=5

// Validation in index.php ensures only allowed modules/actions
```

## Key Functions (includes/functions.php)

### Security Functions
- `initSession()` - Secure session initialization
- `generateCSRFToken()` - Create CSRF tokens
- `verifyCSRFToken($token)` - Validate CSRF tokens
- `sanitizeInput($data)` - Clean user input

### Navigation Functions
- `redirectWithMessage($url, $message, $type)` - Redirect with flash message
- `displayFlashMessage()` - Show session flash messages

### Validation Functions
- `validateEmail($email)` - Email validation
- `validateRequired($value)` - Required field check
- `validateNumeric($value)` - Numeric validation

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

### Adding a New Module

1. Create module directory: `modules/new_module/`
2. Create required files: `list.php`, `create.php`, `edit.php`, `view.php`, `ajax.php`
3. Add to `config/constants.php`: Update `AVAILABLE_MODULES` array
4. Add navigation link in `includes/header.php`
5. Create corresponding database table if needed

### Adding a New Property

Properties require images. Store in `img/` directory and reference in forms.

### Database Migrations

When modifying schema:
1. Create migration file in `database/migrations/`
2. Test on development database first
3. Document changes in migration file
4. Update `schema.sql` with new structure

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

- Full setup guide: `ENVIRONMENT_SETUP.md`
- Database schema: `database/schema.sql`
- Project requirements: `PRPs/real-estate-php-mysql-conversion.md`
- Testing utilities: `test_connection.php`, `debug_connection.php`
