DROP VIEW IF EXISTS sifone_aportes_sociales_vw;
CREATE VIEW sifone_aportes_sociales_vw AS
SELECT cedula, SUM(ABS(COALESCE(salant, 0))) AS aportes_sociales
FROM sifone_balance_prueba
WHERE nombre = 'APORTES SOCIALES 2'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_aportes_revalorizaciones_vw;
CREATE VIEW sifone_aportes_revalorizaciones_vw AS
SELECT cedula, SUM(ABS(COALESCE(salant, 0))) AS aportes_revalorizaciones
FROM sifone_balance_prueba
WHERE nombre = 'Revalorizacion Aportes'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_plan_futuro_vw;
CREATE VIEW sifone_plan_futuro_vw AS
SELECT cedula, SUM(ABS(COALESCE(salant, 0))) AS plan_futuro
FROM sifone_balance_prueba
WHERE nombre = 'PLAN FUTURO'
GROUP BY cedula
;

DROP VIEW IF EXISTS sifone_bolsillos_vw;
CREATE VIEW sifone_bolsillos_vw AS
SELECT cedula, SUM(credit) AS bolsillos
FROM `sifone_movimientos_tributarios`
WHERE cuenta = '42309501'
  AND fecham >= '2025-06-09'
  AND credit > 0
GROUP BY cedula;
;

DROP VIEW IF EXISTS sifone_comisiones_vw;
CREATE VIEW sifone_comisiones_vw AS
SELECT asociado_inicial_cedula AS cedula
     , SUM(valor_ganado) AS comisiones
FROM control_comisiones
GROUP BY cedula
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
       COALESCE(sa.aporte, 0)                       AS aportes_ordinarios,
       COALESCE(sbp_as.aportes_sociales, 0)         AS aportes_sociales,
       (COALESCE(sa.aporte, 0) +
        COALESCE(sbp_as.aportes_sociales, 0))       AS aportes_totales,
       0                                            AS aportes_incentivos,
       COALESCE(sbp_ar.aportes_revalorizaciones, 0) AS aportes_revalorizaciones,
       COALESCE(sbp_pf.plan_futuro, 0)              AS plan_futuro,
       COALESCE(smt_b.bolsillos, 0)                 AS bolsillos,
       0                                            AS bolsillos_incentivos,
       COALESCE(smt_c.comisiones, 0)                AS comisiones
FROM sifone_asociados AS sa
         LEFT JOIN control_asociados AS ca ON ca.cedula = sa.cedula
         LEFT JOIN sifone_aportes_sociales_vw AS sbp_as ON sbp_as.cedula = sa.cedula
         LEFT JOIN sifone_aportes_revalorizaciones_vw AS sbp_ar ON sbp_ar.cedula = sa.cedula
         LEFT JOIN sifone_plan_futuro_vw AS sbp_pf ON sbp_pf.cedula = sa.cedula
         LEFT JOIN sifone_bolsillos_vw AS smt_b ON smt_b.cedula = sa.cedula
         LEFT JOIN sifone_comisiones_vw AS smt_c ON smt_c.cedula = sa.cedula
;
