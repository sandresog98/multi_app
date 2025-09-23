<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('tienda.catalogo');
$currentUser = $auth->getCurrentUser();

$pageTitle = 'Tienda - Catálogo';
$currentPage = 'tienda_catalogo';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tags me-2"></i>Catálogo</h1>
      </div>

      <ul class="nav nav-tabs" id="catTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProductos" type="button" role="tab">Productos</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabMarcas" type="button" role="tab">Marcas</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCategorias" type="button" role="tab">Categorías</button></li>
      </ul>
      <div class="tab-content pt-3">
        <div class="tab-pane fade" id="tabCategorias" role="tabpanel">
          <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary" onclick="nuevoCategoria()"><i class="fas fa-plus me-1"></i>Nueva categoría</button>
          </div>
          <div class="card"><div class="card-body">
            <div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Nombre</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblCategorias"></tbody></table></div>
          </div></div>
        </div>

        <div class="tab-pane fade" id="tabMarcas" role="tabpanel">
          <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary" onclick="nuevoMarca()"><i class="fas fa-plus me-1"></i>Nueva marca</button>
          </div>
          <div class="card"><div class="card-body">
            <div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Nombre</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblMarcas"></tbody></table></div>
          </div></div>
        </div>

        <div class="tab-pane fade show active" id="tabProductos" role="tabpanel">
          <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary" onclick="nuevoProducto()"><i class="fas fa-plus me-1"></i>Nuevo producto</button>
          </div>
          <div class="card mb-3"><div class="card-header"><strong>Filtros</strong></div><div class="card-body">
            <form class="row g-2" onsubmit="return false;">
              <div class="col-md-4"><select id="f_cat" class="form-select"><option value="">Todas las categorías</option></select></div>
              <div class="col-md-4"><select id="f_marca" class="form-select"><option value="">Todas las marcas</option></select></div>
              <div class="col-md-4"><input id="f_nombre" class="form-control" placeholder="Nombre del producto"></div>
              <div class="w-100"></div>
              <div class="col-md-2"><input id="f_pmin" type="number" step="0.01" min="0" class="form-control" placeholder="$ Min"></div>
              <div class="col-md-2"><input id="f_pmax" type="number" step="0.01" min="0" class="form-control" placeholder="$ Max"></div>
              <div class="col-md-2 d-grid"><button id="btnPFiltro" type="button" class="btn btn-outline-primary"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
              <div class="col-md-2 d-grid"><button id="btnPLimpiar" type="button" class="btn btn-outline-secondary"><i class="fas fa-eraser me-1"></i>Limpiar</button></div>
            </form>
          </div></div>
          <div class="card"><div class="card-body">
            <div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Categoría</th><th>Marca</th><th>Nombre</th><th>Foto</th><th>Precios</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblProductos"></tbody></table></div>
          </div></div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Modales -->
<div class="modal fade" id="mCategoria" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Categoría</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form class="row g-2" onsubmit="return false;">
      <input type="hidden" id="cat_id">
      <div class="col-12"><input class="form-control" id="cat_nombre" placeholder="Nombre de la categoría"></div>
      <div class="col-12"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="cat_estado" checked><label class="form-check-label" for="cat_estado">Activa</label></div></div>
    </form>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" onclick="guardarCategoria()">Guardar</button></div>
</div></div></div>

<div class="modal fade" id="mMarca" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Marca</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form class="row g-2" onsubmit="return false;">
      <input type="hidden" id="marca_id">
      <div class="col-12"><input class="form-control" id="marca_nombre" placeholder="Nombre de la marca"></div>
      <div class="col-12"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="marca_estado" checked><label class="form-check-label" for="marca_estado">Activa</label></div></div>
    </form>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" onclick="guardarMarca()">Guardar</button></div>
</div></div></div>

<div class="modal fade" id="mProducto" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Producto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form class="row g-2" onsubmit="return false;" enctype="multipart/form-data">
      <input type="hidden" id="prod_id">
      <div class="col-md-6"><select id="prod_categoria" class="form-select"><option value="">Categoría</option></select></div>
      <div class="col-md-6"><select id="prod_marca" class="form-select"><option value="">Marca</option></select></div>
      <div class="col-md-12"><input class="form-control" id="prod_nombre" placeholder="Nombre del producto"></div>
      <div class="col-md-6"><input type="file" class="form-control" id="prod_foto" accept=".png,.jpg,.jpeg"></div>
      <div class="col-12"><textarea class="form-control" id="prod_desc" placeholder="Descripción del producto" rows="5"></textarea></div>
      <div class="col-md-6"><input type="number" step="0.01" min="0" class="form-control" id="prod_precio_compra" placeholder="Precio compra aprox"></div>
      <div class="col-md-6"><input type="number" step="0.01" min="0" class="form-control" id="prod_precio_venta" placeholder="Precio venta aprox"></div>
      <div class="col-12"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="prod_estado" checked><label class="form-check-label" for="prod_estado">Activo</label></div></div>
    </form>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" onclick="guardarProducto()">Guardar</button></div>
</div></div></div>

<script>
const apiCatalogo = '<?php echo getBaseUrl(); ?>modules/tienda/api/catalogo.php';
let productosCache = [];
let categoriasCache = [];
let marcasCache = [];
async function loadAll(){
  try {
    const res = await fetch(apiCatalogo + '?action=listar');
    const text = await res.text();
    let j; try { j = JSON.parse(text); } catch(e){ alert('Respuesta inválida al listar: '+ text.slice(0,200)); return; }
    if (!res.ok || !j.success) { alert(j.message||('Error HTTP '+res.status)); return; }
    productosCache = j.productos||[];
    categoriasCache = j.categorias||[];
    marcasCache = j.marcas||[];
    renderCategorias(categoriasCache);
    renderMarcas(marcasCache);
    renderProductos(applyProductFilters());
    fillSelects(categoriasCache, marcasCache);
  } catch(e){ alert('Error de red al listar: '+e); }
}
function renderCategorias(list){
  const body = document.getElementById('tblCategorias'); body.innerHTML='';
  if(!list.length){ body.innerHTML='<tr><td colspan="3" class="text-muted">Sin categorías</td></tr>'; return; }
  list.forEach(it=>{
    const tr=document.createElement('tr');
    tr.innerHTML = `<td>${escapeHtml(it.nombre||'')}</td>
                    <td><span class="badge ${it.estado_activo? 'bg-success':'bg-secondary'}">${it.estado_activo? 'Activa':'Inactiva'}</span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-info" onclick='editCategoria(${JSON.stringify(it)})'><i class="fas fa-edit"></i></button>
                      <button class="btn btn-sm btn-outline-danger" onclick='delCategoria(${Number(it.id)})'><i class="fas fa-trash"></i></button>
                    </td>`;
    body.appendChild(tr);
  });
}
function renderMarcas(list){
  const body = document.getElementById('tblMarcas'); body.innerHTML='';
  if(!list.length){ body.innerHTML='<tr><td colspan="3" class="text-muted">Sin marcas</td></tr>'; return; }
  list.forEach(it=>{
    const tr=document.createElement('tr');
    tr.innerHTML = `<td>${escapeHtml(it.nombre||'')}</td>
                    <td><span class="badge ${it.estado_activo? 'bg-success':'bg-secondary'}">${it.estado_activo? 'Activa':'Inactiva'}</span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-info" onclick='editMarca(${JSON.stringify(it)})'><i class="fas fa-edit"></i></button>
                      <button class="btn btn-sm btn-outline-danger" onclick='delMarca(${Number(it.id)})'><i class="fas fa-trash"></i></button>
                    </td>`;
    body.appendChild(tr);
  });
}
function renderProductos(list){
  const body = document.getElementById('tblProductos'); body.innerHTML='';
  if(!list.length){ body.innerHTML='<tr><td colspan="7" class="text-muted">Sin productos</td></tr>'; return; }
  list.forEach(it=>{
    const tr=document.createElement('tr');
    const foto = it.foto_url ? `<a href="${escapeHtml(it.foto_url)}" target="_blank"><img src="${escapeHtml(it.foto_url)}" alt="foto" style="height:40px"></a>` : '';
    tr.innerHTML = `<td>${escapeHtml(it.categoria||'')}</td>
                    <td>${escapeHtml(it.marca||'')}</td>
                    <td>${escapeHtml(it.nombre||'')}</td>
                    <td>${foto}</td>
                    <td><small>Compra: $${Number(it.precio_compra_aprox||0).toLocaleString()}<br>Venta: $${Number(it.precio_venta_aprox||0).toLocaleString()}</small></td>
                    <td><span class="badge ${it.estado_activo? 'bg-success':'bg-secondary'}">${it.estado_activo? 'Activo':'Inactivo'}</span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-info" onclick="editProductoById(${Number(it.id)})"><i class=\"fas fa-edit\"></i></button>
                      <button class="btn btn-sm btn-outline-danger" onclick='delProducto(${Number(it.id)})'><i class="fas fa-trash"></i></button>
                    </td>`;
    body.appendChild(tr);
  });
}
function editProductoById(id){
  const it = (productosCache||[]).find(p=>Number(p.id)===Number(id));
  if (!it){ alert('Producto no encontrado'); return; }
  editProducto(it);
}
function fillSelects(cats, marcas){
  const sc = document.getElementById('prod_categoria'); sc.innerHTML = '<option value="">Categoría</option>' + cats.map(c=>`<option value="${c.id}">${escapeHtml(c.nombre)}</option>`).join('');
  const sm = document.getElementById('prod_marca'); sm.innerHTML = '<option value="">Marca</option>' + marcas.map(m=>`<option value="${m.id}">${escapeHtml(m.nombre)}</option>`).join('');
  // Filtros
  const fsc = document.getElementById('f_cat'); if (fsc) fsc.innerHTML = '<option value="">Todas las categorías</option>' + cats.map(c=>`<option value="${c.id}">${escapeHtml(c.nombre)}</option>`).join('');
  const fsm = document.getElementById('f_marca'); if (fsm) fsm.innerHTML = '<option value="">Todas las marcas</option>' + marcas.map(m=>`<option value="${m.id}">${escapeHtml(m.nombre)}</option>`).join('');
}

function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

// CRUD categorías
async function guardarCategoria(){
  const id = document.getElementById('cat_id').value||'';
  const nombre = document.getElementById('cat_nombre').value.trim();
  const estado = document.getElementById('cat_estado').checked?1:0;
  if (!nombre){ alert('Nombre requerido'); return; }
  try {
    const fd = new FormData(); fd.append('action','guardar_categoria'); fd.append('id',id); fd.append('nombre',nombre); fd.append('estado',estado);
    const res = await fetch(apiCatalogo, { method:'POST', body: fd });
    const text = await res.text(); let j; try { j = JSON.parse(text); } catch(e){ alert('Respuesta inválida: '+ text.slice(0,200)); return; }
    if (!res.ok || !j.success){ alert(j.message||('Error HTTP '+res.status)); return; }
    limpiarCategoria();
    const el = document.getElementById('mCategoria'); const modal = bootstrap.Modal.getInstance(el); if (modal) modal.hide();
    loadAll();
  } catch(e){ alert('Error de red: '+e); }
}
function nuevoCategoria(){ limpiarCategoria(); const m=new bootstrap.Modal(document.getElementById('mCategoria')); m.show(); }
function editCategoria(it){ document.getElementById('cat_id').value=it.id; document.getElementById('cat_nombre').value=it.nombre||''; document.getElementById('cat_estado').checked=!!it.estado_activo; const m=new bootstrap.Modal(document.getElementById('mCategoria')); m.show(); }
async function delCategoria(id){ if(!confirm('¿Eliminar categoría?')) return; const fd=new FormData(); fd.append('action','eliminar_categoria'); fd.append('id',id); const res=await fetch(apiCatalogo,{method:'POST',body:fd}); const j=await res.json(); if(!j.success){alert(j.message||'Error');return;} loadAll(); }
function limpiarCategoria(){ document.getElementById('cat_id').value=''; document.getElementById('cat_nombre').value=''; document.getElementById('cat_estado').checked=true; }

// CRUD marcas
async function guardarMarca(){
  const id = document.getElementById('marca_id').value||'';
  const nombre = document.getElementById('marca_nombre').value.trim();
  const estado = document.getElementById('marca_estado').checked?1:0;
  if (!nombre){ alert('Nombre requerido'); return; }
  try {
    const fd = new FormData(); fd.append('action','guardar_marca'); fd.append('id',id); fd.append('nombre',nombre); fd.append('estado',estado);
    const res = await fetch(apiCatalogo, { method:'POST', body: fd });
    const text = await res.text(); let j; try { j = JSON.parse(text); } catch(e){ alert('Respuesta inválida: '+ text.slice(0,200)); return; }
    if (!res.ok || !j.success){ alert(j.message||('Error HTTP '+res.status)); return; }
    limpiarMarca();
    const el = document.getElementById('mMarca'); const modal = bootstrap.Modal.getInstance(el); if (modal) modal.hide();
    loadAll();
  } catch(e){ alert('Error de red: '+e); }
}
function nuevoMarca(){ limpiarMarca(); const m=new bootstrap.Modal(document.getElementById('mMarca')); m.show(); }
function editMarca(it){ document.getElementById('marca_id').value=it.id; document.getElementById('marca_nombre').value=it.nombre||''; document.getElementById('marca_estado').checked=!!it.estado_activo; const m=new bootstrap.Modal(document.getElementById('mMarca')); m.show(); }
async function delMarca(id){ if(!confirm('¿Eliminar marca?')) return; const fd=new FormData(); fd.append('action','eliminar_marca'); fd.append('id',id); const res=await fetch(apiCatalogo,{method:'POST',body:fd}); const j=await res.json(); if(!j.success){alert(j.message||'Error');return;} loadAll(); }
function limpiarMarca(){ document.getElementById('marca_id').value=''; document.getElementById('marca_nombre').value=''; document.getElementById('marca_estado').checked=true; }

// CRUD productos
async function guardarProducto(){
  const id = document.getElementById('prod_id').value||'';
  const categoria = document.getElementById('prod_categoria').value;
  const marca = document.getElementById('prod_marca').value;
  const nombre = document.getElementById('prod_nombre').value.trim();
  const foto = document.getElementById('prod_foto').files[0]||null;
  const desc = document.getElementById('prod_desc').value.trim();
  const pc = document.getElementById('prod_precio_compra').value;
  const pv = document.getElementById('prod_precio_venta').value;
  const estado = document.getElementById('prod_estado').checked?1:0;
  if (!categoria||!marca||!nombre){ alert('Categoría, marca y nombre son requeridos'); return; }
  if (foto){
    if (foto.size > 2*1024*1024){ alert('Máximo 2MB'); return; }
    if (!/\.(png|jpg|jpeg)$/i.test(foto.name)){ alert('Formato inválido (PNG/JPG/JPEG)'); return; }
  }
  try {
    const fd = new FormData();
    fd.append('action','guardar_producto'); fd.append('id',id); fd.append('categoria_id',categoria); fd.append('marca_id',marca);
    fd.append('nombre',nombre); fd.append('descripcion',desc); fd.append('precio_compra_aprox',pc); fd.append('precio_venta_aprox',pv); fd.append('estado',estado);
    if (foto) fd.append('foto', foto);
    const res = await fetch(apiCatalogo, { method:'POST', body: fd });
    const text = await res.text(); let j; try { j = JSON.parse(text); } catch(e){ alert('Respuesta inválida: '+ text.slice(0,200)); return; }
    if (!res.ok || !j.success){ alert(j.message||('Error HTTP '+res.status)); return; }
    limpiarProducto();
    const el = document.getElementById('mProducto'); const modal = bootstrap.Modal.getInstance(el); if (modal) modal.hide();
    loadAll();
  } catch(e){ alert('Error de red: '+e); }
}
function nuevoProducto(){ limpiarProducto(); const m=new bootstrap.Modal(document.getElementById('mProducto')); m.show(); }
function editProducto(it){
  document.getElementById('prod_id').value = it.id; document.getElementById('prod_categoria').value = it.categoria_id; document.getElementById('prod_marca').value = it.marca_id;
  document.getElementById('prod_nombre').value = it.nombre||''; document.getElementById('prod_desc').value = it.descripcion||'';
  document.getElementById('prod_precio_compra').value = it.precio_compra_aprox||''; document.getElementById('prod_precio_venta').value = it.precio_venta_aprox||'';
  document.getElementById('prod_estado').checked = !!it.estado_activo; const m=new bootstrap.Modal(document.getElementById('mProducto')); m.show();
}
async function delProducto(id){ if(!confirm('¿Eliminar producto?')) return; const fd=new FormData(); fd.append('action','eliminar_producto'); fd.append('id',id); const res=await fetch(apiCatalogo,{method:'POST',body:fd}); const j=await res.json(); if(!j.success){alert(j.message||'Error');return;} loadAll(); }
function limpiarProducto(){ document.getElementById('prod_id').value=''; document.getElementById('prod_categoria').value=''; document.getElementById('prod_marca').value=''; document.getElementById('prod_nombre').value=''; document.getElementById('prod_desc').value=''; document.getElementById('prod_precio_compra').value=''; document.getElementById('prod_precio_venta').value=''; document.getElementById('prod_estado').checked=true; document.getElementById('prod_foto').value=''; }

document.addEventListener('DOMContentLoaded', loadAll);
// Filtros productos
document.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('btnPFiltro')?.addEventListener('click', ()=>{ renderProductos(applyProductFilters()); });
  document.getElementById('btnPLimpiar')?.addEventListener('click', ()=>{ document.getElementById('f_cat').value=''; document.getElementById('f_marca').value=''; document.getElementById('f_nombre').value=''; document.getElementById('f_pmin').value=''; document.getElementById('f_pmax').value=''; renderProductos(productosCache.slice()); });
});
function applyProductFilters(){
  let list = productosCache.slice();
  const cat = document.getElementById('f_cat')?.value||'';
  const mar = document.getElementById('f_marca')?.value||'';
  const nom = (document.getElementById('f_nombre')?.value||'').trim().toLowerCase();
  const pmin = parseFloat(document.getElementById('f_pmin')?.value||'');
  const pmax = parseFloat(document.getElementById('f_pmax')?.value||'');
  if (cat) list = list.filter(it=> String(it.categoria_id)===String(cat));
  if (mar) list = list.filter(it=> String(it.marca_id)===String(mar));
  if (nom) list = list.filter(it=> String(it.nombre||'').toLowerCase().includes(nom));
  if (!isNaN(pmin)) list = list.filter(it=> Number(it.precio_venta_aprox||0) >= pmin);
  if (!isNaN(pmax)) list = list.filter(it=> Number(it.precio_venta_aprox||0) <= pmax);
  return list;
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>


