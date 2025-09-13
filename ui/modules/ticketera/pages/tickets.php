<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('ticketera');
$currentUser = $auth->getCurrentUser();
$pageTitle = 'Ticketera - Tickets';
$currentPage = 'ticketera_tickets';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-tasks me-2"></i>Tickets</h1>
        <button class="btn btn-primary btn-sm" id="btnNuevo"><i class="fas fa-plus me-1"></i>Nuevo ticket</button>
      </div>

      <div class="card mb-3"><div class="card-body">
        <form class="row g-2" onsubmit="return false;">
          <div class="col-md-3"><input id="q" class="form-control" placeholder="Buscar por resumen/descripcion"></div>
          <div class="col-md-2">
            <select id="estado" class="form-select">
              <option value="">Todos los estados</option>
              <option>Backlog</option><option>En Curso</option><option>En Espera</option>
              <option>Resuelto</option><option>Aceptado</option><option>Rechazado</option>
            </select>
          </div>
          <div class="col-md-3 position-relative">
            <input id="f_responsable" class="form-control" placeholder="Responsable" autocomplete="off">
            <div id="f_responsable_rs" class="list-group position-absolute w-100" style="z-index:1070; max-height:220px; overflow:auto;"></div>
          </div>
          <div class="col-md-3 position-relative">
            <input id="f_solicitante" class="form-control" placeholder="Solicitante" autocomplete="off">
            <div id="f_solicitante_rs" class="list-group position-absolute w-100" style="z-index:1070; max-height:220px; overflow:auto;"></div>
          </div>
          <div class="col-md-2 d-grid"><button id="btnBuscar" class="btn btn-outline-primary">Buscar</button></div>
          <div class="col-md-2 d-grid"><button id="btnClear" class="btn btn-outline-secondary">Borrar filtros</button></div>
        </form>
      </div></div>

      <div class="card mt-3"><div class="card-body">
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

      <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="small text-muted" id="resumen"></div>
          <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" id="prev">«</button><button class="btn btn-outline-secondary" id="next">»</button></div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead class="table-light"><tr>
              <th>ID</th>
              <th>Categoría</th>
              <th>Resumen</th>
              <th>Estado</th>
              <th>Solicitante</th>
              <th>Responsable</th>
              <th>F. creación</th>
              <th class="text-end">Acciones</th>
            </tr></thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
// Identidad del usuario actual
window.CURRENT_USER_ID = <?php echo (int)($currentUser['id'] ?? 0); ?>;

// Estilos de timeline (ligeros)
(function(){
  const css = '.timeline{position:relative;padding-left:20px}.timeline-item{position:relative}.timeline-marker{position:absolute;left:-25px;top:5px}.timeline-marker i{font-size:12px}.timeline-item:not(:last-child)::after{content:"";position:absolute;left:-19px;top:20px;bottom:-20px;width:2px;background-color:#e9ecef}';
  const s = document.createElement('style'); s.textContent = css; document.head.appendChild(s);
})();
let page = 1, pages = 1;
let sel_f_responsable = null;
let sel_f_solicitante = null;
document.getElementById('btnBuscar').addEventListener('click', ()=>{ page=1; load(); });
document.getElementById('btnClear').addEventListener('click', ()=>{
  document.getElementById('q').value = '';
  document.getElementById('estado').value = '';
  document.getElementById('f_responsable').value = '';
  document.getElementById('f_solicitante').value = '';
  sel_f_responsable = null;
  sel_f_solicitante = null;
  page = 1; load();
});
document.getElementById('prev').addEventListener('click', ()=>{ if(page>1){ page--; load(); } });
document.getElementById('next').addEventListener('click', ()=>{ if(page<pages){ page++; load(); } });
document.getElementById('btnNuevo').addEventListener('click', abrirNuevo);

// Autocomplete Responsable (filtros)
document.getElementById('f_responsable').addEventListener('input', async (e)=>{
  const q=e.target.value.trim(); const rs = document.getElementById('f_responsable_rs'); rs.innerHTML='';
  const items = await buscarUsuarios(q); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
  rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
  rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_f_responsable={id:Number(a.dataset.id)}; document.getElementById('f_responsable').value=a.dataset.name; rs.innerHTML=''; }));
});
document.getElementById('f_responsable').addEventListener('focus', async ()=>{
  const rs = document.getElementById('f_responsable_rs'); rs.innerHTML='';
  const items = await buscarUsuarios(''); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
  rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
  rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_f_responsable={id:Number(a.dataset.id)}; document.getElementById('f_responsable').value=a.dataset.name; rs.innerHTML=''; }));
});
document.getElementById('f_responsable').addEventListener('blur', ()=>{ setTimeout(()=>{ const rs=document.getElementById('f_responsable_rs'); if(rs) rs.innerHTML=''; }, 200); });

// Autocomplete Solicitante (filtros)
document.getElementById('f_solicitante').addEventListener('input', async (e)=>{
  const q=e.target.value.trim(); const rs = document.getElementById('f_solicitante_rs'); rs.innerHTML='';
  const items = await buscarUsuarios(q); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
  rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
  rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_f_solicitante={id:Number(a.dataset.id)}; document.getElementById('f_solicitante').value=a.dataset.name; rs.innerHTML=''; }));
});
document.getElementById('f_solicitante').addEventListener('focus', async ()=>{
  const rs = document.getElementById('f_solicitante_rs'); rs.innerHTML='';
  const items = await buscarUsuarios(''); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
  rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
  rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_f_solicitante={id:Number(a.dataset.id)}; document.getElementById('f_solicitante').value=a.dataset.name; rs.innerHTML=''; }));
});
document.getElementById('f_solicitante').addEventListener('blur', ()=>{ setTimeout(()=>{ const rs=document.getElementById('f_solicitante_rs'); if(rs) rs.innerHTML=''; }, 200); });

function estadoBadge(e){
  const map = { 'Backlog':'secondary','En Curso':'primary','En Espera':'warning','Resuelto':'info','Aceptado':'success','Rechazado':'danger' };
  const c = map[e] || 'secondary';
  return `<span class=\"badge bg-${c}\">${e}</span>`;
}

async function load(){
  const q = document.getElementById('q').value;
  const estado = document.getElementById('estado').value;
  const responsable = Number((sel_f_responsable&&sel_f_responsable.id)||0) || '';
  const solicitante = Number((sel_f_solicitante&&sel_f_solicitante.id)||0) || '';
  const params = new URLSearchParams({ q, estado, responsable, solicitante, page, limit: 20, sort_by: 'fecha_creacion', sort_dir: 'DESC' });
  const tbody = document.getElementById('tbody');
  tbody.innerHTML = '<tr><td colspan="8" class="text-muted">Cargando…</td></tr>';
  try {
    const res = await fetch('../api/tickets_listar.php?'+params.toString());
    const json = await res.json();
    const data = json.data||{}; const items = data.items||[]; pages = data.pages||1;
    document.getElementById('resumen').textContent = `Página ${data.current_page||page} de ${pages} · Total: ${data.total||items.length}`;
    if(!items.length){ tbody.innerHTML = '<tr><td colspan="8" class="text-muted">Sin resultados</td></tr>'; return; }
    tbody.innerHTML = items.map(it=>{
      const acc = `<div class=\"btn-group btn-group-sm\">`+
        `<a class=\"btn btn-outline-primary\" href=\"ticket_detalle.php?id=${it.id}\" title=\"Ver detalle\"><i class=\"fas fa-eye\"></i></a>`+
      `</div>`;
      return `<tr>
        <td>${it.id}</td>
        <td>${escapeHtml(it.categoria_nombre||'')}</td>
        <td>${escapeHtml(it.resumen||'')}</td>
        <td>${estadoBadge(it.estado||'')}</td>
        <td>${escapeHtml(it.solicitante_nombre||'')}</td>
        <td>${escapeHtml(it.responsable_nombre||'')}</td>
        <td><small>${escapeHtml(it.fecha_creacion||'')}</small></td>
        <td class=\"text-end\">${acc}</td>
      </tr>`;
    }).join('');
  } catch(e){ tbody.innerHTML = '<tr><td colspan="7" class="text-danger">Error</td></tr>'; }
}

function escapeHtml(str){ return String(str||'').replace(/[&<>\"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

async function buscarUsuarios(q){
  const res = await fetch('../api/usuarios_buscar.php?q='+encodeURIComponent(q));
  const j = await res.json(); return (j && j.items) ? j.items : [];
}
async function buscarCategorias(q){
  const res = await fetch('../api/categorias_buscar.php?q='+encodeURIComponent(q));
  const j = await res.json(); return (j && j.items) ? j.items : [];
}

function abrirNuevo(){
  const html = `<div class=\"modal fade\" id=\"mNuevo\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Nuevo ticket</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\">`+
    `<div class=\"row g-2\">`+
      `<div class=\"col-md-6 position-relative\"><label class=\"form-label\">Solicitante</label><input id=\"t_solicitante\" class=\"form-control\" placeholder=\"Buscar usuario\" autocomplete=\"off\"><div id=\"t_solicitante_rs\" class=\"list-group position-absolute w-100\" style=\"z-index:1070; max-height:220px; overflow:auto;\"></div></div>`+
      `<div class=\"col-md-6 position-relative\"><label class=\"form-label\">Responsable</label><input id=\"t_responsable\" class=\"form-control\" placeholder=\"Buscar usuario\" autocomplete=\"off\"><div id=\"t_responsable_rs\" class=\"list-group position-absolute w-100\" style=\"z-index:1070; max-height:220px; overflow:auto;\"></div></div>`+
      `<div class=\"col-md-6 position-relative\"><label class=\"form-label\">Categoría</label><input id=\"t_categoria\" class=\"form-control\" placeholder=\"Buscar categoría\" autocomplete=\"off\"><div id=\"t_categoria_rs\" class=\"list-group position-absolute w-100\" style=\"z-index:1070; max-height:220px; overflow:auto;\"></div></div>`+
      `<div class=\"col-12\"><label class=\"form-label\">Resumen</label><input id=\"t_resumen\" class=\"form-control\" maxlength=\"200\"></div>`+
      `<div class=\"col-12\"><label class=\"form-label\">Descripción</label><textarea id=\"t_descripcion\" class=\"form-control\" rows=\"4\"></textarea></div>`+
    `</div>`+
  `</div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" id=\"btnCrear\">Crear</button></div></div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  const el = document.getElementById('mNuevo'); const modal = new bootstrap.Modal(el); modal.show();
  el.addEventListener('hidden.bs.modal', ()=>el.remove());
  let sel_solicitante = { id: <?php echo (int)($currentUser['id']??0); ?> };
  document.getElementById('t_solicitante').value = '<?php echo htmlspecialchars(($currentUser['nombre_completo']??''), ENT_QUOTES); ?>';
  let sel_responsable = null; let sel_categoria = null;

  // Autocomplete usuarios/categorías
  document.getElementById('t_solicitante').addEventListener('input', async (e)=>{
    const q=e.target.value.trim(); const rs = document.getElementById('t_solicitante_rs'); rs.innerHTML='';
    const items = await buscarUsuarios(q); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
    rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_solicitante={id:Number(a.dataset.id)}; document.getElementById('t_solicitante').value=a.dataset.name; rs.innerHTML=''; }));
  });
  document.getElementById('t_solicitante').addEventListener('focus', async ()=>{
    const rs = document.getElementById('t_solicitante_rs'); rs.innerHTML='';
    const items = await buscarUsuarios(''); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
    rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_solicitante={id:Number(a.dataset.id)}; document.getElementById('t_solicitante').value=a.dataset.name; rs.innerHTML=''; }));
  });
  document.getElementById('t_solicitante').addEventListener('blur', ()=>{ setTimeout(()=>{ const rs=document.getElementById('t_solicitante_rs'); if(rs) rs.innerHTML=''; }, 200); });
  document.getElementById('t_responsable').addEventListener('input', async (e)=>{
    const q=e.target.value.trim(); const rs = document.getElementById('t_responsable_rs'); rs.innerHTML='';
    const items = await buscarUsuarios(q); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
    rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_responsable={id:Number(a.dataset.id)}; document.getElementById('t_responsable').value=a.dataset.name; rs.innerHTML=''; }));
  });
  document.getElementById('t_responsable').addEventListener('focus', async ()=>{
    const rs = document.getElementById('t_responsable_rs'); rs.innerHTML='';
    const items = await buscarUsuarios(''); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    rs.innerHTML = items.map(u=>`<a href="#" class="list-group-item list-group-item-action" data-id="${u.id}" data-name="${u.nombre_completo}">${u.nombre_completo} <small class="text-muted">(${u.usuario})</small></a>`).join('');
    rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_responsable={id:Number(a.dataset.id)}; document.getElementById('t_responsable').value=a.dataset.name; rs.innerHTML=''; }));
  });
  document.getElementById('t_responsable').addEventListener('blur', ()=>{ setTimeout(()=>{ const rs=document.getElementById('t_responsable_rs'); if(rs) rs.innerHTML=''; }, 200); });
  document.getElementById('t_categoria').addEventListener('input', async (e)=>{
    const q=e.target.value.trim(); const rs = document.getElementById('t_categoria_rs'); rs.innerHTML='';
    const items = await buscarCategorias(q); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    rs.innerHTML = items.map(c=>`<a href="#" class="list-group-item list-group-item-action" data-id="${c.id}" data-name="${c.nombre}">${c.nombre}</a>`).join('');
    rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_categoria={id:Number(a.dataset.id)}; document.getElementById('t_categoria').value=a.dataset.name; rs.innerHTML=''; }));
  });
  document.getElementById('t_categoria').addEventListener('focus', async ()=>{
    const rs = document.getElementById('t_categoria_rs'); rs.innerHTML='';
    const items = await buscarCategorias(''); if(!items.length){ rs.innerHTML='<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    rs.innerHTML = items.map(c=>`<a href="#" class="list-group-item list-group-item-action" data-id="${c.id}" data-name="${c.nombre}">${c.nombre}</a>`).join('');
    rs.querySelectorAll('a').forEach(a=>a.addEventListener('mousedown',(ev)=>{ ev.preventDefault(); sel_categoria={id:Number(a.dataset.id)}; document.getElementById('t_categoria').value=a.dataset.name; rs.innerHTML=''; }));
  });
  document.getElementById('t_categoria').addEventListener('blur', ()=>{ setTimeout(()=>{ const rs=document.getElementById('t_categoria_rs'); if(rs) rs.innerHTML=''; }, 200); });
  document.getElementById('btnCrear').addEventListener('click', async ()=>{
    const payload = {
      solicitante_id: Number((sel_solicitante&&sel_solicitante.id)||0),
      responsable_id: Number((sel_responsable&&sel_responsable.id)||0),
      categoria_id: Number((sel_categoria&&sel_categoria.id)||0),
      resumen: document.getElementById('t_resumen').value||'',
      descripcion: document.getElementById('t_descripcion').value||''
    };
    try{
      const res = await fetch('../api/tickets_crear.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const j = await res.json();
      if(j && j.success){ modal.hide(); load(); } else { alert(j.message||'No se pudo crear'); }
    }catch(e){ alert('Error: '+e); }
  });
}

async function verDetalle(id){
  try{
    const res = await fetch('../api/tickets_detalle.php?id='+id);
    const j = await res.json(); if(!(j&&j.success)){ alert(j.message||'Error'); return; }
    const data = j.data||{}; const it = data.item||{}; const eventos = data.eventos||[];
    const infoRows = `
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

    const eventosOrdenados = (eventos||[]).slice().sort((a,b)=>{
      const da = new Date(a.fecha||0).getTime();
      const db = new Date(b.fecha||0).getTime();
      return db - da; // most recent first
    });
    const histHtml = eventosOrdenados.length ? (`<div class="timeline">${eventosOrdenados.map(e=>{
      const fecha = String(e.fecha||'');
      const usuario = String(e.usuario_nombre||'N/A');
      const tipo = String(e.tipo||'');
      const before = e.estado_anterior ? estadoBadge(e.estado_anterior) : '';
      const after  = e.estado_nuevo ? estadoBadge(e.estado_nuevo) : '';
      const trans = (before||after)? `${before} → ${after}` : '';
      const comentario = String(e.comentario||'');
      const title = tipo==='cambio_estado' ? 'Cambio de estado' : 'Comentario';
      return `
        <div class="timeline-item mb-3">
          <div class="d-flex align-items-start">
            <div class="timeline-marker me-3"><i class="fas fa-circle text-primary"></i></div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start"><h6 class="mb-1">${escapeHtml(title)}</h6><small class="text-muted">${escapeHtml(fecha)}</small></div>
              <div class="small text-muted mb-1">Por: ${escapeHtml(usuario)}</div>
              ${trans?(`<div class="small mb-1">${trans}</div>`):''}
              ${comentario?(`<div class="small">${escapeHtml(comentario)}</div>`):''}
            </div>
          </div>
        </div>`;
    }).join('')}</div>`) : '<div class="text-muted">Sin historial</div>';

    // Controles de transición
    const controls = buildTransitionControls(it);

    const html = `<div class="modal fade" id="mTicketDetalle" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Ticket #${it.id||''}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-lg-7">
            <h6>Información</h6>
            <div class="table-responsive"><table class="table table-sm"><tbody>${infoRows}</tbody></table></div>
            <hr>
            <h6>Agregar comentario</h6>
            <div class="input-group"><input id="newComment" class="form-control" placeholder="Escribe un comentario"><button class="btn btn-outline-primary" id="btnAddComment"><i class="fas fa-paper-plane"></i></button></div>
            <hr>
            <h6>Acciones</h6>
            ${controls}
          </div>
          <div class="col-lg-5">
            <h6>Historial</h6>
            ${histHtml}
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div></div></div>`;

    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('mTicketDetalle');
    const modal = new bootstrap.Modal(el); modal.show();
    el.addEventListener('hidden.bs.modal', ()=>el.remove());

    // Wire actions
    const commentBtn = el.querySelector('#btnAddComment');
    if (commentBtn) commentBtn.addEventListener('click', async ()=>{
      const txt = (el.querySelector('#newComment')?.value||'').trim();
      if (!txt){ alert('Escribe un comentario'); return; }
      await postJSON('../api/tickets_comentar.php',{ ticket_id: it.id, comentario: txt });
      modal.hide(); verDetalle(it.id); // recargar
    });
    el.querySelectorAll('[data-action="setState"]').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const estado = btn.getAttribute('data-state');
        const txt = (el.querySelector('#transitionComment')?.value||'').trim();
        if (!txt){ alert('Debes ingresar un comentario para cambiar el estado.'); return; }
        const r = await postJSON('../api/tickets_cambiar_estado.php',{ ticket_id: it.id, estado, comentario: txt });
        if (!(r&&r.success)) { alert((r&&r.message)||'No se pudo cambiar estado'); return; }
        modal.hide(); load(); verDetalle(it.id);
      });
    });
  }catch(e){ alert('Error: '+e); }
}

function buildTransitionControls(it){
  const uid = Number(window.CURRENT_USER_ID||0);
  const responsable = Number(it.responsable_id||0);
  const solicitante = Number(it.solicitante_id||0);
  const estado = String(it.estado||'');
  if (estado==='Aceptado') { return '<div class="text-muted">Ticket finalizado</div>'; }
  const isResp = uid===responsable; const isSol = uid===solicitante;
  let buttons = '';
  if (estado==='Backlog' && isResp){ buttons = ctlBtns(['En Curso','Rechazado']); }
  else if (estado==='En Curso' && isResp){ buttons = ctlBtns(['En Espera','Resuelto']); }
  else if (estado==='En Espera' && isResp){ buttons = ctlBtns(['En Curso','Resuelto']); }
  else if (estado==='Resuelto' && isSol){ buttons = ctlBtns(['Aceptado','Rechazado']); }
  const commentBox = `<div class="input-group mb-2"><span class="input-group-text"><i class="fas fa-comment"></i></span><input id="transitionComment" class="form-control" placeholder="Comentario para la transición (obligatorio)"></div>`;
  const helpText = `<div class="text-muted small mb-2">Selecciona a qué estado cambiar.</div>`;
  return `${buttons?commentBox+helpText:''}<div class="mb-2">${buttons||'<span class="text-muted">No hay acciones disponibles para tu usuario en este estado.</span>'}</div>`;
}
function ctlBtns(names){
  return `<div class="btn-group btn-group-sm" role="group">${names.map(n=>{
    let cls = 'btn btn-primary';
    if (n==='Rechazado') cls = 'btn btn-danger';
    else if (n==='En Espera') cls = 'btn btn-secondary';
    else if (n==='Resuelto') cls = 'btn btn-success';
    return `<button class=\"${cls}\" data-action=\"setState\" data-state=\"${n}\">${n}</button>`;
  }).join('')}</div>`;
}

async function postJSON(url, body){
  const res = await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  try { return await res.json(); } catch { return {success:false,message:'Respuesta inválida'}; }
}

document.addEventListener('DOMContentLoaded', ()=>{
  // Por defecto mostrar tickets donde soy responsable o solicitante
  const uid = Number(window.CURRENT_USER_ID||0);
  if(uid){
    sel_f_responsable = { id: uid };
    sel_f_solicitante = { id: uid };
    const inpr = document.getElementById('f_responsable');
    const inps = document.getElementById('f_solicitante');
    const name = '<?php echo htmlspecialchars(($currentUser['nombre_completo']??''), ENT_QUOTES); ?>';
    if (inpr) inpr.value = name;
    if (inps) inps.value = name;
  }
  load();
});
</script>


