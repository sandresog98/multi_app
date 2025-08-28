<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Dashboard.php';

$authController = new AuthController();
$authController->requireModule('oficina.resumen');
$currentUser = $authController->getCurrentUser();
$dash = new Dashboard();
$kpis = $dash->getKpis();
$fresh = $dash->getFreshness();
$pagosStatus = $dash->getPagosStatus();
$cargas = $dash->getCargasResumen();
$txRecientes = $dash->getTransaccionesRecientes(10);

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
          <div class="card text-bg-light">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="small text-muted">Asociados activos</div>
                  <div class="h4 mb-0"><?php echo (int)$kpis['asociados_activos']; ?></div>
                </div>
                <i class="fas fa-users fa-lg text-primary"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="small text-muted">Asociados inactivos</div>
                  <div class="h4 mb-0"><?php echo (int)$kpis['asociados_inactivos']; ?></div>
                </div>
                <i class="fas fa-user-slash fa-lg text-secondary"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="small text-muted">Productos activos</div>
                  <div class="h4 mb-0"><?php echo (int)$kpis['productos_activos']; ?></div>
                </div>
                <i class="fas fa-box-open fa-lg text-success"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="small text-muted">Asignaciones activas</div>
                  <div class="h4 mb-0"><?php echo (int)$kpis['asignaciones_activas']; ?></div>
                </div>
                <i class="fas fa-link fa-lg text-info"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6 col-xl-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="small text-muted">PSE aprobada sin asignar</div>
              <div class="display-6 mb-0"><?php echo (int)$kpis['pse_aprobada_sin_asignar']; ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="small text-muted">Cash/QR confirmados hoy</div>
              <div class="display-6 mb-0"><?php echo (int)$kpis['cash_confirmados_hoy']; ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="small text-muted">Transacciones hoy (cant)</div>
              <div class="display-6 mb-0"><?php echo (int)$kpis['transacciones_hoy_cantidad']; ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="small text-muted">Transacciones hoy (valor)</div>
              <div class="display-6 mb-0"><?php echo '$'.number_format((float)$kpis['transacciones_hoy_valor'],0); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Data freshness (últimas cargas completadas)</strong></div>
            <div class="card-body">
              <?php if (empty($fresh)): ?>
                <div class="text-muted small">Sin cargas completadas.</div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Tipo</th><th>Última actualización</th></tr></thead>
                  <tbody>
                    <?php foreach ($fresh as $f): ?>
                      <tr><td><?php echo htmlspecialchars($f['tipo']); ?></td><td><?php echo htmlspecialchars($f['ultima_actualizacion']); ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Estado de pagos</strong></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-6">
                  <div class="small text-muted mb-1">PSE</div>
                  <div class="d-flex flex-column gap-1 small">
                    <div>Sin asignar: <strong><?php echo (int)$pagosStatus['pse']['sin_asignar']['count']; ?></strong></div>
                    <div>Parcial: <strong><?php echo (int)$pagosStatus['pse']['parcial']['count']; ?></strong></div>
                    <div>Completado: <strong><?php echo (int)$pagosStatus['pse']['completado']['count']; ?></strong></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="small text-muted mb-1">Cash/QR</div>
                  <div class="d-flex flex-column gap-1 small">
                    <div>Sin asignar: <strong><?php echo (int)$pagosStatus['cash_qr']['sin_asignar']['count']; ?></strong></div>
                    <div>Parcial: <strong><?php echo (int)$pagosStatus['cash_qr']['parcial']['count']; ?></strong></div>
                    <div>Completado: <strong><?php echo (int)$pagosStatus['cash_qr']['completado']['count']; ?></strong></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Cargas (control_cargas)</strong></div>
            <div class="card-body">
              <div class="small mb-2">Pend: <strong><?php echo (int)$cargas['pendiente']; ?></strong> · Proc: <strong><?php echo (int)$cargas['procesando']; ?></strong> · Ok: <strong><?php echo (int)$cargas['completado']; ?></strong> · Error: <strong><?php echo (int)$cargas['error']; ?></strong></div>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>ID</th><th>Tipo</th><th>Archivo</th><th>Estado</th><th>Fecha</th></tr></thead>
                  <tbody>
                    <?php foreach (($cargas['recientes'] ?? []) as $r): ?>
                      <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo htmlspecialchars($r['tipo']); ?></td>
                        <td class="text-truncate" style="max-width:220px" title="<?php echo htmlspecialchars($r['archivo_ruta']); ?>"><?php echo htmlspecialchars(basename($r['archivo_ruta'])); ?></td>
                        <td><?php echo htmlspecialchars($r['estado']); ?></td>
                        <td><small><?php echo htmlspecialchars($r['fecha_actualizacion'] ?: $r['fecha_creacion']); ?></small></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Transacciones recientes</strong></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>ID</th><th>Origen</th><th>Ref</th><th class="text-end">Valor</th><th>Items</th><th>Fecha</th></tr></thead>
                  <tbody>
                    <?php foreach ($txRecientes as $t): ?>
                      <tr>
                        <td><?php echo (int)$t['id']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($t['origen_pago']); ?></span></td>
                        <td><?php echo htmlspecialchars($t['pse_id'] ?: $t['confiar_id']); ?></td>
                        <td class="text-end"><?php echo '$'.number_format((float)$t['valor_pago_total'],0); ?></td>
                        <td><?php echo (int)$t['items']; ?></td>
                        <td><small><?php echo htmlspecialchars($t['fecha_creacion']); ?></small></td>
                      </tr>
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

