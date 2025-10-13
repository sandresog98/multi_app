<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');
try {
  $auth = new AuthController();
  $auth->requireModule('tienda.inventario');
  $pdo = getConnection();

  $pagina = (int)($_GET['pagina'] ?? 1);
  $porPagina = (int)($_GET['por_pagina'] ?? 20);
  $filtros = [];
  
  if (!empty($_GET['categoria_id'])) $filtros['categoria_id'] = (int)$_GET['categoria_id'];
  if (!empty($_GET['marca_id'])) $filtros['marca_id'] = (int)$_GET['marca_id'];
  if (!empty($_GET['nombre'])) $filtros['nombre'] = trim($_GET['nombre']);

  $offset = ($pagina - 1) * $porPagina;
  
  $where = [];
  $params = [];
  
  // Aplicar filtros
  if (!empty($filtros['categoria_id'])) {
    $where[] = "c.id = ?";
    $params[] = $filtros['categoria_id'];
  }
  if (!empty($filtros['marca_id'])) {
    $where[] = "m.id = ?";
    $params[] = $filtros['marca_id'];
  }
  if (!empty($filtros['nombre'])) {
    $where[] = "p.nombre LIKE ?";
    $params[] = '%' . $filtros['nombre'] . '%';
  }
  
  $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
  
  // Contar total de registros
  $countSql = "SELECT COUNT(*) as total
               FROM tienda_producto p
               INNER JOIN tienda_categoria c ON c.id=p.categoria_id
               INNER JOIN tienda_marca m ON m.id=p.marca_id
               $whereClause";
  $countStmt = $pdo->prepare($countSql);
  $countStmt->execute($params);
  $total = $countStmt->fetch()['total'];
  
  // Obtener inventario paginado
  $sql = "SELECT p.id, p.nombre, p.foto_url, c.id AS categoria_id, c.nombre AS categoria, m.id AS marca_id, m.nombre AS marca,
                         COALESCE(cd.ingresado,0) AS ingresado,
                         COALESCE(vd.vendido,0) AS vendido,
                         COALESCE(cd.ingresado,0) - COALESCE(vd.vendido,0) AS disponible
                  FROM tienda_producto p
                  INNER JOIN tienda_categoria c ON c.id=p.categoria_id
                  INNER JOIN tienda_marca m ON m.id=p.marca_id
                  LEFT JOIN (
                    SELECT producto_id, SUM(cantidad) AS ingresado
                    FROM tienda_compra_detalle
                    GROUP BY producto_id
                  ) cd ON cd.producto_id = p.id
                  LEFT JOIN (
                    SELECT producto_id,
                           SUM(CASE WHEN compra_imei_id IS NULL THEN cantidad ELSE 1 END) AS vendido
                    FROM tienda_venta_detalle
                    GROUP BY producto_id
                  ) vd ON vd.producto_id = p.id
                  $whereClause
                  ORDER BY c.nombre, p.nombre
                  LIMIT $porPagina OFFSET $offset";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  
  echo json_encode([
    'success' => true,
    'items' => $items,
    'paginacion' => [
      'total' => $total,
      'pagina' => $pagina,
      'por_pagina' => $porPagina,
      'total_paginas' => ceil($total / $porPagina)
    ]
  ]);
  
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
