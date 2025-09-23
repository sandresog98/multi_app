<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('tienda.inventario');
$currentUser = $auth->getCurrentUser();
$pdo = getConnection();

// Query inventario: totales por producto y detalle IMEIs disponibles
$cats = $pdo->query("SELECT id, nombre FROM tienda_categoria WHERE estado_activo = TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$marcasList = $pdo->query("SELECT id, nombre FROM tienda_marca WHERE estado_activo = TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$items = $pdo->query("SELECT p.id, p.nombre, c.id AS categoria_id, c.nombre AS categoria, m.id AS marca_id, m.nombre AS marca,
                             COALESCE(cd.ingresado,0) AS ingresado,
                             COALESCE(vd.vendido,0) AS vendido,
                             COALESCE(cd.ingresado,0) - COALESCE(vd.vendido,0) AS disponible
                      FROM tienda_producto p
                      INNER JOIN tienda_categoria c ON c.id=p.categoria_id
                      INNER JOIN tienda_marca m ON m.id=p.marca_id
                      LEFT JOIN (
                        SELECT producto_id, SUM(cantidad) AS ingresado
                        FROM tienda_compra_detalle
                        GROUP BY producto_id
                      ) cd ON cd.producto_id = p.id
                      LEFT JOIN (
                        SELECT producto_id,
                               SUM(CASE WHEN compra_imei_id IS NULL THEN cantidad ELSE 1 END) AS vendido
                        FROM tienda_venta_detalle
                        GROUP BY producto_id
                      ) vd ON vd.producto_id = p.id
                      ORDER BY c.nombre, p.nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
            <thead class="table-light"><tr><th>Categoría</th><th>Producto</th><th>Ingresado</th><th>Vendido</th><th>Disponible</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
              <?php foreach ($items as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['categoria'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['nombre'] ?? ''); ?></td>
                <td><?php echo (int)($r['ingresado'] ?? 0); ?></td>
                <td><?php echo (int)($r['vendido'] ?? 0); ?></td>
                <td><?php echo (int)($r['disponible'] ?? 0); ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-info me-1" onclick="verDetalleProd(<?php echo (int)$r['id']; ?>,'<?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?>')"><i class="fas fa-eye"></i> Detalle</button>
                  <button class="btn btn-sm btn-outline-primary" onclick="verImeis(<?php echo (int)$r['id']; ?>)"><i class="fas fa-list"></i> IMEIs</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
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
const dataInv = <?php echo json_encode($items); ?>;
function renderInv(list){
  const tbody = document.querySelector('table tbody');
  tbody.innerHTML = '';
  if (!list || !list.length){ tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>'; return; }
  list.forEach(r=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${escapeHtml(r.categoria||'')}</td><td>${escapeHtml(r.nombre||'')}</td><td>${Number(r.ingresado||0)}</td><td>${Number(r.vendido||0)}</td><td>${Number(r.disponible||0)}</td><td class="text-end"><button class="btn btn-sm btn-outline-info me-1" onclick="verDetalleProd(${r.id}, '${r.nombre?String(r.nombre).replace(/['"\\]/g,''):''}')"><i class="fas fa-eye"></i> Detalle</button> <button class="btn btn-sm btn-outline-primary" onclick="verImeis(${r.id})"><i class="fas fa-list"></i> IMEIs</button></td>`;
    tbody.appendChild(tr);
  });
}
function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
function filtrarInv(){
  const nom = (document.getElementById('f_nombre').value||'').toLowerCase().trim();
  const cat = document.getElementById('f_cat').value||'';
  const mar = document.getElementById('f_marca').value||'';
  let list = dataInv.slice();
  if (nom) list = list.filter(x=> String(x.nombre||'').toLowerCase().includes(nom));
  if (cat) list = list.filter(x=> String(x.categoria_id||'')===String(cat));
  if (mar) list = list.filter(x=> String(x.marca_id||'')===String(mar));
  renderInv(list);
}
function limpiarInv(){ document.getElementById('f_nombre').value=''; document.getElementById('f_cat').value=''; document.getElementById('f_marca').value=''; renderInv(dataInv); }
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


