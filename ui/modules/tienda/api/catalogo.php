<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/TiendaCatalogo.php';

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
      $tmp = $_FILES['foto']['tmp_name'];
      $size = (int)($_FILES['foto']['size'] ?? 0);
      $name = $_FILES['foto']['name'] ?? '';
      
      // Log para debugging
      error_log("Tienda upload - Archivo: $name, Tamaño: $size, Tmp: $tmp");
      
      if ($size <= 0) throw new Exception('Archivo vacío');
      if ($size > 2*1024*1024) throw new Exception('Foto supera 2MB');
      $lower = strtolower($name);
      if (!preg_match('/\.(png|jpg|jpeg)$/', $lower)) throw new Exception('Formato inválido (PNG/JPG/JPEG)');
      
      $subdir = 'assets/uploads/tienda/' . date('Y') . '/' . date('m');
      $absDir = __DIR__ . '/../../../' . $subdir;
      
      error_log("Tienda upload - Directorio destino: $absDir");
      
      if (!is_dir($absDir)) { 
        if (!mkdir($absDir, 0775, true)) throw new Exception('No se pudo crear directorio: ' . $absDir); 
        error_log("Tienda upload - Directorio creado: $absDir");
      }
      if (!is_writable($absDir)) { 
        @chmod($absDir, 0777); 
        error_log("Tienda upload - Permisos cambiados a 0777 para: $absDir");
      }
      if (!is_writable($absDir)) throw new Exception('Directorio no escribible: ' . $absDir);
      
      $base = pathinfo($lower, PATHINFO_FILENAME);
      $safe = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?: 'foto';
      $ext = pathinfo($lower, PATHINFO_EXTENSION);
      $filename = $safe . '-' . uniqid() . '.' . $ext;
      $dest = rtrim($absDir,'/') . '/' . $filename;
      
      error_log("Tienda upload - Archivo destino: $dest");
      
      if (!move_uploaded_file($tmp, $dest)) throw new Exception('No se pudo guardar foto en: ' . $dest);
      
      // Verificar que el archivo se guardó correctamente
      if (!file_exists($dest)) throw new Exception('Archivo no accesible tras guardar: ' . $dest);
      
      // getBaseUrl() apunta a /ui/, y $subdir es relativo a /ui
      $fotoUrl = getBaseUrl() . $subdir . '/' . $filename;
      
      error_log("Tienda upload - URL final: $fotoUrl");
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


