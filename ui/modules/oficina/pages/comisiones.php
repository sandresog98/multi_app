<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Comision.php';

$auth = new AuthController();
$auth->requireModule('oficina.comisiones');
$currentUser = $auth->getCurrentUser();

// Cargar comisiones para mostrar
$model = new Comision();
$page = max(1, (int)($_GET['page'] ?? 1));
$filtros = [];
if (!empty($_GET['asociado_inicial'])) $filtros['asociado_inicial'] = $_GET['asociado_inicial'];
if (!empty($_GET['asociado_referido'])) $filtros['asociado_referido'] = $_GET['asociado_referido'];
if (!empty($_GET['fecha_desde'])) $filtros['fecha_desde'] = $_GET['fecha_desde'];
if (!empty($_GET['fecha_hasta'])) $filtros['fecha_hasta'] = $_GET['fecha_hasta'];

$data = $model->listar($page, 20, $filtros);
$comisiones = $data['comisiones'] ?? [];

$pageTitle = 'Comisiones - Oficina';
$currentPage = 'oficina_comisiones';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-percentage me-2"></i>Comisiones</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalComision">
          <i class="fas fa-plus me-1"></i>Crear Comisión
        </button>
      </div>

      <!-- Filtros -->
      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" class="row g-2">
            <div class="col-md-3">
              <input type="text" class="form-control" name="asociado_inicial" placeholder="Asociado inicial" value="<?php echo htmlspecialchars($_GET['asociado_inicial'] ?? ''); ?>" />
            </div>
            <div class="col-md-3">
              <input type="text" class="form-control" name="asociado_referido" placeholder="Asociado referido" value="<?php echo htmlspecialchars($_GET['asociado_referido'] ?? ''); ?>" />
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" name="fecha_desde" value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>" />
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" name="fecha_hasta" value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>" />
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Listado de comisiones -->
      <div class="card">
        <div class="card-body">
          <?php if (empty($comisiones)): ?>
            <div class="text-center text-muted py-4">
              <i class="fas fa-percentage fa-3x mb-3"></i>
              <p>No hay comisiones registradas</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Asociado Inicial</th>
                    <th>Asociado Referido</th>
                    <th>Fecha</th>
                    <th>Valor</th>
                    <th>Observaciones</th>
                    <th>Creado por</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($comisiones as $comision): ?>
                    <tr>
                      <td>
                        <strong><?php echo htmlspecialchars($comision['asociado_inicial_cedula']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($comision['inicial_nombre'] ?? ''); ?></small>
                      </td>
                      <td>
                        <strong><?php echo htmlspecialchars($comision['asociado_referido_cedula']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($comision['referido_nombre'] ?? ''); ?></small>
                      </td>
                      <td><?php echo date('d/m/Y', strtotime($comision['fecha_comision'])); ?></td>
                      <td><strong>$<?php echo number_format($comision['valor_ganado'], 0, ',', '.'); ?></strong></td>
                      <td><?php echo htmlspecialchars($comision['observaciones'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($comision['creado_por_nombre'] ?? ''); ?></td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editarComision(<?php echo $comision['id']; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarComision(<?php echo $comision['id']; ?>)">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Modal para crear/editar comisión -->
<div class="modal fade" id="modalComision" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalComisionTitle">Crear Comisión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formComision" class="row g-3" autocomplete="off">
          <input type="hidden" id="comisionId" />
          <div class="col-md-6 position-relative">
            <label class="form-label">Asociado inicial</label>
            <input type="text" class="form-control" id="asociadoInicialInput" placeholder="Cédula o nombre" />
            <input type="hidden" id="asociadoInicial" />
            <div id="resIni" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
          </div>
          <div class="col-md-6 position-relative">
            <label class="form-label">Asociado referido</label>
            <input type="text" class="form-control" id="asociadoReferidoInput" placeholder="Cédula o nombre" />
            <input type="hidden" id="asociadoReferido" />
            <div id="resRef" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fecha de comisión</label>
            <input type="date" class="form-control" id="fechaComision" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Valor ganado</label>
            <input type="number" step="0.01" min="0" class="form-control" id="valorGanado" required />
          </div>
          <div class="col-12">
            <label class="form-label">Observaciones (opcional)</label>
            <input type="text" class="form-control" id="observaciones" />
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="guardarComision()">
          <i class="fas fa-save me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const buscarUrl = '<?php echo getBaseUrl(); ?>modules/oficina/api/buscar_asociados.php?q=';

  function setupAutocomplete(inputId, hiddenId, resultsId){
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const cont = document.getElementById(resultsId);
    let debounceTimer = null;
    input.addEventListener('input', function(){
      const q = (input.value||'').trim();
      hidden.value = '';
      clearTimeout(debounceTimer);
      if (q.length < 2) { cont.innerHTML = ''; return; }
      cont.innerHTML = '<div class="list-group-item text-muted">Buscando…</div>';
      debounceTimer = setTimeout(async ()=>{
        try{
          const res = await fetch(buscarUrl + encodeURIComponent(q));
          const data = await res.json();
          const items = data.items || [];
          if (!items.length){ cont.innerHTML = '<div class="list-group-item text-muted">Sin resultados</div>'; return; }
          cont.innerHTML = items.map(it=>`<button type="button" class="list-group-item list-group-item-action" data-cedula="${it.cedula}">${it.cedula} — ${it.nombre}</button>`).join('');
          cont.querySelectorAll('button').forEach(btn=>{
            btn.addEventListener('click', ()=>{
              hidden.value = btn.getAttribute('data-cedula');
              input.value = btn.textContent;
              cont.innerHTML = '';
            });
          });
        }catch(_){ cont.innerHTML = '<div class="list-group-item text-danger">Error de búsqueda</div>'; }
      }, 300);
    });
    document.addEventListener('click', (e)=>{ if (!cont.contains(e.target) && e.target !== input) { cont.innerHTML=''; }});
  }

  setupAutocomplete('asociadoInicialInput','asociadoInicial','resIni');
  setupAutocomplete('asociadoReferidoInput','asociadoReferido','resRef');

  function limpiarFormulario() {
    document.getElementById('formComision').reset();
    document.getElementById('comisionId').value = '';
    document.getElementById('asociadoInicial').value = '';
    document.getElementById('asociadoReferido').value = '';
    document.getElementById('modalComisionTitle').textContent = 'Crear Comisión';
  }

  window.editarComision = function(id) {
    fetch(`../api/comisiones_obtener.php?id=${id}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.comision) {
          const c = data.comision;
          document.getElementById('comisionId').value = c.id;
          document.getElementById('asociadoInicial').value = c.asociado_inicial_cedula;
          document.getElementById('asociadoInicialInput').value = `${c.asociado_inicial_cedula} — ${c.inicial_nombre || ''}`;
          document.getElementById('asociadoReferido').value = c.asociado_referido_cedula;
          document.getElementById('asociadoReferidoInput').value = `${c.asociado_referido_cedula} — ${c.referido_nombre || ''}`;
          document.getElementById('fechaComision').value = c.fecha_comision;
          document.getElementById('valorGanado').value = c.valor_ganado;
          document.getElementById('observaciones').value = c.observaciones || '';
          document.getElementById('modalComisionTitle').textContent = 'Editar Comisión';
          new bootstrap.Modal(document.getElementById('modalComision')).show();
        } else {
          alert('Error al cargar la comisión');
        }
      })
      .catch(e => alert('Error: ' + e.message));
  }

  window.eliminarComision = async function(id) {
    if (!confirm('¿Está seguro de que desea eliminar esta comisión?')) return;
    
    try {
      const fd = new FormData();
      fd.append('id', id);
      const res = await fetch('../api/comisiones_eliminar.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const j = await res.json();
      if (res.ok && j.success) {
        alert('Comisión eliminada');
        location.reload();
      } else {
        alert(j.message || 'Error al eliminar');
      }
    } catch (e) {
      alert('Error: ' + e.message);
    }
  }

  window.guardarComision = async function(){
    const id = document.getElementById('comisionId').value;
    const aIni = (document.getElementById('asociadoInicial').value||'').trim();
    const aRef = (document.getElementById('asociadoReferido').value||'').trim();
    const fecha = (document.getElementById('fechaComision').value||'').trim();
    const valor = (document.getElementById('valorGanado').value||'').trim();
    const obs = (document.getElementById('observaciones').value||'').trim();
    
    if (!aIni || !aRef) { alert('Debe seleccionar los dos asociados.'); return; }
    if (aIni === aRef) { alert('Los asociados no pueden ser el mismo.'); return; }
    if (!fecha) { alert('La fecha de comisión es obligatoria.'); return; }
    const v = Number(valor);
    if (!(v>0)) { alert('El valor ganado debe ser mayor que 0.'); return; }
    
    const btn = document.querySelector('#modalComision .btn-primary');
    try{
      if (btn){ btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Guardando…'; }
      
      const fd = new FormData();
      fd.append('asociado_inicial', aIni);
      fd.append('asociado_referido', aRef);
      fd.append('fecha_comision', fecha);
      fd.append('valor_ganado', String(v));
      fd.append('observaciones', obs);
      
      let url = '../api/comisiones_guardar.php';
      if (id) {
        url = '../api/comisiones_editar.php';
        fd.append('id', id);
      }
      
      const res = await fetch(url, { method:'POST', body: fd, credentials:'same-origin' });
      const j = await res.json();
      
      if (!(res.ok && j && j.success)) { 
        alert(j && j.message ? j.message : 'Error al guardar'); 
        if (btn){btn.disabled=false; btn.innerHTML='<i class="fas fa-save me-1"></i>Guardar';} 
        return; 
      }
      
      alert(id ? 'Comisión actualizada' : 'Comisión registrada');
      bootstrap.Modal.getInstance(document.getElementById('modalComision')).hide();
      location.reload();
    }catch(e){ 
      alert('Error: '+String(e)); 
    }
    finally{ 
      if (btn){ btn.disabled=false; btn.innerHTML='<i class="fas fa-save me-1"></i>Guardar'; } 
    }
  }

  // Limpiar formulario al cerrar modal
  document.getElementById('modalComision').addEventListener('hidden.bs.modal', limpiarFormulario);
});
</script>


