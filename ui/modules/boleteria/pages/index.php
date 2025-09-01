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
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Boletas creadas</div><div class="h3 mb-0"><?php echo (int)($k['creadas'] ?? 0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Boletas contabilizadas</div><div class="h3 mb-0"><?php echo (int)($k['contabilizadas'] ?? 0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Boletas disponibles</div><div class="h3 mb-0"><?php echo (int)($k['disponibles'] ?? 0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Boletas anuladas</div><div class="h3 mb-0"><?php echo (int)($k['anuladas'] ?? 0); ?></div></div></div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Distribución por categoría (Disponibles)</strong></div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Categoría</th><th class="text-end">Boletas</th></tr></thead>
                      <tbody>
                        <?php foreach (($data['disponibles_cat'] ?? []) as $r): ?>
                        <tr><td><?php echo htmlspecialchars($r['categoria']); ?></td><td class="text-end"><?php echo (int)$r['cantidad']; ?></td></tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4">
                  <canvas id="miniChartDisp" height="110"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Distribución por categoría (Vendidas + Contabilizadas)</strong></div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Categoría</th><th class="text-end">Boletas</th></tr></thead>
                      <tbody>
                        <?php foreach (($data['vendidas_cat'] ?? []) as $r): ?>
                        <tr><td><?php echo htmlspecialchars($r['categoria']); ?></td><td class="text-end"><?php echo (int)$r['cantidad']; ?></td></tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4">
                  <canvas id="miniChartVend" height="110"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Asociados con más compra de boletas (1 año)</strong></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Asociado</th><th class="text-end">Boletas</th></tr></thead>
                  <tbody>
                    <?php foreach (($data['top_1y'] ?? []) as $u): ?>
                    <tr><td><?php echo htmlspecialchars(($u['nombre'] ?? '').' ('.$u['cedula'].')'); ?></td><td class="text-end"><?php echo (int)$u['cantidad']; ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Asociados con más compra de boletas (histórico)</strong></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Asociado</th><th class="text-end">Boletas</th></tr></thead>
                  <tbody>
                    <?php foreach (($data['top_all'] ?? []) as $u): ?>
                    <tr><td><?php echo htmlspecialchars(($u['nombre'] ?? '').' ('.$u['cedula'].')'); ?></td><td class="text-end"><?php echo (int)$u['cantidad']; ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
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
  const disp = <?php echo json_encode($data['disponibles_cat'] ?? []); ?>;
  const vend = <?php echo json_encode($data['vendidas_cat'] ?? []); ?>;
  const ctxD = document.getElementById('miniChartDisp');
  if (ctxD && disp && disp.length){
    const labels = disp.map(r=>r.categoria); const values = disp.map(r=>Number(r.cantidad||0));
    new Chart(ctxD, { type:'doughnut', data:{ labels, datasets:[{ data: values, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcaf0'] }] }, options:{ plugins:{ legend:{ display:false } }, cutout:'70%' } });
  }
  const ctxV = document.getElementById('miniChartVend');
  if (ctxV && vend && vend.length){
    const labels = vend.map(r=>r.categoria); const values = vend.map(r=>Number(r.cantidad||0));
    new Chart(ctxV, { type:'doughnut', data:{ labels, datasets:[{ data: values, backgroundColor: ['#198754','#0d6efd','#ffc107','#dc3545','#6c757d','#0dcaf0'] }] }, options:{ plugins:{ legend:{ display:false } }, cutout:'70%' } });
  }
});
</script>

<?php /* EOF */ ?>


