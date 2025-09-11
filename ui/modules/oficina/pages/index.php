<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Dashboard.php';

$authController = new AuthController();
$authController->requireModule('oficina.resumen');
$currentUser = $authController->getCurrentUser();
$dash = new Dashboard();
$usuarios = $dash->getAsociadosTotalesYActivos();
$sinAsignar = $dash->getPseCashSinAsignarCount();
$distAsignPse = $dash->getDistribucionTipoAsignacionPse();
$dineroPorTipo = $dash->getDineroPorTipoTransaccionAsignada();
$trx7 = $dash->getTransaccionesRecibidasPorDias(7);
$trx30 = $dash->getTransaccionesRecibidasPorDias(30);
$pagosCashQrSinAsig = $dash->getPagosCashQrSinAsignadosCount();

$pageTitle = 'Oficina - Multi v2';
$currentPage = 'oficina';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-building me-2"></i>Oficina - Resumen</h1>
      </div>

      <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Asociados totales</div>
            <div class="h3 mb-0"><?php echo (int)$usuarios['total']; ?> (<span class="text-success"><?php echo (int)$usuarios['activos']; ?></span>)</div>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Trx PSE Sin Asignar</div><div class="h3 mb-0"><?php echo (int)$sinAsignar['pse']; ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Trx Cash/QR Sin Asignar</div><div class="h3 mb-0"><?php echo (int)$sinAsignar['cash_qr']; ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Pagos Cash/QR Sin Asignar</div><div class="h3 mb-0"><?php echo (int)$pagosCashQrSinAsig; ?></div></div></div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Asignación de PSE (conteo por tipo)</strong></div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Tipo asignación</th><th class="text-end">Cantidad</th></tr></thead>
                      <tbody>
                        <?php foreach (($distAsignPse ?? []) as $r): ?>
                        <tr><td><?php echo htmlspecialchars($r['tipo']); ?></td><td class="text-end"><?php echo (int)$r['cantidad']; ?></td></tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4"><canvas id="miniChartAsignPse" height="110"></canvas></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Dinero por categoría (PSE / Cash / QR)</strong></div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Tipo</th><th class="text-end">Total</th></tr></thead>
                      <tbody>
                        <?php foreach (($dineroPorTipo ?? []) as $r): ?>
                        <tr><td><?php echo htmlspecialchars(strtoupper($r['tipo'])); ?></td><td class="text-end"><?php echo '$'.number_format((float)($r['total'] ?? 0),0); ?></td></tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4"><canvas id="miniChartDinero" height="110"></canvas></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Transacciones recibidas (7 días)</strong></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light"><tr><th>Tipo</th><th class="text-end">Cantidad</th></tr></thead>
                  <tbody>
                    <?php foreach (($trx7 ?? []) as $r): ?>
                      <tr><td><?php echo htmlspecialchars(strtoupper($r['tipo'])); ?></td><td class="text-end"><?php echo (int)$r['cantidad']; ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Transacciones recibidas (30 días)</strong></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light"><tr><th>Tipo</th><th class="text-end">Cantidad</th></tr></thead>
                  <tbody>
                    <?php foreach (($trx30 ?? []) as $r): ?>
                      <tr><td><?php echo htmlspecialchars(strtoupper($r['tipo'])); ?></td><td class="text-end"><?php echo (int)$r['cantidad']; ?></td></tr>
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
  const asign = <?php echo json_encode($distAsignPse ?? []); ?>;
  const dinero = <?php echo json_encode($dineroPorTipo ?? []); ?>;
  const ctxA = document.getElementById('miniChartAsignPse');
  if (ctxA && asign && asign.length){
    const labels = asign.map(r=>r.tipo||'desconocido');
    const values = asign.map(r=>Number(r.cantidad||0));
    new Chart(ctxA, { type:'doughnut', data:{ labels, datasets:[{ data: values, backgroundColor:['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcaf0'] }] }, options:{ plugins:{ legend:{ display:false } }, cutout:'70%' } });
  }
  const ctxD = document.getElementById('miniChartDinero');
  if (ctxD && dinero && dinero.length){
    const labels = dinero.map(r=>String(r.tipo||'').toUpperCase());
    const values = dinero.map(r=>Number(r.total||0));
    new Chart(ctxD, { type:'doughnut', data:{ labels, datasets:[{ data: values, backgroundColor:['#198754','#0d6efd','#ffc107','#dc3545','#6c757d','#0dcaf0'] }] }, options:{ plugins:{ legend:{ display:false } }, cutout:'70%' } });
  }
});
</script>

