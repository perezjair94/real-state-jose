# Botones de Interés en Propiedades - Comprar/Arrendar

## Descripción General

Se han agregado botones de "Comprar" y "Arrendar" en la página de detalle de propiedades (`modules/properties/view.php`) que permiten a los usuarios (tanto autenticados como visitantes) expresar su interés en una propiedad.

## Características Implementadas

### 1. Botones de Acción
- **Botón "Comprar"** (🏠): Para expresar interés en comprar la propiedad
- **Botón "Arrendar"** (🔑): Para expresar interés en arrendar la propiedad
- Los botones solo aparecen cuando la propiedad está en estado "Disponible"
- Diseño con gradientes atractivos y efectos hover

### 2. Formulario Modal Inteligente
Cuando el usuario hace clic en un botón, se abre un modal con:

#### Campos del Formulario:
- **Nombre** (requerido)
- **Apellido** (requerido)
- **Correo Electrónico** (requerido)
- **Teléfono** (requerido)
- **Fecha Preferida para Visita** (opcional)
- **Hora Preferida** (opcional)
- **Mensaje Adicional** (opcional)

#### Funcionalidad Inteligente:
- Si el usuario está **autenticado** (tiene sesión activa):
  - Los campos de nombre, apellido y correo se pre-llenan automáticamente
  - El sistema vincula la solicitud con su cuenta de usuario

- Si el usuario **NO está autenticado**:
  - Debe llenar todos los campos manualmente
  - Se le muestra un mensaje sugiriendo iniciar sesión
  - El sistema crea un nuevo registro de cliente tipo "Lead"

### 3. Procesamiento Backend

#### Flujo de Datos:
1. **Validación de Entrada**:
   - Verifica que todos los campos requeridos estén presentes
   - Valida formato de correo electrónico
   - Valida que la propiedad exista y esté disponible

2. **Gestión de Clientes**:
   - **Cliente Existente**: Si el correo ya existe en la base de datos, usa ese registro
   - **Cliente Nuevo**: Crea un nuevo registro en la tabla `cliente` con:
     - Tipo de documento: CC (temporal)
     - Número de documento: `LEAD-XXXXXXXXXX` (único generado)
     - Tipo de cliente: "Comprador" o "Arrendatario" según el tipo de interés

3. **Creación de Visita**:
   - Se crea un registro en la tabla `visita` con:
     - Estado: "Programada"
     - Fecha: Fecha preferida del usuario o +3 días por defecto
     - Hora: Hora preferida del usuario o 10:00 AM por defecto
     - Observaciones: Incluye tipo de interés, teléfono y mensaje
     - Se asigna un agente aleatorio activo (o null si no hay)

4. **Respuesta al Usuario**:
   - Mensaje de éxito confirmando que se procesó la solicitud
   - El administrador puede ver la visita en el módulo de visitas

## Archivos Modificados

### 1. `/modules/properties/view.php`

#### Cambios en HTML (líneas 97-131):
```php
// Separación de botones por rol
- Botones de admin (Editar, Cambiar Estado): Solo visible para hasRole('admin')
- Botones de interés (Comprar, Arrendar): Visible para todos cuando estado='Disponible'
- Botones generales (Imprimir, Compartir): Visible para todos
```

#### Nuevo JavaScript (líneas 684-827):
- `showInterestForm(tipoInteres, propertyId)`: Muestra el modal con el formulario
- `submitInterestForm()`: Valida y envía la solicitud por AJAX
- Pre-llenado inteligente de datos para usuarios autenticados

#### Nuevos Estilos CSS (líneas 833-917):
- `.btn-purchase`: Botón verde con gradiente para comprar
- `.btn-rent`: Botón azul oscuro con gradiente para arrendar
- `.interest-form`: Estilos del formulario modal
- `.property-summary`: Resumen de la propiedad en el modal
- `.interest-badge`: Insignias de tipo de interés
- `.info-message`: Mensaje informativo para usuarios no autenticados

### 2. `/modules/properties/ajax.php`

#### Cambios en Detección de Requests (líneas 13-30):
```php
// Soporte para JSON requests además de form-data
- Detecta Content-Type: application/json
- Lee body con file_get_contents('php://input')
- Decodifica JSON y lo hace disponible como $jsonData
```

#### Nuevo Case en Switch (líneas 64-66):
```php
case 'submitInterest':
    handleSubmitInterest($pdo, $response, $jsonData);
    break;
```

#### Nueva Función (líneas 473-620):
```php
function handleSubmitInterest($pdo, &$response, $jsonData)
```

**Funcionalidades:**
- Validación de campos requeridos
- Sanitización de inputs con `sanitizeInput()`
- Validación de email con `validateEmail()`
- Verificación de existencia de propiedad y estado disponible
- Búsqueda de cliente existente por correo
- Creación de nuevo cliente si no existe (con tipo según interés)
- Asignación aleatoria de agente activo
- Creación de registro de visita con observaciones detalladas
- Logging de operaciones para auditoría

## Flujo de Usuario

### Usuario Autenticado (Cliente):
1. Navega a detalle de propiedad disponible
2. Ve botones "Comprar" y "Arrendar"
3. Click en botón → Modal se abre con datos pre-llenados
4. Opcional: Ajusta fecha/hora de visita o agrega mensaje
5. Click en "Enviar Solicitud"
6. Confirmación: "¡Solicitud enviada exitosamente!"

### Usuario NO Autenticado (Visitante):
1. Navega a detalle de propiedad disponible
2. Ve botones "Comprar" y "Arrendar"
3. Click en botón → Modal se abre vacío
4. Llena todos los campos requeridos
5. Ve mensaje sugiriendo crear cuenta
6. Click en "Enviar Solicitud"
7. Sistema crea cliente Lead automáticamente
8. Confirmación: "¡Solicitud enviada exitosamente!"

### Administrador:
1. Recibe la solicitud como una visita "Programada" en el módulo de visitas
2. Ve en observaciones:
   - Tipo de interés (Compra/Arriendo)
   - Teléfono del interesado
   - Mensaje adicional si lo dejó
   - Nota: "Generado automáticamente desde la web"
3. Puede contactar al cliente usando los datos proporcionados
4. Puede gestionar la visita (confirmar, reprogramar, cancelar)

## Validaciones de Seguridad

### Frontend:
- Validación de campos requeridos antes de enviar
- Validación de formato de email con regex
- Campos sanitizados antes de envío

### Backend:
- Uso de prepared statements (PDO) para prevenir SQL injection
- Sanitización de inputs con `sanitizeInput()`
- Validación de email con `validateEmail()`
- Verificación de existencia de propiedad
- Verificación de estado disponible
- Logging de todas las operaciones
- Try-catch para manejo de errores

## Base de Datos

### Tablas Involucradas:

1. **cliente**:
   - Se consulta para verificar existencia por correo
   - Se inserta nuevo registro si no existe (tipo Lead)

2. **inmueble**:
   - Se consulta para verificar existencia y estado

3. **visita**:
   - Se inserta nuevo registro con los datos de la solicitud

4. **agente**:
   - Se consulta para asignar un agente activo aleatoriamente

### Ejemplo de Datos Creados:

**Cliente Lead (nuevo):**
```sql
INSERT INTO cliente (nombre, apellido, tipo_documento, nro_documento, correo, tipo_cliente)
VALUES ('Juan', 'Pérez', 'CC', 'LEAD-A1B2C3D4E5', 'juan.perez@email.com', 'Comprador');
```

**Visita:**
```sql
INSERT INTO visita (fecha_visita, hora_visita, estado, observaciones, id_inmueble, id_cliente, id_agente)
VALUES ('2025-11-03', '10:00:00', 'Programada', 'SOLICITUD DE Compra\nTeléfono: 3001234567\nMensaje: Muy interesado en esta propiedad\n\nGenerado automáticamente desde la web.', 5, 123, 2);
```

## Configuración de Permisos

### Rol Admin:
- Ve todos los botones (Editar, Cambiar Estado, Comprar, Arrendar, etc.)
- Puede gestionar las solicitudes desde el módulo de visitas

### Rol Cliente:
- Ve solo botones de interés (Comprar, Arrendar) cuando está autenticado
- Ve botones generales (Imprimir, Compartir)
- NO ve botones de edición o cambio de estado

### Usuario NO Autenticado:
- Ve solo botones de interés (Comprar, Arrendar)
- Ve botones generales (Imprimir, Compartir)
- NO ve botones de edición o cambio de estado

## Testing

### Pruebas Recomendadas:

1. **Como Usuario Autenticado (Cliente)**:
   - Login con credenciales de cliente
   - Navegar a detalle de propiedad disponible
   - Verificar que botones aparecen
   - Click en "Comprar" → Verificar pre-llenado de datos
   - Enviar solicitud → Verificar mensaje de éxito

2. **Como Visitante (No Autenticado)**:
   - Navegar sin login a detalle de propiedad
   - Click en "Arrendar" → Verificar campos vacíos
   - Llenar formulario → Enviar solicitud
   - Verificar que se creó cliente Lead en BD

3. **Como Administrador**:
   - Login como admin
   - Ir a módulo de Visitas
   - Verificar que aparece la nueva visita "Programada"
   - Verificar observaciones con tipo de interés

4. **Validaciones**:
   - Propiedad no disponible → No deben aparecer botones
   - Email inválido → Mensaje de error
   - Campos vacíos → Mensaje de error

## Mejoras Futuras (Opcionales)

1. **Notificaciones por Email**:
   - Enviar email al cliente confirmando recepción de solicitud
   - Notificar al agente asignado sobre nueva solicitud

2. **Dashboard de Leads**:
   - Módulo específico para gestionar leads (clientes potenciales)
   - Estadísticas de conversión de leads

3. **Seguimiento de Interés**:
   - Historial de propiedades en las que el cliente mostró interés
   - Recomendaciones basadas en intereses previos

4. **Calendario de Visitas**:
   - Vista de calendario para agendar visitas
   - Integración con Google Calendar

5. **Chat en Vivo**:
   - Botón de chat para consultas inmediatas
   - Integración con WhatsApp Business

## Notas Técnicas

### Consideraciones de Rendimiento:
- El formulario se genera dinámicamente en JavaScript (no requiere recarga)
- La solicitud AJAX es asíncrona (no bloquea la UI)
- Los estilos CSS usan variables de tema existente

### Compatibilidad:
- Navegadores modernos (Chrome, Firefox, Safari, Edge)
- Soporte para JavaScript ES6+
- Responsive design (funciona en móviles)

### Seguridad:
- Todas las queries usan prepared statements
- Inputs sanitizados antes de procesamiento
- Validación tanto en frontend como backend
- Logs de auditoría para todas las operaciones

## Documentación de Referencia

- **CLAUDE.md**: Guía general del proyecto
- **INSTRUCCIONES_LOGIN.md**: Sistema de autenticación
- **database/schema.sql**: Esquema de base de datos

## Autor

Implementado usando Claude Code con las siguientes tecnologías:
- PHP 8.2 con PDO
- MySQL 8.0
- JavaScript (Vanilla ES6)
- CSS3 con variables CSS
- Docker para desarrollo

---

**Fecha de Implementación**: 2025-10-31
**Versión**: 1.0.0
