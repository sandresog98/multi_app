<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Cargas.php';

$auth = new AuthController();
$auth->requireModule('oficina.cargas');
$currentUser = $auth->getCurrentUser();
$model = new Cargas();

$pageTitle = 'Cargas - Oficina';
$currentPage = 'cargas';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-file-upload me-2"></i>Cargas</h1>
        <div class="d-flex gap-2">
          <button class="btn btn-success" id="btnEjecutarWorker" title="Ejecutar worker de Python">
            <i class="fas fa-play me-1"></i>Ejecutar Worker
          </button>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><strong>Sifone</strong></div>
            <div class="card-body">
              <form id="formSifone" class="row g-2" enctype="multipart/form-data">
                <div class="col-12">
                  <label class="form-label">Tipo de archivo</label>
                  <select name="tipo" class="form-select" required>
                    <option value="sifone_libro">Libro de Asociados</option>
                    <option value="sifone_cartera_aseguradora">Cartera Aseguradora</option>
                    <option value="sifone_cartera_mora">Cartera Mora</option>
                    <option value="sifone_datacredito">Datacredito</option>
                    <option value="sifone_balance_prueba">Balance prueba</option>
                    <option value="sifone_movimientos_tributarios">Auxiliar de Movimientos Tributarios</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Archivo (.xls/.xlsx)</label>
                  <input type="file" name="archivo" class="form-control" accept=".xls,.xlsx" required>
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-primary"><i class="fas fa-upload me-1"></i>Subir y procesar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><strong>Pagos (PSE / Confiar)</strong></div>
            <div class="card-body">
              <form id="formPagos" class="row g-2" enctype="multipart/form-data">
                <div class="col-12">
                  <label class="form-label">Tipo de archivo</label>
                  <select name="tipo" class="form-select" required>
                    <option value="pagos_pse">PSE</option>
                    <option value="pagos_confiar">Confiar</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Archivo (.xls/.xlsx)</label>
                  <input type="file" name="archivo" class="form-control" accept=".xls,.xlsx" required>
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-primary"><i class="fas fa-upload me-1"></i>Subir y procesar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <strong>Cargas recientes</strong>
          <button class="btn btn-sm btn-outline-secondary" id="btnRefrescar"><i class="fas fa-sync"></i></button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light"><tr><th>ID</th><th>Tipo</th><th>Archivo</th><th>Estado</th><th>Mensaje</th><th>Fecha carga</th></tr></thead>
              <tbody id="cargasTbl"></tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="small text-muted" id="cargasPageInfo"></div>
              <div class="btn-group btn-group-sm" role="group" aria-label="Paginación">
                <button class="btn btn-outline-secondary" id="cargasPrev">Anterior</button>
                <button class="btn btn-outline-secondary" id="cargasNext">Siguiente</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal para mostrar la salida del worker -->
      <div class="modal fade" id="modalWorkerOutput" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="fas fa-terminal me-2"></i>Salida del Worker
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="workerOutputContent">
                <div class="text-center text-muted">
                  <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                  <p>Ejecutando worker...</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
async function enviar(form, tipoGrupo){
  const fd = new FormData(form);
  const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/cargas_subir.php', { method:'POST', body: fd });
  const data = await res.json();
  if (!data.success){ alert(data.message||'Error'); return; }
  form.reset();
  await cargarListado();
}
document.getElementById('formSifone')?.addEventListener('submit', (e)=>{ e.preventDefault(); enviar(e.target, 'sifone'); });
document.getElementById('formPagos')?.addEventListener('submit', (e)=>{ e.preventDefault(); enviar(e.target, 'pagos'); });

let cargasPage = 1;
const cargasPageSize = 20;

async function cargarListado(page = cargasPage){
  try {
    const url = '<?php echo getBaseUrl(); ?>modules/oficina/api/cargas_estado.php?page=' + page + '&limit=' + cargasPageSize;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Error al cargar');

    const list = data.items||[];
    const pag = data.pagination || { page: page, pageSize: cargasPageSize, total: list.length, totalPages: 1 };
    cargasPage = pag.page;

    const body = document.getElementById('cargasTbl');
    body.innerHTML = '';
    list.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.id}</td>
                      <td>${r.tipo}</td>
                      <td class="text-truncate" style="max-width:260px" title="${r.archivo_ruta}">${String(r.archivo_ruta||'').split(/[\\\/]/).pop()}</td>
                      <td>${estadoBadge(r.estado)}</td>
                      <td class="text-truncate" style="max-width:320px">${r.mensaje_log||''}</td>
                      <td><small>${r.fecha_creacion}</small></td>`;
      body.appendChild(tr);
    });

    const info = document.getElementById('cargasPageInfo');
    const start = (pag.page - 1) * pag.pageSize + 1;
    const end = Math.min(pag.page * pag.pageSize, pag.total);
    info.textContent = `Mostrando ${start}-${end} de ${pag.total}`;

    const btnPrev = document.getElementById('cargasPrev');
    const btnNext = document.getElementById('cargasNext');
    btnPrev.disabled = pag.page <= 1;
    btnNext.disabled = pag.page >= pag.totalPages;
  } catch (e) {
    console.error(e);
  }
}
function estadoBadge(est){
  if (est==='completado') return '<span class="badge bg-success">Completado</span>';
  if (est==='procesando') return '<span class="badge bg-warning text-dark">Procesando</span>';
  if (est==='error') return '<span class="badge bg-danger">Error</span>';
  return '<span class="badge bg-secondary">Pendiente</span>';
}
document.getElementById('btnRefrescar')?.addEventListener('click', (e)=>{ e.preventDefault(); cargarListado(1); });
document.getElementById('cargasPrev')?.addEventListener('click', (e)=>{ e.preventDefault(); if (cargasPage>1) cargarListado(cargasPage-1); });
document.getElementById('cargasNext')?.addEventListener('click', (e)=>{ e.preventDefault(); cargarListado(cargasPage+1); });

// Botón para ejecutar worker
document.getElementById('btnEjecutarWorker')?.addEventListener('click', async (e) => {
  e.preventDefault();
  const btn = e.target.closest('button');
  const originalText = btn.innerHTML;
  
  try {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ejecutando...';
    
    // Mostrar modal con estado de ejecución
    const modal = new bootstrap.Modal(document.getElementById('modalWorkerOutput'));
    modal.show();
    
    const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/ejecutar_worker.php', { 
      method: 'POST' 
    });
    const data = await res.json();
    
    // Actualizar contenido del modal
    const outputContent = document.getElementById('workerOutputContent');
    
         if (data.success) {
       let html = '<div class="alert alert-success mb-3">';
       html += '<h6><i class="fas fa-check-circle me-2"></i>Worker ejecutado exitosamente</h6>';
       html += '</div>';
       
       if (data.output && data.output.length > 0) {
         html += '<h6 class="mb-2">Salida del comando:</h6>';
         html += '<pre class="bg-light p-2 rounded small" style="max-height: 300px; overflow-y: auto;">';
         html += data.output.join('\n');
         html += '</pre>';
       } else {
         html += '<p class="text-muted">Comando ejecutado sin salida visible.</p>';
       }
       
       outputContent.innerHTML = html;
     } else {
       let html = '<div class="alert alert-danger mb-3">';
       html += '<h6><i class="fas fa-exclamation-triangle me-2"></i>Error al ejecutar worker</h6>';
       html += '</div>';
       
       if (data.output && data.output.length > 0) {
         html += '<h6 class="mb-2">Salida del comando:</h6>';
         html += '<pre class="bg-light p-2 rounded small" style="max-height: 300px; overflow-y: auto;">';
         html += data.output.join('\n');
         html += '</pre>';
       } else {
         html += '<p class="text-muted">Error sin salida visible.</p>';
       }
       
       outputContent.innerHTML = html;
     }
    
  } catch (error) {
    const outputContent = document.getElementById('workerOutputContent');
    outputContent.innerHTML = `
      <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle me-2"></i>Error de conexión</h6>
        <p>${error.message}</p>
      </div>
    `;
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

cargarListado();

// Resetear modal cuando se cierre y actualizar la página
document.getElementById('modalWorkerOutput')?.addEventListener('hidden.bs.modal', () => {
  const outputContent = document.getElementById('workerOutputContent');
  outputContent.innerHTML = `
    <div class="text-center text-muted">
      <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
      <p>Ejecutando worker...</p>
    </div>
  `;
  
  // Actualizar la lista de cargas para ver el estado actualizado
  cargarListado();
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>


