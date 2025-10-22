<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/CreditosDocs.php';
require_once '../../../models/Logger.php';

$auth = new AuthController();
$auth->requireModule('creditos_docs.crear');
$currentUser = $auth->getCurrentUser();
$creditosModel = new CreditosDocs();
$logger = new Logger();

$message = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear_solicitud') {
        $datos = [
            'tipo_solicitante' => $_POST['tipo_solicitante'] ?? '',
            'nombre_solicitante' => $_POST['nombre_solicitante'] ?? '',
            'numero_identificacion' => $_POST['numero_identificacion'] ?? '',
            'numero_telefono' => $_POST['numero_telefono'] ?? '',
            'correo_electronico' => $_POST['correo_electronico'] ?? '',
            'monto_deseado' => (int)($_POST['monto_deseado'] ?? 0),
            'numero_cuotas_deseadas' => (int)($_POST['numero_cuotas_deseadas'] ?? 0),
            'desea_codeudor' => isset($_POST['desea_codeudor']),
            'creado_por' => $currentUser['id']
        ];

        $resultado = $creditosModel->crearSolicitud($datos);
        
        if ($resultado['success']) {
            $message = $resultado['message'];
            // Redirigir a la página de gestión de la solicitud
            header("Location: gestionar_solicitud.php?id=" . $resultado['solicitud_id']);
            exit;
        } else {
            $error = $resultado['message'];
        }
    }
}

$pageTitle = 'Crear Solicitud de Crédito';
$currentPage = 'creditos_docs_crear';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../../views/layouts/sidebar.php'; ?>
        <main class="col-12 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-file-plus me-2"></i>Crear Solicitud de Crédito</h1>
                <a class="btn btn-secondary" href="listar_solicitudes.php">
                    <i class="fas fa-arrow-left me-1"></i>Volver a Lista
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

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Datos del Solicitante</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="formCrearSolicitud">
                                <input type="hidden" name="action" value="crear_solicitud">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo de Solicitante <span class="text-danger">*</span></label>
                                        <select name="tipo_solicitante" class="form-select" required id="tipoSolicitante" onchange="actualizarCamposRequeridos()">
                                            <option value="">Seleccione un tipo</option>
                                            <option value="estudiante">Estudiante</option>
                                            <option value="empleado_descuento_nomina">Empleado Descuento por Nómina</option>
                                            <option value="empleado_sin_descuento">Empleado Sin Descuento</option>
                                            <option value="independiente">Independiente</option>
                                            <option value="pensionado_descuento_libranza">Pensionado Descuento Libranza</option>
                                            <option value="pensionado_sin_descuento_libranza">Pensionado Sin Descuento Libranza</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre del Solicitante <span class="text-danger">*</span></label>
                                        <input type="text" name="nombre_solicitante" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Número de Identificación <span class="text-danger">*</span></label>
                                        <input type="text" name="numero_identificacion" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Número de Teléfono <span class="text-danger">*</span></label>
                                        <input type="tel" name="numero_telefono" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" name="correo_electronico" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Monto Deseado <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="monto_deseado" class="form-control" required min="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Número de Cuotas Deseadas <span class="text-danger">*</span></label>
                                        <input type="number" name="numero_cuotas_deseadas" class="form-control" required min="1" max="60">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Desea Incluir Codeudor <span class="text-danger">*</span></label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="desea_codeudor" value="1" id="codeudor_si">
                                                <label class="form-check-label" for="codeudor_si">Sí</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="desea_codeudor" value="0" id="codeudor_no" checked>
                                                <label class="form-check-label" for="codeudor_no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Crear Solicitud
                                        </button>
                                        <a href="listar_solicitudes.php" class="btn btn-secondary ms-2">
                                            <i class="fas fa-times me-1"></i>Cancelar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Información del Proceso</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Proceso de Solicitud</h6>
                                <p class="mb-2">El proceso de solicitud de crédito consta de 4 etapas:</p>
                                <ol class="mb-0 small">
                                    <li><strong>Creación:</strong> Datos básicos y documentos iniciales</li>
                                    <li><strong>Revisión:</strong> Documentos de análisis crediticio</li>
                                    <li><strong>Estudio:</strong> Evaluación y documentos de codeudor</li>
                                    <li><strong>Final:</strong> Documentos de desembolso</li>
                                </ol>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Documentos Requeridos</h6>
                                <p class="mb-0 small">Los documentos requeridos varían según el tipo de solicitante. Una vez creada la solicitud, podrá ver la lista completa de documentos necesarios.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function actualizarCamposRequeridos() {
    const tipoSolicitante = document.getElementById('tipoSolicitante').value;
    
    // Aquí se puede agregar lógica para mostrar/ocultar campos específicos
    // según el tipo de solicitante seleccionado
    console.log('Tipo de solicitante seleccionado:', tipoSolicitante);
}

// Validación del formulario
document.getElementById('formCrearSolicitud').addEventListener('submit', function(e) {
    const monto = parseInt(document.querySelector('input[name="monto_deseado"]').value);
    const cuotas = parseInt(document.querySelector('input[name="numero_cuotas_deseadas"]').value);
    
    if (monto < 100000) {
        e.preventDefault();
        alert('El monto mínimo es $100,000');
        return false;
    }
    
    if (cuotas < 1 || cuotas > 60) {
        e.preventDefault();
        alert('El número de cuotas debe estar entre 1 y 60');
        return false;
    }
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>
