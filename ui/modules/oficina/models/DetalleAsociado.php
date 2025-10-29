<?php
require_once __DIR__ . '/../../../config/database.php';

class DetalleAsociado {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function getAsociadoInfo(string $cedula) {
        // Prioriza la vista consolidada; si no hay fila, cae al origen legacy
        $sqlV = "SELECT cedula,
                        nombre_completo AS nombre,
                        celular        AS celula,
                        email          AS mail,
                        ciudad,
                        direccion      AS direcc,
                        fecha_nacimiento AS fecnac,
                        fecha_afiliacion AS fechai,
                        COALESCE(aportes_totales,0) AS aporte
                 FROM sifone_resumen_asociados_vw WHERE cedula = ?";
        $stmtV = $this->conn->prepare($sqlV);
        $stmtV->execute([$cedula]);
        $row = $stmtV->fetch();
        if ($row) return $row;
        // Fallback
        $sqlL = "SELECT cedula, nombre, celula, mail, ciudad, direcc, aporte, fecnac, fechai FROM sifone_asociados WHERE cedula = ?";
        $stmtL = $this->conn->prepare($sqlL);
        $stmtL->execute([$cedula]);
        return $stmtL->fetch();
    }

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

    public function getCreditos(string $cedula): array {
		$sql = "SELECT 
					a.numero AS numero_credito,
					a.tipopr AS tipo_prestamo,
					a.plazo,
					a.carter AS deuda_capital,
					a.valorc AS cuota,
					dv.cuota AS valor_cuota,
					dv.cuotas_pendientes AS cuotas_pendientes,
					dv.fecha_emision AS fecha_inicio,
					dv.fecha_vencimiento AS fecha_vencimiento,
					dv.desembolso_inicial,
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
					   AND dv.codeudor = 0  -- Solo el titular, no el codeudor
				LEFT JOIN sifone_datacredito_vw dvco
					ON CAST(dvco.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
				   AND dvco.codeudor = 1  -- Solo el codeudor
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
							   AND d.codeudor = 0  -- Solo datos del titular para cálculos
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

	public function getCreditosFinalizados(string $cedula): array {
		$sql = "SELECT 
					numero_credito,
					fecha_pago,
					desembolso_inicial,
					saldo_capital,
					cuota,
					cuotas_iniciales,
					cuotas_pendientes
				FROM sifone_datacredito_vw
				WHERE CAST(cedula AS UNSIGNED) = CAST(? AS UNSIGNED)
				  AND estado_credito = 5 AND codeudor = 0
				ORDER BY numero_credito";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute([$cedula]);
		return $stmt->fetchAll();
	}

    public function getAsignaciones(string $cedula): array {
        $sql = "SELECT ap.id, ap.cedula, ap.producto_id, ap.dia_pago, ap.monto_pago, ap.estado_activo,
                       p.nombre as producto_nombre, p.valor_minimo, p.valor_maximo
                FROM control_asignacion_asociado_producto ap
                INNER JOIN control_productos p ON p.id = ap.producto_id
                WHERE ap.cedula = ?
                ORDER BY p.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll();
    }

    public function getActiveProducts(): array {
        $stmt = $this->conn->query("SELECT id, nombre, valor_minimo, valor_maximo FROM control_productos WHERE estado_activo = TRUE ORDER BY nombre");
        return $stmt->fetchAll();
    }

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

    public function assignProduct(string $cedula, int $productoId, int $diaPago, float $montoPago): array {
        $range = $this->getProductRange($productoId);
        if (!$range) return ['success'=>false,'message'=>'Producto inválido'];
        if ($montoPago < (float)$range['valor_minimo'] || $montoPago > (float)$range['valor_maximo']) {
            return ['success'=>false,'message'=>'Monto fuera del rango permitido'];
        }
        $sql = "INSERT INTO control_asignacion_asociado_producto (cedula, producto_id, dia_pago, monto_pago, estado_activo)
                VALUES (?, ?, ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE dia_pago = VALUES(dia_pago), monto_pago = VALUES(monto_pago), estado_activo = TRUE, fecha_actualizacion = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute([$cedula, $productoId, $diaPago, $montoPago]);
        if ($ok) return ['success'=>true,'message'=>'Asignación creada/actualizada'];
        return ['success'=>false,'message'=>'No se pudo asignar producto'];
    }

    public function getProductRange(int $productoId) {
        $stmt = $this->conn->prepare("SELECT valor_minimo, valor_maximo FROM control_productos WHERE id = ?");
        $stmt->execute([$productoId]);
        return $stmt->fetch();
    }

    public function updateAssignment(int $id, int $diaPago, float $montoPago, bool $estado): array {
        $stmt = $this->conn->prepare("SELECT producto_id FROM control_asignacion_asociado_producto WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return ['success'=>false,'message'=>'Asignación no encontrada'];
        $range = $this->getProductRange((int)$row['producto_id']);
        if (!$range) return ['success'=>false,'message'=>'Producto inválido'];
        if ($montoPago < (float)$range['valor_minimo'] || $montoPago > (float)$range['valor_maximo']) {
            return ['success'=>false,'message'=>'Monto fuera del rango permitido'];
        }
        $estadoInt = $estado ? 1 : 0;
        $up = $this->conn->prepare("UPDATE control_asignacion_asociado_producto SET dia_pago = ?, monto_pago = ?, estado_activo = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
        $ok = $up->execute([$diaPago, $montoPago, $estadoInt, $id]);
        if ($ok) return ['success'=>true,'message'=>'Asignación actualizada'];
        return ['success'=>false,'message'=>'No se pudo actualizar la asignación'];
    }

    public function deleteAssignment(int $id): array {
        $del = $this->conn->prepare("DELETE FROM control_asignacion_asociado_producto WHERE id = ?");
        $ok = $del->execute([$id]);
        if ($ok) return ['success'=>true,'message'=>'Asignación eliminada'];
        return ['success'=>false,'message'=>'No se pudo eliminar la asignación'];
    }

    public function getMovimientosTributarios(string $cedula): array {
        $sql = "SELECT fecha, hora, debito, credit, numero, cuenta, detall
                FROM sifone_movimientos_tributarios
                WHERE cedula = ?
                ORDER BY fecha DESC, hora DESC, cuenta DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll();
    }
}
