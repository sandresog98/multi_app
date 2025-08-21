<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Cargas.php';

$auth = new AuthController();
$auth->requireAnyRole(['admin','oficina']);
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
                    <option value="sifone_libro">Libro de asociados</option>
                    <option value="sifone_cartera_aseguradora">Cartera Aseguradora</option>
                    <option value="sifone_cartera_mora">Cartera Mora</option>
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
              <thead class="table-light"><tr><th>ID</th><th>Tipo</th><th>Archivo</th><th>Estado</th><th>Mensaje</th><th>Fecha carga</th><th>Última actualización</th></tr></thead>
              <tbody id="cargasTbl"></tbody>
            </table>
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

async function cargarListado(){
  const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/cargas_estado.php');
  const data = await res.json();
  const list = data.items||[];
  const body = document.getElementById('cargasTbl');
  body.innerHTML = '';
  list.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${r.id}</td>
                    <td>${r.tipo}</td>
                    <td class="text-truncate" style="max-width:260px" title="${r.archivo_ruta}">${String(r.archivo_ruta||'').split(/[\\\/]/).pop()}</td>
                    <td>${estadoBadge(r.estado)}</td>
                    <td class="text-truncate" style="max-width:320px">${r.mensaje_log||''}</td>
                    <td><small>${r.fecha_creacion}</small></td>
                    <td><small>${r.fecha_actualizacion||''}</small></td>`;
    body.appendChild(tr);
  });
}
function estadoBadge(est){
  if (est==='completado') return '<span class="badge bg-success">Completado</span>';
  if (est==='procesando') return '<span class="badge bg-warning text-dark">Procesando</span>';
  if (est==='error') return '<span class="badge bg-danger">Error</span>';
  return '<span class="badge bg-secondary">Pendiente</span>';
}
document.getElementById('btnRefrescar')?.addEventListener('click', (e)=>{ e.preventDefault(); cargarListado(); });
cargarListado();
</script>

<?php include '../../../views/layouts/footer.php'; ?>


