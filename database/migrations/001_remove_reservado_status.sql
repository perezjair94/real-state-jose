-- Migration: Remove 'Reservado' status from inmueble table
-- Date: 2025-10-21
-- Description: Changes ENUM field to only allow 'Disponible', 'Vendido', 'Arrendado'

USE real_estate_db;

-- Step 1: Update any existing 'Reservado' records to 'Disponible'
UPDATE inmueble
SET estado = 'Disponible'
WHERE estado = 'Reservado';

-- Step 2: Alter the table to remove 'Reservado' from the ENUM
ALTER TABLE inmueble
MODIFY COLUMN estado ENUM('Disponible', 'Vendido', 'Arrendado') DEFAULT 'Disponible' COMMENT 'Property status';

-- Step 3: Verify the change
SELECT
    COLUMN_TYPE
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'real_estate_db'
    AND TABLE_NAME = 'inmueble'
    AND COLUMN_NAME = 'estado';

-- Step 4: Display summary of affected records
SELECT
    'Total properties' AS Description,
    COUNT(*) AS Count
FROM inmueble
UNION ALL
SELECT
    'Disponible',
    COUNT(*)
FROM inmueble
WHERE estado = 'Disponible'
UNION ALL
SELECT
    'Vendido',
    COUNT(*)
FROM inmueble
WHERE estado = 'Vendido'
UNION ALL
SELECT
    'Arrendado',
    COUNT(*)
FROM inmueble
WHERE estado = 'Arrendado';
