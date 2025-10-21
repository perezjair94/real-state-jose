-- Consulta de estadísticas de propiedades por estado
-- Este archivo SQL puede ejecutarse directamente en phpMyAdmin o línea de comandos

-- Asegurarse de usar la base de datos correcta
USE real_estate_db;

-- Consulta de conteo de propiedades por estado
SELECT 'Total properties' AS Description, COUNT(*) AS Count
FROM inmueble

UNION ALL

SELECT 'Disponible', COUNT(*)
FROM inmueble
WHERE estado = 'Disponible'

UNION ALL

SELECT 'Vendido', COUNT(*)
FROM inmueble
WHERE estado = 'Vendido'

UNION ALL

SELECT 'Arrendado', COUNT(*)
FROM inmueble
WHERE estado = 'Arrendado';
