<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/TiendaCatalogo.php';
require_once '../../../utils/FileUploadManager.php';

header('Content-Type: application/json');
try {
  $auth = new AuthController();
  $auth->requireModule('tienda.catalogo');
  $model = new TiendaCatalogo();

  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  $isMultipart = stripos($contentType, 'multipart/form-data') !== false;

  $action = $_GET['action'] ?? ($_POST['action'] ?? '');
  if ($method === 'GET' && $action === 'listar') {
    echo json_encode([
      'success'=>true,
      'categorias'=>$model->listarCategorias(),
      'marcas'=>$model->listarMarcas(),
      'productos'=>$model->listarProductos()
    ]);
    return;
  }
  
  if ($method === 'GET' && $action === 'listar_paginado') {
    $pagina = (int)($_GET['pagina'] ?? 1);
    $porPagina = (int)($_GET['por_pagina'] ?? 20);
    $filtros = [];
    
    if (!empty($_GET['categoria_id'])) $filtros['categoria_id'] = (int)$_GET['categoria_id'];
    if (!empty($_GET['marca_id'])) $filtros['marca_id'] = (int)$_GET['marca_id'];
    if (!empty($_GET['nombre'])) $filtros['nombre'] = trim($_GET['nombre']);
    if (!empty($_GET['precio_min'])) $filtros['precio_min'] = (float)$_GET['precio_min'];
    if (!empty($_GET['precio_max'])) $filtros['precio_max'] = (float)$_GET['precio_max'];
    
    $resultado = $model->listarProductosPaginado($pagina, $porPagina, $filtros);
    echo json_encode([
      'success'=>true,
      'categorias'=>$model->listarCategorias(),
      'marcas'=>$model->listarMarcas(),
      'productos'=>$resultado['productos'],
      'paginacion'=>$resultado
    ]);
    return;
  }

  if ($action === 'guardar_categoria') {
    $id = isset($_POST['id']) && $_POST['id']!=='' ? (int)$_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
    if ($nombre==='') throw new Exception('Nombre requerido');
    echo json_encode($model->guardarCategoria($id,$nombre,$estado));
    return;
  }
  if ($action === 'eliminar_categoria') {
    // Eliminación deshabilitada por política
    throw new Exception('Eliminar categorías no está permitido');
  }

  if ($action === 'guardar_marca') {
    $id = isset($_POST['id']) && $_POST['id']!=='' ? (int)$_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
    if ($nombre==='') throw new Exception('Nombre requerido');
    echo json_encode($model->guardarMarca($id,$nombre,$estado));
    return;
  }
  if ($action === 'eliminar_marca') {
    // Eliminación deshabilitada por política
    throw new Exception('Eliminar marcas no está permitido');
  }

  if ($action === 'guardar_producto') {
    if (!$isMultipart) throw new Exception('Se requiere multipart/form-data');
    $id = isset($_POST['id']) && $_POST['id']!=='' ? (int)$_POST['id'] : null;
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $marcaId = (int)($_POST['marca_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precioCompra = $_POST['precio_compra_aprox']!=='' ? (float)$_POST['precio_compra_aprox'] : null;
    $precioVenta = $_POST['precio_venta_aprox']!=='' ? (float)$_POST['precio_venta_aprox'] : null;
    $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
    if ($categoriaId<=0 || $marcaId<=0 || $nombre==='') throw new Exception('Campos requeridos faltantes');

    $fotoUrl = null;
    if (isset($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
      try {
        // Configurar opciones para tienda
        $options = [
          'maxSize' => 2 * 1024 * 1024, // 2MB
          'allowedExtensions' => ['png', 'jpg', 'jpeg'],
          'prefix' => 'tienda_producto',
          'userId' => $auth->getCurrentUser()['id'] ?? '',
          'webPath' => getBaseUrl() . 'assets/uploads/tienda'
        ];
        
        $baseDir = __DIR__ . '/../../../assets/uploads/tienda';
        $result = FileUploadManager::saveUploadedFile($_FILES['foto'], $baseDir, $options);
        $fotoUrl = $result['webUrl'];
        
        error_log("Tienda upload - URL final: $fotoUrl");
        
      } catch (Exception $e) {
        throw new Exception('Error guardando foto: ' . $e->getMessage());
      }
    }
    echo json_encode($model->guardarProducto($id,$categoriaId,$marcaId,$nombre,$fotoUrl,$descripcion,$precioCompra,$precioVenta,$estado));
    return;
  }
  if ($action === 'eliminar_producto') {
    // Eliminación deshabilitada por política
    throw new Exception('Eliminar productos no está permitido');
  }

  echo json_encode(['success'=>false,'message'=>'Acción no soportada']);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


