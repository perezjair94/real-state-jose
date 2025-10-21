# Consultas SQL Útiles

Este directorio contiene consultas SQL útiles para el sistema de gestión inmobiliaria.

## Uso en phpMyAdmin

Para ejecutar estas consultas en phpMyAdmin:

1. Abre phpMyAdmin en tu navegador: `http://localhost/phpmyadmin`
2. **IMPORTANTE**: Selecciona la base de datos `real_estate_db` en el panel izquierdo
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido del archivo `.sql`
5. Haz clic en "Continuar" o "Go"

## Uso en línea de comandos

```bash
# Ejecutar consulta desde archivo
mysql -u root -p < queries/inmueble_stats.sql

# O ejecutar interactivamente
mysql -u root -p real_estate_db
```

## Consultas Disponibles

### `inmueble_stats.sql`
Muestra estadísticas de propiedades por estado:
- Total de propiedades
- Propiedades disponibles
- Propiedades vendidas
- Propiedades arrendadas

**Ejemplo de salida:**
```
+-------------------+-------+
| Description       | Count |
+-------------------+-------+
| Total properties  |     8 |
| Disponible        |     3 |
| Vendido           |     2 |
| Arrendado         |     2 |
+-------------------+-------+
```

## Solución de Problemas

### Error: "#1046 - Base de datos no seleccionada"

**Causa**: No has seleccionado la base de datos antes de ejecutar la consulta.

**Soluciones**:
1. En phpMyAdmin: Haz clic en `real_estate_db` en el panel izquierdo antes de ejecutar la consulta
2. En la consulta: Asegúrate de que la primera línea sea `USE real_estate_db;`
3. En línea de comandos: Especifica la base de datos: `mysql -u root -p real_estate_db`

### Error: "#1109 - Tabla desconocida 'INMUEBLE' in information_schema"

**Causa**: Similar al error anterior, la base de datos no está seleccionada.

**Solución**: Mismo procedimiento que el error #1046.
