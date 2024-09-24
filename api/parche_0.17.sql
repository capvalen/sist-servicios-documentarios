ALTER TABLE `compra_venta` ADD `codproveedor` VARCHAR(50) NULL DEFAULT '' AFTER `moneda`;
ALTER TABLE `compra_venta` ADD `fechaAprobacion` DATE NULL DEFAULT NULL AFTER `codproveedor`;
ALTER TABLE `compra_venta` ADD `numeroDocumento` VARCHAR(50) NULL DEFAULT '' AFTER `fechaAprobacion`;
ALTER TABLE `compra_venta` ADD `fechaDocumento` DATE NULL DEFAULT NULL AFTER `numeroDocumento`;