<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('tienda.ventas');
$currentUser = $auth->getCurrentUser();
$pdo = getConnection();

// Catálogo y stock disponible (IMEIs no vendidos y productos sin IMEI por cantidad)
$prods = $pdo->query("SELECT p.id, p.nombre, p.precio_compra_aprox, p.precio_venta_aprox, c.nombre AS categoria, m.nombre AS marca,
                             COALESCE(cd.ingresado,0) - COALESCE(vd.vendido,0) AS disp_sin_imei,
                             COALESCE(imei.disp_imei,0) AS disp_imei
                      FROM tienda_producto p
                      INNER JOIN tienda_categoria c ON c.id=p.categoria_id
                      INNER JOIN tienda_marca m ON m.id=p.marca_id
                      LEFT JOIN (
                        SELECT producto_id, SUM(cantidad) AS ingresado
                        FROM tienda_compra_detalle GROUP BY producto_id
                      ) cd ON cd.producto_id = p.id
                      LEFT JOIN (
                        SELECT producto_id, SUM(cantidad) AS vendido
                        FROM tienda_venta_detalle WHERE compra_imei_id IS NULL GROUP BY producto_id
                      ) vd ON vd.producto_id = p.id
                      LEFT JOIN (
                        SELECT cd.producto_id, COUNT(*) AS disp_imei
                        FROM tienda_compra_imei ci INNER JOIN tienda_compra_detalle cd ON cd.id=ci.compra_detalle_id
                        WHERE ci.vendido = FALSE
                        GROUP BY cd.producto_id
                      ) imei ON imei.producto_id = p.id
                      WHERE p.estado_activo=TRUE
                      ORDER BY c.nombre, p.nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$clientes = $pdo->query("SELECT id, nombre, nit_cedula FROM tienda_clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$pageTitle = 'Tienda - Ventas';
$currentPage = 'tienda_ventas';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-cash-register me-2"></i>Ventas</h1>
      </div>

      <div class="card mb-3"><div class="card-body">
        <h5 class="mb-2">Cliente</h5>
        <div class="text-muted small mb-2">Selecciona el cliente o el asociado.</div>
        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label small">Tipo cliente</label>
            <select id="tipoCliente" class="form-select"><option value="asociado">Asociado</option><option value="externo">Cliente externo</option></select>
          </div>
          <div class="col-md-4" id="boxAsociado">
            <label class="form-label small">Buscar asociado (cédula o nombre)</label>
            <input class="form-control" id="cedulaAsociado" placeholder="Escribe al menos 2 caracteres" autocomplete="off">
            <div id="asoc_results" class="list-group position-absolute w-50" style="z-index:1070; max-height:220px; overflow:auto;"></div>
          </div>
          <div class="col-md-5 d-none position-relative" id="boxExterno">
            <label class="form-label small">Buscar cliente externo</label>
            <input class="form-control" id="buscarClienteExt" placeholder="Cédula o nombre" autocomplete="off">
            <div id="cli_results" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
          </div>
        </div>
      </div></div>



      <div class="card mb-3"><div class="card-body">
        <h5 class="mb-2">Lista de productos</h5>
        <div class="text-muted small mb-2">Revisa los productos agregados a la venta.</div>
        <div class="d-flex justify-content-end mb-2">
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mSelProducto"><i class="fas fa-plus me-1"></i>Agregar producto</button>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th><th>IMEIs</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblItems"></tbody></table>
        </div>
        <div class="text-end h5" id="totalLine">Total: $<span id="total">0</span></div>
      </div></div>

      <div class="card"><div class="card-body">
        <h5 class="mb-2">Medios de Pago</h5>
        <div class="text-muted small mb-2">Agrega uno o varios medios de pago hasta completar el total.</div>
        <div id="pagosBox" class="row g-2">
          <div class="col-md-3"><select class="form-select" id="tipoPago"><option value="efectivo">Efectivo</option><option value="bold">Bold</option><option value="qr">Código QR</option><option value="sifone">Crédito SIFONE</option><option value="reversion">Reversión</option></select></div>
          <div class="col-md-3"><input type="number" class="form-control" id="montoPago" placeholder="Monto" step="0.01" min="0"></div>
          <div class="col-md-3 d-none" id="boxNumCred"><input class="form-control" id="numCredito" placeholder="Número crédito SIFONE"></div>
          <div class="col-md-3 d-none" id="boxPagoAnt"><input class="form-control" id="pagoAnterior" placeholder="ID pago anterior"></div>
          <div class="col-md-3 d-grid"><button class="btn btn-outline-primary" onclick="addPago()"><i class="fas fa-plus me-1"></i>Agregar pago</button></div>
        </div>
        <div class="table-responsive mt-2"><table class="table table-sm"><thead class="table-light"><tr><th>Tipo</th><th>Monto</th><th>Detalle</th><th class="text-end">Acciones</th></tr></thead><tbody id="tblPagos"></tbody></table></div>
        <div class="text-end h6" id="totalPagosLine">Total pagos: $<span id="totalPagos">0</span></div>
        <div class="text-end"><button id="btnRegistrarVenta" class="btn btn-success" onclick="guardarVenta()" disabled><i class="fas fa-check me-1"></i>Registrar venta</button></div>
      </div></div>

    </main>
  </div>
</div>

<!-- Modal Selección de producto -->
<div class="modal fade" id="mSelProducto" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Agregar producto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-6 position-relative"><label class="form-label small">Producto (escribe categoría, marca o nombre)</label>
        <input id="buscarProd" class="form-control" placeholder="Ej: Celulares Samsung A14" autocomplete="off">
        <div id="prod_results" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
        <select id="selProd" class="form-select d-none">
          <option value="">Seleccione producto</option>
          <?php foreach ($prods as $p): ?>
          <option value="<?php echo (int)$p['id']; ?>" data-label="<?php echo htmlspecialchars(($p['categoria']?:'').' - '.($p['marca']?:'').' - '.$p['nombre']); ?>" data-pc="<?php echo htmlspecialchars((string)($p['precio_compra_aprox'] ?? '')); ?>" data-pv="<?php echo htmlspecialchars((string)($p['precio_venta_aprox'] ?? '')); ?>" data-disp="<?php echo (int)($p['disp_sin_imei'] ?? 0); ?>" data-dispimei="<?php echo (int)($p['disp_imei'] ?? 0); ?>"><?php echo htmlspecialchars(($p['categoria']?:'').' - '.$p['nombre']); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label small">Cantidad</label><input type="number" id="cantidad" class="form-control" min="1" value="1"></div>
      <div class="col-md-4"><label class="form-label small d-flex justify-content-between align-items-center">Precio unitario <small id="precioRef" class="text-muted ms-2"></small></label><input type="number" id="precio" class="form-control" min="0" step="0.01"></div>
      <div class="col-12 d-none" id="boxImei">
        <label class="form-label small">Seleccionar IMEIs disponibles</label>
        <div class="input-group">
          <button type="button" class="btn btn-outline-secondary" onclick="abrirImeis()"><i class="fas fa-list"></i> Ver IMEIs</button>
          <input class="form-control" id="imeis" placeholder="IMEIs seleccionados" readonly>
        </div>
        <div class="form-text">La cantidad debe coincidir con los IMEIs seleccionados.</div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button class="btn btn-primary" onclick="addItem(); const m=bootstrap.Modal.getInstance(document.getElementById('mSelProducto')); if(m) m.hide();"><i class="fas fa-plus me-1"></i>Agregar</button>
  </div>
</div></div></div>

<script>
function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
const tipoCliente = document.getElementById('tipoCliente');
tipoCliente.addEventListener('change', ()=>{
  const ext = tipoCliente.value==='externo';
  document.getElementById('boxAsociado').classList.toggle('d-none', ext);
  document.getElementById('boxExterno').classList.toggle('d-none', !ext);
});
const selProd = document.getElementById('selProd'); const imeiBox = document.getElementById('boxImei');
let precioEdited = false;
document.getElementById('precio').addEventListener('input', ()=>{ precioEdited = true; });
selProd.addEventListener('change', ()=>{ const opt=selProd.selectedOptions[0]; const t=opt?.textContent||''; const isCel=/(^|\s|-)Celulares(\s|-)/i.test(t); imeiBox.classList.toggle('d-none', !isCel); const pc=parseFloat(opt?.getAttribute('data-pc')||''); const pv=parseFloat(opt?.getAttribute('data-pv')||''); if(!isNaN(pv)){ document.getElementById('precio').value=pv; } const disp = Number(opt?.getAttribute('data-disp')||0); const dispImei = Number(opt?.getAttribute('data-dispimei')||0); document.getElementById('cantidad').max = isCel ? dispImei : disp; document.getElementById('precioRef').textContent = (!isNaN(pc) ? ('Compra aprox: $'+pc.toLocaleString()) : '') + (isNaN(pc)?'':'') + (isNaN(disp)&&isNaN(dispImei)?'':(' · Stock: '+ (isCel?dispImei:disp))); precioEdited = false; });
const items=[]; const pagos=[];
function addItem(){ const opt=selProd.selectedOptions[0]; const id=Number(selProd.value||0); const text=opt?.textContent||''; const cant=Number(document.getElementById('cantidad').value||0); const precio=Number(document.getElementById('precio').value||0); if(!id||cant<=0||precio<0){ alert('Complete datos del producto'); return; } const isCel=/(^|\s|-)Celulares(\s|-)/i.test(text); const stock = Number(opt?.getAttribute('data-disp')||0); const stockImei = Number(opt?.getAttribute('data-dispimei')||0); if (!isCel && cant > stock){ alert('Cantidad supera el inventario disponible ('+stock+')'); return; } let imeis=[]; if(isCel){ imeis=(document.getElementById('imeis').value||'').split(/,\s*|\r?\n/).map(s=>s.trim()).filter(Boolean); if(imeis.length!==cant){ alert('Debe ingresar '+cant+' IMEIs'); return; } const set=new Set(imeis); if(set.size!==imeis.length){ alert('IMEIs duplicados'); return; } if (cant > stockImei){ alert('Cantidad supera IMEIs disponibles ('+stockImei+')'); return; } } items.push({producto_id:id, label:text, cantidad:cant, precio_unitario:precio, imeis}); renderItems(); limpiarProductoVenta(); }

function limpiarProductoVenta(){
  // Limpiar campos de selección de producto
  document.getElementById('buscarProd').value='';
  selProd.value='';
  document.getElementById('cantidad').value='1';
  document.getElementById('cantidad').removeAttribute('max');
  document.getElementById('precio').value='';
  document.getElementById('precioRef').textContent='';
  document.getElementById('imeis').value='';
  imeiBox.classList.add('d-none');
  precioEdited = false;
}
function delItem(i){ items.splice(i,1); renderItems(); }
function renderItems(){ const body=document.getElementById('tblItems'); body.innerHTML=''; let total=0; if(!items.length){ body.innerHTML='<tr><td colspan="6" class="text-muted">Sin items</td></tr>'; document.getElementById('total').textContent='0'; syncTotalsHighlight(); return; } items.forEach((it,idx)=>{ const sub=Number(it.cantidad||0)*Number(it.precio_unitario||0); total+=sub; const tr=document.createElement('tr'); tr.innerHTML=`<td>${escapeHtml(it.label)}</td><td>${it.cantidad}</td><td>$${Number(it.precio_unitario||0).toLocaleString()}</td><td>$${sub.toLocaleString()}</td><td>${it.imeis?.length?it.imeis.join('<br>'):''}</td><td class=\"text-end\"><button class=\"btn btn-sm btn-outline-danger\" onclick=\"delItem(${idx})\"><i class=\"fas fa-trash\"></i></button></td>`; body.appendChild(tr); }); document.getElementById('total').textContent=total.toLocaleString(); syncTotalsHighlight(); }
const tipoPagoSel=document.getElementById('tipoPago'); tipoPagoSel.addEventListener('change',()=>{ const t=tipoPagoSel.value; document.getElementById('boxNumCred').classList.toggle('d-none', t!=='sifone'); document.getElementById('boxPagoAnt').classList.toggle('d-none', t!=='reversion'); });
function addPago(){ const t=tipoPagoSel.value; const monto=Number(document.getElementById('montoPago').value||0); if(monto<=0){ alert('Monto inválido'); return; } const det={tipo:t, monto}; if(t==='sifone'){ const nc=document.getElementById('numCredito').value.trim(); if(!/^\d+$/.test(nc)){ alert('Número crédito SIFONE inválido'); return; } det.numero_credito_sifone=nc; } if(t==='reversion'){ const pa=document.getElementById('pagoAnterior').value.trim(); if(!pa){ alert('Ingrese ID pago anterior'); return; } det.pago_anterior_id=pa; } pagos.push(det); renderPagos(); document.getElementById('montoPago').value=''; if(t==='sifone'){ document.getElementById('numCredito').value=''; } if(t==='reversion'){ document.getElementById('pagoAnterior').value=''; } }
function delPago(i){ pagos.splice(i,1); renderPagos(); }
function renderPagos(){
  const body=document.getElementById('tblPagos'); body.innerHTML='';
  let total=0;
  if(!pagos.length){
    body.innerHTML='<tr><td colspan="4" class="text-muted">Sin pagos</td></tr>';
    const tp=document.getElementById('totalPagos'); if(tp) tp.textContent='0';
    return;
  }
  pagos.forEach((p,idx)=>{
    const extra = p.tipo==='sifone'?('Crédito: '+escapeHtml(p.numero_credito_sifone||'')) : (p.tipo==='reversion'?('Pago ant.: '+escapeHtml(p.pago_anterior_id||'')) : '');
    total += Number(p.monto)||0;
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${escapeHtml(p.tipo)}</td><td>$${Number(p.monto).toLocaleString()}</td><td>${extra}</td><td class=\"text-end\"><button class=\"btn btn-sm btn-outline-danger\" onclick=\"delPago(${idx})\"><i class=\"fas fa-trash\"></i></button></td>`;
    body.appendChild(tr);
  });
  const tp=document.getElementById('totalPagos'); if(tp) tp.textContent=total.toLocaleString();
  syncTotalsHighlight();
}

function syncTotalsHighlight(){
  // Calcular a partir de las estructuras de datos para evitar problemas de formato local
  const t = (items||[]).reduce((s,it)=> s + Number(it.cantidad||0) * Number(it.precio_unitario||0), 0);
  const p = (pagos||[]).reduce((s,pg)=> s + Number(pg.monto||0), 0);
  const tl = document.getElementById('totalLine');
  const pl = document.getElementById('totalPagosLine');
  const btn = document.getElementById('btnRegistrarVenta');
  if (!tl || !pl) return;
  const match = Math.abs(t - p) < 0.01;
  tl.classList.toggle('text-danger', !match);
  pl.classList.toggle('text-danger', !match);
  tl.classList.toggle('text-success', match);
  pl.classList.toggle('text-success', match);
  if (btn) btn.disabled = !match || t <= 0;
}
async function guardarVenta(){
  if(!items.length){ alert('Agregar items'); return; }
  const tipo = tipoCliente.value;
  let ced = '';
  let clienteId = null;
  if (tipo === 'asociado') {
    ced = document.getElementById('cedulaAsociado').value.trim();
    if (!ced){ alert('Ingrese cédula del asociado'); return; }
  } else {
    const hid = document.getElementById('clienteExternoId');
    clienteId = hid ? Number(hid.value||0) : null;
    if (!clienteId){ alert('Seleccione un cliente externo'); return; }
  }
  const res = await fetch('../api/ventas_guardar.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ tipo_cliente: tipo, asociado_cedula: ced, cliente_id: clienteId, items, pagos })
  });
  const j = await res.json(); if(!j.success){ alert(j.message||'Error'); return; }
  alert('Venta registrada'); location.reload();
}

// Autocomplete de asociado (similar a transacciones)
async function buscarAsociadosVenta(){
  const input = document.getElementById('cedulaAsociado');
  const cont = document.getElementById('asoc_results');
  const q = input.value.trim();
  cont.innerHTML = '';
  if (q.length < 2) return;
  cont.innerHTML = '<a class="list-group-item text-muted">Buscando…</a>';
  try{
    const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/buscar_asociados.php?q='+encodeURIComponent(q));
    const j = await res.json(); const items = j.items||[];
    if (!items.length){ cont.innerHTML = '<a class="list-group-item text-muted">Sin resultados</a>'; return; }
    cont.innerHTML = '';
    items.forEach(a=>{
      const el = document.createElement('a'); el.href='#'; el.className='list-group-item list-group-item-action'; el.textContent = `${a.cedula} — ${a.nombre}`;
      el.addEventListener('click', (ev)=>{ ev.preventDefault(); input.value = a.cedula; cont.innerHTML=''; });
      cont.appendChild(el);
    });
  }catch(e){ cont.innerHTML = '<a class="list-group-item text-danger">Error</a>'; }
}
document.getElementById('cedulaAsociado')?.addEventListener('input', buscarAsociadosVenta);
document.addEventListener('click', (e)=>{ const c=document.getElementById('asoc_results'); if (c && !c.contains(e.target) && e.target.id!=='cedulaAsociado'){ c.innerHTML=''; }});

// Autocomplete de clientes externos
async function buscarClientesVenta(){
  const input = document.getElementById('buscarClienteExt');
  const cont = document.getElementById('cli_results');
  const q = input.value.trim(); cont.innerHTML = '';
  if (q.length < 2) return;
  cont.innerHTML = '<a class="list-group-item text-muted">Buscando…</a>';
  try{
    const res = await fetch('../api/clientes.php?action=listar');
    const j = await res.json(); const list = (j.items||[]).filter(c => String(c.nit_cedula||'').includes(q) || String(c.nombre||'').toLowerCase().includes(q.toLowerCase()));
    if (!list.length){ cont.innerHTML = '<a class="list-group-item text-muted">Sin resultados</a>'; return; }
    cont.innerHTML = '';
    list.forEach(c=>{ const el=document.createElement('a'); el.href='#'; el.className='list-group-item list-group-item-action'; el.textContent = `${c.nit_cedula} — ${c.nombre}`; el.addEventListener('click', (ev)=>{ ev.preventDefault(); document.getElementById('buscarClienteExt').value = `${c.nit_cedula}`; document.getElementById('clienteExternoId')?.remove(); const hidden=document.createElement('input'); hidden.type='hidden'; hidden.id='clienteExternoId'; hidden.value=c.id; document.body.appendChild(hidden); cont.innerHTML=''; }); cont.appendChild(el); });
  }catch(e){ cont.innerHTML = '<a class="list-group-item text-danger">Error</a>'; }
}
document.getElementById('buscarClienteExt')?.addEventListener('input', buscarClientesVenta);
document.addEventListener('click', (e)=>{ const c=document.getElementById('cli_results'); if (c && !c.contains(e.target) && e.target.id!=='buscarClienteExt'){ c.innerHTML=''; }});

// Selector de IMEIs
async function abrirImeis(){
  const pid = Number(document.getElementById('selProd').value||0);
  if (!pid){ alert('Seleccione un producto'); return; }
  try{
    const res = await fetch('../api/ventas_imeis.php?producto_id='+pid); const j=await res.json();
    if(!j.success){ alert(j.message||'Error'); return; }
    const list = j.items||[];
    const rows = list.map(i=>`<tr><td><input type=\"checkbox\" class=\"form-check-input\" value=\"${i.imei}\" data-pc=\"${i.precio_compra}\" data-pv=\"${i.precio_venta_sugerido}\"></td><td>${i.imei}</td><td>$${Number(i.precio_compra||0).toLocaleString()}</td><td>$${Number(i.precio_venta_sugerido||0).toLocaleString()}</td><td>#${i.compra_id}</td></tr>`).join('');
    const html = `<div class=\"modal fade\" id=\"mImeisSel\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">IMEIs disponibles</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"table-responsive\"><table class=\"table table-sm\"><thead class=\"table-light\"><tr><th></th><th>IMEI</th><th>P. compra</th><th>P. vta sug.</th><th>Compra</th></tr></thead><tbody>${rows||'<tr><td colspan=5 class=\\\"text-muted\\\">Sin IMEIs disponibles</td></tr>'}</tbody></table></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" onclick=\"confirmarImeis()\">Seleccionar</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('mImeisSel'); const modal = new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal',()=>el.remove());
  }catch(e){ alert('Error: '+e); }
}

function confirmarImeis(){
  const el = document.getElementById('mImeisSel');
  const checks = Array.from(el.querySelectorAll('input[type="checkbox"]:checked'));
  const imeis = checks.map(c=>c.value);
  document.getElementById('imeis').value = imeis.join(', ');
  const modal = bootstrap.Modal.getInstance(el); if (modal) modal.hide();
  // Ajustar cantidad al número de IMEIs
  document.getElementById('cantidad').value = imeis.length || 1;
  // Autorelleno con el último precio de venta sugerido marcado (solo si no se editó manualmente)
  if (checks.length){
    const last = checks[checks.length-1];
    const pv = Number(last.getAttribute('data-pv')||0);
    if (!isNaN(pv) && pv>0){
      const precioInput = document.getElementById('precio');
      const curr = Number(precioInput.value||0);
      if (!precioEdited || !(curr>0)){
        precioInput.value = pv;
        precioEdited = false;
      }
    }
  }
}

// Autocomplete productos por categoría/marca/nombre
const prodData = Array.from(document.querySelectorAll('#selProd option')).slice(1).map(o=>({ id:Number(o.value), label:o.getAttribute('data-label')||o.textContent, pc:Number(o.getAttribute('data-pc')||0), pv:Number(o.getAttribute('data-pv')||0), disp:Number(o.getAttribute('data-disp')||0), dispimei:Number(o.getAttribute('data-dispimei')||0) }));
document.getElementById('buscarProd')?.addEventListener('input', ()=>{
  const q = (document.getElementById('buscarProd').value||'').trim().toLowerCase();
  const cont = document.getElementById('prod_results'); cont.innerHTML=''; if(q.length<2){ return; }
  const list = prodData.filter(p=> (p.label||'').toLowerCase().includes(q) && ((p.disp||0) > 0 || (p.dispimei||0) > 0)).slice(0,30);
  if (!list.length){ cont.innerHTML = '<a class="list-group-item text-muted">Sin resultados</a>'; return; }
  const frag = document.createDocumentFragment();
  list.forEach(p=>{ const a=document.createElement('a'); a.href='#'; a.className='list-group-item list-group-item-action'; const stk = (p.dispimei||0) > 0 ? p.dispimei : p.disp; a.innerHTML = `<div class="d-flex justify-content-between"><span>${p.label}</span><small class="text-muted">Stock: ${stk}</small></div>`; a.addEventListener('click', (ev)=>{ ev.preventDefault(); seleccionarProducto(p); cont.innerHTML=''; document.getElementById('buscarProd').value=p.label; }); frag.appendChild(a); });
  cont.appendChild(frag);
});

function seleccionarProducto(p){
  // setear option seleccionado para reutilizar lógica existente
  const sel = document.getElementById('selProd'); sel.value = String(p.id);
  // trigger change para setear stock y precio sugerido
  const event = new Event('change'); sel.dispatchEvent(event);
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>


