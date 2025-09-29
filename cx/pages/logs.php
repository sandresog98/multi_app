<?php
require_once '../controllers/AuthController.php';
require_once '../models/Logger.php';

$auth = new CxAuthController();
$auth->requireAuth();

$logger = new Logger();

// Filtros
$filtros = [
    'cedula' => $_GET['cedula'] ?? '',
    'modulo' => $_GET['modulo'] ?? '',
    'accion' => $_GET['accion'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'limite' => 20,
    'page' => max(1, (int)($_GET['page'] ?? 1))
];

$resultado = $logger->getLogs($filtros);
$logs = $resultado['items'];
$total = $resultado['total'];
$pages = $resultado['pages'];
$currentPage = $resultado['current_page'];

$pageTitle = 'Logs de Actividad';
$heroTitle = 'Logs de Actividad';
$heroSubtitle = 'Registro de actividades de asociados.';
include __DIR__ . '/../views/layouts/header.php';
?>

<main class="container py-3">
    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cédula</label>
                    <input type="text" name="cedula" class="form-control" value="<?php echo htmlspecialchars($filtros['cedula']); ?>" placeholder="Buscar por cédula">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Módulo</label>
                    <select name="modulo" class="form-select">
                        <option value="">Todos</option>
                        <option value="cx.auth" <?php echo $filtros['modulo'] === 'cx.auth' ? 'selected' : ''; ?>>Autenticación</option>
                        <option value="cx.password" <?php echo $filtros['modulo'] === 'cx.password' ? 'selected' : ''; ?>>Contraseñas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Acción</label>
                    <select name="accion" class="form-select">
                        <option value="">Todas</option>
                        <option value="login" <?php echo $filtros['accion'] === 'login' ? 'selected' : ''; ?>>Login</option>
                        <option value="crear" <?php echo $filtros['accion'] === 'crear' ? 'selected' : ''; ?>>Crear</option>
                        <option value="editar" <?php echo $filtros['accion'] === 'editar' ? 'selected' : ''; ?>>Editar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($filtros['fecha_desde']); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-search me-1"></i> Filtrar
                    </button>
                    <a href="logs.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-times me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Registros (<?php echo $total; ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-4">
                    <i class="fa-solid fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron registros</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Cédula</th>
                                <th>Asociado</th>
                                <th>Acción</th>
                                <th>Módulo</th>
                                <th>Detalle</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['id_usuario'] ?? ''); ?></code>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($log['nombre_asociado'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $log['accion'] === 'login' ? 'success' : 
                                                ($log['accion'] === 'crear' ? 'primary' : 
                                                ($log['accion'] === 'editar' ? 'warning' : 'danger')); 
                                        ?>">
                                            <?php echo htmlspecialchars($log['accion']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['modulo']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($log['detalle'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Paginación de logs">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
