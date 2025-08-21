<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Cobranza.php';
require_once '../models/Comunicacion.php';

$authController = new AuthController();
$authController->requireAnyRole(['admin','oficina']);
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Cobranza - Comunicaciones';
$currentPage = 'cobranza_comunicaciones';
include '../../../views/layouts/header.php';
?>

<?php
function tiempoRelativo($fecha) {
    try {
        $dt = new DateTime($fecha);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60) return 'hace ' . $diff . ' seg';
        $mins = floor($diff / 60);
        if ($mins < 60) return 'hace ' . $mins . ' min';
        $hours = floor($mins / 60);
        if ($hours < 24) return 'hace ' . $hours . ' h';
        $days = floor($hours / 24);
        if ($days < 30) return 'hace ' . $days . ' días';
        $months = floor($days / 30);
        if ($months < 12) return 'hace ' . $months . ' meses';
        $years = floor($months / 12);
        return 'hace ' . $years . ' años';
    } catch (Throwable $e) {
        return $fecha;
    }
}
?>

<div class="container-fluid">
	<div class="row">
		<?php include '../../../views/layouts/sidebar.php'; ?>
		<main class="col-12 main-content">
			<div class="pt-3 pb-2 mb-3 border-bottom">
				<h1 class="h2"><i class="fas fa-comments me-2"></i>Comunicaciones</h1>
			</div>

			<?php
				$cobranza = new Cobranza();
				$filtros = [
					'q' => $_GET['q'] ?? '',
					'estado' => $_GET['estado'] ?? '',
					'rango' => $_GET['rango'] ?? ''
				];
				$lista = $cobranza->listarAsociadosConMora($filtros, 200, 0);
			?>

			<div class="card mb-3">
				<div class="card-body">
					<form class="row g-2" method="get">
						<div class="col-sm-6 col-md-5">
							<input type="text" class="form-control" name="q" placeholder="Buscar por cédula, nombre, teléfono o email" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
						</div>
						<div class="col-sm-4 col-md-3">
							<select name="estado" class="form-select">
								<option value="">Estado mora (todos)</option>
								<?php $estSel = strtolower($_GET['estado'] ?? ''); ?>
								<option value="persuasiva" <?php echo $estSel==='persuasiva'?'selected':''; ?>>Persuasiva (0-60)</option>
								<option value="prejuridico" <?php echo $estSel==='prejuridico'?'selected':''; ?>>Prejurídico (61-90)</option>
								<option value="juridico" <?php echo $estSel==='juridico'?'selected':''; ?>>Jurídico (91+)</option>
							</select>
						</div>
						<div class="col-sm-6 col-md-4">
							<select name="rango" class="form-select">
								<option value="">Última comunicación (todas)</option>
								<?php $ranSel = strtolower($_GET['rango'] ?? ''); ?>
								<option value="sin" <?php echo $ranSel==='sin'?'selected':''; ?>>Sin comunicación</option>
								<option value="muy_reciente" <?php echo $ranSel==='muy_reciente'?'selected':''; ?>>Muy reciente (&lt; 2 días)</option>
								<option value="reciente" <?php echo $ranSel==='reciente'?'selected':''; ?>>Reciente (2–5 días)</option>
								<option value="intermedia" <?php echo $ranSel==='intermedia'?'selected':''; ?>>Intermedia (5–10 días)</option>
								<option value="lejana" <?php echo $ranSel==='lejana'?'selected':''; ?>>Lejana (10–20 días)</option>
								<option value="muy_lejana" <?php echo $ranSel==='muy_lejana'?'selected':''; ?>>Muy lejana (&gt; 20 días)</option>
							</select>
						</div>
						<div class="col-sm-2">
							<button class="btn btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i>Buscar</button>
						</div>
						<div class="col-sm-2">
							<a href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comunicaciones.php" class="btn btn-outline-secondary w-100"><i class="fas fa-eraser me-1"></i>Eliminar filtros</a>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<strong>Asociados con créditos en mora</strong>
					<small class="text-muted">Top 200 por días/valor</small>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-sm align-middle">
							<thead class="table-light">
								<tr>
									<th>Cédula</th>
									<th>Nombre</th>
									<th>Detalle</th>
									<th class="text-center">Máx. días</th>
									<th>Estado</th>
									<th>Última comunicación</th>
									<th class="text-end">Acciones</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($lista as $row): ?>
									<?php $estado = $row['estado_mora']; ?>
									<tr>
										<td><?php echo htmlspecialchars($row['cedula']); ?></td>
										<td><?php echo htmlspecialchars($row['nombre']); ?></td>
										<td>
											<div class="small">Mora: <strong><?php echo '$'.number_format((float)$row['total_mora'], 0); ?></strong></div>
											<div class="small text-muted">Cartera: <strong><?php echo '$'.number_format((float)($row['total_cartera'] ?? 0), 0); ?></strong></div>
										</td>
										<td class="text-center"><?php echo (int)$row['max_diav']; ?></td>
										<td>
											<span class="badge bg-<?php echo $estado['color']; ?>"><?php echo htmlspecialchars($estado['label']); ?></span>
										</td>
										<td>
											<?php if (!empty($row['ultima_comunicacion'])): ?>
												<?php $d = isset($row['dias_ultima']) ? (int)$row['dias_ultima'] : 0; ?>
												<?php if ($d === 0) { try { $d = max(0, (new DateTime())->diff(new DateTime($row['ultima_comunicacion']))->days); } catch (Throwable $e) { $d = 0; } } ?>
												<?php $label = ($d < 2 ? 'Muy reciente' : ($d < 5 ? 'Reciente' : ($d < 10 ? 'Intermedia' : ($d <= 20 ? 'Lejana' : 'Muy lejana')))); ?>
												<?php $color = ($label === 'Muy reciente' ? 'success' : ($label === 'Reciente' ? 'primary' : ($label === 'Intermedia' ? 'warning' : ($label === 'Lejana' ? 'secondary' : 'dark')))); ?>
												<small class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></small>
												<div class="small text-muted mt-1" title="<?php echo htmlspecialchars($row['ultima_comunicacion']); ?>"><?php echo tiempoRelativo($row['ultima_comunicacion']); ?></div>
											<?php else: ?>
												<small class="badge bg-danger">Sin comunicación</small>
											<?php endif; ?>
										</td>
										<td class="text-end">
											<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetalle" data-cedula="<?php echo htmlspecialchars($row['cedula']); ?>" data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
												<i class="fas fa-eye"></i>
											</button>
											<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#offHistorial" aria-controls="offHistorial" data-cedula="<?php echo htmlspecialchars($row['cedula']); ?>" data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
												<i class="fas fa-clock"></i> Historial
											</button>
											<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalComunicacion" data-cedula="<?php echo htmlspecialchars($row['cedula']); ?>" data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
												<i class="fas fa-plus"></i> Comunicación
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg modal-dialog-scrollable">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Detalle de mora - <span id="detalleNombre"></span> (<span id="detalleCedula"></span>)</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div id="detalleTabla" class="table-responsive"></div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
						</div>
					</div>
				</div>
			</div>

			<div class="offcanvas offcanvas-end" tabindex="-1" id="offHistorial" aria-labelledby="offHistorialLabel">
				<div class="offcanvas-header">
					<h5 class="offcanvas-title" id="offHistorialLabel">Historial de comunicaciones</h5>
					<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
				</div>
				<div class="offcanvas-body">
					<div class="mb-2 text-muted">Asociado: <strong><span id="histNombre"></span></strong> (<span id="histCedula"></span>)</div>
					<div id="histContenido" class="small">Seleccione un asociado para ver su historial.</div>
				</div>
			</div>

			<div class="modal fade" id="modalComunicacion" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<form class="modal-content" id="formComunicacion">
						<div class="modal-header">
							<h5 class="modal-title">Nueva comunicación - <span id="comNombre"></span> (<span id="comCedula"></span>)</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<input type="hidden" name="cedula" id="comCedulaInput">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">Tipo de comunicación</label>
									<select class="form-select" name="tipo" required>
										<option value="Llamada">Llamada</option>
										<option value="Mensaje de Texto">Mensaje de Texto</option>
										<option value="Whatsapp">Whatsapp</option>
										<option value="Email">Email</option>
									</select>
								</div>
								<div class="col-md-4">
									<label class="form-label">Estado</label>
									<select class="form-select" name="estado" required>
										<option value="Sin comunicación">Sin comunicación</option>
										<option value="Informa de pago realizado">Informa de pago realizado</option>
										<option value="Comprometido a realizar el pago">Comprometido a realizar el pago</option>
										<option value="Sin respuesta">Sin respuesta</option>
									</select>
								</div>
								<div class="col-md-4">
									<label class="form-label">Fecha comunicación</label>
									<input type="datetime-local" class="form-control" name="fecha" required>
								</div>
								<div class="col-12">
									<label class="form-label">Comentario</label>
									<textarea class="form-control" name="comentario" rows="3" placeholder="Detalle de la comunicación"></textarea>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
							<button type="submit" class="btn btn-primary">Guardar</button>
						</div>
					</form>
				</div>
			</div>
		</main>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
	window.CURRENT_USER_ID = <?php echo (int)($currentUser['id'] ?? 0); ?>;
	const modalDetalle = document.getElementById('modalDetalle');
	modalDetalle.addEventListener('show.bs.modal', (ev) => {
		const btn = ev.relatedTarget;
		document.getElementById('detalleNombre').textContent = btn.getAttribute('data-nombre');
		document.getElementById('detalleCedula').textContent = btn.getAttribute('data-cedula');
		fetch(`../api/cobranza_detalle.php?cedula=${encodeURIComponent(btn.getAttribute('data-cedula'))}`)
			.then(r => r.json()).then(data => {
				const rows = data?.items || [];
				let html = '<table class="table table-sm"><thead><tr><th>Préstamo</th><th>Tipo</th><th class="text-end">Saldo mora</th><th class="text-center">Días</th><th class="text-end">Int. mora</th></tr></thead><tbody>';
				for (const it of rows) {
					html += `<tr><td>${it.presta||''}</td><td>${it.tipopr||''}</td><td class="text-end">$${Number(it.sdomor||0).toLocaleString('es-CO')}</td><td class="text-center">${it.diav||0}</td><td class="text-end">$${Number(it.intmora||0).toLocaleString('es-CO')}</td></tr>`;
				}
				html += '</tbody></table>';
				document.getElementById('detalleTabla').innerHTML = html;
			}).catch(() => {
				document.getElementById('detalleTabla').innerHTML = '<div class="text-danger small">Error al cargar el detalle.</div>';
			});
	});

	function cargarHistorialDesdeBoton(btn) {
		const nombre = btn.getAttribute('data-nombre') || '';
		const cedula = btn.getAttribute('data-cedula') || '';
		document.getElementById('histNombre').textContent = nombre;
		document.getElementById('histCedula').textContent = cedula;
		document.getElementById('histContenido').innerHTML = 'Cargando...';
		if (!cedula) { document.getElementById('histContenido').innerHTML = '<div class="text-danger small">Cédula no disponible.</div>'; return; }
		fetch(`../api/cobranza_historial.php?cedula=${encodeURIComponent(cedula)}`)
			.then(r => r.json()).then(data => {
				const rows = data?.items || [];
				if (!rows.length) {
					document.getElementById('histContenido').innerHTML = '<div class="text-muted">Sin comunicaciones registradas.</div>';
					return;
				}
				let html = '<div class="list-group">';
				for (const it of rows) {
					const canEdit = window.CURRENT_USER_ID && it.id_usuario && Number(window.CURRENT_USER_ID)===Number(it.id_usuario);
					const safeComentario = String(it.comentario||'').replace(/</g,'&lt;');
					const dataTipo = String(it.tipo_comunicacion||'').replace(/"/g,'&quot;');
					const dataEstado = String(it.estado||'').replace(/"/g,'&quot;');
					const dataComentario = String(it.comentario||'').replace(/"/g,'&quot;').replace(/</g,'&lt;');
					const dataFecha = String(it.fecha_comunicacion||'');
					html += `<div class="list-group-item" data-id="${it.id}" data-tipo="${dataTipo}" data-estado="${dataEstado}" data-comentario="${dataComentario}" data-fecha="${dataFecha}">
						<div class="d-flex justify-content-between">
							<div>
								<strong>${it.tipo_comunicacion}</strong> · ${it.estado}
								<div class="small text-muted">por ${(it.usuario_nombre||'—').replace(/</g,'&lt;')}</div>
							</div>
							<div class="text-end">
								<small class="text-muted">${(it.fecha_comunicacion||'').replace('T',' ')}</small>
								${canEdit ? (`
								<button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-action="edit" data-id="${it.id}" title="Editar"><i class="fas fa-pen"></i></button>
								<button type="button" class="btn btn-sm btn-outline-danger ms-1" data-action="delete" data-id="${it.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
								`) : ''}
							</div>
						</div>
						<div class="small">${safeComentario}</div>
					</div>`;
				}
				html += '</div>';
				document.getElementById('histContenido').innerHTML = html;
			}).catch(() => {
				document.getElementById('histContenido').innerHTML = '<div class="text-danger small">Error al cargar el historial.</div>';
			});
	}

	document.addEventListener('click', (e) => {
		const btn = e.target.closest('button[data-bs-target="#offHistorial"]');
		if (!btn) return;
		cargarHistorialDesdeBoton(btn);
	});

	// Modal edición
	const editModalHtml = `
<div class="modal fade" id="modalEditarCom" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formEditarCom">
      <div class="modal-header">
        <h5 class="modal-title">Editar comunicación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="editId">
        <div class="mb-2">
          <label class="form-label">Tipo</label>
          <select class="form-select" name="tipo" id="editTipo" required>
            <option value="Llamada">Llamada</option>
            <option value="Mensaje de Texto">Mensaje de Texto</option>
            <option value="Whatsapp">Whatsapp</option>
            <option value="Email">Email</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Estado</label>
          <select class="form-select" name="estado" id="editEstado" required>
            <option value="Sin comunicación">Sin comunicación</option>
            <option value="Informa de pago realizado">Informa de pago realizado</option>
            <option value="Comprometido a realizar el pago">Comprometido a realizar el pago</option>
            <option value="Sin respuesta">Sin respuesta</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Fecha comunicación</label>
          <input type="datetime-local" class="form-control" name="fecha" id="editFecha" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Comentario</label>
          <textarea class="form-control" name="comentario" id="editComentario" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>`;
	const tempWrap = document.createElement('div');
	tempWrap.innerHTML = editModalHtml;
	document.body.appendChild(tempWrap.firstElementChild);

	// Delegación de clicks en historial para editar/eliminar
	document.getElementById('offHistorial').addEventListener('click', (ev) => {
		const btn = ev.target.closest('button[data-action]');
		if (!btn) return;
		const action = btn.getAttribute('data-action');
		const id = btn.getAttribute('data-id');
		if (action === 'delete') {
			if (!confirm('¿Eliminar comunicación?')) return;
			const fd = new FormData(); fd.append('id', id);
			fetch('../api/cobranza_eliminar_comunicacion.php', { method: 'POST', body: fd })
				.then(r=>r.json()).then(data => {
					if (data.success) {
						bootstrap.Offcanvas.getInstance(document.getElementById('offHistorial')).hide();
						alert('Eliminado');
					} else { alert(data.message||'Error'); }
				}).catch(()=>alert('Error solicitud'));
		} else if (action === 'edit') {
			const item = btn.closest('.list-group-item');
			const modal = new bootstrap.Modal(document.getElementById('modalEditarCom'));
			document.getElementById('editId').value = id;
			document.getElementById('editTipo').value = item.getAttribute('data-tipo') || 'Llamada';
			document.getElementById('editEstado').value = item.getAttribute('data-estado') || 'Sin respuesta';
			document.getElementById('editComentario').value = (item.getAttribute('data-comentario') || '').replace(/&lt;/g,'<');
			// Convertir fecha (YYYY-MM-DD HH:MM:SS) a datetime-local (YYYY-MM-DDTHH:MM)
			const raw = item.getAttribute('data-fecha') || '';
			let dtVal = '';
			if (raw) {
				const norm = raw.replace(' ', 'T').slice(0,16);
				dtVal = norm;
			}
			document.getElementById('editFecha').value = dtVal;
			modal.show();
		}
	});

	document.getElementById('formEditarCom').addEventListener('submit', (ev) => {
		ev.preventDefault();
		const fd = new FormData(ev.target);
		fetch('../api/cobranza_editar_comunicacion.php', { method: 'POST', body: fd })
			.then(r=>r.json()).then(data => {
				if (data.success) {
					bootstrap.Modal.getInstance(document.getElementById('modalEditarCom')).hide();
					const oc = bootstrap.Offcanvas.getInstance(document.getElementById('offHistorial'));
					if (oc) oc.hide();
					alert('Actualizado');
				} else { alert(data.message||'Error'); }
			}).catch(()=>alert('Error solicitud'));
	});

	const modalCom = document.getElementById('modalComunicacion');
	modalCom.addEventListener('show.bs.modal', (ev) => {
		const btn = ev.relatedTarget;
		document.getElementById('comNombre').textContent = btn.getAttribute('data-nombre');
		document.getElementById('comCedula').textContent = btn.getAttribute('data-cedula');
		document.getElementById('comCedulaInput').value = btn.getAttribute('data-cedula');
	});

	document.getElementById('formComunicacion').addEventListener('submit', (ev) => {
		ev.preventDefault();
		const fd = new FormData(ev.target);
		fetch('../api/cobranza_crear_comunicacion.php', { method: 'POST', body: fd })
			.then(r => r.json()).then(data => {
				if (data.success) {
					bootstrap.Modal.getInstance(modalCom).hide();
					alert('Comunicación registrada');
				} else {
					alert('Error: ' + (data.message||'No se pudo registrar'));
				}
			})
			.catch(() => alert('Error en solicitud'));
	});
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>


