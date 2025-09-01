<?php
require_once __DIR__ . '/../../../config/database.php';

class TicketeraCategoria {
    private $conn;
    public function __construct(){ $this->conn = getConnection(); }

    public function listar($page=1,$limit=20,$search='',$estado=''){ 
        $offset = ($page-1)*$limit; $where=[]; $params=[];
        if ($search!==''){ $where[] = 'nombre LIKE ?'; $params[] = '%'.$search.'%'; }
        if ($estado!==''){ $where[] = 'estado_activo = ?'; $params[] = ($estado==='1'||$estado==='true')?1:0; }
        $whereClause = $where? ('WHERE '.implode(' AND ',$where)) : '';
        $sql = "SELECT id, nombre, descripcion, estado_activo, fecha_creacion, fecha_actualizacion
                FROM ticketera_categoria $whereClause ORDER BY nombre ASC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_merge($params, [$limit,$offset]));
        $rows = $stmt->fetchAll();
        $c = $this->conn->prepare("SELECT COUNT(*) AS total FROM ticketera_categoria $whereClause");
        $c->execute($params); $total = (int)($c->fetch()['total']??0);
        return ['items'=>$rows,'total'=>$total,'pages'=>($limit>0?(int)ceil($total/$limit):1),'current_page'=>$page];
    }

    public function crear($nombre,$descripcion=null,$estadoActivo=true){
        $nombre = trim($nombre); if ($nombre==='') return ['success'=>false,'message'=>'Nombre requerido'];
        $stmt = $this->conn->prepare("INSERT INTO ticketera_categoria (nombre, descripcion, estado_activo) VALUES (?, ?, ?)");
        $ok = $stmt->execute([$nombre, $descripcion, $estadoActivo?1:0]);
        if (!$ok) return ['success'=>false,'message'=>'No se pudo crear'];
        return ['success'=>true,'id'=>$this->conn->lastInsertId()];
    }

    public function actualizar($id,$nombre,$descripcion,$estadoActivo){
        $stmt = $this->conn->prepare("UPDATE ticketera_categoria SET nombre = ?, descripcion = ?, estado_activo = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
        $ok = $stmt->execute([trim($nombre), $descripcion, $estadoActivo?1:0, (int)$id]);
        if (!$ok) return ['success'=>false,'message'=>'No se pudo actualizar'];
        return ['success'=>true];
    }
}


