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

    public function getCreditos(string $cedula): array {
        $sql = "SELECT 
                    a.numero AS numero_credito,
                    a.tipopr AS tipo_prestamo,
                    a.plazo,
                    a.carter AS deuda_capital,
                    a.valorc AS cuota,
                    dv.cuota AS valor_cuota,
                    dv.cuotas_pendientes AS cuotas_pendientes,
                    m.sdomor AS saldo_mora,
                    m.diav AS dias_mora,
                    COALESCE(m.fechap, dv.fecha_pago) AS fecha_pago
                FROM sifone_cartera_aseguradora a
                LEFT JOIN sifone_cartera_mora m
                    ON m.cedula = a.cedula AND m.presta = a.numero
                LEFT JOIN sifone_datacredito_vw dv
                    ON CAST(dv.cedula AS UNSIGNED) = CAST(a.cedula AS UNSIGNED)
                   AND CAST(dv.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
                WHERE a.cedula = ?
                ORDER BY a.numero";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll() ?: [];
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

    public function getBalancePrueba(string $cedula): array {
        $targets = ['Revalorizacion Aportes','PLAN FUTURO','APORTES SOCIALES 2'];
        $place = implode(',', array_fill(0, count($targets), '?'));
        $sql = "SELECT LOWER(nombre) AS nombre, SUM(ABS(COALESCE(salant,0))) AS valor
                FROM sifone_balance_prueba
                WHERE cedula = ? AND nombre IN ($place)
                GROUP BY nombre";
        $stmt = $this->conn->prepare($sql);
        $params = array_merge([$cedula], $targets);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $map = [
            'revalorizacion aportes' => 'revalorizacion_aportes',
            'plan futuro' => 'plan_futuro',
            'aportes sociales 2' => 'aportes_sociales_2',
        ];
        $out = ['revalorizacion_aportes'=>0.0,'plan_futuro'=>0.0,'aportes_sociales_2'=>0.0];
        foreach ($rows as $r) {
            $key = strtolower(trim((string)$r['nombre']));
            if (isset($map[$key])) { $out[$map[$key]] = (float)$r['valor']; }
        }
        return $out;
    }
}
?>


