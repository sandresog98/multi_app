<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/Transaccion.php';
require_once __DIR__ . '/../../../models/Logger.php';

class Dashboard {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function getKpis(): array {
        $kpis = [
            'asociados_activos' => 0,
            'asociados_inactivos' => 0,
            'productos_activos' => 0,
            'asignaciones_activas' => 0,
            'pse_aprobada_sin_asignar' => 0,
            'cash_confirmados_hoy' => 0,
            'transacciones_hoy_cantidad' => 0,
            'transacciones_hoy_valor' => 0,
        ];
        // Asociados
        $kpis['asociados_activos'] = (int)$this->conn->query("SELECT COUNT(*) c FROM control_asociados WHERE estado_activo = TRUE")->fetchColumn();
        $kpis['asociados_inactivos'] = (int)$this->conn->query("SELECT COUNT(*) c FROM control_asociados WHERE estado_activo = FALSE")->fetchColumn();
        // Productos y asignaciones
        $kpis['productos_activos'] = (int)$this->conn->query("SELECT COUNT(*) c FROM control_productos WHERE estado_activo = TRUE")->fetchColumn();
        $kpis['asignaciones_activas'] = (int)$this->conn->query("SELECT COUNT(*) c FROM control_asignacion_asociado_producto WHERE estado_activo = TRUE")->fetchColumn();
        // PSE aprobada sin asignar
        $sqlPse = "SELECT COUNT(*) c FROM banco_pse p LEFT JOIN banco_asignacion_pse a ON a.pse_id = p.pse_id WHERE p.estado = 'Aprobada' AND a.pse_id IS NULL";
        $kpis['pse_aprobada_sin_asignar'] = (int)$this->conn->query($sqlPse)->fetchColumn();
        // Cash confirmados hoy
        $kpis['cash_confirmados_hoy'] = (int)$this->conn->query("SELECT COUNT(*) c FROM banco_confirmacion_confiar WHERE DATE(fecha_validacion) = CURDATE()")->fetchColumn();
        // Transacciones hoy
        $rowTx = $this->conn->query("SELECT COUNT(*) c, COALESCE(SUM(valor_pago_total),0) s FROM control_transaccion WHERE DATE(fecha_creacion) = CURDATE()")->fetch();
        $kpis['transacciones_hoy_cantidad'] = (int)($rowTx['c'] ?? 0);
        $kpis['transacciones_hoy_valor'] = (float)($rowTx['s'] ?? 0);
        return $kpis;
    }

    public function getFreshness(): array {
        $stmt = $this->conn->query("SELECT tipo, MAX(fecha_actualizacion) AS ultima_actualizacion FROM control_cargas WHERE estado='completado' GROUP BY tipo ORDER BY tipo");
        return $stmt->fetchAll();
    }

    public function getPagosStatus(): array {
        $tx = new Transaccion();
        $pagos = $tx->getPagosDisponibles();
        $status = [
            'pse' => ['sin_asignar'=>['count'=>0,'valor'=>0],'parcial'=>['count'=>0,'valor'=>0],'completado'=>['count'=>0,'valor'=>0]],
            'cash_qr' => ['sin_asignar'=>['count'=>0,'valor'=>0],'parcial'=>['count'=>0,'valor'=>0],'completado'=>['count'=>0,'valor'=>0]],
        ];
        foreach (($pagos['pse'] ?? []) as $r) {
            $v = (float)($r['valor'] ?? 0); $u = (float)($r['utilizado'] ?? 0);
            if ($u <= 0) { $status['pse']['sin_asignar']['count']++; $status['pse']['sin_asignar']['valor'] += $v; }
            elseif ($u < $v) { $status['pse']['parcial']['count']++; $status['pse']['parcial']['valor'] += ($v - $u); }
            else { $status['pse']['completado']['count']++; $status['pse']['completado']['valor'] += 0; }
        }
        foreach (($pagos['cash_qr'] ?? []) as $r) {
            $v = (float)($r['valor'] ?? 0); $u = (float)($r['utilizado'] ?? 0);
            if ($u <= 0) { $status['cash_qr']['sin_asignar']['count']++; $status['cash_qr']['sin_asignar']['valor'] += $v; }
            elseif ($u < $v) { $status['cash_qr']['parcial']['count']++; $status['cash_qr']['parcial']['valor'] += ($v - $u); }
            else { $status['cash_qr']['completado']['count']++; $status['cash_qr']['completado']['valor'] += 0; }
        }
        return $status;
    }

    public function getCargasResumen(): array {
        $res = [
            'pendiente'=>0,'procesando'=>0,'completado'=>0,'error'=>0,
            'recientes'=>[]
        ];
        $stmt = $this->conn->query("SELECT estado, COUNT(*) c FROM control_cargas GROUP BY estado");
        foreach ($stmt->fetchAll() as $r) { $res[$r['estado']] = (int)$r['c']; }
        $rec = $this->conn->query("SELECT id, tipo, archivo_ruta, estado, LEFT(COALESCE(mensaje_log,''), 120) AS mensaje, fecha_creacion, fecha_actualizacion FROM control_cargas ORDER BY id DESC LIMIT 5")->fetchAll();
        $res['recientes'] = $rec;
        return $res;
    }

    public function getLogsRecientes(int $limit = 10): array {
        $logger = new Logger();
        return $logger->getLogs(['limite'=>$limit]);
    }

    public function getTransaccionesRecientes(int $limit = 10): array {
        $sql = "SELECT t.id, t.origen_pago, t.pse_id, t.confiar_id, t.valor_pago_total, t.fecha_creacion,
                       (SELECT COUNT(*) FROM control_transaccion_detalle d WHERE d.transaccion_id = t.id) AS items
                FROM control_transaccion t
                ORDER BY t.id DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}


