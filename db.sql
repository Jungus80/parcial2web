SET NAMES utf8mb4;

-- =========================
-- IDIOMA
-- =========================
CREATE TABLE Idioma (
  idi_id INT AUTO_INCREMENT PRIMARY KEY,
  idi_nombre VARCHAR(50) NOT NULL UNIQUE,
  idi_bandera_url VARCHAR(255),
  idi_url_pagina_traducida VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- USUARIO
-- =========================
CREATE TABLE Usuario (
  usu_id INT AUTO_INCREMENT PRIMARY KEY,
  usu_nombre VARCHAR(80) NOT NULL,
  usu_email VARCHAR(120) NOT NULL UNIQUE,
  usu_password_hash VARCHAR(255) NOT NULL,
  usu_rol VARCHAR(30) NOT NULL DEFAULT 'cliente',
  usu_activo BOOLEAN NOT NULL DEFAULT TRUE,
  usu_fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usu_ultimo_acceso TIMESTAMP NULL,
  usu_idioma INT,
  CONSTRAINT fk_usuario_idioma
    FOREIGN KEY (usu_idioma) REFERENCES Idioma(idi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- PROVEEDOR
-- =========================
CREATE TABLE Proveedor (
  prv_id INT AUTO_INCREMENT PRIMARY KEY,
  prv_nombre VARCHAR(120) NOT NULL,
  prv_telefono VARCHAR(30),
  prv_celular VARCHAR(30),
  prv_direccion VARCHAR(255),
  prv_url_web VARCHAR(255),
  prv_calificacion_estrellas DECIMAL(2,1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- CATEGORIA
-- =========================
CREATE TABLE Categoria (
  cat_id INT AUTO_INCREMENT PRIMARY KEY,
  cat_nombre VARCHAR(80) NOT NULL UNIQUE,
  cat_descripcion VARCHAR(255),
  cat_activa BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- PRODUCTO
-- =========================
CREATE TABLE Producto (
  pro_id INT AUTO_INCREMENT PRIMARY KEY,
  pro_nombre VARCHAR(120) NOT NULL,
  pro_descripcion TEXT,
  pro_precio_unitario DECIMAL(10,2) NOT NULL,
  pro_precio_compra DECIMAL(10,2),
  pro_cantidad_stock INT NOT NULL DEFAULT 0,
  pro_disponible BOOLEAN NOT NULL DEFAULT TRUE,
  pro_fecha_entrada TIMESTAMP NULL,
  pro_proveedor INT,
  pro_categoria INT,
  pro_imagen_url VARCHAR(255) NULL, -- Nueva columna para la URL de la imagen del producto
  pro_precio_oferta DECIMAL(10,2) DEFAULT NULL,

  CONSTRAINT ck_precio_unitario CHECK (pro_precio_unitario > 0),
  CONSTRAINT ck_stock CHECK (pro_cantidad_stock >= 0),

  CONSTRAINT fk_producto_proveedor
    FOREIGN KEY (pro_proveedor) REFERENCES Proveedor(prv_id),

  CONSTRAINT fk_producto_categoria
    FOREIGN KEY (pro_categoria) REFERENCES Categoria(cat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- CARRITO
-- =========================
CREATE TABLE Carrito (
  car_id INT AUTO_INCREMENT PRIMARY KEY,
  car_usuario INT NOT NULL,
  car_estado ENUM('ACTIVO','EXPIRADO','CONVERTIDO') NOT NULL DEFAULT 'ACTIVO',
  car_fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  car_fecha_expiracion TIMESTAMP,

  CONSTRAINT fk_carrito_usuario
    FOREIGN KEY (car_usuario) REFERENCES Usuario(usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Detalle_carrito (
  dca_id INT AUTO_INCREMENT PRIMARY KEY,
  dca_carrito INT NOT NULL,
  dca_producto INT NOT NULL,
  dca_cantidad INT NOT NULL,
  dca_fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT ck_dca_cantidad CHECK (dca_cantidad > 0),

  UNIQUE (dca_carrito, dca_producto),

  CONSTRAINT fk_dca_carrito
    FOREIGN KEY (dca_carrito) REFERENCES Carrito(car_id)
    ON DELETE CASCADE,

  CONSTRAINT fk_dca_producto
    FOREIGN KEY (dca_producto) REFERENCES Producto(pro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- VENTA
-- =========================
CREATE TABLE Venta (
  ven_id INT AUTO_INCREMENT PRIMARY KEY,
  ven_carrito INT NOT NULL UNIQUE,
  ven_usuario INT NOT NULL,
  ven_estado ENUM('BORRADOR','ACEPTADA','PAGADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
  ven_fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ven_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  ven_hash CHAR(64),
  ven_modificado BOOLEAN NOT NULL DEFAULT FALSE,

  CONSTRAINT fk_venta_carrito
    FOREIGN KEY (ven_carrito) REFERENCES Carrito(car_id),

  CONSTRAINT fk_venta_usuario
    FOREIGN KEY (ven_usuario) REFERENCES Usuario(usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Detalle_venta (
  dev_id INT AUTO_INCREMENT PRIMARY KEY,
  dev_venta INT NOT NULL,
  dev_producto INT NOT NULL,
  dev_cantidad INT NOT NULL,
  dev_precio_unidad_venta DECIMAL(10,2) NOT NULL,
  dev_subtotal DECIMAL(10,2) NOT NULL,

  UNIQUE (dev_venta, dev_producto),

  CONSTRAINT fk_dev_venta
    FOREIGN KEY (dev_venta) REFERENCES Venta(ven_id)
    ON DELETE CASCADE,

  CONSTRAINT fk_dev_producto
    FOREIGN KEY (dev_producto) REFERENCES Producto(pro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- FACTURA
-- =========================
CREATE TABLE Factura (
  fac_id INT AUTO_INCREMENT PRIMARY KEY,
  fac_venta INT NOT NULL UNIQUE,
  fac_fecha_emision TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fac_ruta_pdf VARCHAR(255),
  fac_estado ENUM('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',

  CONSTRAINT fk_factura_venta
    FOREIGN KEY (fac_venta) REFERENCES Venta(ven_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- METRICAS
-- =========================
CREATE TABLE Metrica_navegacion (
  met_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  met_usuario INT NOT NULL,
  met_pagina_url VARCHAR(255) NOT NULL,
  met_fecha_visita TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  met_tiempo_visita INT,
  met_referrer VARCHAR(255),

  CONSTRAINT fk_metrica_usuario
    FOREIGN KEY (met_usuario) REFERENCES Usuario(usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Observa (
  obs_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  obs_usuario INT NOT NULL,
  obs_producto INT NOT NULL,
  obs_fecha_visita TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  obs_permanencia INT,

  UNIQUE (obs_usuario, obs_producto, obs_fecha_visita),

  CONSTRAINT fk_obs_usuario
    FOREIGN KEY (obs_usuario) REFERENCES Usuario(usu_id),

  CONSTRAINT fk_obs_producto
    FOREIGN KEY (obs_producto) REFERENCES Producto(pro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
