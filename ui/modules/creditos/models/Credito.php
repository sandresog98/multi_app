<?php
require_once __DIR__ . '/../../../config/database.php';

class Credito {
    private $conn;
    public function __construct() { $this->conn = getConnection(); }

    public function crear(array $data) {
        $stmt = $this->conn->prepare("INSERT INTO creditos_solicitudes (nombres, identificacion, celular, email, tipo, dep_nomina_1, dep_nomina_2, dep_cert_laboral, dep_simulacion_pdf, ind_decl_renta, ind_simulacion_pdf, ind_codeudor_nomina_1, ind_codeudor_nomina_2, ind_codeudor_cert_laboral, creado_por) VALUES (?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?)");
        $ok = $stmt->execute([
            $data['nombres'] ?? '', $data['identificacion'] ?? '', $data['celular'] ?? '', $data['email'] ?? '', $data['tipo'] ?? '',
            $data['dep_nomina_1'] ?? null, $data['dep_nomina_2'] ?? null, $data['dep_cert_laboral'] ?? null, $data['dep_simulacion_pdf'] ?? null,
            $data['ind_decl_renta'] ?? null, $data['ind_simulacion_pdf'] ?? null, $data['ind_codeudor_nomina_1'] ?? null, $data['ind_codeudor_nomina_2'] ?? null, $data['ind_codeudor_cert_laboral'] ?? null,
            $data['creado_por'] ?? null
        ]);
        if (!$ok) { return ['success'=>false,'message'=>'No se pudo crear']; }
        return ['success'=>true,'id'=>$this->conn->lastInsertId()];
    }

    public function listar($page=1,$limit=20,$filters=[],$sortBy='fecha_creacion',$sortDir='DESC') {
        $offset = ($page-1)*$limit;
        $where=[]; $params=[];
        if (!empty($filters['q'])) { $where[] = '(identificacion LIKE ? OR nombres LIKE ?)'; $params[]='%'.$filters['q'].'%'; $params[]='%'.$filters['q'].'%'; }
        if (!empty($filters['estado'])) { $where[] = 'estado = ?'; $params[] = $filters['estado']; }
        $whereClause = $where ? ('WHERE '.implode(' AND ',$where)) : '';
        $allowed = [
            'fecha_creacion'=>'fecha_creacion','estado'=>'estado','identificacion'=>'identificacion','nombres'=>'nombres'
        ];
        $col = $allowed[$sortBy] ?? 'fecha_creacion';
        $dir = strtoupper($sortDir)==='ASC'?'ASC':'DESC';
        $sql = "SELECT id, nombres, identificacion, celular, email, tipo, estado, fecha_creacion, fecha_actualizacion, archivo_datacredito, archivo_estudio, archivo_pagare_pdf, archivo_amortizacion FROM creditos_solicitudes $whereClause ORDER BY $col $dir LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql); $stmt->execute(array_merge($params,[$limit,$offset]));
        $items = $stmt->fetchAll();
        $cstmt = $this->conn->prepare("SELECT COUNT(*) total FROM creditos_solicitudes $whereClause"); $cstmt->execute($params); $total = (int)($cstmt->fetch()['total'] ?? 0);
        return [ 'items'=>$items, 'total'=>$total, 'pages'=>$limit>0? (int)ceil($total/$limit):1, 'current_page'=>$page ];
    }

    public function cambiarEstado(int $id, string $nuevoEstado, array $data = []) {
        $permitidos = ['Creado','Con Datacrédito','Aprobado','Rechazado','Con Estudio','Guardado'];
        if (!in_array($nuevoEstado,$permitidos,true)) { return ['success'=>false,'message'=>'Estado inválido']; }
        // Validar transición a partir del estado actual
        $curStmt = $this->conn->prepare('SELECT estado FROM creditos_solicitudes WHERE id = ?');
        $curStmt->execute([$id]);
        $actual = (string)($curStmt->fetchColumn());
        if ($actual === '') { return ['success'=>false,'message'=>'Solicitud no existe']; }
        $transiciones = [
            'Creado' => ['Con Datacrédito'],
            'Con Datacrédito' => ['Aprobado','Rechazado'],
            'Aprobado' => ['Con Estudio'],
            'Con Estudio' => ['Guardado'],
            'Guardado' => [],
            'Rechazado' => []
        ];
        $permit = $transiciones[$actual] ?? [];
        if (!in_array($nuevoEstado, $permit, true)) {
            return ['success'=>false,'message'=>'Transición no permitida desde "'.$actual.'" a "'.$nuevoEstado.'"'];
        }
        $sets = ['estado = ?']; $params = [$nuevoEstado];
        $mapFiles = [
            'archivo_datacredito','archivo_estudio','archivo_pagare_pdf','archivo_amortizacion'
        ];
        foreach ($mapFiles as $k) {
            if (array_key_exists($k,$data)) { $sets[] = "$k = ?"; $params[] = $data[$k]; }
        }
        if (isset($data['aprobado_por'])) { $sets[] = 'aprobado_por = ?'; $params[] = (int)$data['aprobado_por']; }
        $params[] = $id;
        $sql = 'UPDATE creditos_solicitudes SET '.implode(', ',$sets).', fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute($params);
        if (!$ok || $stmt->rowCount()===0) { return ['success'=>false,'message'=>'No se actualizó']; }
        // Escribir historial
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $hist = $this->conn->prepare('INSERT INTO creditos_historial (solicitud_id, usuario_id, accion, estado_anterior, estado_nuevo, archivo_campo, archivo_ruta) VALUES (?,?,?,?,?,?,?)');
            $archivoCampo = null; $archivoRuta = null;
            foreach (['archivo_datacredito','archivo_estudio','archivo_pagare_pdf','archivo_amortizacion'] as $k) {
                if (isset($data[$k])) { $archivoCampo = $k; $archivoRuta = $data[$k]; break; }
            }
            $hist->execute([$id, $userId, 'cambiar_estado', $actual, $nuevoEstado, $archivoCampo, $archivoRuta]);
        } catch (Throwable $ignored) {}
        return ['success'=>true];
    }

    public function resumenPorTipo(): array {
        $stmt = $this->conn->query("SELECT tipo, COUNT(*) cantidad FROM creditos_solicitudes GROUP BY tipo");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function resumenPorEstado(): array {
        $stmt = $this->conn->query("SELECT estado, COUNT(*) cantidad FROM creditos_solicitudes GROUP BY estado");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}


