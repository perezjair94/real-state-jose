# Database Documentation

Real Estate Management System - Database structure and migration system.

## Quick Start

### Fresh Installation

For a completely new database:

```bash
# Option 1: Using the migration system (RECOMMENDED)
php run_migration.php 000_initial_schema.sql

# Option 2: Using MySQL command line
mysql -u root -p < database/migrations/000_initial_schema.sql

# Option 3: Using the master schema file (legacy)
mysql -u root -p < database/schema.sql
```

### Apply All Migrations

To run all migrations in order:

```bash
php run_migration.php all
```

### Apply Specific Migration

```bash
php run_migration.php 001_remove_reservado_status.sql
```

## File Structure

```
database/
├── README.md                      # This file
├── schema.sql                     # Master schema (reference only)
├── usuarios_schema.sql            # Authentication system tables
├── seed.sql                       # Sample data for testing
└── migrations/                    # Incremental schema updates
    ├── README.md                  # Migration documentation
    ├── 000_initial_schema.sql    # Base schema
    └── 001_remove_reservado_status.sql
```

## Database Files

### schema.sql
**Purpose:** Master schema file for complete reference
**Usage:** Fresh installs only (prefer using migrations/000_initial_schema.sql)
**Contains:**
- All table definitions
- All indexes
- All views
- All triggers

**Do NOT use for updates** - Use migrations instead!

### usuarios_schema.sql
**Purpose:** Authentication system setup
**Usage:** Run after schema.sql or initial migration
**Contains:**
- `usuarios` table (user accounts with roles)
- `vista_usuarios` view (user info without passwords)
- Trigger for auto-linking users to clients

```bash
# Apply authentication schema
mysql -u root -p real_estate_db < database/usuarios_schema.sql
```

### seed.sql
**Purpose:** Sample data for development and testing
**Usage:** Optional - only for development environments
**Contains:**
- Sample clients
- Sample properties
- Sample agents
- Sample transactions

```bash
# Load sample data (DEVELOPMENT ONLY)
mysql -u root -p real_estate_db < database/seed.sql
```

### migrations/
**Purpose:** Incremental database updates
**Usage:** All schema changes after initial setup

See [migrations/README.md](migrations/README.md) for detailed migration documentation.

## Migration System

### Why Use Migrations?

1. **Version Control:** Track database changes over time
2. **Team Collaboration:** Everyone applies the same changes
3. **Reproducibility:** Same database state across environments
4. **Safety:** Controlled, tested updates instead of ad-hoc changes
5. **Rollback:** Ability to undo changes if needed

### Migration Naming Convention

```
{number}_{description}.sql

Examples:
000_initial_schema.sql
001_remove_reservado_status.sql
002_add_property_features.sql
```

- **Number:** 3-digit sequential number (000, 001, 002, etc.)
- **Description:** Snake_case description of the change
- **Extension:** Always `.sql`

### Creating a New Migration

1. **Create the file:**
   ```bash
   touch database/migrations/002_your_migration_name.sql
   ```

2. **Add migration header:**
   ```sql
   -- Migration: Your Migration Title
   -- Date: YYYY-MM-DD
   -- Description: Detailed description of what this migration does

   USE real_estate_db;

   -- Your SQL changes here
   ```

3. **Test locally:**
   ```bash
   php run_migration.php 002_your_migration_name.sql
   ```

4. **Document in migrations/README.md**

5. **Commit to git**

### Migration Best Practices

✅ **DO:**
- Test migrations on a development database first
- Backup before running migrations in production
- Keep migrations small and focused
- Use transactions when possible
- Document what each migration does
- Include verification queries at the end

❌ **DON'T:**
- Modify existing migration files after they've been run
- Run migrations manually (use the migration runner)
- Mix schema changes with data changes (separate migrations)
- Delete migration files (they're part of the history)

## Database Schema Overview

### Core Tables

1. **cliente** - Customer information
   - Buyers, sellers, renters, landlords
   - Document validation
   - Contact information

2. **inmueble** - Property listings
   - Property details (type, location, price)
   - Status (Disponible, Vendido, Arrendado)
   - Features (rooms, bathrooms, area, garage)

3. **agente** - Real estate agents
   - Agent information
   - Supervisor relationships
   - Active/inactive status

4. **venta** - Sales transactions
   - Sale records
   - Commission tracking
   - Links property, client, and agent

5. **contrato** - Contracts
   - Sale and rental contracts
   - Contract status tracking
   - Document file storage

6. **arriendo** - Rental agreements
   - Active rentals
   - Monthly rent and deposits
   - Rental status

7. **visita** - Property visits
   - Scheduled visits
   - Visit outcomes
   - Client interest tracking

8. **usuarios** - User accounts
   - Admin and cliente roles
   - Password hashing
   - Brute force protection

### Views

- **vista_propiedades_disponibles** - Available properties only
- **vista_contratos_activos** - Active contracts with details
- **vista_usuarios** - User info without password hashes

### Triggers

- **tr_venta_actualizar_estado** - Set property status to "Vendido" when sold
- **tr_arriendo_actualizar_estado** - Set property status to "Arrendado" when rented
- **tr_arriendo_verificar_fechas** - Validate rental date ranges
- **tr_usuario_vincular_cliente** - Auto-link users to client records

## Utilities

### run_migration.php
PHP script to run migrations safely from command line.

```bash
# Show help and available migrations
php run_migration.php

# Run specific migration
php run_migration.php 000_initial_schema.sql

# Run all migrations
php run_migration.php all
```

### test_inmueble_query.php
Test script to verify the inmueble table and run statistics queries.

```bash
php test_inmueble_query.php
```

## Backup and Restore

### Create Backup

```bash
# Backup entire database
mysqldump -u root -p real_estate_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup specific table
mysqldump -u root -p real_estate_db inmueble > backup_inmueble_$(date +%Y%m%d).sql

# Backup structure only (no data)
mysqldump -u root -p --no-data real_estate_db > schema_backup.sql
```

### Restore Backup

```bash
# Restore full database
mysql -u root -p real_estate_db < backup_20251021_120000.sql

# Restore specific table
mysql -u root -p real_estate_db < backup_inmueble_20251021.sql
```

## Troubleshooting

### Common Errors

#### #1046 - Base de datos no seleccionada

**Solution:** Ensure `USE real_estate_db;` is at the top of your SQL file, or select the database in phpMyAdmin before running queries.

#### #1109 - Tabla desconocida 'INMUEBLE' in information_schema

**Solution:** Same as #1046 - database not selected.

#### Foreign key constraint errors

**Solution:** Ensure parent tables exist before creating tables with foreign keys. Use the migration runner which handles order automatically.

#### Trigger already exists

**Solution:** Use `DROP TRIGGER IF EXISTS` before `CREATE TRIGGER` in migrations.

## Character Encoding

All tables use:
- **Character Set:** `utf8mb4`
- **Collation:** `utf8mb4_unicode_ci`

This supports:
- Full UTF-8 including emojis 🏠
- International characters (ñ, á, é, í, ó, ú)
- Proper case-insensitive sorting

## Performance Indexes

Key indexes for query performance:

- **cliente:** documento, email, tipo_cliente
- **inmueble:** ciudad, tipo, estado, precio
- **agente:** email, activo
- **arriendo:** fechas, estado
- **visita:** fecha, agente

## Security Features

1. **Foreign Keys:** All relationships use foreign key constraints
2. **Cascading Updates:** Updates propagate automatically
3. **Restricted Deletes:** Prevent accidental data loss
4. **Password Hashing:** Bcrypt hashing in usuarios table
5. **Brute Force Protection:** Login attempt tracking and lockout

## Additional Resources

- **Setup Guide:** `../documentation/setup.md`
- **Authentication Guide:** `../INSTRUCCIONES_LOGIN.md`
- **SQL Queries:** `../queries/` directory
- **Project Documentation:** `../CLAUDE.md`
