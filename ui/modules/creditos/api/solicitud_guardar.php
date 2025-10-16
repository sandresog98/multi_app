<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';
require_once '../../../models/Logger.php';
require_once '../../../utils/FileUploadManager.php';

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

  // Helper guardado de archivo usando FileUploadManager
  $saveFile = function(string $field, bool $pdfOnly = false) use ($user) {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { 
      return null; 
    }
    
    // Configurar opciones según el tipo de archivo
    $options = [
      'maxSize' => 5 * 1024 * 1024, // 5MB
      'prefix' => 'credito_' . $field,
      'userId' => $user['id'] ?? '',
      'webPath' => getBaseUrl() . 'assets/uploads/creditos'
    ];
    
    if ($pdfOnly) {
      $options['allowedExtensions'] = ['pdf'];
    } else {
      $options['allowedExtensions'] = ['jpg', 'jpeg', 'png', 'pdf'];
    }
    
    // Directorio base para créditos
    $baseDir = __DIR__ . '/../../../assets/uploads/creditos';
    
    try {
      $result = FileUploadManager::saveUploadedFile($_FILES[$field], $baseDir, $options);
      return $result['webUrl'];
    } catch (Exception $e) {
      try { 
        (new Logger())->logEditar('creditos.solicitudes', 'Error guardando archivo', null, [
          'field' => $field, 
          'error' => $e->getMessage(),
          'fileName' => $_FILES[$field]['name'] ?? 'unknown'
        ]); 
      } catch (Throwable $ignored) {}
      throw new Exception('Error guardando archivo ' . $field . ': ' . $e->getMessage());
    }
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


