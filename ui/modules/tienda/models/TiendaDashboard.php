<?php
require_once __DIR__ . '/../../../config/database.php';

class TiendaDashboard {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Obtener cantidad total de productos
     */
    public function getProductosTotales(): array {
        $total = (int)$this->conn->query("SELECT COUNT(*) FROM tienda_producto WHERE estado_activo = TRUE")->fetchColumn();
        $conPrecio = (int)$this->conn->query("SELECT COUNT(*) FROM tienda_producto WHERE estado_activo = TRUE AND precio_venta_aprox > 0")->fetchColumn();
        return ['total' => $total, 'con_precio' => $conPrecio];
    }
    
    /**
     * Obtener productos en inventario (con stock disponible)
     */
    public function getProductosEnInventario(): int {
        // Productos que tienen compras registradas y no han sido vendidos completamente
        $sql = "SELECT COUNT(DISTINCT p.id) 
                FROM tienda_producto p
                INNER JOIN tienda_compra_detalle cd ON cd.producto_id = p.id
                LEFT JOIN tienda_venta_detalle vd ON vd.producto_id = p.id
                WHERE p.estado_activo = TRUE
                AND cd.cantidad > COALESCE((SELECT SUM(vd2.cantidad) FROM tienda_venta_detalle vd2 WHERE vd2.producto_id = p.id), 0)";
        
        return (int)$this->conn->query($sql)->fetchColumn();
    }
    
    /**
     * Obtener celulares en inventario (productos de categoría celular)
     */
    public function getCelularesEnInventario(): int {
        $sql = "SELECT COUNT(DISTINCT p.id) 
                FROM tienda_producto p
                INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                INNER JOIN tienda_compra_detalle cd ON cd.producto_id = p.id
                LEFT JOIN tienda_venta_detalle vd ON vd.producto_id = p.id
                WHERE p.estado_activo = TRUE
                AND (LOWER(c.nombre) LIKE '%celular%' OR LOWER(c.nombre) LIKE '%telefono%' OR LOWER(c.nombre) LIKE '%smartphone%')
                AND cd.cantidad > COALESCE((SELECT SUM(vd2.cantidad) FROM tienda_venta_detalle vd2 WHERE vd2.producto_id = p.id), 0)";
        
        return (int)$this->conn->query($sql)->fetchColumn();
    }
    
    /**
     * Obtener ventas realizadas hoy
     */
    public function getVentasHoy(): int {
        $sql = "SELECT COUNT(*) FROM tienda_venta WHERE DATE(fecha_creacion) = CURDATE()";
        return (int)$this->conn->query($sql)->fetchColumn();
    }
    
    /**
     * Obtener ventas por categoría (últimos 30 días)
     */
    public function getVentasPorCategoria(): array {
        $sql = "SELECT c.nombre AS categoria, COUNT(vd.id) AS cantidad, SUM(vd.subtotal) AS total
                FROM tienda_venta_detalle vd
                INNER JOIN tienda_producto p ON p.id = vd.producto_id
                INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                INNER JOIN tienda_venta v ON v.id = vd.venta_id
                WHERE v.fecha_creacion >= (NOW() - INTERVAL 30 DAY)
                GROUP BY c.id, c.nombre
                ORDER BY cantidad DESC";
        
        return $this->conn->query($sql)->fetchAll();
    }
    
    /**
     * Obtener ventas por marca (últimos 30 días)
     */
    public function getVentasPorMarca(): array {
        $sql = "SELECT m.nombre AS marca, COUNT(vd.id) AS cantidad, SUM(vd.subtotal) AS total
                FROM tienda_venta_detalle vd
                INNER JOIN tienda_producto p ON p.id = vd.producto_id
                INNER JOIN tienda_marca m ON m.id = p.marca_id
                INNER JOIN tienda_venta v ON v.id = vd.venta_id
                WHERE v.fecha_creacion >= (NOW() - INTERVAL 30 DAY)
                GROUP BY m.id, m.nombre
                ORDER BY cantidad DESC";
        
        return $this->conn->query($sql)->fetchAll();
    }
    
    /**
     * Obtener ventas del día actual
     */
    public function getVentasHoyDetalle(): array {
        $sql = "SELECT 
                    COUNT(v.id) AS cantidad_ventas,
                    SUM(v.total) AS total_ventas,
                    COUNT(vd.id) AS cantidad_productos,
                    SUM(vd.subtotal) AS total_productos
                FROM tienda_venta v
                LEFT JOIN tienda_venta_detalle vd ON vd.venta_id = v.id
                WHERE DATE(v.fecha_creacion) = CURDATE()";
        
        return $this->conn->query($sql)->fetch();
    }
    
    /**
     * Obtener ventas del mes actual
     */
    public function getVentasMesActual(): array {
        $sql = "SELECT 
                    COUNT(v.id) AS cantidad_ventas,
                    SUM(v.total) AS total_ventas,
                    COUNT(vd.id) AS cantidad_productos,
                    SUM(vd.subtotal) AS total_productos
                FROM tienda_venta v
                LEFT JOIN tienda_venta_detalle vd ON vd.venta_id = v.id
                WHERE YEAR(v.fecha_creacion) = YEAR(CURDATE()) 
                AND MONTH(v.fecha_creacion) = MONTH(CURDATE())";
        
        return $this->conn->query($sql)->fetch();
    }
    
    /**
     * Obtener productos más vendidos (últimos 30 días)
     */
    public function getProductosMasVendidos(): array {
        $sql = "SELECT p.nombre, c.nombre AS categoria, m.nombre AS marca, 
                       COUNT(vd.id) AS veces_vendido, SUM(vd.cantidad) AS cantidad_total
                FROM tienda_venta_detalle vd
                INNER JOIN tienda_producto p ON p.id = vd.producto_id
                INNER JOIN tienda_categoria c ON c.id = p.categoria_id
                INNER JOIN tienda_marca m ON m.id = p.marca_id
                INNER JOIN tienda_venta v ON v.id = vd.venta_id
                WHERE v.fecha_creacion >= (NOW() - INTERVAL 30 DAY)
                GROUP BY p.id, p.nombre, c.nombre, m.nombre
                ORDER BY cantidad_total DESC
                LIMIT 10";
        
        return $this->conn->query($sql)->fetchAll();
    }
    
    /**
     * Obtener estadísticas de inventario
     */
    public function getEstadisticasInventario(): array {
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) AS productos_totales,
                    COUNT(DISTINCT CASE WHEN cd.cantidad > COALESCE(vendidos.cantidad_vendida, 0) THEN p.id END) AS productos_con_stock,
                    COUNT(DISTINCT CASE WHEN COALESCE(vendidos.cantidad_vendida, 0) >= cd.cantidad THEN p.id END) AS productos_sin_stock,
                    SUM(cd.cantidad) AS cantidad_total_comprada,
                    SUM(COALESCE(vendidos.cantidad_vendida, 0)) AS cantidad_total_vendida
                FROM tienda_producto p
                INNER JOIN tienda_compra_detalle cd ON cd.producto_id = p.id
                LEFT JOIN (
                    SELECT producto_id, SUM(cantidad) AS cantidad_vendida
                    FROM tienda_venta_detalle
                    GROUP BY producto_id
                ) vendidos ON vendidos.producto_id = p.id
                WHERE p.estado_activo = TRUE";
        
        return $this->conn->query($sql)->fetch();
    }
    
    /**
     * Obtener ventas por tipo de cliente (últimos 30 días)
     */
    public function getVentasPorTipoCliente(): array {
        $sql = "SELECT tipo_cliente, COUNT(*) AS cantidad, SUM(total) AS total
                FROM tienda_venta
                WHERE fecha_creacion >= (NOW() - INTERVAL 30 DAY)
                GROUP BY tipo_cliente
                ORDER BY cantidad DESC";
        
        return $this->conn->query($sql)->fetchAll();
    }
    
    /**
     * Obtener ventas por método de pago (últimos 30 días)
     */
    public function getVentasPorMetodoPago(): array {
        $sql = "SELECT tipo, COUNT(*) AS cantidad, SUM(monto) AS total
                FROM tienda_venta_pago vp
                INNER JOIN tienda_venta v ON v.id = vp.venta_id
                WHERE v.fecha_creacion >= (NOW() - INTERVAL 30 DAY)
                GROUP BY tipo
                ORDER BY cantidad DESC";
        
        return $this->conn->query($sql)->fetchAll();
    }
}
