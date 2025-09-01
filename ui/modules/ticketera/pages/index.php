<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('ticketera');
$currentUser = $auth->getCurrentUser();
$pageTitle = 'Ticketera - Resumen';
$currentPage = 'ticketera_resumen';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-project-diagram me-2"></i>Resumen</h1>
      </div>

      <div class="card"><div class="card-body">
        <div class="text-muted">Próximamente: indicadores y panel de estado de tickets.</div>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>


