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

  if ($action === 'guardar_categoria') {
    $id = isset($_POST['id']) && $_POST['id']!=='' ? (int)$_POST['id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
    if ($nombre==='') throw new Exception('Nombre requerido');
    echo json_encode($model->guardarCategoria($id,$nombre,$estado));
    return;
  }
  if ($action === 'eliminar_categoria') {
    $id = (int)($_POST['id'] ?? 0); if ($id<=0) throw new Exception('ID inválido');
    echo json_encode($model->eliminarCategoria($id)); return;
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
    $id = (int)($_POST['id'] ?? 0); if ($id<=0) throw new Exception('ID inválido');
    echo json_encode($model->eliminarMarca($id)); return;
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
      if ($size <= 0) throw new Exception('Archivo vacío');
      if ($size > 2*1024*1024) throw new Exception('Foto supera 2MB');
      $lower = strtolower($name);
      if (!preg_match('/\.(png|jpg|jpeg)$/', $lower)) throw new Exception('Formato inválido (PNG/JPG/JPEG)');
      $subdir = 'uploads/tienda/' . date('Y') . '/' . date('m');
      $absDir = getAbsolutePath($subdir);
      if (!is_dir($absDir)) { if (!mkdir($absDir, 0775, true)) throw new Exception('No se pudo crear directorio'); }
      if (!is_writable($absDir)) { @chmod($absDir, 0777); }
      if (!is_writable($absDir)) throw new Exception('Directorio no escribible');
      $base = pathinfo($lower, PATHINFO_FILENAME);
      $safe = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?: 'foto';
      $ext = pathinfo($lower, PATHINFO_EXTENSION);
      $filename = $safe . '-' . uniqid() . '.' . $ext;
      $dest = rtrim($absDir,'/') . '/' . $filename;
      if (!move_uploaded_file($tmp, $dest)) throw new Exception('No se pudo guardar foto');
      // getBaseUrl() apunta a /ui/, y $subdir es relativo a /ui
      $fotoUrl = getBaseUrl() . $subdir . '/' . $filename;
    }
    echo json_encode($model->guardarProducto($id,$categoriaId,$marcaId,$nombre,$fotoUrl,$descripcion,$precioCompra,$precioVenta,$estado));
    return;
  }
  if ($action === 'eliminar_producto') {
    $id = (int)($_POST['id'] ?? 0); if ($id<=0) throw new Exception('ID inválido');
    echo json_encode($model->eliminarProducto($id)); return;
  }

  echo json_encode(['success'=>false,'message'=>'Acción no soportada']);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


