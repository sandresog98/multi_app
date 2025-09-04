<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('ticketera');
$currentUser = $auth->getCurrentUser();
$pageTitle = 'Ticketera - Detalle de Ticket';
$currentPage = 'ticketera_tickets';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0 d-flex align-items-center"><i class="fas fa-ticket-alt me-2"></i><span id="pageTitle">Detalle de ticket</span><span id="titleEstado" class="ms-3"></span></h1>
        <div>
          <a href="tickets.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
      </div>

      <div class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Ciclo de vida de un ticket</h5>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#lifecyclePanel" aria-expanded="false">Mostrar/Ocultar</button>
        </div>
        <div class="collapse mt-3" id="lifecyclePanel">
          <div class="text-muted">
            <span class="badge bg-secondary">Backlog</span>
            → <span class="badge bg-primary">En Curso</span>
            → <span class="badge bg-warning">En Espera</span>
            ↔ <span class="badge bg-primary">En Curso</span>
            → <span class="badge bg-info">Resuelto</span>
            → <span class="badge bg-success">Aceptado</span>
            <br>
            <span class="badge bg-danger">Rechazado</span> puede aplicarse desde <span class="badge bg-secondary">Backlog</span> (responsable). Desde <span class="badge bg-info">Resuelto</span>, el solicitante puede marcar <span class="badge bg-success">Aceptado</span> o <span class="badge bg-danger">Rechazado</span> (vuelve a <span class="badge bg-primary">En Curso</span>).
          </div>
        </div>
      </div></div>

      <div id="content"></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
window.CURRENT_USER_ID = <?php echo (int)($currentUser['id'] ?? 0); ?>;
(function(){
  const css = '.timeline{position:relative;padding-left:20px}.timeline-item{position:relative}.timeline-marker{position:absolute;left:-25px;top:5px}.timeline-marker i{font-size:12px}.timeline-item:not(:last-child)::after{content:"";position:absolute;left:-19px;top:20px;bottom:-20px;width:2px;background-color:#e9ecef}';
  const s = document.createElement('style'); s.textContent = css; document.head.appendChild(s);
})();

function estadoBadge(e){
  const map = { 'Backlog':'secondary','En Curso':'primary','En Espera':'warning','Resuelto':'info','Aceptado':'success','Rechazado':'danger' };
  const c = map[e] || 'secondary';
  return `<span class="badge bg-${c}">${e}</span>`;
}
function escapeHtml(str){ return String(str||'').replace(/[&<>\"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

function buildInfoRows(it){
  return `
    <tr>
      <th class="w-25">Estado</th><td>${estadoBadge(it.estado||'')}</td>
      <th class="w-25">Categoría</th><td>${escapeHtml(it.categoria_nombre||'')}</td>
    </tr>
    <tr>
      <th class="w-25">Responsable</th><td colspan="3">${escapeHtml(it.responsable_nombre||'')}</td>
    </tr>
    <tr>
      <th class="w-25">Creador</th><td>${escapeHtml(it.creador_nombre||'')}</td>
      <th class="w-25">Solicitante</th><td>${escapeHtml(it.solicitante_nombre||'')}</td>
    </tr>
    <tr>
      <th class="w-25">Resumen</th><td colspan="3">${escapeHtml(it.resumen||'')}</td>
    </tr>
    <tr>
      <th class="w-25">Descripción</th><td colspan="3">${escapeHtml(it.descripcion||'')}</td>
    </tr>`;
}

function buildHistHtml(eventos){
  const ordered = (eventos||[]).slice().sort((a,b)=>{
    const da = new Date(a.fecha||0).getTime();
    const db = new Date(b.fecha||0).getTime();
    return db - da; // most recent first
  });
  return ordered.length ? (`<div class="timeline">${ordered.map(e=>{
    const fecha = String(e.fecha||'');
    const usuario = String(e.usuario_nombre||'N/A');
    const tipo = String(e.tipo||'');
    const comentario = String(e.comentario||'');
    const isReas = (tipo === 'reasignacion') || /reasign/i.test(comentario);
    const before = e.estado_anterior ? estadoBadge(e.estado_anterior) : '';
    const after  = e.estado_nuevo ? estadoBadge(e.estado_nuevo) : '';
    const trans = (before||after)? `${before} → ${after}` : '';
    // comentario ya definido arriba
    const title = (tipo==='cambio_estado') ? 'Cambio de estado' : (isReas ? 'Reasignación' : 'Comentario');
    const titleNode = isReas ? '<span class="badge" style="background-color:#6f42c1;">Reasignación</span>' : escapeHtml(title);
    const markerIcon = isReas ? '<i class="fas fa-circle" style="color:#6f42c1"></i>' : '<i class="fas fa-circle text-primary"></i>';
    return `
      <div class="timeline-item mb-3">
        <div class="d-flex align-items-start">
          <div class="timeline-marker me-3">${markerIcon}</div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start"><h6 class="mb-1">${titleNode}</h6><small class="text-muted">${escapeHtml(fecha)}</small></div>
            <div class="small text-muted mb-1">Por: ${escapeHtml(usuario)}</div>
            ${(isReas && (e.responsable_anterior_nombre || e.responsable_nuevo_nombre)) ? (`<div class=\"small mb-1\"><strong>De:</strong> ${escapeHtml(String(e.responsable_anterior_nombre||'N/A'))} <span class=\"mx-1\">→</span> <strong>A:</strong> ${escapeHtml(String(e.responsable_nuevo_nombre||'N/A'))}</div>`) : ''}
            ${trans?(`<div class=\"small mb-1\">${trans}</div>`):''}
            ${comentario?(`<div class="small">${escapeHtml(comentario)}</div>`):''}
          </div>
        </div>
      </div>`;
  }).join('')}</div>`) : '<div class="text-muted">Sin historial</div>';
}

function buildTransitionControls(it){
  const uid = Number(window.CURRENT_USER_ID||0);
  const responsable = Number(it.responsable_id||0);
  const solicitante = Number(it.solicitante_id||0);
  const estado = String(it.estado||'');
  if (estado==='Aceptado') { return '<div class="text-muted">Ticket finalizado</div>'; }
  const isResp = uid===responsable; const isSol = uid===solicitante;
  let buttonsHtml = '';
  if (estado==='Backlog' && isResp){ buttonsHtml = ctlBtns(['En Curso','Rechazado']); }
  else if (estado==='En Curso' && isResp){ buttonsHtml = ctlBtns(['En Espera','Resuelto']); }
  else if (estado==='En Espera' && isResp){ buttonsHtml = ctlBtns(['En Curso','Resuelto']); }
  else if (estado==='Resuelto' && isSol){ buttonsHtml = ctlBtns(['Aceptado','Rechazado']); }
  if (isResp && !['Aceptado','Rechazado','Resuelto'].includes(estado)) { buttonsHtml += `<button class="btn btn-outline-secondary btn-sm" id="btnReasignar"><i class="fas fa-user-tag me-1"></i>Reasignar</button>`; }
  const commentBox = `<div class="input-group mb-2"><span class="input-group-text"><i class="fas fa-comment"></i></span><input id="transitionComment" class="form-control" placeholder="Comentario para la transición (obligatorio)"></div>`;
  const helpText = `<div class="text-muted small mb-2">Selecciona a qué estado cambiar.</div>`;
  const group = buttonsHtml ? `<div class="d-flex flex-wrap align-items-center gap-2 mb-2">${buttonsHtml}</div>` : '<div class="mb-2"><span class="text-muted">No hay acciones disponibles para tu usuario en este estado.</span></div>';
  return `${buttonsHtml?commentBox+helpText:''}${group}`;
}
function ctlBtns(names){
  return names.map(n=>{
    let cls = 'btn btn-primary';
    let icon = 'fa-play';
    if (n==='Rechazado') { cls = 'btn btn-danger'; icon = 'fa-times'; }
    else if (n==='En Espera') { cls = 'btn btn-secondary'; icon = 'fa-clock'; }
    else if (n==='Resuelto') { cls = 'btn btn-success'; icon = 'fa-check'; }
    else if (n==='Aceptado') { cls = 'btn btn-success'; icon = 'fa-check-circle'; }
    else if (n==='En Curso') { cls = 'btn btn-primary'; icon = 'fa-play'; }
    return `<button class=\"${cls} btn-sm\" data-action=\"setState\" data-state=\"${n}\"><i class=\"fas ${icon} me-1\"></i>${n}</button>`;
  }).join('');
}

async function postJSON(url, body){
  const res = await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  try { return await res.json(); } catch { return {success:false,message:'Respuesta inválida'}; }
}

async function buscarUsuarios(q){
  const res = await fetch('../api/usuarios_buscar.php?q='+encodeURIComponent(q));
  try { const j = await res.json(); return (j && j.items) ? j.items : []; } catch { return []; }
}

function abrirReasignar(it){
  const html = `<div class=\"modal fade\" id=\"mReasignar\" tabindex=\"-1\"><div class=\"modal-dialog\"><div class=\"modal-content\">\n    <div class=\"modal-header\"><h5 class=\"modal-title\">Reasignar ticket #${it.id}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div>\n    <div class=\"modal-body\">\n      <div class=\"mb-2\">Responsable actual: <strong>${escapeHtml(it.responsable_nombre||'')}</strong></div>\n      <label class=\"form-label\">Nuevo responsable</label>\n      <div class=\"position-relative\">\n        <input id=\"r_usuario\" class=\"form-control\" placeholder=\"Buscar usuario\" autocomplete=\"off\">\n        <div id=\"r_usuario_rs\" class=\"list-group position-absolute w-100\" style=\"z-index:1070; max-height:220px; overflow:auto;\"></div>\n      </div>\n      <label class=\"form-label mt-3\">Comentario</label>\n      <textarea id=\"r_coment\" class=\"form-control\" rows=\"3\" placeholder=\"Motivo de la reasignación (opcional)\"></textarea>\n    </div>\n    <div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" id=\"btnDoReasignar\">Guardar</button></div>\n  </div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  const el = document.getElementById('mReasignar'); const modal = new bootstrap.Modal(el); modal.show();
  el.addEventListener('hidden.bs.modal', ()=>el.remove());
  let selUser = null;
  const inp = el.querySelector('#r_usuario'); const rs = el.querySelector('#r_usuario_rs');
  async function fill(q){ const items = await buscarUsuarios(q); rs.innerHTML = items.length? items.map(u=>`<a href=\"#\" class=\"list-group-item list-group-item-action\" data-id=\"${u.id}\" data-name=\"${u.nombre_completo}\">${u.nombre_completo} <small class=\"text-muted\">(${u.usuario})</small></a>`).join('') : '<div class=\"list-group-item text-muted\">Sin resultados</div>'; rs.querySelectorAll('a').forEach(a=>a.addEventListener('click',(ev)=>{ ev.preventDefault(); selUser={id:Number(a.dataset.id)}; inp.value=a.dataset.name; rs.innerHTML=''; })); }
  inp.addEventListener('input', ()=>fill(inp.value.trim())); inp.addEventListener('focus', ()=>fill('')); inp.addEventListener('blur', ()=>setTimeout(()=>{ rs.innerHTML=''; },200));
  el.querySelector('#btnDoReasignar').addEventListener('click', async ()=>{
    const nuevoId = Number((selUser&&selUser.id)||0);
    if(!nuevoId){ alert('Selecciona el nuevo responsable'); return; }
    const comentario = (el.querySelector('#r_coment').value||'').trim();
    if(!comentario){ alert('Debes ingresar un comentario para reasignar.'); return; }
    const r = await postJSON('../api/tickets_reasignar.php', { ticket_id: it.id, nuevo_responsable_id: nuevoId, comentario });
    if(!(r&&r.success)){ alert((r&&r.message)||'No se pudo reasignar'); return; }
    modal.hide(); loadDetail();
  });
}

async function loadDetail(){
  const params = new URLSearchParams(window.location.search);
  const id = Number(params.get('id')||0);
  if(!id){ document.getElementById('content').innerHTML = '<div class="alert alert-danger">ID inválido</div>'; return; }
  try{
    const res = await fetch('../api/tickets_detalle.php?id='+id);
    const j = await res.json(); if(!(j&&j.success)){ document.getElementById('content').innerHTML = `<div class=\"alert alert-danger\">${escapeHtml(j.message||'Error')}</div>`; return; }
    const data = j.data||{}; const it = data.item||{}; const eventos = data.eventos||[];
    const titleEl = document.getElementById('pageTitle'); if(titleEl){ titleEl.textContent = `Ticket #${it.id||id}`; }
    const tEstado = document.getElementById('titleEstado'); if(tEstado){ tEstado.innerHTML = estadoBadge(it.estado||''); }
    const html = `
      <div class=\"row g-3\">
        <div class=\"col-lg-7\">
          <div class=\"card\"><div class=\"card-body\">
            <h6>Información</h6>
            <div class=\"table-responsive\"><table class=\"table table-sm\"><tbody id=\"infoBody\">${buildInfoRows(it)}</tbody></table></div>
            <hr>
            <h6>Agregar comentario</h6>
            <div class=\"input-group\"><input id=\"newComment\" class=\"form-control\" placeholder=\"Escribe un comentario\"><button class=\"btn btn-outline-primary\" id=\"btnAddComment\"><i class=\"fas fa-paper-plane\"></i></button></div>
            <hr>
            <h6>Acciones</h6>
            <div id=\"actionControls\">${buildTransitionControls(it)}</div>
          </div></div>
        </div>
        <div class=\"col-lg-5\">
          <div class=\"card\"><div class=\"card-body\">
            <h6>Historial</h6>
            <div id=\"histBody\">${buildHistHtml(eventos)}</div>
          </div></div>
        </div>
      </div>`;
    document.getElementById('content').innerHTML = html;

    document.getElementById('btnAddComment').addEventListener('click', async ()=>{
      const txt = (document.getElementById('newComment').value||'').trim();
      if(!txt){ alert('Escribe un comentario'); return; }
      const r = await postJSON('../api/tickets_comentar.php',{ ticket_id: it.id, comentario: txt });
      if(!(r&&r.success)){ alert((r&&r.message)||'No se pudo comentar'); return; }
      document.getElementById('newComment').value = '';
      loadDetail();
    });
    document.querySelectorAll('[data-action="setState"]').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const estado = btn.getAttribute('data-state');
        const txt = (document.getElementById('transitionComment').value||'').trim();
        if(!txt){ alert('Debes ingresar un comentario para cambiar el estado.'); return; }
        const r = await postJSON('../api/tickets_cambiar_estado.php',{ ticket_id: it.id, estado, comentario: txt });
        if(!(r&&r.success)){ alert((r&&r.message)||'No se pudo cambiar estado'); return; }
        loadDetail();
      });
    });
    const btnReas = document.getElementById('btnReasignar');
    if (btnReas) { btnReas.addEventListener('click', ()=> abrirReasignar(it)); }
  }catch(e){ document.getElementById('content').innerHTML = `<div class=\"alert alert-danger\">Error: ${escapeHtml(String(e))}</div>`; }
}

document.addEventListener('DOMContentLoaded', loadDetail);
</script>



