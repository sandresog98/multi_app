<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('creditos');
$currentUser = $auth->getCurrentUser();
$pageTitle = 'Gestión Créditos - Listado';
$currentPage = 'creditos_listado';
include '../../../views/layouts/header.php';
?>

<style>
.timeline { position: relative; padding-left: 20px; }
.timeline-item { position: relative; }
.timeline-marker { position: absolute; left: -25px; top: 5px; }
.timeline-marker i { font-size: 12px; }
.timeline-item:not(:last-child)::after { content: ''; position: absolute; left: -19px; top: 20px; bottom: -20px; width: 2px; background-color: #e9ecef; }
</style>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-list me-2"></i>Listado de solicitudes</h1>
      </div>

      <div class="card mb-3"><div class="card-body">
        <form class="row g-2" onsubmit="return false;">
          <div class="col-md-4"><input id="q" class="form-control" placeholder="Buscar por identificación o nombre"></div>
          <div class="col-md-3">
            <select id="estado" class="form-select">
              <option value="">Todos los estados</option>
              <option>Creado</option>
              <option>Con Datacrédito</option>
              <option>Aprobado</option>
              <option>Rechazado</option>
              <option>Con Estudio</option>
              <option>Guardado</option>
            </select>
          </div>
          <div class="col-md-2 d-grid"><button id="btnBuscar" class="btn btn-outline-primary">Buscar</button></div>
        </form>
      </div></div>

      <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="small text-muted" id="resumen"></div>
          <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" id="prev">«</button><button class="btn btn-outline-secondary" id="next">»</button></div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead class="table-light"><tr>
              <th>Identificación</th>
              <th>Nombre</th>
              <th>Tipo</th>
              <th>Estado</th>
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
const currentRole = '<?php echo htmlspecialchars($currentUser['rol'] ?? '', ENT_QUOTES); ?>';
const canApprove = (currentRole === 'admin' || currentRole === 'lider');
let page=1,pages=1;
document.getElementById('btnBuscar').addEventListener('click',()=>{page=1;load();});
document.getElementById('prev').addEventListener('click',()=>{if(page>1){page--;load();}});
document.getElementById('next').addEventListener('click',()=>{if(page<pages){page++;load();}});

function sinSeg(s){ if(!s) return ''; const m=String(s).match(/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/); return m?`${m[1]} ${m[2]}`:s; }
function fmtCOP(n){ const v = Number(n||0); return new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',maximumFractionDigits:0}).format(v); }

async function load(){
  const q=document.getElementById('q').value; const estado=document.getElementById('estado').value;
  const params = new URLSearchParams({ q, estado, page, limit: 10, sort_by: 'fecha_creacion', sort_dir: 'DESC' });
  const tbody=document.getElementById('tbody');
  tbody.innerHTML='<tr><td colspan="6" class="text-muted">Cargando…</td></tr>';
  try{
    const res = await fetch('../api/solicitudes_listar.php?'+params.toString());
    const json = await res.json();
    const data = json.data||{}; const items=data.items||[]; pages=data.pages||1;
    document.getElementById('resumen').textContent=`Página ${data.current_page||page} de ${pages} · Total: ${data.total||items.length}`;
    if(!items.length){ tbody.innerHTML='<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>'; return; }
    tbody.innerHTML = items.map(it=>{
      const acc = `<div class=\"btn-group btn-group-sm\">`+
        `<button class=\"btn btn-outline-primary\" onclick=\"abrirDetalle(${it.id})\" title=\"Ver detalle\"><i class=\"fas fa-eye\"></i></button>`+
        (it.estado==='Creado'?`<button class=\"btn btn-outline-secondary\" onclick=\"abrirModalArchivo(${it.id},'Con Datacrédito','archivo_datacredito')\" title=\"Adjuntar Datacrédito (PDF)\"><i class=\"fas fa-file-upload\"></i></button>`:'')+
        (it.estado==='Aprobado'?`<button class=\"btn btn-outline-secondary\" onclick=\"abrirModalArchivo(${it.id},'Con Estudio','archivo_estudio')\" title=\"Adjuntar Estudio (PDF)\"><i class=\"fas fa-search\"></i></button>`:'')+
        (it.estado==='Con Estudio'?`<button class=\"btn btn-outline-secondary\" onclick=\"abrirModalGuardado(${it.id})\" title=\"Adjuntar Pagaré y Amortización (PDF)\"><i class=\"fas fa-save\"></i></button>`:'')+
        (canApprove && it.estado==='Con Datacrédito'?`<button class=\"btn btn-outline-success\" onclick=\"cambiar(${it.id},'Aprobado')\" title=\"Aprobar\"><i class=\"fas fa-check\"></i></button>`:'')+
        (canApprove && it.estado==='Con Datacrédito'?`<button class=\"btn btn-outline-danger\" onclick=\"cambiar(${it.id},'Rechazado')\" title=\"Rechazar\"><i class=\"fas fa-times\"></i></button>`:'')+
      `</div>`;
      return `<tr>
        <td>${escapeHtml(it.identificacion)}</td>
        <td>${escapeHtml(it.nombres)}</td>
        <td>${escapeHtml(it.tipo)}</td>
        <td><span class=\"badge bg-${estadoColor(it.estado)}\">${escapeHtml(it.estado)}</span></td>
        <td><small>${escapeHtml(sinSeg(it.fecha_creacion))}</small></td>
        <td class=\"text-end\">${acc}</td>
      </tr>`;
    }).join('');
  }catch(e){ tbody.innerHTML='<tr><td colspan="6" class="text-danger">Error</td></tr>'; }
}

function estadoColor(est){
  switch(est){
    case 'Creado': return 'secondary';
    case 'Con Datacrédito': return 'info';
    case 'Aprobado': return 'success';
    case 'Rechazado': return 'danger';
    case 'Con Estudio': return 'warning';
    case 'Guardado': return 'primary';
    default: return 'secondary';
  }
}

function escapeHtml(str){ return String(str||'').replace(/[&<>\"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

async function cambiar(id,estado){
  try{
    const res = await fetch('../api/solicitud_cambiar_estado.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,estado})});
    const j = await res.json();
    if(j && j.success){ load(); } else { alert(j.message||'No se pudo cambiar'); }
  }catch(e){ alert('Error: '+e); }
}

document.addEventListener('DOMContentLoaded', load);

// Modales de archivos
function abrirModalArchivo(id,estado,campo){
  const html = `<div class=\"modal fade\" id=\"mSubir\" tabindex=\"-1\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">${estado}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"mb-3\"><label class=\"form-label\">Adjuntar (PDF)</label><input type=\"file\" class=\"form-control\" id=\"fileCampo\" accept=\".pdf\"></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" id=\"btnSubir\">Guardar</button></div></div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  const modalEl = document.getElementById('mSubir'); const modal = new bootstrap.Modal(modalEl); modal.show();
  modalEl.addEventListener('hidden.bs.modal', ()=>modalEl.remove());
  document.getElementById('btnSubir').addEventListener('click', async ()=>{
    const f = document.getElementById('fileCampo').files[0]; if(!f){alert('Adjunte PDF'); return;}
    if (f.size > 5*1024*1024) { alert('Máximo 5MB'); return; }
    if (!/\.pdf$/i.test(f.name)) { alert('Solo PDF'); return; }
    const fd = new FormData(); fd.append('id', id); fd.append('estado', estado); fd.append(campo, f);
    const res = await fetch('../api/solicitud_cambiar_estado.php', { method: 'POST', body: fd });
    const j = await res.json(); if (j && j.success) { modal.hide(); load(); } else { alert(j.message||'No se pudo guardar'); }
  });
}

function abrirModalGuardado(id){
  const html = `<div class=\"modal fade\" id=\"mGuardar\" tabindex=\"-1\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Adjuntar Pagaré y Amortización</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"mb-3\"><label class=\"form-label\">Pagaré (PDF)</label><input type=\"file\" class=\"form-control\" id=\"pagare\" accept=\".pdf\"></div><div class=\"mb-3\"><label class=\"form-label\">Amortización (PDF)</label><input type=\"file\" class=\"form-control\" id=\"amort\" accept=\".pdf\"></div></div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" id=\"btnGuardarArch\">Guardar</button></div></div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  const modalEl = document.getElementById('mGuardar'); const modal = new bootstrap.Modal(modalEl); modal.show();
  modalEl.addEventListener('hidden.bs.modal', ()=>modalEl.remove());
  document.getElementById('btnGuardarArch').addEventListener('click', async ()=>{
    const p = document.getElementById('pagare').files[0]; const a = document.getElementById('amort').files[0];
    if(!p||!a){alert('Adjunte ambos archivos'); return;}
    if (p.size>5*1024*1024 || a.size>5*1024*1024){ alert('Máx 5MB'); return; }
    if (!/\.pdf$/i.test(p.name) || !/\.pdf$/i.test(a.name)) { alert('Solo PDF'); return; }
    const fd = new FormData(); fd.append('id', id); fd.append('estado','Guardado'); fd.append('archivo_pagare_pdf', p); fd.append('archivo_amortizacion', a);
    const res = await fetch('../api/solicitud_cambiar_estado.php', { method: 'POST', body: fd });
    const j = await res.json(); if (j && j.success) { modal.hide(); load(); } else { alert(j.message||'No se pudo guardar'); }
  });
}

async function abrirDetalle(id){
  try{
    const res = await fetch('../api/solicitudes_detalle.php?id='+id);
    const j = await res.json(); if(!(j&&j.success)){ alert(j.message||'Error'); return; }
    const it = j.item||{}; const hist = j.historial||[];
    const labels = {
      identificacion:'Identificación', nombres:'Nombre', celular:'Celular', email:'Correo', monto_deseado:'Monto deseado', tipo:'Tipo', estado:'Estado', fecha_creacion:'F. creación', fecha_actualizacion:'F. actualización'
    };
    const pairs = [
      ['identificacion','nombres'],
      ['celular','email'],
      ['monto_deseado','tipo'],
      ['estado','fecha_creacion'],
      ['fecha_actualizacion', null]
    ];
    const infoRows = pairs.map(([k1,k2])=>{
      const v1 = k1==='monto_deseado' ? fmtCOP(it[k1]) : (k1==='fecha_creacion'||k1==='fecha_actualizacion'? sinSeg(it[k1]) : String(it[k1]||''));
      let right = '';
      if (k2) {
        const v2 = k2==='monto_deseado' ? fmtCOP(it[k2]) : (k2==='fecha_creacion'||k2==='fecha_actualizacion'? sinSeg(it[k2]) : String(it[k2]||''));
        right = `<th class=\"w-25\">${labels[k2]}</th><td>${escapeHtml(v2)}</td>`;
      } else {
        right = '<th class=\"w-25\"></th><td></td>';
      }
      return `<tr><th class=\"w-25\">${labels[k1]}</th><td>${escapeHtml(v1)}</td>${right}</tr>`;
    }).join('');

    // Sección Archivos ordenada según flujo
    const grupos = [
      { titulo: 'Datacrédito', campos: ['archivo_datacredito'] },
      { titulo: 'Estudio', campos: ['archivo_estudio'] },
      { titulo: 'Pagaré y Amortización', campos: ['archivo_pagare_pdf','archivo_amortizacion'] },
      { titulo: 'Dependiente', campos: ['dep_nomina_1','dep_nomina_2','dep_cert_laboral','dep_simulacion_pdf'] },
      { titulo: 'Independiente', campos: ['ind_decl_renta','ind_simulacion_pdf','ind_codeudor_nomina_1','ind_codeudor_nomina_2','ind_codeudor_cert_laboral'] }
    ];
    const nombresCampo = {
      archivo_datacredito:'Datacrédito (PDF)', archivo_estudio:'Estudio (PDF)', archivo_pagare_pdf:'Pagaré (PDF)', archivo_amortizacion:'Amortización (PDF)',
      dep_nomina_1:'Desprendible nómina (1)', dep_nomina_2:'Desprendible nómina (2)', dep_cert_laboral:'Certificación laboral', dep_simulacion_pdf:'Simulación pagos (PDF)',
      ind_decl_renta:'Declaración de renta', ind_simulacion_pdf:'Simulación pagos (PDF)', ind_codeudor_nomina_1:'Nómina codeudor (1)', ind_codeudor_nomina_2:'Nómina codeudor (2)', ind_codeudor_cert_laboral:'Certificación codeudor'
    };
    const bloquesArchivos = grupos.map(g=>{
      const items = g.campos.filter(k=>it[k]).map(k=>{
        const href = escapeHtml(it[k]);
        const label = nombresCampo[k];
        return `<div class=\"d-flex align-items-center\"><a class=\"btn btn-sm btn-outline-primary me-2\" href=\"${href}\" target=\"_blank\" title=\"Ver\"><i class=\"fas fa-eye\"></i></a><span>${label}</span></div>`;
      });
      if(!items.length) return '';
      // construir tabla de dos columnas
      const filas = [];
      for (let i=0; i<items.length; i+=2) {
        const left = items[i];
        const right = items[i+1] || '';
        filas.push(`<tr><td class=\"w-50\">${left}</td><td class=\"w-50\">${right}</td></tr>`);
      }
      return `<div class=\"mb-2\"><h6 class=\"mb-1\">${g.titulo}</h6><div class=\"table-responsive\"><table class=\"table table-sm\"><tbody>${filas.join('')}</tbody></table></div></div>`;
    }).join('') || '<div class=\"text-muted\">Sin archivos</div>';

    // Historial como timeline (igual que Boletería)
    const histHtml = hist.length ? (`<div class=\"timeline\">${hist.map(h=>{
      const fecha = escapeHtml(h.fecha||'');
      const usuario = escapeHtml(h.usuario||'N/A');
      const accion = escapeHtml(h.accion||'');
      const ea = escapeHtml(h.estado_anterior||'-');
      const en = escapeHtml(h.estado_nuevo||'-');
      const archivo = (h.archivo_ruta?`<a href=\"${escapeHtml(h.archivo_ruta)}\" target=\"_blank\">${escapeHtml(h.archivo_campo||'archivo')}</a>`:'');
      return `
        <div class=\"timeline-item mb-3\">
          <div class=\"d-flex align-items-start\">
            <div class=\"timeline-marker me-3\"><i class=\"fas fa-circle text-primary\"></i></div>
            <div class=\"flex-grow-1\">
              <div class=\"d-flex justify-content-between align-items-start\"><h6 class=\"mb-1\">${accion}</h6><small class=\"text-muted\">${fecha}</small></div>
              <div class=\"small text-muted mb-1\">Por: ${usuario}</div>
              <div class=\"small\">${ea} → ${en} ${archivo?('· '+archivo):''}</div>
            </div>
          </div>
        </div>`;
    }).join('')}</div>`) : '<div class=\"text-muted\">Sin historial</div>';

    const html = `<div class=\"modal fade\" id=\"mDetalle\" tabindex=\"-1\"><div class=\"modal-dialog modal-lg\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">Detalle solicitud #${it.id}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\">`+
      `<h6>Información</h6><div class=\"table-responsive\"><table class=\"table table-sm\"><tbody>${infoRows}</tbody></table></div>`+
      `<h6>Archivos</h6>${bloquesArchivos}`+
      `<hr><h6>Historial</h6>${histHtml}`+
      `</div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cerrar</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('mDetalle'); const modal = new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal',()=>el.remove());
  }catch(e){ alert('Error: '+e); }
}
</script>


