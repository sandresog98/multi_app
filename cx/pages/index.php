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
<style>
body {
  background: linear-gradient(rgba(14, 165, 233, 0.8), rgba(37, 99, 235, 0.8)), url('../assets/img/imagen_motivacion.png');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}
</style>
    <main class="container py-3">
      <div class="card card-link mb-2">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="icon-circle me-3"><i class="fa-solid fa-chart-pie"></i></div>
            <div>
              <div class="fw-semibold">Resumen Financiero</div>
              <div class="text-muted small">Consulta tus aportes, créditos y productos.</div>
            </div>
            <div class="ms-auto"><a class="btn btn-sm btn-primary" href="../modules/resumen/pages/index.php">Ver</a></div>
          </div>
        </div>
      </div>
    </main>
<?php include __DIR__ . '/../views/layouts/footer.php'; ?>


