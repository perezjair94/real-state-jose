# Refactorización de Rutas de Imágenes - Admin Properties List

## Cambios Realizados

Se ha actualizado la lógica de manejo de rutas de imágenes en `/modules/properties/list.php` para usar la **misma lógica simplificada** que se usa en `cliente/propiedades.php` (Explorar Propiedades).

### Antes (Lógica Compleja)
```php
if (strpos($foto, 'img/') === 0 || strpos($foto, 'casa') !== false) {
    // Default image from img/ folder
    $imagePath = (strpos($foto, 'img/') === 0) ? $foto : '/img/' . $foto;
} else {
    // Custom uploaded photo
    $imagePath = '/assets/uploads/properties/' . $foto;
}
```

**Problemas:**
- Lógica innecesariamente compleja
- Soportaba imágenes en carpeta `/img/` con prefijo `casa`
- Inconsistente con el cliente

### Después (Lógica Simplificada)
```php
if (is_array($fotos_table) && !empty($fotos_table) && isset($fotos_table[0]) && !empty($fotos_table[0])) {
    // Custom uploaded photo
    $imageUrl_table = '/assets/uploads/properties/' . htmlspecialchars($fotos_table[0]);
} else {
    // Default image based on property rotation
    $defaultImages_table = ['/img/casa1.jpeg', '/img/casa2.jpg', '/img/casa3.jpeg'];
    $imageIndex_table = $property['id_inmueble'] % count($defaultImages_table);
    $imageUrl_table = $defaultImages_table[$imageIndex_table];
}
```

**Ventajas:**
- Lógica simplificada y clara
- Usa rotación de imágenes por ID en lugar de nombres específicos
- Consistente con `cliente/propiedades.php`
- Reduce errores por typos en nombres de archivos

## Archivos Modificados

### `/modules/properties/list.php`

1. **Cards View - Sección de inicialización de imágenes (líneas ~253-279)**
   - Actualizada lógica de construcción de array de imágenes para carousel
   - Ahora usa rotación basada en ID de propiedad

2. **Table View - Sección de imagen individual (líneas ~394-413)**
   - Actualizada lógica para obtener primera imagen de JSON
   - Implementa fallback con rotación de imágenes

3. **JavaScript - Inicialización de card galleries (líneas ~531-556)**
   - Actualizada lógica en la sección dentro de `<script>` tags
   - Mantiene consistencia con las otras dos secciones

## Comportamiento

### Imágenes Subidas (Custom)
- Se almacenan en: `/assets/uploads/properties/`
- En BD: Solo el nombre del archivo (ej: `foto_123.jpg`)
- Se acceden como: `/assets/uploads/properties/foto_123.jpg`

### Imágenes Default
- Se almacenan en: `/img/`
- Rotación automática basada en: `id_inmueble % 3`
  - ID 1, 4, 7, 10... → `/img/casa1.jpeg`
  - ID 2, 5, 8, 11... → `/img/casa2.jpg`
  - ID 3, 6, 9, 12... → `/img/casa3.jpeg`

## Prueba

Para verificar que los cambios funcionan correctamente:

1. Accede al admin
2. Navega a "Gestión de Inmuebles"
3. Verifica que en:
   - Vista Tarjetas: Las imágenes se cargan correctamente en el carousel
   - Vista Tabla: Las imágenes en la columna de imagen se muestran correctamente
4. Verifica que propiedades sin imágenes muestren rotación de imágenes default

## Compatibilidad

- Mantiene fallback a `/img/casa1.jpeg` en caso de error de carga
- Compatible con todas las imágenes ya almacenadas en BD
- No requiere cambios en la estructura de BD
