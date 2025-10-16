<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/TasasProductos.php';

$authController = new AuthController();
$authController->requireModule('oficina.tasas_productos');
$currentUser = $authController->getCurrentUser();
$tasasModel = new TasasProductos();

$message = '';
$error = '';

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $data = [
            'producto_id' => (int)($_POST['producto_id'] ?? 0),
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
            'tasa' => (float)($_POST['tasa'] ?? 0),
            'estado_activo' => isset($_POST['estado_activo'])
        ];
        
        $errores = $tasasModel->validarDatos($data);
        if (empty($errores)) {
            // Verificar si ya existe una tasa activa para el mismo producto en el mismo período
            if ($tasasModel->verificarTasaExistente($data['producto_id'], $data['fecha_inicio'], $data['fecha_fin'])) {
                $error = 'Ya existe una tasa activa para este producto en el período especificado';
            } else {
                $result = $tasasModel->crearTasa($data, $currentUser['id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            $error = implode('<br>', $errores);
        }
    }
    
    elseif ($action === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'producto_id' => (int)($_POST['producto_id'] ?? 0),
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
            'tasa' => (float)($_POST['tasa'] ?? 0),
            'estado_activo' => isset($_POST['estado_activo'])
        ];
        
        $errores = $tasasModel->validarDatos($data);
        if (empty($errores)) {
            // Verificar si ya existe una tasa activa para el mismo producto en el mismo período (excluyendo el actual)
            if ($tasasModel->verificarTasaExistente($data['producto_id'], $data['fecha_inicio'], $data['fecha_fin'], $id)) {
                $error = 'Ya existe una tasa activa para este producto en el período especificado';
            } else {
                $result = $tasasModel->actualizarTasa($id, $data, $currentUser['id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            $error = implode('<br>', $errores);
        }
    }
    
    elseif ($action === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $result = $tasasModel->eliminarTasa($id, $currentUser['id']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Obtener todas las tasas y productos
$tasas = $tasasModel->listarTasas();
$productos = $tasasModel->obtenerProductosActivos();

$pageTitle = 'Tasas de Productos - Multi v2';
$currentPage = 'tasas_productos';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-box me-2"></i>Tasas de Productos</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearTasaModal">
          <i class="fas fa-plus me-1"></i>Nueva Tasa
        </button>
      </div>

      <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="fas fa-check me-2"></i><?php echo $message; ?>
          <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
          <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <strong>Lista de Tasas de Productos</strong>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Producto</th>
                  <th class="text-center">Fecha Inicio</th>
                  <th class="text-center">Fecha Fin</th>
                  <th class="text-end">Tasa</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($tasas)): ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      <i class="fas fa-inbox fa-2x mb-2"></i><br>
                      No hay tasas de productos registradas
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($tasas as $tasa): ?>
                    <tr class="<?php echo $tasa['estado_activo'] ? '' : 'table-secondary'; ?>">
                      <td><?php echo (int)$tasa['id']; ?></td>
                      <td>
                        <div>
                          <strong><?php echo htmlspecialchars($tasa['producto_nombre']); ?></strong>
                        </div>
                      </td>
                      <td class="text-center"><?php echo date('d/m/Y', strtotime($tasa['fecha_inicio'])); ?></td>
                      <td class="text-center">
                        <?php echo $tasa['fecha_fin'] ? date('d/m/Y', strtotime($tasa['fecha_fin'])) : '-'; ?>
                      </td>
                      <td class="text-end"><?php echo number_format((float)$tasa['tasa'], 4); ?>%</td>
                      <td class="text-center">
                        <span class="badge <?php echo $tasa['estado_activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                          <?php echo $tasa['estado_activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group">
                          <button class="btn btn-sm btn-outline-primary" 
                                  data-bs-toggle="modal" 
                                  data-bs-target="#editarTasaModal<?php echo $tasa['id']; ?>"
                                  title="Editar">
                            <i class="fas fa-edit"></i>
                          </button>
                          <form method="POST" class="d-inline" 
                                onsubmit="return confirm('¿Está seguro de eliminar esta tasa de producto?');">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $tasa['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Modal Crear Tasa -->
<div class="modal fade" id="crearTasaModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nueva Tasa de Producto</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="crear">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Producto <span class="text-danger">*</span></label>
              <select name="producto_id" class="form-select" required>
                <option value="">Seleccione un producto</option>
                <?php foreach ($productos as $producto): ?>
                  <option value="<?php echo $producto['id']; ?>">
                    <?php echo htmlspecialchars($producto['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
              <input type="date" name="fecha_inicio" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Fin</label>
              <input type="date" name="fecha_fin" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tasa</label>
              <div class="input-group">
                <input type="number" name="tasa" class="form-control" step="0.0001" min="0" value="0">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="estado_activo" id="estado_activo" checked>
                <label class="form-check-label" for="estado_activo">Activo</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear Tasa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modales Editar (uno por cada tasa) -->
<?php foreach ($tasas as $tasa): ?>
<div class="modal fade" id="editarTasaModal<?php echo $tasa['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Tasa de Producto</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="editar">
          <input type="hidden" name="id" value="<?php echo $tasa['id']; ?>">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Producto <span class="text-danger">*</span></label>
              <select name="producto_id" class="form-select" required>
                <option value="">Seleccione un producto</option>
                <?php foreach ($productos as $producto): ?>
                  <option value="<?php echo $producto['id']; ?>" 
                          <?php echo $producto['id'] == $tasa['producto_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($producto['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
              <input type="date" name="fecha_inicio" class="form-control" required 
                     value="<?php echo $tasa['fecha_inicio']; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Fin</label>
              <input type="date" name="fecha_fin" class="form-control" 
                     value="<?php echo $tasa['fecha_fin'] ?: ''; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tasa</label>
              <div class="input-group">
                <input type="number" name="tasa" class="form-control" step="0.0001" min="0" 
                       value="<?php echo $tasa['tasa']; ?>">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="estado_activo" 
                       id="estado_activo_<?php echo $tasa['id']; ?>" 
                       <?php echo $tasa['estado_activo'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="estado_activo_<?php echo $tasa['id']; ?>">Activo</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar Tasa</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Establecer fecha de hoy como fecha por defecto
  const fechaInicioInputs = document.querySelectorAll('input[name="fecha_inicio"]');
  const hoy = new Date().toISOString().split('T')[0];
  fechaInicioInputs.forEach(input => {
    if (!input.value) {
      input.value = hoy;
    }
  });
  
  // Validación de fechas
  const fechaInicio = document.querySelector('input[name="fecha_inicio"]');
  const fechaFin = document.querySelector('input[name="fecha_fin"]');
  
  if (fechaInicio && fechaFin) {
    fechaInicio.addEventListener('change', function() {
      fechaFin.min = this.value;
    });
    
    fechaFin.addEventListener('change', function() {
      if (this.value && this.value < fechaInicio.value) {
        alert('La fecha de fin debe ser posterior a la fecha de inicio');
        this.value = '';
      }
    });
  }
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>
