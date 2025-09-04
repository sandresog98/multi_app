CREATE DATABASE IF NOT EXISTS multiapptwo;
USE multiapptwo;

-- Tabla de logs del sistema (eventos seleccionados)
CREATE TABLE IF NOT EXISTS control_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accion ENUM('login','crear','editar','eliminar') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    detalle TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos_anteriores LONGTEXT,
    datos_nuevos LONGTEXT,
    nivel ENUM('info','warning','error','critical') DEFAULT 'info',
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_usuario (id_usuario)
);

-- Tablas de Sifone
CREATE TABLE IF NOT EXISTS sifone_asociados (
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
CREATE TABLE IF NOT EXISTS sifone_cartera_aseguradora (
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
CREATE TABLE IF NOT EXISTS sifone_cartera_mora (
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
CREATE TABLE IF NOT EXISTS control_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol VARCHAR(20) NOT NULL,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
);
-- Triggers control_usuarios
DROP TRIGGER IF EXISTS control_usuarios_bu;
CREATE TRIGGER control_usuarios_bu BEFORE UPDATE ON control_usuarios
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS control_usuarios_bi;
CREATE TRIGGER control_usuarios_bi BEFORE INSERT ON control_usuarios
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
    -- Usuario administrador por defecto (contraseña ya hasheada)
    INSERT IGNORE INTO control_usuarios (usuario, password, nombre_completo, email, rol)
    VALUES ('admin', '$2y$10$AfwFabRPO0HtoRYHmDFZ3eN5ynO4z8yGe.rcBOUhbchrk3DSwFi..', 'Administrador del Sistema', 'admin@coomultiunion.com', 'admin');

CREATE TABLE IF NOT EXISTS control_asociados (
    cedula VARCHAR(20) UNIQUE PRIMARY KEY,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
);
-- Triggers control_asociados
DROP TRIGGER IF EXISTS control_asociados_bu;
CREATE TRIGGER control_asociados_bu BEFORE UPDATE ON control_asociados
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS control_asociados_bi;
CREATE TRIGGER control_asociados_bi BEFORE INSERT ON control_asociados
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS control_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    parametros TEXT,
    valor_minimo DECIMAL(10,2) NOT NULL,
    valor_maximo DECIMAL(10,2) NOT NULL,
    prioridad INT NOT NULL DEFAULT 100,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
);
-- Triggers control_productos
DROP TRIGGER IF EXISTS control_productos_bu;
CREATE TRIGGER control_productos_bu BEFORE UPDATE ON control_productos
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS control_productos_bi;
CREATE TRIGGER control_productos_bi BEFORE INSERT ON control_productos
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS control_asignacion_asociado_producto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(20) NOT NULL,
  producto_id INT NOT NULL,
  dia_pago TINYINT UNSIGNED NOT NULL, -- 1..31
  monto_pago DECIMAL(12,2) NOT NULL,
  estado_activo BOOLEAN DEFAULT TRUE,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
);
-- Triggers control_asignacion_asociado_producto
DROP TRIGGER IF EXISTS control_asig_prod_bu;
CREATE TRIGGER control_asig_prod_bu BEFORE UPDATE ON control_asignacion_asociado_producto
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS control_asig_prod_bi;
CREATE TRIGGER control_asig_prod_bi BEFORE INSERT ON control_asignacion_asociado_producto
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
-- Transacciones (cabecera)
CREATE TABLE IF NOT EXISTS control_transaccion (
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
CREATE TABLE IF NOT EXISTS control_transaccion_detalle (
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
CREATE TABLE IF NOT EXISTS cobranza_comunicaciones (
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

-- Cobranza: snapshot de detalle de mora al crear comunicación
CREATE TABLE IF NOT EXISTS cobranza_detalle_mora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunicacion_id INT NOT NULL,
    asociado_cedula VARCHAR(20) NOT NULL,
    aportes_monto DECIMAL(12,2) NULL,
    total_creditos INT NOT NULL DEFAULT 0,
    creado_por INT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comunicacion (comunicacion_id),
    INDEX idx_cedula (asociado_cedula),
    INDEX idx_fecha (fecha)
);
CREATE TABLE IF NOT EXISTS cobranza_detalle_mora_credito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detalle_id INT NOT NULL,
    numero_credito VARCHAR(50) NOT NULL,
    deuda_capital DECIMAL(12,2) NOT NULL DEFAULT 0,
    deuda_mora DECIMAL(12,2) NOT NULL DEFAULT 0,
    dias_mora INT NOT NULL DEFAULT 0,
    fecha_pago DATE NULL,
    INDEX idx_detalle (detalle_id),
    INDEX idx_numero (numero_credito)
);

-- Tablas de pagos bancarios
CREATE TABLE IF NOT EXISTS banco_pse (
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
CREATE TABLE IF NOT EXISTS banco_confiar (
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
CREATE TABLE IF NOT EXISTS banco_asignacion_pse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pse_id VARCHAR(50) NOT NULL,
    confiar_id VARCHAR(50) NOT NULL,
    tipo_asignacion ENUM('directa','grupal','manual') NULL,
    fecha_validacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asignacion (pse_id, confiar_id),
    KEY idx_pse (pse_id),
    KEY idx_confiar (confiar_id)
);
CREATE TABLE IF NOT EXISTS banco_confirmacion_confiar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    confiar_id VARCHAR(50) NOT NULL UNIQUE,
    cedula VARCHAR(20) NULL,
    link_validacion VARCHAR(255) NULL,
    comentario TEXT NULL,
    estado ENUM('pendiente','asignado','conciliado','no_valido') NOT NULL DEFAULT 'pendiente',
    motivo_no_valido TEXT NULL,
    no_valido_por INT NULL,
    no_valido_fecha TIMESTAMP NULL,
    fecha_validacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_estado (estado),
    KEY idx_no_valido_fecha (no_valido_fecha)
);

-- Orquestación de cargas de archivos (jobs)
CREATE TABLE IF NOT EXISTS control_cargas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL, -- ej: sifone_libro, sifone_cartera_aseguradora, sifone_cartera_mora, pagos_pse, pagos_confiar
    archivo_ruta VARCHAR(255) NOT NULL,
    estado ENUM('pendiente','procesando','completado','error') DEFAULT 'pendiente',
    mensaje_log TEXT NULL,
    usuario_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    KEY idx_tipo (tipo),
    KEY idx_estado (estado),
    KEY idx_usuario (usuario_id),
    KEY idx_fecha (fecha_creacion)
);

-- Boletería: categorías
CREATE TABLE IF NOT EXISTS boleteria_categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    precio_compra DECIMAL(12,2) NOT NULL,
    precio_venta DECIMAL(12,2) NOT NULL,
    descripcion VARCHAR(500) NULL,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_bol_cat_nombre (nombre),
    KEY idx_bol_cat_estado (estado)
);
-- Triggers boleteria_categoria
DROP TRIGGER IF EXISTS boleteria_categoria_bu;
CREATE TRIGGER boleteria_categoria_bu BEFORE UPDATE ON boleteria_categoria
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS boleteria_categoria_bi;
CREATE TRIGGER boleteria_categoria_bi BEFORE INSERT ON boleteria_categoria
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- Boletería: boletas
CREATE TABLE IF NOT EXISTS boleteria_boletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    serial VARCHAR(64) NOT NULL,
    precio_compra_snapshot DECIMAL(12,2) NOT NULL,
    precio_venta_snapshot DECIMAL(12,2) NOT NULL,
    archivo_ruta VARCHAR(255) NULL,
    fecha_vencimiento DATE NULL,
    estado ENUM('disponible','vendida','anulada','contabilizada') DEFAULT 'disponible',
    asociado_cedula VARCHAR(20) NULL,
    metodo_venta ENUM('credito','regalo_cooperativa') NULL,
    comprobante VARCHAR(255) NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_vendida TIMESTAMP NULL,
    fecha_contabilizacion TIMESTAMP NULL,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    -- Campos de auditoría
    creado_por INT NULL,
    vendido_por INT NULL,
    contabilizado_por INT NULL,
    UNIQUE KEY uq_bol_serial_cat (categoria_id, serial),
    KEY idx_bol_categoria (categoria_id),
    KEY idx_bol_estado (estado),
    KEY idx_bol_cedula (asociado_cedula),
    KEY idx_bol_fecha_vencimiento (fecha_vencimiento)
);
-- Triggers boleteria_boletas
DROP TRIGGER IF EXISTS boleteria_boletas_bu;
CREATE TRIGGER boleteria_boletas_bu BEFORE UPDATE ON boleteria_boletas
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS boleteria_boletas_bi;
CREATE TRIGGER boleteria_boletas_bi BEFORE INSERT ON boleteria_boletas
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- Boletería: historial de eventos
CREATE TABLE IF NOT EXISTS boleteria_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boleta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    accion ENUM('crear','vender','contabilizar','anular','desanular') NOT NULL,
    estado_anterior ENUM('disponible','vendida','anulada','contabilizada') NULL,
    estado_nuevo ENUM('disponible','vendida','anulada','contabilizada') NULL,
    detalle TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_boleta (boleta_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha)
);

-- Gestión Créditos: solicitudes
CREATE TABLE IF NOT EXISTS creditos_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(120) NOT NULL,
    identificacion VARCHAR(30) NOT NULL,
    celular VARCHAR(30) NOT NULL,
    email VARCHAR(120) NOT NULL,
    monto_deseado DECIMAL(12,2) NULL,
    tipo ENUM('Dependiente','Independiente') NOT NULL,
    estado ENUM('Creado','Con Datacrédito','Aprobado','Rechazado','Con Estudio','Guardado') DEFAULT 'Creado',
    -- Rutas de archivos (opcionales según tipo/estado)
    dep_nomina_1 VARCHAR(255) NULL,
    dep_nomina_2 VARCHAR(255) NULL,
    dep_cert_laboral VARCHAR(255) NULL,
    dep_simulacion_pdf VARCHAR(255) NULL,
    ind_decl_renta VARCHAR(255) NULL,
    ind_simulacion_pdf VARCHAR(255) NULL,
    ind_codeudor_nomina_1 VARCHAR(255) NULL,
    ind_codeudor_nomina_2 VARCHAR(255) NULL,
    ind_codeudor_cert_laboral VARCHAR(255) NULL,
    archivo_datacredito VARCHAR(255) NULL,
    archivo_estudio VARCHAR(255) NULL,
    archivo_pagare_pdf VARCHAR(255) NULL,
    archivo_amortizacion VARCHAR(255) NULL,
    creado_por INT NULL,
    aprobado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_identificacion (identificacion),
    INDEX idx_estado (estado)
);
-- Triggers creditos_solicitudes
DROP TRIGGER IF EXISTS creditos_solicitudes_bu;
CREATE TRIGGER creditos_solicitudes_bu BEFORE UPDATE ON creditos_solicitudes
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS creditos_solicitudes_bi;
CREATE TRIGGER creditos_solicitudes_bi BEFORE INSERT ON creditos_solicitudes
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- Gestión Créditos: historial de acciones
CREATE TABLE IF NOT EXISTS creditos_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    usuario_id INT NULL,
    accion VARCHAR(60) NOT NULL,
    estado_anterior ENUM('Creado','Con Datacrédito','Aprobado','Rechazado','Con Estudio','Guardado') NULL,
    estado_nuevo ENUM('Creado','Con Datacrédito','Aprobado','Rechazado','Con Estudio','Guardado') NULL,
    archivo_campo VARCHAR(64) NULL,
    archivo_ruta VARCHAR(255) NULL,
    detalle TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_solicitud (solicitud_id),
    INDEX idx_fecha (fecha)
);

-- Ticketera: categorías
CREATE TABLE IF NOT EXISTS ticketera_categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(500) NULL,
    estado_activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_ticket_cat_nombre (nombre),
    KEY idx_ticket_cat_estado (estado_activo)
);
DROP TRIGGER IF EXISTS ticketera_categoria_bu;
CREATE TRIGGER ticketera_categoria_bu BEFORE UPDATE ON ticketera_categoria
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS ticketera_categoria_bi;
CREATE TRIGGER ticketera_categoria_bi BEFORE INSERT ON ticketera_categoria
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- Ticketera: tickets
CREATE TABLE IF NOT EXISTS ticketera_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creador_id INT NOT NULL,
    solicitante_id INT NOT NULL,
    responsable_id INT NOT NULL,
    categoria_id INT NULL,
    resumen VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    estado ENUM('Backlog','En Curso','En Espera','Resuelto','Aceptado','Rechazado') DEFAULT 'Backlog',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    KEY idx_ticket_estado (estado),
    KEY idx_ticket_responsable (responsable_id),
    KEY idx_ticket_solicitante (solicitante_id),
    KEY idx_ticket_categoria (categoria_id)
);
DROP TRIGGER IF EXISTS ticketera_tickets_bu;
CREATE TRIGGER ticketera_tickets_bu BEFORE UPDATE ON ticketera_tickets
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
DROP TRIGGER IF EXISTS ticketera_tickets_bi;
CREATE TRIGGER ticketera_tickets_bi BEFORE INSERT ON ticketera_tickets
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- Ticketera: eventos / comentarios
CREATE TABLE IF NOT EXISTS ticketera_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('comentario','cambio_estado','reasignacion') NOT NULL DEFAULT 'comentario',
    estado_anterior ENUM('Backlog','En Curso','En Espera','Resuelto','Aceptado','Rechazado') NULL,
    estado_nuevo ENUM('Backlog','En Curso','En Espera','Resuelto','Aceptado','Rechazado') NULL,
    responsable_anterior_id INT NULL,
    responsable_nuevo_id INT NULL,
    comentario TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ticket (ticket_id),
    KEY idx_usuario (usuario_id),
    KEY idx_resp_ant (responsable_anterior_id),
    KEY idx_resp_nuevo (responsable_nuevo_id),
    KEY idx_fecha (fecha)
);
