# Real Estate Management System - Setup Guide

## Educational PHP/MySQL Project

This is a comprehensive Real Estate Management System built as an educational project to demonstrate modern PHP/MySQL development practices while maintaining code simplicity for learning purposes.

## Features

### ✅ Completed Modules
- **Properties Management** (Inmuebles) - Complete CRUD operations
- **Clients Management** (Clientes) - Client database management
- **Database Schema** - Normalized MySQL structure with relationships
- **Security Framework** - PDO prepared statements, input validation
- **Responsive Design** - Mobile-first CSS with professional styling
- **AJAX Functionality** - Enhanced user experience with asynchronous operations

### 🚧 Additional Modules (Ready for Extension)
- Agents Management (Agentes)
- Sales Tracking (Ventas)
- Contract Management (Contratos)
- Rental Management (Arriendos)
- Visit Scheduling (Visitas)

## Installation Requirements

### Server Requirements
- **PHP 7.4+** (Recommended: PHP 8.0+)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache** or **Nginx** web server
- **XAMPP**, **WAMP**, or **MAMP** for local development

### Browser Requirements
- Modern web browser with JavaScript enabled
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

## Quick Setup (XAMPP/WAMP)

### Step 1: Download and Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP following the installation wizard
3. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Setup the Project
1. Copy the project folder to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\real-estate-system\
   ```

2. Create the database:
   - Open phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Run the SQL script from `database/schema.sql`
   - Optionally run `database/seed.sql` for sample data

### Step 3: Configure Database Connection
1. Open `config/database.php`
2. Verify the database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'real_estate_db';
   private $username = 'root';
   private $password = '';
   ```

### Step 4: Set Permissions
1. Ensure the `assets/uploads/` directory is writable:
   ```bash
   chmod 755 assets/uploads/
   chmod 755 assets/uploads/properties/
   chmod 755 assets/uploads/contracts/
   ```

### Step 5: Access the Application
1. Open your web browser
2. Navigate to: [http://localhost/real-estate-system](http://localhost/real-estate-system)
3. You should see the Real Estate Management System dashboard

## Database Schema

The system uses a normalized MySQL database with the following entities:

### Core Tables
- **cliente** - Customer information (buyers, sellers, tenants, landlords)
- **inmueble** - Property listings with details and photos
- **agente** - Real estate agents and representatives
- **venta** - Completed property sales transactions
- **contrato** - Legal contracts for sales and rentals
- **arriendo** - Active rental agreements
- **visita** - Scheduled property visits

### Relationships
- Properties can have multiple: sales, contracts, rentals, visits
- Clients can participate in multiple: sales, contracts, rentals, visits
- Agents can manage multiple visits and transactions

## Security Features

### 2024 Security Best Practices
- **PDO Prepared Statements** - Prevents SQL injection attacks
- **Input Validation** - Client-side and server-side validation
- **CSRF Protection** - Cross-Site Request Forgery prevention
- **XSS Prevention** - Input sanitization and output encoding
- **Session Security** - Secure session management
- **File Upload Security** - Type and size validation

### Input Validation
```php
// Server-side validation example
$validator = new Validator();
$rules = [
    'email' => ['required', 'email'],
    'price' => ['required', 'numeric', 'min_value:1'],
    'description' => ['max_length:1000']
];
```

### Database Queries
```php
// Secure prepared statement example
$sql = "SELECT * FROM inmueble WHERE id_inmueble = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$propertyId]);
```

## File Structure

```
real-estate-system/
├── index.php              # Main application entry point
├── config/                 # Configuration files
│   ├── database.php       # Database connection
│   └── constants.php      # Application constants
├── includes/              # Common includes
│   ├── header.php         # Page header
│   ├── footer.php         # Page footer
│   └── functions.php      # Utility functions
├── modules/               # Feature modules
│   ├── properties/        # Property management
│   └── clients/           # Client management
├── assets/                # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── uploads/           # File uploads
├── database/              # Database files
│   ├── schema.sql         # Database structure
│   └── seed.sql           # Sample data
└── documentation/         # Project documentation
```

## Educational Notes

### Learning Objectives
This project demonstrates:
1. **Full-Stack Development** - PHP backend with MySQL database
2. **Security Practices** - Modern security implementation
3. **Responsive Design** - Mobile-first CSS approach
4. **AJAX Implementation** - Asynchronous user interactions
5. **Database Design** - Normalized relational structure
6. **Form Validation** - Dual client/server validation
7. **File Management** - Secure file upload handling

### Code Quality
- **PSR Standards** - Following PHP coding standards
- **Documentation** - Comprehensive inline comments
- **Error Handling** - Proper exception management
- **Separation of Concerns** - Modular architecture
- **Accessibility** - WCAG compliant interface elements

## Troubleshooting

### Common Issues

#### Database Connection Error
```
Error: Connection failed
```
**Solution**:
1. Verify MySQL is running in XAMPP
2. Check database credentials in `config/database.php`
3. Ensure database exists and is accessible

#### File Upload Issues
```
Error: Failed to upload file
```
**Solution**:
1. Check directory permissions
2. Verify file size limits in PHP configuration
3. Ensure upload directory exists

#### JavaScript Errors
```
Error: App is not defined
```
**Solution**:
1. Ensure all JavaScript files are loaded
2. Check browser console for specific errors
3. Verify file paths are correct

### PHP Configuration
Recommended PHP settings for development:
```ini
upload_max_filesize = 5M
post_max_size = 5M
memory_limit = 128M
max_execution_time = 30
```

## Development Workflow

### Adding New Features
1. Create module directory in `modules/`
2. Implement CRUD operations (create, read, update, delete)
3. Add validation rules
4. Create AJAX handlers
5. Update navigation in `includes/header.php`

### Database Changes
1. Create migration file in `database/migrations/`
2. Update schema documentation
3. Test with sample data

### Testing
1. Test all CRUD operations
2. Verify security measures
3. Check responsive design
4. Validate cross-browser compatibility

## Production Deployment

### Security Checklist
- [ ] Change default database passwords
- [ ] Set `ENVIRONMENT` to 'production' in constants
- [ ] Disable debug mode
- [ ] Configure HTTPS
- [ ] Set up regular backups
- [ ] Update file permissions
- [ ] Configure error logging

### Performance Optimization
- [ ] Enable PHP OPcache
- [ ] Optimize database queries
- [ ] Compress static assets
- [ ] Configure browser caching
- [ ] Set up CDN for uploads

## Support

### Educational Resources
- **PHP Manual**: [https://www.php.net/manual/](https://www.php.net/manual/)
- **MySQL Documentation**: [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/)
- **Web Security**: [https://owasp.org/](https://owasp.org/)

### Project Structure
This project follows educational best practices:
- Clear separation of concerns
- Comprehensive commenting
- Real-world security implementation
- Professional coding standards
- Scalable architecture

### Next Steps
1. Complete remaining modules (Agents, Sales, Contracts, Rentals, Visits)
2. Add user authentication system
3. Implement advanced reporting features
4. Add API endpoints for mobile integration
5. Create automated testing suite

---

**Educational Project** | **PHP/MySQL Full-Stack Development** | **2024 Security Standards**