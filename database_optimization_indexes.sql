-- Script SQL para optimizar índices de base de datos
-- Ejecutar este script con permisos de DBA para mejorar rendimiento de reportes
-- Fecha: 2024-01-20

-- Índices para tabla canota (datos de ventas)
CREATE INDEX IF NOT EXISTS idx_canota_fecha_plaza_tienda_vendedor
ON canota (nota_fecha, cplaza, ctienda, vend_clave);

CREATE INDEX IF NOT EXISTS idx_canota_status ON canota (ban_status);
CREATE INDEX IF NOT EXISTS idx_canota_fecha ON canota (nota_fecha);

-- Índices para tabla venta (devoluciones)
CREATE INDEX IF NOT EXISTS idx_venta_fecha_vendedor_plaza_tienda
ON venta (f_emision, clave_vend, cplaza, ctienda);

CREATE INDEX IF NOT EXISTS idx_venta_tipo_estado ON venta (tipo_doc, estado);
CREATE INDEX IF NOT EXISTS idx_venta_fecha_emision ON venta (f_emision);

-- Índices para tabla partvta (detalles de venta)
CREATE INDEX IF NOT EXISTS idx_partvta_referencia_plaza_tienda
ON partvta (no_referen, cplaza, ctienda);

CREATE INDEX IF NOT EXISTS idx_partvta_articulo ON partvta (clave_art);
CREATE INDEX IF NOT EXISTS idx_partvta_total ON partvta (totxpart);

-- Índices para tabla asesores_vvt (información de vendedores)
CREATE INDEX IF NOT EXISTS idx_asesores_plaza_asesor
ON asesores_vvt (plaza, asesor);

-- Índices para tabla metas (metas de ventas)
CREATE INDEX IF NOT EXISTS idx_metas_fecha_plaza_tienda
ON metas (fecha, plaza, tienda);

CREATE INDEX IF NOT EXISTS idx_metas_fecha ON metas (fecha);

-- Índices para tabla bi_sys_tiendas (información de tiendas)
CREATE INDEX IF NOT EXISTS idx_bi_tiendas_plaza_tienda
ON bi_sys_tiendas (id_plaza, clave_tienda);

CREATE INDEX IF NOT EXISTS idx_bi_tiendas_zona ON bi_sys_tiendas (zona);

-- Índices para tabla xcorte (cortes de caja)
CREATE INDEX IF NOT EXISTS idx_xcorte_fecha_plaza_tienda
ON xcorte (fecha, cplaza, tienda);

CREATE INDEX IF NOT EXISTS idx_xcorte_fecha ON xcorte (fecha);