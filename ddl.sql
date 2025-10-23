CREATE DATABASE IF NOT EXISTS multiapptwo;
USE multiapptwo;

-- Tabla de logs del sistema (eventos seleccionados)
    CREATE TABLE IF NOT EXISTS control_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario VARCHAR(20),
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
    CREATE TABLE IF NOT EXISTS sifone_datacredito (
        a INT,
        b VARCHAR(255),
        c INT,
        d VARCHAR(255),
        e INT,
        f DATE,
        g DATE,
        h INT,
        i INT,
        j INT,
        k INT,
        l INT,
        m VARCHAR(255),
        n INT,
        o DATE,
        p VARCHAR(50),
        q DATE,
        r VARCHAR(50),
        s VARCHAR(50),
        t INT,
        u INT,
        v VARCHAR(50),
        w DECIMAL(18, 2),
        x DECIMAL(18, 2),
        y DECIMAL(18, 2),
        z DECIMAL(18, 2),
        aa DECIMAL(18, 2),
        ab DECIMAL(18, 2),
        ac DECIMAL(18, 2),
        ad DECIMAL(18, 2),
        ae DECIMAL(18, 2),
        af DECIMAL(18, 2),
        ag VARCHAR(50),
        ah DATE,
        ai DATE,
        aj VARCHAR(50),
        ak VARCHAR(50),
        al VARCHAR(255),
        am VARCHAR(50),
        an VARCHAR(50),
        ao VARCHAR(255),
        ap VARCHAR(50),
        aq VARCHAR(50),
        ar VARCHAR(255),
        as1 VARCHAR(50),
        at1 VARCHAR(50),
        au VARCHAR(255),
        av VARCHAR(50),
        aw VARCHAR(50),
        ax DATE,
        ay DATE,
        az VARCHAR(50),
        ba VARCHAR(50),
        bb VARCHAR(50),
        bc INT,
        bd DECIMAL(18, 2),
        be DATE,
        bf VARCHAR(50),
        bg DATE,
        bh INT,
        bi DATE,
        bj DATE,
        bk DATE,
        bl DATE,
        bm DATE,
        bn DATE,
        bo VARCHAR(50),
        bp DATE,
        bq VARCHAR(50),
        br VARCHAR(50),
        bs VARCHAR(50),
        bt VARCHAR(50),
        bu VARCHAR(50),
        bv DATE,
        bw DATE,
        bx VARCHAR(50),
        by1 DATE,
        bz VARCHAR(50),
        ca DATE,
        cb VARCHAR(50),
        cc VARCHAR(50),
        cd DATE
    );
    CREATE TABLE IF NOT EXISTS sifone_balance_prueba (
        cuenta          VARCHAR(50),
        nombre          VARCHAR(255),
        cedula          VARCHAR(50),
        nombrt          VARCHAR(255),
        salant          NUMERIC(18, 2),
        debito          NUMERIC(18, 2),
        credit          NUMERIC(18, 2),
        nuesal          NUMERIC(18, 2),
        grupo1          BIGINT, 
        nombr1          VARCHAR(255),
        grupo2          BIGINT, 
        nombr2          VARCHAR(255),
        grupo3          BIGINT, 
        nombr3          VARCHAR(255),
        grupo4          BIGINT, 
        nombr4          VARCHAR(255),
        salantg1        NUMERIC(18, 2),
        debitog1        NUMERIC(18, 2),
        creditg1        NUMERIC(18, 2),
        nuesalg1        NUMERIC(18, 2),
        salantg2        NUMERIC(18, 2),
        debitog2        NUMERIC(18, 2),
        creditg2        NUMERIC(18, 2),
        nuesalg2        NUMERIC(18, 2),
        salantg3        NUMERIC(18, 2),
        debitog3        NUMERIC(18, 2),
        creditg3        NUMERIC(18, 2),
        nuesalg3        NUMERIC(18, 2),
        salantg4        NUMERIC(18, 2),
        debitog4        NUMERIC(18, 2),
        creditg4        NUMERIC(18, 2),
        nuesalg4        NUMERIC(18, 2),
        salantc         NUMERIC(18, 2),
        nuesalc         NUMERIC(18, 2),
        debitoc         NUMERIC(18, 2),
        creditc         NUMERIC(18, 2),
        detall          VARCHAR(255),
        cuentx          VARCHAR(50),
        period          VARCHAR(7),
        longitud        INTEGER 
    );
    CREATE TABLE IF NOT EXISTS sifone_movimientos_tributarios (
        cuenta          BIGINT,
        nombrc          VARCHAR(255),
        period          VARCHAR(7),
        cedula          VARCHAR(50),
        nombre          VARCHAR(255),
        compro          VARCHAR(20),
        numero          VARCHAR(50),
        docref          VARCHAR(50),
        fecham          DATE,
        detall          VARCHAR(255),
        saldoi          NUMERIC(18),
        debito          NUMERIC(18),
        credit          NUMERIC(18),
        saldof          NUMERIC(18),
        base            NUMERIC(18),
        usuari          VARCHAR(100),
        cencos          VARCHAR(50),
        fecha           DATE,
        hora            TIME
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

-- tablas control
    CREATE TABLE IF NOT EXISTS control_asociados (
        cedula VARCHAR(20) UNIQUE PRIMARY KEY,
        password_hash VARCHAR(255) NULL,
        password_set_at TIMESTAMP NULL DEFAULT NULL,
        reset_token VARCHAR(64) NULL,
        reset_token_expires_at DATETIME NULL,
        estado_activo BOOLEAN DEFAULT TRUE,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_reset_token (reset_token),
        INDEX idx_estado_activo (estado_activo)
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
        recibo_caja_sifone VARCHAR(50) NOT NULL,
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

-- Comunicaciones de cobranza
    CREATE TABLE IF NOT EXISTS cobranza_comunicaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asociado_cedula VARCHAR(20) NOT NULL,
        tipo_comunicacion VARCHAR(50) NOT NULL,
        estado ENUM('Sin comunicación','Informa de pago realizado','Comprometido a realizar el pago','Sin respuesta') NOT NULL,
        comentario TEXT NULL,
        fecha_comunicacion DATETIME NOT NULL,
        id_usuario INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        tipo_origen VARCHAR(20) NOT NULL DEFAULT 'credito',
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

-- Tienda: Catálogo
    CREATE TABLE IF NOT EXISTS tienda_categoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(120) NOT NULL,
        estado_activo BOOLEAN DEFAULT TRUE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uq_tienda_cat_nombre (nombre),
        KEY idx_tienda_cat_activo (estado_activo)
    );
        DROP TRIGGER IF EXISTS tienda_categoria_bu;
        CREATE TRIGGER tienda_categoria_bu BEFORE UPDATE ON tienda_categoria
        FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
        DROP TRIGGER IF EXISTS tienda_categoria_bi;
        CREATE TRIGGER tienda_categoria_bi BEFORE INSERT ON tienda_categoria
        FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS tienda_marca (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(120) NOT NULL,
        estado_activo BOOLEAN DEFAULT TRUE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uq_tienda_marca_nombre (nombre),
        KEY idx_tienda_marca_activo (estado_activo)
    );
        DROP TRIGGER IF EXISTS tienda_marca_bu;
        CREATE TRIGGER tienda_marca_bu BEFORE UPDATE ON tienda_marca
        FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
        DROP TRIGGER IF EXISTS tienda_marca_bi;
        CREATE TRIGGER tienda_marca_bi BEFORE INSERT ON tienda_marca
        FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS tienda_producto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categoria_id INT NOT NULL,
        marca_id INT NOT NULL,
        nombre VARCHAR(200) NOT NULL,
        foto_url VARCHAR(255) NULL,
        descripcion TEXT NULL,
        precio_compra_aprox DECIMAL(12,2) NULL,
        precio_venta_aprox DECIMAL(12,2) NULL,
        estado_activo BOOLEAN DEFAULT TRUE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uq_tienda_prod (categoria_id, marca_id, nombre),
        KEY idx_tienda_prod_cat (categoria_id),
        KEY idx_tienda_prod_marca (marca_id),
        CONSTRAINT fk_tienda_prod_cat FOREIGN KEY (categoria_id) REFERENCES tienda_categoria(id),
        CONSTRAINT fk_tienda_prod_marca FOREIGN KEY (marca_id) REFERENCES tienda_marca(id)
    );
        DROP TRIGGER IF EXISTS tienda_producto_bu;
        CREATE TRIGGER tienda_producto_bu BEFORE UPDATE ON tienda_producto
        FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
        DROP TRIGGER IF EXISTS tienda_producto_bi;
        CREATE TRIGGER tienda_producto_bi BEFORE INSERT ON tienda_producto
        FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);
    -- Tienda: Clientes (externos)
    CREATE TABLE IF NOT EXISTS tienda_clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(200) NOT NULL,
        nit_cedula VARCHAR(50) NOT NULL,
        telefono VARCHAR(50) NULL,
        email VARCHAR(120) NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_tienda_cliente_doc (nit_cedula)
    );
    -- Tienda: Compras (ingreso a inventario)
    CREATE TABLE IF NOT EXISTS tienda_compra (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NULL,
        observacion VARCHAR(300) NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
    );
        DROP TRIGGER IF EXISTS tienda_compra_bu;
        CREATE TRIGGER tienda_compra_bu BEFORE UPDATE ON tienda_compra
        FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    CREATE TABLE IF NOT EXISTS tienda_compra_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        compra_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad INT NOT NULL,
        precio_compra DECIMAL(12,2) NOT NULL,
        precio_venta_sugerido DECIMAL(12,2) NOT NULL,
        -- CONSTRAINT fk_tienda_compra FOREIGN KEY (compra_id) REFERENCES tienda_compra(id),
        -- CONSTRAINT fk_tienda_compra_prod FOREIGN KEY (producto_id) REFERENCES tienda_producto(id),
        KEY idx_tienda_compra (compra_id),
        KEY idx_tienda_compra_prod (producto_id)
    );
    -- IMEIs para celulares (único por unidad)
    CREATE TABLE IF NOT EXISTS tienda_compra_imei (
        id INT AUTO_INCREMENT PRIMARY KEY,
        compra_detalle_id INT NOT NULL,
        imei VARCHAR(30) NOT NULL,
        vendido BOOLEAN DEFAULT FALSE,
        -- CONSTRAINT fk_tienda_imei_det FOREIGN KEY (compra_detalle_id) REFERENCES tienda_compra_detalle(id)
        UNIQUE KEY uq_tienda_imei (imei)
    );
    -- Tienda: Ventas
    CREATE TABLE IF NOT EXISTS tienda_venta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo_cliente ENUM('asociado','externo') NOT NULL,
        asociado_cedula VARCHAR(20) NULL,
        cliente_id INT NULL,
        usuario_id INT NULL,
        total DECIMAL(12,2) NOT NULL DEFAULT 0,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_tienda_venta_tipo (tipo_cliente),
        KEY idx_tienda_venta_asoc (asociado_cedula),
        KEY idx_tienda_venta_cliente (cliente_id)
    );
    CREATE TABLE IF NOT EXISTS tienda_venta_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venta_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(12,2) NOT NULL,
        subtotal DECIMAL(12,2) NOT NULL,
        compra_imei_id INT NULL,
        -- CONSTRAINT fk_tienda_venta FOREIGN KEY (venta_id) REFERENCES tienda_venta(id),
        -- CONSTRAINT fk_tienda_venta_prod FOREIGN KEY (producto_id) REFERENCES tienda_producto(id),
        -- CONSTRAINT fk_tienda_venta_imei FOREIGN KEY (compra_imei_id) REFERENCES tienda_compra_imei(id),
        KEY idx_tienda_venta_id (venta_id)
    );
    CREATE TABLE IF NOT EXISTS tienda_venta_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venta_id INT NOT NULL,
        tipo ENUM('efectivo','bold','qr','sifone','reversion') NOT NULL,
        monto DECIMAL(12,2) NOT NULL,
        numero_credito_sifone VARCHAR(50) NULL,
        pago_anterior_id INT NULL,
        -- CONSTRAINT fk_tienda_venta_pago FOREIGN KEY (venta_id) REFERENCES tienda_venta(id),
        KEY idx_tienda_pago_tipo (tipo)
    );
    -- Tienda: Reversiones
    CREATE TABLE IF NOT EXISTS tienda_reversion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venta_detalle_id INT NOT NULL,
        motivo TEXT NULL,
        puede_revender BOOLEAN DEFAULT FALSE,
        usuario_id INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        -- CONSTRAINT fk_tienda_rev_det FOREIGN KEY (venta_detalle_id) REFERENCES tienda_venta_detalle(id)
    );

-- Oficina: Comisiones entre asociados (referidor y referido)
    CREATE TABLE IF NOT EXISTS control_comisiones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asociado_inicial_cedula VARCHAR(20) NOT NULL,
        asociado_referido_cedula VARCHAR(20) NOT NULL,
        fecha_comision DATE NOT NULL,
        valor_ganado DECIMAL(12,2) NOT NULL,
        observaciones TEXT NULL,
        creado_por INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ini (asociado_inicial_cedula),
        KEY idx_ref (asociado_referido_cedula),
        KEY idx_fecha (fecha_comision)
    );

    -- Tabla para control de tasas de interés de créditos
    CREATE TABLE IF NOT EXISTS control_tasas_creditos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_credito VARCHAR(100) NOT NULL,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NULL,
        limite_meses INT NOT NULL,
        tasa DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
        seguro_vida DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
        seguro_deudores DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
        estado_activo BOOLEAN DEFAULT TRUE,
        creado_por INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_por INT NULL,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
    );

    -- Tabla para control de tasas de productos
    CREATE TABLE IF NOT EXISTS control_tasas_productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NULL,
        tasa DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
        estado_activo BOOLEAN DEFAULT TRUE,
        creado_por INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_por INT NULL,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL
    );

    -- Triggers para control_tasas_creditos
    DROP TRIGGER IF EXISTS control_tasas_creditos_bu;
    CREATE TRIGGER control_tasas_creditos_bu BEFORE UPDATE ON control_tasas_creditos
    FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    
    DROP TRIGGER IF EXISTS control_tasas_creditos_bi;
    CREATE TRIGGER control_tasas_creditos_bi BEFORE INSERT ON control_tasas_creditos
    FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

    -- Triggers para control_tasas_productos
    DROP TRIGGER IF EXISTS control_tasas_productos_bu;
    CREATE TRIGGER control_tasas_productos_bu BEFORE UPDATE ON control_tasas_productos
    FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    
    DROP TRIGGER IF EXISTS control_tasas_productos_bi;
    CREATE TRIGGER control_tasas_productos_bi BEFORE INSERT ON control_tasas_productos
    FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

    -- Tabla para control de cláusulas
    CREATE TABLE IF NOT EXISTS control_clausulas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT NOT NULL,
        parametros TEXT NOT NULL,
        requiere_archivo BOOLEAN DEFAULT FALSE,
        estado_activo BOOLEAN DEFAULT TRUE,
        creado_por INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_por INT NULL,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
        KEY idx_nombre (nombre),
        KEY idx_activo (estado_activo)
    );

    -- Tabla para asignación de cláusulas a asociados
    CREATE TABLE IF NOT EXISTS control_asignacion_asociado_clausula (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asociado_cedula VARCHAR(20) NOT NULL,
        clausula_id INT NOT NULL,
        monto_mensual INT NOT NULL,
        fecha_inicio DATE NOT NULL,
        meses_vigencia INT NOT NULL,
        parametros TEXT NULL,
        archivo_ruta VARCHAR(255) NULL,
        estado_activo BOOLEAN DEFAULT TRUE,
        creado_por INT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_por INT NULL,
        fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (clausula_id) REFERENCES control_clausulas(id),
        KEY idx_asociado (asociado_cedula),
        KEY idx_clausula (clausula_id),
        KEY idx_activo (estado_activo),
        KEY idx_fecha_inicio (fecha_inicio)
    );

    -- Triggers para control_clausulas
    DROP TRIGGER IF EXISTS control_clausulas_bu;
    CREATE TRIGGER control_clausulas_bu BEFORE UPDATE ON control_clausulas
    FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    
    DROP TRIGGER IF EXISTS control_clausulas_bi;
    CREATE TRIGGER control_clausulas_bi BEFORE INSERT ON control_clausulas
    FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- Triggers para control_asignacion_asociado_clausula
DROP TRIGGER IF EXISTS control_asignacion_asociado_clausula_bu;
CREATE TRIGGER control_asignacion_asociado_clausula_bu BEFORE UPDATE ON control_asignacion_asociado_clausula
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;

DROP TRIGGER IF EXISTS control_asignacion_asociado_clausula_bi;
CREATE TRIGGER control_asignacion_asociado_clausula_bi BEFORE INSERT ON control_asignacion_asociado_clausula
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- =============================================
-- MÓDULO CRÉDITOS DOCS - GESTIÓN DE CRÉDITOS
-- =============================================

-- Tabla principal de solicitudes de crédito
CREATE TABLE IF NOT EXISTS credito_docs_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_solicitud VARCHAR(20) UNIQUE NOT NULL,
    tipo_solicitante ENUM('estudiante', 'empleado_descuento_nomina', 'empleado_sin_descuento', 'independiente', 'pensionado_descuento_libranza', 'pensionado_sin_descuento_libranza') NOT NULL,
    
    -- Datos básicos del solicitante
    nombre_solicitante VARCHAR(255) NOT NULL,
    numero_identificacion VARCHAR(20) NOT NULL,
    numero_telefono VARCHAR(15) NOT NULL,
    correo_electronico VARCHAR(255) NOT NULL,
    monto_deseado INT NOT NULL,
    numero_cuotas_deseadas INT NOT NULL,
    
    -- Estado y progreso
    estado ENUM('solicitado', 'revisado', 'con_estudio', 'desembolsado', 'rechazado') DEFAULT 'solicitado',
    etapa_actual ENUM('creacion', 'revision', 'estudio', 'final') DEFAULT 'creacion',
    
    -- Campos específicos de etapa final
    numero_credito_sifone INT NULL,
    valor_real_desembolso DECIMAL(15,2) NULL,
    fecha_desembolso DATE NULL,
    plazo_desembolso INT NULL,
    
    -- Campos de codeudor
    desea_codeudor BOOLEAN NOT NULL DEFAULT FALSE,
    tipo_codeudor ENUM('no_necesita', 'codeudor_dependiente', 'codeudor_pensionado') NULL,
    
    -- Comentarios de rechazo
    comentarios_rechazo TEXT NULL,
    
    -- Auditoría
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_por INT NULL,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
    
    KEY idx_estado (estado),
    KEY idx_etapa (etapa_actual),
    KEY idx_tipo_solicitante (tipo_solicitante),
    KEY idx_numero_solicitud (numero_solicitud)
);

-- Tabla de documentos por etapa
CREATE TABLE IF NOT EXISTS credito_docs_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    etapa ENUM('creacion', 'revision', 'estudio', 'final') NOT NULL,
    tipo_documento VARCHAR(100) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tamaño_archivo INT NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    es_obligatorio BOOLEAN DEFAULT FALSE,
    es_opcional BOOLEAN DEFAULT FALSE,
    es_trato_especial BOOLEAN DEFAULT FALSE,
    aplica_para_tipo_solicitante TEXT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subido_por INT NULL,
    
    -- FOREIGN KEY (solicitud_id) REFERENCES credito_docs_solicitudes(id) ON DELETE CASCADE,
    KEY idx_solicitud_etapa (solicitud_id, etapa),
    KEY idx_tipo_documento (tipo_documento),
    UNIQUE KEY unique_documento_solicitud (solicitud_id, etapa, tipo_documento)
);

-- Tabla de configuración de documentos
CREATE TABLE IF NOT EXISTS credito_docs_configuracion_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etapa ENUM('creacion', 'revision', 'estudio', 'final') NOT NULL,
    tipo_documento VARCHAR(100) NOT NULL,
    nombre_mostrar VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    es_obligatorio BOOLEAN DEFAULT FALSE,
    es_opcional BOOLEAN DEFAULT FALSE,
    es_trato_especial BOOLEAN DEFAULT FALSE,
    aplica_para_tipo_solicitante TEXT NOT NULL,
    validaciones_especiales TEXT NULL,
    orden_display INT DEFAULT 0,
    estado_activo BOOLEAN DEFAULT TRUE,
    
    UNIQUE KEY unique_configuracion (etapa, tipo_documento),
    KEY idx_etapa_activo (etapa, estado_activo)
);

-- Tabla de validaciones especiales
CREATE TABLE IF NOT EXISTS credito_docs_validaciones_especiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    tipo_validacion VARCHAR(100) NOT NULL,
    documentos_requeridos TEXT NOT NULL,
    documentos_subidos TEXT NOT NULL,
    cumple_validacion BOOLEAN DEFAULT FALSE,
    fecha_validacion TIMESTAMP NULL,
    
    -- FOREIGN KEY (solicitud_id) REFERENCES credito_docs_solicitudes(id) ON DELETE CASCADE,
    KEY idx_solicitud_tipo (solicitud_id, tipo_validacion)
);

-- Triggers para credito_docs_solicitudes
DROP TRIGGER IF EXISTS credito_docs_solicitudes_bu;
CREATE TRIGGER credito_docs_solicitudes_bu BEFORE UPDATE ON credito_docs_solicitudes
FOR EACH ROW SET NEW.fecha_actualizacion = CURRENT_TIMESTAMP;

DROP TRIGGER IF EXISTS credito_docs_solicitudes_bi;
CREATE TRIGGER credito_docs_solicitudes_bi BEFORE INSERT ON credito_docs_solicitudes
FOR EACH ROW SET NEW.fecha_actualizacion = COALESCE(NEW.fecha_actualizacion, CURRENT_TIMESTAMP);

-- =============================================
-- DATOS DE CONFIGURACIÓN DE DOCUMENTOS
-- =============================================

-- ETAPA CREACIÓN
INSERT INTO credito_docs_configuracion_documentos (etapa, tipo_documento, nombre_mostrar, descripcion, es_obligatorio, es_opcional, es_trato_especial, aplica_para_tipo_solicitante, orden_display) VALUES
('creacion', 'cedula', 'Cédula (Copia ampliada al 150%)', 'Copia de cédula ampliada al 150%', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 1),
('creacion', 'rut', 'RUT', 'Copia RUT', TRUE, FALSE, FALSE, '["independiente"]', 2),
('creacion', 'camara_comercio', 'Cámara y Comercio', 'Registro mercantil cámara y comercio no mayor a 30 días', FALSE, TRUE, FALSE, '["independiente"]', 3),
('creacion', 'comprobante_pago_1', 'Comprobante de Pago 1', 'Comprobantes de pago 2 últimas quincenas o meses', TRUE, FALSE, FALSE, '["empleado_descuento_nomina", "empleado_sin_descuento", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 4),
('creacion', 'comprobante_pago_2', 'Comprobante de Pago 2', 'Comprobantes de pago 2 últimas quincenas o meses', TRUE, FALSE, FALSE, '["empleado_descuento_nomina", "empleado_sin_descuento", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 5),
('creacion', 'certificacion_laboral', 'Certificación Laboral', 'Menor a 30 días de expedición', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento"]', 6),
('creacion', 'certificado_ingresos_retenciones', 'Certificado de Ingresos y Retenciones', 'Año inmediatamente anterior, para solicitudes mayores a $4,0 MM', FALSE, TRUE, FALSE, '["empleado_descuento_nomina", "empleado_sin_descuento"]', 7),
('creacion', 'soporte_otros_ingresos_1', 'Soporte de Otros Ingresos 1', 'Soporte de otros ingresos', FALSE, TRUE, TRUE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 8),
('creacion', 'soporte_otros_ingresos_2', 'Soporte de Otros Ingresos 2', 'Soporte de otros ingresos', FALSE, TRUE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 9),
('creacion', 'soporte_otros_ingresos_3', 'Soporte de Otros Ingresos 3', 'Soporte de otros ingresos', FALSE, TRUE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 10),
('creacion', 'ingresos_certificados_contador', 'Ingresos Certificados por Contador Público', 'No mayor a 30 días de vigencia, junto con la copia de Tarjeta Profesional y Certificado de Antecedentes Disciplinarios', FALSE, FALSE, TRUE, '["independiente"]', 11),
('creacion', 'declaracion_renta', 'Declaración de Renta', 'Año gravable si está obligado a presentar, de lo contrario adjuntar Certificación juramentada', FALSE, FALSE, TRUE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 12),
('creacion', 'estados_financieros', 'Estados Financieros', 'De dos últimos años firmados por contador público, con copia de matrícula y antecedentes disciplinarios no mayor a 90 días', FALSE, TRUE, FALSE, '["independiente"]', 13),
('creacion', 'certificado_tradicion_inmueble', 'Certificado de Tradición de Inmueble', 'Menor a 60 días de expedición', FALSE, TRUE, FALSE, '["empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 14),
('creacion', 'orden_matricula_universitaria', 'Orden de Matrícula Universitaria', 'Copia orden de matrícula universitaria', TRUE, FALSE, FALSE, '["estudiante"]', 15),
('creacion', 'servicio_publico_domicilio', 'Servicio Público de Domicilio', 'Copia servicio público de domicilio actual', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 16),
('creacion', 'tarjeta_propiedad_vehiculo', 'Tarjeta de Propiedad de Vehículo', 'Copia de tarjeta de propiedad de vehículo', FALSE, TRUE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 17),
('creacion', 'extracto_bancario_1', 'Extracto Bancario 1', 'Extractos bancarios últimos tres meses', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 18),
('creacion', 'extracto_bancario_2', 'Extracto Bancario 2', 'Extractos bancarios últimos tres meses', FALSE, TRUE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 19),
('creacion', 'extracto_bancario_3', 'Extracto Bancario 3', 'Extractos bancarios últimos tres meses', FALSE, TRUE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 20);

-- ETAPA REVISIÓN
INSERT INTO credito_docs_configuracion_documentos (etapa, tipo_documento, nombre_mostrar, descripcion, es_obligatorio, es_opcional, es_trato_especial, aplica_para_tipo_solicitante, validaciones_especiales, orden_display) VALUES
('revision', 'estudio_datacredito', 'Estudio en Datacredito', 'Estudio en Datacredito', FALSE, FALSE, TRUE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', '{"requiere_al_menos_uno": ["estudio_datacredito", "certificado_aportes_trebol"]}', 1),
('revision', 'certificado_aportes_trebol', 'Certificado de Aportes Trébol', 'Certificado de Aportes Trébol', FALSE, FALSE, TRUE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', '{"requiere_al_menos_uno": ["estudio_datacredito", "certificado_aportes_trebol"]}', 2),
('revision', 'formato_fondeo_independientes', 'Formato de Fondeo para Independientes', 'Formato de fondeo para independientes', TRUE, FALSE, FALSE, '["independiente"]', '{"condicion": "solo_si_no_tiene_contador_ni_renta_ni_estados"}', 3),
('revision', 'simulacion_credito_sifone', 'Simulación de Crédito Sifone', 'Simulación de Crédito Sifone', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', NULL, 4);

-- ETAPA ESTUDIO
INSERT INTO credito_docs_configuracion_documentos (etapa, tipo_documento, nombre_mostrar, descripcion, es_obligatorio, es_opcional, es_trato_especial, aplica_para_tipo_solicitante, orden_display) VALUES
('estudio', 'evaluacion_credito_cartera', 'Evaluación del Crédito por el Área de Cartera', 'Evaluación del crédito por el área de cartera', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 1),
('estudio', 'cedula_codeudor', 'Cédula de Codeudor', 'Cédula de codeudor', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 2),
('estudio', 'codeudor_comprobante_1', 'Codeudor Comprobante 1', 'Comprobantes de pago 2 últimas quincenas o meses', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 3),
('estudio', 'codeudor_comprobante_2', 'Codeudor Comprobante 2', 'Comprobantes de pago 2 últimas quincenas o meses', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 4),
('estudio', 'codeudor_certificacion_laboral', 'Codeudor Certificación Laboral', 'Menor a 30 días de expedición', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 5);

-- ETAPA FINAL
INSERT INTO credito_docs_configuracion_documentos (etapa, tipo_documento, nombre_mostrar, descripcion, es_obligatorio, es_opcional, es_trato_especial, aplica_para_tipo_solicitante, orden_display) VALUES
('final', 'estudio_credito_sifone', 'Estudio de Crédito Sifone', 'Estudio de Crédito Sifone', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 1),
('final', 'documento_pagare', 'Documento Pagaré', 'Documento Pagaré', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 2),
('final', 'documento_libranza', 'Documento Libranza para Crédito', 'Documento Libranza para Crédito', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 3),
('final', 'plan_pagos', 'Plan de Pagos', 'Plan de Pagos', TRUE, FALSE, FALSE, '["estudiante", "empleado_descuento_nomina", "empleado_sin_descuento", "independiente", "pensionado_descuento_libranza", "pensionado_sin_descuento_libranza"]', 4);
