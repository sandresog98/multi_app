<?php
// Redirección simple a la carpeta UI
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$target = ($base ? $base : '') . '/ui/';
header('Location: ' . $target, true, 302);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="0; url=<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>">
  <title>Redirigiendo…</title>
</head>
<body>
  <p>Redirigiendo a <a href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>">UI</a>…</p>
</body>
</html>

