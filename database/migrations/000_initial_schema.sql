-- Migration: Initial Database Schema
-- Date: 2025-10-21
-- Description: Base schema for Real Estate Management System
-- This migration creates all initial tables, views, triggers, and indexes

USE real_estate_db;

-- ============================================================================
-- TABLES
-- ============================================================================

-- Client entity (cliente)
-- Stores customer information for buyers, sellers, renters, and landlords
CREATE TABLE IF NOT EXISTS cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'First name',
    apellido VARCHAR(100) NOT NULL COMMENT 'Last name',
    tipo_documento ENUM('CC', 'CE', 'PP', 'NIT') NOT NULL COMMENT 'Document type: CC=Cedula, CE=Cedula Extranjeria, PP=Passport, NIT=Tax ID',
    nro_documento VARCHAR(20) UNIQUE NOT NULL COMMENT 'Document number (unique)',
    correo VARCHAR(150) UNIQUE NOT NULL COMMENT 'Email address (unique)',
    direccion TEXT COMMENT 'Full address',
    tipo_cliente ENUM('Comprador', 'Vendedor', 'Arrendatario', 'Arrendador') NOT NULL COMMENT 'Client type',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date'
) ENGINE=InnoDB COMMENT='Customer database for real estate transactions';

-- Property entity (inmueble)
-- Stores property listings with details and status
CREATE TABLE IF NOT EXISTS inmueble (
    id_inmueble INT AUTO_INCREMENT PRIMARY KEY,
    tipo_inmueble ENUM('Casa', 'Apartamento', 'Local', 'Oficina', 'Lote') NOT NULL COMMENT 'Property type',
    direccion TEXT NOT NULL COMMENT 'Property address',
    ciudad VARCHAR(100) NOT NULL COMMENT 'City location',
    precio DECIMAL(15,2) NOT NULL COMMENT 'Property price in COP',
    estado ENUM('Disponible', 'Vendido', 'Arrendado') DEFAULT 'Disponible' COMMENT 'Property status',
    descripcion TEXT COMMENT 'Detailed property description',
    fotos JSON COMMENT 'Array of photo file names',
    area_construida DECIMAL(8,2) COMMENT 'Built area in m2',
    area_lote DECIMAL(8,2) COMMENT 'Lot area in m2',
    habitaciones INT DEFAULT 0 COMMENT 'Number of bedrooms',
    banos INT DEFAULT 0 COMMENT 'Number of bathrooms',
    garaje BOOLEAN DEFAULT FALSE COMMENT 'Has garage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date'
) ENGINE=InnoDB COMMENT='Property listings and details';

-- Agent entity (agente)
-- Real estate agents managing properties and client relationships
CREATE TABLE IF NOT EXISTS agente (
    id_agente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL COMMENT 'Full agent name',
    correo VARCHAR(150) UNIQUE NOT NULL COMMENT 'Agent email address',
    telefono VARCHAR(20) NOT NULL COMMENT 'Phone number',
    asesor VARCHAR(200) COMMENT 'Supervisor or mentor name',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Agent active status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date'
) ENGINE=InnoDB COMMENT='Real estate agents and representatives';

-- Sales entity (venta)
-- Records completed property sales transactions
CREATE TABLE IF NOT EXISTS venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    fecha_venta DATE NOT NULL COMMENT 'Sale completion date',
    valor DECIMAL(15,2) NOT NULL COMMENT 'Final sale price',
    comision DECIMAL(10,2) COMMENT 'Agent commission amount',
    observaciones TEXT COMMENT 'Additional sale notes',
    id_inmueble INT NOT NULL COMMENT 'Property sold',
    id_cliente INT NOT NULL COMMENT 'Buyer client',
    id_agente INT COMMENT 'Agent who handled the sale',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',

    FOREIGN KEY (id_inmueble) REFERENCES inmueble(id_inmueble) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_agente) REFERENCES agente(id_agente) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Completed property sales records';

-- Contract entity (contrato)
-- Legal contracts for sales and rental agreements
CREATE TABLE IF NOT EXISTS contrato (
    id_contrato INT AUTO_INCREMENT PRIMARY KEY,
    tipo_contrato ENUM('Venta', 'Arriendo') NOT NULL COMMENT 'Contract type: Sale or Rental',
    fecha_inicio DATE NOT NULL COMMENT 'Contract start date',
    fecha_fin DATE COMMENT 'Contract end date (for rentals)',
    valor_contrato DECIMAL(15,2) NOT NULL COMMENT 'Contract value',
    archivo_contrato VARCHAR(255) COMMENT 'Contract document file name',
    estado ENUM('Borrador', 'Activo', 'Finalizado', 'Cancelado') DEFAULT 'Borrador' COMMENT 'Contract status',
    observaciones TEXT COMMENT 'Contract notes',
    id_inmueble INT NOT NULL COMMENT 'Property in contract',
    id_cliente INT NOT NULL COMMENT 'Client in contract',
    id_agente INT COMMENT 'Agent managing contract',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date',

    FOREIGN KEY (id_inmueble) REFERENCES inmueble(id_inmueble) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_agente) REFERENCES agente(id_agente) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Sales and rental contracts';

-- Rental entity (arriendo)
-- Active rental agreements and payment tracking
CREATE TABLE IF NOT EXISTS arriendo (
    id_arriendo INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio DATE NOT NULL COMMENT 'Rental start date',
    fecha_fin DATE NOT NULL COMMENT 'Rental end date',
    canon_mensual DECIMAL(10,2) NOT NULL COMMENT 'Monthly rent amount',
    deposito DECIMAL(10,2) COMMENT 'Security deposit amount',
    estado ENUM('Activo', 'Vencido', 'Terminado', 'Moroso') DEFAULT 'Activo' COMMENT 'Rental status',
    observaciones TEXT COMMENT 'Rental notes',
    id_inmueble INT NOT NULL COMMENT 'Rented property',
    id_cliente INT NOT NULL COMMENT 'Tenant client',
    id_agente INT COMMENT 'Agent managing rental',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date',

    FOREIGN KEY (id_inmueble) REFERENCES inmueble(id_inmueble) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_agente) REFERENCES agente(id_agente) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Active rental agreements';

-- Visit entity (visita)
-- Scheduled property visits with clients and agents
CREATE TABLE IF NOT EXISTS visita (
    id_visita INT AUTO_INCREMENT PRIMARY KEY,
    fecha_visita DATE NOT NULL COMMENT 'Visit date',
    hora_visita TIME NOT NULL COMMENT 'Visit time',
    estado ENUM('Programada', 'Realizada', 'Cancelada', 'Reprogramada') DEFAULT 'Programada' COMMENT 'Visit status',
    observaciones TEXT COMMENT 'Visit notes or results',
    calificacion ENUM('Muy Interesado', 'Interesado', 'Poco Interesado', 'No Interesado') COMMENT 'Client interest level',
    id_inmueble INT NOT NULL COMMENT 'Property to visit',
    id_cliente INT NOT NULL COMMENT 'Client visiting',
    id_agente INT NOT NULL COMMENT 'Agent conducting visit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date',

    FOREIGN KEY (id_inmueble) REFERENCES inmueble(id_inmueble) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_agente) REFERENCES agente(id_agente) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Scheduled property visits';

-- ============================================================================
-- INDEXES
-- ============================================================================

-- Cliente indexes
CREATE INDEX IF NOT EXISTS idx_cliente_documento ON cliente(tipo_documento, nro_documento);
CREATE INDEX IF NOT EXISTS idx_cliente_email ON cliente(correo);
CREATE INDEX IF NOT EXISTS idx_cliente_tipo ON cliente(tipo_cliente);

-- Inmueble indexes
CREATE INDEX IF NOT EXISTS idx_inmueble_ubicacion ON inmueble(ciudad);
CREATE INDEX IF NOT EXISTS idx_inmueble_tipo ON inmueble(tipo_inmueble);
CREATE INDEX IF NOT EXISTS idx_inmueble_estado ON inmueble(estado);
CREATE INDEX IF NOT EXISTS idx_inmueble_precio ON inmueble(precio);

-- Agente indexes
CREATE INDEX IF NOT EXISTS idx_agente_email ON agente(correo);
CREATE INDEX IF NOT EXISTS idx_agente_activo ON agente(activo);

-- Arriendo indexes
CREATE INDEX IF NOT EXISTS idx_arriendo_fechas ON arriendo(fecha_inicio, fecha_fin);
CREATE INDEX IF NOT EXISTS idx_arriendo_estado ON arriendo(estado);

-- Visita indexes
CREATE INDEX IF NOT EXISTS idx_visita_fecha ON visita(fecha_visita, hora_visita);
CREATE INDEX IF NOT EXISTS idx_visita_agente ON visita(id_agente);

-- ============================================================================
-- VIEWS
-- ============================================================================

-- View: Available properties
CREATE OR REPLACE VIEW vista_propiedades_disponibles AS
SELECT
    i.id_inmueble,
    i.tipo_inmueble,
    i.direccion,
    i.ciudad,
    i.precio,
    i.habitaciones,
    i.banos,
    i.area_construida,
    i.descripcion,
    i.created_at
FROM inmueble i
WHERE i.estado = 'Disponible'
ORDER BY i.created_at DESC;

-- View: Active contracts
CREATE OR REPLACE VIEW vista_contratos_activos AS
SELECT
    c.id_contrato,
    c.tipo_contrato,
    c.fecha_inicio,
    c.fecha_fin,
    c.valor_contrato,
    cl.nombre AS cliente_nombre,
    cl.apellido AS cliente_apellido,
    i.direccion AS propiedad_direccion,
    i.ciudad AS propiedad_ciudad,
    a.nombre AS agente_nombre
FROM contrato c
JOIN cliente cl ON c.id_cliente = cl.id_cliente
JOIN inmueble i ON c.id_inmueble = i.id_inmueble
LEFT JOIN agente a ON c.id_agente = a.id_agente
WHERE c.estado = 'Activo'
ORDER BY c.fecha_inicio DESC;

-- ============================================================================
-- TRIGGERS
-- ============================================================================

DELIMITER $$

-- Trigger: Update property status when sold
DROP TRIGGER IF EXISTS tr_venta_actualizar_estado$$
CREATE TRIGGER tr_venta_actualizar_estado
AFTER INSERT ON venta
FOR EACH ROW
BEGIN
    UPDATE inmueble
    SET estado = 'Vendido', updated_at = CURRENT_TIMESTAMP
    WHERE id_inmueble = NEW.id_inmueble;
END$$

-- Trigger: Update property status when rented
DROP TRIGGER IF EXISTS tr_arriendo_actualizar_estado$$
CREATE TRIGGER tr_arriendo_actualizar_estado
AFTER INSERT ON arriendo
FOR EACH ROW
BEGIN
    UPDATE inmueble
    SET estado = 'Arrendado', updated_at = CURRENT_TIMESTAMP
    WHERE id_inmueble = NEW.id_inmueble;
END$$

-- Trigger: Check rental end dates
DROP TRIGGER IF EXISTS tr_arriendo_verificar_fechas$$
CREATE TRIGGER tr_arriendo_verificar_fechas
BEFORE INSERT ON arriendo
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin <= NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de fin debe ser posterior a la fecha de inicio';
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

-- Verify tables were created
SELECT
    'Tables created' AS Status,
    COUNT(*) AS Count
FROM information_schema.tables
WHERE table_schema = 'real_estate_db'
    AND table_name IN ('cliente', 'inmueble', 'agente', 'venta', 'contrato', 'arriendo', 'visita');

-- Verify views were created
SELECT
    'Views created' AS Status,
    COUNT(*) AS Count
FROM information_schema.views
WHERE table_schema = 'real_estate_db'
    AND table_name IN ('vista_propiedades_disponibles', 'vista_contratos_activos');

-- Verify triggers were created
SELECT
    'Triggers created' AS Status,
    COUNT(*) AS Count
FROM information_schema.triggers
WHERE trigger_schema = 'real_estate_db';

SELECT 'Migration 000_initial_schema.sql completed successfully!' AS Result;
