<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';
require_once '../../../models/Logger.php';

$authController = new AuthController();
$authController->requireModule('logs.gestion');

$pageTitle = 'Logs del Sistema - Multi v2';
$currentPage = 'logs';
$currentUser = $authController->getCurrentUser();
$conn = getConnection();
$logger = new Logger();

// Filtros
$filtros = [];
foreach (['usuario','modulo','accion','nivel','fecha_desde','fecha_hasta'] as $k) {
    if (!empty($_GET[$k])) $filtros[$k] = $_GET[$k];
}
$filtros['limite'] = 200;

$logs = $logger->getLogs($filtros);
$estadisticas = $logger->getEstadisticas($_GET['fecha_desde'] ?? null, $_GET['fecha_hasta'] ?? null);

// Listas para filtros
$modulos = $conn->query("SELECT DISTINCT modulo FROM control_logs ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
$acciones = $conn->query("SELECT DISTINCT accion FROM control_logs ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
$niveles = $conn->query("SELECT DISTINCT nivel FROM control_logs ORDER BY nivel")->fetchAll(PDO::FETCH_COLUMN);
$usuarios = $conn->query("SELECT id, usuario, nombre_completo FROM control_usuarios ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);

include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i>Logs del Sistema</h1>
      </div>

      <div class="row mb-4">
        <div class="col-md-2"><div class="card bg-primary text-white"><div class="card-body text-center"><h6>Total Logs</h6><h3><?php echo (int)$estadisticas['total_logs']; ?></h3></div></div></div>
        <div class="col-md-2"><div class="card bg-success text-white"><div class="card-body text-center"><h6>Creaciones</h6><h3><?php echo (int)$estadisticas['creaciones']; ?></h3></div></div></div>
        <div class="col-md-2"><div class="card bg-warning text-white"><div class="card-body text-center"><h6>Ediciones</h6><h3><?php echo (int)$estadisticas['ediciones']; ?></h3></div></div></div>
        <div class="col-md-2"><div class="card bg-danger text-white"><div class="card-body text-center"><h6>Eliminaciones</h6><h3><?php echo (int)$estadisticas['eliminaciones']; ?></h3></div></div></div>
        <div class="col-md-2"><div class="card bg-secondary text-white"><div class="card-body text-center"><h6>Usuarios Activos</h6><h3><?php echo (int)$estadisticas['usuarios_activos']; ?></h3></div></div></div>
      </div>

      <div class="card mb-4"><div class="card-header"><h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5></div>
        <div class="card-body">
          <form method="GET" class="row g-3">
            <div class="col-md-2"><label class="form-label">Usuario</label>
              <select name="usuario" class="form-select"><option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                  <option value="<?php echo $u['id']; ?>" <?php echo (($_GET['usuario'] ?? '') == $u['id'])?'selected':''; ?>><?php echo htmlspecialchars($u['nombre_completo']); ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-2"><label class="form-label">Módulo</label>
              <select name="modulo" class="form-select"><option value="">Todos</option>
                <?php foreach ($modulos as $m): ?><option value="<?php echo $m; ?>" <?php echo (($_GET['modulo'] ?? '') == $m)?'selected':''; ?>><?php echo ucfirst($m); ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-2"><label class="form-label">Acción</label>
              <select name="accion" class="form-select"><option value="">Todas</option>
                <?php foreach ($acciones as $a): ?><option value="<?php echo $a; ?>" <?php echo (($_GET['accion'] ?? '') == $a)?'selected':''; ?>><?php echo ucfirst($a); ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-2"><label class="form-label">Nivel</label>
              <select name="nivel" class="form-select"><option value="">Todos</option>
                <?php foreach ($niveles as $n): ?><option value="<?php echo $n; ?>" <?php echo (($_GET['nivel'] ?? '') == $n)?'selected':''; ?>><?php echo ucfirst($n); ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input type="date" name="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>"></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input type="date" name="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>"></div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Filtrar</button>
              <a href="logs.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Limpiar</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>Registro de Actividad</h5></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead><tr><th>ID</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Detalle</th><th>IP</th><th>Fecha</th><th>Acciones</th></tr></thead>
              <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?php echo (int)$log['id']; ?></td>
                  <td><?php echo htmlspecialchars($log['nombre_completo'] ?? $log['usuario'] ?? 'Sistema'); ?></td>
                  <td><?php echo ucfirst($log['accion']); ?></td>
                  <td><?php echo ucfirst($log['modulo']); ?></td>
                  <td><span class="text-truncate d-inline-block" style="max-width:240px" title="<?php echo htmlspecialchars($log['detalle']); ?>"><?php echo htmlspecialchars($log['detalle']); ?></span></td>
                  <td><small class="text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                  <td><small class="text-muted"><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></small></td>
                  <td><button class="btn btn-sm btn-outline-info" onclick="verDetalleLog(<?php echo (int)$log['id']; ?>)"><i class="fas fa-eye"></i></button></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="logDetailModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Detalle del Log</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="logDetailContent"><div class="text-center p-3">Seleccione un log para ver detalles</div></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
</div></div></div>

<script>
function verDetalleLog(id) {
  const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
  document.getElementById('logDetailContent').innerHTML = '<div class="text-center p-3"><div class="spinner-border"></div><div>Cargando...</div></div>';
  modal.show();
  fetch('../api/obtener_detalle.php?id=' + encodeURIComponent(id))
    .then(r => r.json())
    .then(resp => {
      if (!resp.success) throw new Error(resp.message || 'Error');
      const log = resp.data;
      let html = '';
      html += '<div class="row">';
      html += '<div class="col-md-6"><h6 class="text-primary">Información General</h6><table class="table table-sm">' +
              `<tr><td><strong>ID:</strong></td><td>#${log.id}</td></tr>` +
              `<tr><td><strong>Usuario:</strong></td><td>${(log.nombre_completo || log.usuario || 'Sistema')}</td></tr>` +
              `<tr><td><strong>Módulo:</strong></td><td><span class="badge bg-primary">${log.modulo}</span></td></tr>` +
              `<tr><td><strong>Acción:</strong></td><td><span class="badge bg-info">${log.accion}</span></td></tr>` +
              `<tr><td><strong>Nivel:</strong></td><td><span class="badge bg-${log.nivel_color}">${log.nivel}</span></td></tr>` +
              `<tr><td><strong>Fecha:</strong></td><td>${log.timestamp_formateado}</td></tr>` +
              `<tr><td><strong>IP:</strong></td><td><code>${log.ip_address||''}</code></td></tr>` +
              '</table></div>';
      html += '<div class="col-md-6"><h6 class="text-primary">Detalles</h6><div class="alert alert-light"><strong>Descripción:</strong><br>' + (log.detalle || 'Sin descripción') + '</div></div>';
      html += '</div>';
      if (log.datos_anteriores_formateados && Object.keys(log.datos_anteriores_formateados).length) {
        html += '<hr><h6 class="text-warning">Datos Anteriores</h6><div class="table-responsive"><table class="table table-sm table-warning"><thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>';
        for (const [k,v] of Object.entries(log.datos_anteriores_formateados)) {
          html += `<tr><td><strong>${k}</strong></td><td>${typeof v==='object'?JSON.stringify(v):v}</td></tr>`;
        }
        html += '</tbody></table></div>';
      }
      if (log.datos_nuevos_formateados && Object.keys(log.datos_nuevos_formateados).length) {
        html += '<hr><h6 class="text-success">Datos Nuevos</h6><div class="table-responsive"><table class="table table-sm table-success"><thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>';
        for (const [k,v] of Object.entries(log.datos_nuevos_formateados)) {
          html += `<tr><td><strong>${k}</strong></td><td>${typeof v==='object'?JSON.stringify(v):v}</td></tr>`;
        }
        html += '</tbody></table></div>';
      }
      document.getElementById('logDetailContent').innerHTML = html;
    })
    .catch(err => {
      document.getElementById('logDetailContent').innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${err.message}</div>`;
    });
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>

