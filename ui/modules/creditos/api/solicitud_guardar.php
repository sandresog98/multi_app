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

  // Campos base
  $nombres = trim($_POST['nombres'] ?? '');
  $identificacion = trim($_POST['identificacion'] ?? '');
  $celular = trim($_POST['celular'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $tipo = trim($_POST['tipo'] ?? '');
  $monto = $_POST['monto_deseado'] !== null ? (float)$_POST['monto_deseado'] : null;
  if (!$nombres || !$identificacion || !$celular || !$email || !$tipo) { throw new Exception('Datos requeridos incompletos'); }

  // Helper guardado de archivo
  $saveFile = function(string $field, bool $pdfOnly = false) {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { return null; }
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
    // Directorios: Filesystem en ui/uploads, URL pública en uploads
    $subdirFs = 'uploads/creditos/' . date('Y') . '/' . date('m');
    $subdirWeb = $subdirFs; // para URL pública usamos el mismo subpath relativo a /ui
    $absDir = getAbsolutePath($subdirFs);
    if (!is_dir($absDir)) { if (!mkdir($absDir, 0775, true)) { throw new Exception('No se pudo crear directorio'); } }
    if (!is_writable($absDir)) { @chmod($absDir, 0777); }
    if (!is_writable($absDir)) { throw new Exception('Directorio no escribible'); }
    $base = pathinfo($lower, PATHINFO_FILENAME);
    $safe = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?: 'archivo';
    $ext = pathinfo($lower, PATHINFO_EXTENSION);
    $filename = $safe . '-' . uniqid() . '.' . $ext;
    $dest = rtrim($absDir,'/') . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) { throw new Exception('No se pudo guardar archivo: ' . $field); }
    return getBaseUrl() . $subdirWeb . '/' . $filename;
  };

  $fields = [
    'dep_nomina_1' => false,
    'dep_nomina_2' => false,
    'dep_cert_laboral' => false,
    'dep_simulacion_pdf' => true,
    'ind_decl_renta' => false,
    'ind_simulacion_pdf' => true,
    'ind_codeudor_nomina_1' => false,
    'ind_codeudor_nomina_2' => false,
    'ind_codeudor_cert_laboral' => false,
  ];
  $paths = [];
  foreach ($fields as $k => $pdfOnly) {
    $paths[$k] = $saveFile($k, $pdfOnly);
  }

  $stmt = $pdo->prepare("INSERT INTO creditos_solicitudes (
      nombres, identificacion, celular, email, monto_deseado, tipo,
      dep_nomina_1, dep_nomina_2, dep_cert_laboral, dep_simulacion_pdf,
      ind_decl_renta, ind_simulacion_pdf, ind_codeudor_nomina_1, ind_codeudor_nomina_2, ind_codeudor_cert_laboral,
      creado_por
    ) VALUES (?,?,?,?,?, ?, ?,?,?,?, ?,?,?,?, ?, ?) ");
  $ok = $stmt->execute([
    $nombres, $identificacion, $celular, $email, $monto, $tipo,
    $paths['dep_nomina_1'], $paths['dep_nomina_2'], $paths['dep_cert_laboral'], $paths['dep_simulacion_pdf'],
    $paths['ind_decl_renta'], $paths['ind_simulacion_pdf'], $paths['ind_codeudor_nomina_1'], $paths['ind_codeudor_nomina_2'], $paths['ind_codeudor_cert_laboral'],
    (int)($user['id'] ?? null)
  ]);

  if (!$ok) { throw new Exception('No se pudo crear la solicitud'); }
  $id = (int)$pdo->lastInsertId();
  // Historial de creación
  try {
    $hist = $pdo->prepare('INSERT INTO creditos_historial (solicitud_id, usuario_id, accion, estado_anterior, estado_nuevo) VALUES (?,?,?,?,?)');
    $hist->execute([$id, (int)($user['id'] ?? null), 'crear', null, 'Creado']);
  } catch (Throwable $ignored) {}
  // Log de sistema
  try { (new Logger())->logCrear('creditos','Crear solicitud',[ 'id'=>$id, 'identificacion'=>$identificacion, 'tipo'=>$tipo ]); } catch (Throwable $ignored) {}
  echo json_encode(['success' => true, 'id' => $id]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


