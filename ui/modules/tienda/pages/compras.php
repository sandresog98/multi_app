<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('tienda.compras');
$currentUser = $auth->getCurrentUser();
$pdo = getConnection();

// Cargar catálogo básico para selects
$cats = $pdo->query("SELECT id, nombre FROM tienda_categoria WHERE estado_activo=TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$marcas = $pdo->query("SELECT id, nombre FROM tienda_marca WHERE estado_activo=TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$prods = $pdo->query("SELECT p.id, p.nombre, p.precio_compra_aprox, p.precio_venta_aprox, c.nombre AS categoria, c.id AS categoria_id FROM tienda_producto p INNER JOIN tienda_categoria c ON c.id=p.categoria_id WHERE p.estado_activo=TRUE ORDER BY c.nombre, p.nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$pageTitle = 'Tienda - Compras';
$currentPage = 'tienda_compras';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-truck-loading me-2"></i>Compras (Ingreso inventario)</h1>
      </div>

      <div class="card mb-3"><div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Producto</label>
            <select id="selProd" class="form-select">
              <option value="">Seleccione producto</option>
              <?php foreach ($prods as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>" data-cat-id="<?php echo (int)$p['categoria_id']; ?>" data-pc="<?php echo htmlspecialchars((string)($p['precio_compra_aprox'] ?? '')); ?>" data-pv="<?php echo htmlspecialchars((string)($p['precio_venta_aprox'] ?? '')); ?>"><?php echo htmlspecialchars(($p['categoria']?:'').' - '.$p['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Cantidad</label>
            <input type="number" class="form-control" id="cantidad" min="1" value="1">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Precio compra</label>
            <input type="number" class="form-control" id="precioCompra" step="0.01" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Precio venta sugerido</label>
            <input type="number" class="form-control" id="precioVentaSug" step="0.01" min="0">
          </div>
          <div class="col-12" id="imeiBox" style="display:none">
            <label class="form-label small">IMEIs (uno por línea)</label>
            <textarea id="imeis" class="form-control" rows="3" placeholder="Ingrese un IMEI por línea"></textarea>
            <div class="form-text">Requerido para categoría "Celulares". Debe ingresar exactamente la misma cantidad de IMEIs que unidades.</div>
          </div>
          <div class="col-md-3 d-grid">
            <button class="btn btn-primary" onclick="agregarItem()"><i class="fas fa-plus me-1"></i>Agregar</button>
          </div>
        </div>
      </div></div>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr><th>Producto</th><th>Cantidad</th><th>P. compra</th><th>P. venta sug.</th><th>IMEIs</th><th class="text-end">Acciones</th></tr></thead>
            <tbody id="tbodyCompra"></tbody>
          </table>
        </div>
        <div class="text-end"><button class="btn btn-success" onclick="guardarCompra()"><i class="fas fa-save me-1"></i>Guardar compra</button></div>
      </div></div>

      <div class="card mt-3"><div class="card-header d-flex justify-content-between align-items-center"><strong>Compras recientes</strong><button class="btn btn-sm btn-outline-secondary" onclick="loadCompras()"><i class="fas fa-sync"></i></button></div><div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle"><thead class="table-light"><tr><th>ID</th><th>Fecha</th><th>Items</th><th>Cant total</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblCompras"></tbody></table>
        </div>
      </div></div>
    </main>
  </div>
</div>

<script>
const categoriaCelularesNombre = 'Celulares';
const prodSelect = document.getElementById('selProd');
const imeiBox = document.getElementById('imeiBox');
const pcInput = document.getElementById('precioCompra');
const pvInput = document.getElementById('precioVentaSug');
prodSelect.addEventListener('change', ()=>{
  const opt = prodSelect.options[prodSelect.selectedIndex];
  const label = opt ? opt.textContent : '';
  const isCel = /(^|\s|-)Celulares(\s|-)/i.test(label);
  imeiBox.style.display = isCel ? '' : 'none';
  const pcAttr = parseFloat(opt?.getAttribute('data-pc') || '');
  const pvAttr = parseFloat(opt?.getAttribute('data-pv') || '');
  if (!isNaN(pcAttr)) { pcInput.value = pcAttr; }
  if (!isNaN(pvAttr)) { pvInput.value = pvAttr; }
});

function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
const items = [];
function agregarItem(){
  const prod = document.getElementById('selProd').value;
  const prodText = document.getElementById('selProd').selectedOptions[0]?.textContent || '';
  const cant = parseInt(document.getElementById('cantidad').value||'0',10);
  const pc = parseFloat(document.getElementById('precioCompra').value||'0');
  const pv = parseFloat(document.getElementById('precioVentaSug').value||'0');
  if (!prod || cant<=0 || pc<0 || pv<0){ alert('Complete los datos'); return; }
  const isCel = /(^|\s|-)Celulares(\s|-)/i.test(prodText);
  let imeis = [];
  if (isCel){
    imeis = (document.getElementById('imeis').value||'').split(/\r?\n/).map(s=>s.trim()).filter(Boolean);
    if (imeis.length !== cant){ alert('Debe ingresar exactamente '+cant+' IMEIs'); return; }
    const set = new Set(imeis);
    if (set.size !== imeis.length){ alert('IMEIs duplicados en la lista'); return; }
  }
  items.push({producto_id: Number(prod), producto_label: prodText, cantidad: cant, precio_compra: pc, precio_venta_sugerido: pv, imeis});
  renderItems(); limpiarCampos();
}
function limpiarCampos(){ document.getElementById('selProd').value=''; document.getElementById('cantidad').value='1'; document.getElementById('precioCompra').value=''; document.getElementById('precioVentaSug').value=''; document.getElementById('imeis').value=''; imeiBox.style.display='none'; }
function renderItems(){ const body=document.getElementById('tbodyCompra'); body.innerHTML=''; if(!items.length){ body.innerHTML='<tr><td colspan="6" class="text-muted">Sin items</td></tr>'; return; } items.forEach((it,idx)=>{ const tr=document.createElement('tr'); tr.innerHTML=`<td>${escapeHtml(it.producto_label)}</td><td>${it.cantidad}</td><td>$${it.precio_compra.toLocaleString()}</td><td>$${it.precio_venta_sugerido.toLocaleString()}</td><td>${it.imeis?.length? it.imeis.join('<br>') : ''}</td><td class="text-end"><button class="btn btn-sm btn-outline-danger" onclick="delItem(${idx})"><i class="fas fa-trash"></i></button></td>`; body.appendChild(tr); }); }
function delItem(i){ items.splice(i,1); renderItems(); }

async function guardarCompra(){
  if (!items.length){ alert('Agregue items'); return; }
  const res = await fetch('../api/compras_guardar.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ items }) });
  const j = await res.json(); if(!j.success){ alert(j.message||'Error'); return; }
  alert('Compra guardada'); location.reload();
}

async function loadCompras(){
  try{
    const res = await fetch('../api/compras_listar.php'); const j = await res.json();
    const body = document.getElementById('tblCompras'); body.innerHTML='';
    if (!j.success){ body.innerHTML = '<tr><td colspan="5" class="text-danger">'+(j.message||'Error')+'</td></tr>'; return; }
    const list = j.items||[];
    if (!list.length){ body.innerHTML = '<tr><td colspan="5" class="text-muted">Sin compras</td></tr>'; return; }
    list.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML = `<td>${r.id}</td><td>${escapeHtml(r.fecha_creacion||'')}</td><td>${r.items||0}</td><td>${r.total_cantidad||0}</td>
        <td class="text-end"><button class="btn btn-sm btn-outline-info me-1" onclick="verCompra(${r.id})"><i class="fas fa-eye"></i></button>${r.deletable?`<button class=\"btn btn-sm btn-outline-danger\" onclick=\"eliminarCompra(${r.id})\"><i class=\"fas fa-trash\"></i></button>`:'<span class="text-muted">Bloqueada</span>'}</td>`;
      body.appendChild(tr);
    });
  }catch(e){ document.getElementById('tblCompras').innerHTML = '<tr><td colspan="5" class="text-danger">'+e+'</td></tr>'; }
}
async function eliminarCompra(id){ if(!confirm('¿Eliminar compra #'+id+'? Esta acción no se puede deshacer.')) return; const fd=new FormData(); fd.append('id',id); const res=await fetch('../api/compras_eliminar.php',{method:'POST',body:fd}); const j=await res.json(); if(!j.success){ alert(j.message||'No se pudo eliminar'); return; } loadCompras(); alert('Compra eliminada'); }
document.addEventListener('DOMContentLoaded', loadCompras);

async function verCompra(id){
  try{
    const res = await fetch('../api/compras_detalle.php?id='+id); const j=await res.json();
    if(!j.success){ alert(j.message||'Error'); return; }
    const cab = j.cabecera||{}; const dets = j.detalles||[];
    const rows = dets.map(d=>{
      const imeis = (d.imeis||[]).join('<br>');
      const pc = Number(d.precio_compra||0).toLocaleString();
      const pv = Number(d.precio_venta_sugerido||0).toLocaleString();
      return `<tr><td>${escapeHtml(d.categoria||'')}</td><td>${escapeHtml(d.producto||'')}</td><td>${d.cantidad||0}</td><td>$${pc}</td><td>$${pv}</td><td>${imeis}</td>`;
    }).join('');
    const html = `<div class=\"modal fade\" id=\"mDetalleCompra\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Compra #${id}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"mb-2\"><small class=\"text-muted\">Fecha: ${escapeHtml(cab.fecha_creacion||'')}</small></div><div class=\"table-responsive\"><table class=\"table table-sm\"><thead class=\"table-light\"><tr><th>Categoría</th><th>Producto</th><th>Cant</th><th>P. compra</th><th>P. venta sug.</th><th>IMEIs</th></tr></thead><tbody>${rows||'<tr><td colspan=6 class=\\\"text-muted\\\">Sin detalle</td></tr>'}</tbody></table></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cerrar</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('mDetalleCompra'); const modal = new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal', ()=>el.remove());
  }catch(e){ alert('Error: '+e); }
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>


