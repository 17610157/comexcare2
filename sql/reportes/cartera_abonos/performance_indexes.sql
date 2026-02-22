-- Índices optimizados para Cartera Abonos - Tiempo Real
-- Optimizado para consultas de alto rendimiento

-- 1. Índice compuesto principal para la tabla cobranza (la más crítica)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobranza_abonos_optimized 
ON cobranza (cargo_ab, estado, fecha DESC, cplaza, ctienda) 
WHERE cargo_ab = 'A' AND estado = 'S' AND cborrado <> '1';

-- 2. Índice para búsquedas rápidas por referencia
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobranza_referencia_optimized 
ON cobranza (tipo_ref, no_ref, clave_cl, cplaza, ctienda) 
WHERE cargo_ab = 'A';

-- 3. Índice para el subquery de cargos (fecha_venc)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobranza_cargos_optimized 
ON cobranza (cargo_ab, cplaza, ctienda, tipo_ref, no_ref, clave_cl, fecha_venc, dfechafac) 
WHERE cargo_ab = 'C';

-- 4. Índice optimizado para cliente_depurado
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cliente_depurado_optimized 
ON cliente_depurado (cplaza, ctienda, clie_clave) 
INCLUDE (clie_rfc, clie_nombr, clie_credi);

-- 5. Índice de cobertura para zona (si se usa frecuentemente)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_zona_cobertura 
ON zona (plaza, tienda) 
INCLUDE (descripcio);

-- 6. Índice para búsquedas de texto (nombre, rfc)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cliente_texto_busqueda 
ON cliente_depurado USING gin(to_tsvector('spanish', clie_nombr || ' ' || clie_rfc));

-- 7. Índice de soporte para el ORDER BY principal
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobranza_order_optimized 
ON cobranza (cplaza, ctienda, fecha DESC) 
WHERE cargo_ab = 'A' AND estado = 'S';

-- 8. Índice parcial para filtrado rápido por fechas
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobranza_fechas_parcial 
ON cobranza (fecha) 
WHERE cargo_ab = 'A' AND estado = 'S' AND cborrado <> '1' 
AND fecha >= CURRENT_DATE - INTERVAL '2 years';

-- 9. Estadísticas para optimizador de consultas
ANALYZE cobranza;
ANALYZE cliente_depurado;
ANALYZE zona;

-- 10. Configuración de parámetros de rendimiento (ejecutar como superusuario)
-- ALTER SYSTEM SET work_mem = '256MB';
-- ALTER SYSTEM SET shared_buffers = '25% of RAM';
-- ALTER SYSTEM SET effective_cache_size = '75% of RAM';
-- SELECT pg_reload_conf();

COMMIT;

-- Nota: En PostgreSQL, CONCURRENTLY permite crear índices sin bloquear la tabla
-- Para MySQL, remover CONCURRENTLY y ejecutar durante mantenimiento