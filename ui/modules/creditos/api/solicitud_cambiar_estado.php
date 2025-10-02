<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';
require_once '../controllers/CreditosController.php';
require_once '../../../models/Logger.php';

header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireModule('creditos');
  $ctrl = new CreditosController();
  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  $isMultipart = stripos($contentType, 'multipart/form-data') !== false;
  $data = [];
  if ($isMultipart) {
    $id = (int)($_POST['id'] ?? 0);
    $estado = (string)($_POST['estado'] ?? '');
    if ($id<=0 || $estado==='') throw new Exception('Parámetros inválidos');
    // Guardar archivos según estado requerido
    $saveFile = function(string $field, bool $pdfOnly = false) {
      if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
      }
      $tmp = $_FILES[$field]['tmp_name'];
      $size = (int)($_FILES[$field]['size'] ?? 0);
      $name = $_FILES[$field]['name'] ?? '';
      if ($size <= 0) { return null; }
      if ($size > 5 * 1024 * 1024) { throw new Exception('Archivo supera 5MB: ' . $field); }
      $lower = strtolower($name);
      if ($pdfOnly) {
        if (!preg_match('/\.pdf$/', $lower)) { throw new Exception('Solo PDF permitido en ' . $field); }
      } else {
        if (!preg_match('/\.(jpg|jpeg|png|pdf)$/', $lower)) { throw new Exception('Formato no permitido en ' . $field); }
      }
      $subdir = 'uploads/creditos/' . date('Y') . '/' . date('m');
      $absDir = getAbsolutePath($subdir);
      if (!is_dir($absDir)) {
        $mk = @mkdir($absDir, 0775, true);
        if (!$mk) {
          try { (new Logger())->logEditar('creditos.solicitudes', 'Fallo mkdir en uploads', null, ['absDir'=>$absDir,'field'=>$field]); } catch (Throwable $ignored) {}
          throw new Exception('No se pudo crear directorio destino de uploads');
        }
      }
      if (!is_writable($absDir)) { @chmod($absDir, 0777); }
      if (!is_writable($absDir)) {
        try { (new Logger())->logEditar('creditos.solicitudes', 'Directorio no escribible', null, ['absDir'=>$absDir,'field'=>$field]); } catch (Throwable $ignored) {}
        throw new Exception('Directorio de uploads no escribible');
      }
      $base = pathinfo($lower, PATHINFO_FILENAME);
      $safe = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?: 'archivo';
      $ext = pathinfo($lower, PATHINFO_EXTENSION);
      $filename = $safe . '-' . uniqid() . '.' . $ext;
      $dest = rtrim($absDir,'/') . '/' . $filename;
      if (!move_uploaded_file($tmp, $dest)) {
        try { (new Logger())->logEditar('creditos.solicitudes', 'move_uploaded_file falló', null, ['tmp'=>$tmp,'dest'=>$dest,'field'=>$field]); } catch (Throwable $ignored) {}
        throw new Exception('No se pudo guardar archivo: ' . $field);
      }
      if (!file_exists($dest)) {
        try { (new Logger())->logEditar('creditos.solicitudes', 'Archivo no accesible tras move_uploaded_file', null, ['dest'=>$dest, 'field'=>$field]); } catch (Throwable $ignored) {}
        throw new Exception('Archivo no accesible tras guardar: ' . $field);
      }
      return getBaseUrl() . $subdir . '/' . $filename;
    };
    if ($estado === 'Con Datacrédito') {
      $ruta = $saveFile('archivo_datacredito', true);
      if (!$ruta) { throw new Exception('Debe adjuntar PDF de Datacrédito'); }
      $data['archivo_datacredito'] = $ruta;
    } elseif ($estado === 'Con Estudio') {
      $ruta = $saveFile('archivo_estudio', true);
      if (!$ruta) { throw new Exception('Debe adjuntar el archivo de Estudio (PDF)'); }
      $data['archivo_estudio'] = $ruta;
    } elseif ($estado === 'Guardado') {
      $pagare = $saveFile('archivo_pagare_pdf', true);
      $amort = $saveFile('archivo_amortizacion', true);
      $libranza = $saveFile('archivo_libranza', true);
      if (!$pagare || !$amort || !$libranza) { throw new Exception('Debe adjuntar Pagaré, Amortización y Libranza (PDF)'); }
      $sifone = (string)($_POST['numero_credito_sifone'] ?? '');
      if ($sifone === '' || !preg_match('/^\d+$/', $sifone)) { throw new Exception('Número crédito SIFONE inválido'); }
      $data['archivo_pagare_pdf'] = $pagare;
      $data['archivo_amortizacion'] = $amort;
      $data['archivo_libranza'] = $libranza;
      $data['numero_credito_sifone'] = $sifone;
    }
    $result = $ctrl->cambiarEstado($id,$estado,$data);
    
    // Log de cambio de estado de solicitud
    if ($result['success']) {
      try {
        (new Logger())->logEditar('creditos.solicitudes', 'Cambiar estado de solicitud', ['id' => $id], ['id' => $id, 'estado' => $estado, 'archivos' => $data]);
      } catch (Throwable $ignored) {}
    }
    
    echo json_encode($result);
  } else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    $id = (int)($input['id'] ?? 0);
    $estado = (string)($input['estado'] ?? '');
    if ($id<=0 || $estado==='') throw new Exception('Parámetros inválidos');
    // Para estados que requieren archivo, bloquear JSON simple
    if (in_array($estado, ['Con Datacrédito','Con Estudio','Guardado'], true)) {
      throw new Exception('Este cambio de estado requiere adjuntar archivos');
    }
    
    $result = $ctrl->cambiarEstado($id,$estado,[]);
    
    // Log de cambio de estado de solicitud
    if ($result['success']) {
      try {
        (new Logger())->logEditar('creditos.solicitudes', 'Cambiar estado de solicitud', ['id' => $id], ['id' => $id, 'estado' => $estado]);
      } catch (Throwable $ignored) {}
    }
    
    echo json_encode($result);
  }
} catch (Throwable $e) {
  try { (new Logger())->logEditar('creditos.solicitudes', 'Error al cambiar estado de solicitud', null, ['error' => $e->getMessage()]); } catch (Throwable $ignored) {}
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


