<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Clausulas.php';
require_once '../../../models/Logger.php';

$auth = new AuthController();
$auth->requireModule('oficina.clausulas');
$currentUser = $auth->getCurrentUser();
$clausulasModel = new Clausulas();
$logger = new Logger();

$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'parametros' => $_POST['parametros'] ?? '',
            'requiere_archivo' => isset($_POST['requiere_archivo']),
            'estado_activo' => isset($_POST['estado_activo']),
            'creado_por' => $currentUser['id']
        ];
        
        $result = $clausulasModel->crearClausula($datos);
        if ($result['success']) {
            $message = $result['message'];
            $logger->logCrear('clausulas', 'Creación de cláusula', $datos);
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'parametros' => $_POST['parametros'] ?? '',
            'requiere_archivo' => isset($_POST['requiere_archivo']),
            'estado_activo' => isset($_POST['estado_activo']),
            'actualizado_por' => $currentUser['id']
        ];
        
        $result = $clausulasModel->actualizarClausula($id, $datos);
        if ($result['success']) {
            $message = $result['message'];
            $logger->logEditar('clausulas', 'Actualización de cláusula', ['id' => $id], $datos);
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $result = $clausulasModel->eliminarClausula($id);
        if ($result['success']) {
            $message = $result['message'];
            $logger->logEliminar('clausulas', 'Eliminación de cláusula', ['id' => $id]);
        } else {
            $error = $result['message'];
        }
    }
}

// Obtener lista de cláusulas
$clausulas = $clausulasModel->listarClausulas();

$pageTitle = 'Gestión de Cláusulas';
$currentPage = 'clausulas';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../../views/layouts/sidebar.php'; ?>
        <main class="col-12 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-file-contract me-2"></i>Gestión de Cláusulas</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearClausulaModal">
                    <i class="fas fa-plus me-1"></i>Nueva Cláusula
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Lista de cláusulas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Cláusulas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($clausulas)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-contract text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No hay cláusulas registradas</h5>
                            <p class="text-muted">Crea tu primera cláusula usando el botón "Nueva Cláusula"</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Parámetros</th>
                                        <th class="text-center">Requiere Archivo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Fecha Creación</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clausulas as $clausula): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($clausula['nombre']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($clausula['descripcion']); ?>">
                                                <?php echo htmlspecialchars($clausula['descripcion']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($clausula['parametros']); ?>">
                                                <?php echo htmlspecialchars($clausula['parametros']); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($clausula['requiere_archivo']): ?>
                                                <span class="badge bg-warning"><i class="fas fa-file me-1"></i>Sí</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($clausula['estado_activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo date('d/m/Y', strtotime($clausula['fecha_creacion'])); ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarClausulaModal<?php echo $clausula['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="eliminarClausula(<?php echo $clausula['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Crear Cláusula -->
<div class="modal fade" id="crearClausulaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nueva Cláusula</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Parámetros <span class="text-danger">*</span></label>
                            <textarea name="parametros" class="form-control" rows="2" required placeholder="Ej: Monto mínimo: $100,000, Plazo máximo: 12 meses"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="estado_activo" id="estado_activo" checked>
                                <label class="form-check-label" for="estado_activo">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Requiere Archivo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="requiere_archivo" id="requiere_archivo">
                                <label class="form-check-label" for="requiere_archivo">Sí (firma requerida)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cláusula</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales Editar (uno por cada cláusula) -->
<?php foreach ($clausulas as $clausula): ?>
<div class="modal fade" id="editarClausulaModal<?php echo $clausula['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Cláusula</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="id" value="<?php echo $clausula['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required maxlength="100" 
                                   value="<?php echo htmlspecialchars($clausula['nombre']); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" class="form-control" rows="3" required><?php echo htmlspecialchars($clausula['descripcion']); ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Parámetros <span class="text-danger">*</span></label>
                            <textarea name="parametros" class="form-control" rows="2" required><?php echo htmlspecialchars($clausula['parametros']); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="estado_activo" 
                                       id="estado_activo_<?php echo $clausula['id']; ?>" 
                                       <?php echo $clausula['estado_activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="estado_activo_<?php echo $clausula['id']; ?>">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Requiere Archivo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="requiere_archivo" 
                                       id="requiere_archivo_<?php echo $clausula['id']; ?>" 
                                       <?php echo $clausula['requiere_archivo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="requiere_archivo_<?php echo $clausula['id']; ?>">Sí (firma requerida)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Cláusula</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Formulario oculto para eliminar -->
<form id="eliminarForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="eliminar">
    <input type="hidden" name="id" id="eliminarId">
</form>

<script>
function eliminarClausula(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cláusula?')) {
        document.getElementById('eliminarId').value = id;
        document.getElementById('eliminarForm').submit();
    }
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>
