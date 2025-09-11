<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Transaccion.php';
require_once '../models/DetalleAsociado.php';
require_once '../../../models/Logger.php';

$auth = new AuthController();
$auth->requireModule('oficina.transacciones');
$currentUser = $auth->getCurrentUser();
$model = new Transaccion();
$logger = new Logger();

$message = '';
$error = '';

$cedula = trim($_GET['cedula'] ?? '');
$resumen = [];
$asociadoInfo = null;
$pagos = [];
$listado = [];
$tpage = (int)($_GET['tpage'] ?? 1);

if ($cedula !== '') {
  $resumen = $model->getResumenPorAsociado($cedula);
  $pagos = $model->getPagosDisponibles();
  $detModel = new DetalleAsociado();
  $asociadoInfo = $detModel->getAsociadoInfo($cedula);
}
// Siempre listar transacciones (filtradas por cédula si aplica)
$listado = $model->listTransacciones($cedula ?: null, $tpage, 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'crear') {
    try {
      $ced = trim($_POST['cedula'] ?? '');
      $origen = trim($_POST['origen'] ?? '');
      $pseId = $_POST['pse_id'] ?? null;
      $confiarId = $_POST['confiar_id'] ?? null;
      $valorPago = (float)($_POST['valor_pago'] ?? 0);
      $detalles = json_decode($_POST['detalles'] ?? '[]', true) ?: [];
      $reciboCaja = trim($_POST['recibo_caja_sifone'] ?? '');
      if ($reciboCaja === '') { throw new Exception('El campo Recibo de caja Sifone es obligatorio'); }
      $res = $model->crearTransaccion($ced, $origen, $pseId, $confiarId, $valorPago, $detalles, (int)($currentUser['id'] ?? null), $reciboCaja);
      if ($res['success']) { $message = 'Transacción creada'; $logger->logCrear('transacciones','Crear transacción', null, ['id'=>$res['id'],'cedula'=>$ced]); }
      else { $error = $res['message'] ?? 'No se pudo crear'; }
      // Recalcular vista si persiste en la misma página
      $cedula = $ced; $resumen = $model->getResumenPorAsociado($cedula); $pagos = $model->getPagosDisponibles();
    } catch (Exception $e) { $error = $e->getMessage(); }
  } elseif ($action === 'eliminar') {
    try {
      $id = (int)($_POST['id'] ?? 0);
      if ($id > 0 && $model->deleteTransaccion($id)) { $message = 'Transacción eliminada'; }
      else { $error = 'No se pudo eliminar'; }
      // refrescar listado
      $listado = $model->listTransacciones($cedula ?: null, $tpage, 10);
    } catch (Exception $e) { $error = $e->getMessage(); }
  }
}

$pageTitle = 'Transacciones - Oficina';
$currentPage = 'transacciones';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-exchange-alt me-2"></i>Transacciones</h1>
      </div>

      <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

      <form class="row g-2 mb-3" method="GET" autocomplete="off">
        <div class="col-md-6 position-relative">
          <label class="form-label">Buscar asociado (cédula o nombre)</label>
          <input class="form-control" id="buscarCedula" name="cedula" value="<?php echo htmlspecialchars($cedula); ?>" placeholder="Escribe al menos 2 caracteres">
          <div id="asoc_results_main" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
        </div>
        <div class="col-md-2 align-self-end"><button class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Buscar</button></div>
      </form>

      <?php if (!empty($cedula)): ?>
      <?php if (!empty($asociadoInfo)): ?>
      <div class="card mb-3">
        <div class="card-header"><strong>Información del asociado</strong></div>
        <div class="card-body">
          <div class="row g-3 small">
            <div class="col-md-4"><strong>Nombre:</strong> <?php echo htmlspecialchars($asociadoInfo['nombre'] ?? ''); ?></div>
            <div class="col-md-4"><strong>Cédula:</strong> <?php echo htmlspecialchars($asociadoInfo['cedula'] ?? ''); ?></div>
            <div class="col-md-4"><strong>Teléfono:</strong> <?php echo htmlspecialchars($asociadoInfo['celula'] ?? ''); ?></div>
            <div class="col-md-4"><strong>Email:</strong> <?php echo htmlspecialchars($asociadoInfo['mail'] ?? ''); ?></div>
            <div class="col-md-4"><strong>Ciudad:</strong> <?php echo htmlspecialchars($asociadoInfo['ciudad'] ?? ''); ?></div>
            <div class="col-md-4"><strong>Dirección:</strong> <?php echo htmlspecialchars($asociadoInfo['direcc'] ?? ''); ?></div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card mb-3"><div class="card-header"><strong>Rubros recomendados</strong></div><div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead class="table-light"><tr><th>Tipo</th><th>Referencia</th><th>Detalle</th><th>Valor recomendado</th><th>Valor a asignar</th></tr></thead>
                <tbody id="rubroBody">
                  <?php foreach (($resumen['detalles']['creditos'] ?? []) as $m): ?>
                    <tr data-tipo="credito" data-ref="<?php echo htmlspecialchars($m['numero']); ?>">
                      <td>Crédito</td>
                      <td><?php echo htmlspecialchars($m['numero']); ?></td>
                      <td><?php echo 'Cuota: $'.number_format((float)$m['cuota'], 0) . ($m['has_mora'] ? (' | Mora: '.(int)$m['diav'].' días ($'.number_format((float)$m['intmora'],0).')') : ''); ?></td>
                      <td class="text-end col-recomendado"><?php echo number_format((float)$m['recomendado'], 0); ?></td>
                      <td width="160"><input type="number" step="0.01" class="form-control form-control-sm rubro-asignar" value="0"></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php foreach (($resumen['detalles']['cobranza'] ?? []) as $c): ?>
                    <tr data-tipo="cobranza" data-ref="<?php echo htmlspecialchars($c['numero']); ?>">
                      <td>Cobranza</td>
                      <td><?php echo htmlspecialchars($c['numero']); ?></td>
                      <td><?php echo 'Días mora: '.(int)$c['diav']; ?></td>
                      <td class="text-end col-recomendado"><?php echo number_format((float)$c['valor'], 0); ?></td>
                      <td width="160"><input type="number" step="0.01" class="form-control form-control-sm rubro-asignar" value="0"></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php foreach (($resumen['detalles']['productos'] ?? []) as $p): ?>
                    <tr data-tipo="producto" data-prod="<?php echo (int)$p['producto_id']; ?>" data-desc="<?php echo htmlspecialchars($p['nombre']); ?>">
                      <td>Producto</td>
                      <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                      <td>-</td>
                      <td class="text-end col-recomendado"><?php echo number_format((float)$p['monto'], 0); ?></td>
                      <td width="160"><input type="number" step="0.01" class="form-control form-control-sm rubro-asignar" value="0"></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div></div>
        </div>
        <div class="col-lg-4">
          <div class="card mb-3"><div class="card-header"><strong>Seleccionar pago</strong></div><div class="card-body">
            <div class="mb-2"><label class="form-label">Origen</label>
              <select id="origen" class="form-select form-select-sm">
                <option value="pse">PSE</option>
                <option value="cash_qr">Cash/QR</option>
              </select>
            </div>
            <div class="mb-2" id="pagoPseBox">
              <div class="d-flex justify-content-between align-items-center">
                <label class="form-label mb-0">PSE relacionados</label>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalPse"><i class="fas fa-list"></i></button>
              </div>
              <select id="pseId" class="form-select form-select-sm" disabled title="Usa la lista para seleccionar">
                <option value="">-- seleccionar --</option>
                <?php foreach (($pagos['pse'] ?? []) as $r): ?>
                  <option value="<?php echo htmlspecialchars($r['pse_id']); ?>" data-valor="<?php echo (float)$r['valor']; ?>">PSE <?php echo htmlspecialchars($r['pse_id']); ?> — $<?php echo number_format((float)$r['valor'],0); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2 d-none" id="pagoCashBox">
              <div class="d-flex justify-content-between align-items-center">
                <label class="form-label mb-0">Cash/QR confirmados</label>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalCash"><i class="fas fa-list"></i></button>
              </div>
              <select id="confiarId" class="form-select form-select-sm" disabled title="Usa la lista para seleccionar">
                <option value="">-- seleccionar --</option>
                <?php foreach (($pagos['cash_qr'] ?? []) as $r): ?>
                  <option value="<?php echo htmlspecialchars($r['confiar_id']); ?>" data-valor="<?php echo (float)$r['valor']; ?>">CONF <?php echo htmlspecialchars($r['confiar_id']); ?> — $<?php echo number_format((float)$r['valor'],0); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mt-2"><label class="form-label">Valor pago</label><input id="valorPago" class="form-control form-control-sm" readonly></div>
            <div class="mt-2"><label class="form-label">Recibo de caja Sifone</label><input id="reciboCaja" class="form-control form-control-sm" placeholder="Obligatorio"></div>
            <div class="mt-2"><label class="form-label">Total asignado</label><input id="totalAsignado" class="form-control form-control-sm" readonly></div>
            <div class="mt-3 d-grid">
              <button class="btn btn-primary btn-sm" onclick="guardarTransaccion()"><i class="fas fa-save me-1"></i>Guardar</button>
            </div>
          </div></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($cedula)): ?>
      <div class="card mt-3"><div class="card-header"><strong>Transacciones creadas</strong></div><div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr><th>ID</th><th>Origen</th><th>PSE/CONF</th><th>Recibo Sifone</th><th>Valor pago</th><th>Total asignado</th><th>Items</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            <?php $txModals = []; foreach (($listado['items'] ?? []) as $tx): ?>
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
                  <form method="POST" onsubmit="return confirm('¿Eliminar transacción?');" class="d-inline">
                    <input type="hidden" name="action" value="eliminar">
                    <input type="hidden" name="id" value="<?php echo (int)$tx['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php $txd_inline = $model->getTransaccionDetalles((int)$tx['id']); ?>
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
                  <?php $txh = $model->getTransaccion((int)$tx['id']); $txd = $model->getTransaccionDetalles((int)$tx['id']); ?>
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
        <?php if ((($listado['pages'] ?? 1)) > 1): $tp=(int)$listado['current_page']; $pgs=(int)$listado['pages']; ?>
          <nav><ul class="pagination pagination-sm justify-content-center">
            <?php for ($i=1;$i<=$pgs;$i++): ?>
              <li class="page-item <?php echo $i==$tp?'active':''; ?>"><a class="page-link" href="?cedula=<?php echo urlencode($cedula); ?>&tpage=<?php echo $i; ?>#txlist"><?php echo $i; ?></a></li>
            <?php endfor; ?>
          </ul></nav>
        <?php endif; ?>
      </div></div>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Modal listado PSE -->
<div class="modal fade" id="modalPse" tabindex="-1">
<div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-list me-2"></i>PSE relacionados</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <form class="row g-2 mb-2" onsubmit="return false">
        <div class="col-md-3"><label class="form-label small">Fecha</label><input type="date" class="form-control form-control-sm" id="pseFiltroFecha"></div>
        <div class="col-md-3"><label class="form-label small">Referencia 2 (CC)</label><input class="form-control form-control-sm" id="pseFiltroRef2" placeholder="CC"></div>
        <div class="col-md-2"><label class="form-label small">Referencia 3 (N)</label><input class="form-control form-control-sm" id="pseFiltroRef3" placeholder="N"></div>
        <div class="col-md-2"><label class="form-label small">PSE ID</label><input class="form-control form-control-sm" id="pseFiltroId" placeholder="pse_id"></div>
        <div class="col-md-2 align-self-end"><button class="btn btn-sm btn-outline-primary w-100" onclick="filtrarPse()"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
      </form>
      <div class="small text-muted mb-1">CC/N corresponde a Referencia 2 / Referencia 3 del PSE.</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light"><tr><th>PSE</th><th>Fecha</th><th>Valor</th><th>Usado</th><th>Restante</th><th>CC/N</th><th></th></tr></thead>
          <tbody id="pseListBody">
          </tbody>
        </table>
      </div>
    </div>
  </div></div>
  </div>

<!-- Modal listado Cash/QR -->
<div class="modal fade" id="modalCash" tabindex="-1">
<div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-list me-2"></i>Cash/QR confirmados</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <form class="row g-2 mb-2" onsubmit="return false">
        <div class="col-md-3"><label class="form-label small">Fecha</label><input type="date" class="form-control form-control-sm" id="cashFiltroFecha"></div>
        <div class="col-md-3"><label class="form-label small">Cédula asignada</label><input class="form-control form-control-sm" id="cashFiltroCedula" placeholder="Cédula"></div>
        <div class="col-md-3"><label class="form-label small">Descripción</label><input class="form-control form-control-sm" id="cashFiltroDesc" placeholder="Descripción"></div>
        <div class="col-md-2"><label class="form-label small">Confiar ID</label><input class="form-control form-control-sm" id="cashFiltroId" placeholder="confiar_id"></div>
        <div class="col-md-1 align-self-end"><button class="btn btn-sm btn-outline-primary w-100" onclick="filtrarCash()"><i class="fas fa-filter"></i></button></div>
      </form>
      <div class="small text-muted mb-1">Se muestra la descripción del movimiento y la cédula asignada en confirmación.</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light"><tr><th>CONF</th><th>Fecha</th><th>Valor</th><th>Usado</th><th>Restante</th><th>Descripción</th><th>Cédula asignada</th><th></th></tr></thead>
          <tbody id="cashListBody">
          </tbody>
        </table>
      </div>
    </div>
  </div></div>
  </div>

<script>
// Autocomplete de asociado
async function buscarAsociadosMain(){
  const input = document.getElementById('buscarCedula');
  const cont = document.getElementById('asoc_results_main');
  const q = input.value.trim();
  cont.innerHTML = '';
  if (q.length < 2) return;
  cont.innerHTML = '<div class="list-group-item text-muted">Buscando…</div>';
  try {
    const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/buscar_asociados.php?q='+encodeURIComponent(q));
    const data = await res.json();
    const items = data.items||[];
    if (items.length === 0) { cont.innerHTML = '<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    const frag = document.createDocumentFragment();
    items.forEach(a => {
      const el = document.createElement('a');
      el.href = '#';
      el.className = 'list-group-item list-group-item-action';
      el.textContent = `${a.cedula} — ${a.nombre}`;
      el.addEventListener('click', (ev) => {
        ev.preventDefault();
        input.value = a.cedula;
        cont.innerHTML = '';
      });
      frag.appendChild(el);
    });
    cont.innerHTML = '';
    cont.appendChild(frag);
  } catch (e) {
    cont.innerHTML = '<div class="list-group-item text-danger">Error de búsqueda</div>';
  }
}

document.getElementById('buscarCedula')?.addEventListener('input', buscarAsociadosMain);
document.addEventListener('click', (e) => {
  const cont = document.getElementById('asoc_results_main');
  if (!cont.contains(e.target) && e.target.id !== 'buscarCedula'){
    cont.innerHTML = '';
  }
});
function updateTotal(){
  let total = 0;
  document.querySelectorAll('#rubroBody .rubro-asignar').forEach(inp => { total += Number(inp.value||0); });
  document.getElementById('totalAsignado').value = total.toLocaleString();
}

function updatePagoBox(){
  const origen = document.getElementById('origen').value;
  document.getElementById('pagoPseBox').classList.toggle('d-none', origen !== 'pse');
  document.getElementById('pagoCashBox').classList.toggle('d-none', origen !== 'cash_qr');
  document.getElementById('valorPago').value = '';
}

document.getElementById('origen')?.addEventListener('change', updatePagoBox);
document.querySelectorAll('#rubroBody .rubro-asignar').forEach(inp => inp.addEventListener('input', updateTotal));
document.getElementById('pseId')?.addEventListener('change', function(){
  const opt = this.selectedOptions[0];
  const val = opt ? Number(opt.dataset.valor||0) : 0;
  document.getElementById('valorPago').value = val.toLocaleString();
  autofillAsignaciones();
});
document.getElementById('confiarId')?.addEventListener('change', function(){
  const opt = this.selectedOptions[0];
  const val = opt ? Number(opt.dataset.valor||0) : 0;
  document.getElementById('valorPago').value = val.toLocaleString();
  autofillAsignaciones();
});

function seleccionarPse(id, valor){
  const sel = document.getElementById('pseId');
  sel.value = id;
  document.getElementById('origen').value = 'pse';
  updatePagoBox();
  document.getElementById('valorPago').value = Number(valor||0).toLocaleString();
  autofillAsignaciones();
}
function seleccionarCash(id, valor){
  const sel = document.getElementById('confiarId');
  sel.value = id;
  document.getElementById('origen').value = 'cash_qr';
  updatePagoBox();
  document.getElementById('valorPago').value = Number(valor||0).toLocaleString();
  autofillAsignaciones();
}

// Cargar listas en modales con filtros en cliente
const pagosPse = <?php echo json_encode($pagos['pse'] ?? []); ?>;
const pagosCash = <?php echo json_encode($pagos['cash_qr'] ?? []); ?>;

function renderPseList(list){
  const body = document.getElementById('pseListBody');
  body.innerHTML = '';
  list.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${r.pse_id}</td>
                    <td>${r.fecha||''}</td>
                    <td>$${Number(r.valor||0).toLocaleString()}</td>
                    <td>$${Number(r.utilizado||0).toLocaleString()}</td>
                    <td>$${Number(r.restante||0).toLocaleString()}</td>
                    <td>${r.referencia_2||''} / ${r.referencia_3||''}</td>
                    <td class="text-end"><button class="btn btn-sm btn-primary" ${Number(r.restante||0)<=0?'disabled':''} data-bs-dismiss="modal">Seleccionar</button></td>`;
    tr.querySelector('button').addEventListener('click', ()=> seleccionarPse(r.pse_id, Number(r.restante||r.valor||0)));
    body.appendChild(tr);
  });
}
function renderCashList(list){
  const body = document.getElementById('cashListBody');
  body.innerHTML = '';
  list.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${r.confiar_id}</td>
                    <td>${r.fecha||''}</td>
                    <td>$${Number(r.valor||0).toLocaleString()}</td>
                    <td>$${Number(r.utilizado||0).toLocaleString()}</td>
                    <td>$${Number(r.restante||0).toLocaleString()}</td>
                    <td class="text-truncate" style="max-width:240px">${r.descripcion||''}</td>
                    <td>${r.cedula_asignada||''}</td>
                    <td class="text-end"><button class="btn btn-sm btn-primary" ${Number(r.restante||0)<=0?'disabled':''} data-bs-dismiss="modal">Seleccionar</button></td>`;
    tr.querySelector('button').addEventListener('click', ()=> seleccionarCash(r.confiar_id, Number(r.restante||r.valor||0)));
    body.appendChild(tr);
  });
}

function filtrarPse(){
  const f = document.getElementById('pseFiltroFecha').value;
  const ref2 = document.getElementById('pseFiltroRef2').value.trim();
  const ref3 = document.getElementById('pseFiltroRef3').value.trim();
  const pid = document.getElementById('pseFiltroId').value.trim();
  let list = pagosPse.slice();
  if (f) list = list.filter(r => (r.fecha||'').startsWith(f));
  if (ref2) list = list.filter(r => String(r.referencia_2||'').includes(ref2));
  if (ref3) list = list.filter(r => String(r.referencia_3||'').includes(ref3));
  if (pid) list = list.filter(r => String(r.pse_id||'').includes(pid));
  renderPseList(list);
}
function filtrarCash(){
  const f = document.getElementById('cashFiltroFecha').value;
  const ced = document.getElementById('cashFiltroCedula').value.trim();
  const desc = document.getElementById('cashFiltroDesc').value.trim().toLowerCase();
  const cid = document.getElementById('cashFiltroId').value.trim();
  let list = pagosCash.slice();
  if (f) list = list.filter(r => (r.fecha||'').startsWith(f));
  if (ced) list = list.filter(r => String(r.cedula_asignada||'').includes(ced));
  if (desc) list = list.filter(r => String(r.descripcion||'').toLowerCase().includes(desc));
  if (cid) list = list.filter(r => String(r.confiar_id||'').includes(cid));
  renderCashList(list);
}

// Inicializar listas al abrir modales
document.getElementById('modalPse')?.addEventListener('shown.bs.modal', ()=> { renderPseList(pagosPse); });
document.getElementById('modalCash')?.addEventListener('shown.bs.modal', ()=> { renderCashList(pagosCash); });

async function guardarTransaccion(){
  const cedula = '<?php echo htmlspecialchars($cedula); ?>';
  const origen = document.getElementById('origen').value;
  const pseId = document.getElementById('pseId').value || null;
  const confiarId = document.getElementById('confiarId').value || null;
  // Tomar el valor de pago directamente del option seleccionado para evitar problemas de formato local
  let valorPago = 0;
  if (origen === 'pse') {
    const opt = document.getElementById('pseId').selectedOptions[0];
    valorPago = Number(opt ? (opt.dataset.valor||0) : 0);
  } else if (origen === 'cash_qr') {
    const opt = document.getElementById('confiarId').selectedOptions[0];
    valorPago = Number(opt ? (opt.dataset.valor||0) : 0);
  } else {
    // Fallback: parsear el input removiendo cualquier separador
    valorPago = Number((document.getElementById('valorPago').value||'0').replace(/[^\d.-]/g,''));
  }
  const detalles = [];
  const reciboCaja = (document.getElementById('reciboCaja').value||'').trim();
  if (!reciboCaja) { alert('El campo "Recibo de caja Sifone" es obligatorio.'); return; }
  document.querySelectorAll('#rubroBody tr').forEach(tr => {
    const tipo = tr.dataset.tipo;
    const ref = tr.dataset.ref || null;
    const prod = tr.dataset.prod || null;
    const desc = tr.dataset.desc || null;
    const recomendado = (function(){
      const el = tr.querySelector('.col-recomendado');
      if (!el) return 0;
      const txt = el.textContent.replace(/\$/g,'').replace(/,/g,'');
      const num = parseFloat(txt);
      return isNaN(num)?0:num;
    })();
    const asignado = Number(tr.querySelector('.rubro-asignar').value||0);
    if (asignado > 0){
      detalles.push({ tipo_rubro: tipo, referencia_credito: ref, producto_id: prod?Number(prod):null, descripcion: desc, valor_recomendado: recomendado, valor_asignado: asignado });
    }
  });
  if (valorPago <= 0) { alert('Seleccione un pago'); return; }
  let sum = 0; detalles.forEach(d=> sum+= (d.valor_asignado||0));
  if (sum > valorPago + 0.01) { alert('La suma asignada excede el valor del pago'); return; }

  const form = new FormData();
  form.append('action','crear');
  form.append('cedula', cedula);
  form.append('origen', origen);
  form.append('pse_id', pseId||'');
  form.append('confiar_id', confiarId||'');
  form.append('valor_pago', String(valorPago));
  form.append('detalles', JSON.stringify(detalles));
  form.append('recibo_caja_sifone', reciboCaja);
  const res = await fetch('', { method:'POST', body:form });
  location.reload();
}

updatePagoBox();
updateTotal();

function parseMoneda(text){
  if (!text) return 0;
  const num = Number(String(text).replace(/[^\d.-]/g,''));
  return isNaN(num) ? 0 : num;
}

function obtenerRecomendadoDeFila(tr){
  const el = tr.querySelector('.col-recomendado');
  if (!el) return 0;
  return parseMoneda(el.textContent);
}

function autofillAsignaciones(){
  // Obtiene el valor del pago directamente del option seleccionado (evita formato local)
  let pago = 0;
  const origen = document.getElementById('origen').value;
  if (origen === 'pse') {
    const opt = document.getElementById('pseId').selectedOptions[0];
    pago = Number(opt ? (opt.dataset.valor||0) : 0);
  } else if (origen === 'cash_qr') {
    const opt = document.getElementById('confiarId').selectedOptions[0];
    pago = Number(opt ? (opt.dataset.valor||0) : 0);
  } else {
    pago = parseMoneda(document.getElementById('valorPago').value||'0');
  }
  if (pago <= 0) return;
  let restante = pago;
  const filas = Array.from(document.querySelectorAll('#rubroBody tr'));
  // Resetear valores antes de asignar
  filas.forEach(tr => { const inp = tr.querySelector('.rubro-asignar'); if (inp) inp.value = 0; });
  // Asignar en orden por filas: hasta el recomendado o hasta agotar el pago
  for (const tr of filas){
    const recomendado = obtenerRecomendadoDeFila(tr);
    if (recomendado <= 0) continue;
    const asignar = Math.min(recomendado, restante);
    const inp = tr.querySelector('.rubro-asignar');
    if (inp){ inp.value = asignar.toFixed(2); }
    restante -= asignar;
    if (restante <= 0) break;
  }
  updateTotal();
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>
