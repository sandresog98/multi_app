<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Cobranza.php';

$authController = new AuthController();
$authController->requireModule('cobranza.resumen');
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Cobranza - Resumen';
$currentPage = 'cobranza';
include '../../../views/layouts/header.php';

$model = new Cobranza();
try { $kpis = $model->obtenerKpis(); } catch (Throwable $e) { $kpis = ["asociados_mora"=>0,"total_mora"=>0,"promedio_diav"=>0,"sin_comunicacion"=>0]; }
try { $bandas = $model->distribucionMoraBandas(); } catch (Throwable $e) { $bandas = []; }
try { $rangos = $model->distribucionUltimaComunicacion(); } catch (Throwable $e) { $rangos = []; }
try { $top7 = $model->comunicacionesPorUsuario(7, 10); } catch (Throwable $e) { $top7 = []; }
try { $top30 = $model->comunicacionesPorUsuario(30, 10); } catch (Throwable $e) { $top30 = []; }
try { $estadosPorAsociado = $model->estadosUltimaComunicacionAsociado(); } catch (Throwable $e) { $estadosPorAsociado = []; }
?>

<div class="container-fluid">
	<div class="row">
		<?php include '../../../views/layouts/sidebar.php'; ?>
		<main class="col-12 main-content">
			<div class="pt-3 pb-2 mb-3 border-bottom">
				<h1 class="h2"><i class="fas fa-phone me-2"></i>Cobranza - Resumen</h1>
			</div>

			<div class="row g-3">
				<div class="col-sm-6 col-xl-3">
					<div class="card text-bg-light"><div class="card-body">
						<div class="small text-muted">Asociados con mora</div>
						<div class="h3 mb-0"><?php echo (int)$kpis['asociados_mora']; ?></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card text-bg-light"><div class="card-body">
						<div class="small text-muted">Total mora</div>
						<div class="h3 mb-0"><?php echo '$'.number_format((float)$kpis['total_mora'],0); ?></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card text-bg-light"><div class="card-body">
						<div class="small text-muted">Promedio días mora</div>
						<div class="h3 mb-0"><?php echo number_format((float)$kpis['promedio_diav'],1); ?></div>
					</div></div>
				</div>
				<div class="col-sm-6 col-xl-3">
					<div class="card text-bg-light"><div class="card-body">
						<div class="small text-muted">Sin comunicación</div>
						<div class="h3 mb-0"><?php echo (int)$kpis['sin_comunicacion']; ?></div>
					</div></div>
				</div>
			</div>

			<div class="row g-3 mt-1">
				<div class="col-lg-4">
					<div class="card h-100">
						<div class="card-header"><strong>Distribución por estado de mora</strong></div>
						<div class="card-body">
							<?php if (!empty($bandas)): ?>
								<div class="row align-items-center">
									<div class="col-8">
										<div class="table-responsive">
											<table class="table table-sm align-middle mb-0">
												<thead class="table-light"><tr><th>Estado de mora</th><th class="text-end">Asociados</th></tr></thead>
												<tbody>
													<?php foreach ($bandas as $b): ?>
													<tr>
														<td><?php echo htmlspecialchars($b['banda']); ?></td>
														<td class="text-end"><?php echo (int)$b['asociados']; ?></td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
									<div class="col-4">
										<canvas id="miniChartBandas" height="110"></canvas>
									</div>
								</div>
							<?php else: ?>
								<div class="text-muted small">Sin datos.</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="card h-100">
						<div class="card-header"><strong>Última comunicación</strong></div>
						<div class="card-body">
							<?php if (!empty($rangos)): ?>
								<div class="row align-items-center">
									<div class="col-8">
										<div class="table-responsive">
											<table class="table table-sm align-middle mb-0">
												<thead class="table-light"><tr><th>Rango</th><th class="text-end">Asociados</th></tr></thead>
												<tbody>
													<?php foreach ($rangos as $r): ?>
													<tr>
														<td><?php echo htmlspecialchars($r['rango']); ?></td>
														<td class="text-end"><?php echo (int)$r['asociados']; ?></td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
									<div class="col-4">
										<canvas id="miniChartRangos" height="110"></canvas>
									</div>
								</div>
							<?php else: ?>
								<div class="text-muted small">Sin datos.</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="card h-100">
						<div class="card-header"><strong>Estados por usuario</strong></div>
						<div class="card-body">
							<?php if (!empty($estadosPorAsociado)): ?>
								<div class="row align-items-center">
									<div class="col-8">
										<div class="table-responsive">
											<table class="table table-sm align-middle mb-0">
												<thead class="table-light"><tr><th>Estado</th><th class="text-end">Asociados</th></tr></thead>
												<tbody>
													<?php foreach ($estadosPorAsociado as $e): ?>
													<tr>
														<td><?php echo htmlspecialchars($e['estado'] ?? 'Sin comunicación'); ?></td>
														<td class="text-end"><?php echo (int)($e['asociados'] ?? 0); ?></td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
									<div class="col-4">
										<canvas id="miniChartEstadosUlt" height="110"></canvas>
									</div>
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
						<div class="card-header"><strong>Top usuarios por comunicaciones (7 días)</strong></div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-sm align-middle">
									<thead class="table-light"><tr><th>Usuario</th><th class="text-end">Comunicaciones</th></tr></thead>
									<tbody>
										<?php foreach ($top7 as $u): ?>
										<tr><td><?php echo htmlspecialchars($u['nombre']); ?></td><td class="text-end"><?php echo (int)$u['cantidad']; ?></td></tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="card h-100">
						<div class="card-header"><strong>Top usuarios por comunicaciones (30 días)</strong></div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-sm align-middle">
									<thead class="table-light"><tr><th>Usuario</th><th class="text-end">Comunicaciones</th></tr></thead>
									<tbody>
										<?php foreach ($top30 as $u): ?>
										<tr><td><?php echo htmlspecialchars($u['nombre']); ?></td><td class="text-end"><?php echo (int)$u['cantidad']; ?></td></tr>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const bandas = <?php echo json_encode($bandas, JSON_UNESCAPED_UNICODE); ?>;
  const rangos = <?php echo json_encode($rangos, JSON_UNESCAPED_UNICODE); ?>;
  const estadosUlt = <?php echo json_encode($estadosPorAsociado, JSON_UNESCAPED_UNICODE); ?>;

  (() => {
    const ctx = document.getElementById('miniChartBandas'); if (!ctx || !bandas?.length) return;
    const labels = bandas.map(b => b.banda);
    const data = bandas.map(b => Number(b.asociados||0));
    new Chart(ctx, { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: ['#0d6efd','#ffc107','#dc3545'] }] }, options: { plugins: { legend: { display: false } }, cutout: '70%' } });
  })();

  (() => {
    const ctx = document.getElementById('miniChartRangos'); if (!ctx || !rangos?.length) return;
    const labels = rangos.map(r => r.rango);
    const data = rangos.map(r => Number(r.asociados||0));
    const colorMap = {
      'Sin comunicación': '#dc3545',
      'Muy reciente': '#198754',
      'Reciente': '#0d6efd',
      'Intermedia': '#fd7e14',
      'Lejana': '#6c757d',
      'Muy lejana': '#000000'
    };
    const colors = labels.map(l => colorMap[l] || '#6c757d');
    new Chart(ctx, { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: colors }] }, options: { plugins: { legend: { display: false } }, cutout: '70%' } });
  })();

  // Estados por usuario: pintar badges de color por estado común
  (function(){
    const ctx = document.getElementById('miniChartEstadosUlt'); if (!ctx || !estadosUlt?.length) return;
    const labels = estadosUlt.map(e => e.estado || 'Sin comunicación');
    const data = estadosUlt.map(e => Number(e.asociados||0));
    const colorMap = {
      'Sin comunicación': '#dc3545',
      'Informa de pago realizado': '#198754',
      'Comprometido a realizar el pago': '#0d6efd',
      'Sin respuesta': '#6c757d'
    };
    const colors = labels.map(l => colorMap[l] || '#6c757d');
    new Chart(ctx, { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: colors }] }, options: { plugins: { legend: { display: false } }, cutout: '70%' } });
  })();
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>


