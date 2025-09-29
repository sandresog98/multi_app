<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/config/paths.php';

$auth = new CxAuthController();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc = $_POST['documento'] ?? '';
    $res = $auth->requestReset($doc);
    if ($res['success'] ?? false) { 
        header('Location: password_verify.php?doc=' . urlencode($doc) . '&success=1'); 
        exit; 
    }
    else { $error = $res['message'] ?? 'No fue posible iniciar el proceso'; }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Crear o recuperar contraseña</title>
    <link rel="icon" type="image/x-icon" href="../ui/assets/favicons/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
      body { background: #0ea5e9; min-height: 100vh; }
      .screen { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px; }
      .card-mobile { width:100%; max-width:420px; border:0; border-radius:16px; box-shadow: 0 12px 30px rgba(0,0,0,.18); overflow:hidden; }
      .card-header { background: linear-gradient(135deg,#0ea5e9,#2563eb); color:#fff; text-align:center; padding:24px; }
      .form-section { padding:20px; }
      .btn-primary { background:#0ea5e9; border-color:#0ea5e9; }
      .btn-primary:active, .btn-primary:hover { background:#0284c7; border-color:#0284c7; }
      .input-group-text { background:#f8fafc; }
    </style>
  </head>
  <body>
    <div class="screen">
      <div class="card card-mobile">
        <div class="card-header">
          <img src="../ui/assets/img/logo.png" alt="Coomultiunion" height="130" class="mb-1">
          <div class="fw-semibold">Crear o recuperar contraseña</div>
        </div>
        <div class="form-section">
          <?php if ($error): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
          <form method="post" class="mb-3">
            <div class="mb-3">
              <label class="form-label">Número de documento</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                <input name="documento" inputmode="numeric" pattern="[0-9]*" class="form-control" placeholder="Ingresa tu documento" required>
              </div>
            </div>
            <div class="d-grid gap-2">
              <button class="btn btn-primary btn-lg" type="submit"><i class="fa-solid fa-paper-plane me-1"></i> Enviar código</button>
              <a class="btn btn-outline-primary" href="password_verify.php"><i class="fa-solid fa-key me-1"></i> Ya tengo un código</a>
              <a class="btn btn-outline-secondary" href="login.php"><i class="fa-solid fa-arrow-left me-1"></i> Volver al inicio</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
 </html>


