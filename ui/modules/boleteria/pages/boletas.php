<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$authController = new AuthController();
$authController->requireModule('boleteria.boletas');
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Boletería - Boletas';
$currentPage = 'boleteria_boletas';
include '../../../views/layouts/header.php';
?>

<style>
.timeline {
  position: relative;
  padding-left: 20px;
}

.timeline-item {
  position: relative;
}

.timeline-marker {
  position: absolute;
  left: -25px;
  top: 5px;
}

.timeline-marker i {
  font-size: 12px;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: -19px;
  top: 20px;
  bottom: -20px;
  width: 2px;
  background-color: #e9ecef;
}
</style>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-ticket-alt me-2"></i>Boletas</h1>
        <div class="d-flex gap-2">
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBoleta"><i class="fas fa-plus me-1"></i>Nueva boleta</button>
          <button class="btn btn-outline-secondary btn-sm" disabled><i class="fas fa-layer-group me-1"></i>Crear lote</button>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <form class="row g-2" id="formFiltros">
            <div class="col-12 col-md-3">
              <label class="form-label">Categoría</label>
              <select class="form-select" id="filtroCategoria"><option value="">Todas</option></select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label">Estado</label>
              <select class="form-select" id="filtroEstado">
                <option value="">Todos</option>
                <option value="disponible">Disponible</option>
                <option value="vendida">Vendida</option>
                <option value="anulada">Anulada</option>
                <option value="contabilizada">Contabilizada</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label">Serial</label>
              <input type="text" class="form-control" id="filtroSerial" placeholder="Buscar serial">
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label">Cédula</label>
              <input type="text" class="form-control" id="filtroCedula" placeholder="Buscar cédula">
            </div>
            <div class="col-12"></div>
            <div class="col-6 col-md-3">
              <label class="form-label">F. vendida (desde - hasta)</label>
              <div class="input-group">
                <input type="date" class="form-control" id="fvDesde">
                <span class="input-group-text">a</span>
                <input type="date" class="form-control" id="fvHasta">
              </div>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">F. vencimiento (desde - hasta)</label>
              <div class="input-group">
                <input type="date" class="form-control" id="fvenDesde">
                <span class="input-group-text">a</span>
                <input type="date" class="form-control" id="fvenHasta">
              </div>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
              <button class="btn btn-outline-primary w-100" type="button" id="btnFiltrar">Filtrar</button>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
              <button class="btn btn-outline-secondary w-100" type="button" id="btnLimpiar">Eliminar filtros</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted" id="bolResumen"></div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Paginación">
              <button class="btn btn-outline-secondary" id="bolPrev">«</button>
              <button class="btn btn-outline-secondary" id="bolNext">»</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-striped align-middle" id="tablaBoletas">
              <thead class="table-light">
                <tr>
                  <th data-sort="serial" class="sortable">Serial</th>
                  <th data-sort="categoria" class="sortable">Categoría</th>
                  <th data-sort="precio_venta" class="text-end sortable">Precio</th>
                  <th data-sort="estado" class="sortable">Estado</th>
                  <th data-sort="">Asociado</th>
                  <th>Método</th>
                  
                  <th data-sort="fecha_vendida" class="sortable">F. vendida</th>
                  <th data-sort="fecha_vencimiento" class="sortable">F. vencimiento</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody id="boletasBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Crear Boleta (solo UI, sin backend aún) -->
      <div class="modal fade" id="modalBoleta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Nueva boleta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="formBoleta">
                <div class="mb-3">
                  <label class="form-label">Categoría</label>
                  <select class="form-select" id="bolCategoria" required><option value="">Seleccione…</option></select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Serial (alfanumérico)</label>
                  <input type="text" class="form-control" id="bolSerial" required>
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">Precio compra (snapshot)</label>
                    <input type="number" class="form-control" id="bolPrecioCompra" step="0.01" min="0" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Precio venta (snapshot)</label>
                    <input type="number" class="form-control" id="bolPrecioVenta" step="0.01" min="0" required>
                  </div>
                </div>
                <div class="mb-3 mt-2">
                  <label class="form-label">Fecha de vencimiento (opcional)</label>
                  <input type="date" class="form-control" id="bolFechaVencimiento">
                </div>
                <div class="mb-3 mt-2">
                  <label class="form-label">Archivo (opcional) JPG/JPEG/PNG/PDF</label>
                  <input type="file" class="form-control" id="bolArchivo" accept=".jpg,.jpeg,.png,.pdf">
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id="btnGuardarBoleta">Guardar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Vender Boleta -->
      <div class="modal fade" id="modalVender" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Vender boleta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2 position-relative">
                <label class="form-label">Buscar asociado (cédula o nombre)</label>
                <input type="text" class="form-control" id="buscarAsociado" placeholder="Buscar por cédula o nombre" autocomplete="off" oninput="buscarAsociadosBol()">
                <div id="bol_asoc_results" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
              </div>
              <div class="row g-2 mt-2">
                <div class="col-12">
                  <label class="form-label">Método de venta</label>
                  <select class="form-select" id="metodoVenta" required>
                    <option value="credito" selected>Crédito</option>
                    <option value="regalo_cooperativa">Regalo Cooperativa</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id="btnConfirmarVenta" disabled>Confirmar venta</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Contabilizar Boleta -->
      <div class="modal fade" id="modalContabilizar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Contabilizar boleta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Comprobante</label>
                <input type="text" class="form-control" id="comprobanteContabilizacion" placeholder="Número de comprobante" required>
              </div>
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Solo se pueden contabilizar boletas que estén vendidas.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id="btnConfirmarContabilizacion">Contabilizar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Detalle de Boleta -->
      <div class="modal fade" id="modalDetalleBoleta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Detalle de boleta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="detalleBoletaContenido">
                <div class="text-center text-muted">
                  <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                  <p>Cargando detalle...</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  poblarCategoriasSelects();
  cargarBoletas();
  document.getElementById('btnFiltrar').addEventListener('click', cargarBoletas);
  document.getElementById('btnLimpiar').addEventListener('click', limpiarFiltrosBoletas);
  document.getElementById('btnGuardarBoleta').addEventListener('click', guardarBoleta);
  document.getElementById('btnConfirmarContabilizacion').addEventListener('click', confirmarContabilizacion);
  document.getElementById('bolSerial').addEventListener('input', (e) => {
    const v = e.target.value;
    // Solo alfanumérico y guiones opcionales si lo deseas; por ahora alfanumérico puro
    const limpio = v.replace(/[^a-zA-Z0-9]/g, '');
    if (v !== limpio) e.target.value = limpio;
  });
  document.getElementById('bolSerial').addEventListener('blur', validarSerialUnico);
  document.getElementById('bolCategoria').addEventListener('change', () => {
    // revalidar serial cuando cambia la categoría
    if (document.getElementById('bolSerial').value.trim()) { validarSerialUnico(); }
  });
  document.querySelectorAll('#tablaBoletas thead th.sortable').forEach(th => th.addEventListener('click', () => cambiarOrdenBol(th.dataset.sort)));
  document.getElementById('bolPrev').addEventListener('click', () => cambiarPaginaBol(-1));
  document.getElementById('bolNext').addEventListener('click', () => cambiarPaginaBol(1));
});

function formatoMoneda(n) {
  const v = Number(n || 0);
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v);
}

function sinSegundos(ts) {
  if (!ts) return '';
  const s = String(ts);
  const m = s.match(/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/);
  return m ? `${m[1]} ${m[2]}` : s;
}

let bolPage = 1, bolPages = 1, bolSortBy = 'id', bolSortDir = 'DESC';
let boletaAOperar = null;
window.asociadoSeleccionado = null;

async function poblarCategoriasSelects() {
  try {
    const res = await fetch('../../boleteria/api/categorias_listar.php?limit=1000');
    const json = await res.json();
    const items = (json && json.data && json.data.items) ? json.data.items : [];
    const selFiltro = document.getElementById('filtroCategoria');
    const selBol = document.getElementById('bolCategoria');
    selFiltro.innerHTML = '<option value="">Todas</option>' + items.map(i => `<option value="${i.id}">${escapeHtml(i.nombre)}</option>`).join('');
    selBol.innerHTML = '<option value="">Seleccione…</option>' + items.map(i => `<option value="${i.id}" data-pc="${i.precio_compra}" data-pv="${i.precio_venta}">${escapeHtml(i.nombre)}</option>`).join('');
    selBol.addEventListener('change', e => {
      const opt = selBol.options[selBol.selectedIndex];
      if (opt && opt.dataset) {
        document.getElementById('bolPrecioCompra').value = opt.dataset.pc || '';
        document.getElementById('bolPrecioVenta').value = opt.dataset.pv || '';
      }
    });
  } catch (e) {
    console.error(e);
  }
}

async function cargarBoletas() {
  const tbody = document.getElementById('boletasBody');
  tbody.innerHTML = '<tr><td colspan="9" class="text-muted">Cargando…</td></tr>';
  const categoria_id = document.getElementById('filtroCategoria').value;
  const estado = document.getElementById('filtroEstado').value;
  const serial = document.getElementById('filtroSerial').value;
  const cedula = document.getElementById('filtroCedula').value;
  const fv_desde = document.getElementById('fvDesde').value;
  const fv_hasta = document.getElementById('fvHasta').value;
  const fven_desde = document.getElementById('fvenDesde').value;
  const fven_hasta = document.getElementById('fvenHasta').value;
  
  // Debug: mostrar filtros en consola
  console.log('Filtros aplicados:', { categoria_id, estado, serial, cedula, fv_desde, fv_hasta, fven_desde, fven_hasta });
  
  const params = new URLSearchParams({ categoria_id, estado, serial, cedula, fv_desde, fv_hasta, fven_desde, fven_hasta, page: bolPage, limit: 10, sort_by: bolSortBy, sort_dir: bolSortDir });
  const url = '../../boleteria/api/boletas_listar.php?' + params.toString();
  try {
    const res = await fetch(url);
    if (!res.ok) {
      const txt = await res.text();
      tbody.innerHTML = `<tr><td colspan="9" class="text-danger">Error ${res.status}: ${escapeHtml(txt)}</td></tr>`;
      return;
    }
    const json = await res.json();
    if (!json || json.success === false) {
      tbody.innerHTML = `<tr><td colspan="9" class="text-danger">${escapeHtml(json && json.message ? json.message : 'Error desconocido')}</td></tr>`;
      return;
    }
    const data = json.data || {};
    const items = data.items || [];
    bolPages = data.pages || 1;
    document.getElementById('bolResumen').textContent = `Página ${data.current_page || bolPage} de ${bolPages} · Total: ${data.total || items.length}`;
    if (!items.length) { 
      tbody.innerHTML = '<tr><td colspan="9" class="text-muted">Sin datos.</td></tr>'; 
      return; 
    }
    tbody.innerHTML = items.map(item => {
      const estadoBadge = item.estado === 'disponible' ? '<span class="badge bg-success">Disponible</span>' : 
                          (item.estado === 'vendida' ? '<span class="badge bg-primary">Vendida</span>' : 
                          (item.estado === 'contabilizada' ? '<span class="badge bg-info">Contabilizada</span>' : 
                          '<span class="badge bg-secondary">Anulada</span>'));
      
      // Acciones reorganizadas: arriba (detalle, ver boleta, descargar), abajo (vender/contabilizar, anular)
      const base = '<?php echo getBaseUrl(); ?>';
      const fileBtns = item.archivo_ruta ? `
        <a href="${escapeHtml(base + item.archivo_ruta)}" target="_blank" class="btn btn-outline-primary" title="Ver boleta"><i class="fas fa-eye"></i></a>
        <a href="${escapeHtml(base + item.archivo_ruta)}" download class="btn btn-outline-secondary" title="Descargar factura"><i class="fas fa-download"></i></a>` : '';
      const topRow = `<div class="btn-group btn-group-sm"> 
        <button class="btn btn-outline-info" onclick="abrirDetalleBoleta(${item.id})" title="Ver detalle"><i class="fas fa-info-circle"></i></button>
        ${fileBtns}
      </div>`;
      let bottomRow = '';
      if (item.estado === 'disponible') {
        bottomRow = `<div class=\"btn-group btn-group-sm\">
          <button class=\"btn btn-outline-success\" onclick=\"abrirVender(${item.id})\" title=\"Vender\"><i class=\"fas fa-shopping-cart\"></i></button>
          <button class=\"btn btn-outline-secondary\" onclick=\"anularBoleta(${item.id})\" title=\"Anular\"><i class=\"fas fa-ban\"></i></button>
        </div>`;
      } else if (item.estado === 'vendida') {
        bottomRow = `<div class=\"btn-group btn-group-sm\">
          <button class=\"btn btn-outline-info\" onclick=\"abrirContabilizar(${item.id})\" title=\"Contabilizar\"><i class=\"fas fa-calculator\"></i></button>
          <button class=\"btn btn-outline-secondary\" onclick=\"anularBoleta(${item.id})\" title=\"Anular\"><i class=\"fas fa-ban\"></i></button>
        </div>`;
      } else if (item.estado === 'anulada') {
        bottomRow = `<div class=\"btn-group btn-group-sm\">
          <button class=\"btn btn-outline-primary\" onclick=\"desanularBoleta(${item.id})\" title=\"Desanular\"><i class=\"fas fa-redo\"></i></button>
        </div>`;
      }
      const acciones = `<div class=\"d-flex flex-column gap-1\">${topRow}${bottomRow?('<div>'+bottomRow+'</div>'):''}</div>`;
      const metodoHtml = item.metodo_venta ? `${escapeHtml(item.metodo_venta === 'credito' ? 'Crédito' : 'Regalo Cooperativa')}${item.comprobante ? `<br><small class=\"text-muted\">${escapeHtml(item.comprobante)}</small>` : ''}` : '<span class="text-muted small">—</span>';
      return `<tr>
        <td>${escapeHtml(item.serial)}</td>
        <td>${escapeHtml(item.categoria_nombre || String(item.categoria_id))}</td>
        <td class="text-end"><div class="small"><span class="text-muted">Compra:</span> ${formatoMoneda(item.precio_compra_snapshot)}</div><div class="small"><span class="text-muted">Venta:</span> ${formatoMoneda(item.precio_venta_snapshot)}</div></td>
        <td>${estadoBadge}</td>
        <td>${escapeHtml(item.asociado_cedula || '')}${item.asociado_nombre ? `<br><small class=\"text-muted\">${escapeHtml(item.asociado_nombre)}</small>` : ''}</td>
        <td>${metodoHtml}</td>
        <td><small>${escapeHtml(sinSegundos(item.fecha_vendida) || '')}</small></td>
        <td><small>${item.fecha_vencimiento ? new Date(item.fecha_vencimiento).toLocaleDateString('es-CO') : '-'}</small></td>
        <td class="text-end" style="white-space: nowrap; width:1%">${acciones}</td>
      </tr>`;
    }).join('');
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="9" class="text-danger">Error: ${escapeHtml(String(e))}</td></tr>`;
  }
}

async function desanularBoleta(id) {
  if (!confirm('¿Desanular esta boleta?')) return;
  try {
    const res = await fetch('../../boleteria/api/boletas_desanular.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    const json = await res.json();
    if (json && json.success) { mostrarToast('Boleta desanulada'); cargarBoletas(); } else { alert(json.message || 'No se pudo desanular'); }
  } catch (e) { alert('Error: ' + e); }
}

async function deshacerVenta(id) {
  if (!confirm('¿Deshacer la venta de esta boleta?')) return;
  try {
    const res = await fetch('../../boleteria/api/boletas_deshacer_venta.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    const json = await res.json();
    if (json && json.success) { mostrarToast('Venta deshecha'); cargarBoletas(); } else { alert(json.message || 'No se pudo deshacer'); }
  } catch (e) { alert('Error: ' + e); }
}

function cambiarOrdenBol(col) {
  if (!col) return;
  if (bolSortBy === col) { bolSortDir = (bolSortDir === 'ASC') ? 'DESC' : 'ASC'; } else { bolSortBy = col; bolSortDir = 'ASC'; }
  cargarBoletas();
}

function cambiarPaginaBol(delta) {
  const np = bolPage + delta;
  if (np < 1 || np > bolPages) return;
  bolPage = np;
  cargarBoletas();
}

async function guardarBoleta() {
  const categoria_id = document.getElementById('bolCategoria').value;
  const serial = document.getElementById('bolSerial').value.trim();
  const precio_compra = document.getElementById('bolPrecioCompra').value;
  const precio_venta = document.getElementById('bolPrecioVenta').value;
  if (!categoria_id) { alert('Seleccione categoría'); return; }
  if (!serial) { alert('Serial requerido'); return; }
  if (!/^[a-zA-Z0-9]+$/.test(serial)) { alert('El serial debe ser alfanumérico (sin espacios ni símbolos)'); return; }
  // Validación de unicidad en cliente
  const unico = await esSerialUnico(categoria_id, serial);
  if (!unico) { alert('El serial ya existe en esta categoría'); return; }
  try {
    const formData = new FormData();
    formData.append('categoria_id', categoria_id);
    formData.append('serial', serial);
    formData.append('precio_compra', precio_compra);
    formData.append('precio_venta', precio_venta);
    const fechaVencimiento = document.getElementById('bolFechaVencimiento').value;
    if (fechaVencimiento) { formData.append('fecha_vencimiento', fechaVencimiento); }
    const file = document.getElementById('bolArchivo').files[0];
    if (file) { formData.append('archivo', file); }
    const res = await fetch('../../boleteria/api/boletas_guardar.php', { method: 'POST', body: formData });
    const json = await res.json();
    if (json && json.success) {
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalBoleta'));
      if (modal) modal.hide();
      document.getElementById('formBoleta').reset();
      mostrarToast('Boleta creada con éxito');
      cargarBoletas();
    } else {
      alert(json && json.message ? json.message : 'No se pudo guardar');
    }
  } catch (e) { alert('Error: ' + e); }
}

async function validarSerialUnico() {
  const categoria_id = document.getElementById('bolCategoria').value;
  const serial = document.getElementById('bolSerial').value.trim();
  const avisoId = 'avisoSerial';
  let aviso = document.getElementById(avisoId);
  if (!aviso) {
    aviso = document.createElement('div');
    aviso.id = avisoId;
    aviso.className = 'form-text';
    document.getElementById('bolSerial').closest('.mb-3').appendChild(aviso);
  }
  aviso.textContent = '';
  if (!categoria_id || !serial) return;
  if (!/^[a-zA-Z0-9]+$/.test(serial)) return;
  const unico = await esSerialUnico(categoria_id, serial);
  if (!unico) {
    aviso.textContent = 'Este serial ya existe en la categoría seleccionada.';
    aviso.className = 'form-text text-danger';
  } else {
    aviso.textContent = 'Serial disponible.';
    aviso.className = 'form-text text-success';
  }
}

async function esSerialUnico(categoria_id, serial) {
  try {
    const params = new URLSearchParams({ categoria_id, serial });
    const res = await fetch('../../boleteria/api/boletas_existe.php?' + params.toString());
    const json = await res.json();
    if (json && json.success) { return !json.exists; }
  } catch (e) { /* ignore */ }
  // si hay error, no bloqueamos, dejamos que el servidor valide
  return true;
}

function abrirVender(id) {
  boletaAOperar = id;
  document.getElementById('buscarAsociado').value = '';
  const dl = document.getElementById('asociadosList'); if (dl) dl.innerHTML = '';
  const cont = document.getElementById('bol_asoc_results'); if (cont) cont.innerHTML = '';
  window.asociadoSeleccionado = null;
  // Mantener método por defecto 'credito' seleccionado
  actualizarEstadoConfirmarVenta();
  new bootstrap.Modal(document.getElementById('modalVender')).show();
}

async function buscarAsociadosBol(){
  const input = document.getElementById('buscarAsociado');
  const q = input.value.trim();
  const cont = document.getElementById('bol_asoc_results');
  cont.innerHTML = '<div class="list-group-item text-muted">Buscando…</div>';
  if (q.length < 2) { cont.innerHTML = ''; return; }
  try {
    const res = await fetch('../../oficina/api/buscar_asociados.php?q=' + encodeURIComponent(q));
    const json = await res.json();
    const items = (json && json.items) ? json.items : [];
    if (!items.length) { cont.innerHTML = '<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    const frag = document.createDocumentFragment();
    items.forEach(a => {
      const el = document.createElement('a');
      el.href = '#';
      el.className = 'list-group-item list-group-item-action';
      el.textContent = `${a.cedula} — ${a.nombre}`;
      el.addEventListener('click', (ev) => {
        ev.preventDefault();
        seleccionarAsociado(a.cedula, a.nombre);
        cont.innerHTML = '';
      });
      frag.appendChild(el);
    });
    cont.innerHTML = '';
    cont.appendChild(frag);
  } catch (e) { cont.innerHTML = '<div class="list-group-item text-danger">Error de búsqueda</div>'; }
}

document.getElementById('metodoVenta').addEventListener('change', actualizarEstadoConfirmarVenta);
document.getElementById('btnConfirmarVenta').addEventListener('click', () => {
  if (!window.asociadoSeleccionado) { alert('Seleccione un asociado'); return; }
  confirmarVenta(window.asociadoSeleccionado, '');
});

function seleccionarAsociado(cedula, nombre) {
  window.asociadoSeleccionado = cedula;
  document.getElementById('buscarAsociado').value = `${cedula} — ${nombre}`;
  actualizarEstadoConfirmarVenta();
}

function actualizarEstadoConfirmarVenta() {
  const btn = document.getElementById('btnConfirmarVenta');
  const metodo = (document.getElementById('metodoVenta').value || '').trim();
  btn.disabled = !(window.asociadoSeleccionado && metodo);
}

async function confirmarVenta(cedula, nombre) {
  const id = boletaAOperar;
  if (!id) return;
  const metodoVenta = (document.getElementById('metodoVenta').value || '').trim();
  const permitidos = ['credito','regalo_cooperativa'];
  if (!metodoVenta) { alert('Seleccione método de venta'); return; }
  if (!permitidos.includes(metodoVenta)) { alert('Método de venta inválido'); return; }
  try {
    const res = await fetch('../../boleteria/api/boletas_vender.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, cedula, metodo_venta: metodoVenta }) });
    const json = await res.json();
    if (json && json.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalVender')).hide();
      mostrarToast('Boleta vendida a ' + cedula);
      cargarBoletas();
    } else {
      alert(json.message || 'No se pudo vender');
    }
  } catch (e) { alert('Error: ' + e); }
}

async function anularBoleta(id) {
  if (!confirm('¿Seguro que deseas anular esta boleta?')) return;
  try {
    const res = await fetch('../../boleteria/api/boletas_anular.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    const json = await res.json();
    if (json && json.success) { mostrarToast('Boleta anulada'); cargarBoletas(); } else { alert(json.message || 'No se pudo anular'); }
  } catch (e) { alert('Error: ' + e); }
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
}

function abrirContabilizar(id) {
  boletaAOperar = id;
  document.getElementById('comprobanteContabilizacion').value = '';
  new bootstrap.Modal(document.getElementById('modalContabilizar')).show();
}

async function confirmarContabilizacion() {
  const comprobante = document.getElementById('comprobanteContabilizacion').value.trim();
  if (!comprobante) {
    alert('Comprobante requerido');
    return;
  }

  try {
    const res = await fetch('../../boleteria/api/boletas_contabilizar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: boletaAOperar, comprobante })
    });
    const json = await res.json();
    if (json && json.success) {
      mostrarToast('Boleta contabilizada exitosamente');
      bootstrap.Modal.getInstance(document.getElementById('modalContabilizar')).hide();
      cargarBoletas();
    } else {
      alert(json.message || 'No se pudo contabilizar');
    }
  } catch (e) {
    alert('Error: ' + e);
  }
}

function abrirDetalleBoleta(id) {
  boletaAOperar = id;
  const modal = new bootstrap.Modal(document.getElementById('modalDetalleBoleta'));
  modal.show();
  cargarDetalleBoleta(id);
}

async function cargarDetalleBoleta(id) {
  try {
    const res = await fetch(`../../boleteria/api/boletas_eventos.php?id=${id}`);
    const json = await res.json();
    const contenido = document.getElementById('detalleBoletaContenido');
    
    if (json.success && json.eventos) {
      let html = '<div class="timeline">';
      json.eventos.forEach(evento => {
        const fecha = new Date(evento.fecha).toLocaleString('es-CO');
        const accion = evento.accion.charAt(0).toUpperCase() + evento.accion.slice(1);
        html += `
          <div class="timeline-item mb-3">
            <div class="d-flex align-items-start">
              <div class="timeline-marker me-3">
                <i class="fas fa-circle text-primary"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                  <h6 class="mb-1">${accion}</h6>
                  <small class="text-muted">${fecha}</small>
                </div>
                <div class="small text-muted mb-1">Por: ${evento.usuario_nombre || 'N/A'}</div>
                ${evento.detalle ? `<p class="mb-0 small">${escapeHtml(evento.detalle)}</p>` : ''}
                ${evento.estado_anterior && evento.estado_nuevo ? 
                  `<div class="mt-2"><span class="badge bg-secondary">${evento.estado_anterior}</span> → <span class="badge bg-primary">${evento.estado_nuevo}</span></div>` : ''}
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      contenido.innerHTML = html;
    } else {
      contenido.innerHTML = '<div class="text-muted">No hay eventos disponibles para esta boleta.</div>';
    }
  } catch (e) {
    document.getElementById('detalleBoletaContenido').innerHTML = '<div class="text-danger">Error al cargar el detalle.</div>';
  }
}

function mostrarToast(mensaje) {
  let cont = document.getElementById('toastContainer');
  if (!cont) {
    cont = document.createElement('div');
    cont.id = 'toastContainer';
    cont.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(cont);
  }
  const el = document.createElement('div');
  el.className = 'toast align-items-center text-bg-success border-0';
  el.role = 'alert';
  el.ariaLive = 'assertive';
  el.ariaAtomic = 'true';
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${escapeHtml(mensaje)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  cont.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 3000 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

function limpiarFiltrosBoletas() {
  const selCat = document.getElementById('filtroCategoria'); if (selCat) selCat.value = '';
  const selEst = document.getElementById('filtroEstado'); if (selEst) selEst.value = '';
  const inpSer = document.getElementById('filtroSerial'); if (inpSer) inpSer.value = '';
  const inpCed = document.getElementById('filtroCedula'); if (inpCed) inpCed.value = '';
  const fcD = document.getElementById('fcDesde'); if (fcD) fcD.value = '';
  const fcH = document.getElementById('fcHasta'); if (fcH) fcH.value = '';
  const fvD = document.getElementById('fvDesde'); if (fvD) fvD.value = '';
  const fvH = document.getElementById('fvHasta'); if (fvH) fvH.value = '';
  const fvenD = document.getElementById('fvenDesde'); if (fvenD) fvenD.value = '';
  const fvenH = document.getElementById('fvenHasta'); if (fvenH) fvenH.value = '';
  bolPage = 1; bolSortBy = 'id'; bolSortDir = 'DESC';
  cargarBoletas();
}

async function debugBoletas() {
  console.log('=== DEBUG BOLETAS ===');
  console.log('Filtros actuales:');
  console.log('- Categoría:', document.getElementById('filtroCategoria').value);
  console.log('- Estado:', document.getElementById('filtroEstado').value);
  console.log('- Serial:', document.getElementById('filtroSerial').value);
  console.log('- Cédula:', document.getElementById('filtroCedula').value);
  console.log('- F. creación desde:', document.getElementById('fcDesde').value);
  console.log('- F. creación hasta:', document.getElementById('fcHasta').value);
  console.log('- F. vendida desde:', document.getElementById('fvDesde').value);
  console.log('- F. vendida hasta:', document.getElementById('fvHasta').value);
  console.log('- F. vencimiento desde:', document.getElementById('fvenDesde').value);
  console.log('- F. vencimiento hasta:', document.getElementById('fvenHasta').value);
  console.log('- Página:', bolPage);
  console.log('- Orden:', bolSortBy, bolSortDir);
  
  // Hacer una consulta sin filtros para ver todas las boletas
  try {
    const res = await fetch('../../boleteria/api/boletas_listar.php?limit=1000');
    const json = await res.json();
    console.log('Todas las boletas (sin filtros):', json);
    
    if (json.success && json.data && json.data.items) {
      console.log('Total de boletas en BD:', json.data.total);
      console.log('Estados disponibles:', [...new Set(json.data.items.map(b => b.estado))]);
      console.log('Primeras 5 boletas:', json.data.items.slice(0, 5));
    }
  } catch (e) {
    console.error('Error en debug:', e);
  }
}
</script>

<?php /* EOF */ ?>


