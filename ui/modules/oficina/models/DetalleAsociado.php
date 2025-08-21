<?php
require_once __DIR__ . '/../../../config/database.php';

class DetalleAsociado {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function getAsociadoInfo(string $cedula) {
        $sql = "SELECT cedula, nombre, celula, mail, ciudad, direcc, aporte FROM sifone_asociados WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetch();
    }

    public function getCreditos(string $cedula): array {
        $sql = "SELECT 
                    a.numero AS numero_credito,
                    a.tipopr AS tipo_prestamo,
                    a.plazo,
                    a.tasa,
                    a.carter AS deuda_capital,
                    m.sdomor AS saldo_mora,
                    m.diav AS dias_mora
                FROM sifone_cartera_aseguradora a
                LEFT JOIN sifone_cartera_mora m
                    ON m.cedula = a.cedula AND m.presta = a.numero
                WHERE a.cedula = ?
                ORDER BY a.numero";
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
}
