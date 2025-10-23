<?php
require_once __DIR__ . '/../config/database.php';

class ResumenFinanciero {
    private $conn;
    public function __construct() { $this->conn = cx_getConnection(); }

    public function getInfoBasica(string $cedula): array {
        $stmt = $this->conn->prepare("SELECT cedula, nombre, mail, celula, ciudad, direcc, aporte, fecnac, fechai FROM sifone_asociados WHERE cedula = ?");
        $stmt->execute([$cedula]);
        return $stmt->fetch() ?: [];
    }

    public function getComisiones(string $cedula): array {
        $sql = "SELECT 
                    COALESCE(comisiones, 0) AS comisiones
                FROM sifone_resumen_asociados_vw 
                WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        $result = $stmt->fetch();
        return [
            'comisiones' => (float)($result['comisiones'] ?? 0)
        ];
    }

    public function getCreditos(string $cedula): array {
        $sql = "SELECT 
                    a.numero AS numero_credito,
                    a.tipopr AS tipo_prestamo,
                    a.plazo,
                    a.carter AS deuda_capital,
                    a.valorc AS cuota,
                    dv.cuota AS valor_cuota,
                    dv.cuotas_pendientes AS cuotas_pendientes,
                    dv.desembolso_inicial,
                    dv.fecha_emision AS fecha_inicio,
                    dv.fecha_vencimiento AS fecha_vencimiento,
                    dv.saldo_capital,
                    -- Datos de codeudor
                    dvco.nombre AS codeudor_nombre,
                    dvco.celular AS codeudor_celular,
                    dvco.email AS codeudor_email,
                    dvco.direccion AS codeudor_direccion,
                    m.sdomor AS saldo_mora,
                    m.diav AS dias_mora,
                    COALESCE(m.fechap, dv.fecha_pago) AS fecha_pago,
                    CASE 
                        WHEN m.diav IS NULL THEN 0
                        WHEN m.diav > 60 THEN ROUND(a.valorc * 0.08, 2)
                        WHEN m.diav > 50 THEN ROUND(a.valorc * 0.06, 2)
                        WHEN m.diav > 40 THEN ROUND(a.valorc * 0.05, 2)
                        WHEN m.diav > 30 THEN ROUND(a.valorc * 0.04, 2)
                        WHEN m.diav > 20 THEN ROUND(a.valorc * 0.03, 2)
                        WHEN m.diav > 11 THEN ROUND(a.valorc * 0.02, 2)
                        ELSE 0
                    END AS monto_cobranza,
                    -- Nuevos campos de seguros e intereses
                    COALESCE(tasas.seguro_vida, 0) AS seguro_vida,
                    COALESCE(tasas.seguro_deudores, 0) AS seguro_deudores,
                    COALESCE(tasas.interes, 0) AS interes
                FROM sifone_cartera_aseguradora a
                LEFT JOIN sifone_cartera_mora m
                    ON m.cedula = a.cedula AND m.presta = a.numero
                LEFT JOIN sifone_datacredito_vw dv
                    ON CAST(dv.cedula AS UNSIGNED) = CAST(a.cedula AS UNSIGNED)
                   AND CAST(dv.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
                LEFT JOIN sifone_datacredito_vw dvco
                    ON CAST(dvco.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
                   AND dvco.codeudor = 1
                LEFT JOIN (
                    SELECT c.cedula,
                           c.numero,
                           d.saldo_capital,
                           FLOOR(((d.saldo_capital * (t.seguro_vida / 10)) / 30) *
                                 DATEDIFF(CURRENT_DATE, COALESCE(d.fecha_pago, d.fecha_emision) - INTERVAL 1 MONTH)) AS seguro_vida,
                           FLOOR(((d.saldo_capital * (t.seguro_deudores / 100)) / 30) *
                                 DATEDIFF(CURRENT_DATE, COALESCE(d.fecha_pago, d.fecha_emision) - INTERVAL 1 MONTH)) AS seguro_deudores,
                           FLOOR(((d.saldo_capital * (t.tasa / 100)) / 30) *
                                 DATEDIFF(CURRENT_DATE, COALESCE(d.fecha_pago, d.fecha_emision) - INTERVAL 1 MONTH)) AS interes
                    FROM sifone_cartera_aseguradora AS c
                    INNER JOIN sifone_datacredito_vw AS d
                               ON c.numero = d.numero_credito
                    LEFT JOIN control_tasas_creditos AS t
                              ON c.tipopr = t.nombre_credito
                                  AND c.fechae BETWEEN t.fecha_inicio AND t.fecha_fin
                    WHERE c.cedula = ?
                ) tasas ON tasas.numero = a.numero
                WHERE a.cedula = ?
                ORDER BY a.numero";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula, $cedula]);
        return $stmt->fetchAll();
    }

    public function getAsignaciones(string $cedula): array {
        $sql = "SELECT ap.id, ap.producto_id, ap.dia_pago, ap.monto_pago, ap.estado_activo, p.nombre AS producto_nombre
                FROM control_asignacion_asociado_producto ap
                INNER JOIN control_productos p ON p.id = ap.producto_id
                WHERE ap.cedula = ? AND ap.estado_activo = TRUE
                ORDER BY p.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll() ?: [];
    }

    // Método para obtener información monetaria desde la vista consolidada (igual que DetalleAsociado)
    public function getMonetariosDesdeVista(string $cedula): array {
        // Intentar con diferentes tipos de cédula para mayor compatibilidad
        $sql = "SELECT 
                        COALESCE(aportes_totales,0)           AS aportes_totales,
                        COALESCE(aportes_incentivos,0)        AS aportes_incentivos,
                        COALESCE(aportes_revalorizaciones,0)  AS aportes_revalorizaciones,
                        COALESCE(plan_futuro,0)               AS plan_futuro,
                        COALESCE(bolsillos,0)                  AS bolsillos,
                        COALESCE(bolsillos_incentivos,0)       AS bolsillos_incentivos,
                        COALESCE(comisiones,0)                AS comisiones,
                        COALESCE(total_saldos_favor,0)         AS total_saldos_favor,
                        COALESCE(total_incentivos,0)           AS total_incentivos
                FROM sifone_resumen_asociados_vw 
                WHERE CAST(cedula AS CHAR) = CAST(? AS CHAR)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        $row = $stmt->fetch() ?: [];
        
        // Debug: Log si no se encuentra el usuario
        if (empty($row)) {
            error_log("Usuario no encontrado en sifone_resumen_asociados_vw: $cedula");
        }
        
        return [
            'aportes_totales'         => (float)($row['aportes_totales'] ?? 0),
            'aportes_incentivos'      => (float)($row['aportes_incentivos'] ?? 0),
            'aportes_revalorizaciones'=> (float)($row['aportes_revalorizaciones'] ?? 0),
            'plan_futuro'             => (float)($row['plan_futuro'] ?? 0),
            'bolsillos'               => (float)($row['bolsillos'] ?? 0),
            'bolsillos_incentivos'    => (float)($row['bolsillos_incentivos'] ?? 0),
            'comisiones'              => (float)($row['comisiones'] ?? 0),
            'total_saldos_favor'      => (float)($row['total_saldos_favor'] ?? 0),
            'total_incentivos'        => (float)($row['total_incentivos'] ?? 0),
        ];
    }

    // Método fallback para balance de prueba (igual que DetalleAsociado)
    public function getBalancePruebaMonetarios(string $cedula): array {
        $targets = [
            'aportes ordinarios',
            'Revalorizacion Aportes',
            'PLAN FUTURO',
            'APORTES SOCIALES 2',
        ];

        $placeholders = implode(',', array_fill(0, count($targets), '?'));
        $sql = "SELECT LOWER(nombre) AS nombre, SUM(ABS(COALESCE(salant,0))) AS valor
                FROM sifone_balance_prueba
                WHERE CAST(cedula AS CHAR) = CAST(? AS CHAR) AND nombre IN ($placeholders)
                GROUP BY nombre";
        $stmt = $this->conn->prepare($sql);
        $params = array_merge([$cedula], $targets);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $map = [
            'aportes ordinarios' => 'aportes_totales',
            'revalorizacion aportes' => 'aportes_revalorizaciones',
            'plan futuro' => 'plan_futuro',
            'aportes sociales 2' => 'aportes_incentivos',
        ];

        $result = [
            'aportes_totales' => 0.0,
            'aportes_incentivos' => 0.0,
            'aportes_revalorizaciones' => 0.0,
            'plan_futuro' => 0.0,
            'bolsillos' => 0.0,
            'bolsillos_incentivos' => 0.0,
            'comisiones' => 0.0,
        ];

        foreach ($rows as $r) {
            $keyName = strtolower(trim((string)$r['nombre']));
            if (isset($map[$keyName])) {
                $result[$map[$keyName]] = (float)$r['valor'];
            }
        }
        return $result;
    }

    // Método principal que combina ambos enfoques (igual que DetalleAsociado)
    public function getBalancePrueba(string $cedula): array {
        // Monetarios desde vista consolidada; fallback al cálculo anterior si viene vacío
        $bp = $this->getMonetariosDesdeVista($cedula);
        // Verificar si la vista consolidada tiene datos válidos (al menos aportes totales > 0)
        if (empty($bp['aportes_totales']) || $bp['aportes_totales'] <= 0) {
            $bp = $this->getBalancePruebaMonetarios($cedula);
        }
        return $bp;
    }
}
?>