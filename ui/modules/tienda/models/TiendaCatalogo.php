<?php
require_once __DIR__ . '/../../../config/database.php';

class TiendaCatalogo {
    private $conn;
    public function __construct(){ $this->conn = getConnection(); }

    // Categorías
    public function listarCategorias(): array {
        $stmt = $this->conn->query("SELECT id, nombre, estado_activo FROM tienda_categoria ORDER BY nombre");
        return $stmt->fetchAll() ?: [];
    }
    public function guardarCategoria(?int $id, string $nombre, bool $estadoActivo=true): array {
        if ($id) {
            $stmt = $this->conn->prepare("UPDATE tienda_categoria SET nombre=?, estado_activo=? WHERE id=?");
            $ok = $stmt->execute([$nombre, $estadoActivo?1:0, $id]);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO tienda_categoria (nombre, estado_activo) VALUES (?,?)");
            $ok = $stmt->execute([$nombre, $estadoActivo?1:0]);
        }
        if (!$ok) return ['success'=>false,'message'=>'No se pudo guardar categoría'];
        return ['success'=>true,'id'=>$id?:$this->conn->lastInsertId()];
    }
    public function eliminarCategoria(int $id): array {
        $stmt = $this->conn->prepare("DELETE FROM tienda_categoria WHERE id=?");
        $ok = $stmt->execute([$id]);
        return $ok?['success'=>true]:['success'=>false,'message'=>'No se pudo eliminar'];
    }

    // Marcas
    public function listarMarcas(): array {
        $stmt = $this->conn->query("SELECT id, nombre, estado_activo FROM tienda_marca ORDER BY nombre");
        return $stmt->fetchAll() ?: [];
    }
    public function guardarMarca(?int $id, string $nombre, bool $estadoActivo=true): array {
        if ($id) {
            $stmt = $this->conn->prepare("UPDATE tienda_marca SET nombre=?, estado_activo=? WHERE id=?");
            $ok = $stmt->execute([$nombre, $estadoActivo?1:0, $id]);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO tienda_marca (nombre, estado_activo) VALUES (?,?)");
            $ok = $stmt->execute([$nombre, $estadoActivo?1:0]);
        }
        if (!$ok) return ['success'=>false,'message'=>'No se pudo guardar marca'];
        return ['success'=>true,'id'=>$id?:$this->conn->lastInsertId()];
    }
    public function eliminarMarca(int $id): array {
        $stmt = $this->conn->prepare("DELETE FROM tienda_marca WHERE id=?");
        $ok = $stmt->execute([$id]);
        return $ok?['success'=>true]:['success'=>false,'message'=>'No se pudo eliminar'];
    }

    // Productos
    public function listarProductos(): array {
        $sql = "SELECT p.id, p.nombre, p.foto_url, p.descripcion, p.precio_compra_aprox, p.precio_venta_aprox, p.estado_activo,
                       c.id AS categoria_id, c.nombre AS categoria,
                       m.id AS marca_id, m.nombre AS marca
                FROM tienda_producto p
                INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                INNER JOIN tienda_marca m ON m.id = p.marca_id
                ORDER BY c.nombre, m.nombre, p.nombre";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll() ?: [];
    }
    public function guardarProducto(?int $id, int $categoriaId, int $marcaId, string $nombre, ?string $fotoUrl, ?string $descripcion, ?float $precioCompra, ?float $precioVenta, bool $estadoActivo=true): array {
        if ($id) {
            $sets = "categoria_id=?, marca_id=?, nombre=?, descripcion=?, precio_compra_aprox=?, precio_venta_aprox=?, estado_activo=?";
            $params = [$categoriaId, $marcaId, $nombre, $descripcion, $precioCompra, $precioVenta, $estadoActivo?1:0, $id];
            if ($fotoUrl !== null) { $sets .= ", foto_url=?"; array_splice($params, 7, 0, [$fotoUrl]); }
            $sql = "UPDATE tienda_producto SET $sets WHERE id=?";
            $stmt = $this->conn->prepare($sql);
            $ok = $stmt->execute($params);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO tienda_producto (categoria_id, marca_id, nombre, foto_url, descripcion, precio_compra_aprox, precio_venta_aprox, estado_activo) VALUES (?,?,?,?,?,?,?,?)");
            $ok = $stmt->execute([$categoriaId, $marcaId, $nombre, $fotoUrl, $descripcion, $precioCompra, $precioVenta, $estadoActivo?1:0]);
        }
        if (!$ok) return ['success'=>false,'message'=>'No se pudo guardar producto'];
        return ['success'=>true,'id'=>$id?:$this->conn->lastInsertId()];
    }
    public function eliminarProducto(int $id): array {
        $stmt = $this->conn->prepare("DELETE FROM tienda_producto WHERE id=?");
        $ok = $stmt->execute([$id]);
        return $ok?['success'=>true]:['success'=>false,'message'=>'No se pudo eliminar'];
    }
}

?>


