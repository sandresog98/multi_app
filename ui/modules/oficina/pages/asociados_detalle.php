<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/DetalleAsociado.php';
require_once '../models/Transaccion.php';
require_once '../models/Clausulas.php';
require_once '../../../models/Logger.php';
require_once '../../../utils/FileUploadManager.php';
require_once '../../../utils/dictionary.php';

$auth = new AuthController();
$auth->requireModule('oficina.asociados');
$currentUser = $auth->getCurrentUser();
$detalleModel = new DetalleAsociado();
$txModel = new Transaccion();
$clausulasModel = new Clausulas();
$logger = new Logger();

$cedula = $_GET['cedula'] ?? '';
if (!$cedula) { header('Location: asociados.php'); exit; }

$info = $detalleModel->getAsociadoInfo($cedula) ?: [];
$creditos = $detalleModel->getCreditos($cedula);
$creditosFinalizados = $detalleModel->getCreditosFinalizados($cedula);
$asignaciones = $detalleModel->getAsignaciones($cedula);
$productosActivos = $detalleModel->getActiveProducts();
$asignacionesClausulas = $clausulasModel->obtenerAsignacionesAsociado($cedula);
$clausulasActivas = $clausulasModel->obtenerClausulasActivas();

// Cálculos monetarios
$valorProductosMensual = 0.0;
foreach ($asignaciones as $ap) { if (!empty($ap['estado_activo'])) { $valorProductosMensual += (float)$ap['monto_pago']; } }
$valorPagoMinCreditos = 0.0;
foreach ($creditos as $c) {
  $cuotaBase = (float)($c['valor_cuota'] ?? ($c['cuota'] ?? 0));
  $saldoMora = (float)($c['saldo_mora'] ?? 0);
  $montoCobranza = (float)($c['monto_cobranza'] ?? 0);
  $valorPagoMinCreditos += ($saldoMora > 0 ? $saldoMora : $cuotaBase) + $montoCobranza;
}
$valorTotalMonetario = $valorProductosMensual + $valorPagoMinCreditos;

// Monetarios desde vista consolidada; fallback al cálculo anterior si viene vacío
$bp = $detalleModel->getMonetariosDesdeVista($cedula);
// Verificar si la vista consolidada tiene datos válidos (al menos aportes totales > 0)
if (empty($bp['aportes_totales']) || $bp['aportes_totales'] <= 0) {
  $bp = $detalleModel->getBalancePruebaMonetarios($cedula);
}

// Transacciones del asociado
$tpage = (int)($_GET['tpage'] ?? 1);
$txListado = $txModel->listTransacciones($cedula, $tpage, 10);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'asignar_producto') {
    $productoId = (int)($_POST['producto_id'] ?? 0);
    $diaPago = (int)($_POST['dia_pago'] ?? 1);
    $montoPago = (float)($_POST['monto_pago'] ?? 0);
    $res = $detalleModel->assignProduct($cedula, $productoId, $diaPago, $montoPago);
    if ($res['success']) { $message = $res['message']; $asignaciones = $detalleModel->getAsignaciones($cedula); $logger->logCrear('asociados','Asignación de producto',['cedula'=>$cedula,'producto_id'=>$productoId,'dia_pago'=>$diaPago,'monto_pago'=>$montoPago]); }
    else { $error = $res['message']; }
  } elseif ($action === 'actualizar_asignacion') {
    $id = (int)($_POST['id'] ?? 0);
    $diaPago = (int)($_POST['dia_pago'] ?? 1);
    $montoPago = (float)($_POST['monto_pago'] ?? 0);
    $estado = isset($_POST['estado']) ? (bool)$_POST['estado'] : true;
    $res = $detalleModel->updateAssignment($id, $diaPago, $montoPago, $estado);
    if ($res['success']) { $message = $res['message']; $asignaciones = $detalleModel->getAsignaciones($cedula); $logger->logEditar('asociados','Actualización de asignación',['id'=>$id],['id'=>$id,'dia_pago'=>$diaPago,'monto_pago'=>$montoPago,'estado'=>$estado]); }
    else { $error = $res['message']; }
  } elseif ($action === 'eliminar_asignacion') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $detalleModel->deleteAssignment($id);
    if ($res['success']) { $message = $res['message']; $asignaciones = $detalleModel->getAsignaciones($cedula); $logger->logEliminar('asociados','Eliminación de asignación',['id'=>$id]); }
    else { $error = $res['message']; }
  } elseif ($action === 'asignar_clausula') {
    $clausulaId = (int)($_POST['clausula_id'] ?? 0);
    $montoMensual = (int)($_POST['monto_mensual'] ?? 0);
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $mesesVigencia = (int)($_POST['meses_vigencia'] ?? 0);
    $parametros = $_POST['parametros'] ?? '';
    
    // Manejar archivo si es necesario
    $archivoRuta = null;
    if (!empty($_FILES['archivo']['name'])) {
      $resultadoArchivo = FileUploadManager::saveUploadedFile(
        $_FILES['archivo'],
        'clausulas',
        $currentUser['id'] ?? 1,
        'clausula_asignacion'
      );
      
      if ($resultadoArchivo['success']) {
        $archivoRuta = $resultadoArchivo['file_path'];
      } else {
        $error = 'Error al subir el archivo: ' . $resultadoArchivo['message'];
        // Continuar sin archivo o mostrar error
      }
    }
    
    $datos = [
      'asociado_cedula' => $cedula,
      'clausula_id' => $clausulaId,
      'monto_mensual' => $montoMensual,
      'fecha_inicio' => $fechaInicio,
      'meses_vigencia' => $mesesVigencia,
      'parametros' => $parametros,
      'archivo_ruta' => $archivoRuta,
      'creado_por' => $currentUser['id']
    ];
    
    $res = $clausulasModel->asignarClausulaAsociado($datos);
    if ($res['success']) { 
      $message = $res['message']; 
      $asignacionesClausulas = $clausulasModel->obtenerAsignacionesAsociado($cedula); 
      $logger->logCrear('asociados','Asignación de cláusula',['cedula'=>$cedula,'clausula_id'=>$clausulaId]); 
    }
    else { $error = $res['message']; }
  } elseif ($action === 'eliminar_asignacion_clausula') {
    $id = (int)($_POST['id'] ?? 0);
    $res = $clausulasModel->eliminarAsignacionClausula($id);
    if ($res['success']) { 
      $message = $res['message']; 
      $asignacionesClausulas = $clausulasModel->obtenerAsignacionesAsociado($cedula); 
      $logger->logEliminar('asociados','Eliminación de asignación de cláusula',['id'=>$id]); 
    }
    else { $error = $res['message']; }
  }
}

$pageTitle = 'Detalle de Asociado';
$currentPage = 'asociados';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-id-card me-2"></i>Detalle de Asociado</h1>
        <a class="btn btn-sm btn-secondary" href="asociados.php"><i class="fas fa-arrow-left me-1"></i>Volver</a>
      </div>

      <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="card"><div class="card-header"><strong>Información del asociado</strong></div><div class="card-body">
            <div><strong><?php echo dict_label('sifone_asociados','nombre','Nombre'); ?>:</strong> <?php echo htmlspecialchars($info['nombre'] ?? ''); ?></div>
            <div><strong><?php echo dict_label('sifone_asociados','cedula','Cédula'); ?>:</strong> <?php echo htmlspecialchars($info['cedula'] ?? $cedula); ?></div>
            <div><strong><?php echo dict_label('sifone_asociados','celula','Teléfono'); ?>:</strong> <?php echo htmlspecialchars($info['celula'] ?? ''); ?></div>
            <div><strong><?php echo dict_label('sifone_asociados','mail','Email'); ?>:</strong> <?php echo htmlspecialchars($info['mail'] ?? ''); ?></div>
            <div><strong><?php echo dict_label('sifone_asociados','ciudad','Ciudad'); ?>:</strong> <?php echo htmlspecialchars($info['ciudad'] ?? ''); ?></div>
            <div><strong><?php echo dict_label('sifone_asociados','direcc','Dirección'); ?>:</strong> <?php echo htmlspecialchars($info['direcc'] ?? ''); ?></div>
            <div><strong>Fecha de nacimiento:</strong> <?php echo !empty($info['fecnac']) ? date('d/m/Y', strtotime($info['fecnac'])) : '-'; ?>
              <?php if (!empty($info['fecnac'])) { $edad = (int)floor((time() - strtotime($info['fecnac'])) / (365.25*24*3600)); echo ' <span class="text-muted">('.$edad.' años)</span>'; } ?></div>
            <div><strong>Fecha de afiliación:</strong> <?php echo !empty($info['fechai']) ? date('d/m/Y', strtotime($info['fechai'])) : '-'; ?></div>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card"><div class="card-header"><strong>Información monetaria</strong></div><div class="card-body">
            <div>
              <strong>Aportes Totales:</strong>
              <?php echo '$' . number_format((float)($bp['aportes_totales'] ?? 0), 0); ?>
              <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['aportes_incentivos'] ?? 0), 0); ?>)</small>
            </div>
            <div><strong>Revalorizaciones de aportes:</strong> <?php echo '$' . number_format((float)($bp['aportes_revalorizaciones'] ?? 0), 0); ?></div>
            <div><strong>Plan Futuro:</strong> <?php echo '$' . number_format((float)($bp['plan_futuro'] ?? 0), 0); ?></div>
            <div>
              <strong>Bolsillos:</strong>
              <?php echo '$' . number_format((float)($bp['bolsillos'] ?? 0), 0); ?>
              <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['bolsillos_incentivos'] ?? 0), 0); ?>)</small>
            </div>
            <div><strong>Comisiones:</strong> <?php echo '$' . number_format((float)($bp['comisiones'] ?? 0), 0); ?></div>
            <div>
              <strong>Total Saldos a favor:</strong>
              <?php echo '$' . number_format((float)($bp['total_saldos_favor'] ?? 0), 0); ?>
              <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['total_incentivos'] ?? 0), 0); ?>)</small>
            </div>
          </div></div>
          <div class="card mt-2"><div class="card-header"><strong>Valor de pagos</strong></div><div class="card-body">
            <div><strong>Valor mensual de productos:</strong> <?php echo '$' . number_format($valorProductosMensual, 0); ?></div>
            <div><strong>Valor pago mínimo créditos:</strong> <?php echo '$' . number_format($valorPagoMinCreditos, 0); ?></div>
            <div><strong>Valor total:</strong> <?php echo '$' . number_format($valorTotalMonetario, 0); ?></div>
          </div></div>
        </div>
      </div>

      <?php if (!empty($creditos)): ?>
      <div class="row g-3 mt-1">
        <div class="col-12">
          <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><strong>Información credito</strong>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detalleCreditosModal">
              <i class="fas fa-eye me-1"></i>Ver detalle
            </button>
          </div><div class="card-body">
            <div class="table-responsive small">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                  <th><?php echo dict_label('sifone_cartera_aseguradora','numero','Crédito'); ?></th>
                  <th><?php echo dict_label('sifone_cartera_aseguradora','tipopr','Línea Crédito'); ?></th>
                  <th class="text-center text-nowrap">Inicio</th>
                  <th class="text-center text-nowrap">Final</th>
                  <th class="text-end">V.Inicial</th>
                  <th class="text-center">Cuotas</th>
                  <th class="text-end">Mora</th>
                  <th class="text-center">Días Mora</th>
                  <th class="text-end">Cobranza</th>
                  <th class="text-end"><strong>Pago Mínimo</strong></th>
                  <th class="text-end">Saldo Capital</th>
                  <th class="text-end">Saldo Total</th>
                  <th class="text-center">Codeudor</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($creditos as $c): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($c['numero_credito']); ?></td>
                    <td><?php echo htmlspecialchars($c['tipo_prestamo']); ?></td>
                    <td class="text-center text-nowrap"><?php echo !empty($c['fecha_inicio']) ? date('d/m/Y', strtotime($c['fecha_inicio'])) : '-'; ?></td>
                    <td class="text-center text-nowrap"><?php echo !empty($c['fecha_vencimiento']) ? date('d/m/Y', strtotime($c['fecha_vencimiento'])) : '-'; ?></td>
                    <td class="text-end"><?php echo '$' . number_format((float)($c['desembolso_inicial'] ?? 0), 0); ?></td>
                    <td class="text-center">
                      <?php
                        $plazo = (int)$c['plazo'];
                        $pendientes = (int)($c['cuotas_pendientes'] ?? 0);
                        $actuales = $plazo - $pendientes;
                        echo "$actuales/$plazo";
                      ?>
                    </td>
                    <td class="text-end"><?php echo '$' . number_format((float)($c['saldo_mora'] ?? 0), 0); ?></td>
                    <td class="text-center"><?php echo (int)$c['dias_mora']; ?></td>
                    <td class="text-end"><?php echo '$' . number_format((float)($c['monto_cobranza'] ?? 0), 0); ?></td>
                    <td class="text-end"><strong>
                      <?php
                        $__cuotaBase = (float)($c['valor_cuota'] ?? ($c['cuota'] ?? 0));
                        $__saldoMora = (float)($c['saldo_mora'] ?? 0);
                        $__montoCob = (float)($c['monto_cobranza'] ?? 0);
                        $__pagoMin = ($__saldoMora > 0 ? $__saldoMora : $__cuotaBase) + $__montoCob;
                        echo '$' . number_format($__pagoMin, 0);
                      ?>
                    </strong></td>
                    <td class="text-end"><?php echo '$' . number_format((float)($c['saldo_capital'] ?? 0), 0); ?></td>
                    <td class="text-end">
                      <?php
                        $__saldoCapital = (float)($c['saldo_capital'] ?? 0);
                        $__seguroVida = (float)($c['seguro_vida'] ?? 0);
                        $__seguroDeudores = (float)($c['seguro_deudores'] ?? 0);
                        $__interes = (float)($c['interes'] ?? 0);
                        $__pagoTotal = $__saldoCapital + $__seguroVida + $__seguroDeudores + $__interes;
                        echo '$' . number_format($__pagoTotal, 0);
                      ?>
                    </td>
                    <td class="text-center">
                      <?php if (!empty($c['codeudor_nombre']) || !empty($c['codeudor_celular']) || !empty($c['codeudor_email']) || !empty($c['codeudor_direccion'])): ?>
                        <?php $modalId = 'codeudorModal_' . preg_replace('/[^A-Za-z0-9_\-]/','_', (string)$c['numero_credito']); ?>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>"><i class="fas fa-user-friends me-1"></i>Ver</button>
                        <?php ob_start(); ?>
                        <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
                          <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-friends me-2"></i>Codeudor — Crédito <?php echo htmlspecialchars($c['numero_credito']); ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                          <div class="modal-body">
                            <div class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($c['codeudor_nombre'] ?? ''); ?></div>
                            <div class="mb-1"><strong>Teléfono:</strong> <?php echo htmlspecialchars($c['codeudor_celular'] ?? ''); ?></div>
                            <div class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($c['codeudor_email'] ?? ''); ?></div>
                            <div class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($c['codeudor_direccion'] ?? ''); ?></div>
                          </div>
                          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
                        </div></div></div>
                        <?php $codeudorModals[] = ob_get_clean(); ?>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php echo isset($codeudorModals) ? implode('', $codeudorModals) : ''; ?>
            </div>
          </div></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Modal Detalle Créditos -->
      <div class="modal fade" id="detalleCreditosModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Detalle Créditos</h5>
              <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th><?php echo dict_label('sifone_cartera_aseguradora','numero','Crédito'); ?></th>
                      <th><?php echo dict_label('sifone_cartera_aseguradora','tipopr','Tipo Préstamo'); ?></th>
                      <th class="text-center text-nowrap">Fecha Inicio</th>
                      <th class="text-center text-nowrap">Fecha Vencimiento</th>
                      <th class="text-end">Valor Inicial</th>
                      <th class="text-center">Cuotas</th>
                      <th class="text-end">Valor Cuota</th>
                      <th class="text-center">Días Mora</th>
                      <th class="text-end">Saldo Mora</th>
                      <th class="text-end">Cobranza</th>
                      <th class="text-end">Saldo Capital</th>
                      <th class="text-end">Seguro Vida</th>
                      <th class="text-end">Seguro Deudores</th>
                      <th class="text-end">Interés</th>
                      <th class="text-end">Pago Total</th>
                      <th class="text-end">Pago</th>
                      <th class="text-center text-nowrap">Fecha Pago</th>
                      <th class="text-center">Codeudor</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($creditos as $c): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($c['numero_credito']); ?></td>
                      <td><?php echo htmlspecialchars($c['tipo_prestamo']); ?></td>
                      <td class="text-center text-nowrap"><?php echo !empty($c['fecha_inicio']) ? date('d/m/Y', strtotime($c['fecha_inicio'])) : '-'; ?></td>
                      <td class="text-center text-nowrap"><?php echo !empty($c['fecha_vencimiento']) ? date('d/m/Y', strtotime($c['fecha_vencimiento'])) : '-'; ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['desembolso_inicial'] ?? 0), 0); ?></td>
                      <td class="text-center">
                        <?php
                          $plazo = (int)$c['plazo'];
                          $pendientes = (int)($c['cuotas_pendientes'] ?? 0);
                          $actuales = $plazo - $pendientes;
                          echo "$actuales/$plazo";
                        ?>
                      </td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['valor_cuota'] ?? $c['cuota'] ?? 0), 0); ?></td>
                      <td class="text-center"><?php echo (int)$c['dias_mora']; ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['saldo_mora'] ?? 0), 0); ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['monto_cobranza'] ?? 0), 0); ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['saldo_capital'] ?? 0), 0); ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['seguro_vida'] ?? 0), 0); ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['seguro_deudores'] ?? 0), 0); ?></td>
                      <td class="text-end"><?php echo '$' . number_format((float)($c['interes'] ?? 0), 0); ?></td>
                      <td class="text-end">
                        <?php
                          $__saldoCapital = (float)($c['saldo_capital'] ?? 0);
                          $__seguroVida = (float)($c['seguro_vida'] ?? 0);
                          $__seguroDeudores = (float)($c['seguro_deudores'] ?? 0);
                          $__interes = (float)($c['interes'] ?? 0);
                          $__pagoTotal = $__saldoCapital + $__seguroVida + $__seguroDeudores + $__interes;
                          echo '$' . number_format($__pagoTotal, 0);
                        ?>
                      </td>
                      <td class="text-end">
                        <?php
                          $__cuotaBase = (float)($c['valor_cuota'] ?? ($c['cuota'] ?? 0));
                          $__saldoMora = (float)($c['saldo_mora'] ?? 0);
                          $__montoCob = (float)($c['monto_cobranza'] ?? 0);
                          $__pagoMin = ($__saldoMora > 0 ? $__saldoMora : $__cuotaBase) + $__montoCob;
                          echo '$' . number_format($__pagoMin, 0);
                        ?>
                      </td>
                      <td class="text-center text-nowrap"><?php echo !empty($c['fecha_pago']) ? date('d/m/Y', strtotime($c['fecha_pago'])) : '-'; ?></td>
                      <td class="text-center">
                        <?php if (!empty($c['codeudor_nombre']) || !empty($c['codeudor_celular']) || !empty($c['codeudor_email']) || !empty($c['codeudor_direccion'])): ?>
                          <span class="text-success"><i class="fas fa-check"></i> Sí</span>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Asignar Cláusula -->
      <div class="modal fade" id="asignarClausulaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Asignar Cláusula</h5>
              <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
              <div class="modal-body">
                <input type="hidden" name="action" value="asignar_clausula">
                
                <div class="row">
                  <div class="col-md-12">
                    <label class="form-label">Tipo de Cláusula <span class="text-danger">*</span></label>
                    <select name="clausula_id" class="form-select" required id="clausulaSelect" onchange="toggleArchivoField()">
                      <option value="">Seleccione una cláusula</option>
                      <?php if (!empty($clausulasActivas)): ?>
                        <?php foreach ($clausulasActivas as $clausula): ?>
                          <option value="<?php echo $clausula['id']; ?>" data-requiere-archivo="<?php echo $clausula['requiere_archivo'] ? 'true' : 'false'; ?>">
                            <?php echo htmlspecialchars($clausula['nombre']); ?>
                          </option>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <option value="" disabled>No hay cláusulas disponibles</option>
                      <?php endif; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Monto Mensual <span class="text-danger">*</span></label>
                    <input type="number" name="monto_mensual" class="form-control" required min="0">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Meses de Vigencia <span class="text-danger">*</span></label>
                    <input type="number" name="meses_vigencia" class="form-control" required min="1">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Parámetros <span class="text-danger">*</span></label>
                    <input type="text" name="parametros" class="form-control" placeholder="Parámetros adicionales" required>
                  </div>
                  <div class="col-md-12" id="archivoField" style="display: none;">
                    <label class="form-label">Archivo (Firma) <span class="text-danger">*</span></label>
                    <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">Formatos permitidos: PDF, JPG, PNG</small>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Asignar Cláusula</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Formulario oculto para eliminar asignación de cláusula -->
      <form id="eliminarAsignacionClausulaForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="eliminar_asignacion_clausula">
        <input type="hidden" name="id" id="eliminarAsignacionClausulaId">
      </form>

      <!-- Modal Movimientos Sifone -->
      <div class="modal fade" id="modalMovSifone" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-list me-2"></i>Transacciones Sifone</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <?php $movs = $detalleModel->getMovimientosTributarios($cedula); ?>
          <?php if (!empty($movs)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light"><tr>
                <th>Fecha</th>
                <th>Recibo Sifone</th>
                <th>Producto Id</th>
                <th>Cuenta</th>
                <th>Detalle</th>
                <th>Tipo</th>
                <th class="text-end">Valor</th>
              </tr></thead>
              <tbody>
                <?php foreach ($movs as $m): ?>
                <tr>
                  <td><small>
                    <?php
                      $f = !empty($m['fecha']) ? date('Y-m-d', strtotime($m['fecha'])) : '';
                      $h = !empty($m['hora']) ? substr((string)$m['hora'], 0, 8) : '';
                      echo trim(($f . ' ' . $h)) ?: '-';
                    ?>
                  </small></td>
                  <td><?php echo htmlspecialchars($m['numero'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($m['cuenta'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($m['cuenta'] ?? ''); ?></td>
                  <td class="text-truncate" style="max-width:240px"><?php echo htmlspecialchars($m['detall'] ?? ''); ?></td>
                  <td>
                    <?php
                      $deb = (float)($m['debito'] ?? 0);
                      $cre = (float)($m['credit'] ?? 0);
                      echo $deb != 0 ? 'Débito' : ($cre != 0 ? 'Crédito' : '');
                    ?>
                  </td>
                  <td class="text-end">
                    <?php $valor = (float)($m['debito'] ?? 0); if ($valor == 0) { $valor = (float)($m['credit'] ?? 0); } echo '$'.number_format($valor, 0); ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <div class="text-muted">No hay transacciones Sifone para este asociado.</div>
          <?php endif; ?>
        </div>
      </div></div></div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><strong>Información de productos</strong>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#asignarProductoModal"><i class="fas fa-plus me-1"></i>Asignar producto</button>
          </div>
          <div class="card-body">
            <div class="mb-2"><strong>Total activos:</strong> <?php echo '$' . number_format($valorProductosMensual, 0); ?></div>
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="table-light"><tr>
                  <th>ID</th><th>Producto</th><th>Día Pago</th><th>Monto Pago</th><th>Estado</th><th>Acciones</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($asignaciones as $ap): ?>
                  <tr>
                    <td><?php echo (int)$ap['id']; ?></td>
                    <td><?php echo htmlspecialchars($ap['producto_nombre']); ?></td>
                    <td><?php echo (int)$ap['dia_pago']; ?></td>
                    <td><?php echo '$' . number_format((float)$ap['monto_pago'], 0); ?></td>
                    <td><span class="badge <?php echo $ap['estado_activo'] ? 'bg-success':'bg-secondary'; ?>"><?php echo $ap['estado_activo'] ? 'Activo':'Inactivo'; ?></span></td>
                    <td>
                      <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarAsignacionModal<?php echo $ap['id']; ?>"><i class="fas fa-edit"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar asignación?');">
                          <input type="hidden" name="action" value="eliminar_asignacion">
                          <input type="hidden" name="id" value="<?php echo $ap['id']; ?>">
                          <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <!-- Modal Editar Asignación -->
                  <div class="modal fade" id="editarAsignacionModal<?php echo $ap['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Asignación</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="POST" onsubmit="return validarEdicionAsignacion(this)" data-min="<?php echo (float)$ap['valor_minimo']; ?>" data-max="<?php echo (float)$ap['valor_maximo']; ?>">
                    <div class="modal-body">
                      <input type="hidden" name="action" value="actualizar_asignacion">
                      <input type="hidden" name="id" value="<?php echo $ap['id']; ?>">
                      <div class="row g-2">
                        <div class="col-md-4"><label class="form-label">Día Pago</label><input type="number" min="1" max="31" name="dia_pago" class="form-control" value="<?php echo (int)$ap['dia_pago']; ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Monto Pago <small class="text-muted">(rango: $<?php echo number_format((float)$ap['valor_minimo'],0); ?> - $<?php echo number_format((float)$ap['valor_maximo'],0); ?>)</small></label><input type="number" min="0" step="0.01" name="monto_pago" class="form-control" value="<?php echo (float)$ap['monto_pago']; ?>" required></div>
                        <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="estado" id="estado_asig_<?php echo $ap['id']; ?>" <?php echo $ap['estado_activo']?'checked':''; ?>><label class="form-check-label" for="estado_asig_<?php echo $ap['id']; ?>">Activo</label></div></div>
                      </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
                    </form>
                  </div></div></div>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div></div>
        </div>
        <?php if (!empty($creditosFinalizados)): ?>
        <div class="col-md-6">
          <div class="card"><div class="card-header"><strong>Créditos finalizados</strong></div><div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead class="table-light"><tr>
                  <th>Crédito</th>
                  <th>Fecha Pago</th>
                  <th>Desembolso</th>
                  <th>Cuotas Iniciales</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($creditosFinalizados as $f): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($f['numero_credito']); ?></td>
                    <td><?php echo !empty($f['fecha_pago']) ? date('d/m/Y', strtotime($f['fecha_pago'])) : '-'; ?></td>
                    <td><?php echo '$' . number_format((float)($f['desembolso_inicial'] ?? 0), 0); ?></td>
                    <td><?php echo (int)($f['cuotas_iniciales'] ?? 0); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sección de Cláusulas -->
      <div class="row g-3 mt-1">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Cláusulas asignadas</strong>
              <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#asignarClausulaModal">
                <i class="fas fa-plus me-1"></i>Asignar cláusula
              </button>
            </div>
            <div class="card-body">
              <?php if (empty($asignacionesClausulas)): ?>
                <div class="text-center py-3">
                  <i class="fas fa-file-contract text-muted" style="font-size: 2rem;"></i>
                  <p class="text-muted mt-2 mb-0">No hay cláusulas asignadas</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>Cláusula</th>
                        <th>Monto Mensual</th>
                        <th>Fecha Inicio</th>
                        <th>Meses Vigencia</th>
                        <th>Parámetros</th>
                        <th>Archivo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($asignacionesClausulas as $ac): ?>
                      <tr>
                        <td>
                          <strong><?php echo htmlspecialchars($ac['clausula_nombre']); ?></strong>
                          <br><small class="text-muted"><?php echo htmlspecialchars($ac['clausula_descripcion']); ?></small>
                        </td>
                        <td class="text-end"><?php echo '$' . number_format((int)$ac['monto_mensual'], 0); ?></td>
                        <td class="text-center"><?php echo date('d/m/Y', strtotime($ac['fecha_inicio'])); ?></td>
                        <td class="text-center"><?php echo (int)$ac['meses_vigencia']; ?></td>
                        <td>
                          <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($ac['parametros']); ?>">
                            <?php echo htmlspecialchars($ac['parametros']); ?>
                          </div>
                        </td>
                        <td class="text-center">
                          <?php if (!empty($ac['archivo_ruta'])): ?>
                            <a href="<?php echo htmlspecialchars($ac['archivo_ruta']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                              <i class="fas fa-file me-1"></i>Ver
                            </a>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <?php if ($ac['estado_activo']): ?>
                            <span class="badge bg-success">Activo</span>
                          <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-outline-danger" onclick="eliminarAsignacionClausula(<?php echo $ac['id']; ?>)">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($txListado['items'])): ?>
      <div class="row g-3 mt-1" id="txlist">
        <div class="col-12">
          <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><strong>Transacciones creadas</strong>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalMovSifone"><i class="fas fa-list me-1"></i>Ver Transacciones Sifone</button>
          </div><div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle">
                <thead class="table-light"><tr><th>ID</th><th>Origen</th><th>PSE/CONF</th><th>Recibo Sifone</th><th>Valor pago</th><th>Total asignado</th><th>Items</th><th>Fecha</th><th></th></tr></thead>
                <tbody>
                <?php $txModals = []; foreach (($txListado['items'] ?? []) as $tx): ?>
                  <tr>
                    <td><?php echo (int)$tx['id']; ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($tx['origen_pago']); ?></span></td>
                    <td>
                      <?php echo htmlspecialchars($tx['pse_id'] ?: $tx['confiar_id']); ?>
                      <?php if (!empty($tx['ref_fecha'])): ?>
                        <small class="text-muted"> — <?php echo htmlspecialchars($tx['ref_fecha']); ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($tx['recibo_caja_sifone'] ?? ''); ?></td>
                    <td><?php echo '$'.number_format((float)$tx['valor_pago_total'],0); ?></td>
                    <td><?php echo '$'.number_format((float)$tx['total_asignado'],0); ?></td>
                    <td><?php echo (int)$tx['items']; ?></td>
                    <td><small><?php echo htmlspecialchars($tx['fecha_creacion']); ?></small></td>
                    <td class="text-end">
                      <a href="#modalTx<?php echo (int)$tx['id']; ?>" data-bs-toggle="modal" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                    </td>
                  </tr>
                  <?php $txd_inline = $txModel->getTransaccionDetalles((int)$tx['id']); ?>
                  <tr class="table-light">
                    <td colspan="9">
                      <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle small">
                          <thead class="table-light"><tr>
                            <th style="width:120px;">Tipo</th>
                            <th style="width:140px;">Referencia</th>
                            <th>Descripción</th>
                            <th class="text-end" style="width:140px;">Recomendado</th>
                            <th class="text-end" style="width:140px;">Asignado</th>
                          </tr></thead>
                          <tbody>
                            <?php foreach ($txd_inline as $d): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($d['tipo_rubro']); ?></td>
                                <td><?php echo htmlspecialchars($d['referencia_credito'] ?: $d['producto_id']); ?></td>
                                <td class="text-truncate" style="max-width:600px;">
                                  <?php echo htmlspecialchars($d['descripcion'] ?? ''); ?>
                                </td>
                                <td class="text-end"><?php echo '$'.number_format((float)$d['valor_recomendado'],0); ?></td>
                                <td class="text-end"><?php echo '$'.number_format((float)$d['valor_asignado'],0); ?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </td>
                  </tr>
                  <?php ob_start(); ?>
                  <div class="modal fade" id="modalTx<?php echo (int)$tx['id']; ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Detalle transacción #<?php echo (int)$tx['id']; ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                      <?php $txh = $txModel->getTransaccion((int)$tx['id']); $txd = $txModel->getTransaccionDetalles((int)$tx['id']); ?>
                      <div class="mb-2 small text-muted">Origen: <strong><?php echo htmlspecialchars($txh['origen_pago']); ?></strong> | Referencia: <strong><?php echo htmlspecialchars($txh['pse_id'] ?: $txh['confiar_id']); ?></strong> | Valor pago: <strong><?php echo '$'.number_format((float)$txh['valor_pago_total'],0); ?></strong></div>
                      <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                          <thead class="table-light"><tr><th>Tipo</th><th>Referencia</th><th>Descripción</th><th class="text-end">Recomendado</th><th class="text-end">Asignado</th></tr></thead>
                          <tbody>
                          <?php foreach ($txd as $d): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($d['tipo_rubro']); ?></td>
                              <td><?php echo htmlspecialchars($d['referencia_credito'] ?: $d['producto_id']); ?></td>
                              <td class="text-truncate" style="max-width:260px"><?php echo htmlspecialchars($d['descripcion'] ?? ''); ?></td>
                              <td class="text-end"><?php echo '$'.number_format((float)$d['valor_recomendado'],0); ?></td>
                              <td class="text-end"><?php echo '$'.number_format((float)$d['valor_asignado'],0); ?></td>
                            </tr>
                          <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div></div></div>
                  <?php $txModals[] = ob_get_clean(); ?>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php echo implode('', $txModals); ?>
            <?php if ((($txListado['pages'] ?? 1)) > 1): $tp=(int)$txListado['current_page']; $pgs=(int)$txListado['pages']; ?>
              <nav><ul class="pagination pagination-sm justify-content-center">
                <?php for ($i=1;$i<=$pgs;$i++): ?>
                  <li class="page-item <?php echo $i==$tp?'active':''; ?>"><a class="page-link" href="?cedula=<?php echo urlencode($cedula); ?>&tpage=<?php echo $i; ?>#txlist"><?php echo $i; ?></a></li>
                <?php endfor; ?>
              </ul></nav>
            <?php endif; ?>
          </div></div>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Modal Asignar Producto -->
<div class="modal fade" id="asignarProductoModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Asignar Producto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" onsubmit="return validarAsignacionMonto()"><div class="modal-body">
    <input type="hidden" name="action" value="asignar_producto">
    <div class="mb-2"><label class="form-label">Producto</label>
      <select class="form-select" name="producto_id" id="producto_id_select" required onchange="actualizarRango()">
        <option value="">Seleccione</option>
        <?php foreach ($productosActivos as $p): ?><option value="<?php echo $p['id']; ?>" data-min="<?php echo (float)$p['valor_minimo']; ?>" data-max="<?php echo (float)$p['valor_maximo']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Día de Pago</label><input type="number" min="1" max="31" name="dia_pago" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Monto de Pago <small class="text-muted" id="rango_monto_help"></small></label><input type="number" min="0" step="0.01" name="monto_pago" id="monto_pago_input" class="form-control" required></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Asignar</button></div>
  </form>
</div></div></div>

<script>
function actualizarRango() {
  const sel = document.getElementById('producto_id_select');
  const opt = sel.options[sel.selectedIndex];
  const min = opt ? Number(opt.getAttribute('data-min')) : null;
  const max = opt ? Number(opt.getAttribute('data-max')) : null;
  const help = document.getElementById('rango_monto_help');
  if (min!=null && max!=null) help.textContent = `(rango: $${min.toLocaleString()} - $${max.toLocaleString()})`;
  else help.textContent = '';
}
function validarAsignacionMonto() {
  const sel = document.getElementById('producto_id_select');
  const opt = sel.options[sel.selectedIndex];
  if (!opt) return false;
  const min = Number(opt.getAttribute('data-min'));
  const max = Number(opt.getAttribute('data-max'));
  const monto = Number(document.getElementById('monto_pago_input').value);
  if (isNaN(monto) || monto < min || monto > max) {
    alert(`El monto debe estar entre $${min.toLocaleString()} y $${max.toLocaleString()}`);
    return false; // no cerrar modal, no enviar
  }
  return true;
}
// Inicializa ayuda al abrir modal (por si queda seleccionado)
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('asignarProductoModal');
  if (modal) modal.addEventListener('shown.bs.modal', actualizarRango);
});

// Funciones para cláusulas
function toggleArchivoField() {
  const select = document.getElementById('clausulaSelect');
  const archivoField = document.getElementById('archivoField');
  const archivoInput = archivoField.querySelector('input[type="file"]');
  
  if (select.selectedIndex > 0) {
    const option = select.options[select.selectedIndex];
    const requiereArchivo = option.getAttribute('data-requiere-archivo') === 'true';
    
    if (requiereArchivo) {
      archivoField.style.display = 'block';
      archivoInput.required = true;
    } else {
      archivoField.style.display = 'none';
      archivoInput.required = false;
    }
  } else {
    archivoField.style.display = 'none';
    archivoInput.required = false;
  }
}

function eliminarAsignacionClausula(id) {
  if (confirm('¿Estás seguro de que deseas eliminar esta asignación de cláusula?')) {
    document.getElementById('eliminarAsignacionClausulaId').value = id;
    document.getElementById('eliminarAsignacionClausulaForm').submit();
  }
}

// Establecer fecha de hoy como fecha por defecto
document.addEventListener('DOMContentLoaded', () => {
  const fechaInicioInput = document.querySelector('input[name="fecha_inicio"]');
  if (fechaInicioInput) {
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicioInput.value = hoy;
  }
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>


