<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/Cobranza.php';
require_once '../models/Comunicacion.php';

$authController = new AuthController();
$authController->requireModule('cobranza.comunicaciones');
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
					<small class="text-muted">Listado paginado</small>
				</div>
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center mb-2">
						<div class="small text-muted" id="comResumen"></div>
						<div class="btn-group btn-group-sm" role="group">
							<button class="btn btn-outline-secondary" id="comPrev">«</button>
							<button class="btn btn-outline-secondary" id="comNext">»</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-sm align-middle" id="tablaCom">
							<thead class="table-light">
								<tr>
									<th data-sort="cedula" class="sortable">Cédula</th>
									<th data-sort="nombre" class="sortable">Nombre</th>
									<th>Detalle</th>
									<th data-sort="max_diav" class="text-center sortable">Máx. días</th>
									<th>Estado</th>
									<th data-sort="ultima_comunicacion" class="sortable">Última comunicación</th>
									<th class="text-end">Acciones</th>
								</tr>
							</thead>
							<tbody id="comBody"></tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-xl modal-dialog-scrollable">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Detalle de mora - <span id="detalleNombre"></span> (<span id="detalleCedula"></span>)</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div id="detalleContenido"></div>
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

			<div class="modal fade" id="modalEditarCom" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<form class="modal-content" id="formEditarCom">
						<div class="modal-header">
							<h5 class="modal-title">Editar comunicación</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<input type="hidden" name="id" id="editId">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">Tipo de comunicación</label>
									<select class="form-select" name="tipo" id="editTipo" required>
										<option value="Llamada">Llamada</option>
										<option value="Mensaje de Texto">Mensaje de Texto</option>
										<option value="Whatsapp">Whatsapp</option>
										<option value="Email">Email</option>
									</select>
								</div>
								<div class="col-md-4">
									<label class="form-label">Estado</label>
									<select class="form-select" name="estado" id="editEstado" required>
										<option value="Informa de pago realizado">Informa de pago realizado</option>
										<option value="Comprometido a realizar el pago">Comprometido a realizar el pago</option>
										<option value="Sin respuesta">Sin respuesta</option>
									</select>
								</div>
								<div class="col-md-4">
									<label class="form-label">Fecha comunicación</label>
									<input type="datetime-local" class="form-control" name="fecha" id="editFecha" required>
								</div>
								<div class="col-12">
									<label class="form-label">Comentario</label>
									<textarea class="form-control" name="comentario" id="editComentario" rows="3" placeholder="Detalle de la comunicación"></textarea>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
							<button type="submit" class="btn btn-primary">Guardar cambios</button>
						</div>
					</form>
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
                                        <option value="Codeudor - Llamada">Codeudor - Llamada</option>
                                        <option value="Codeudor - Mensaje de Texto">Codeudor - Mensaje de Texto</option>
                                        <option value="Codeudor - Whatsapp">Codeudor - Whatsapp</option>
                                        <option value="Codeudor - Email">Codeudor - Email</option>
                                    </select>
								</div>
								<div class="col-md-4">
									<label class="form-label">Estado</label>
									<select class="form-select" name="estado" required>
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
				if (!data.success || !data.data) {
					document.getElementById('detalleContenido').innerHTML = '<div class="text-danger small">Error al cargar el detalle.</div>';
					return;
				}
				
				const detalle = data.data;
				let html = '';
				
				// Información del asociado
				if (detalle.asociado) {
					html += `
						<div class="row g-3 mb-3">
							<div class="col-md-6">
								<div class="card">
									<div class="card-header"><strong>Información del asociado</strong></div>
									<div class="card-body">
										<div><strong>Nombre:</strong> ${detalle.asociado.nombre || ''}</div>
										<div><strong>Cédula:</strong> ${detalle.asociado.cedula || ''}</div>
										<div><strong>Teléfono:</strong> ${detalle.asociado.celula || ''}</div>
										<div><strong>Email:</strong> ${detalle.asociado.mail || ''}</div>
										<div><strong>Ciudad:</strong> ${detalle.asociado.ciudad || ''}</div>
										<div><strong>Dirección:</strong> ${detalle.asociado.direcc || ''}</div>
										<div><strong>Fecha de nacimiento:</strong> ${detalle.asociado.fecnac ? new Date(detalle.asociado.fecnac).toLocaleDateString('es-CO') : '-'}</div>
										<div><strong>Edad:</strong> ${detalle.asociado.fecnac ? (Math.floor((Date.now() - new Date(detalle.asociado.fecnac).getTime()) / (365.25*24*3600*1000))) + ' años' : '-'}</div>
										<div><strong>Fecha de afiliación:</strong> ${detalle.asociado.fechai ? new Date(detalle.asociado.fechai).toLocaleDateString('es-CO') : '-'}</div>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="card">
                                    <div class="card-header"><strong>Información monetaria</strong></div>
                                    <div class="card-body">
                                        <div><strong>Aportes:</strong> $${Number(detalle.asociado.aporte || 0).toLocaleString('es-CO')}</div>
                                        ${(()=>{
                                            try {
                                                const asignaciones = Array.isArray(detalle.productos)?detalle.productos:[];
                                                const productosMensual = asignaciones.filter(p=>p.estado_activo).reduce((s,p)=>s+Number(p.monto_pago||0),0);
                                                const creditos = Array.isArray(detalle.creditos)?detalle.creditos:[];
                                                const pagoMinCred = creditos.reduce((s,c)=>{
                                                    const cuotaBase = Number((c.valor_cuota ?? c.cuota) || 0);
                                                    const saldoMora = Number(c.saldo_mora || 0);
                                                    const montoCob = Number(c.monto_cobranza || 0);
                                                    return s + ((saldoMora>0?saldoMora:cuotaBase) + montoCob);
                                                },0);
                                                const total = productosMensual + pagoMinCred;
                                                return `
                                                    <div><strong>Valor mensual de productos:</strong> $${productosMensual.toLocaleString('es-CO')}</div>
                                                    <div><strong>Valor pago mínimo créditos:</strong> $${pagoMinCred.toLocaleString('es-CO')}</div>
                                                    <div><strong>Valor total:</strong> $${total.toLocaleString('es-CO')}</div>
                                                `;
                                            } catch(e) { return ''; }
                                        })()}
                                    </div>
								</div>
							</div>
						</div>
					`;
				}
				
				// Información de créditos
				if (detalle.creditos && detalle.creditos.length > 0) {
					html += `
						<div class="row g-3 mb-3">
							<div class="col-12">
								<div class="card">
									<div class="card-header"><strong>Información crédito</strong></div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-sm table-hover">
												<thead class="table-light">
                                                    <tr>
                                                        <th>Crédito</th>
                                                        <th>Tipo Préstamo</th>
                                                        <th>Plazo</th>
                                                        <th>Valor Cuota</th>
                                                        <th>Cuotas Pendientes</th>
                                                        <th>Tasa Interés</th>
                                                        <th>Deuda Capital</th>
                                                        <th>Días Mora</th>
                                                        <th>Saldo Mora</th>
                                                        <th>Monto Cobranza</th>
                                                        <th>Pago mínimo</th>
                                                        <th>Fecha de Pago</th>
                                                    </tr>
												</thead>
												<tbody>
					`;
					
					for (const credito of detalle.creditos) {
                        const fechaPago = credito.fecha_pago ? new Date(credito.fecha_pago).toLocaleDateString('es-CO') : '-';
                        const valorCuota = Number((credito.valor_cuota ?? credito.cuota) || 0);
                        const cuotasPend = Number(credito.cuotas_pendientes || 0);
                        const saldoMora = Number(credito.saldo_mora || 0);
                        const montoCob = Number(credito.monto_cobranza || 0);
                        const pagoMin = (saldoMora>0?saldoMora:valorCuota) + montoCob;
                        html += `
                            <tr>
                                <td>${credito.numero_credito || ''}</td>
                                <td>${credito.tipo_prestamo || ''}</td>
                                <td>${Number(credito.plazo || 0)}</td>
                                <td>$${valorCuota.toLocaleString('es-CO')}</td>
                                <td>${cuotasPend}</td>
                                <td>${(Number(credito.tasa || 0) * 100).toFixed(2)}%</td>
                                <td>$${Number(credito.deuda_capital || 0).toLocaleString('es-CO')}</td>
                                <td>${Number(credito.dias_mora || 0)}</td>
                                <td>$${saldoMora.toLocaleString('es-CO')}</td>
                                <td>$${montoCob.toLocaleString('es-CO')}</td>
                                <td>$${pagoMin.toLocaleString('es-CO')}</td>
                                <td>${fechaPago}</td>
                            </tr>
                            ${(credito.codeudor_nombre||credito.codeudor_celular||credito.codeudor_email||credito.codeudor_direccion)?`
                            <tr class="table-light">
                                <td colspan="12">
                                    <div class="small text-muted">Codeudor: <strong>${(credito.codeudor_nombre||'').replace(/</g,'&lt;')}</strong> · Teléfono: <strong>${(credito.codeudor_celular||'')}</strong> · Email: <strong>${(credito.codeudor_email||'')}</strong> · Dirección: <strong>${(credito.codeudor_direccion||'').replace(/</g,'&lt;')}</strong></div>
                                </td>
                            </tr>`:''}
                        `;
					}
					
					html += `
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					`;
				}
				
				// Información de productos
				if (detalle.productos && detalle.productos.length > 0) {
					html += `
						<div class="row g-3 mb-3">
							<div class="col-12">
								<div class="card">
									<div class="card-header"><strong>Información de productos</strong></div>
									<div class="card-body">
										<div class="table-responsive">
											<table class="table table-sm table-hover">
												<thead class="table-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Producto</th>
                                                        <th>Día Pago</th>
                                                        <th>Monto Pago</th>
                                                        <th>Estado</th>
                                                    </tr>
												</thead>
												<tbody>
					`;
					
					for (const producto of detalle.productos) {
						const estado = producto.estado_activo ? 'Activo' : 'Inactivo';
						const estadoClass = producto.estado_activo ? 'bg-success' : 'bg-secondary';
						html += `
							<tr>
								<td>${Number(producto.id || 0)}</td>
								<td>${producto.producto_nombre || ''}</td>
								<td>${Number(producto.dia_pago || 0)}</td>
                                <td>$${Number(producto.monto_pago || 0).toLocaleString('es-CO')}</td>
								<td><span class="badge ${estadoClass}">${estado}</span></td>
							</tr>
						`;
					}
					
					html += `
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					`;
				}
				
				// Si no hay información
				if (!detalle.asociado && (!detalle.creditos || detalle.creditos.length === 0) && (!detalle.productos || detalle.productos.length === 0)) {
					html = '<div class="text-muted">No hay información disponible para este asociado.</div>';
				}
				
				document.getElementById('detalleContenido').innerHTML = html;
			}).catch(() => {
				document.getElementById('detalleContenido').innerHTML = '<div class="text-danger small">Error al cargar el detalle.</div>';
			});
	});

	function cargarHistorialDesdeBoton(btn) {
		const nombre = btn.getAttribute('data-nombre') || '';
		const cedula = btn.getAttribute('data-cedula') || '';
		cargarHistorial(cedula, nombre);
	}

	function cargarHistorial(cedula, nombre) {
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
					const dataTipo = String(it.tipo_comunicacion||'').replace(/\"/g,'&quot;');
					const dataEstado = String(it.estado||'').replace(/\"/g,'&quot;');
					const dataComentario = String(it.comentario||'').replace(/\"/g,'&quot;').replace(/</g,'&lt;');
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
								<button type="button" class="btn btn-sm btn-outline-secondary ms-1" data-action="edit" data-id="${it.id}" title="Editar"><i class="fas fa-pen"></i></button>
								<button type="button" class="btn btn-sm btn-outline-danger ms-1" data-action="delete" data-id="${it.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
								`) : ''}
							</div>
						</div>
						<div class="small">${safeComentario}</div>
					</div>`;
				}
				html += '</div>';
				document.getElementById('histContenido').innerHTML = html;
				// Cargar el snapshot y renderizarlo debajo de cada comunicación
				const histContEl = document.getElementById('histContenido');
				histContEl.querySelectorAll('.list-group-item').forEach((itemEl) => {
					const commId = itemEl.getAttribute('data-id');
					const snapWrap = document.createElement('div');
					snapWrap.className = 'mt-2 border-top pt-2 small';
					snapWrap.innerHTML = '<div class="text-muted">Cargando detalle…</div>';
					itemEl.appendChild(snapWrap);
					fetch(`../api/cobranza_detalle_snapshot.php?id=${encodeURIComponent(commId)}`)
						.then(r=>r.json()).then(data => {
							if (!(data && data.success)) { snapWrap.innerHTML = ''; return; }
							const d = data.data||{}; const det = d.detalle; const crs = d.creditos||[];
							if (!det) { snapWrap.innerHTML = ''; return; }
							let shtml = '<div class="mb-1"><strong>Detalle al momento de la comunicación</strong></div>';
							shtml += `<div class="mb-2">Aportes: $${Number(det.aportes_monto||0).toLocaleString('es-CO')} · Total créditos: ${Number(det.total_creditos||0)} · Fecha: ${String(det.fecha||'')}</div>`;
							if (crs.length) {
								shtml += '<div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Crédito</th><th>Deuda capital</th><th>Deuda mora</th><th>Días mora</th><th>Fecha pago</th></tr></thead><tbody>';
								shtml += crs.map(c=>`<tr><td>${(c.numero_credito||'').replace(/</g,'&lt;')}</td><td>$${Number(c.deuda_capital||0).toLocaleString('es-CO')}</td><td>$${Number(c.deuda_mora||0).toLocaleString('es-CO')}</td><td>${Number(c.dias_mora||0)}</td><td>${c.fecha_pago?new Date(c.fecha_pago).toLocaleDateString('es-CO'):'-'}</td></tr>`).join('');
								shtml += '</tbody></table></div>';
							}
							snapWrap.innerHTML = shtml;
						}).catch(()=>{ snapWrap.innerHTML = ''; });
				});
			}).catch(() => {
				document.getElementById('histContenido').innerHTML = '<div class="text-danger small">Error al cargar el historial.</div>';
			});
	}

	// Cuando se abre el offcanvas desde el botón de la tabla, cargar historial con sus datos
	const offHistEl = document.getElementById('offHistorial');
	if (offHistEl) {
		offHistEl.addEventListener('show.bs.offcanvas', (ev) => {
			const btn = ev.relatedTarget;
		if (!btn) return;
			const ced = btn.getAttribute('data-cedula') || '';
			const nom = btn.getAttribute('data-nombre') || '';
			cargarHistorial(ced, nom);
		});
	}

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
						mostrarToast('Comunicación eliminada');
						if (typeof cargarTablaCom === 'function') { cargarTablaCom(); }
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

	const editFormEl = document.getElementById('formEditarCom');
	if (editFormEl) {
		editFormEl.addEventListener('submit', (ev) => {
		ev.preventDefault();
		const fd = new FormData(ev.target);
		fetch('../api/cobranza_editar_comunicacion.php', { method: 'POST', body: fd })
			.then(r=>r.json()).then(data => {
				if (data.success) {
						const modalEl = document.getElementById('modalEditarCom');
						if (modalEl) { const mdl = bootstrap.Modal.getInstance(modalEl); if (mdl) mdl.hide(); }
					const oc = bootstrap.Offcanvas.getInstance(document.getElementById('offHistorial'));
					if (oc) oc.hide();
					mostrarToast('Comunicación actualizada');
					if (typeof cargarTablaCom === 'function') { cargarTablaCom(); }
				} else { alert(data.message||'Error'); }
			}).catch(()=>alert('Error solicitud'));
	});
	}

	const modalCom = document.getElementById('modalComunicacion');
	modalCom.addEventListener('show.bs.modal', (ev) => {
		const btn = ev.relatedTarget;
		document.getElementById('comNombre').textContent = btn.getAttribute('data-nombre');
		document.getElementById('comCedula').textContent = btn.getAttribute('data-cedula');
		document.getElementById('comCedulaInput').value = btn.getAttribute('data-cedula');
		
		// Limpiar el formulario para evitar confusión con valores anteriores
		const form = document.getElementById('formComunicacion');
		form.reset();
		
		// Establecer fecha actual por defecto
		const fechaInput = form.querySelector('input[name="fecha"]');
		if (fechaInput) {
			const now = new Date();
			const year = now.getFullYear();
			const month = String(now.getMonth() + 1).padStart(2, '0');
			const day = String(now.getDate()).padStart(2, '0');
			const hours = String(now.getHours()).padStart(2, '0');
			const minutes = String(now.getMinutes()).padStart(2, '0');
			fechaInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
		}
	});

	document.getElementById('formComunicacion').addEventListener('submit', (ev) => {
		ev.preventDefault();
		const fd = new FormData(ev.target);
		fetch('../api/cobranza_crear_comunicacion.php', { method: 'POST', body: fd })
			.then(r => r.json()).then(data => {
				if (data.success) {
					bootstrap.Modal.getInstance(modalCom).hide();
					mostrarToast('Comunicación registrada');
					if (typeof cargarTablaCom === 'function') { cargarTablaCom(); }
					// Abrir/refrescar historial del asociado
					const ced = document.getElementById('comCedulaInput').value || '';
					const nom = document.getElementById('comNombre').textContent || '';
					const off = new bootstrap.Offcanvas(document.getElementById('offHistorial'));
					off.show();
					cargarHistorial(ced, nom);
				} else {
					alert('Error: ' + (data.message||'No se pudo registrar'));
				}
			})
			.catch(() => alert('Error en solicitud'));
	});

	// Tabla paginada con orden
	window.comPage = 1; window.comPages = 1; window.comSortBy = 'max_diav'; window.comSortDir = 'DESC';
	document.querySelectorAll('#tablaCom thead th.sortable').forEach(th => th.addEventListener('click', () => cambiarOrdenCom(th.dataset.sort)));
	document.getElementById('comPrev').addEventListener('click', () => cambiarPaginaCom(-1));
	document.getElementById('comNext').addEventListener('click', () => cambiarPaginaCom(1));
	cargarTablaCom();

	function cambiarOrdenCom(col) {
		if (!col) return; if (window.comSortBy === col) { window.comSortDir = (window.comSortDir === 'ASC') ? 'DESC' : 'ASC'; } else { window.comSortBy = col; window.comSortDir = 'ASC'; }
		cargarTablaCom();
	}
	function cambiarPaginaCom(delta) {
		const np = window.comPage + delta; if (np < 1 || np > window.comPages) return; window.comPage = np; cargarTablaCom();
	}
	async function cargarTablaCom() {
		const params = new URLSearchParams({
			page: window.comPage, limit: 10, sort_by: window.comSortBy, sort_dir: window.comSortDir,
			q: '<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES); ?>',
			estado: '<?php echo htmlspecialchars($_GET['estado'] ?? '', ENT_QUOTES); ?>',
			rango: '<?php echo htmlspecialchars($_GET['rango'] ?? '', ENT_QUOTES); ?>'
		});
		const tbody = document.getElementById('comBody');
		tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Cargando…</td></tr>';
		try {
			const res = await fetch('../api/cobranza_listar_paginado.php?' + params.toString());
			const json = await res.json();
			console.log('cobranza_listar_paginado response:', json);
			if (!(json && json.success)) {
				tbody.innerHTML = `<tr><td colspan="7" class="text-danger">${(json && json.message) ? json.message : 'Error en la API'}</td></tr>`;
				return;
			}
			const data = json && json.data ? json.data : {};
			const items = data.items || [];
			window.comPages = data.pages || 1;
			document.getElementById('comResumen').textContent = `Página ${data.current_page || window.comPage} de ${window.comPages} · Total: ${data.total || items.length}`;
			if (!items.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Sin datos.</td></tr>'; return; }
			tbody.innerHTML = items.map(row => {
				const estado = (function(maxd){ if (maxd>=91) return {label:'Jurídico',color:'danger'}; if (maxd>=61) return {label:'Prejurídico',color:'warning'}; return {label:'Persuasiva',color:'primary'}; })(Number(row.max_diav||0));
				let ultimaHtml = '<small class="badge bg-danger">Sin comunicación</small>';
				if (row.ultima_comunicacion) {
					const d = Number(row.dias_ultima||0);
					const label = (d<2?'Muy reciente':(d<5?'Reciente':(d<10?'Intermedia':(d<=20?'Lejana':'Muy lejana'))));
					const color = (label==='Muy reciente'?'success':(label==='Reciente'?'primary':(label==='Intermedia'?'warning':(label==='Lejana'?'secondary':'dark'))));
					ultimaHtml = `<small class="badge bg-${color}">${label}</small><div class="small text-muted mt-1" title="${row.ultima_comunicacion}">${row.ultima_comunicacion}</div>`;
				}
				return `<tr>
					<td><span class="cursor-pointer" onclick="navigator.clipboard.writeText('${row.cedula||''}')">${row.cedula||''}</span></td>
					<td>${(row.nombre||'').replace(/</g,'&lt;')}</td>
					<td><div class="small">Mora: <strong>$${Number(row.total_mora||0).toLocaleString('es-CO')}</strong></div><div class="small text-muted">Cartera: <strong>$${Number(row.total_cartera||0).toLocaleString('es-CO')}</strong></div></td>
					<td class="text-center">${Number(row.max_diav||0)}</td>
					<td><span class="badge bg-${estado.color}">${estado.label}</span></td>
					<td>${ultimaHtml}</td>
					<td class="text-end">
						<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetalle" data-cedula="${row.cedula||''}" data-nombre="${(row.nombre||'').replace(/</g,'&lt;')}"><i class="fas fa-eye"></i></button>
						<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#offHistorial" aria-controls="offHistorial" data-cedula="${row.cedula||''}" data-nombre="${(row.nombre||'').replace(/</g,'&lt;')}"><i class="fas fa-clock"></i> Historial</button>
						<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalComunicacion" data-cedula="${row.cedula||''}" data-nombre="${(row.nombre||'').replace(/</g,'&lt;')}"><i class="fas fa-plus"></i> Comunicación</button>
					</td>
				</tr>`;
			}).join('');
		} catch (e) {
			tbody.innerHTML = '<tr><td colspan="7" class="text-danger">Error al cargar.</td></tr>';
		}
	}
});

function mostrarToast(mensaje) {
	let cont = document.getElementById('toastContainer');
	if (!cont) { cont = document.createElement('div'); cont.id = 'toastContainer'; cont.className = 'toast-container position-fixed top-0 end-0 p-3'; document.body.appendChild(cont); }
	const el = document.createElement('div'); el.className = 'toast align-items-center text-bg-success border-0'; el.role = 'alert'; el.ariaLive = 'assertive'; el.ariaAtomic = 'true';
	el.innerHTML = `<div class="d-flex"><div class="toast-body">${mensaje.replace(/</g,'&lt;')}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
	cont.appendChild(el); const toast = new bootstrap.Toast(el, { delay: 3000 }); toast.show(); el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>

<?php include '../../../views/layouts/footer.php'; ?>


