<?php
require_once __DIR__ . '/../../../config/database.php';

class TiendaClientes {
  private $conn;
  public function __construct(){ $this->conn = getConnection(); }

  public function listar(): array {
    $stmt = $this->conn->query("SELECT id, nombre, nit_cedula, telefono, email, fecha_creacion FROM tienda_clientes ORDER BY nombre");
    return $stmt->fetchAll() ?: [];
  }
  public function guardar(?int $id, string $nombre, string $doc, ?string $tel, ?string $mail): array {
    if ($id) {
      $stmt = $this->conn->prepare("UPDATE tienda_clientes SET nombre=?, nit_cedula=?, telefono=?, email=? WHERE id=?");
      $ok = $stmt->execute([$nombre,$doc,$tel,$mail,$id]);
    } else {
      $stmt = $this->conn->prepare("INSERT INTO tienda_clientes (nombre, nit_cedula, telefono, email) VALUES (?,?,?,?)");
      $ok = $stmt->execute([$nombre,$doc,$tel,$mail]);
    }
    if (!$ok) return ['success'=>false,'message'=>'No se pudo guardar cliente'];
    return ['success'=>true,'id'=>$id?:$this->conn->lastInsertId()];
  }
  public function eliminar(int $id): array {
    $stmt = $this->conn->prepare("DELETE FROM tienda_clientes WHERE id=?");
    $ok = $stmt->execute([$id]);
    return $ok?['success'=>true]:['success'=>false,'message'=>'No se pudo eliminar'];
  }
}

?>


