DROP VIEW IF EXISTS sifone_aportes_ordinarios_vw;
CREATE VIEW sifone_aportes_ordinarios_vw AS
SELECT CAST(cedula AS CHAR) AS cedula, SUM(ABS(COALESCE(salant, 0))) AS aportes_ordinarios
FROM sifone_balance_prueba
WHERE nombre = 'aportes ordinarios'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_aportes_incentivos_vw;
CREATE VIEW sifone_aportes_incentivos_vw AS
SELECT m.cedula
    , SUM(FLOOR((POW((1 + (t.tasa / 100)), (1/12)) - 1) * ABS(m.credit))) AS aportes_incentivos
FROM sifone_movimientos_tributarios AS m
         INNER JOIN control_tasas_productos AS t
                    ON t.producto_id = 1 -- APORTES INCENTIVOS
                        AND m.fecham BETWEEN t.fecha_inicio AND t.fecha_fin
WHERE cuenta = '31050501'
GROUP BY m.cedula
;

DROP VIEW IF EXISTS sifone_aportes_sociales_vw;
CREATE VIEW sifone_aportes_sociales_vw AS
SELECT CAST(cedula AS CHAR) AS cedula, SUM(ABS(COALESCE(salant, 0))) AS aportes_sociales
FROM sifone_balance_prueba
WHERE nombre = 'APORTES SOCIALES 2'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_aportes_revalorizaciones_vw;
CREATE VIEW sifone_aportes_revalorizaciones_vw AS
SELECT CAST(cedula AS CHAR) AS cedula, SUM(ABS(COALESCE(salant, 0))) AS aportes_revalorizaciones
FROM sifone_balance_prueba
WHERE nombre = 'Revalorizacion Aportes'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_plan_futuro_vw;
CREATE VIEW sifone_plan_futuro_vw AS
SELECT CAST(cedula AS CHAR) AS cedula, SUM(ABS(COALESCE(salant, 0))) AS plan_futuro
FROM sifone_balance_prueba
WHERE nombre = 'PLAN FUTURO'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_bolsillos_vw;
CREATE VIEW sifone_bolsillos_vw AS
SELECT CAST(m.cedula AS CHAR)                                                             AS cedula
     , SUM(m.credit)                                                                      AS bolsillos
     , SUM(FLOOR((POW((1 + (COALESCE(t.tasa, 0) / 100)), (1 / 12)) - 1) * ABS(m.credit))) AS bolsillos_incentivos
FROM `sifone_movimientos_tributarios` AS m
         LEFT JOIN control_tasas_productos AS t
                   ON t.producto_id = 2 -- BOLSILLOS
                       AND m.fecham BETWEEN t.fecha_inicio AND t.fecha_fin
WHERE cuenta = '42309501'
  AND fecham >= '2025-06-09'
  AND credit > 0
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_comisiones_vw;
CREATE VIEW sifone_comisiones_vw AS
SELECT CAST(asociado_inicial_cedula AS CHAR) AS cedula
     , SUM(valor_ganado) AS comisiones
FROM control_comisiones
GROUP BY asociado_inicial_cedula
;

DROP VIEW IF EXISTS sifone_resumen_asociados_vw;
CREATE VIEW sifone_resumen_asociados_vw AS
SELECT sa.cedula                                    AS cedula,
       ca.estado_activo                             AS estado,
       -- Información Personal
       sa.fechai                                    AS fecha_afiliacion,
       sa.nombre                                    AS nombre_completo,
       sa.fecnac                                    AS fecha_nacimiento,
       -- Información Contacto
       sa.celula                                    AS celular,
       sa.mail                                      AS email,
       sa.direcc                                    AS direccion,
       sa.ciudad                                    AS ciudad,
       -- información monetaria
       COALESCE(sbp_ao.aportes_ordinarios, 0)       AS aportes_ordinarios,
       COALESCE(sbp_as.aportes_sociales, 0)         AS aportes_sociales,
       (COALESCE(sbp_ao.aportes_ordinarios, 0) +
        COALESCE(sbp_as.aportes_sociales, 0))       AS aportes_totales,
       COALESCE(sbp_ai.aportes_incentivos, 0)       AS aportes_incentivos,
       COALESCE(sbp_ar.aportes_revalorizaciones, 0) AS aportes_revalorizaciones,
       COALESCE(sbp_pf.plan_futuro, 0)              AS plan_futuro,
       COALESCE(smt_b.bolsillos, 0)                 AS bolsillos,
       COALESCE(smt_b.bolsillos_incentivos, 0)      AS bolsillos_incentivos,
       COALESCE(smt_c.comisiones, 0)                AS comisiones,
       COALESCE(sbp_ao.aportes_ordinarios, 0)       +
       COALESCE(sbp_as.aportes_sociales, 0)         +
       COALESCE(sbp_ar.aportes_revalorizaciones, 0) +
       COALESCE(sbp_pf.plan_futuro, 0)              +
       COALESCE(smt_b.bolsillos, 0)                 AS total_saldos_favor,
       COALESCE(sbp_ai.aportes_incentivos, 0)       +
       COALESCE(smt_b.bolsillos_incentivos, 0)      +
       COALESCE(smt_c.comisiones, 0)				        AS total_incentivos
FROM sifone_asociados AS sa
         LEFT JOIN control_asociados AS ca ON CAST(ca.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_aportes_ordinarios_vw AS sbp_ao ON CAST(sbp_ao.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_aportes_sociales_vw AS sbp_as ON CAST(sbp_as.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_aportes_incentivos_vw AS sbp_ai ON CAST(sbp_ai.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_aportes_revalorizaciones_vw AS sbp_ar ON CAST(sbp_ar.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_plan_futuro_vw AS sbp_pf ON CAST(sbp_pf.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_bolsillos_vw AS smt_b ON CAST(smt_b.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
         LEFT JOIN sifone_comisiones_vw AS smt_c ON CAST(smt_c.cedula AS CHAR) = CAST(sa.cedula AS CHAR)
;
