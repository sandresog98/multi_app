<?php
function cx_build_reset_email_html(string $nombre, string $codigo): string {
    $safeNombre = htmlspecialchars($nombre ?: 'Asociado(a)');
    $safeCodigo = htmlspecialchars($codigo);
    return <<<HTML
<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>Recuperación de contraseña</title>
    <style>
      body { background:#f5f7fb; font-family: Arial, Helvetica, sans-serif; margin:0; padding:24px; color:#111827; }
      .card { max-width:520px; margin:0 auto; background:#ffffff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.06); overflow:hidden; }
      .header { background:linear-gradient(135deg,#0052cc,#2e77ff); color:#fff; padding:24px; text-align:center; }
      .header h1 { margin:0; font-size:20px; letter-spacing:0.3px; }
      .content { padding:20px 24px 28px; }
      .greet { font-size:16px; margin:0 0 12px; }
      .p { margin:0 0 14px; color:#374151; }
      .code { font-size:28px; letter-spacing:4px; font-weight:700; background:#f3f4f6; padding:12px 16px; display:inline-block; border-radius:8px; color:#111827; }
      .footer { font-size:12px; color:#6b7280; padding:16px 24px; border-top:1px solid #eef2f7; background:#fafbff; }
    </style>
  </head>
  <body>
    <div class="card">
      <div class="header">
        <h1>Coomultiunion — Recuperación de contraseña</h1>
      </div>
      <div class="content">
        <p class="greet">Hola, {$safeNombre}</p>
        <p class="p">Usa el siguiente código para crear o restablecer tu contraseña de acceso:</p>
        <div class="code">{$safeCodigo}</div>
        <p class="p">Este código vence en 20 minutos. Si no solicitaste este proceso, puedes ignorar este mensaje.</p>
      </div>
      <div class="footer">No responder a este email. Enviado automáticamente por el sistema.</div>
    </div>
  </body>
  </html>
HTML;
}
?>


