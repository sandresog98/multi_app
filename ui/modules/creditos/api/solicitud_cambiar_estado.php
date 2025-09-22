<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';
require_once '../controllers/CreditosController.php';

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
      if (!is_dir($absDir)) { if (!mkdir($absDir, 0775, true)) { throw new Exception('No se pudo crear directorio'); } }
      if (!is_writable($absDir)) { @chmod($absDir, 0777); }
      if (!is_writable($absDir)) { throw new Exception('Directorio no escribible'); }
      $base = pathinfo($lower, PATHINFO_FILENAME);
      $safe = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?: 'archivo';
      $ext = pathinfo($lower, PATHINFO_EXTENSION);
      $filename = $safe . '-' . uniqid() . '.' . $ext;
      $dest = rtrim($absDir,'/') . '/' . $filename;
      if (!move_uploaded_file($tmp, $dest)) { throw new Exception('No se pudo guardar archivo: ' . $field); }
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
    echo json_encode($ctrl->cambiarEstado($id,$estado,$data));
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
    echo json_encode($ctrl->cambiarEstado($id,$estado,[]));
  }
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


