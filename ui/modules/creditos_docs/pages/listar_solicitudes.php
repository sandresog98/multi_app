<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/CreditosDocs.php';
require_once '../../../models/Logger.php';

$auth = new AuthController();
$auth->requireModule('creditos_docs.listar');
$currentUser = $auth->getCurrentUser();
$creditosModel = new CreditosDocs();
$logger = new Logger();

$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
    
    if ($action === 'rechazar' && $solicitudId > 0) {
        $comentarios = $_POST['comentarios_rechazo'] ?? '';
        if (empty($comentarios)) {
            $error = 'Los comentarios de rechazo son obligatorios';
        } else {
            $resultado = $creditosModel->rechazarSolicitud($solicitudId, $comentarios);
            if ($resultado['success']) {
                $message = $resultado['message'];
            } else {
                $error = $resultado['message'];
            }
        }
    }
}

// Obtener filtros
$filtros = [
    'estado' => $_GET['estado'] ?? '',
    'etapa' => $_GET['etapa'] ?? '',
    'tipo_solicitante' => $_GET['tipo_solicitante'] ?? '',
    'numero_solicitud' => $_GET['numero_solicitud'] ?? ''
];

// Obtener solicitudes
$solicitudes = $creditosModel->listarSolicitudes($filtros);

$pageTitle = 'Gestión de Solicitudes de Crédito';
$currentPage = 'creditos_docs_listar';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../../views/layouts/sidebar.php'; ?>
        <main class="col-12 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-list me-2"></i>Gestión de Solicitudes de Crédito</h1>
                <a class="btn btn-primary" href="crear_solicitud.php">
                    <i class="fas fa-plus me-1"></i>Nueva Solicitud
                </a>
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

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Filtros</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="solicitado" <?php echo $filtros['estado'] === 'solicitado' ? 'selected' : ''; ?>>Solicitado</option>
                                <option value="revisado" <?php echo $filtros['estado'] === 'revisado' ? 'selected' : ''; ?>>Revisado</option>
                                <option value="con_estudio" <?php echo $filtros['estado'] === 'con_estudio' ? 'selected' : ''; ?>>Con Estudio</option>
                                <option value="desembolsado" <?php echo $filtros['estado'] === 'desembolsado' ? 'selected' : ''; ?>>Desembolsado</option>
                                <option value="rechazado" <?php echo $filtros['estado'] === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Etapa</label>
                            <select name="etapa" class="form-select">
                                <option value="">Todas las etapas</option>
                                <option value="creacion" <?php echo $filtros['etapa'] === 'creacion' ? 'selected' : ''; ?>>Creación</option>
                                <option value="revision" <?php echo $filtros['etapa'] === 'revision' ? 'selected' : ''; ?>>Revisión</option>
                                <option value="estudio" <?php echo $filtros['etapa'] === 'estudio' ? 'selected' : ''; ?>>Estudio</option>
                                <option value="final" <?php echo $filtros['etapa'] === 'final' ? 'selected' : ''; ?>>Final</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Solicitante</label>
                            <select name="tipo_solicitante" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="estudiante" <?php echo $filtros['tipo_solicitante'] === 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                                <option value="empleado_descuento_nomina" <?php echo $filtros['tipo_solicitante'] === 'empleado_descuento_nomina' ? 'selected' : ''; ?>>Empleado Descuento Nómina</option>
                                <option value="empleado_sin_descuento" <?php echo $filtros['tipo_solicitante'] === 'empleado_sin_descuento' ? 'selected' : ''; ?>>Empleado Sin Descuento</option>
                                <option value="independiente" <?php echo $filtros['tipo_solicitante'] === 'independiente' ? 'selected' : ''; ?>>Independiente</option>
                                <option value="pensionado_descuento_libranza" <?php echo $filtros['tipo_solicitante'] === 'pensionado_descuento_libranza' ? 'selected' : ''; ?>>Pensionado Descuento Libranza</option>
                                <option value="pensionado_sin_descuento_libranza" <?php echo $filtros['tipo_solicitante'] === 'pensionado_sin_descuento_libranza' ? 'selected' : ''; ?>>Pensionado Sin Descuento Libranza</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número de Solicitud</label>
                            <input type="text" name="numero_solicitud" class="form-control" value="<?php echo htmlspecialchars($filtros['numero_solicitud']); ?>" placeholder="Buscar por número">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                            <a href="listar_solicitudes.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de solicitudes -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Solicitudes (<?php echo count($solicitudes); ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($solicitudes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No hay solicitudes</h5>
                            <p class="text-muted">No se encontraron solicitudes con los filtros aplicados.</p>
                            <a href="crear_solicitud.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Crear Primera Solicitud
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Número</th>
                                        <th>Solicitante</th>
                                        <th>Tipo</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Etapa</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $solicitud): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($solicitud['numero_solicitud']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($solicitud['nombre_solicitante']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($solicitud['numero_identificacion']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst(str_replace('_', ' ', $solicitud['tipo_solicitante'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>$<?php echo number_format($solicitud['monto_deseado'], 0); ?></strong>
                                            <br><small class="text-muted"><?php echo $solicitud['numero_cuotas_deseadas']; ?> cuotas</small>
                                        </td>
                                        <td>
                                            <?php
                                            $estadoClass = [
                                                'solicitado' => 'bg-warning',
                                                'revisado' => 'bg-info',
                                                'con_estudio' => 'bg-primary',
                                                'desembolsado' => 'bg-success',
                                                'rechazado' => 'bg-danger'
                                            ];
                                            $estadoTexto = [
                                                'solicitado' => 'Solicitado',
                                                'revisado' => 'Revisado',
                                                'con_estudio' => 'Con Estudio',
                                                'desembolsado' => 'Desembolsado',
                                                'rechazado' => 'Rechazado'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $estadoClass[$solicitud['estado']] ?? 'bg-secondary'; ?>">
                                                <?php echo $estadoTexto[$solicitud['estado']] ?? ucfirst($solicitud['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo ucfirst($solicitud['etapa_actual']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_creacion'])); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="gestionar_solicitud.php?id=<?php echo $solicitud['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($solicitud['estado'] !== 'desembolsado' && $solicitud['estado'] !== 'rechazado'): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="rechazarSolicitud(<?php echo $solicitud['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
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

<!-- Modal Rechazar Solicitud -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Solicitud</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formRechazar">
                <div class="modal-body">
                    <input type="hidden" name="action" value="rechazar">
                    <input type="hidden" name="solicitud_id" id="rechazarSolicitudId">
                    
                    <div class="mb-3">
                        <label class="form-label">Comentarios de Rechazo <span class="text-danger">*</span></label>
                        <textarea name="comentarios_rechazo" class="form-control" rows="4" required placeholder="Explique el motivo del rechazo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar Solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rechazarSolicitud(solicitudId) {
    document.getElementById('rechazarSolicitudId').value = solicitudId;
    const modal = new bootstrap.Modal(document.getElementById('modalRechazar'));
    modal.show();
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>
