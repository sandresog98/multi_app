<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('tienda.reversiones');
$currentUser = $auth->getCurrentUser();
$pdo = getConnection();

$pageTitle = 'Tienda - Reversiones';
$currentPage = 'tienda_reversiones';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-undo-alt me-2"></i>Reversiones</h1>
      </div>

      <div class="card mb-3"><div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-3"><input id="f_texto" class="form-control" placeholder="Buscar (cliente/asociado)"></div>
          <div class="col-md-3"><input id="f_id" class="form-control" placeholder="ID venta"></div>
          <div class="col-md-2"><input id="f_fecha" type="date" class="form-control"></div>
          <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" onclick="cargarVentas()"><i class="fas fa-search me-1"></i>Buscar</button></div>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><strong>Ventas</strong><button class="btn btn-sm btn-outline-secondary" onclick="cargarVentas()"><i class="fas fa-sync"></i></button></div><div class="card-body">
        <div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>ID</th><th>Fecha</th><th>Cliente/Asociado</th><th>Total</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblVentas"></tbody></table></div>
      </div></div>

      <div class="card"><div class="card-header"><strong>Reversiones registradas</strong></div><div class="card-body">
        <div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>ID</th><th>Detalle venta</th><th>Motivo</th><th>Revender</th><th>Fecha</th><th class="text-end">Acciones</th></tr></thead><tbody id="tbl"></tbody></table></div>
      </div></div>
    </main>
  </div>
</div>

<script>
function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
async function loadAll(){ const res=await fetch('../api/reversiones.php?action=listar'); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; } const body=document.getElementById('tbl'); body.innerHTML=''; (j.items||[]).forEach(r=>{ const tr=document.createElement('tr'); const prod = r.producto ? `<br><small class=\"text-muted\">${escapeHtml(r.producto)}${r.imei? ' · IMEI: '+escapeHtml(r.imei):''}</small>` : (r.imei? `<br><small class=\"text-muted\">IMEI: ${escapeHtml(r.imei)}</small>` : ''); tr.innerHTML=`<td>${r.id}</td><td>#${r.venta_detalle_id}${prod}</td><td>${escapeHtml(r.motivo||'')}</td><td>${r.puede_revender? 'Sí':'No'}</td><td>${escapeHtml(r.fecha_creacion||'')}</td><td class=\"text-end\"><button class=\"btn btn-sm btn-outline-danger\" onclick=\"eliminarReversion(${r.id})\"><i class=\"fas fa-trash\"></i></button></td>`; body.appendChild(tr); }); }

async function eliminarReversion(id){ if(!confirm('¿Eliminar esta reversión?')) return; const fd=new FormData(); fd.append('action','eliminar'); fd.append('id',id); const res=await fetch('../api/reversiones.php',{method:'POST',body:fd}); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; } loadAll(); }

async function cargarVentas(){
  try{
    const fecha = document.getElementById('f_fecha')?.value || '';
    const url = '../api/ventas_listar.php?limit=10'+(fecha?('&fecha='+encodeURIComponent(fecha)):'');
    const res = await fetch(url); const j=await res.json(); const body=document.getElementById('tblVentas'); body.innerHTML=''; if(!j.success){ body.innerHTML='<tr><td colspan="5" class="text-danger">'+(j.message||'Error')+'</td></tr>'; return; }
    let list = j.items||[]; const txt=(document.getElementById('f_texto').value||'').toLowerCase().trim(); const idf=(document.getElementById('f_id').value||'').trim();
    if (txt){ list = list.filter(v => String(v.cliente_nombre||'').toLowerCase().includes(txt) || String(v.asociado_cedula||'').includes(txt)); }
    if (idf){ list = list.filter(v => String(v.id).includes(idf)); }
    if (!list.length){ body.innerHTML='<tr><td colspan="5" class="text-muted">Sin resultados</td></tr>'; return; }
    list.forEach(v=>{ const tr=document.createElement('tr'); const quien = v.tipo_cliente==='asociado'?('Asociado · '+escapeHtml(v.asociado_cedula||'')) : ('Cliente · '+escapeHtml(v.cliente_nombre||'')); tr.innerHTML = `<td>${v.id}</td><td>${escapeHtml(v.fecha_creacion||'')}</td><td>${quien}</td><td>$${Number(v.total||0).toLocaleString()}</td><td class=\"text-end\"><button class=\"btn btn-sm btn-outline-info\" onclick=\"verProductosVenta(${v.id})\"><i class=\"fas fa-eye\"></i> Productos</button></td>`; body.appendChild(tr); });
  }catch(e){ document.getElementById('tblVentas').innerHTML='<tr><td colspan="5" class="text-danger">'+e+'</td></tr>'; }
}

async function verProductosVenta(id){
  try{
    const res=await fetch('../api/ventas_detalle.php?id='+id); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; }
    const dets=j.detalles||[];
    const rows = dets.map(d=>`<tr><td>${escapeHtml(d.categoria||'')}</td><td>${escapeHtml(d.producto||'')}</td><td>${d.cantidad||0}</td><td>$${Number(d.precio_unitario||0).toLocaleString()}</td><td>${d.compra_imei_id?escapeHtml(d.imei||''):'-'}</td><td class=\"text-end\"><button class=\"btn btn-sm btn-primary\" onclick=\"abrirReversion(${d.id})\"><i class=\"fas fa-undo-alt\"></i> Revertir</button></td></tr>`).join('');
    const html = `<div class=\"modal fade\" id=\"mProdVenta\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Productos de la venta #${id}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"table-responsive\"><table class=\"table table-sm\"><thead class=\"table-light\"><tr><th>Categoría</th><th>Producto</th><th>Cant</th><th>Precio</th><th>IMEI</th><th></th></tr></thead><tbody>${rows||'<tr><td colspan=6 class=\\\"text-muted\\\">Sin items</td></tr>'}</tbody></table></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cerrar</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html); const el=document.getElementById('mProdVenta'); const modal=new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal',()=>el.remove());
  }catch(e){ alert('Error: '+e); }
}

function abrirReversion(detId){
  const html = `<div class=\"modal fade\" id=\"mRev\" tabindex=\"-1\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Registrar reversión</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"mb-2\"><label class=\"form-label\">Motivo</label><input class=\"form-control\" id=\"revMotivo\" placeholder=\"Motivo o comentario\"></div><div class=\"form-check\"><input class=\"form-check-input\" type=\"checkbox\" id=\"revPuede\"><label class=\"form-check-label\" for=\"revPuede\">Puede revender</label></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" onclick=\"confirmarReversion(${detId})\">Guardar</button></div></div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html); const el=document.getElementById('mRev'); const modal=new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal',()=>el.remove());
}

async function confirmarReversion(detId){ const motivo=(document.getElementById('revMotivo').value||'').trim(); const rev=document.getElementById('revPuede').checked?1:0; const fd=new FormData(); fd.append('action','crear'); fd.append('venta_detalle_id',detId); fd.append('motivo',motivo); fd.append('puede_revender',rev); const res=await fetch('../api/reversiones.php',{method:'POST',body:fd}); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; } const m=document.getElementById('mRev'); const modal=bootstrap.Modal.getInstance(m); if(modal) modal.hide(); loadAll(); /* cerrar lista productos */ const p=document.getElementById('mProdVenta'); const pm=bootstrap.Modal.getInstance(p); if(pm) pm.hide(); }
document.addEventListener('DOMContentLoaded', ()=>{ loadAll(); cargarVentas(); });
</script>

<?php include '../../../views/layouts/footer.php'; ?>


