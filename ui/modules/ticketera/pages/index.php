<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../controllers/TicketeraController.php';

$auth = new AuthController();
$auth->requireModule('ticketera');
$currentUser = $auth->getCurrentUser();
$pageTitle = 'Ticketera - Resumen';
$currentPage = 'ticketera_resumen';
include '../../../views/layouts/header.php';

$ctl = new TicketeraController();
$ctl->requireResumen();
$data = $ctl->obtenerResumen();
$kpis = $data['kpis'] ?? ['tickets_creados'=>0,'tickets_abiertos'=>0,'promedio_dias_abierto'=>0,'aceptados_semana'=>0];
$dist = $data['dist'] ?? [];
$abiertosPor = $data['abiertosPor'] ?? [];
$cerrados7 = $data['cerrados7'] ?? [];
$solicitados7 = $data['solicitados7'] ?? [];
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-project-diagram me-2"></i>Resumen</h1>
      </div>

      <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Tickets creados</div>
            <div class="h3 mb-0"><?php echo (int)$kpis['tickets_creados']; ?></div>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Tickets abiertos</div>
            <div class="h3 mb-0"><?php echo (int)$kpis['tickets_abiertos']; ?></div>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Tiempo promedio de ticket abierto (horas)</div>
            <div class="h3 mb-0"><?php echo number_format((float)($kpis['promedio_horas_abierto'] ?? 0),1); ?></div>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Tickets aceptados esta semana</div>
            <div class="h3 mb-0"><?php echo (int)$kpis['aceptados_semana']; ?></div>
          </div></div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Distribución por estado (sin aceptados)</strong></div>
            <div class="card-body">
              <?php if (!empty($dist)): ?>
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Estado</th><th class="text-end">Tickets</th></tr></thead>
                      <tbody>
                        <?php foreach ($dist as $b): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($b['estado']); ?></td>
                          <td class="text-end"><?php echo (int)$b['cantidad']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4">
                  <canvas id="miniChartEstados" height="110"></canvas>
                </div>
              </div>
              <?php else: ?>
                <div class="text-muted small">Sin datos.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Tickets abiertos por usuario (responsable)</strong></div>
            <div class="card-body">
              <?php if (!empty($abiertosPor)): ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Usuario</th><th class="text-end">Abiertos</th></tr></thead>
                  <tbody>
                    <?php foreach ($abiertosPor as $u): ?>
                    <tr><td><?php echo htmlspecialchars($u['nombre']); ?></td><td class="text-end"><?php echo (int)$u['cantidad']; ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
                <div class="text-muted small">Sin datos.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Tickets cerrados por usuario (7 días) (responsable)</strong></div>
            <div class="card-body">
              <?php if (!empty($cerrados7)): ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Usuario</th><th class="text-end">Cerrados</th></tr></thead>
                  <tbody>
                    <?php foreach ($cerrados7 as $u): ?>
                    <tr><td><?php echo htmlspecialchars($u['nombre']); ?></td><td class="text-end"><?php echo (int)$u['cantidad']; ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
                <div class="text-muted small">Sin datos.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Tickets solicitados por usuario (7 días)</strong></div>
            <div class="card-body">
              <?php if (!empty($solicitados7)): ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Usuario solicitante</th><th class="text-end">Solicitados</th></tr></thead>
                  <tbody>
                    <?php foreach ($solicitados7 as $u): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($u['solicitante_nombre']); ?></td>
                      <td class="text-end"><?php echo (int)$u['cantidad']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
                <div class="text-muted small">Sin datos.</div>
              <?php endif; ?>
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
  const dist = <?php echo json_encode($dist, JSON_UNESCAPED_UNICODE); ?>;
  (() => {
    const ctx = document.getElementById('miniChartEstados'); if (!ctx || !dist?.length) return;
    const labels = dist.map(b => b.estado);
    const data = dist.map(b => Number(b.cantidad||0));
    const colorMap = {
      'Backlog': '#6c757d',
      'En Curso': '#0d6efd',
      'En Espera': '#ffc107',
      'Resuelto': '#0dcaf0',
      'Rechazado': '#dc3545'
    };
    const colors = labels.map(l => colorMap[l] || '#6c757d');
    new Chart(ctx, { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: colors }] }, options: { plugins: { legend: { display: false } }, cutout: '70%' } });
  })();
});
</script>

