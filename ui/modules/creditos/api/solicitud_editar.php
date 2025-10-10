<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';
require_once '../../../models/Logger.php';

header('Content-Type: application/json');

try {
  $auth = new AuthController();
  $auth->requireModule('creditos');
  $user = $auth->getCurrentUser();
  $pdo = getConnection();

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new Exception('ID inválido');

  // Verificar que la solicitud existe
  $stmt = $pdo->prepare("SELECT * FROM creditos_solicitudes WHERE id = ?");
  $stmt->execute([$id]);
  $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$solicitud) throw new Exception('Solicitud no encontrada');

  // Campos editables
  $nombres = trim($_POST['nombres'] ?? '');
  $identificacion = trim($_POST['identificacion'] ?? '');
  $celular = trim($_POST['celular'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $monto_deseado = $_POST['monto_deseado'] !== null ? (float)$_POST['monto_deseado'] : null;
  $numero_credito_sifone = trim($_POST['numero_credito_sifone'] ?? '');

  if (!$nombres || !$identificacion || !$celular || !$email) {
    throw new Exception('Datos requeridos incompletos');
  }

  // Helper para guardar archivos
  $saveFile = function(string $field, bool $pdfOnly = false) {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      return null;
    }
    $tmp = $_FILES[$field]['tmp_name'];
    $size = (int)($_FILES[$field]['size'] ?? 0);
    $name = $_FILES[$field]['name'] ?? '';
    if ($size <= 0) return null;
    if ($size > 5 * 1024 * 1024) throw new Exception('Archivo supera 5MB: ' . $field);
    
    $lower = strtolower($name);
    if ($pdfOnly) {
      if (!preg_match('/\.pdf$/', $lower)) throw new Exception('Solo PDF permitido en ' . $field);
    } else {
      if (!preg_match('/\.(jpg|jpeg|png|pdf)$/', $lower)) throw new Exception('Formato no permitido en ' . $field);
    }

    // Guardar en ui/assets/uploads/creditos/YYYY/MM
    $webSubdir = 'assets/uploads/creditos/' . date('Y') . '/' . date('m');
    $absDir = __DIR__ . '/../../../' . $webSubdir;
    if (!is_dir($absDir)) {
      $mk = @mkdir($absDir, 0775, true);
      if (!$mk) throw new Exception('No se pudo crear directorio destino de uploads');
    }
    if (!is_writable($absDir)) { @chmod($absDir, 0777); }
    if (!is_writable($absDir)) throw new Exception('Directorio de uploads no escribible');

    $base = pathinfo($lower, PATHINFO_FILENAME);
    $safe = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?: 'archivo';
    $ext = pathinfo($lower, PATHINFO_EXTENSION);
    $filename = $safe . '-' . uniqid() . '.' . $ext;
    $dest = rtrim($absDir,'/') . '/' . $filename;
    
    if (!move_uploaded_file($tmp, $dest)) throw new Exception('No se pudo guardar archivo: ' . $field);
    if (!file_exists($dest)) throw new Exception('Archivo no accesible tras guardar: ' . $field);
    
    return getBaseUrl() . $webSubdir . '/' . $filename;
  };

  // Helper para eliminar archivo anterior si existe
  $deleteOldFile = function($oldPath) {
    if (!$oldPath) return;
    $relativePath = str_replace(getBaseUrl(), '', $oldPath);
    $fullPath = __DIR__ . '/../../../' . $relativePath;
    if (file_exists($fullPath)) {
      @unlink($fullPath);
    }
  };

  // Campos de archivos editables
  $fileFields = [
    'dep_nomina_1' => false,
    'dep_nomina_2' => false,
    'dep_cert_laboral' => false,
    'dep_simulacion_pdf' => true,
    'ind_decl_renta' => false,
    'ind_simulacion_pdf' => true,
    'ind_codeudor_nomina_1' => false,
    'ind_codeudor_nomina_2' => false,
    'ind_codeudor_cert_laboral' => false,
    'archivo_datacredito' => true,
    'archivo_estudio' => true,
    'archivo_pagare_pdf' => true,
    'archivo_amortizacion' => true,
    'archivo_libranza' => true
  ];

  $updates = [];
  $params = [];

  // Actualizar campos básicos
  $updates[] = 'nombres = ?';
  $params[] = $nombres;
  $updates[] = 'identificacion = ?';
  $params[] = $identificacion;
  $updates[] = 'celular = ?';
  $params[] = $celular;
  $updates[] = 'email = ?';
  $params[] = $email;
  $updates[] = 'monto_deseado = ?';
  $params[] = $monto_deseado;
  $updates[] = 'numero_credito_sifone = ?';
  $params[] = $numero_credito_sifone;

  // Procesar archivos individuales (nueva funcionalidad)
  if (isset($_POST['campo']) && isset($_FILES['archivo'])) {
    $campo = $_POST['campo'];
    if (array_key_exists($campo, $fileFields)) {
      $pdfOnly = $fileFields[$campo];
      $newPath = $saveFile('archivo', $pdfOnly);
      if ($newPath) {
        // Eliminar archivo anterior si existe
        $deleteOldFile($solicitud[$campo]);
        $updates[] = "$campo = ?";
        $params[] = $newPath;
      }
    }
  } else {
    // Procesar archivos múltiples (funcionalidad original)
    foreach ($fileFields as $field => $pdfOnly) {
      if (isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $newPath = $saveFile($field, $pdfOnly);
        if ($newPath) {
          // Eliminar archivo anterior si existe
          $deleteOldFile($solicitud[$field]);
          $updates[] = "$field = ?";
          $params[] = $newPath;
        }
      }
    }
  }

  // Verificar si hay eliminaciones de archivos
  foreach ($fileFields as $field => $pdfOnly) {
    if (isset($_POST["eliminar_$field"]) && $_POST["eliminar_$field"] === '1') {
      $deleteOldFile($solicitud[$field]);
      $updates[] = "$field = NULL";
    }
  }

  if (empty($updates)) {
    throw new Exception('No hay cambios para guardar');
  }

  $updates[] = 'fecha_actualizacion = CURRENT_TIMESTAMP';
  $params[] = $id;

  $sql = "UPDATE creditos_solicitudes SET " . implode(', ', $updates) . " WHERE id = ?";
  $stmt = $pdo->prepare($sql);
  $ok = $stmt->execute($params);

  if (!$ok || $stmt->rowCount() === 0) {
    throw new Exception('No se pudo actualizar la solicitud');
  }

  // Registrar en historial
  try {
    $hist = $pdo->prepare('INSERT INTO creditos_historial (solicitud_id, usuario_id, accion, estado_anterior, estado_nuevo) VALUES (?,?,?,?,?)');
    $hist->execute([$id, (int)($user['id'] ?? null), 'editar', $solicitud['estado'], $solicitud['estado']]);
  } catch (Throwable $ignored) {}

  // Log del sistema
  try {
    (new Logger())->logEditar('creditos.solicitudes', 'Editar solicitud', ['id' => $id], [
      'id' => $id,
      'nombres' => $nombres,
      'identificacion' => $identificacion,
      'campos_actualizados' => count($updates) - 1
    ]);
  } catch (Throwable $ignored) {}

  echo json_encode(['success' => true, 'message' => 'Solicitud actualizada correctamente']);

} catch (Throwable $e) {
  try {
    (new Logger())->logEditar('creditos.solicitudes', 'Error al editar solicitud', null, ['error' => $e->getMessage()]);
  } catch (Throwable $ignored) {}
  
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
