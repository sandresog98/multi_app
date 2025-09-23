<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('tienda.clientes');
$currentUser = $auth->getCurrentUser();

$pageTitle = 'Tienda - Clientes';
$currentPage = 'tienda_clientes';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-user-friends me-2"></i>Clientes</h1>
      </div>

      <div class="d-flex justify-content-end mb-2"><button class="btn btn-primary" type="button" onclick="nuevoCli()"><i class="fas fa-plus me-1"></i>Nuevo cliente</button></div>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr><th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Email</th><th>F. creación</th><th class="text-end">Acciones</th></tr></thead>
            <tbody id="tblCli"></tbody>
          </table>
        </div>
      </div></div>

    </main>
  </div>
</div>

<!-- Modal cliente -->
<div class="modal fade" id="mCliente" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Cliente</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form class="row g-2" onsubmit="return false;" id="formCli">
      <input type="hidden" id="cli_id">
      <div class="col-12"><input class="form-control" id="cli_nombre" placeholder="Nombre" required></div>
      <div class="col-12"><input class="form-control" id="cli_doc" placeholder="NIT/Cédula" required></div>
      <div class="col-12"><input class="form-control" id="cli_tel" placeholder="Teléfono"></div>
      <div class="col-12"><input class="form-control" id="cli_mail" placeholder="Correo"></div>
    </form>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" onclick="guardarCli()">Guardar</button></div>
</div></div></div>

<script>
function escapeHtml(str){ return String(str||'').replace(/[&<>"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); }
async function loadCli(){ const res=await fetch('../api/clientes.php?action=listar'); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; } renderCli(j.items||[]); }
function renderCli(list){ const body=document.getElementById('tblCli'); body.innerHTML=''; if(!list.length){ body.innerHTML='<tr><td colspan="6" class="text-muted">Sin clientes</td></tr>'; return; } list.forEach(it=>{ const tr=document.createElement('tr'); tr.innerHTML=`<td>${escapeHtml(it.nombre||'')}</td><td>${escapeHtml(it.nit_cedula||'')}</td><td>${escapeHtml(it.telefono||'')}</td><td>${escapeHtml(it.email||'')}</td><td>${escapeHtml(it.fecha_creacion||'')}</td><td class="text-end"><button class="btn btn-sm btn-outline-info" onclick='editCli(${JSON.stringify(it)})'><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick='delCli(${Number(it.id)})'><i class="fas fa-trash"></i></button></td>`; body.appendChild(tr); }); }
async function guardarCli(){ const id=document.getElementById('cli_id').value||''; const nombre=document.getElementById('cli_nombre').value.trim(); const doc=document.getElementById('cli_doc').value.trim(); const tel=document.getElementById('cli_tel').value.trim(); const mail=document.getElementById('cli_mail').value.trim(); if(!nombre||!doc){ alert('Nombre y NIT/Cédula son requeridos'); return; } const fd=new FormData(); fd.append('action','guardar'); fd.append('id',id); fd.append('nombre',nombre); fd.append('nit_cedula',doc); fd.append('telefono',tel); fd.append('email',mail); const res=await fetch('../api/clientes.php',{method:'POST',body:fd}); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; } limpiarCli(); const el=document.getElementById('mCliente'); const modal=bootstrap.Modal.getInstance(el); if(modal) modal.hide(); loadCli(); }
function editCli(it){ document.getElementById('cli_id').value=it.id; document.getElementById('cli_nombre').value=it.nombre||''; document.getElementById('cli_doc').value=it.nit_cedula||''; document.getElementById('cli_tel').value=it.telefono||''; document.getElementById('cli_mail').value=it.email||''; const m=new bootstrap.Modal(document.getElementById('mCliente')); m.show(); }
async function delCli(id){ if(!confirm('¿Eliminar cliente?')) return; const fd=new FormData(); fd.append('action','eliminar'); fd.append('id',id); const res=await fetch('../api/clientes.php',{method:'POST',body:fd}); const j=await res.json(); if(!j.success){ alert(j.message||'Error'); return; } loadCli(); }
function limpiarCli(){ document.getElementById('cli_id').value=''; document.getElementById('cli_nombre').value=''; document.getElementById('cli_doc').value=''; document.getElementById('cli_tel').value=''; document.getElementById('cli_mail').value=''; }
function nuevoCli(){ limpiarCli(); const m=new bootstrap.Modal(document.getElementById('mCliente')); m.show(); }
document.addEventListener('DOMContentLoaded', loadCli);
</script>

<?php include '../../../views/layouts/footer.php'; ?>


