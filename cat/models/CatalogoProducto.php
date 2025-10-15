<?php
require_once __DIR__ . '/../config/database.php';

class CatalogoProducto {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obtener productos con filtros, paginación y ordenamiento
     */
    public function obtenerProductos($filtros = [], $pagina = 1, $porPagina = 12, $ordenar = 'nombre', $direccion = 'ASC') {
        $offset = ($pagina - 1) * $porPagina;
        
        $where = ['p.estado_activo = 1']; // Solo productos activos
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros['categoria_id'])) {
            $where[] = "p.categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        
        if (!empty($filtros['marca_id'])) {
            $where[] = "p.marca_id = ?";
            $params[] = $filtros['marca_id'];
        }
        
        if (!empty($filtros['nombre'])) {
            $where[] = "p.nombre LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
        }
        
        if (!empty($filtros['precio_min'])) {
            $where[] = "COALESCE(
                (SELECT AVG(cd.precio_venta_sugerido) 
                 FROM tienda_compra_detalle cd 
                 WHERE cd.producto_id = p.id 
                 AND cd.precio_venta_sugerido > 0),
                p.precio_venta_aprox
            ) >= ?";
            $params[] = $filtros['precio_min'];
        }
        
        if (!empty($filtros['precio_max'])) {
            $where[] = "COALESCE(
                (SELECT AVG(cd.precio_venta_sugerido) 
                 FROM tienda_compra_detalle cd 
                 WHERE cd.producto_id = p.id 
                 AND cd.precio_venta_sugerido > 0),
                p.precio_venta_aprox
            ) <= ?";
            $params[] = $filtros['precio_max'];
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Validar ordenamiento
        $ordenValido = ['nombre', 'precio', 'categoria', 'marca'];
        if (!in_array($ordenar, $ordenValido)) {
            $ordenar = 'nombre';
        }
        
        $direccionValida = ['ASC', 'DESC'];
        if (!in_array(strtoupper($direccion), $direccionValida)) {
            $direccion = 'ASC';
        }
        
        // Mapear campos de ordenamiento
        $ordenMap = [
            'nombre' => 'p.nombre',
            'precio' => 'COALESCE(
                (SELECT AVG(cd.precio_venta_sugerido) 
                 FROM tienda_compra_detalle cd 
                 WHERE cd.producto_id = p.id 
                 AND cd.precio_venta_sugerido > 0),
                p.precio_venta_aprox
            )',
            'categoria' => 'c.nombre',
            'marca' => 'm.nombre'
        ];
        
        $orderBy = $ordenMap[$ordenar] . ' ' . $direccion;
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total
                     FROM tienda_producto p
                     INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                     INNER JOIN tienda_marca m ON m.id = p.marca_id
                     $whereClause";
        
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Obtener productos paginados con precio de venta prioritario
        $sql = "SELECT p.id, p.nombre, p.foto_url, p.descripcion, 
                       p.precio_compra_aprox,
                       COALESCE(
                           (SELECT AVG(cd.precio_venta_sugerido) 
                            FROM tienda_compra_detalle cd 
                            WHERE cd.producto_id = p.id 
                            AND cd.precio_venta_sugerido > 0),
                           p.precio_venta_aprox
                       ) AS precio_venta,
                       c.id AS categoria_id, c.nombre AS categoria,
                       m.id AS marca_id, m.nombre AS marca
                FROM tienda_producto p
                INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                INNER JOIN tienda_marca m ON m.id = p.marca_id
                $whereClause
                ORDER BY $orderBy
                LIMIT $porPagina OFFSET $offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll() ?: [];
        
        return [
            'productos' => $productos,
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => ceil($total / $porPagina)
        ];
    }
    
    /**
     * Obtener todas las categorías activas
     */
    public function obtenerCategorias() {
        $stmt = $this->conn->query("SELECT id, nombre FROM tienda_categoria WHERE estado_activo = 1 ORDER BY nombre");
        return $stmt->fetchAll() ?: [];
    }
    
    /**
     * Obtener todas las marcas activas
     */
    public function obtenerMarcas() {
        $stmt = $this->conn->query("SELECT id, nombre FROM tienda_marca WHERE estado_activo = 1 ORDER BY nombre");
        return $stmt->fetchAll() ?: [];
    }
    
    /**
     * Obtener un producto específico por ID
     */
    public function obtenerProducto($id) {
        $sql = "SELECT p.id, p.nombre, p.foto_url, p.descripcion, 
                       p.precio_compra_aprox,
                       COALESCE(
                           (SELECT AVG(cd.precio_venta_sugerido) 
                            FROM tienda_compra_detalle cd 
                            WHERE cd.producto_id = p.id 
                            AND cd.precio_venta_sugerido > 0),
                           p.precio_venta_aprox
                       ) AS precio_venta,
                       c.id AS categoria_id, c.nombre AS categoria,
                       m.id AS marca_id, m.nombre AS marca
                FROM tienda_producto p
                INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                INNER JOIN tienda_marca m ON m.id = p.marca_id
                WHERE p.id = ? AND p.estado_activo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
