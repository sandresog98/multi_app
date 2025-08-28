<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Boleta.php';

$authController = new AuthController();
$authController->requireModule('boleteria.resumen');
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Boletería - Resumen';
$currentPage = 'boleteria';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-ticket-alt me-2"></i>Boletería - Resumen</h1>
      </div>
      <?php $boletaModel = new Boleta(); $data = $boletaModel->getResumenKpis(); $k = $data['kpis'] ?? []; ?>
      <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="small text-muted">Total boletas</div><div class="h4 mb-0"><?php echo (int)($k['total'] ?? 0); ?></div></div><i class="fas fa-ticket-alt fa-lg text-primary"></i></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="small text-muted">Disponibles</div><div class="h4 mb-0"><?php echo (int)($k['disponibles'] ?? 0); ?></div></div><i class="fas fa-check-circle fa-lg text-success"></i></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="small text-muted">Vendidas</div><div class="h4 mb-0"><?php echo (int)($k['vendidas'] ?? 0); ?></div></div><i class="fas fa-shopping-cart fa-lg text-primary"></i></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="small text-muted">Anuladas</div><div class="h4 mb-0"><?php echo (int)($k['anuladas'] ?? 0); ?></div></div><i class="fas fa-ban fa-lg text-secondary"></i></div></div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Boletas por categoría (Top 10)</strong></div>
            <div class="card-body">
              <canvas id="chartCategorias" height="160"></canvas>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Boletas vendidas por día (14 días)</strong></div>
            <div class="card-body">
              <canvas id="chartVendidasDia" height="160"></canvas>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cat = <?php echo json_encode($data['por_categoria'] ?? []); ?>;
  const vd = <?php echo json_encode($data['vendidas_dia'] ?? []); ?>;

  // Categorías: barras
  const ctx1 = document.getElementById('chartCategorias');
  if (ctx1 && cat && cat.length) {
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: cat.map(x => x.categoria || '—'),
        datasets: [{ label: 'Total', data: cat.map(x => Number(x.total||0)), backgroundColor: 'rgba(54, 162, 235, 0.5)' },
                   { label: 'Vendidas', data: cat.map(x => Number(x.vendidas||0)), backgroundColor: 'rgba(75, 192, 192, 0.5)' }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });
  }

  // Vendidas por día: línea
  const ctx2 = document.getElementById('chartVendidasDia');
  if (ctx2 && vd && vd.length) {
    new Chart(ctx2, {
      type: 'line',
      data: { labels: vd.map(x => x.fecha), datasets: [{ label: 'Vendidas', data: vd.map(x => Number(x.cantidad||0)), fill: false, borderColor: '#198754', tension: 0.2 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  }
});
</script>

<?php /* EOF */ ?>


