<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Producto.php';
require_once '../../../models/Logger.php';

$auth = new AuthController();
$auth->requireModule('oficina.productos');
$currentUser = $auth->getCurrentUser();
$productoModel = new Producto();
$logger = new Logger();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create') {
      $nombre = trim($_POST['nombre'] ?? '');
      $descripcion = trim($_POST['descripcion'] ?? '');
      $parametros = trim($_POST['parametros'] ?? '');
      $valor_minimo = (float)($_POST['valor_minimo'] ?? 0);
      $valor_maximo = (float)($_POST['valor_maximo'] ?? 0);
      $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
      $prioridad = (int)($_POST['prioridad'] ?? 100);
      if ($nombre === '' || $valor_minimo < 0 || $valor_maximo < $valor_minimo) {
        $error = 'Validación: nombre requerido, valores coherentes.';
      } else {
        $res = $productoModel->create($nombre, $descripcion, $parametros, $valor_minimo, $valor_maximo, $prioridad, $estado);
        if ($res['success']) {
          $message = 'Producto creado';
          $logger->logCrear('productos', 'Creación de producto', compact('nombre','descripcion','parametros','valor_minimo','valor_maximo','prioridad','estado'));
        } else { $error = $res['message']; }
      }
    } elseif ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $before = $productoModel->getById($id);
      $nombre = trim($_POST['nombre'] ?? '');
      $descripcion = trim($_POST['descripcion'] ?? '');
      $parametros = trim($_POST['parametros'] ?? '');
      $valor_minimo = (float)($_POST['valor_minimo'] ?? 0);
      $valor_maximo = (float)($_POST['valor_maximo'] ?? 0);
      $prioridad = (int)($_POST['prioridad'] ?? 100);
      $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
      $res = $productoModel->update($id, $nombre, $descripcion, $parametros, $valor_minimo, $valor_maximo, $prioridad, $estado);
      if ($res['success']) {
        $message = 'Producto actualizado';
        $logger->logEditar('productos', 'Actualización de producto', $before, compact('id','nombre','descripcion','parametros','valor_minimo','valor_maximo','prioridad','estado'));
      } else { $error = $res['message']; }
    } elseif ($action === 'cambiar_estado') {
      $id = (int)($_POST['id'] ?? 0);
      $estado = (int)($_POST['estado'] ?? 1);
      $before = $productoModel->getById($id);
      $res = $productoModel->cambiarEstado($id, $estado);
      if ($res['success']) { $message = 'Estado actualizado'; $logger->logEditar('productos','Cambio de estado de producto',$before,['id'=>$id,'estado'=>$estado]); }
      else { $error = $res['message']; }
    }
  } catch (Exception $e) { $error = $e->getMessage(); }
}

$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$data = $productoModel->getProductos($page, 20, $search, $estado);

$pageTitle = 'Productos - Oficina';
$currentPage = 'productos';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-box me-2"></i>Productos</h1>
        <div>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#crearProductoModal"><i class="fas fa-plus me-1"></i>Nuevo</button>
        </div>
      </div>

      <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

      <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4"><input class="form-control" name="search" placeholder="Buscar" value="<?php echo htmlspecialchars($search); ?>"></div>
        <div class="col-md-3">
          <select name="estado" class="form-select">
            <option value="">Todos</option>
            <option value="activo" <?php echo $estado==='activo'?'selected':''; ?>>Activos</option>
            <option value="inactivo" <?php echo $estado==='inactivo'?'selected':''; ?>>Inactivos</option>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
      </form>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-dark"><tr>
              <th>ID</th><th>Nombre</th><th>Descripción</th><th>Parámetros</th><th>Rango Valor</th><th>Prioridad</th><th>Estado</th><th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($data['productos'] as $p): ?>
              <tr>
                <td><?php echo (int)$p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                <td><?php echo htmlspecialchars($p['descripcion']); ?></td>
                <td><small class="text-muted d-inline-block text-truncate" style="max-width:220px"><?php echo htmlspecialchars($p['parametros']); ?></small></td>
                <td><?php echo '$' . number_format($p['valor_minimo'], 0); ?> - <?php echo '$' . number_format($p['valor_maximo'], 0); ?></td>
                <td><?php echo (int)($p['prioridad'] ?? 100); ?></td>
                <td><span class="badge <?php echo $p['estado_activo'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $p['estado_activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                <td>
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarProductoModal<?php echo $p['id']; ?>"><i class="fas fa-edit"></i></button>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="cambiar_estado">
                      <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                      <input type="hidden" name="estado" value="<?php echo $p['estado_activo']?0:1; ?>">
                      <button class="btn btn-sm btn-outline-warning" title="Cambiar estado"><i class="fas fa-toggle-on"></i></button>
                    </form>
                  </div>
                </td>
              </tr>

              <!-- Modal Editar -->
              <div class="modal fade" id="editarProductoModal<?php echo $p['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Producto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST"><div class="modal-body">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                  <div class="mb-2"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?php echo htmlspecialchars($p['nombre']); ?>" required></div>
                  <div class="mb-2"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion"><?php echo htmlspecialchars($p['descripcion']); ?></textarea></div>
                  <div class="mb-2"><label class="form-label">Parámetros</label><textarea class="form-control" name="parametros"><?php echo htmlspecialchars($p['parametros']); ?></textarea></div>
                  <div class="row g-2 mb-2"><div class="col"><label class="form-label">Valor mínimo</label><input type="number" step="0.01" class="form-control" name="valor_minimo" value="<?php echo htmlspecialchars($p['valor_minimo']); ?>" required></div>
                  <div class="col"><label class="form-label">Valor máximo</label><input type="number" step="0.01" class="form-control" name="valor_maximo" value="<?php echo htmlspecialchars($p['valor_maximo']); ?>" required></div></div>
                  <div class="mb-2"><label class="form-label">Prioridad (menor = más arriba)</label><input type="number" class="form-control" name="prioridad" value="<?php echo (int)($p['prioridad'] ?? 100); ?>" min="1" max="9999"></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="estado" id="estado_<?php echo $p['id']; ?>" <?php echo $p['estado_activo']? 'checked':''; ?>><label class="form-check-label" for="estado_<?php echo $p['id']; ?>">Activo</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
                </form></div></div></div>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div></div>

      <!-- Paginación simple -->
      <?php if (($data['pages'] ?? 1) > 1): $pages=$data['pages']; $cur=$data['current_page']; ?>
        <nav><ul class="pagination justify-content-center">
          <?php for ($i=1;$i<=$pages;$i++): ?><li class="page-item <?php echo $i==$cur?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        </ul></nav>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="crearProductoModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nuevo Producto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST"><div class="modal-body">
    <input type="hidden" name="action" value="create">
    <div class="mb-2"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required></div>
    <div class="mb-2"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion"></textarea></div>
    <div class="mb-2"><label class="form-label">Parámetros</label><textarea class="form-control" name="parametros"></textarea></div>
    <div class="row g-2 mb-2"><div class="col"><label class="form-label">Valor mínimo</label><input type="number" step="0.01" class="form-control" name="valor_minimo" required></div>
    <div class="col"><label class="form-label">Valor máximo</label><input type="number" step="0.01" class="form-control" name="valor_maximo" required></div></div>
    <div class="mb-2"><label class="form-label">Prioridad (menor = más arriba)</label><input type="number" class="form-control" name="prioridad" value="100" min="1" max="9999"></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="estado" id="estado_new" checked><label class="form-check-label" for="estado_new">Activo</label></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Crear</button></div>
  </form>
</div></div></div>

<?php include '../../../views/layouts/footer.php'; ?>


