<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Transaccion.php';

$auth = new AuthController();
$auth->requireModule('oficina.trx_list');
$currentUser = $auth->getCurrentUser();
$model = new Transaccion();

// Cargar pagos disponibles (incluye usado/restante)
$pagos = $model->getPagosDisponibles();

$pageTitle = 'Trx List - Oficina';
$currentPage = 'trx_list';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-list-ul me-2"></i>Trx List</h1>
      </div>

      <div class="row g-3">
        <div class="col-12 mb-2">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary active" id="tabPseBtn" onclick="showTab('pse')"><i class="fas fa-plug me-1"></i>PSE</button>
            <button type="button" class="btn btn-outline-primary" id="tabCashBtn" onclick="showTab('cash')"><i class="fas fa-money-bill-wave me-1"></i>Cash/QR</button>
          </div>
        </div>

        <div class="col-lg-12" id="pseSection">
          <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><strong>PSE relacionados</strong></div>
            <div class="card-body">
              <form class="row g-2 mb-2" onsubmit="return false">
                <div class="col-md-2"><label class="form-label small">Fecha</label><input type="date" class="form-control form-control-sm" id="pseFiltroFecha"></div>
                <div class="col-md-3"><label class="form-label small">Cédula</label><input class="form-control form-control-sm" id="pseFiltroRef2" placeholder="Cédula"></div>
                <div class="col-md-3"><label class="form-label small">Nombre</label><input class="form-control form-control-sm" id="pseFiltroRef3" placeholder="Nombre"></div>
                <div class="col-md-2"><label class="form-label small">Estado</label>
                  <select class="form-select form-select-sm" id="pseFiltroEstado">
                    <option value="no_completado" selected>Activas (sin asignar / parcial)</option>
                    <option value="sin_asignar">Sin asignar</option>
                    <option value="parcial">Parcial</option>
                    <option value="completado">Completado</option>
                    <option value="">Todos</option>
                  </select>
                </div>
                <div class="col-md-2 align-self-end"><button class="btn btn-sm btn-outline-primary w-100" onclick="filtrarPseList()"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
              </form>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                  <thead class="table-light"><tr><th>PSE</th><th>Fecha</th><th>Valor</th><th>Usado</th><th>Restante</th><th>Estado</th><th>Cédula</th><th>Nombre</th></tr></thead>
                  <tbody id="pseListTbl">
                  <?php foreach (($pagos['pse'] ?? []) as $r): 
                    $valor = (float)($r['valor'] ?? 0); $usado = (float)($r['utilizado'] ?? 0);
                    $estado = ($usado <= 0) ? '<span class="badge bg-secondary">Sin asignar</span>' : (($usado < $valor) ? '<span class="badge bg-warning text-dark">Parcial</span>' : '<span class="badge bg-success">Completado</span>');
                    // Por defecto ocultar Completado en render SSR si no viene filtro explícito (fallback; el filtro principal es en JS)
                    if (!isset($_GET['pse_estado']) && $usado >= $valor) continue;
                  ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['pse_id'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['fecha'] ?? ''); ?></td>
                      <td><?php echo '$'.number_format($valor,0); ?></td>
                      <td><?php echo '$'.number_format($usado,0); ?></td>
                      <td><?php echo '$'.number_format(max($valor-$usado,0),0); ?></td>
                      <td><?php echo $estado; ?></td>
                      <td><?php echo htmlspecialchars($r['referencia_2'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['referencia_3'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-12 d-none" id="cashSection">
          <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><strong>Cash/QR confirmados</strong></div>
            <div class="card-body">
              <form class="row g-2 mb-2" onsubmit="return false">
                <div class="col-md-2"><label class="form-label small">Fecha</label><input type="date" class="form-control form-control-sm" id="cashFiltroFecha"></div>
                <div class="col-md-3"><label class="form-label small">Cédula asignada</label><input class="form-control form-control-sm" id="cashFiltroCedula" placeholder="Cédula"></div>
                <div class="col-md-3"><label class="form-label small">Descripción</label><input class="form-control form-control-sm" id="cashFiltroDesc" placeholder="Descripción"></div>
                <div class="col-md-2"><label class="form-label small">Estado</label>
                  <select class="form-select form-select-sm" id="cashFiltroEstado">
                    <option value="no_completado" selected>Activas (sin asignar / parcial)</option>
                    <option value="sin_asignar">Sin asignar</option>
                    <option value="parcial">Parcial</option>
                    <option value="completado">Completado</option>
                    <option value="">Todos</option>
                  </select>
                </div>
                <div class="col-md-2 align-self-end"><button class="btn btn-sm btn-outline-primary w-100" onclick="filtrarCashList()"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
              </form>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                  <thead class="table-light"><tr><th>CONF</th><th>Fecha</th><th>Valor</th><th>Usado</th><th>Restante</th><th>Estado</th><th>Descripción</th><th>Cédula asignada</th></tr></thead>
                  <tbody id="cashListTbl">
                  <?php foreach (($pagos['cash_qr'] ?? []) as $r): 
                    $valor = (float)($r['valor'] ?? 0); $usado = (float)($r['utilizado'] ?? 0);
                    $estado = ($usado <= 0) ? '<span class="badge bg-secondary">Sin asignar</span>' : (($usado < $valor) ? '<span class="badge bg-warning text-dark">Parcial</span>' : '<span class="badge bg-success">Completado</span>');
                  ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['confiar_id'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['fecha'] ?? ''); ?></td>
                      <td><?php echo '$'.number_format($valor,0); ?></td>
                      <td><?php echo '$'.number_format($usado,0); ?></td>
                      <td><?php echo '$'.number_format(max($valor-$usado,0),0); ?></td>
                      <td><?php echo $estado; ?></td>
                      <td class="text-truncate" style="max-width:320px"><?php echo htmlspecialchars($r['descripcion'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['cedula_asignada'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
const pagosPse = <?php echo json_encode(array_values($pagos['pse'] ?? [])); ?>;
const pagosCash = <?php echo json_encode(array_values($pagos['cash_qr'] ?? [])); ?>;

function estadoBadge(valor, usado){
  const v = Number(valor||0), u = Number(usado||0);
  if (u <= 0) return '<span class="badge bg-secondary">Sin asignar</span>';
  if (u < v) return '<span class="badge bg-warning text-dark">Parcial</span>';
  return '<span class="badge bg-success">Completado</span>';
}

function renderPse(list){
  const body = document.getElementById('pseListTbl');
  body.innerHTML = '';
  if (!list || list.length === 0){ body.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Sin resultados</td></tr>'; return; }
  list.forEach(r => {
    const tr = document.createElement('tr');
    const estado = estadoBadge(r.valor, r.utilizado);
    tr.innerHTML = `<td>${r.pse_id}</td>
                    <td>${r.fecha||''}</td>
                    <td>$${Number(r.valor||0).toLocaleString()}</td>
                    <td>$${Number(r.utilizado||0).toLocaleString()}</td>
                    <td>$${Number((r.valor||0)-(r.utilizado||0)).toLocaleString()}</td>
                    <td>${estado}</td>
                    <td>${r.referencia_2||''}</td>
                    <td>${r.referencia_3||''}</td>`;
    body.appendChild(tr);
  });
}

function renderCash(list){
  const body = document.getElementById('cashListTbl');
  body.innerHTML = '';
  if (!list || list.length === 0){ body.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Sin resultados</td></tr>'; return; }
  list.forEach(r => {
    const tr = document.createElement('tr');
    const estado = estadoBadge(r.valor, r.utilizado);
    tr.innerHTML = `<td>${r.confiar_id}</td>
                    <td>${r.fecha||''}</td>
                    <td>$${Number(r.valor||0).toLocaleString()}</td>
                    <td>$${Number(r.utilizado||0).toLocaleString()}</td>
                    <td>$${Number((r.valor||0)-(r.utilizado||0)).toLocaleString()}</td>
                    <td>${estado}</td>
                    <td class="text-truncate" style="max-width:320px">${r.descripcion||''}</td>
                    <td>${r.cedula_asignada||''}</td>`;
    body.appendChild(tr);
  });
}

function showTab(which){
  const isPse = which === 'pse';
  document.getElementById('pseSection').classList.toggle('d-none', !isPse);
  document.getElementById('cashSection').classList.toggle('d-none', isPse);
  document.getElementById('tabPseBtn').classList.toggle('active', isPse);
  document.getElementById('tabCashBtn').classList.toggle('active', !isPse);
}

// Enlazar botones y vista inicial
document.getElementById('tabPseBtn')?.addEventListener('click', function(e){ e.preventDefault(); showTab('pse'); });
document.getElementById('tabCashBtn')?.addEventListener('click', function(e){ e.preventDefault(); showTab('cash'); });
showTab('pse');

function filtrarPseList(){
  const f = document.getElementById('pseFiltroFecha').value;
  const ref2 = document.getElementById('pseFiltroRef2').value.trim();
  const ref3 = document.getElementById('pseFiltroRef3').value.trim();
  const est = document.getElementById('pseFiltroEstado').value;
  let list = pagosPse.slice();
  if (f) list = list.filter(r => (r.fecha||'').startsWith(f));
  if (ref2) list = list.filter(r => String(r.referencia_2||'').includes(ref2));
  if (ref3) list = list.filter(r => String(r.referencia_3||'').includes(ref3));
  if (est === 'no_completado') list = list.filter(r => Number(r.utilizado||0) < Number(r.valor||0));
  else if (est === 'sin_asignar') list = list.filter(r => Number(r.utilizado||0) <= 0);
  else if (est === 'parcial') list = list.filter(r => { const v=Number(r.valor||0), u=Number(r.utilizado||0); return u>0 && u<v; });
  else if (est === 'completado') list = list.filter(r => Number(r.utilizado||0) >= Number(r.valor||0));
  renderPse(list);
}

function filtrarCashList(){
  const f = document.getElementById('cashFiltroFecha').value;
  const ced = document.getElementById('cashFiltroCedula').value.trim();
  const desc = document.getElementById('cashFiltroDesc').value.trim().toLowerCase();
  const est = document.getElementById('cashFiltroEstado').value;
  let list = pagosCash.slice();
  if (f) list = list.filter(r => (r.fecha||'').startsWith(f));
  if (ced) list = list.filter(r => String(r.cedula_asignada||'').includes(ced));
  if (desc) list = list.filter(r => String(r.descripcion||'').toLowerCase().includes(desc));
  if (est === 'no_completado') list = list.filter(r => Number(r.utilizado||0) < Number(r.valor||0));
  else if (est === 'sin_asignar') list = list.filter(r => Number(r.utilizado||0) <= 0);
  else if (est === 'parcial') list = list.filter(r => { const v=Number(r.valor||0), u=Number(r.utilizado||0); return u>0 && u<v; });
  else if (est === 'completado') list = list.filter(r => Number(r.utilizado||0) >= Number(r.valor||0));
  renderCash(list);
}

// Inicialización: si no hay server-side rows, usa client-side renderer
if (document.getElementById('pseListTbl').children.length === 0) { filtrarPseList(); } else { filtrarPseList(); }
if (document.getElementById('cashListTbl').children.length === 0) { filtrarCashList(); } else { filtrarCashList(); }
</script>

<?php include '../../../views/layouts/footer.php'; ?>

