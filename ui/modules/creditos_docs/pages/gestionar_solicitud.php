<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/CreditosDocs.php';
require_once '../../../models/Logger.php';
require_once '../../../utils/FileUploadManager.php';

$auth = new AuthController();
$auth->requireModule('creditos_docs.gestionar');
$currentUser = $auth->getCurrentUser();
$creditosModel = new CreditosDocs();
$logger = new Logger();

$solicitudId = (int)($_GET['id'] ?? 0);
if ($solicitudId <= 0) {
    header("Location: listar_solicitudes.php");
    exit;
}

$solicitud = $creditosModel->obtenerSolicitud($solicitudId);
if (!$solicitud) {
    header("Location: listar_solicitudes.php");
    exit;
}

$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'subir_documento') {
        $etapa = $_POST['etapa'] ?? '';
        $tipoDocumento = $_POST['tipo_documento'] ?? '';
        
        if (!empty($_FILES['archivo']['name'])) {
            $resultado = $creditosModel->subirDocumento(
                $solicitudId,
                $etapa,
                $tipoDocumento,
                $_FILES['archivo'],
                $currentUser['id']
            );
            
            if ($resultado['success']) {
                $message = $resultado['message'];
            } else {
                $error = $resultado['message'];
            }
        } else {
            $error = 'Debe seleccionar un archivo';
        }
    } elseif ($action === 'eliminar_documento') {
        $documentoId = (int)($_POST['documento_id'] ?? 0);
        $resultado = $creditosModel->eliminarDocumento($documentoId);
        
        if ($resultado['success']) {
            $message = $resultado['message'];
        } else {
            $error = $resultado['message'];
        }
    } elseif ($action === 'avanzar_etapa') {
        $resultado = $creditosModel->avanzarEtapa($solicitudId);
        
        if ($resultado['success']) {
            $message = $resultado['message'];
            // Recargar datos de la solicitud
            $solicitud = $creditosModel->obtenerSolicitud($solicitudId);
        } else {
            $error = $resultado['message'];
            if (isset($resultado['errores'])) {
                $error .= '<br><ul><li>' . implode('</li><li>', $resultado['errores']) . '</li></ul>';
            }
        }
    } elseif ($action === 'actualizar_solicitud') {
        $datos = [
            'tipo_codeudor' => $_POST['tipo_codeudor'] ?? null,
            'numero_credito_sifone' => !empty($_POST['numero_credito_sifone']) ? (int)$_POST['numero_credito_sifone'] : null,
            'valor_real_desembolso' => !empty($_POST['valor_real_desembolso']) ? (float)$_POST['valor_real_desembolso'] : null,
            'fecha_desembolso' => $_POST['fecha_desembolso'] ?? null,
            'plazo_desembolso' => !empty($_POST['plazo_desembolso']) ? (int)$_POST['plazo_desembolso'] : null
        ];
        
        $resultado = $creditosModel->actualizarSolicitud($solicitudId, $datos);
        
        if ($resultado['success']) {
            $message = $resultado['message'];
            $solicitud = $creditosModel->obtenerSolicitud($solicitudId);
        } else {
            $error = $resultado['message'];
        }
    } elseif ($action === 'reemplazar_documento') {
        $documentoId = (int)($_POST['documento_id'] ?? 0);
        
        if (!empty($_FILES['archivo']['name'])) {
            $resultado = $creditosModel->reemplazarDocumento($documentoId, $_FILES['archivo'], $currentUser['id']);
            
            if ($resultado['success']) {
                $message = $resultado['message'];
            } else {
                $error = $resultado['message'];
            }
        } else {
            $error = 'Debe seleccionar un archivo';
        }
    } elseif ($action === 'actualizar_campos_editables') {
        $datos = [
            'nombre_solicitante' => $_POST['nombre_solicitante'] ?? '',
            'numero_telefono' => $_POST['numero_telefono'] ?? '',
            'correo_electronico' => $_POST['correo_electronico'] ?? '',
            'monto_deseado' => (int)($_POST['monto_deseado'] ?? 0),
            'numero_cuotas_deseadas' => (int)($_POST['numero_cuotas_deseadas'] ?? 0),
            'desea_codeudor' => (int)($_POST['desea_codeudor'] ?? 0)
        ];
        
        $resultado = $creditosModel->actualizarCamposEditables($solicitudId, $datos);
        
        if ($resultado['success']) {
            $message = $resultado['message'];
            $solicitud = $creditosModel->obtenerSolicitud($solicitudId);
        } else {
            $error = $resultado['message'];
        }
    }
}

// Obtener documentos por etapa
$documentosCreacion = $creditosModel->obtenerDocumentosSolicitud($solicitudId, 'creacion');
$documentosRevision = $creditosModel->obtenerDocumentosSolicitud($solicitudId, 'revision');
$documentosEstudio = $creditosModel->obtenerDocumentosSolicitud($solicitudId, 'estudio');
$documentosFinal = $creditosModel->obtenerDocumentosSolicitud($solicitudId, 'final');

// Obtener configuración de documentos para cada etapa
$configCreacion = $creditosModel->obtenerConfiguracionDocumentos('creacion', $solicitud['tipo_solicitante']);
$configRevision = $creditosModel->obtenerConfiguracionDocumentos('revision', $solicitud['tipo_solicitante']);
$configEstudio = $creditosModel->obtenerConfiguracionDocumentos('estudio', $solicitud['tipo_solicitante']);
$configFinal = $creditosModel->obtenerConfiguracionDocumentos('final', $solicitud['tipo_solicitante']);

// Obtener documentos de codeudor disponibles si desea codeudor
$configCodeudor = [];
if ($solicitud['desea_codeudor']) {
    $configCodeudor = $creditosModel->obtenerDocumentosCodeudorDisponibles($solicitud['tipo_solicitante']);
}

// Obtener información del ciclo de vida
$cicloVida = $creditosModel->obtenerInfoCicloVida();

// Verificar si se puede avanzar de etapa
$validacionAvance = $creditosModel->validarAvanceEtapa($solicitudId);
$puedeAvanzar = $validacionAvance['success'];
$erroresValidacion = $validacionAvance['errores'] ?? [];

$pageTitle = 'Gestionar Solicitud - ' . $solicitud['numero_solicitud'];
$currentPage = 'creditos_docs_gestionar';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../../views/layouts/sidebar.php'; ?>
        <main class="col-12 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-file-alt me-2"></i>
                    Solicitud: <?php echo htmlspecialchars($solicitud['numero_solicitud']); ?>
                </h1>
                <div>
                    <a href="listar_solicitudes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver a Lista
                    </a>
                    <?php if ($solicitud['estado'] !== 'desembolsado' && $solicitud['estado'] !== 'rechazado'): ?>
                    <button class="btn <?php echo $puedeAvanzar ? 'btn-success' : 'btn-warning'; ?> ms-2" 
                            onclick="<?php echo $puedeAvanzar ? 'avanzarEtapa()' : 'mostrarErroresValidacion()'; ?>">
                        <i class="fas fa-forward me-1"></i>
                        <?php echo $puedeAvanzar ? 'Avanzar Etapa' : 'Ver Requisitos'; ?>
                    </button>
                    <?php endif; ?>
                </div>
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

            <!-- Cuadro del Ciclo de Vida (Desplegable) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" 
                             data-bs-toggle="collapse" 
                             data-bs-target="#cicloVidaCollapse" 
                             aria-expanded="true" 
                             aria-controls="cicloVidaCollapse"
                             style="cursor: pointer;">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Ciclo de Vida del Crédito
                            </h6>
                            <i class="fas fa-chevron-down" id="cicloVidaIcon"></i>
                        </div>
                        <div class="collapse show" id="cicloVidaCollapse">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row">
                                            <?php foreach ($cicloVida['etapas'] as $index => $etapa): ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="text-center">
                                                    <div class="mb-2">
                                                        <i class="<?php echo $etapa['icono']; ?> fa-2x text-<?php echo $etapa['color']; ?>"></i>
                                                    </div>
                                                    <h6 class="mb-1"><?php echo $etapa['nombre']; ?></h6>
                                                    <small class="text-muted"><?php echo $etapa['descripcion']; ?></small>
                                                    <?php if ($solicitud['etapa_actual'] === strtolower(str_replace(' ', '_', $etapa['nombre']))): ?>
                                                    <div class="mt-1">
                                                        <span class="badge bg-primary">Etapa Actual</span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-warning mb-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Reglas Importantes
                                        </h6>
                                        <ul class="list-unstyled small">
                                            <?php foreach ($cicloVida['reglas'] as $regla): ?>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <?php echo htmlspecialchars($regla); ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de la solicitud -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">Información del Solicitante</h6>
                            <?php if ($solicitud['etapa_actual'] === 'creacion'): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="editarDatosBasicos()">
                                <i class="fas fa-edit me-1"></i>Editar
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6"><strong>Nombre:</strong></div>
                                <div class="col-6"><?php echo htmlspecialchars($solicitud['nombre_solicitante']); ?></div>
                                
                                <div class="col-6"><strong>Identificación:</strong></div>
                                <div class="col-6"><?php echo htmlspecialchars($solicitud['numero_identificacion']); ?></div>
                                
                                <div class="col-6"><strong>Teléfono:</strong></div>
                                <div class="col-6"><?php echo htmlspecialchars($solicitud['numero_telefono']); ?></div>
                                
                                <div class="col-6"><strong>Email:</strong></div>
                                <div class="col-6"><?php echo htmlspecialchars($solicitud['correo_electronico']); ?></div>
                                
                                <div class="col-6"><strong>Tipo:</strong></div>
                                <div class="col-6">
                                    <span class="badge bg-info">
                                        <?php echo ucfirst(str_replace('_', ' ', $solicitud['tipo_solicitante'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Detalles del Crédito</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6"><strong>Monto Deseado:</strong></div>
                                <div class="col-6">$<?php echo number_format($solicitud['monto_deseado'], 0); ?></div>
                                
                                <div class="col-6"><strong>Cuotas:</strong></div>
                                <div class="col-6"><?php echo $solicitud['numero_cuotas_deseadas']; ?></div>
                                
                                <div class="col-6"><strong>Estado:</strong></div>
                                <div class="col-6">
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
                                </div>
                                
                                <div class="col-6"><strong>Etapa:</strong></div>
                                <div class="col-6">
                                    <span class="badge bg-light text-dark">
                                        <?php echo ucfirst($solicitud['etapa_actual']); ?>
                                    </span>
                                </div>
                                
                                <div class="col-6"><strong>Codeudor:</strong></div>
                                <div class="col-6">
                                    <?php echo $solicitud['desea_codeudor'] ? 'Sí' : 'No'; ?>
                                    <?php if ($solicitud['desea_codeudor'] && $solicitud['tipo_codeudor']): ?>
                                        <br><small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $solicitud['tipo_codeudor'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Etapas del proceso -->
            <div class="row">
                <!-- ETAPA CREACIÓN -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-plus-circle me-2"></i>Etapa: Creación
                            </h6>
                            <span class="badge <?php echo $solicitud['etapa_actual'] === 'creacion' ? 'bg-primary' : 'bg-light text-dark'; ?>">
                                <?php echo $solicitud['etapa_actual'] === 'creacion' ? 'Actual' : 'Completada'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php foreach ($configCreacion as $doc): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($doc['nombre_mostrar']); ?></strong>
                                        <?php if ($doc['es_obligatorio']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                        <?php if ($doc['es_trato_especial']): ?>
                                            <span class="badge bg-warning text-dark ms-1">B</span>
                                        <?php endif; ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($doc['descripcion']); ?></small>
                                    </div>
                                    <div>
                                        <?php
                                        $documentoSubido = null;
                                        foreach ($documentosCreacion as $docSubido) {
                                            if ($docSubido['tipo_documento'] === $doc['tipo_documento']) {
                                                $documentoSubido = $docSubido;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($documentoSubido): ?>
                                            <?php
                                            $archivoUrl = $documentoSubido['ruta_archivo'];
                                            $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|pdf)$/i', $archivoUrl);
                                            ?>
                                            <?php if ($isImage): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="Vista previa" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($archivoUrl); ?>', '_blank')" title="Clic para ver completo">
                                            </div>
                                            <?php endif; ?>
                                            <div class="btn-group">
                                                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($solicitud['etapa_actual'] === $etapa): ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="reemplazarDocumento(<?php echo $documentoSubido['id']; ?>, '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarDocumento(<?php echo $documentoSubido['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="subirDocumento('creacion', '<?php echo $doc['tipo_documento']; ?>', '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ETAPA REVISIÓN -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-search me-2"></i>Etapa: Revisión
                            </h6>
                            <span class="badge <?php echo $solicitud['etapa_actual'] === 'revision' ? 'bg-primary' : ($solicitud['etapa_actual'] === 'creacion' ? 'bg-secondary' : 'bg-light text-dark'); ?>">
                                <?php 
                                if ($solicitud['etapa_actual'] === 'revision') echo 'Actual';
                                elseif ($solicitud['etapa_actual'] === 'creacion') echo 'Pendiente';
                                else echo 'Completada';
                                ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($solicitud['etapa_actual'] === 'creacion'): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-lock"></i><br>
                                    Complete la etapa anterior para acceder
                                </div>
                            <?php else: ?>
                                <?php foreach ($configRevision as $doc): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($doc['nombre_mostrar']); ?></strong>
                                            <?php if ($doc['es_obligatorio']): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                            <?php if ($doc['es_trato_especial']): ?>
                                                <span class="badge bg-warning text-dark ms-1">B</span>
                                            <?php endif; ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($doc['descripcion']); ?></small>
                                        </div>
                                        <div>
                                            <?php
                                            $documentoSubido = null;
                                            foreach ($documentosRevision as $docSubido) {
                                                if ($docSubido['tipo_documento'] === $doc['tipo_documento']) {
                                                    $documentoSubido = $docSubido;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($documentoSubido): ?>
                                                <?php
                                                $archivoUrl = $documentoSubido['ruta_archivo'];
                                                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|pdf)$/i', $archivoUrl);
                                                ?>
                                                <?php if ($isImage): ?>
                                                <div class="mb-2">
                                                    <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="Vista previa" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($archivoUrl); ?>', '_blank')" title="Clic para ver completo">
                                                </div>
                                                <?php endif; ?>
                                                <div class="btn-group">
                                                    <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($solicitud['etapa_actual'] === $etapa): ?>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="reemplazarDocumento(<?php echo $documentoSubido['id']; ?>, '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarDocumento(<?php echo $documentoSubido['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="subirDocumento('revision', '<?php echo $doc['tipo_documento']; ?>', '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ETAPA ESTUDIO -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Etapa: Estudio
                            </h6>
                            <span class="badge <?php echo $solicitud['etapa_actual'] === 'estudio' ? 'bg-primary' : (in_array($solicitud['etapa_actual'], ['creacion', 'revision']) ? 'bg-secondary' : 'bg-light text-dark'); ?>">
                                <?php 
                                if ($solicitud['etapa_actual'] === 'estudio') echo 'Actual';
                                elseif (in_array($solicitud['etapa_actual'], ['creacion', 'revision'])) echo 'Pendiente';
                                else echo 'Completada';
                                ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (in_array($solicitud['etapa_actual'], ['creacion', 'revision'])): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-lock"></i><br>
                                    Complete las etapas anteriores para acceder
                                </div>
                            <?php else: ?>
                                <?php foreach ($configEstudio as $doc): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($doc['nombre_mostrar']); ?></strong>
                                            <?php if ($doc['es_obligatorio']): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($doc['descripcion']); ?></small>
                                        </div>
                                        <div>
                                            <?php
                                            $documentoSubido = null;
                                            foreach ($documentosEstudio as $docSubido) {
                                                if ($docSubido['tipo_documento'] === $doc['tipo_documento']) {
                                                    $documentoSubido = $docSubido;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($documentoSubido): ?>
                                                <?php
                                                $archivoUrl = $documentoSubido['ruta_archivo'];
                                                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|pdf)$/i', $archivoUrl);
                                                ?>
                                                <?php if ($isImage): ?>
                                                <div class="mb-2">
                                                    <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="Vista previa" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($archivoUrl); ?>', '_blank')" title="Clic para ver completo">
                                                </div>
                                                <?php endif; ?>
                                                <div class="btn-group">
                                                    <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($solicitud['etapa_actual'] === $etapa): ?>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="reemplazarDocumento(<?php echo $documentoSubido['id']; ?>, '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarDocumento(<?php echo $documentoSubido['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="subirDocumento('estudio', '<?php echo $doc['tipo_documento']; ?>', '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ETAPA FINAL -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-check-circle me-2"></i>Etapa: Final
                            </h6>
                            <span class="badge <?php echo $solicitud['etapa_actual'] === 'final' ? 'bg-primary' : (in_array($solicitud['etapa_actual'], ['creacion', 'revision', 'estudio']) ? 'bg-secondary' : 'bg-light text-dark'); ?>">
                                <?php 
                                if ($solicitud['etapa_actual'] === 'final') echo 'Actual';
                                elseif (in_array($solicitud['etapa_actual'], ['creacion', 'revision', 'estudio'])) echo 'Pendiente';
                                else echo 'Completada';
                                ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (in_array($solicitud['etapa_actual'], ['creacion', 'revision', 'estudio'])): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-lock"></i><br>
                                    Complete las etapas anteriores para acceder
                                </div>
                            <?php else: ?>
                                <?php foreach ($configFinal as $doc): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($doc['nombre_mostrar']); ?></strong>
                                            <?php if ($doc['es_obligatorio']): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($doc['descripcion']); ?></small>
                                        </div>
                                        <div>
                                            <?php
                                            $documentoSubido = null;
                                            foreach ($documentosFinal as $docSubido) {
                                                if ($docSubido['tipo_documento'] === $doc['tipo_documento']) {
                                                    $documentoSubido = $docSubido;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($documentoSubido): ?>
                                                <?php
                                                $archivoUrl = $documentoSubido['ruta_archivo'];
                                                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|pdf)$/i', $archivoUrl);
                                                ?>
                                                <?php if ($isImage): ?>
                                                <div class="mb-2">
                                                    <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="Vista previa" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($archivoUrl); ?>', '_blank')" title="Clic para ver completo">
                                                </div>
                                                <?php endif; ?>
                                                <div class="btn-group">
                                                    <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($solicitud['etapa_actual'] === $etapa): ?>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="reemplazarDocumento(<?php echo $documentoSubido['id']; ?>, '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarDocumento(<?php echo $documentoSubido['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="subirDocumento('final', '<?php echo $doc['tipo_documento']; ?>', '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Documentos de Codeudor (si aplica) - Movida junto a Revisión -->
            <?php if ($solicitud['desea_codeudor'] && !empty($configCodeudor)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user-friends me-2"></i>Documentos de Codeudor
                                <span class="badge bg-light text-dark ms-2">Disponible en cualquier etapa</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Información:</strong> Como marcó "Sí" en "Desea incluir codeudor", puede subir estos documentos en cualquier momento del proceso.
                            </div>
                            
                            <?php foreach ($configCodeudor as $doc): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($doc['nombre_mostrar']); ?></strong>
                                        <span class="text-danger">*</span>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($doc['descripcion']); ?></small>
                                    </div>
                                    <div>
                                        <?php
                                        $documentoSubido = null;
                                        foreach ($documentosEstudio as $docSubido) {
                                            if ($docSubido['tipo_documento'] === $doc['tipo_documento']) {
                                                $documentoSubido = $docSubido;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($documentoSubido): ?>
                                            <?php
                                            $archivoUrl = $documentoSubido['ruta_archivo'];
                                            $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|pdf)$/i', $archivoUrl);
                                            ?>
                                            <?php if ($isImage): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="Vista previa" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($archivoUrl); ?>', '_blank')" title="Clic para ver completo">
                                            </div>
                                            <?php endif; ?>
                                            <div class="btn-group">
                                                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-warning" onclick="reemplazarDocumento(<?php echo $documentoSubido['id']; ?>, '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarDocumento(<?php echo $documentoSubido['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="subirDocumentoCodeudor('<?php echo $doc['tipo_documento']; ?>', '<?php echo htmlspecialchars($doc['nombre_mostrar']); ?>')">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Información adicional para etapa final -->
            <?php if ($solicitud['etapa_actual'] === 'final'): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Información de Desembolso</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="actualizar_solicitud">
                                
                                <div class="col-md-3">
                                    <label class="form-label">Número de Crédito en Sifone</label>
                                    <input type="number" name="numero_credito_sifone" class="form-control" value="<?php echo $solicitud['numero_credito_sifone'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Valor Real del Desembolso</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="valor_real_desembolso" class="form-control" value="<?php echo $solicitud['valor_real_desembolso'] ?? ''; ?>" step="0.01">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Fecha del Desembolso</label>
                                    <input type="date" name="fecha_desembolso" class="form-control" value="<?php echo $solicitud['fecha_desembolso'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Plazo del Desembolso</label>
                                    <input type="number" name="plazo_desembolso" class="form-control" value="<?php echo $solicitud['plazo_desembolso'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Actualizar Información
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal Subir Documento -->
<div class="modal fade" id="modalSubirDocumento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Documento</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formSubirDocumento">
                <div class="modal-body">
                    <input type="hidden" name="action" value="subir_documento">
                    <input type="hidden" name="etapa" id="etapaDocumento">
                    <input type="hidden" name="tipo_documento" id="tipoDocumento">
                    
                    <div class="mb-3">
                        <label class="form-label">Documento</label>
                        <div id="nombreDocumento" class="fw-bold mb-2"></div>
                        <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Formatos permitidos: PDF, JPG, PNG</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminar documento -->
<form id="formEliminarDocumento" method="POST" style="display: none;">
    <input type="hidden" name="action" value="eliminar_documento">
    <input type="hidden" name="documento_id" id="eliminarDocumentoId">
</form>

<!-- Formulario oculto para avanzar etapa -->
<form id="formAvanzarEtapa" method="POST" style="display: none;">
    <input type="hidden" name="action" value="avanzar_etapa">
</form>

<!-- Modal Editar Datos Básicos -->
<div class="modal fade" id="modalEditarDatos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Datos Básicos</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEditarDatos">
                <div class="modal-body">
                    <input type="hidden" name="action" value="actualizar_campos_editables">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Solicitante <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_solicitante" class="form-control" value="<?php echo htmlspecialchars($solicitud['nombre_solicitante']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" name="numero_telefono" class="form-control" value="<?php echo htmlspecialchars($solicitud['numero_telefono']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="correo_electronico" class="form-control" value="<?php echo htmlspecialchars($solicitud['correo_electronico']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monto Deseado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="monto_deseado" class="form-control" value="<?php echo $solicitud['monto_deseado']; ?>" required min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Número de Cuotas Deseadas <span class="text-danger">*</span></label>
                            <input type="number" name="numero_cuotas_deseadas" class="form-control" value="<?php echo $solicitud['numero_cuotas_deseadas']; ?>" required min="1" max="60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Desea Incluir Codeudor</label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="desea_codeudor" value="1" id="codeudor_si_edit" <?php echo $solicitud['desea_codeudor'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="codeudor_si_edit">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="desea_codeudor" value="0" id="codeudor_no_edit" <?php echo !$solicitud['desea_codeudor'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="codeudor_no_edit">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reemplazar Documento -->
<div class="modal fade" id="modalReemplazarDocumento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reemplazar Documento</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formReemplazarDocumento">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reemplazar_documento">
                    <input type="hidden" name="documento_id" id="reemplazarDocumentoId">
                    
                    <div class="mb-3">
                        <label class="form-label">Documento</label>
                        <div id="nombreDocumentoReemplazar" class="fw-bold mb-2"></div>
                        <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Formatos permitidos: PDF, JPG, PNG</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Advertencia:</strong> Al reemplazar el documento, el archivo anterior será eliminado permanentemente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Reemplazar Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function subirDocumento(etapa, tipoDocumento, nombreDocumento) {
    document.getElementById('etapaDocumento').value = etapa;
    document.getElementById('tipoDocumento').value = tipoDocumento;
    document.getElementById('nombreDocumento').textContent = nombreDocumento;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSubirDocumento'));
    modal.show();
}

function eliminarDocumento(documentoId) {
    if (confirm('¿Está seguro de que desea eliminar este documento?')) {
        document.getElementById('eliminarDocumentoId').value = documentoId;
        document.getElementById('formEliminarDocumento').submit();
    }
}

function avanzarEtapa() {
    if (confirm('¿Está seguro de que desea avanzar a la siguiente etapa? Se validarán todos los documentos requeridos.')) {
        document.getElementById('formAvanzarEtapa').submit();
    }
}

function editarDatosBasicos() {
    const modal = new bootstrap.Modal(document.getElementById('modalEditarDatos'));
    modal.show();
}

function reemplazarDocumento(documentoId, nombreDocumento) {
    document.getElementById('reemplazarDocumentoId').value = documentoId;
    document.getElementById('nombreDocumentoReemplazar').textContent = nombreDocumento;
    
    const modal = new bootstrap.Modal(document.getElementById('modalReemplazarDocumento'));
    modal.show();
}

function subirDocumentoCodeudor(tipoDocumento, nombreDocumento) {
    document.getElementById('subirDocumentoTipo').value = tipoDocumento;
    document.getElementById('subirDocumentoEtapa').value = 'estudio'; // Forzar etapa de estudio
    document.getElementById('nombreDocumentoSubir').textContent = nombreDocumento;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSubirDocumento'));
    modal.show();
}

function mostrarErroresValidacion() {
    const errores = <?php echo json_encode($erroresValidacion); ?>;
    
    if (errores.length === 0) {
        alert('No hay errores de validación');
        return;
    }
    
    let mensaje = 'Para avanzar de etapa faltan los siguientes documentos:\n\n';
    errores.forEach((error, index) => {
        mensaje += `${index + 1}. ${error}\n`;
    });
    
    alert(mensaje);
}

function avanzarEtapa() {
    if (confirm('¿Estás seguro de que deseas avanzar a la siguiente etapa?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="avanzar_etapa">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Manejar animación del ícono del ciclo de vida
document.addEventListener('DOMContentLoaded', function() {
    const cicloVidaCollapse = document.getElementById('cicloVidaCollapse');
    const cicloVidaIcon = document.getElementById('cicloVidaIcon');
    
    if (cicloVidaCollapse && cicloVidaIcon) {
        cicloVidaCollapse.addEventListener('show.bs.collapse', function () {
            cicloVidaIcon.classList.remove('fa-chevron-down');
            cicloVidaIcon.classList.add('fa-chevron-up');
        });
        
        cicloVidaCollapse.addEventListener('hide.bs.collapse', function () {
            cicloVidaIcon.classList.remove('fa-chevron-up');
            cicloVidaIcon.classList.add('fa-chevron-down');
        });
    }
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>
