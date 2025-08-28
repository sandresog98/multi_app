<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('creditos');
$currentUser = $auth->getCurrentUser();

$pageTitle = 'Gestión Créditos - Solicitudes';
$currentPage = 'creditos_solicitudes';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-file-signature me-2"></i>Solicitudes de crédito</h1>
      </div>

      <div class="card mb-3"><div class="card-body">
        <form id="formSolicitud" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombres</label>
              <input class="form-control" id="nombres" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Identificación</label>
              <input class="form-control" id="identificacion" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Contacto celular</label>
              <input class="form-control" id="celular" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" id="email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Monto deseado</label>
              <input type="number" class="form-control" id="monto_deseado" step="0.01" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo de solicitud</label>
              <select class="form-select" id="tipo" required>
                <option value="">Seleccione…</option>
                <option>Dependiente</option>
                <option>Independiente</option>
              </select>
            </div>
          </div>

          <hr class="my-4">

          <div id="seccionDependiente" style="display:none">
            <h6 class="mb-3">Dependiente</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Último desprendible nómina (1)</label>
                <input type="file" class="form-control" id="dep_nomina_1" accept=".jpg,.jpeg,.png,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Último desprendible nómina (2)</label>
                <input type="file" class="form-control" id="dep_nomina_2" accept=".jpg,.jpeg,.png,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Certificación laboral (<= 30 días)</label>
                <input type="file" class="form-control" id="dep_cert_laboral" accept=".jpg,.jpeg,.png,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Simulación plan de pagos (PDF)</label>
                <input type="file" class="form-control" id="dep_simulacion_pdf" accept=".pdf">
              </div>
            </div>
          </div>

          <div id="seccionIndependiente" style="display:none">
            <h6 class="mb-3">Independiente</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Declaración de renta</label>
                <input type="file" class="form-control" id="ind_decl_renta" accept=".jpg,.jpeg,.png,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Simulación plan de pagos (PDF)</label>
                <input type="file" class="form-control" id="ind_simulacion_pdf" accept=".pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Desprendible nómina codeudor (1)</label>
                <input type="file" class="form-control" id="ind_codeudor_nomina_1" accept=".jpg,.jpeg,.png,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Desprendible nómina codeudor (2)</label>
                <input type="file" class="form-control" id="ind_codeudor_nomina_2" accept=".jpg,.jpeg,.png,.pdf">
              </div>
              <div class="col-md-6">
                <label class="form-label">Certificación laboral codeudor</label>
                <input type="file" class="form-control" id="ind_codeudor_cert_laboral" accept=".jpg,.jpeg,.png,.pdf">
              </div>
            </div>
          </div>

          <div class="mt-4 d-flex justify-content-end">
            <button type="button" class="btn btn-primary" id="btnGuardarSolicitud">Guardar solicitud</button>
          </div>
        </form>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
document.getElementById('tipo').addEventListener('change', () => {
  const t = document.getElementById('tipo').value;
  document.getElementById('seccionDependiente').style.display = (t === 'Dependiente') ? '' : 'none';
  document.getElementById('seccionIndependiente').style.display = (t === 'Independiente') ? '' : 'none';
});

function validarArchivo(file, allowPdfOnly = false){
  if (!file) return true;
  if (file.size > 5 * 1024 * 1024) { alert('Archivo supera 5MB'); return false; }
  const name = file.name.toLowerCase();
  const okExt = allowPdfOnly ? /\.pdf$/ : /\.(jpg|jpeg|png|pdf)$/;
  if (!okExt.test(name)) { alert('Formato no permitido'); return false; }
  return true;
}

document.getElementById('btnGuardarSolicitud').addEventListener('click', async () => {
  const tipo = document.getElementById('tipo').value;
  if (!tipo) { alert('Seleccione tipo'); return; }
  const formData = new FormData();
  formData.append('nombres', document.getElementById('nombres').value);
  formData.append('identificacion', document.getElementById('identificacion').value);
  formData.append('celular', document.getElementById('celular').value);
  formData.append('email', document.getElementById('email').value);
  formData.append('monto_deseado', document.getElementById('monto_deseado').value || '');
  formData.append('tipo', tipo);

  if (tipo === 'Dependiente') {
    const f1 = document.getElementById('dep_nomina_1').files[0];
    const f2 = document.getElementById('dep_nomina_2').files[0];
    const cert = document.getElementById('dep_cert_laboral').files[0];
    const sim = document.getElementById('dep_simulacion_pdf').files[0];
    if (![f1,f2,cert,sim].every(f=>!!f)) { alert('Adjunte todos los archivos de Dependiente'); return; }
    if (![validarArchivo(f1),validarArchivo(f2),validarArchivo(cert),validarArchivo(sim,true)].every(Boolean)) return;
    formData.append('dep_nomina_1', f1); formData.append('dep_nomina_2', f2);
    formData.append('dep_cert_laboral', cert); formData.append('dep_simulacion_pdf', sim);
  } else if (tipo === 'Independiente') {
    const dr = document.getElementById('ind_decl_renta').files[0];
    const sim = document.getElementById('ind_simulacion_pdf').files[0];
    const n1 = document.getElementById('ind_codeudor_nomina_1').files[0];
    const n2 = document.getElementById('ind_codeudor_nomina_2').files[0];
    const cl = document.getElementById('ind_codeudor_cert_laboral').files[0];
    if (![dr,sim,n1,n2,cl].every(f=>!!f)) { alert('Adjunte todos los archivos de Independiente'); return; }
    if (![validarArchivo(dr),validarArchivo(n1),validarArchivo(n2),validarArchivo(cl),validarArchivo(sim,true)].every(Boolean)) return;
    formData.append('ind_decl_renta', dr); formData.append('ind_simulacion_pdf', sim);
    formData.append('ind_codeudor_nomina_1', n1); formData.append('ind_codeudor_nomina_2', n2);
    formData.append('ind_codeudor_cert_laboral', cl);
  }

  try {
    const res = await fetch('../api/solicitud_guardar.php', { method: 'POST', body: formData });
    const json = await res.json();
    if (json && json.success) { alert('Solicitud creada'); window.location.reload(); }
    else { alert(json.message || 'No se pudo guardar'); }
  } catch (e) { alert('Error: ' + e); }
});
</script>
