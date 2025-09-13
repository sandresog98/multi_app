<?php
require_once __DIR__ . '/../../../config/database.php';

class Cobranza {
	private $conn;

	public function __construct() {
		$this->conn = getConnection();
	}

	public function listarAsociadosConMora($filtros = [], $limit = 200, $offset = 0) {
		$where = [];
		$params = [];
		// Filtro único (cedula, nombre, telefono, email)
		if (!empty($filtros['q'])) {
			$where[] = '(m.cedula LIKE ? OR m.nombre LIKE ? OR m.telefo LIKE ? OR m.mail LIKE ?)';
			$q = '%' . $filtros['q'] . '%';
			array_push($params, $q, $q, $q, $q);
		}
		$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

		// Filtro por estado (según max_diav)
		$having = [];
		if (!empty($filtros['estado'])) {
			$estado = strtolower($filtros['estado']);
			if ($estado === 'juridico') {
				$having[] = 'max_diav >= 91';
			} elseif ($estado === 'prejuridico' || $estado === 'prejurídico') {
				$having[] = 'max_diav BETWEEN 61 AND 90';
			} elseif ($estado === 'persuasiva' || $estado === 'persuasivo') {
				$having[] = 'max_diav <= 60';
			}
		}

		// Filtro por rango de última comunicación
		if (!empty($filtros['rango'])) {
			$rango = strtolower($filtros['rango']);
			if ($rango === 'sin') {
				$having[] = 'ultima_comunicacion IS NULL';
			} elseif ($rango === 'muy_reciente') {
				$having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) < 2';
			} elseif ($rango === 'reciente') {
				$having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 2 AND DATEDIFF(CURDATE(), ultima_comunicacion) < 5';
			} elseif ($rango === 'intermedia') {
				$having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 5 AND DATEDIFF(CURDATE(), ultima_comunicacion) < 10';
			} elseif ($rango === 'lejana') {
				$having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 10 AND DATEDIFF(CURDATE(), ultima_comunicacion) <= 20';
			} elseif ($rango === 'muy_lejana') {
				$having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) > 20';
			}
		}
		$havingSql = $having ? ('HAVING ' . implode(' AND ', $having)) : '';

		$sql = "
			SELECT 
				m.cedula,
				MAX(m.nombre) AS nombre,
				MAX(m.diav) AS max_diav,
				SUM(CASE WHEN m.sdomor IS NOT NULL THEN m.sdomor ELSE 0 END) AS total_mora,
				cc.ultima_comunicacion,
				DATEDIFF(CURDATE(), cc.ultima_comunicacion) AS dias_ultima,
				cc.total_comunicaciones,
				car.total_cartera
			FROM sifone_cartera_mora m
			LEFT JOIN (
				SELECT asociado_cedula, MAX(fecha_comunicacion) AS ultima_comunicacion, COUNT(*) AS total_comunicaciones
				FROM cobranza_comunicaciones
				GROUP BY asociado_cedula
			) cc ON cc.asociado_cedula = m.cedula
			LEFT JOIN (
				SELECT cedula, SUM(COALESCE(carter,0)) AS total_cartera
				FROM sifone_cartera_aseguradora
				GROUP BY cedula
			) car ON car.cedula = m.cedula
			$whereSql
			GROUP BY m.cedula, cc.ultima_comunicacion, cc.total_comunicaciones, car.total_cartera
			$havingSql
			ORDER BY max_diav DESC, total_mora DESC
			LIMIT $limit OFFSET $offset
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as &$r) {
			$r['estado_mora'] = $this->clasificarEstadoMora((int)$r['max_diav']);
		}
		return $rows;
	}

	public function listarAsociadosConMoraPaginado($filtros = [], $page = 1, $limit = 20, $sortBy = 'max_diav', $sortDir = 'DESC') {
		$page = max(1, (int)$page);
		$limit = max(1, min(200, (int)$limit));
		$offset = ($page - 1) * $limit;

		$where = [];
		$params = [];
		if (!empty($filtros['q'])) {
			$where[] = '(m.cedula LIKE ? OR m.nombre LIKE ? OR m.telefo LIKE ? OR m.mail LIKE ?)';
			$q = '%' . $filtros['q'] . '%';
			array_push($params, $q, $q, $q, $q);
		}
		$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

		$having = [];
		if (!empty($filtros['estado'])) {
			$estado = strtolower($filtros['estado']);
			if ($estado === 'juridico') { $having[] = 'max_diav >= 91'; }
			elseif ($estado === 'prejuridico' || $estado === 'prejurídico') { $having[] = 'max_diav BETWEEN 61 AND 90'; }
			elseif ($estado === 'persuasiva' || $estado === 'persuasivo') { $having[] = 'max_diav <= 60'; }
		}
		if (!empty($filtros['rango'])) {
			$rango = strtolower($filtros['rango']);
			if ($rango === 'sin') { $having[] = 'ultima_comunicacion IS NULL'; }
			elseif ($rango === 'muy_reciente') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) < 2'; }
			elseif ($rango === 'reciente') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 2 AND DATEDIFF(CURDATE(), ultima_comunicacion) < 5'; }
			elseif ($rango === 'intermedia') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 5 AND DATEDIFF(CURDATE(), ultima_comunicacion) < 10'; }
			elseif ($rango === 'lejana') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 10 AND DATEDIFF(CURDATE(), ultima_comunicacion) <= 20'; }
			elseif ($rango === 'muy_lejana') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) > 20'; }
		}
		$havingSql = $having ? ('HAVING ' . implode(' AND ', $having)) : '';

		$allowedSort = [
			'max_diav' => 'max_diav',
			'total_mora' => 'total_mora',
			'nombre' => 'nombre',
			'ultima_comunicacion' => 'ultima_comunicacion'
		];
		$col = $allowedSort[$sortBy] ?? 'max_diav';
		$dir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

		$base = "
			FROM sifone_cartera_mora m
			LEFT JOIN (
				SELECT asociado_cedula, MAX(fecha_comunicacion) AS ultima_comunicacion, COUNT(*) AS total_comunicaciones
				FROM cobranza_comunicaciones
				GROUP BY asociado_cedula
			) cc ON cc.asociado_cedula = m.cedula
			LEFT JOIN (
				SELECT cedula, SUM(COALESCE(carter,0)) AS total_cartera
				FROM sifone_cartera_aseguradora
				GROUP BY cedula
			) car ON car.cedula = m.cedula
			$whereSql
			GROUP BY m.cedula, cc.ultima_comunicacion, cc.total_comunicaciones, car.total_cartera
			$havingSql
		";

		$dataSql = "SELECT 
			m.cedula,
			MAX(m.nombre) AS nombre,
			MAX(m.diav) AS max_diav,
			SUM(CASE WHEN m.sdomor IS NOT NULL THEN m.sdomor ELSE 0 END) AS total_mora,
			cc.ultima_comunicacion,
			DATEDIFF(CURDATE(), cc.ultima_comunicacion) AS dias_ultima,
			cc.total_comunicaciones,
			car.total_cartera
			$base
			ORDER BY $col $dir
			LIMIT ? OFFSET ?";
		$stmt = $this->conn->prepare($dataSql);
		$paramsExec = array_merge($params, [$limit, $offset]);
		$stmt->execute($paramsExec);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as &$r) { $r['estado_mora'] = $this->clasificarEstadoMora((int)$r['max_diav']); }

		$countSql = "SELECT COUNT(1) AS total FROM (SELECT m.cedula $base) t";
		$countStmt = $this->conn->prepare($countSql);
		$countStmt->execute($params);
		$total = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

		return [
			'items' => $rows,
			'total' => $total,
			'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
			'current_page' => $page
		];
	}

	public function obtenerDetalleMoraPorAsociado($cedula) {
		$sql = "
			SELECT m.presta, COALESCE(a2.tipopr, '') AS tipopr, m.sdomor, m.diav, m.intmora
			FROM sifone_cartera_mora m
			LEFT JOIN sifone_cartera_aseguradora a2 ON a2.numero = m.presta
			WHERE m.cedula = ?
			ORDER BY m.diav DESC
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute([$cedula]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function obtenerDetalleCompletoAsociado($cedula) {
		// Información del asociado
		$sqlAsociado = "SELECT cedula, nombre, celula, mail, ciudad, direcc, aporte, fecnac, fechai FROM sifone_asociados WHERE cedula = ?";
		$stmtAsociado = $this->conn->prepare($sqlAsociado);
		$stmtAsociado->execute([$cedula]);
		$asociado = $stmtAsociado->fetch(PDO::FETCH_ASSOC);

		// Información de créditos
		$sqlCreditos = "SELECT 
							a.numero AS numero_credito,
							a.tipopr AS tipo_prestamo,
							a.plazo,
							a.tasa,
							a.carter AS deuda_capital,
							m.sdomor AS saldo_mora,
							m.diav AS dias_mora,
							m.fechap AS fecha_pago
						FROM sifone_cartera_aseguradora a
						LEFT JOIN sifone_cartera_mora m
							ON m.cedula = a.cedula AND m.presta = a.numero
						WHERE a.cedula = ?
						ORDER BY a.numero";
		$stmtCreditos = $this->conn->prepare($sqlCreditos);
		$stmtCreditos->execute([$cedula]);
		$creditos = $stmtCreditos->fetchAll(PDO::FETCH_ASSOC);

		// Información de productos asignados
		$sqlProductos = "SELECT ap.id, ap.cedula, ap.producto_id, ap.dia_pago, ap.monto_pago, ap.estado_activo,
							   p.nombre as producto_nombre, p.valor_minimo, p.valor_maximo
						FROM control_asignacion_asociado_producto ap
						INNER JOIN control_productos p ON p.id = ap.producto_id
						WHERE ap.cedula = ?
						ORDER BY p.nombre";
		$stmtProductos = $this->conn->prepare($sqlProductos);
		$stmtProductos->execute([$cedula]);
		$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

		return [
			'asociado' => $asociado,
			'creditos' => $creditos,
			'productos' => $productos
		];
	}

	private function clasificarEstadoMora($maxDiav) {
		if ($maxDiav >= 91) return ['label' => 'Jurídico', 'color' => 'danger'];
		if ($maxDiav >= 61) return ['label' => 'Prejurídico', 'color' => 'warning'];
		return ['label' => 'Persuasiva', 'color' => 'primary'];
	}

	public function obtenerKpis() {
		$kpis = [
			"asociados_mora" => 0,
			"total_mora" => 0,
			"promedio_diav" => 0,
			"sin_comunicacion" => 0
		];
		// Asociados, total mora y promedio días
		$sql = "SELECT COUNT(DISTINCT cedula) AS asociados, SUM(COALESCE(sdomor,0)) AS total_mora, AVG(COALESCE(diav,0)) AS prom_diav FROM sifone_cartera_mora";
		$stmt = $this->conn->query($sql);
		$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
		$kpis["asociados_mora"] = (int)($row['asociados'] ?? 0);
		$kpis["total_mora"] = (float)($row['total_mora'] ?? 0);
		$kpis["promedio_diav"] = (float)($row['prom_diav'] ?? 0);
		// Sin comunicación (entre los que tienen mora)
		$sql2 = "SELECT COUNT(1) AS sin_com
				FROM (
					SELECT m.cedula, MAX(cc.fecha_comunicacion) AS ultima
					FROM sifone_cartera_mora m
					LEFT JOIN cobranza_comunicaciones cc ON cc.asociado_cedula = m.cedula
					GROUP BY m.cedula
				) t WHERE t.ultima IS NULL";
		$stmt2 = $this->conn->query($sql2);
		$row2 = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
		$kpis["sin_comunicacion"] = (int)($row2['sin_com'] ?? 0);
		return $kpis;
	}

	public function distribucionMoraBandas() {
		$sql = "
			SELECT 'Persuasiva' AS banda, COUNT(*) AS asociados FROM (
				SELECT cedula, MAX(COALESCE(diav,0)) AS diav FROM sifone_cartera_mora GROUP BY cedula
			) t WHERE diav <= 60
			UNION ALL
			SELECT 'Prejurídico' AS banda, COUNT(*) FROM (
				SELECT cedula, MAX(COALESCE(diav,0)) AS diav FROM sifone_cartera_mora GROUP BY cedula
			) t WHERE diav BETWEEN 61 AND 90
			UNION ALL
			SELECT 'Jurídico' AS banda, COUNT(*) FROM (
				SELECT cedula, MAX(COALESCE(diav,0)) AS diav FROM sifone_cartera_mora GROUP BY cedula
			) t WHERE diav >= 91
		";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function distribucionUltimaComunicacion() {
		$sql = "SELECT rango, COUNT(1) AS asociados FROM (
			SELECT 
				cedula,
				CASE 
					WHEN ultima IS NULL THEN 'Sin comunicación'
					WHEN DATEDIFF(CURDATE(), ultima) < 2 THEN 'Muy reciente'
					WHEN DATEDIFF(CURDATE(), ultima) < 5 THEN 'Reciente'
					WHEN DATEDIFF(CURDATE(), ultima) < 10 THEN 'Intermedia'
					WHEN DATEDIFF(CURDATE(), ultima) <= 20 THEN 'Lejana'
					ELSE 'Muy lejana'
				END AS rango
			FROM (
				SELECT m.cedula, MAX(cc.fecha_comunicacion) AS ultima
				FROM sifone_cartera_mora m
				LEFT JOIN cobranza_comunicaciones cc ON cc.asociado_cedula = m.cedula
				GROUP BY m.cedula
			) t
		) z GROUP BY rango";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function comunicacionesPorTipoUltimosDias($dias = 30) {
		$dias = (int)$dias;
		$sql = "SELECT tipo_comunicacion AS tipo, COUNT(*) AS cantidad
				FROM cobranza_comunicaciones
				WHERE fecha_comunicacion >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)
				GROUP BY tipo_comunicacion";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function comunicacionesPorUsuario($dias = 7, $limit = 10) {
		$dias = (int)$dias; $limit = (int)$limit;
		$sql = "SELECT c.id_usuario, COALESCE(u.nombre_completo, u.usuario, CONCAT('Usuario ', c.id_usuario)) AS nombre, COUNT(*) AS cantidad
				FROM cobranza_comunicaciones c
				LEFT JOIN control_usuarios u ON u.id = c.id_usuario
				WHERE c.id_usuario IS NOT NULL AND c.fecha_comunicacion >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)
				GROUP BY c.id_usuario, nombre
				ORDER BY cantidad DESC
				LIMIT $limit";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function estadoUltimaComunicacionPorUsuario() {
		$sql = "SELECT u.id, COALESCE(u.nombre_completo, u.usuario, CONCAT('Usuario ', u.id)) AS nombre,
				COALESCE(x.estado, 'Sin comunicación') AS estado
			FROM control_usuarios u
			LEFT JOIN (
				SELECT c.id_usuario, SUBSTRING_INDEX(GROUP_CONCAT(c.estado ORDER BY c.fecha_comunicacion DESC SEPARATOR '\n'), '\n', 1) AS estado
				FROM cobranza_comunicaciones c
				WHERE c.id_usuario IS NOT NULL
				GROUP BY c.id_usuario
			) x ON x.id_usuario = u.id
			WHERE u.estado_activo = 1
			ORDER BY nombre";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function estadosUltimaComunicacionAsociado() {
		$sql = "SELECT estado, COUNT(*) AS asociados FROM (
			SELECT asociado_cedula,
			       SUBSTRING_INDEX(GROUP_CONCAT(estado ORDER BY fecha_comunicacion DESC SEPARATOR '\n'), '\n', 1) AS estado
			FROM cobranza_comunicaciones
			GROUP BY asociado_cedula
		) t
		GROUP BY estado
		ORDER BY asociados DESC";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function topAsociadosMora($limit = 10) {
		$limit = (int)$limit;
		$sql = "SELECT m.cedula, MAX(m.nombre) AS nombre,
				SUM(COALESCE(m.sdomor,0)) AS total_mora,
				MAX(COALESCE(m.diav,0)) AS max_diav
			FROM sifone_cartera_mora m
			GROUP BY m.cedula
			ORDER BY total_mora DESC
			LIMIT $limit";
		$stmt = $this->conn->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
?>


