<?php
require_once __DIR__ . '/../../config/paths.php';

// La función cx_repo_base_url() está definida en config/paths.php

$pageTitle = $pageTitle ?? 'Portal de Asociados';
$heroTitle = $heroTitle ?? '';
$heroSubtitle = $heroSubtitle ?? '';
$faviconUrl = cx_repo_base_url() . '/ui/assets/favicons/favicon.ico';
$logoUrl = cx_repo_base_url() . '/ui/assets/img/logo.png';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $faviconUrl; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo cx_repo_base_url(); ?>/cx/assets/css/footer.css">
    <style>
      .hero { background: linear-gradient(135deg,#0ea5e9,#2563eb); color:#fff; padding: 16px 0; }
      .hero .container { display:flex; align-items:center; justify-content:space-between; }
      .brand-logo { height: 56px; }
      .brand-title { font-weight: 700; margin: 0; }
      .brand-sub { opacity:.9; font-size: 12px; }
      .logout-btn { background: rgba(220,38,38,0.8); border: 1px solid rgba(220,38,38,0.9); color: white; padding: 8px 12px; border-radius: 8px; transition: all 0.3s ease; }
      .logout-btn:hover { background: rgba(220,38,38,1); color: white; }
      .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #ffffff; border-top: 1px solid #e5e7eb; z-index: 10; }
      .bottom-nav .nav-link { padding: 10px 6px; font-size: 12px; color:#6b7280; }
      .bottom-nav .nav-link.active { color:#0ea5e9; }
      main { padding-bottom: 64px; }
    </style>
  </head>
  <body class="bg-light">
    <?php if ($heroTitle !== '' || $heroSubtitle !== ''): ?>
    <header class="hero">
      <div class="container">
        <div class="text-start">
          <?php if ($heroTitle !== ''): ?><div class="brand-title"><?php echo htmlspecialchars($heroTitle); ?></div><?php endif; ?>
          <?php if ($heroSubtitle !== ''): ?><div class="brand-sub"><?php echo htmlspecialchars($heroSubtitle); ?></div><?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-3">
          <img src="<?php echo $logoUrl; ?>" alt="Coomultiunion" class="brand-logo">
          <?php if (isset($_SESSION['cx_cedula']) && !empty($_SESSION['cx_cedula'])): ?>
            <?php 
            $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
            $logoutUrl = (strpos($currentScript, '/modules/resumen/pages/') !== false) ? '../../../login.php?logout=1' : '../login.php?logout=1';
            ?>
            <a href="<?php echo $logoutUrl; ?>" class="logout-btn" title="Cerrar sesión">
              <i class="fa-solid fa-right-from-bracket"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </header>
    <?php endif; ?>


