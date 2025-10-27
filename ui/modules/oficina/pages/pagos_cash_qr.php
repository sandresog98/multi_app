<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/PagoCashQr.php';
require_once '../../../models/Logger.php';
require_once '../../../utils/FileUploadManager.php';

$auth = new AuthController();
$auth->requireModule('oficina.pagos_cash_qr');
$currentUser = $auth->getCurrentUser();
$model = new PagoCashQr();
$logger = new Logger();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'asignar') {
      $confiar_id = trim($_POST['confiar_id'] ?? '');
      $cedula = trim($_POST['cedula'] ?? '');
      $link = trim($_POST['link'] ?? '');
      // Manejo de carga de imagen (opcional). Si hay imagen válida, sobrescribe $link con la URL pública del archivo subido
      if (isset($_FILES['comprobante']) && is_array($_FILES['comprobante']) && (($_FILES['comprobante']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
      	try {
      		// Usar FileUploadManager para manejo consistente de archivos
      		$options = [
      			'maxSize' => 5 * 1024 * 1024, // 5MB
      			'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
      			'prefix' => 'recibo',
      			'userId' => $currentUser['id'] ?? '',
      			'webPath' => getBaseUrl() . 'uploads/recibos',
      			'createSubdirs' => true // Crear subdirectorios año/mes
      		];
      		
      		$baseDir = __DIR__ . '/../../../uploads/recibos';
      		$result = FileUploadManager::saveUploadedFile($_FILES['comprobante'], $baseDir, $options);
      		$link = $result['webUrl'];
      		
      		error_log("Pagos Cash QR - Archivo guardado: " . $result['path']);
      		error_log("Pagos Cash QR - URL generada: $link");
      	} catch (Exception $e) {
      		throw new Exception('Error al guardar comprobante: ' . $e->getMessage());
      	}
      }
      $comentario = trim($_POST['comentario'] ?? '');
      $res = $model->assign($confiar_id, $cedula, $link, $comentario);
      if ($res['success']) { $message = 'Asignación registrada'; $logger->logEditar('pagos_cashqr','Asignar comprobante y usuario', null, compact('confiar_id','cedula','link')); }
      else { $error = $res['message'] ?? 'No se pudo asignar'; }
    } elseif ($action === 'eliminar') {
      $confiar_id = trim($_POST['confiar_id'] ?? '');
      $res = $model->removeAssignment($confiar_id);
      if ($res['success']) { $message = 'Asignación eliminada'; $logger->logEliminar('pagos_cashqr','Eliminar asignación', ['confiar_id'=>$confiar_id]); }
      else { $error = $res['message'] ?? 'No se pudo eliminar'; }
    } elseif ($action === 'no_valida') {
      $confiar_id = trim($_POST['confiar_id'] ?? '');
      $motivo = trim($_POST['motivo'] ?? '');
      if ($motivo==='') throw new Exception('Motivo requerido');
      $res = $model->markInvalid($confiar_id, $motivo, (int)($currentUser['id']??0));
      if ($res['success']) { $message = 'Marcada como no válida'; $logger->logEditar('pagos_cashqr','Marcar no válida', null, compact('confiar_id','motivo')); }
      else { $error = $res['message'] ?? 'No se pudo marcar'; }
    }
  } catch (Exception $e) { $error = $e->getMessage(); }
}

$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$tipo = trim($_GET['tipo'] ?? 'all'); // all | efectivo | qr | transf_av | cheque
$asignacion = trim($_GET['asignacion'] ?? ''); // '' | asignados | no_asignados
$data = $model->listConfiar($page, 20, $search, $tipo, $asignacion);

$pageTitle = 'Pagos Cash/QR - Oficina';
$currentPage = 'pagos_cash_qr';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-receipt me-2"></i>Pagos Cash/QR</h1>
      </div>

      <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i><?php echo htmlspecialchars($message); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

      <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4"><input class="form-control" name="search" placeholder="Buscar id/descripcion/documento" value="<?php echo htmlspecialchars($search); ?>"></div>
        <div class="col-md-2">
          <select name="tipo" class="form-select">
            <option value="all" <?php echo $tipo==='all'?'selected':''; ?>>Todos</option>
            <option value="efectivo" <?php echo $tipo==='efectivo'?'selected':''; ?>>Solo Efectivo</option>
            <option value="qr" <?php echo $tipo==='qr'?'selected':''; ?>>Solo QR</option>
            <option value="transf_av" <?php echo $tipo==='transf_av'?'selected':''; ?>>Solo Transf. Agencia Virtual</option>
            <option value="cheque" <?php echo $tipo==='cheque'?'selected':''; ?>>Solo Cheque</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="asignacion" class="form-select">
            <option value="">Todos</option>
            <option value="asignados" <?php echo $asignacion==='asignados'?'selected':''; ?>>Asignados</option>
            <option value="no_asignados" <?php echo $asignacion==='no_asignados'?'selected':''; ?>>No asignados</option>
            <option value="no_validas" <?php echo $asignacion==='no_validas'?'selected':''; ?>>No válidas</option>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
      </form>

      <div class="card"><div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-dark"><tr>
              <th>Confiar ID</th>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Valor</th>
              <th>Asignación</th>
              <th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($data['items'] as $row): ?>
              <tr>
                <?php 
                $linkPrev = $row['asignado_link'] ?? ''; 
                $hasLinkPrev = !empty($linkPrev); 
                // Detectar si el enlace es una imagen
                $isImage = $hasLinkPrev && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $linkPrev);
                ?>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($row['confiar_id']); ?></div>
                  <div class="small text-muted"><?php echo htmlspecialchars($row['descripcion']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                <td><span class="badge <?php echo ($row['tipo_transaccion']==='Pago Efectivo')?'bg-success':(($row['tipo_transaccion']==='Pago QR')?'bg-info':(($row['tipo_transaccion']==='Cheque')?'bg-primary':'bg-warning text-dark')); ?>"><?php echo htmlspecialchars($row['tipo_transaccion']); ?></span></td>
                <td><?php echo '$' . number_format((float)$row['valor_consignacion'], 0); ?></td>
                <td>
                  <?php if (!empty($row['asignado_cedula'])): ?>
                    <div class="small"><?php echo htmlspecialchars($row['asignado_cedula']); ?></div>
                    <div class="small"><?php echo htmlspecialchars($row['asignado_nombre'] ?? ''); ?></div>
                    <?php if (!empty($row['asignado_comentario'])): ?><div class="small text-muted"><?php echo htmlspecialchars($row['asignado_comentario']); ?></div><?php endif; ?>
                    <?php if ($isImage): ?>
                      <div class="mt-2">
                        <img src="<?php echo htmlspecialchars($linkPrev); ?>" alt="Comprobante" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($linkPrev); ?>', '_blank')" title="Hacer clic para ver completo">
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <?php if (($row['asignado_estado'] ?? '')==='no_valido'): ?>
                      <span class="badge bg-dark">No válida</span>
                      <?php if (!empty($row['motivo_no_valido'])): ?><div class="small text-muted"><?php echo htmlspecialchars($row['motivo_no_valido']); ?></div><?php endif; ?>
                    <?php else: ?>
                      <span class="badge bg-secondary">Sin asignar</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group">
                    <?php if ($hasLinkPrev): ?>
                      <a class="btn btn-sm btn-outline-info" href="<?php echo htmlspecialchars($linkPrev); ?>" target="_blank" title="Ver comprobante"><i class="fas fa-eye"></i></a>
                    <?php endif; ?>
                    <?php if (empty($row['asignado_cedula']) && (($row['asignado_estado'] ?? '')!=='no_valido')): ?>
                      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#asignarModal<?php echo $row['confiar_id']; ?>" title="Asignar"><i class="fas fa-link"></i></button>
                      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#noValidaModal<?php echo $row['confiar_id']; ?>" title="Marcar no válida"><i class="fas fa-ban"></i></button>
                    <?php else: ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="confiar_id" value="<?php echo htmlspecialchars($row['confiar_id']); ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Eliminar asignación"><i class="fas fa-unlink"></i></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

              <?php if (empty($row['asignado_cedula']) && (($row['asignado_estado'] ?? '')!=='no_valido')): ?>
              <div class="modal fade" id="asignarModal<?php echo $row['confiar_id']; ?>" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-link me-2"></i>Asignar comprobante y usuario</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                  <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                      <input type="hidden" name="action" value="asignar">
                      <input type="hidden" name="confiar_id" value="<?php echo htmlspecialchars($row['confiar_id']); ?>">
                      <div class="mb-2 position-relative">
                        <label class="form-label">Cédula</label>
                        <input class="form-control" name="cedula" id="cedula_<?php echo $row['confiar_id']; ?>" placeholder="Buscar por cédula o nombre" autocomplete="off" required oninput="buscarAsociados('cedula_<?php echo $row['confiar_id']; ?>','asoc_results_<?php echo $row['confiar_id']; ?>','cedula_nombre_<?php echo $row['confiar_id']; ?>')">
                        <div class="form-text" id="cedula_nombre_<?php echo $row['confiar_id']; ?>"></div>
                        <div id="asoc_results_<?php echo $row['confiar_id']; ?>" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1070; max-height:220px; overflow:auto; background:#fff; border:1px solid #dee2e6; border-top:none; box-shadow:0 4px 10px rgba(0,0,0,0.1);"></div>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Comprobante (imagen o URL)</label>
                        <input type="file" class="form-control" name="comprobante" accept="image/*" onchange="validarImagen(this)">
                        <div class="form-text">Opcional: si no subes imagen, puedes pegar un enlace abajo.</div>
                        <input type="url" class="form-control mt-2" name="link" placeholder="https://...">
                      </div>
                      <div class="mb-2"><label class="form-label">Comentario (opcional)</label><input class="form-control" name="comentario" maxlength="255"></div>
                      <div class="small text-muted">Confiar: <?php echo htmlspecialchars($row['confiar_id']); ?> | Fecha: <?php echo htmlspecialchars($row['fecha']); ?> | Valor: <?php echo '$' . number_format((float)$row['valor_consignacion'], 0); ?></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
                  </form>
                </div></div>
              </div>
              <?php endif; ?>

              <!-- Modal No Válida -->
              <?php if (($row['asignado_estado'] ?? '')!=='no_valido'): ?>
              <div class="modal fade" id="noValidaModal<?php echo $row['confiar_id']; ?>" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-ban me-2"></i>Marcar como no válida</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                  <form method="POST">
                    <div class="modal-body">
                      <input type="hidden" name="action" value="no_valida">
                      <input type="hidden" name="confiar_id" value="<?php echo htmlspecialchars($row['confiar_id']); ?>">
                      <label class="form-label">Motivo</label>
                      <textarea class="form-control" name="motivo" rows="3" required placeholder="Explica por qué no es válido"></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark">Marcar</button></div>
                  </form>
                </div></div>
              </div>
              <?php endif; ?>

            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div></div>

      <?php if (($data['pages'] ?? 1) > 1): $pages=$data['pages']; $cur=$data['current_page']; ?>
        <nav><ul class="pagination justify-content-center">
          <?php for ($i=1;$i<=$pages;$i++): ?><li class="page-item <?php echo $i==$cur?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($tipo); ?>&asignacion=<?php echo urlencode($asignacion); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        </ul></nav>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
// Búsqueda simple de asociados con lista clickeable
async function buscarAsociados(inputId, listContainerId, nombreId){
  const input = document.getElementById(inputId);
  const q = input.value.trim();
  const cont = document.getElementById(listContainerId);
  cont.innerHTML = '<div class="list-group-item text-muted">Buscando…</div>';
  if (q.length < 2) return;
  try {
    const res = await fetch('<?php echo getBaseUrl(); ?>modules/oficina/api/buscar_asociados.php?q='+encodeURIComponent(q));
    const data = await res.json();
    const items = data.items||[];
    if (items.length === 0) { cont.innerHTML = '<div class="list-group-item text-muted">Sin resultados</div>'; return; }
    const frag = document.createDocumentFragment();
    items.forEach(a => {
      const el = document.createElement('a');
      el.href = '#';
      el.className = 'list-group-item list-group-item-action';
      el.textContent = `${a.cedula} — ${a.nombre}`;
      el.addEventListener('click', (ev) => {
        ev.preventDefault();
        input.value = a.cedula;
        document.getElementById(nombreId).textContent = 'Nombre: ' + a.nombre;
        cont.innerHTML = '';
      });
      frag.appendChild(el);
    });
    cont.innerHTML = '';
    cont.appendChild(frag);
  } catch (e) {
    cont.innerHTML = '<div class="list-group-item text-danger">Error de búsqueda</div>';
  }
}

// Delegación para inputs de cédula
document.addEventListener('input', (e) => {
  if (e.target && e.target.id && e.target.id.startsWith('cedula_')){
    const id = e.target.id.replace('cedula_','');
    buscarAsociados('cedula_'+id, 'asoc_results_'+id, 'cedula_nombre_'+id);
  }
});

// Ocultar lista al hacer click fuera
document.addEventListener('click', (e) => {
  const lists = document.querySelectorAll('[id^="asoc_results_"]');
  lists.forEach(list => {
    if (!list.contains(e.target) && !(document.getElementById(list.id.replace('asoc_results_','cedula_'))?.contains(e.target))){
      list.innerHTML = '';
    }
  });
});

// Validación de imagen (similar a otros módulos)
function validarImagen(input) {
  if (!input.files || input.files.length === 0) return;
  
  const file = input.files[0];
  const maxSize = 5 * 1024 * 1024; // 5MB
  
  // Validar tamaño
  if (file.size > maxSize) {
    alert('La imagen excede el tamaño máximo (5 MB)');
    input.value = '';
    return;
  }
  
  // Validar tipo de archivo
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
  if (!allowedTypes.includes(file.type)) {
    alert('Formato de imagen no permitido (solo jpg, png, gif, webp)');
    input.value = '';
    return;
  }
  
  // Mostrar información del archivo
  const sizeKB = Math.round(file.size / 1024);
  const infoDiv = input.parentNode.querySelector('.file-info');
  if (infoDiv) {
    infoDiv.remove();
  }
  
  const info = document.createElement('div');
  info.className = 'file-info small text-success mt-1';
  info.innerHTML = `✓ Archivo seleccionado: ${file.name} (${sizeKB} KB)`;
  input.parentNode.appendChild(info);
}
</script>
