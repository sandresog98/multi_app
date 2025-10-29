DROP VIEW IF EXISTS sifone_datacredito_vw;
CREATE VIEW sifone_datacredito_vw AS
    SELECT
        b AS cedula,
        c AS numero_credito,
        d AS nombre,
        f AS fecha_emision,
        DATE_ADD(g, INTERVAL 1 MONTH) AS fecha_vencimiento,
        h as codeudor,
        m AS estado_credito,
        q AS fecha_pago,
        x AS desembolso_inicial,
        y AS saldo_capital,
        aa AS cuota,
        ac AS cuotas_iniciales,
        ad AS cuotas_pendientes,
        az AS direccion,
        ba AS email,
        bb AS celular
    FROM sifone_datacredito
;