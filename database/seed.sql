-- Sample data for Real Estate Management System
-- Educational testing data
-- Run after schema.sql

USE real_estate_db;

-- Disable foreign key checks temporarily for data insertion
SET FOREIGN_KEY_CHECKS = 0;

-- Sample clients (clientes)
INSERT INTO cliente (nombre, apellido, tipo_documento, nro_documento, correo, direccion, tipo_cliente) VALUES
('Juan', 'Pérez García', 'CC', '12345678', 'juan.perez@email.com', 'Calle 123 #45-67, Bogotá', 'Comprador'),
('María', 'González López', 'CC', '87654321', 'maria.gonzalez@email.com', 'Carrera 45 #67-89, Medellín', 'Vendedor'),
('Carlos', 'Rodríguez Silva', 'CE', '98765432', 'carlos.rodriguez@email.com', 'Avenida 68 #123-45, Cali', 'Arrendatario'),
('Ana', 'Martínez Cruz', 'CC', '11223344', 'ana.martinez@email.com', 'Calle 89 #12-34, Barranquilla', 'Arrendador'),
('Luis', 'Hernández Díaz', 'PP', 'PP1234567', 'luis.hernandez@email.com', 'Transversal 23 #56-78, Cartagena', 'Comprador'),
('Laura', 'Jiménez Morales', 'CC', '55667788', 'laura.jimenez@email.com', 'Diagonal 45 #67-89, Bucaramanga', 'Vendedor'),
('Miguel', 'Torres Vargas', 'CC', '99887766', 'miguel.torres@email.com', 'Circular 12 #34-56, Pereira', 'Arrendatario'),
('Sofia', 'Ramírez Castro', 'CE', '44556677', 'sofia.ramirez@email.com', 'Autopista 67 #89-12, Manizales', 'Comprador');

-- Sample real estate agents (agentes)
INSERT INTO agente (nombre, correo, telefono, asesor, activo) VALUES
('María García Vélez', 'maria.garcia@inmobiliaria.com', '300-555-0123', 'Carlos Rodríguez', TRUE),
('José Luis Martínez', 'jose.martinez@inmobiliaria.com', '301-555-0124', 'Ana Patricia López', TRUE),
('Carmen Elena Torres', 'carmen.torres@inmobiliaria.com', '302-555-0125', 'Carlos Rodríguez', TRUE),
('Roberto Carlos Díaz', 'roberto.diaz@inmobiliaria.com', '303-555-0126', 'María Fernanda Ruiz', TRUE),
('Liliana Herrera Soto', 'liliana.herrera@inmobiliaria.com', '304-555-0127', 'Ana Patricia López', TRUE),
('Fernando Gutiérrez', 'fernando.gutierrez@inmobiliaria.com', '305-555-0128', 'Carlos Rodríguez', FALSE);

-- Sample properties (inmuebles)
INSERT INTO inmueble (tipo_inmueble, direccion, ciudad, precio, descripcion, area_construida, area_lote, habitaciones, banos, garaje, estado) VALUES
('Casa', 'Calle 123 #45-67, Barrio El Poblado', 'Medellín', 450000000.00, 'Hermosa casa de dos pisos con amplio jardín, ubicada en zona residencial exclusiva. Cuenta con sala, comedor, cocina integral, zona de lavado y garaje para dos vehículos.', 180.50, 250.00, 4, 3, TRUE, 'Disponible'),

('Apartamento', 'Carrera 15 #89-123, Zona Rosa', 'Bogotá', 320000000.00, 'Moderno apartamento en piso alto con excelente vista a la ciudad. Edificio con portería 24 horas, gimnasio y salón social.', 95.75, NULL, 3, 2, TRUE, 'Disponible'),

('Local', 'Avenida Principal #234-56, Centro Comercial Plaza', 'Cali', 180000000.00, 'Local comercial en centro comercial de alta afluencia. Ideal para restaurante o tienda de ropa. Incluye bodega y baño privado.', 45.30, NULL, 0, 1, FALSE, 'Disponible'),

('Oficina', 'Torre Empresarial, Piso 12, Oficina 1205', 'Barranquilla', 150000000.00, 'Oficina ejecutiva con vista panorámica al mar. Edificio inteligente con aire acondicionado central y parqueadero asignado.', 62.15, NULL, 0, 1, TRUE, 'Disponible'),

('Lote', 'Vereda Las Flores, Kilómetro 15 Vía al Mar', 'Cartagena', 280000000.00, 'Lote plano ideal para construcción de casa campestre. Cuenta con acceso a servicios públicos y escrituras al día.', NULL, 1200.00, 0, 0, FALSE, 'Disponible'),

('Casa', 'Calle 67 #123-45, Barrio Los Rosales', 'Bucaramanga', 380000000.00, 'Casa familiar en excelente estado, remodelada recientemente. Cuenta con patio trasero, zona BBQ y estudio independiente.', 160.25, 200.50, 3, 2, TRUE, 'Disponible'),

('Apartamento', 'Conjunto Residencial Alamedas, Torre 3, Apto 801', 'Pereira', 250000000.00, 'Apartamento nuevo en conjunto cerrado con zonas verdes, piscina y cancha múltiple. Excelente ubicación cerca al centro comercial.', 78.90, NULL, 2, 2, TRUE, 'Disponible'),

('Local', 'Calle Principal #45-123, Sector Comercial', 'Manizales', 120000000.00, 'Local a pie de calle con excelente visibilidad. Perfecto para farmacia, panadería o cualquier negocio que requiera alta afluencia.', 38.75, NULL, 0, 1, FALSE, 'Arrendado');

-- Sample sales (ventas)
INSERT INTO venta (fecha_venta, valor, comision, observaciones, id_inmueble, id_cliente, id_agente) VALUES
('2024-01-15', 445000000.00, 13350000.00, 'Venta realizada con financiación bancaria. Cliente muy satisfecho con la negociación.', 1, 1, 1),
('2024-02-20', 315000000.00, 9450000.00, 'Venta de contado. Proceso muy ágil, documentación completa desde el inicio.', 2, 5, 2);

-- Sample contracts (contratos)
INSERT INTO contrato (tipo_contrato, fecha_inicio, fecha_fin, valor_contrato, estado, observaciones, id_inmueble, id_cliente, id_agente) VALUES
('Venta', '2024-01-15', NULL, 445000000.00, 'Finalizado', 'Contrato de compraventa con financiación bancaria al 70%.', 1, 1, 1),
('Venta', '2024-02-20', NULL, 315000000.00, 'Finalizado', 'Venta de contado, proceso expedito sin contratiempos.', 2, 5, 2),
('Arriendo', '2024-03-01', '2025-02-28', 1500000.00, 'Activo', 'Contrato de arrendamiento con opción de renovación automática.', 8, 3, 3),
('Arriendo', '2024-01-10', '2024-12-31', 2200000.00, 'Activo', 'Arrendamiento para oficina, incluye servicios públicos básicos.', 4, 7, 4);

-- Sample rentals (arriendos)
INSERT INTO arriendo (fecha_inicio, fecha_fin, canon_mensual, deposito, estado, observaciones, id_inmueble, id_cliente, id_agente) VALUES
('2024-03-01', '2025-02-28', 1500000.00, 3000000.00, 'Activo', 'Arrendamiento de local comercial, pago puntual, buen inquilino.', 8, 3, 3),
('2024-01-10', '2024-12-31', 2200000.00, 4400000.00, 'Activo', 'Oficina ejecutiva, contrato con empresa establecida.', 4, 7, 4);

-- Sample visits (visitas)
INSERT INTO visita (fecha_visita, hora_visita, estado, observaciones, calificacion, id_inmueble, id_cliente, id_agente) VALUES
('2024-03-15', '10:00:00', 'Programada', 'Primera visita, cliente interesado en casa familiar.', NULL, 6, 8, 1),
('2024-03-16', '14:30:00', 'Programada', 'Visita de seguimiento, cliente quiere ver apartamento nuevamente.', NULL, 7, 5, 2),
('2024-03-10', '09:00:00', 'Realizada', 'Visita exitosa, cliente muy interesado, solicita segunda cita.', 'Muy Interesado', 3, 4, 3),
('2024-03-12', '16:00:00', 'Realizada', 'Cliente visitó pero no mostró mucho interés en la propiedad.', 'Poco Interesado', 5, 6, 4),
('2024-03-08', '11:30:00', 'Cancelada', 'Cliente canceló por compromiso laboral, reprogramar.', NULL, 7, 1, 5),
('2024-03-20', '15:00:00', 'Programada', 'Visita inicial, cliente busca inversión en lote.', NULL, 5, 2, 1);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Update property statuses based on sales and rentals
UPDATE inmueble SET estado = 'Vendido' WHERE id_inmueble IN (1, 2);
UPDATE inmueble SET estado = 'Arrendado' WHERE id_inmueble IN (4, 8);

-- Display summary of inserted data
SELECT 'Clientes insertados' as Tabla, COUNT(*) as Registros FROM cliente
UNION ALL
SELECT 'Agentes insertados', COUNT(*) FROM agente
UNION ALL
SELECT 'Inmuebles insertados', COUNT(*) FROM inmueble
UNION ALL
SELECT 'Ventas insertadas', COUNT(*) FROM venta
UNION ALL
SELECT 'Contratos insertados', COUNT(*) FROM contrato
UNION ALL
SELECT 'Arriendos insertados', COUNT(*) FROM arriendo
UNION ALL
SELECT 'Visitas insertadas', COUNT(*) FROM visita;