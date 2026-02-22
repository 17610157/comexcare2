-- Estructura de tabla materializada para Cartera Abonos
-- Se actualiza en background para consultas en tiempo real

-- 1. Tabla principal materializada
CREATE TABLE IF NOT EXISTS cartera_abonos_materialized (
    id BIGSERIAL PRIMARY KEY,
    plaza VARCHAR(10) NOT NULL,
    tienda VARCHAR(15) NOT NULL,
    fecha DATE NOT NULL,
    fecha_vta DATE,
    concepto VARCHAR(50) NOT NULL,
    tipo VARCHAR(10) NOT NULL,
    factura VARCHAR(50) NOT NULL,
    clave VARCHAR(50) NOT NULL,
    rfc VARCHAR(20),
    nombre VARCHAR(200),
    monto_fa DECIMAL(15,2) DEFAULT 0,
    monto_dv DECIMAL(15,2) DEFAULT 0,
    monto_cd DECIMAL(15,2) DEFAULT 0,
    dias_cred INTEGER DEFAULT 0,
    dias_vencidos INTEGER DEFAULT 0,
    
    -- Campos de control y sincronización
    source_id BIGINT, -- ID original de tabla cobranza
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sync_status VARCHAR(20) DEFAULT 'active', -- active, deleted, modified
    sync_batch VARCHAR(50), -- Para tracking de actualizaciones
    checksum VARCHAR(64), -- Para detectar cambios
    
    -- Índices para rendimiento máximo
    INDEX idx_materialized_fecha (fecha DESC),
    INDEX idx_materialized_plaza_tienda (plaza, tienda),
    INDEX idx_materializado_keys (plaza, tienda, tipo, factura, clave),
    INDEX idx_materializado_cliente (rfc, nombre),
    INDEX idx_materializado_sync (sync_status, last_updated),
    INDEX idx_materializado_busqueda (
        plaza, tienda, fecha, 
        nombre, rfc, factura, clave
    )
);

-- 2. Tabla de control de sincronización
CREATE TABLE IF NOT EXISTS cartera_abonos_sync_control (
    id SERIAL PRIMARY KEY,
    sync_type VARCHAR(50) NOT NULL, -- full, incremental, delta
    status VARCHAR(20) NOT NULL, -- running, completed, failed
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    records_processed INTEGER DEFAULT 0,
    records_added INTEGER DEFAULT 0,
    records_updated INTEGER DEFAULT 0,
    records_deleted INTEGER DEFAULT 0,
    error_message TEXT,
    sync_batch VARCHAR(50) UNIQUE,
    last_source_timestamp TIMESTAMP, -- Último timestamp de datos fuente
    next_sync_at TIMESTAMP,
    
    INDEX idx_sync_control_status (status),
    INDEX idx_sync_control_type (sync_type),
    INDEX idx_sync_control_batch (sync_batch)
);

-- 3. Tabla de log de cambios para sincronización incremental
CREATE TABLE IF NOT EXISTS cartera_abonos_change_log (
    id BIGSERIAL PRIMARY KEY,
    source_table VARCHAR(50) NOT NULL, -- cobranza, cliente_depurado
    source_id BIGINT NOT NULL,
    action VARCHAR(20) NOT NULL, -- INSERT, UPDATE, DELETE
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    field_name VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    processed BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP,
    
    INDEX idx_change_log_source (source_table, source_id),
    INDEX idx_change_log_processed (processed, changed_at),
    INDEX idx_change_log_action (action, changed_at)
);

-- 4. Vista optimizada para consultas (apunta a tabla materializada)
CREATE OR REPLACE VIEW cartera_abonos_view AS
SELECT 
    plaza,
    tienda,
    fecha,
    fecha_vta,
    concepto,
    tipo,
    factura,
    clave,
    rfc,
    nombre,
    monto_fa,
    monto_dv,
    monto_cd,
    dias_cred,
    dias_vencidos
FROM cartera_abonos_materialized
WHERE sync_status = 'active'
ORDER BY plaza, tienda, fecha DESC;

-- 5. Función para generar checksum de registro
CREATE OR REPLACE FUNCTION generate_cartera_checksum(
    p_plaza VARCHAR,
    p_tienda VARCHAR,
    p_fecha DATE,
    p_concepto VARCHAR,
    p_tipo VARCHAR,
    p_factura VARCHAR,
    p_clave VARCHAR,
    p_rfc VARCHAR,
    p_nombre VARCHAR,
    p_monto_fa DECIMAL,
    p_monto_dv DECIMAL,
    p_monto_cd DECIMAL,
    p_dias_cred INTEGER,
    p_dias_vencidos INTEGER
) RETURNS VARCHAR AS $$
BEGIN
    RETURN md5(
        COALESCE(p_plaza, '') || '|' ||
        COALESCE(p_tienda, '') || '|' ||
        COALESCE(p_fecha::TEXT, '') || '|' ||
        COALESCE(p_concepto, '') || '|' ||
        COALESCE(p_tipo, '') || '|' ||
        COALESCE(p_factura, '') || '|' ||
        COALESCE(p_clave, '') || '|' ||
        COALESCE(p_rfc, '') || '|' ||
        COALESCE(p_nombre, '') || '|' ||
        COALESCE(p_monto_fa::TEXT, '0') || '|' ||
        COALESCE(p_monto_dv::TEXT, '0') || '|' ||
        COALESCE(p_monto_cd::TEXT, '0') || '|' ||
        COALESCE(p_dias_cred::TEXT, '0') || '|' ||
        COALESCE(p_dias_vencidos::TEXT, '0')
    );
END;
$$ LANGUAGE plpgsql;

-- 6. Trigger para log de cambios en tabla fuente (cobranza)
CREATE OR REPLACE FUNCTION log_cobranza_changes() RETURNS TRIGGER AS $$
BEGIN
    -- Solo loguear cambios en registros relevantes para cartera abonos
    IF NEW.cargo_ab = 'A' AND NEW.estado = 'S' AND NEW.cborrado <> '1' THEN
        INSERT INTO cartera_abonos_change_log (
            source_table, source_id, action, changed_at,
            field_name, old_value, new_value
        ) VALUES (
            'cobranza', NEW.id, 
            CASE WHEN TG_OP = 'INSERT' THEN 'INSERT'
                 WHEN TG_OP = 'UPDATE' THEN 'UPDATE'
                 ELSE 'DELETE' END,
            CURRENT_TIMESTAMP,
            NULL, NULL, NULL
        );
    END IF;
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- 7. Crear triggers para monitoreo de cambios
DROP TRIGGER IF EXISTS trg_cobranza_changes ON cobranza;
CREATE TRIGGER trg_cobranza_changes
    AFTER INSERT OR UPDATE OR DELETE ON cobranza
    FOR EACH ROW EXECUTE FUNCTION log_cobranza_changes();

-- 8. Procedimiento de sincronización inicial (full sync)
CREATE OR REPLACE FUNCTION sync_cartera_abonos_full() RETURNS INTEGER AS $$
DECLARE
    batch_id VARCHAR;
    start_time TIMESTAMP := CURRENT_TIMESTAMP;
    records_count INTEGER := 0;
BEGIN
    -- Generar ID de batch
    batch_id := 'FULL_' || to_char(start_time, 'YYYYMMDD_HH24MISS');
    
    -- Limpiar tabla materializada
    TRUNCATE TABLE cartera_abonos_materialized;
    
    -- Insertar control de sincronización
    INSERT INTO cartera_abonos_sync_control (
        sync_type, status, sync_batch, started_at
    ) VALUES ('full', 'running', batch_id, start_time);
    
    -- Cargar datos optimizados con CTEs
    INSERT INTO cartera_abonos_materialized (
        plaza, tienda, fecha, fecha_vta, concepto, tipo, factura, clave,
        rfc, nombre, monto_fa, monto_dv, monto_cd, dias_cred, dias_vencidos,
        source_id, sync_batch, checksum
    )
    WITH 
    abonos_base AS (
        SELECT 
            c.cplaza, c.ctienda, c.fecha, c.concepto, c.tipo_ref, c.no_ref, 
            c.clave_cl, c.IMPORTE, c.id as source_id
        FROM cobranza c
        WHERE c.cargo_ab = 'A' 
        AND c.estado = 'S' 
        AND c.cborrado <> '1'
    ),
    clientes_info AS (
        SELECT 
            cl.ctienda, cl.cplaza, cl.clie_clave, 
            cl.clie_rfc, cl.clie_nombr, cl.clie_credi
        FROM cliente_depurado cl
        WHERE EXISTS (
            SELECT 1 FROM abonos_base ab 
            WHERE ab.cplaza = cl.cplaza 
            AND ab.ctienda = cl.ctienda 
            AND ab.clave_cl = cl.clie_clave
        )
    ),
    fechas_vencimiento AS (
        SELECT 
            co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.clave_cl,
            co.fecha_venc, co.dfechafac
        FROM cobranza co
        WHERE co.cargo_ab = 'C'
        AND EXISTS (
            SELECT 1 FROM abonos_base ab 
            WHERE ab.cplaza = co.cplaza 
            AND ab.ctienda = co.ctienda 
            AND ab.tipo_ref = co.tipo_ref 
            AND ab.no_ref = co.no_ref 
            AND ab.clave_cl = co.clave_cl
        )
    )
    SELECT 
        ab.cplaza, ab.ctienda, ab.fecha, COALESCE(fv.dfechafac, ab.fecha), 
        ab.concepto, ab.tipo_ref, ab.no_ref, ab.clave_cl,
        COALESCE(ci.clie_rfc, ''), COALESCE(ci.clie_nombr, ''),
        CASE WHEN ab.tipo_ref = 'FA' AND ab.concepto <> 'DV' THEN ab.IMPORTE ELSE 0 END,
        CASE WHEN ab.tipo_ref = 'FA' AND ab.concepto = 'DV' THEN ab.IMPORTE ELSE 0 END,
        CASE WHEN ab.tipo_ref = 'CD' AND ab.concepto <> 'DV' THEN ab.IMPORTE ELSE 0 END,
        COALESCE(ci.clie_credi, 0),
        (ab.fecha - COALESCE(fv.fecha_venc, ab.fecha)),
        ab.source_id, batch_id,
        generate_cartera_checksum(
            ab.cplaza, ab.ctienda, ab.fecha, ab.concepto, ab.tipo_ref, 
            ab.no_ref, ab.clave_cl, COALESCE(ci.clie_rfc, ''), 
            COALESCE(ci.clie_nombr, ''),
            CASE WHEN ab.tipo_ref = 'FA' AND ab.concepto <> 'DV' THEN ab.IMPORTE ELSE 0 END,
            CASE WHEN ab.tipo_ref = 'FA' AND ab.concepto = 'DV' THEN ab.IMPORTE ELSE 0 END,
            CASE WHEN ab.tipo_ref = 'CD' AND ab.concepto <> 'DV' THEN ab.IMPORTE ELSE 0 END,
            COALESCE(ci.clie_credi, 0),
            (ab.fecha - COALESCE(fv.fecha_venc, ab.fecha))
        )
    FROM abonos_base ab
    LEFT JOIN clientes_info ci ON (
        ab.cplaza = ci.cplaza 
        AND ab.ctienda = ci.ctienda 
        AND ab.clave_cl = ci.clie_clave
    )
    LEFT JOIN fechas_vencimiento fv ON (
        ab.cplaza = fv.cplaza 
        AND ab.ctienda = fv.ctienda 
        AND ab.tipo_ref = fv.tipo_ref 
        AND ab.no_ref = fv.no_ref 
        AND ab.clave_cl = fv.clave_cl
    );
    
    -- Obtener conteo
    GET DIAGNOSTICS records_count = ROW_COUNT;
    
    -- Actualizar control de sincronización
    UPDATE cartera_abonos_sync_control 
    SET status = 'completed', 
        completed_at = CURRENT_TIMESTAMP,
        records_processed = records_count,
        records_added = records_count
    WHERE sync_batch = batch_id;
    
    -- Marcar logs como procesados
    UPDATE cartera_abonos_change_log 
    SET processed = TRUE, processed_at = CURRENT_TIMESTAMP
    WHERE processed = FALSE;
    
    RETURN records_count;
END;
$$ LANGUAGE plpgsql;

-- 9. Procedimiento de sincronización incremental
CREATE OR REPLACE FUNCTION sync_cartera_abonos_incremental() RETURNS INTEGER AS $$
DECLARE
    batch_id VARCHAR;
    start_time TIMESTAMP := CURRENT_TIMESTAMP;
    records_count INTEGER := 0;
    last_sync TIMESTAMP;
BEGIN
    -- Obtener última sincronización
    SELECT MAX(started_at) INTO last_sync 
    FROM cartera_abonos_sync_control 
    WHERE status = 'completed' AND sync_type = 'incremental';
    
    -- Si no hay sincronización previa, hacer full sync
    IF last_sync IS NULL THEN
        RETURN sync_cartera_abonos_full();
    END IF;
    
    -- Generar ID de batch
    batch_id := 'INC_' || to_char(start_time, 'YYYYMMDD_HH24MISS');
    
    -- Insertar control de sincronización
    INSERT INTO cartera_abonos_sync_control (
        sync_type, status, sync_batch, started_at, last_source_timestamp
    ) VALUES ('incremental', 'running', batch_id, start_time, last_sync);
    
    -- Procesar cambios desde última sincronización
    -- (Implementación específica según lógica de cambios)
    
    -- Actualizar control
    UPDATE cartera_abonos_sync_control 
    SET status = 'completed', completed_at = CURRENT_TIMESTAMP
    WHERE sync_batch = batch_id;
    
    RETURN records_count;
END;
$$ LANGUAGE plpgsql;

-- 10. Estadísticas y mantenimiento
ANALYZE cartera_abonos_materialized;
ANALYZE cartera_abonos_sync_control;
ANALYZE cartera_abonos_change_log;

COMMIT;