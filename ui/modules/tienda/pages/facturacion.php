<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('tienda.facturacion');
$currentUser = $auth->getCurrentUser();

$pageTitle = 'Tienda - Facturación';
$currentPage = 'tienda_facturacion';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-file-invoice-dollar me-2"></i>Facturación</h1>
      </div>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>ID</th><th>Fecha</th><th>Cliente/Asociado</th><th>Total</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblFact"></tbody></table>
        </div>
      </div></div>

    </main>
  </div>
</div>

<script>
function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
async function loadVentas(){
  try{
    const res = await fetch('../api/ventas_listar.php'); const j=await res.json();
    const body = document.getElementById('tblFact'); body.innerHTML='';
    if(!j.success){ body.innerHTML='<tr><td colspan="5" class="text-danger">'+(j.message||'Error')+'</td></tr>'; return; }
    const list = j.items||[]; if(!list.length){ body.innerHTML='<tr><td colspan="5" class="text-muted">Sin ventas</td></tr>'; return; }
    list.forEach(v=>{
      const tr=document.createElement('tr');
      const quien = v.tipo_cliente==='asociado' ? ('Asociado · '+escapeHtml(v.asociado_cedula||'')) : ('Cliente · '+escapeHtml(v.cliente_nombre||''));
      const btnEliminar = (Number(v.reversiones||0) > 0 || Number(v.usada_como_pago_anterior||0) > 0) ? '<span class="text-muted">Bloqueada</span>' : `<button class="btn btn-sm btn-outline-danger" onclick="eliminarVenta(${v.id})"><i class="fas fa-trash"></i></button>`;
      tr.innerHTML = `<td>${v.id}</td><td>${escapeHtml(v.fecha_creacion||'')}</td><td>${quien}</td><td>$${Number(v.total||0).toLocaleString()}</td><td class="text-end"><button class="btn btn-sm btn-outline-info me-1" onclick="verVenta(${v.id})"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-outline-success me-1" onclick="generarPDF(${v.id})"><i class="fas fa-file-pdf"></i></button>${btnEliminar}</td>`;
      body.appendChild(tr);
    });
  }catch(e){ document.getElementById('tblFact').innerHTML='<tr><td colspan="5" class="text-danger">'+e+'</td></tr>'; }
}

async function verVenta(id){
  try{
    const res=await fetch('../api/ventas_detalle.php?id='+id); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; }
    const v=j.venta||{}; const dets=j.detalles||[]; const pags=j.pagos||[];
    const rows = dets.map(d=>{
      const compra = Number(d.precio_compra||0);
      const venta = Number(d.precio_unitario||0);
      const gan = (venta - compra) * (d.cantidad||1);
      const imeiHtml = d.compra_imei_id ? ('<br><small class=\\\'text-muted\\\'>IMEI: '+escapeHtml(d.imei||'')+'</small>') : '';
      const revFlag = d.revertido ? ' <span class=\"badge bg-warning text-dark\">Reversado</span>' : '';
      return `<tr><td>${escapeHtml(d.categoria||'')}</td><td>${escapeHtml(d.producto||'')}${imeiHtml}${revFlag}</td><td>${d.cantidad||0}</td><td>$${venta.toLocaleString()}<br><small class=\"text-muted\">Compra: $${compra.toLocaleString()}</small></td><td>$${Number(d.subtotal||0).toLocaleString()}<br><small class=\"${gan>=0?'text-success':'text-danger'}\">Ganancia: $${gan.toLocaleString()}</small></td></tr>`;
    }).join('');
    const rowsP = pags.map(p=>`<tr><td>${escapeHtml(p.tipo||'')}</td><td>$${Number(p.monto||0).toLocaleString()}</td><td>${escapeHtml(p.numero_credito_sifone||p.pago_anterior_id||'')}</td></tr>`).join('');
    const cliente = v.tipo_cliente==='asociado' ? ('Asociado: '+escapeHtml(v.asociado_cedula||'')) : ('Cliente: '+escapeHtml(v.cliente_nombre||'')+' · '+escapeHtml(v.cliente_doc||''));
    const html = `<div class=\"modal fade\" id=\"mVentaDet\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Venta #${id}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"mb-2 small text-muted\">${cliente}</div><h6>Productos</h6><div class=\"table-responsive\"><table class=\"table table-sm\"><thead class=\"table-light\"><tr><th>Categoría</th><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>${rows||'<tr><td colspan=5 class=\\\"text-muted\\\">Sin items</td></tr>'}</tbody></table></div><h6 class=\"mt-3\">Pagos</h6><div class=\"table-responsive\"><table class=\"table table-sm\"><thead class=\"table-light\"><tr><th>Tipo</th><th>Monto</th><th>Detalle</th></tr></thead><tbody>${rowsP||'<tr><td colspan=3 class=\\\"text-muted\\\">Sin pagos</td></tr>'}</tbody></table></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cerrar</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el=document.getElementById('mVentaDet'); const modal=new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal',()=>el.remove());
  }catch(e){ alert('Error: '+e); }
}

async function eliminarVenta(id){ if(!confirm('¿Eliminar venta #'+id+'?')) return; const fd=new FormData(); fd.append('id',id); const res=await fetch('../api/ventas_eliminar.php',{method:'POST',body:fd}); const j=await res.json(); if(!j.success){ alert(j.message||'No se pudo eliminar'); return; } loadVentas(); }

async function generarPDF(id){
  try{
    // Mostrar indicador de carga
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    // Generar y descargar PDF
    window.open('../api/generar_factura_pdf.php?id=' + id, '_blank');
    
    // Restaurar botón
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }, 1000);
    
  }catch(e){
    alert('Error al generar PDF: ' + e.message);
    // Restaurar botón en caso de error
    const btn = event.target;
    btn.innerHTML = '<i class="fas fa-file-pdf"></i>';
    btn.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', loadVentas);
</script>

<?php include '../../../views/layouts/footer.php'; ?>


