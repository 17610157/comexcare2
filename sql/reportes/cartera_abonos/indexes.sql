-- Indices para acelerar la consulta del reporte Cartera Abonos (Mes Anterior)
-- Asegúrate de ejecutarlos una vez que la estructura de tablas esté estable
CREATE INDEX IF NOT EXISTS idx_cobranza_fecha_cargo_estado ON cobranza (fecha, cargo_ab, estado);
CREATE INDEX IF NOT EXISTS idx_cobranza_plaza_tienda ON cobranza (cplaza, ctienda);
CREATE INDEX IF NOT EXISTS idx_cobranza_keys ON cobranza (cplaza, ctienda, tipo_ref, no_ref, clave_cl);
CREATE INDEX IF NOT EXISTS idx_cliente_depurado_keys ON cliente_depurado (ctienda, cplaza, clie_clave);
CREATE INDEX IF NOT EXISTS idx_zona_plaza_tienda ON zona (plaza, tienda);
