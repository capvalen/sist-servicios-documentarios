CREATE TABLE proveedor (
    id INT AUTO_INCREMENT,
    ruc VARCHAR(20) NOT NULL,
    razon VARCHAR(250) NOT NULL,
    direccionDestino VARCHAR(255),
    celular VARCHAR(15),
    zip VARCHAR(10),
    atencion VARCHAR(100),
    correo VARCHAR(100),
    emision DATE,
    diasPago INT,
    referencia VARCHAR(255),
    moneda int,
    incoterm VARCHAR(20),
		registro datetime,
    PRIMARY KEY (id)
);

CREATE TABLE orden_cabecera (
    id INT AUTO_INCREMENT,
    idProveedor INT NOT MULL,
    orden VARCHAR(50),
    ruc VARCHAR(20),
    razon VARCHAR(100),
    direccion VARCHAR(255),
    celular VARCHAR(15),
    ciudad VARCHAR(100),
    pais VARCHAR(100),
    casilla VARCHAR(50),
    enviar VARCHAR(250),
    direccionDestino VARCHAR(255),
    ciudadDestino VARCHAR(100),
    paisDestino VARCHAR(100),
    almacen VARCHAR(100),
    lugar VARCHAR(100),
    direccionEntrega VARCHAR(255),
    contacto VARCHAR(250),
    observaciones TEXT,
    telefonoComprador VARCHAR(15),
    correoComprador VARCHAR(100),
    aprobador1 VARCHAR(100),
    aprobador2 VARCHAR(100),
    aprobador3 VARCHAR(100),
    registro datetime null DEFAULT CURRENT_TIMESTAMP
    PRIMARY KEY (id)
);

CREATE TABLE orden_detalle (
    id INT AUTO_INCREMENT,
    idOrden INT NOT NULL,
    solped VARCHAR(50),
    pos INT,
    codigo VARCHAR(100),
    descripcion TEXT,
    observaciones TEXT,
    fecha DATE,
    cantidad DECIMAL(10, 2),
    medida VARCHAR(50),
    precioUnitario DECIMAL(10, 2),
    PRIMARY KEY (id)
);
ALTER TABLE `proveedor` CHANGE `registro` `registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `proveedor` ADD `activo` INT NULL DEFAULT '1' AFTER `registro`;
ALTER TABLE `orden_detalle` ADD `activo` INT NULL DEFAULT '1' AFTER `precioUnitario`;
ALTER TABLE `orden_cabecera` ADD `activo` INT NULL DEFAULT '1' AFTER `registro`;