<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';
require_once '../../../models/Logger.php';

$authController = new AuthController();
$authController->requireRole('admin');

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
              <thead><tr><th>ID</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Detalle</th><th>IP</th><th>Fecha</th></tr></thead>
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

<?php include '../../../views/layouts/footer.php'; ?>


