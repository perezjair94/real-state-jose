# Database Migrations

This directory contains SQL migration scripts to update the database schema and data.

## Available Migrations

### 000_initial_schema.sql
**Date:** 2025-10-21
**Description:** Initial database schema for Real Estate Management System.

**Changes:**
- Creates all core tables: cliente, inmueble, agente, venta, contrato, arriendo, visita
- Creates indexes for performance optimization
- Creates views: vista_propiedades_disponibles, vista_contratos_activos
- Creates triggers for automatic property status updates
- Sets up foreign key relationships with proper constraints

**How to apply:**
```bash
# Via MySQL command line
mysql -u root -p real_estate_db < database/migrations/000_initial_schema.sql

# Or via phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select real_estate_db database
# 3. Go to SQL tab
# 4. Copy and paste the contents of 000_initial_schema.sql
# 5. Click "Go" to execute
```

**Note:** This migration uses `CREATE TABLE IF NOT EXISTS` so it's safe to run even if tables already exist. It will only create missing tables and update views/triggers.

---

### 001_remove_reservado_status.sql
**Date:** 2025-10-21
**Description:** Removes the 'Reservado' status from the property status ENUM field.

**Changes:**
- Updates existing properties with 'Reservado' status to 'Disponible'
- Alters the `inmueble.estado` ENUM to only allow: 'Disponible', 'Vendido', 'Arrendado'
- Verifies the change and displays a summary

**How to apply:**
```bash
# Via MySQL command line
mysql -u root -p real_estate_db < database/migrations/001_remove_reservado_status.sql

# Or via phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select real_estate_db database
# 3. Go to SQL tab
# 4. Copy and paste the contents of 001_remove_reservado_status.sql
# 5. Click "Go" to execute
```

**Rollback:**
If you need to rollback this change (not recommended after data has been modified):
```sql
ALTER TABLE inmueble
MODIFY COLUMN estado ENUM('Disponible', 'Vendido', 'Arrendado', 'Reservado') DEFAULT 'Disponible' COMMENT 'Property status';
```

## Migration Best Practices

1. **Backup First:** Always backup your database before running migrations
   ```bash
   mysqldump -u root -p real_estate_db > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Test in Development:** Apply migrations in a development/test environment first

3. **Review Changes:** Read the migration file completely before executing

4. **Check Dependencies:** Ensure migrations are run in order (numbered sequentially)

5. **Document Results:** Keep a log of when migrations were applied and their results

## Migration History

| Migration | Date Applied | Applied By | Status | Notes |
|-----------|-------------|------------|--------|-------|
| 000_initial_schema.sql | - | - | Completed | Base schema - safe to re-run |
| 001_remove_reservado_status.sql | - | - | Pending | Status enum simplification |
