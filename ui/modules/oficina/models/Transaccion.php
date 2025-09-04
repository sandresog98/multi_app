<?php
require_once __DIR__ . '/../../../config/database.php';

class Transaccion {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getResumenPorAsociado(string $cedula): array {
        // Unificar créditos: aseguradora LEFT JOIN mora
        $resumen = [
            'creditos_recomendado' => 0.0,
            'cobranza_recomendada' => 0.0,
            'productos_recomendado' => 0.0,
            'detalles' => [
                'creditos' => [], 'cobranza' => [], 'productos' => []
            ]
        ];

        $sqlCred = "SELECT a.numero, a.valorc, m.intmora, m.diav, m.sdomor
                    FROM sifone_cartera_aseguradora a
                    LEFT JOIN sifone_cartera_mora m
                      ON m.cedula = a.cedula AND m.presta = a.numero
                    WHERE a.cedula = ?";
        $stmtCred = $this->conn->prepare($sqlCred);
        $stmtCred->execute([$cedula]);
        $creditos = $stmtCred->fetchAll();
        foreach ($creditos as $cr) {
            $cuota = (float)$cr['valorc'];
            $intmora = (float)($cr['intmora'] ?? 0);
            $diav = isset($cr['diav']) ? (int)$cr['diav'] : null;
            $hasMora = $diav !== null;
            $recomendado = round($cuota + ($hasMora ? $intmora : 0), 2);
            $resumen['creditos_recomendado'] += $recomendado;
            $resumen['detalles']['creditos'][] = [
                'numero' => $cr['numero'],
                'cuota' => $cuota,
                'intmora' => $intmora,
                'diav' => $diav,
                'has_mora' => $hasMora,
                'recomendado' => $recomendado
            ];

            // Cobranza sólo si hay días de mora
            if ($hasMora) {
                $pct = 0.0;
                if ($diav > 60) $pct = 0.08; else if ($diav > 50) $pct = 0.06; else if ($diav > 40) $pct = 0.05; else if ($diav > 30) $pct = 0.04; else if ($diav > 20) $pct = 0.03; else if ($diav > 11) $pct = 0.02; else $pct = 0.0;
                $cbr = round($cuota * $pct, 2);
                if ($cbr > 0) {
                    $resumen['cobranza_recomendada'] += $cbr;
                    $resumen['detalles']['cobranza'][] = ['numero' => $cr['numero'], 'cuota' => $cuota, 'diav' => $diav, 'valor' => $cbr];
                }
            }
        }

        // Productos asignados
        $sqlProd = "SELECT ap.producto_id, p.nombre, ap.monto_pago FROM control_asignacion_asociado_producto ap JOIN control_productos p ON p.id = ap.producto_id WHERE ap.cedula = ? AND ap.estado_activo = TRUE AND p.estado_activo = TRUE ORDER BY p.prioridad ASC, p.nombre ASC";
        $stmtP = $this->conn->prepare($sqlProd);
        $stmtP->execute([$cedula]);
        foreach ($stmtP->fetchAll() as $pr) {
            $monto = (float)$pr['monto_pago'];
            $resumen['productos_recomendado'] += $monto;
            $resumen['detalles']['productos'][] = ['producto_id' => (int)$pr['producto_id'], 'nombre' => $pr['nombre'], 'monto' => $monto];
        }

        return $resumen;
    }

    public function getPagosDisponibles(): array {
        // PSE relacionados
        $sqlPse = "SELECT a.pse_id,
                           a.confiar_id,
                           p.valor,
                           DATE(p.fecha_hora_resolucion_de_la_transaccion) AS fecha,
                           p.referencia_2,
                           p.referencia_3,
                           COALESCE(u.utilizado,0) AS utilizado,
                           GREATEST(p.valor - COALESCE(u.utilizado,0), 0) AS restante
                   FROM banco_asignacion_pse a
                   JOIN banco_pse p ON p.pse_id = a.pse_id
                   LEFT JOIN (
                     SELECT ct.pse_id, SUM(d.valor_asignado) AS utilizado
                     FROM control_transaccion ct
                     JOIN control_transaccion_detalle d ON d.transaccion_id = ct.id
                     WHERE ct.pse_id IS NOT NULL
                     GROUP BY ct.pse_id
                   ) u ON u.pse_id = a.pse_id
                   ORDER BY p.fecha_hora_resolucion_de_la_transaccion DESC LIMIT 100";
        $stmt1 = $this->conn->query($sqlPse);
        $pse = $stmt1->fetchAll();
        // Cash/QR confirmados (excluye no_válidos)
        $sqlCash = "SELECT c.confiar_id,
                           b.valor_consignacion AS valor,
                           b.fecha,
                           b.descripcion,
                           c.cedula AS cedula_asignada,
                           COALESCE(u.utilizado,0) AS utilizado,
                           GREATEST(b.valor_consignacion - COALESCE(u.utilizado,0), 0) AS restante
                    FROM banco_confirmacion_confiar c
                    JOIN banco_confiar b ON b.confiar_id = c.confiar_id
                    WHERE c.estado <> 'no_valido'
                    LEFT JOIN (
                      SELECT ct.confiar_id, SUM(d.valor_asignado) AS utilizado
                      FROM control_transaccion ct
                      JOIN control_transaccion_detalle d ON d.transaccion_id = ct.id
                      WHERE ct.confiar_id IS NOT NULL
                      GROUP BY ct.confiar_id
                    ) u ON u.confiar_id = c.confiar_id
                    ORDER BY b.fecha DESC LIMIT 100";
        $stmt2 = $this->conn->query($sqlCash);
        $cash = $stmt2->fetchAll();
        return ['pse' => $pse, 'cash_qr' => $cash];
    }

    public function crearTransaccion(string $cedula, string $origen, ?string $pseId, ?string $confiarId, float $valorPago, array $detalles, ?int $usuarioId = null): array {
        try {
            $this->conn->beginTransaction();
            // Validación simple: suma de asignado no debe superar el pago
            $suma = 0.0; foreach ($detalles as $d) { $suma += (float)($d['valor_asignado'] ?? 0); }
            if ($suma > $valorPago + 0.01) {
                throw new Exception('La suma de valores asignados excede el valor del pago seleccionado');
            }
            // Evitar reuso de pago
            $stmtChk = null;
            if ($origen === 'pse' && $pseId) {
                $stmtChk = $this->conn->prepare("SELECT COUNT(*) c FROM control_transaccion WHERE pse_id = ?");
                $stmtChk->execute([$pseId]);
            } elseif ($origen === 'cash_qr' && $confiarId) {
                $stmtChk = $this->conn->prepare("SELECT COUNT(*) c FROM control_transaccion WHERE confiar_id = ?");
                $stmtChk->execute([$confiarId]);
            }
            if ($stmtChk && (int)($stmtChk->fetch()['c'] ?? 0) > 0) {
                throw new Exception('El pago seleccionado ya fue utilizado en otra transacción');
            }

            // Insert cabecera
            $stmtH = $this->conn->prepare("INSERT INTO control_transaccion (cedula, origen_pago, pse_id, confiar_id, valor_pago_total, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtH->execute([$cedula, $origen, $pseId, $confiarId, $valorPago, $usuarioId]);
            $transaccionId = (int)$this->conn->lastInsertId();

            // Insert detalles
            $stmtD = $this->conn->prepare("INSERT INTO control_transaccion_detalle (transaccion_id, tipo_rubro, referencia_credito, producto_id, descripcion, valor_recomendado, valor_asignado) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($detalles as $d) {
                $stmtD->execute([
                    $transaccionId,
                    $d['tipo_rubro'] ?? null,
                    $d['referencia_credito'] ?? null,
                    $d['producto_id'] ?? null,
                    $d['descripcion'] ?? null,
                    (float)($d['valor_recomendado'] ?? 0),
                    (float)($d['valor_asignado'] ?? 0)
                ]);
            }

            $this->conn->commit();
            return ['success' => true, 'id' => $transaccionId];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listTransacciones(?string $cedula = null, int $page = 1, int $limit = 20): array {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        if (!empty($cedula)) { $where[] = 't.cedula = ?'; $params[] = $cedula; }
        $whereClause = empty($where) ? '' : ('WHERE '.implode(' AND ', $where));

        $sql = "SELECT t.id, t.cedula, t.origen_pago, t.pse_id, t.confiar_id, t.valor_pago_total, t.fecha_creacion,
                       COALESCE(SUM(d.valor_asignado),0) AS total_asignado,
                       COUNT(d.id) AS items
                FROM control_transaccion t
                LEFT JOIN control_transaccion_detalle d ON d.transaccion_id = t.id
                $whereClause
                GROUP BY t.id
                ORDER BY t.fecha_creacion DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        $rows = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) c FROM control_transaccion t $whereClause";
        $cstmt = $this->conn->prepare($countSql);
        $cstmt->execute($params);
        $total = (int)($cstmt->fetch()['c'] ?? 0);

        return [ 'items' => $rows, 'total' => $total, 'pages' => $limit>0 ? (int)ceil($total/$limit) : 1, 'current_page' => $page ];
    }

    public function deleteTransaccion(int $id): bool {
        try {
            $this->conn->beginTransaction();
            $delD = $this->conn->prepare('DELETE FROM control_transaccion_detalle WHERE transaccion_id = ?');
            $delD->execute([$id]);
            $delH = $this->conn->prepare('DELETE FROM control_transaccion WHERE id = ?');
            $ok = $delH->execute([$id]);
            $this->conn->commit();
            return (bool)$ok;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getTransaccion(int $id): ?array {
        $sql = "SELECT t.id, t.cedula, t.origen_pago, t.pse_id, t.confiar_id, t.valor_pago_total, t.fecha_creacion,
                       COALESCE(SUM(d.valor_asignado),0) AS total_asignado
                FROM control_transaccion t
                LEFT JOIN control_transaccion_detalle d ON d.transaccion_id = t.id
                WHERE t.id = ?
                GROUP BY t.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getTransaccionDetalles(int $id): array {
        $sql = "SELECT id, tipo_rubro, referencia_credito, producto_id, descripcion, valor_recomendado, valor_asignado
                FROM control_transaccion_detalle WHERE transaccion_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
}
