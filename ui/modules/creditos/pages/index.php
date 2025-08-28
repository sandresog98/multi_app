<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('creditos');
$currentUser = $auth->getCurrentUser();

$pageTitle = 'Gestión Créditos - Resumen';
$currentPage = 'creditos';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Gestión Créditos</h1>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="card-title mb-0">Créditos por tipo</h5>
            </div>
            <div class="row align-items-center">
              <div class="col-8">
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0" id="tablaTipos"></table>
                </div>
              </div>
              <div class="col-4"><canvas id="chartTipos" height="110"></canvas></div>
            </div>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="card-title mb-0">Créditos por estado</h5>
            </div>
            <div class="row align-items-center">
              <div class="col-8">
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0" id="tablaEstados"></table>
                </div>
              </div>
              <div class="col-4"><canvas id="chartEstados" height="110"></canvas></div>
            </div>
          </div></div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch('../api/estadisticas.php');
    const json = await res.json();
    const porTipo = json.por_tipo || [];
    const porEstado = json.por_estado || [];
    const palette = ['#4e79a7','#f28e2c','#e15759','#76b7b2','#edc949','#59a14f','#edc948','#b07aa1'];

    // Tabla tipos
    const tTipos = document.getElementById('tablaTipos');
    tTipos.innerHTML = '<thead class="table-light"><tr><th>Tipo</th><th class="text-end">Cantidad</th></tr></thead><tbody>'+porTipo.map(i=>`<tr><td>${i.tipo}</td><td class="text-end">${i.cantidad}</td></tr>`).join('')+'</tbody>';

    const ctx1 = document.getElementById('chartTipos').getContext('2d');
    new Chart(ctx1, {
      type: 'doughnut',
      data: {
        labels: porTipo.map(i=>i.tipo),
        datasets: [{ data: porTipo.map(i=>Number(i.cantidad||0)), backgroundColor: palette }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    });

    // Tabla estados
    const tEstados = document.getElementById('tablaEstados');
    tEstados.innerHTML = '<thead class="table-light"><tr><th>Estado</th><th class="text-end">Cantidad</th></tr></thead><tbody>'+porEstado.map(i=>`<tr><td>${i.estado}</td><td class="text-end">${i.cantidad}</td></tr>`).join('')+'</tbody>';

    const ctx2 = document.getElementById('chartEstados').getContext('2d');
    new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: porEstado.map(i=>i.estado),
        datasets: [{ data: porEstado.map(i=>Number(i.cantidad||0)), backgroundColor: palette }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    });
  } catch (e) { /* noop */ }
});
</script>


