CREATE DATABASE multiapptwo;
USE multiapptwo;

-- Tabla de logs del sistema (eventos seleccionados)
CREATE OR REPLACE TABLE control_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accion ENUM('login','crear','editar','eliminar') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    detalle TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    nivel ENUM('info','warning','error','critical') DEFAULT 'info',
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_usuario (id_usuario)
);

-- Tablas de Sifone
CREATE OR REPLACE TABLE sifone_asociados (
    cedula VARCHAR(20),
    clased VARCHAR(10),
    codigo VARCHAR(20),
    antigu DECIMAL(10,2),
    nombre VARCHAR(255),
    fechai DATE,
    fecnac DATE,
    ciunac VARCHAR(100),
    ahorro DECIMAL(15,2),
    otroah DECIMAL(15,2),
    aporte DECIMAL(15,2),
    acumul DECIMAL(15,2),
    saldoc DECIMAL(15,2),
    saldoa DECIMAL(15,2),
    direcc VARCHAR(255),
    vereda VARCHAR(100),
    ciudad VARCHAR(100),
    depart VARCHAR(100),
    celula VARCHAR(20),
    mail VARCHAR(255),
    telefo VARCHAR(20),
    coment TEXT,
    cencos VARCHAR(50),
    nomcen VARCHAR(100),
    compan VARCHAR(100),
    cuotaf DECIMAL(15,2),
    fecing DATE,
    salari DECIMAL(15,2),
    salint VARCHAR(10),
    aprend VARCHAR(5),
    porarp VARCHAR(5),
    otroin DECIMAL(15,2),
    endeu DECIMAL(15,2),
    porend DECIMAL(15,2),
    xnomina VARCHAR(5),
    cuenta VARCHAR(50),
    banco VARCHAR(100),
    tipocu VARCHAR(50),
    sexo VARCHAR(5),
    estrat INT,
    escola INT,
    cabfam VARCHAR(5),
    emplea VARCHAR(5),
    ocupac INT,
    jorlab INT,
    locali VARCHAR(100),
    barrio VARCHAR(100),
    tipoco VARCHAR(50),
    fechav VARCHAR(50),
    quince VARCHAR(10),
    estadc VARCHAR(50),
    socio VARCHAR(5),
    directi VARCHAR(5),
    ciuced VARCHAR(100),
    dirlab VARCHAR(255),
    tellab VARCHAR(20),
    ciulab VARCHAR(100),
    ocupro VARCHAR(100),
    actciu VARCHAR(100),
    otrosdctos DECIMAL(15,2),
    fecexp DATE,
    grupo_sang VARCHAR(10),
    rh VARCHAR(5),
    egresos DECIMAL(15,2),
    pasivos DECIMAL(15,2),
    activos DECIMAL(15,2),
    tipo_vvda VARCHAR(50),
    pers_cargo DECIMAL(5,2),
    form_ing VARCHAR(100),
    conc_ing VARCHAR(100),
    regimen VARCHAR(50),
    asisasamb VARCHAR(5),
    fcesant VARCHAR(100),
    fpensio VARCHAR(100),
    eps VARCHAR(100),
    per_planta VARCHAR(5),
    per_admven VARCHAR(5),
    scorin DECIMAL(10,2),
    pep VARCHAR(5),
    decorf VARCHAR(40),
    cons_cdr VARCHAR(20),
    fecha_act DATE,
    pais VARCHAR(100),
    paisrf INT
);
CREATE OR REPLACE TABLE sifone_cartera_aseguradora (
    cedula VARCHAR(20),
    numero VARCHAR(20),
    priape VARCHAR(100),
    segape VARCHAR(100),
    nombr1 VARCHAR(100),
    nombre VARCHAR(255),
    fecnac DATE,
    fechae DATE,
    plazo DECIMAL(5,2),
    sexo CHAR(1),
    edad INT,
    ahorro DECIMAL(15,2),
    carter DECIMAL(15,2),
    tipopr VARCHAR(255),
    valorc DECIMAL(15,2),
    tasa DECIMAL(5,4)
);
CREATE OR REPLACE TABLE sifone_cartera_mora (
    nombre VARCHAR(255),
    telefo VARCHAR(20),
    cedula VARCHAR(20),
    codigo VARCHAR(20),
    ahorro DECIMAL(15,2),
    volunt DECIMAL(15,2),
    aporte DECIMAL(15,2),
    presta VARCHAR(20),
    formap VARCHAR(10),
    cantid DECIMAL(5,2),
    sdomor DECIMAL(15,2),
    tipopr VARCHAR(10),
    fechae DATE,
    tasain DECIMAL(5,2),
    diav DECIMAL(5,2),
    fechap DATE,
    provis DECIMAL(15,2),
    ultpag DATE,
    direcc VARCHAR(255),
    pagadu VARCHAR(255),
    celula VARCHAR(20),
    ciures VARCHAR(100),
    cencos VARCHAR(50),
    nomcen VARCHAR(100),
    barrio VARCHAR(100),
    mail VARCHAR(255),
    intmora DECIMAL(15,2)
);

-- Tabla de usuarios para autenticación
CREATE OR REPLACE TABLE control_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol VARCHAR(20) NOT NULL,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
    -- Usuario administrador por defecto (contraseña ya hasheada)
    INSERT INTO control_usuarios (usuario, password, nombre_completo, email, rol)
    VALUES ('admin', '$2y$10$AfwFabRPO0HtoRYHmDFZ3eN5ynO4z8yGe.rcBOUhbchrk3DSwFi..', 'Administrador del Sistema', 'admin@coomultiunion.com', 'admin');

CREATE OR REPLACE TABLE control_asociados (
    cedula VARCHAR(20) UNIQUE PRIMARY KEY,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE OR REPLACE TABLE control_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    parametros TEXT,
    valor_minimo DECIMAL(10,2) NOT NULL,
    valor_maximo DECIMAL(10,2) NOT NULL,
    prioridad INT NOT NULL DEFAULT 100,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE OR REPLACE TABLE control_asignacion_asociado_producto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(20) NOT NULL,
  producto_id INT NOT NULL,
  dia_pago TINYINT UNSIGNED NOT NULL, -- 1..31
  monto_pago DECIMAL(12,2) NOT NULL,
  estado_activo BOOLEAN DEFAULT TRUE,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Transacciones (cabecera)
CREATE OR REPLACE TABLE control_transaccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL,
    origen_pago ENUM('pse','cash_qr') NOT NULL,
    pse_id VARCHAR(50) NULL,
    confiar_id VARCHAR(50) NULL,
    valor_pago_total DECIMAL(12,2) NOT NULL,
    usuario_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cedula (cedula),
    KEY idx_origen (origen_pago),
    KEY idx_pse (pse_id),
    KEY idx_confiar (confiar_id)
);

-- Transacciones (detalle)
CREATE OR REPLACE TABLE control_transaccion_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaccion_id INT NOT NULL,
    tipo_rubro ENUM('credito_mora','cobranza','credito','producto') NOT NULL,
    referencia_credito VARCHAR(50) NULL,
    producto_id INT NULL,
    descripcion VARCHAR(200) NULL,
    valor_recomendado DECIMAL(12,2) NOT NULL DEFAULT 0,
    valor_asignado DECIMAL(12,2) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_transaccion (transaccion_id),
    KEY idx_tipo (tipo_rubro),
    KEY idx_credito (referencia_credito),
    KEY idx_producto (producto_id)
);

-- Comunicaciones de cobranza
CREATE OR REPLACE TABLE cobranza_comunicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asociado_cedula VARCHAR(20) NOT NULL,
    tipo_comunicacion ENUM('Llamada','Mensaje de Texto','Whatsapp','Email') NOT NULL,
    estado ENUM('Sin comunicación','Informa de pago realizado','Comprometido a realizar el pago','Sin respuesta') NOT NULL,
    comentario TEXT NULL,
    fecha_comunicacion DATETIME NOT NULL,
    id_usuario INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cedula (asociado_cedula),
    KEY idx_fecha (fecha_comunicacion)
);

-- Tablas de pagos bancarios
CREATE OR REPLACE TABLE banco_pse (
    pse_id VARCHAR(50) PRIMARY KEY,
    cus BIGINT NOT NULL,
    valor BIGINT NOT NULL,
    banco_originador VARCHAR(50) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    cod_de_autorizacion_rechazo_o_fallida VARCHAR(50) NOT NULL,
    fecha_hora_creada DATETIME NOT NULL,
    fecha_hora_ultimo_estado DATETIME NOT NULL,
    impuesto BIGINT NOT NULL,
    ticket_id BIGINT NOT NULL,
    ciclo_origen BIGINT NOT NULL,
    ciclo_transaccion BIGINT NOT NULL,
    servicio_codigo BIGINT NOT NULL,
    servicio_nombre VARCHAR(50) NOT NULL,
    referencia_1 VARCHAR(50) NOT NULL,
    referencia_2 BIGINT NOT NULL,
    referencia_3 VARCHAR(50) NOT NULL,
    tipo_de_usuario VARCHAR(50) NOT NULL,
    tipo_de_autorizacion VARCHAR(50) NULL,
    fecha_hora_resolucion_de_la_transaccion DATETIME NOT NULL,
    banco_recaudador VARCHAR(50) NOT NULL,
    modalidad_de_vinculacion VARCHAR(50) NOT NULL,
    id_funcionalidad BIGINT NOT NULL,
    nombre_funcionalidad VARCHAR(50) NOT NULL,
    tipo_de_cuenta_destino VARCHAR(50) NOT NULL,
    numero_de_cuenta_destino BIGINT NOT NULL,
    procedencia_de_pago VARCHAR(50) NOT NULL,
    medio_de_pago VARCHAR(50) NOT NULL,
    tipo_de_dispositivo VARCHAR(50) NOT NULL,
    navegador VARCHAR(50) NOT NULL,
    tipo_de_flujo VARCHAR(50) NOT NULL
);
CREATE OR REPLACE TABLE banco_confiar (
    confiar_id VARCHAR(50) PRIMARY KEY,
    fecha DATE NULL,
    descripcion VARCHAR(255) NULL,
    documento VARCHAR(255) NULL,
    oficina VARCHAR(255) NULL,
    valor_consignacion DOUBLE NULL,
    valor_retiro DOUBLE NULL,
    saldo DOUBLE NULL,
    tipo_transaccion VARCHAR(50) NULL
);
CREATE OR REPLACE TABLE banco_asignacion_pse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pse_id VARCHAR(50) NOT NULL,
    confiar_id VARCHAR(50) NOT NULL,
    tipo_asignacion ENUM('directa','grupal','manual') NULL,
    fecha_validacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asignacion (pse_id, confiar_id),
    KEY idx_pse (pse_id),
    KEY idx_confiar (confiar_id)
);
CREATE OR REPLACE TABLE banco_confirmacion_confiar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    confiar_id VARCHAR(50) NOT NULL UNIQUE,
    cedula VARCHAR(20) NOT NULL,
    link_validacion VARCHAR(255) NOT NULL,
    comentario TEXT NULL,
    fecha_validacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orquestación de cargas de archivos (jobs)
CREATE OR REPLACE TABLE control_cargas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL, -- ej: sifone_libro, sifone_cartera_aseguradora, sifone_cartera_mora, pagos_pse, pagos_confiar
    archivo_ruta VARCHAR(255) NOT NULL,
    estado ENUM('pendiente','procesando','completado','error') DEFAULT 'pendiente',
    mensaje_log TEXT NULL,
    usuario_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_tipo (tipo),
    KEY idx_estado (estado),
    KEY idx_usuario (usuario_id),
    KEY idx_fecha (fecha_creacion)
);