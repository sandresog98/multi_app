<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('ticketera');
$currentUser = $auth->getCurrentUser();
$pageTitle = 'Ticketera - Categorías';
$currentPage = 'ticketera_categorias';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-tags me-2"></i>Categorías</h1>
        <button class="btn btn-primary btn-sm" id="btnNueva"><i class="fas fa-plus me-1"></i>Nueva</button>
      </div>

      <div class="card mb-3"><div class="card-body">
        <form class="row g-2" onsubmit="return false;">
          <div class="col-md-4"><input id="q" class="form-control" placeholder="Buscar por nombre"></div>
          <div class="col-md-3"><select id="estado" class="form-select"><option value="">Todas</option><option value="1">Activas</option><option value="0">Inactivas</option></select></div>
          <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" id="btnBuscar">Buscar</button></div>
        </form>
      </div></div>

      <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="small text-muted" id="resumen"></div>
          <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" id="prev">«</button><button class="btn btn-outline-secondary" id="next">»</button></div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead class="table-light"><tr><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Fecha</th><th class="text-end">Acciones</th></tr></thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
let page=1,pages=1;
document.getElementById('btnBuscar').addEventListener('click',()=>{page=1;load();});
document.getElementById('prev').addEventListener('click',()=>{if(page>1){page--;load();}});
document.getElementById('next').addEventListener('click',()=>{if(page<pages){page++;load();}});
document.getElementById('btnNueva').addEventListener('click', ()=> abrirForm());

function badgeEstado(v){ return v?'<span class="badge bg-success">Activa</span>':'<span class="badge bg-secondary">Inactiva</span>'; }

async function load(){
  const q=document.getElementById('q').value; const estado=document.getElementById('estado').value;
  const tbody=document.getElementById('tbody'); tbody.innerHTML='<tr><td colspan="5" class="text-muted">Cargando…</td></tr>';
  const params = new URLSearchParams({ q, estado, page, limit: 10 });
  try{
    const res = await fetch('../api/categorias_listar.php?'+params.toString());
    const json = await res.json();
    const data = json.data||{}; const items=data.items||[]; pages=data.pages||1;
    document.getElementById('resumen').textContent=`Página ${data.current_page||page} de ${pages} · Total: ${data.total||items.length}`;
    if(!items.length){ tbody.innerHTML='<tr><td colspan="5" class="text-muted">Sin resultados</td></tr>'; return; }
    tbody.innerHTML = items.map(it=>{
      const acc = `<div class=\"btn-group btn-group-sm\"><button class=\"btn btn-outline-primary\" onclick=\"abrirForm(${it.id},'${escapeHtml(it.nombre)}','${escapeHtml(it.descripcion||'')}',${it.estado_activo?1:0})\"><i class=\"fas fa-edit\"></i></button></div>`;
      return `<tr><td>${escapeHtml(it.nombre)}</td><td>${escapeHtml(it.descripcion||'')}</td><td>${badgeEstado(it.estado_activo)}</td><td><small>${escapeHtml(it.fecha_creacion||'')}</small></td><td class=\"text-end\">${acc}</td></tr>`;
    }).join('');
  }catch(e){ tbody.innerHTML='<tr><td colspan="5" class="text-danger">Error</td></tr>'; }
}

function escapeHtml(str){ return String(str||'').replace(/[&<>\"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }

function abrirForm(id=0,nombre='',descripcion='',estado=1){
  const title = id? 'Editar categoría':'Nueva categoría';
  const checked = estado? 'checked':'';
  const html = `<div class=\"modal fade\" id=\"mCat\" tabindex=\"-1\"><div class=\"modal-dialog\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\">${title}</h5><button class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\">`+
    `<div class=\"mb-2\"><label class=\"form-label\">Nombre</label><input id=\"cat_nombre\" class=\"form-control\" value=\"${nombre}\" required></div>`+
    `<div class=\"mb-2\"><label class=\"form-label\">Descripción</label><textarea id=\"cat_desc\" class=\"form-control\" rows=\"3\">${descripcion}</textarea></div>`+
    `<div class=\"form-check form-switch\"><input class=\"form-check-input\" type=\"checkbox\" id=\"cat_estado\" ${checked}><label class=\"form-check-label\" for=\"cat_estado\">Activa</label></div>`+
  `</div><div class=\"modal-footer\"><button class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancelar</button><button class=\"btn btn-primary\" id=\"btnGuardar\">Guardar</button></div></div></div></div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  const el = document.getElementById('mCat'); const modal = new bootstrap.Modal(el); modal.show(); el.addEventListener('hidden.bs.modal', ()=>el.remove());
  document.getElementById('btnGuardar').addEventListener('click', async ()=>{
    const payload = { id, nombre: document.getElementById('cat_nombre').value||'', descripcion: document.getElementById('cat_desc').value||'', estado_activo: document.getElementById('cat_estado').checked };
    try{
      const res = await fetch('../api/categorias_guardar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const j = await res.json(); if(j && j.success){ modal.hide(); load(); } else { alert(j.message||'No se pudo guardar'); }
    }catch(e){ alert('Error: '+e); }
  });
}

document.addEventListener('DOMContentLoaded', load);
</script>


