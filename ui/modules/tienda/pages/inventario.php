<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('tienda.inventario');
$currentUser = $auth->getCurrentUser();
$pdo = getConnection();

// Obtener categorías y marcas para los filtros
$cats = $pdo->query("SELECT id, nombre FROM tienda_categoria WHERE estado_activo = TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$marcasList = $pdo->query("SELECT id, nombre FROM tienda_marca WHERE estado_activo = TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$pageTitle = 'Tienda - Inventario';
$currentPage = 'tienda_inventario';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-warehouse me-2"></i>Inventario</h1>
      </div>

      <div class="card mb-3"><div class="card-body">
        <form class="row g-2" onsubmit="return false;">
          <div class="col-md-3"><select id="f_cat" class="form-select"><option value="">Todas las categorías</option><?php foreach($cats as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3"><select id="f_marca" class="form-select"><option value="">Todas las marcas</option><?php foreach($marcasList as $m): ?><option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><input id="f_nombre" class="form-control" placeholder="Nombre de producto"></div>
          <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-primary" onclick="filtrarInv()"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
          <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-secondary" onclick="limpiarInv()"><i class="fas fa-eraser me-1"></i>Limpiar</button></div>
        </form>
      </div></div>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr><th>Categoría</th><th>Producto</th><th>Foto</th><th>Ingresado</th><th>Vendido</th><th>Disponible</th><th class="text-end">Acciones</th></tr></thead>
            <tbody id="tblInventario">
              <!-- Los datos se cargarán dinámicamente -->
            </tbody>
          </table>
        </div>
        <!-- Paginación -->
        <nav aria-label="Paginación de inventario" class="mt-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
              <span id="infoPaginacionInv">Mostrando 0 de 0 productos</span>
            </div>
            <ul class="pagination pagination-sm mb-0" id="paginacionInventario">
              <!-- Los botones de paginación se generarán dinámicamente -->
            </ul>
          </div>
        </nav>
      </div></div>

    </main>
  </div>
</div>

<div class="modal fade" id="mImeis" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">IMEIs disponibles</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div id="imeisBody">Cargando...</div></div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
</div></div></div>

<script>
const apiInventario = '../api/inventario_paginado.php';
let paginacionActualInv = {
  pagina: 1,
  total: 0,
  total_paginas: 0,
  por_pagina: 20
};

async function loadInventarioPaginado(pagina = 1) {
  try {
    const filtros = getFiltrosInventarioActuales();
    const params = new URLSearchParams({
      pagina: pagina,
      por_pagina: 20,
      ...filtros
    });
    
    const res = await fetch(apiInventario + '?' + params);
    const text = await res.text();
    let j; try { j = JSON.parse(text); } catch(e){ alert('Respuesta inválida al cargar inventario: '+ text.slice(0,200)); return; }
    if (!res.ok || !j.success) { alert(j.message||('Error HTTP '+res.status)); return; }
    
    paginacionActualInv = j.paginacion||{};
    renderInv(j.items||[]);
    renderPaginacionInv();
  } catch(e){ alert('Error de red al cargar inventario: '+e); }
}

function getFiltrosInventarioActuales() {
  const filtros = {};
  const nom = (document.getElementById('f_nombre').value||'').trim();
  const cat = document.getElementById('f_cat').value||'';
  const mar = document.getElementById('f_marca').value||'';
  
  if (nom) filtros.nombre = nom;
  if (cat) filtros.categoria_id = cat;
  if (mar) filtros.marca_id = mar;
  
  return filtros;
}

function renderInv(list){
  const tbody = document.getElementById('tblInventario');
  tbody.innerHTML = '';
  if (!list || !list.length){ tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Sin resultados</td></tr>'; return; }
  list.forEach(r=>{
    const tr = document.createElement('tr');
    const foto = r.foto_url ? `<a href="${escapeHtml(r.foto_url)}" target="_blank"><img src="${escapeHtml(r.foto_url)}" alt="foto" style="height:40px"></a>` : '';
    tr.innerHTML = `<td>${escapeHtml(r.categoria||'')}</td><td>${escapeHtml(r.nombre||'')}</td><td>${foto}</td><td>${Number(r.ingresado||0)}</td><td>${Number(r.vendido||0)}</td><td>${Number(r.disponible||0)}</td><td class="text-end"><button class="btn btn-sm btn-outline-info me-1" onclick="verDetalleProd(${r.id}, '${r.nombre?String(r.nombre).replace(/['"\\]/g,''):''}')"><i class="fas fa-eye"></i> Detalle</button> <button class="btn btn-sm btn-outline-primary" onclick="verImeis(${r.id})"><i class="fas fa-list"></i> IMEIs</button></td>`;
    tbody.appendChild(tr);
  });
}

function renderPaginacionInv() {
  const info = document.getElementById('infoPaginacionInv');
  const paginacion = document.getElementById('paginacionInventario');
  
  if (!info || !paginacion) return;
  
  const inicio = ((paginacionActualInv.pagina - 1) * paginacionActualInv.por_pagina) + 1;
  const fin = Math.min(paginacionActualInv.pagina * paginacionActualInv.por_pagina, paginacionActualInv.total);
  
  info.textContent = `Mostrando ${inicio}-${fin} de ${paginacionActualInv.total} productos`;
  
  paginacion.innerHTML = '';
  
  if (paginacionActualInv.total_paginas <= 1) return;
  
  // Botón anterior
  const liPrev = document.createElement('li');
  liPrev.className = `page-item ${paginacionActualInv.pagina <= 1 ? 'disabled' : ''}`;
  liPrev.innerHTML = `<a class="page-link" href="#" onclick="cambiarPaginaInv(${paginacionActualInv.pagina - 1})">Anterior</a>`;
  paginacion.appendChild(liPrev);
  
  // Botones de páginas
  const inicioPagina = Math.max(1, paginacionActualInv.pagina - 2);
  const finPagina = Math.min(paginacionActualInv.total_paginas, paginacionActualInv.pagina + 2);
  
  for (let i = inicioPagina; i <= finPagina; i++) {
    const li = document.createElement('li');
    li.className = `page-item ${i === paginacionActualInv.pagina ? 'active' : ''}`;
    li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPaginaInv(${i})">${i}</a>`;
    paginacion.appendChild(li);
  }
  
  // Botón siguiente
  const liNext = document.createElement('li');
  liNext.className = `page-item ${paginacionActualInv.pagina >= paginacionActualInv.total_paginas ? 'disabled' : ''}`;
  liNext.innerHTML = `<a class="page-link" href="#" onclick="cambiarPaginaInv(${paginacionActualInv.pagina + 1})">Siguiente</a>`;
  paginacion.appendChild(liNext);
}

async function cambiarPaginaInv(pagina) {
  if (pagina < 1 || pagina > paginacionActualInv.total_paginas) return;
  await loadInventarioPaginado(pagina);
}

function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

async function filtrarInv(){
  await loadInventarioPaginado(1);
}

async function limpiarInv(){ 
  document.getElementById('f_nombre').value=''; 
  document.getElementById('f_cat').value=''; 
  document.getElementById('f_marca').value=''; 
  await loadInventarioPaginado(1); 
}

// Cargar inventario al iniciar la página
document.addEventListener('DOMContentLoaded', () => {
  loadInventarioPaginado(1);
});
async function verImeis(productoId){
  try{
    const res = await fetch('../api/inventario_imeis.php?producto_id='+productoId);
    const j = await res.json();
    const body = document.getElementById('imeisBody');
    if (!j.success){ body.textContent = j.message||'Error'; }
    else {
      const list = j.items||[];
      if (!list.length){ body.innerHTML = '<div class="text-muted">Sin IMEIs disponibles para este producto</div>'; }
      else { body.innerHTML = '<ul class="list-group">'+list.map(x=>'<li class="list-group-item">'+x.imei+'</li>').join('')+'</ul>'; }
    }
  }catch(e){ document.getElementById('imeisBody').textContent = 'Error: '+e; }
  const m = new bootstrap.Modal(document.getElementById('mImeis')); m.show();
}

async function verDetalleProd(productoId, nombre){
  try{
    const res = await fetch('../api/inventario_detalle_producto.php?producto_id='+productoId);
    const j = await res.json();
    if(!j.success){ alert(j.message||'Error'); return; }
    const lotes = j.lotes||[]; const imeis = j.imeis||[];
    const tblLotes = lotes.length ? ('<div class="mb-3"><h6>Lotes (sin IMEI)</h6><div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr><th>Compra</th><th>Cant</th><th>Disp.</th><th>P. compra</th><th>P. vta sug.</th></tr></thead><tbody>'+lotes.map(l=>`<tr><td>#${l.compra_id}</td><td>${l.cantidad||0}</td><td>${l.disponible||0}</td><td>$${Number(l.precio_compra||0).toLocaleString()}</td><td>$${Number(l.precio_venta_sugerido||0).toLocaleString()}</td></tr>`).join('')+'</tbody></table></div></div>') : '<div class="text-muted mb-2">Sin lotes disponibles</div>';
    const tblImeis = imeis.length ? ('<div class="mb-2"><h6>IMEIs disponibles</h6><div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr><th>IMEI</th><th>P. compra</th><th>P. vta sug.</th><th>Compra</th></tr></thead><tbody>'+imeis.map(i=>`<tr><td>${i.imei}</td><td>$${Number(i.precio_compra||0).toLocaleString()}</td><td>$${Number(i.precio_venta_sugerido||0).toLocaleString()}</td><td>#${i.compra_id}</td></tr>`).join('')+'</tbody></table></div></div>') : '';
    const revs = (j.reversiones||[]).length ? ('<div class="mb-2"><h6>Reversiones</h6><div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr><th>ID</th><th>Venta</th><th>Dispositivo</th><th>P. compra</th><th>P. vta sug.</th><th>Fecha</th></tr></thead><tbody>'+ (j.reversiones||[]).map(r=>`<tr><td>${r.id}</td><td>#${r.venta_id} · Detalle ${r.venta_detalle_id}</td><td>${r.imei?('IMEI '+escapeHtml(r.imei)):'-'}</td><td>${typeof r.precio_compra!=='undefined' && r.precio_compra!==null ? ('$'+Number(r.precio_compra||0).toLocaleString()) : '-'}</td><td>${typeof r.precio_venta_sugerido!=='undefined' && r.precio_venta_sugerido!==null ? ('$'+Number(r.precio_venta_sugerido||0).toLocaleString()) : '-'}</td><td>${escapeHtml(r.fecha_creacion||'')}</td></tr>`).join('') +'</tbody></table></div></div>') : '';
    const html = `<div class=\"modal fade\" id=\"mDetProd\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Inventario · ${nombre}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\">${tblLotes}${tblImeis}${revs}</div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cerrar</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('mDetProd'); const modal = new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal',()=>el.remove());
  }catch(e){ alert('Error: '+e); }
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>


