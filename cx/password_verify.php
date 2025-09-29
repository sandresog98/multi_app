<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/config/paths.php';

$auth = new CxAuthController();
$message = '';
$error = '';
$prefillDoc = $_GET['doc'] ?? '';

// Mostrar mensaje de éxito si viene de password_request
if (!empty($_GET['success'])) {
    $message = 'Código enviado a tu correo electrónico. Revisa tu bandeja de entrada.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc = $_POST['documento'] ?? '';
    $code = $_POST['codigo'] ?? '';
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $res = $auth->confirmReset($doc, $code, $pass);
        if ($res['success'] ?? false) { 
            header('Location: login.php?success=1'); 
            exit; 
        }
        else { $error = $res['message'] ?? 'No fue posible actualizar la contraseña'; }
    }
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Verificar código</title>
    <link rel="icon" type="image/x-icon" href="../ui/assets/favicons/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
      body { 
        background: #0ea5e9;
        min-height: 100vh; 
      }
      .screen { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px; }
      .card-mobile { width:100%; max-width:420px; border:0; border-radius:16px; box-shadow: 0 12px 30px rgba(0,0,0,.18); overflow:hidden; background: white; }
      .card-header { 
        background: url('assets/img/imagen_motivacion.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color:#fff; 
        text-align:center; 
        padding:24px; 
        position: relative;
        min-height: 200px;
      }
      .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,#0ea5e9,#2563eb);
        opacity: 0.6;
        z-index: 1;
      }
      .card-header img,
      .card-header .fw-semibold {
        position: relative;
        z-index: 2;
      }
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
          <div class="fw-semibold">Verificar código</div>
        </div>
        <div class="form-section">
          <?php if ($message): ?><div class="alert alert-success py-2"><i class="fa-solid fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
          <?php if ($error): ?><div class="alert alert-danger py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Documento</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                <input name="documento" inputmode="numeric" pattern="[0-9]*" class="form-control" placeholder="Ingresa tu documento" value="<?php echo htmlspecialchars($prefillDoc); ?>" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Código de verificación</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-shield"></i></span>
                <input name="codigo" inputmode="numeric" pattern="[0-9]*" class="form-control" maxlength="6" placeholder="Código de 6 dígitos" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Nueva contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input name="password" type="password" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres" required>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label">Confirmar contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input name="password2" type="password" class="form-control" minlength="6" placeholder="Repite tu contraseña" required>
              </div>
            </div>
            <div class="d-grid gap-2">
              <button class="btn btn-primary btn-lg" type="submit"><i class="fa-solid fa-check me-1"></i> Guardar contraseña</button>
              <a class="btn btn-outline-secondary" href="password_request.php"><i class="fa-solid fa-arrow-left me-1"></i> Volver</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
 </html>


