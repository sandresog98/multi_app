<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Asociado.php';
require_once '../../../models/Logger.php';
require_once '../../../utils/dictionary.php';

$auth = new AuthController();
$auth->requireModule('oficina.asociados');
$currentUser = $auth->getCurrentUser();
$asociadoModel = new Asociado();
$logger = new Logger();

$message = '';
$error = '';

// Cambiar estado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cambiar_estado') {
  $cedula = $_POST['cedula'] ?? '';
  $estado = (int)($_POST['estado'] ?? 1);
  if ($cedula) {
    $ok = $asociadoModel->updateEstado($cedula, $estado);
    if ($ok) { $message = 'Estado actualizado'; $logger->logEditar('asociados','Cambio de estado',['cedula'=>$cedula],['cedula'=>$cedula,'estado'=>$estado]); }
    else { $error = 'No se pudo actualizar estado'; }
  }
}

$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : 'activo';
$productos = isset($_GET['productos']) ? trim($_GET['productos']) : '';
$data = $asociadoModel->getAsociados($page, 20, $search, $estado, $productos);
$kpis = $asociadoModel->getKpisProductosEstados();

$pageTitle = 'Asociados - Oficina';
$currentPage = 'asociados';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-users me-2"></i><?php echo dict_label('sifone_asociados','__titulo','Asociados'); ?></h1>
      </div>

      <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

      <div class="row g-3 mb-2">
        <div class="col-sm-6 col-xl-2">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Activos</div><div class="h4 mb-0"><?php echo (int)($kpis['activos']??0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Inactivos</div><div class="h4 mb-0"><?php echo (int)($kpis['inactivos']??0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Sin productos</div><div class="h4 mb-0"><?php echo (int)($kpis['sin_productos']??0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Con productos</div><div class="h4 mb-0"><?php echo (int)($kpis['con_productos']??0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Con créditos</div><div class="h4 mb-0"><?php echo (int)($kpis['con_creditos']??0); ?></div></div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="card text-bg-light"><div class="card-body"><div class="small text-muted">Con productos y crédito</div><div class="h4 mb-0"><?php echo (int)($kpis['con_ambos']??0); ?></div></div></div>
        </div>
      </div>

      <form class="row g-2 mb-3" method="GET">
        <div class="col-md-6"><input class="form-control" name="search" placeholder="Buscar por cédula, nombre, email o teléfono" value="<?php echo htmlspecialchars($search); ?>"></div>
        <div class="col-md-3">
          <select name="estado" class="form-select">
            <option value="">Todos</option>
            <option value="activo" <?php echo $estado==='activo'?'selected':''; ?>>Activos</option>
            <option value="inactivo" <?php echo $estado==='inactivo'?'selected':''; ?>>Inactivos</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="productos" class="form-select">
            <option value="">Todos (productos/créditos)</option>
            <option value="sin_productos" <?php echo $productos==='sin_productos'?'selected':''; ?>>Sin productos</option>
            <option value="con_productos" <?php echo $productos==='con_productos'?'selected':''; ?>>Con productos</option>
            <option value="con_creditos" <?php echo $productos==='con_creditos'?'selected':''; ?>>Con créditos</option>
            <option value="con_ambos" <?php echo $productos==='con_ambos'?'selected':''; ?>>Con productos y crédito</option>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
      </form>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-dark"><tr>
              <th><?php echo dict_label('sifone_asociados','cedula','Cédula'); ?></th>
              <th><?php echo dict_label('sifone_asociados','nombre','Nombre'); ?></th>
              <th><?php echo dict_label('sifone_asociados','mail','Email'); ?></th>
              <th><?php echo dict_label('sifone_asociados','celula','Teléfono'); ?></th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($data['asociados'] as $a): ?>
              <tr>
                <td><?php echo htmlspecialchars($a['cedula']); ?></td>
                <td><?php echo htmlspecialchars($a['nombre']); ?></td>
                <td><?php echo htmlspecialchars($a['mail']); ?></td>
                <td><?php echo htmlspecialchars($a['celula']); ?></td>
                <td><span class="badge <?php echo $a['estado_activo'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $a['estado_activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                <td>
                  <div class="btn-group">
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="cambiar_estado">
                      <input type="hidden" name="cedula" value="<?php echo htmlspecialchars($a['cedula']); ?>">
                      <input type="hidden" name="estado" value="<?php echo $a['estado_activo']?0:1; ?>">
                      <button class="btn btn-sm btn-outline-warning" title="Cambiar estado"><i class="fas fa-toggle-on"></i></button>
                    </form>
                    <a class="btn btn-sm btn-outline-info" href="asociados_detalle.php?cedula=<?php echo urlencode($a['cedula']); ?>" title="Ver detalle"><i class="fas fa-eye"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div></div>

      <?php if (($data['pages'] ?? 1) > 1): 
        $pages = (int)$data['pages'];
        $cur = (int)$data['current_page'];
        $start = max(1, $cur - 2);
        $end = min($pages, $cur + 2);
      ?>
        <nav aria-label="Paginación">
          <ul class="pagination justify-content-center">
            <?php if ($cur > 1): $prev=$cur-1; ?>
              <li class="page-item"><a class="page-link" href="?page=<?php echo $prev; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>">Anterior</a></li>
            <?php endif; ?>
            <?php if ($start > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>">1</a></li>
              <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($i=$start; $i<=$end; $i++): ?>
              <li class="page-item <?php echo $i==$cur?'active':''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($end < $pages): ?>
              <?php if ($end < $pages-1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
              <li class="page-item"><a class="page-link" href="?page=<?php echo $pages; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>"><?php echo $pages; ?></a></li>
            <?php endif; ?>
            <?php if ($cur < $pages): $next=$cur+1; ?>
              <li class="page-item"><a class="page-link" href="?page=<?php echo $next; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>">Siguiente</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>


