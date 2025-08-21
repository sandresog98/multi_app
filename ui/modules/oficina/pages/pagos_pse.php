<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/PagoPse.php';
require_once '../../../models/Logger.php';

$auth = new AuthController();
$auth->requireAnyRole(['admin','oficina']);
$currentUser = $auth->getCurrentUser();
$model = new PagoPse();
$logger = new Logger();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $pse_id = $_POST['pse_id'] ?? '';
  try {
    if ($action === 'asignar') {
      $confiar_id = $_POST['confiar_id'] ?? '';
      $before = $model->getAssignment($pse_id);
      $res = $model->assignConfiar($pse_id, $confiar_id);
      if ($res['success']) {
        $message = 'Asignación guardada';
        $logger->logEditar('pagos_pse','Asignar Confiar a PSE', $before, ['pse_id'=>$pse_id,'confiar_id'=>$confiar_id]);
      } else { $error = $res['message'] ?? 'No se pudo asignar'; }
    } elseif ($action === 'eliminar_asignacion') {
      $before = $model->getAssignment($pse_id);
      $res = $model->removeAssignment($pse_id);
      if ($res['success']) { $message = 'Asignación eliminada'; $logger->logEliminar('pagos_pse','Eliminar asignación', $before); }
      else { $error = 'No se pudo eliminar la asignación'; }
    }
  } catch (Exception $e) { $error = $e->getMessage(); }
}

$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$estado = trim($_GET['estado'] ?? 'Aprobada');
$asignacion = trim($_GET['asignacion'] ?? '');
$data = $model->getPseList($page, 20, $search, $estado, $asignacion);

$pageTitle = 'Pagos PSE - Oficina';
$currentPage = 'pagos_pse';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-money-check-alt me-2"></i>Pagos PSE</h1>
      </div>

      <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

      <form class="row g-2 mb-3" method="GET">
        <div class="col-md-3"><input class="form-control" name="search" placeholder="Buscar por PSE o referencia" value="<?php echo htmlspecialchars($search); ?>"></div>
        <div class="col-md-2">
          <select name="estado" class="form-select">
            <option value="">Todos</option>
            <option value="Aprobada" <?php echo $estado==='Aprobada'?'selected':''; ?>>Aprobada</option>
            <option value="Rechazada" <?php echo $estado==='Rechazada'?'selected':''; ?>>Rechazada</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="asignacion" class="form-select">
            <option value="">Asignados y no asignados</option>
            <option value="asignados" <?php echo $asignacion==='asignados'?'selected':''; ?>>Solo asignados</option>
            <option value="no_asignados" <?php echo $asignacion==='no_asignados'?'selected':''; ?>>Solo no asignados</option>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
      </form>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-dark"><tr>
              <th>PSE ID</th>
              <th>Fecha</th>
              <th>Valor</th>
              <th>Estado</th>
              <th>Confiar ID</th>
              <th>Tipo asignación</th>
              <th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($data['items'] as $row): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($row['pse_id']); ?></div>
                  <div class="small text-muted">CC: <?php echo htmlspecialchars($row['referencia_2']); ?> | <?php echo htmlspecialchars($row['referencia_3']); ?></div>
                </td>
                <td><?php echo htmlspecialchars(substr($row['fecha'],0,10)); ?></td>
                <td>
                  <div><?php echo '$' . number_format($row['valor'], 0); ?></div>
                  <div class="small text-muted"><?php echo htmlspecialchars($row['servicio_nombre']); ?></div>
                </td>
                <td>
                  <span class="badge <?php echo $row['estado']==='Aprobada'?'bg-success':'bg-secondary'; ?>"><?php echo htmlspecialchars($row['estado']); ?></span>
                  <div class="small text-muted"><?php echo htmlspecialchars($row['medio_de_pago']); ?></div>
                </td>
                <td>
                  <div><?php echo htmlspecialchars($row['confiar_id'] ?? ''); ?></div>
                  <?php if (!empty($row['confiar_id'])): ?>
                    <div class="small text-muted">
                      <?php echo '$' . number_format((float)($row['asignado_valor'] ?? 0), 0); ?> | <?php echo htmlspecialchars($row['asignado_fecha'] ?? ''); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($row['tipo_asignacion'])): ?>
                    <span class="badge bg-<?php echo $row['tipo_asignacion']==='directa'?'success':($row['tipo_asignacion']==='grupal'?'warning text-dark':'info'); ?>">
                      <?php echo 'Asignación ' . htmlspecialchars($row['tipo_asignacion']); ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group">
                    <?php if (empty($row['confiar_id'])): ?>
                      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#asignarModal<?php echo $row['pse_id']; ?>" title="Asignar"><i class="fas fa-link"></i></button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#verAsignacionModal<?php echo $row['pse_id']; ?>" title="Ver detalle"><i class="fas fa-eye"></i></button>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="eliminar_asignacion">
                        <input type="hidden" name="pse_id" value="<?php echo htmlspecialchars($row['pse_id']); ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Eliminar asignación"><i class="fas fa-unlink"></i></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

              <!-- Modal asignar (solo si no tiene asignación) -->
              <?php if (empty($row['confiar_id'])): ?>
              <div class="modal fade" id="asignarModal<?php echo $row['pse_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-xl"><div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-link me-2"></i>Asignar Confiar</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <div class="row g-2">
                        <div class="col-md-6">
                          <div class="card h-100"><div class="card-body p-2">
                            <div class="fw-semibold mb-1"><i class="fas fa-money-check-alt me-1"></i>PSE</div>
                            <div class="small">ID: <strong><?php echo htmlspecialchars($row['pse_id']); ?></strong></div>
                            <div class="small">Fecha: <strong><?php echo htmlspecialchars(substr($row['fecha'],0,10)); ?></strong></div>
                            <div class="small">Valor: <strong><?php echo '$' . number_format($row['valor'], 0); ?></strong></div>
                            <div class="small">Servicio: <strong><?php echo htmlspecialchars($row['servicio_nombre']); ?></strong></div>
                            <div class="small">Banco: <strong><?php echo htmlspecialchars($row['banco_recaudador']); ?></strong></div>
                            <div class="small">Medio: <strong><?php echo htmlspecialchars($row['medio_de_pago']); ?></strong></div>
                            <div class="small">CC: <strong><?php echo htmlspecialchars($row['referencia_2']); ?></strong> / <strong><?php echo htmlspecialchars($row['referencia_3']); ?></strong></div>
                          </div></div>
                        </div>
                        <div class="col-md-6">
                          <div class="card h-100"><div class="card-body p-2">
                            <div class="fw-semibold mb-1"><i class="fas fa-university me-1"></i>Confiar sugeridos</div>
                            <div class="small text-muted">Busca por ID, descripción o documento. Por defecto se filtra por misma fecha y valor.</div>
                          </div></div>
                        </div>
                      </div>
                    </div>
                    <div class="row g-2 mb-3">
                      <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Fecha</label>
                        <input type="date" class="form-control" id="fecha_<?php echo $row['pse_id']; ?>" value="<?php echo htmlspecialchars(substr($row['fecha'],0,10)); ?>">
                      </div>
                      <div class="col-md-8">
                        <label class="form-label small text-muted mb-1">Buscar</label>
                        <div class="input-group">
                          <input type="text" class="form-control" placeholder="Buscar confiar (id/descripcion/documento)" id="q_<?php echo $row['pse_id']; ?>">
                          <button class="btn btn-outline-secondary" type="button" onclick="buscarConfiar('<?php echo $row['pse_id']; ?>')"><i class="fas fa-search"></i></button>
                        </div>
                      </div>
                    </div>
                    <div id="result_<?php echo $row['pse_id']; ?>" class="table-responsive" style="max-height:300px; overflow:auto;"></div>
                  </div>
                </div></div>
              </div>
              <?php endif; ?>

              <!-- Modal ver asignación (si ya tiene) -->
              <?php if (!empty($row['confiar_id'])): ?>
              <div class="modal fade" id="verAsignacionModal<?php echo $row['pse_id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-xl"><div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Asignación actual</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-lg-6">
                        <div class="card h-100"><div class="card-body p-2">
                          <div class="fw-semibold mb-2"><i class="fas fa-money-check-alt me-1"></i>PSE</div>
                          <div class="small">ID: <strong><?php echo htmlspecialchars($row['pse_id']); ?></strong></div>
                          <div class="small">Fecha: <strong><?php echo htmlspecialchars(substr($row['fecha'],0,10)); ?></strong></div>
                          <div class="small">Valor: <strong><?php echo '$' . number_format($row['valor'], 0); ?></strong></div>
                          <div class="small">Servicio: <strong><?php echo htmlspecialchars($row['servicio_nombre']); ?></strong></div>
                          <div class="small">Banco recaudador: <strong><?php echo htmlspecialchars($row['banco_recaudador']); ?></strong></div>
                          <div class="small">Banco originador: <strong><?php echo htmlspecialchars($row['banco_originador']); ?></strong></div>
                          <div class="small">Medio: <strong><?php echo htmlspecialchars($row['medio_de_pago']); ?></strong></div>
                          <div class="small">Funcionalidad: <strong><?php echo htmlspecialchars($row['nombre_funcionalidad']); ?></strong></div>
                          <div class="small">CC: <strong><?php echo htmlspecialchars($row['referencia_2']); ?></strong> / <strong><?php echo htmlspecialchars($row['referencia_3']); ?></strong></div>
                        </div></div>
                      </div>
                      <div class="col-lg-6">
                        <div class="card h-100"><div class="card-body p-2">
                          <div class="fw-semibold mb-2"><i class="fas fa-university me-1"></i>Confiar</div>
                          <div class="small">Confiar ID: <strong><?php echo htmlspecialchars($row['confiar_id']); ?></strong></div>
                          <div class="small">Tipo: <strong><?php echo htmlspecialchars($row['asignado_tipo'] ?? ''); ?></strong></div>
                          <div class="small">Fecha: <strong><?php echo htmlspecialchars($row['asignado_fecha']); ?></strong></div>
                          <div class="small">Oficina: <strong><?php echo htmlspecialchars($row['asignado_oficina']); ?></strong></div>
                          <div class="small">Documento: <strong><?php echo htmlspecialchars($row['asignado_documento']); ?></strong></div>
                          <div class="small">Descripción: <strong><?php echo htmlspecialchars($row['asignado_descripcion']); ?></strong></div>
                          <div class="small">Valor: <strong><?php echo '$' . number_format((float)$row['asignado_valor'], 0); ?></strong></div>
                          <div class="small">Saldo: <strong><?php echo '$' . number_format((float)($row['asignado_saldo'] ?? 0), 0); ?></strong></div>
                        </div></div>
                      </div>
                    </div>
                  </div>
                </div></div>
              </div>
              <?php endif; ?>

            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div></div>

      <?php if (($data['pages'] ?? 1) > 1): $pages=$data['pages']; $cur=$data['current_page']; ?>
        <nav><ul class="pagination justify-content-center">
          <?php for ($i=1;$i<=$pages;$i++): ?><li class="page-item <?php echo $i==$cur?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&asignacion=<?php echo urlencode($asignacion); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        </ul></nav>
      <?php endif; ?>

    </main>
  </div>
</div>

<script>
async function buscarConfiar(pseId){
  const q = document.getElementById('q_'+pseId).value;
  const fecha = document.getElementById('fecha_'+pseId)?.value || '';
  const container = document.getElementById('result_'+pseId);
  container.innerHTML = '<div class="text-muted">Buscando...</div>';
  try {
    const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/pse_buscar_confiar.php?pse_id='+encodeURIComponent(pseId)+'&q='+encodeURIComponent(q)+'&fecha='+encodeURIComponent(fecha), { headers: { 'Accept':'application/json' } });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Respuesta no válida del servidor'); }
    if (!data || !data.success){ container.innerHTML = '<div class="text-danger">'+((data&&data.message)||'Error de servidor')+'</div>'; return; }
    const rows = data.items||[];
    if (rows.length===0){ container.innerHTML = '<div class="text-muted">Sin resultados</div>'; return; }
    let html = '<table class="table table-sm table-hover"><thead><tr><th>Confiar ID</th><th>Fecha</th><th>Descripción</th><th>Documento</th><th>Tipo</th><th>Valor</th><th>Asignado</th><th>Restante</th><th>Estado</th><th></th></tr></thead><tbody>';
    for (const r of rows){
      html += `<tr>
        <td>${r.confiar_id}</td>
        <td>${r.fecha}</td>
        <td>${r.descripcion||''}</td>
        <td>${r.documento||''}</td>
        <td>${r.tipo_transaccion||''}</td>
        <td>$${Number(r.valor_consignacion||0).toLocaleString()}</td>
        <td>$${Number(r.asignado_total||0).toLocaleString()}</td>
        <td>$${Number(r.capacidad_restante||0).toLocaleString()}</td>
        <td>
          ${Number(r.capacidad_restante||0) <= 0 ? '<span class="badge bg-success">Completado</span>' : (Number(r.asignado_total||0) > 0 ? '<span class="badge bg-warning text-dark">Parcial</span>' : '<span class="badge bg-secondary">Disponible</span>')}
        </td>
        <td>
          <form method="POST">
            <input type="hidden" name="action" value="asignar">
            <input type="hidden" name="pse_id" value="${pseId}">
            <input type="hidden" name="confiar_id" value="${r.confiar_id}">
            <button class="btn btn-sm btn-primary" ${Number(r.capacidad_restante||0) <= 0 ? 'disabled' : ''} title="Asignar"><i class="fas fa-check"></i></button>
          </form>
        </td>
      </tr>`;
    }
    html += '</tbody></table>';
    container.innerHTML = html;
  } catch (e){ container.innerHTML = '<div class="text-danger">'+e.message+'</div>'; }
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>
