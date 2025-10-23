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
                    m.sdomor AS saldo_mora,
                    m.diav AS dias_mora,
                    COALESCE(m.fechap, dv.fecha_pago) AS fecha_pago,
                    dv.fecha_emision AS fecha_inicio,
                    dv.fecha_vencimiento,
                    dv.saldo_capital,
                    -- Datos de codeudor desde JOIN específico
                    dvco.nombre AS codeudor_nombre,
                    dvco.celular AS codeudor_celular,
                    dvco.email AS codeudor_email,
                    dvco.direccion AS codeudor_direccion
                FROM sifone_cartera_aseguradora a
                LEFT JOIN sifone_cartera_mora m
                    ON m.cedula = a.cedula AND m.presta = a.numero
                LEFT JOIN sifone_datacredito_vw dv
                    ON CAST(dv.cedula AS UNSIGNED) = CAST(a.cedula AS UNSIGNED)
                   AND CAST(dv.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
                LEFT JOIN sifone_datacredito_vw dvco
                    ON CAST(dvco.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
                   AND dvco.codeudor = 1
                WHERE a.cedula = ?
                ORDER BY a.numero";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        $creditos = $stmt->fetchAll() ?: [];
        
        // Agregar campos adicionales que no están en la vista
        foreach ($creditos as &$credito) {
            // Campos que no están disponibles en la vista, usar valores por defecto
            $credito['seguro_vida'] = 0;
            $credito['seguro_deudores'] = 0;
            $credito['interes'] = 0;
            $credito['monto_cobranza'] = 0;
        }
        
        return $creditos;
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
        $sql = "SELECT 
                    COALESCE(aportes_totales, 0) AS aportes_totales,
                    COALESCE(aportes_incentivos, 0) AS aportes_incentivos,
                    COALESCE(aportes_revalorizaciones, 0) AS aportes_revalorizaciones,
                    COALESCE(plan_futuro, 0) AS plan_futuro,
                    COALESCE(bolsillos, 0) AS bolsillos,
                    COALESCE(bolsillos_incentivos, 0) AS bolsillos_incentivos,
                    COALESCE(comisiones, 0) AS comisiones,
                    COALESCE(total_saldos_favor, 0) AS total_saldos_favor,
                    COALESCE(total_incentivos, 0) AS total_incentivos
                FROM sifone_resumen_asociados_vw 
                WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetch() ?: [];
    }

    // Método fallback para balance de prueba (igual que DetalleAsociado)
    public function getBalancePruebaMonetarios(string $cedula): array {
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
            'revalorizacion aportes' => 'aportes_revalorizaciones',
            'plan futuro' => 'plan_futuro',
            'aportes sociales 2' => 'aportes_sociales_2',
        ];
        $out = ['aportes_revalorizaciones'=>0.0,'plan_futuro'=>0.0,'aportes_sociales_2'=>0.0];
        foreach ($rows as $r) {
            $key = strtolower(trim((string)$r['nombre']));
            if (isset($map[$key])) { $out[$map[$key]] = (float)$r['valor']; }
        }
        return $out;
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