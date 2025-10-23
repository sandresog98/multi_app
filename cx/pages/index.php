<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/paths.php';

$auth = new CxAuthController();
$auth->requireAuth();
$nombre = $_SESSION['cx_nombre'] ?? '';
$pageTitle = 'Inicio';
$heroTitle = 'Portal de Asociados';
$heroSubtitle = 'Bienvenido' . ($nombre ? ', ' . htmlspecialchars($nombre) : '');
include __DIR__ . '/../views/layouts/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
    <style>
      /* Estilos para el logo de la cooperativa */
      .coop-logo {
        max-width: 360px !important;
        height: auto !important;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1)) !important;
        transition: transform 0.3s ease !important;
      }
      .coop-logo:hover {
        transform: scale(1.05) !important;
      }
      .logo-container {
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(10px) !important;
        border-radius: 15px !important;
        padding: 25px !important;
        margin-bottom: 25px !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
      }
      
      /* Responsive para el logo */
      @media (max-width: 480px) {
        .coop-logo {
          max-width: 280px !important;
        }
        .logo-container {
          padding: 20px !important;
          margin-bottom: 20px !important;
        }
      }
    </style>
    <main class="container py-3">
      <!-- Logo de la cooperativa -->
      <div class="text-center logo-container">
        <img src="<?php echo cx_repo_base_url(); ?>/cx/assets/img/logo_coop_blue.png" 
             alt="Logo Cooperativa" 
             class="coop-logo">
      </div>
      
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <div class="card card-link mb-2">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="icon-circle me-3"><i class="fa-solid fa-user"></i></div>
                <div>
                  <div class="fw-semibold">Perfil</div>
                  <div class="text-muted small">Tu información personal y productos asignados.</div>
                </div>
                <div class="ms-auto"><a class="btn btn-sm btn-primary" href="../modules/perfil/pages/index.php">Ver</a></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="card card-link mb-2">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="icon-circle me-3"><i class="fa-solid fa-wallet"></i></div>
                <div>
                  <div class="fw-semibold">Información Monetaria</div>
                  <div class="text-muted small">Consulta tu información financiera y aportes.</div>
                </div>
                <div class="ms-auto"><a class="btn btn-sm btn-primary" href="../modules/monetario/pages/index.php">Ver</a></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="card card-link mb-2">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="icon-circle me-3"><i class="fa-solid fa-credit-card"></i></div>
                <div>
                  <div class="fw-semibold">Información de Créditos</div>
                  <div class="text-muted small">Consulta tus créditos y detalles de pago.</div>
                </div>
                <div class="ms-auto"><a class="btn btn-sm btn-primary" href="../modules/creditos/pages/index.php">Ver</a></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
<?php include __DIR__ . '/../views/layouts/footer.php'; ?>


