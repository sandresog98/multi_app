<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$authController = new AuthController();
$authController->requireModule('cobranza.comunicaciones');
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Cobranza - Comms Aportes';
$currentPage = 'cobranza_comunicaciones_aportes';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
	<div class="row">
		<?php include '../../../views/layouts/sidebar.php'; ?>
		<main class="col-12 main-content">
			<div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center">
				<h1 class="h2"><i class="fas fa-comments me-2"></i>Comms Aportes</h1>
			</div>

			<div class="card">
                <div class="card-body">
                    <form class="row g-2" id="filtrosForm">
                        <div class="col-sm-4 col-md-4">
							<input type="text" class="form-control" name="q" id="q" placeholder="Buscar por cédula, nombre, teléfono o email">
						</div>
                        <div class="col-sm-4 col-md-4">
                            <select class="form-select" id="ult_comm">
                                <option value="">Última comunicación (todas)</option>
                                <option value="sin">Sin comunicación</option>
                                <option value="muy_reciente">Muy reciente (&lt; 2 días)</option>
                                <option value="reciente">Reciente (2–5 días)</option>
                                <option value="intermedia">Intermedia (5–10 días)</option>
                                <option value="lejana">Lejana (10–20 días)</option>
                                <option value="muy_lejana">Muy lejana (&gt; 20 días)</option>
                            </select>
                        </div>
                        <div class="col-sm-4 col-md-3">
                            <select class="form-select" id="rango">
                                <option value="">Días sin aportes (todos)</option>
                                <option value="30_60">30 a 60</option>
                                <option value="60_90">60 a 90</option>
                                <option value="90_mas">Más de 90</option>
                            </select>
                        </div>
                        <div class="col-sm-2 col-md-1">
							<button class="btn btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i>Buscar</button>
						</div>
                        <div class="col-sm-2 col-md-1">
							<button class="btn btn-outline-secondary w-100" type="button" id="btnClear"><i class="fas fa-eraser me-1"></i>Limpiar</button>
						</div>
					</form>
				</div>
			</div>

			<div class="card mt-3">
				<div class="card-header d-flex justify-content-between align-items-center">
					<strong>Asociados con mora de aportes</strong>
					<small class="text-muted">Listado paginado</small>
				</div>
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center mb-2">
						<div class="small text-muted" id="listResumen"></div>
						<div class="btn-group btn-group-sm" role="group">
							<button class="btn btn-outline-secondary" id="btnPrev">«</button>
							<button class="btn btn-outline-secondary" id="btnNext">»</button>
						</div>
					</div>
					<div class="table-responsive">
                        <table class="table table-sm align-middle" id="tablaAportes">
							<thead class="table-light">
								<tr>
									<th>Cédula</th>
									<th>Nombre</th>
									<th>Último aporte</th>
									<th class="text-center">Días sin aporte</th>
                                    <th>Última comunicación</th>
									<th class="text-end">Acciones</th>
								</tr>
							</thead>
							<tbody id="apBody"></tbody>
						</table>
					</div>
				</div>
			</div>

		</main>
	</div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  window.apPage = 1; window.apPages = 1;
  const form = document.getElementById('filtrosForm');
  const q = document.getElementById('q');
  const ultComm = document.getElementById('ult_comm');
  const rango = document.getElementById('rango');
  const tbody = document.getElementById('apBody');

  form.addEventListener('submit', (ev)=>{ ev.preventDefault(); window.apPage=1; cargar(); });
  document.getElementById('btnClear').addEventListener('click', ()=>{ q.value=''; ultComm.value=''; rango.value=''; window.apPage=1; cargar(); });
  document.getElementById('btnPrev').addEventListener('click', ()=>{ const np = window.apPage-1; if (np<1) return; window.apPage=np; cargar(); });
  document.getElementById('btnNext').addEventListener('click', ()=>{ const np = window.apPage+1; if (np>window.apPages) return; window.apPage=np; cargar(); });

  async function cargar(){
    const params = new URLSearchParams({ page:String(window.apPage), limit:'20', q:q.value.trim(), ult_comm:ultComm.value, rango:rango.value });
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Cargando…</td></tr>';
    try{
      const r = await fetch('../api/aportes_listar_paginado.php?'+params.toString());
      const j = await r.json();
      if(!(j&&j.success)){ tbody.innerHTML = '<tr><td colspan="6" class="text-danger">Error</td></tr>'; return; }
      const data = j.data||{}; const items = data.items||[]; window.apPages = data.pages||1; document.getElementById('listResumen').textContent = `Página ${data.current_page||1} de ${window.apPages} · Total: ${data.total||items.length}`;
      if(!items.length){ tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Sin datos.</td></tr>'; return; }
      tbody.innerHTML = items.map(it=>{
        const fecha = it.ultima_aporte ? new Date(it.ultima_aporte).toLocaleDateString('es-CO') : '-';
        let ultimaHtml = '<small class="badge bg-danger">Sin comunicación</small>';
        if (it.ultima_comunicacion) {
          const d = Number(it.dias_ultima||0);
          const label = (d<2?'Muy reciente':(d<5?'Reciente':(d<10?'Intermedia':(d<=20?'Lejana':'Muy lejana'))));
          const color = (label==='Muy reciente'?'success':(label==='Reciente'?'primary':(label==='Intermedia'?'warning':(label==='Lejana'?'secondary':'dark'))));
          ultimaHtml = `<small class="badge bg-${color}">${label}</small><div class="small text-muted mt-1" title="${it.ultima_comunicacion}">${it.ultima_comunicacion}</div>`;
        }
        return `<tr>
          <td><span class="cursor-pointer" onclick="navigator.clipboard.writeText('${(it.cedula||'')}')">${(it.cedula||'')}</span></td>
          <td>${String(it.nombre||'').replace(/</g,'&lt;')}</td>
          <td>${fecha}</td>
          <td class="text-center">${Number(it.dias_sin_aporte||0)}</td>
          <td>${ultimaHtml}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-action="verDetalle" data-cedula="${(it.cedula||'')}" data-nombre="${String(it.nombre||'').replace(/</g,'&lt;')}"><i class="fas fa-eye"></i></button>
            <button class="btn btn-sm btn-outline-secondary" data-action="verHist" data-cedula="${(it.cedula||'')}" data-nombre="${String(it.nombre||'').replace(/</g,'&lt;')}"><i class="fas fa-clock"></i> Historial</button>
            <button class="btn btn-sm btn-primary" data-cedula="${(it.cedula||'')}" data-nombre="${String(it.nombre||'').replace(/</g,'&lt;')}" data-action="nuevaCom"><i class="fas fa-plus"></i> Comunicación</button>
          </td>
        </tr>`;
      }).join('');
    }catch(e){ tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Error al cargar.</td></tr>'; }
  }

  document.addEventListener('click', (ev)=>{
    const btn = ev.target.closest('button[data-action]');
    if(!btn) return;
    const act = btn.getAttribute('data-action');
    if (act === 'nuevaCom') {
      abrirNuevaCom(btn.getAttribute('data-cedula')||'', btn.getAttribute('data-nombre')||'');
    } else if (act === 'verDetalle') {
      abrirDetalle(btn.getAttribute('data-cedula')||'', btn.getAttribute('data-nombre')||'');
    } else if (act === 'verHist') {
      abrirHistorial(btn.getAttribute('data-cedula')||'', btn.getAttribute('data-nombre')||'');
    }
  });

  function abrirNuevaCom(cedula, nombre){
    const html = `<div class="modal fade" id="modalComAportes" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" id="formComAp">
      <div class="modal-header"><h5 class="modal-title">Nueva comunicación (Aportes) - ${nombre} (${cedula})</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="cedula" value="${cedula}">
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
      <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
    </form></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('modalComAportes'); const mdl = new bootstrap.Modal(el); mdl.show();
    el.addEventListener('hidden.bs.modal', ()=>el.remove());
    // Prefijar fecha actual por defecto
    try{
      const fechaInput = el.querySelector('input[name="fecha"]');
      if (fechaInput) {
        const now = new Date();
        const y = now.getFullYear(); const m = String(now.getMonth()+1).padStart(2,'0'); const d = String(now.getDate()).padStart(2,'0');
        const hh = String(now.getHours()).padStart(2,'0'); const mm = String(now.getMinutes()).padStart(2,'0');
        fechaInput.value = `${y}-${m}-${d}T${hh}:${mm}`;
      }
    }catch(_){}
    el.querySelector('#formComAp').addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      const fd = new FormData(ev.target);
      try{
        fd.append('tipo_origen','aportes');
        const r = await fetch('../api/cobranza_crear_comunicacion.php', { method:'POST', body: fd });
        const j = await r.json();
        if(!(j&&j.success)){ alert(j&&j.message?j.message:'Error'); return; }
        mdl.hide();
        // Refrescar datos y abrir historial del asociado creado (replicando Comms Crédito)
        if (typeof cargar === 'function') { cargar(); }
        mostrarToast('Comunicación registrada');
        abrirHistorial(cedula, nombre);
      }catch(e){ alert('Error solicitud'); }
    });
  }

  function abrirDetalle(cedula, nombre){
    const html = `<div class="modal fade" id="modalDetAportes" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detalle de aportes — ${nombre} (${cedula})</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="detAportesBody">Cargando…</div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById('modalDetAportes'); const mdl = new bootstrap.Modal(el); mdl.show();
    el.addEventListener('hidden.bs.modal', ()=>el.remove());
    cargarDetalleAportes(cedula);
  }

  async function cargarDetalleAportes(cedula){
    const body = document.getElementById('detAportesBody');
    try{
      // 1) Traer detalle general (igual a Comms Crédito)
      const rGen = await fetch('../api/cobranza_detalle.php?cedula='+encodeURIComponent(cedula));
      const jGen = await rGen.json();
      if(!(jGen&&jGen.success&&jGen.data)){ body.innerHTML = '<div class="text-danger">Error al cargar detalle general.</div>'; return; }
      const detalle = jGen.data;
      // 1b) Traer último aporte para mostrarlo en Información monetaria
      let ultAporteStr = '—';
      try {
        const rAp0 = await fetch('../api/aportes_detalle.php?cedula='+encodeURIComponent(cedula));
        const jAp0 = await rAp0.json();
        const ua = jAp0 && jAp0.success && jAp0.data ? jAp0.data.ultimo_aporte : null;
        if (ua) {
          ultAporteStr = new Date(ua).toLocaleDateString('es-CO');
        }
      } catch {}
      let html = '';
      // Información del asociado y monetaria + valor de pagos (copiado de comunicaciones.php)
      if (detalle.asociado) {
        html += `
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="card"><div class="card-header"><strong>Información del asociado</strong></div><div class="card-body">
                <div><strong>Nombre:</strong> ${detalle.asociado.nombre || ''}</div>
                <div><strong>Cédula:</strong> ${detalle.asociado.cedula || ''}</div>
                <div><strong>Teléfono:</strong> ${detalle.asociado.celula || ''}</div>
                <div><strong>Email:</strong> ${detalle.asociado.mail || ''}</div>
                <div><strong>Ciudad:</strong> ${detalle.asociado.ciudad || ''}</div>
                <div><strong>Dirección:</strong> ${detalle.asociado.direcc || ''}</div>
                <div><strong>Fecha de nacimiento:</strong> ${detalle.asociado.fecnac ? new Date(detalle.asociado.fecnac).toLocaleDateString('es-CO') : '-'}</div>
                <div><strong>Edad:</strong> ${detalle.asociado.fecnac ? (Math.floor((Date.now() - new Date(detalle.asociado.fecnac).getTime()) / (365.25*24*3600*1000))) + ' años' : '-'}</div>
                <div><strong>Fecha de afiliación:</strong> ${detalle.asociado.fechai ? new Date(detalle.asociado.fechai).toLocaleDateString('es-CO') : '-'}</div>
              </div></div>
            </div>
            <div class="col-md-6">
              <div class="card"><div class="card-header"><strong>Información monetaria</strong></div><div class="card-body">
                <div><strong>Aportes Totales:</strong> $${Number(detalle.monetarios?.aportes_totales || 0).toLocaleString('es-CO')} <small class="text-muted">(Incentivos: $${Number(detalle.monetarios?.aportes_incentivos || 0).toLocaleString('es-CO')})</small></div>
                <div><strong>Revalorizaciones de aportes:</strong> $${Number(detalle.monetarios?.aportes_revalorizaciones || 0).toLocaleString('es-CO')}</div>
                <div><strong>Plan Futuro:</strong> $${Number(detalle.monetarios?.plan_futuro || 0).toLocaleString('es-CO')}</div>
                <div><strong>Bolsillos:</strong> $${Number(detalle.monetarios?.bolsillos || 0).toLocaleString('es-CO')} <small class="text-muted">(Incentivos: $${Number(detalle.monetarios?.bolsillos_incentivos || 0).toLocaleString('es-CO')})</small></div>
                <div><strong>Último aporte:</strong> ${ultAporteStr}</div>
              </div></div>
              <div class="card mt-2"><div class="card-header"><strong>Valor de pagos</strong></div><div class="card-body">
                ${(()=>{ try{ const asign = Array.isArray(detalle.productos)?detalle.productos:[]; const vProd = asign.filter(p=>p.estado_activo).reduce((s,p)=>s+Number(p.monto_pago||0),0); const crs = Array.isArray(detalle.creditos)?detalle.creditos:[]; const vMin = crs.reduce((acc,c)=>{ const cuotaBase=Number((c.valor_cuota??c.cuota)||0); const saldoMora=Number(c.saldo_mora||0); const montoCob=Number(c.monto_cobranza||0); return acc + ((saldoMora>0?saldoMora:cuotaBase)+montoCob); },0); const total=vProd+vMin; return `<div><strong>Valor mensual de productos:</strong> $${vProd.toLocaleString('es-CO')}</div><div><strong>Valor pago mínimo créditos:</strong> $${vMin.toLocaleString('es-CO')}</div><div><strong>Valor total:</strong> $${total.toLocaleString('es-CO')}</div>`; }catch(e){ return ''; } })()}
              </div></div>
            </div>
          </div>`;
      }
      // Información de créditos (mismo formato compacto)
      if (detalle.creditos && detalle.creditos.length>0){
        html += `<div class="row g-3 mb-3"><div class="col-12"><div class="card"><div class="card-header"><strong>Información crédito</strong></div><div class="card-body"><div class="table-responsive small"><table class="table table-sm table-hover align-middle mb-0"><thead class="table-light"><tr><th>Crédito</th><th>Tipo</th><th class="text-center">Cuotas</th><th class="text-center">Pendientes</th><th class="text-center text-nowrap">F. Inicio</th><th class="text-center text-nowrap">F. Vencimiento</th><th class="text-center text-nowrap">F. Pago</th><th class="text-center">Días Mora</th><th class="text-end">V. Capital</th><th class="text-end">V. Cuota</th><th class="text-end">V. Mora</th><th class="text-end">Cobranza</th><th class="text-end">Pago mínimo</th></tr></thead><tbody>` +
        detalle.creditos.map(c=>{ const fI=c.fecha_inicio?new Date(c.fecha_inicio).toLocaleDateString('es-CO'):'-'; const fV=c.fecha_vencimiento?new Date(c.fecha_vencimiento).toLocaleDateString('es-CO'):'-'; const fP=c.fecha_pago?new Date(c.fecha_pago).toLocaleDateString('es-CO'):'-'; const vq=Number((c.valor_cuota??c.cuota)||0); const sM=Number(c.saldo_mora||0); const mc=Number(c.monto_cobranza||0); const pmin=(sM>0?sM:vq)+mc; return `<tr><td>${String(c.numero_credito||'').replace(/</g,'&lt;')}</td><td>${String(c.tipo_prestamo||'').replace(/</g,'&lt;')}</td><td class="text-center">${Number(c.plazo||0)}</td><td class="text-center">${Number(c.cuotas_pendientes||0)}</td><td class="text-center text-nowrap">${fI}</td><td class="text-center text-nowrap">${fV}</td><td class="text-center text-nowrap">${fP}</td><td class="text-center">${Number(c.dias_mora||0)}</td><td class="text-end">$${Number(c.deuda_capital||0).toLocaleString('es-CO')}</td><td class="text-end">$${vq.toLocaleString('es-CO')}</td><td class="text-end">$${sM.toLocaleString('es-CO')}</td><td class="text-end">$${mc.toLocaleString('es-CO')}</td><td class="text-end">$${pmin.toLocaleString('es-CO')}</td></tr>`; }).join('') + `</tbody></table></div></div></div></div></div>`;
      }

      body.innerHTML = html || '<div class="text-muted">Sin información.</div>';
    }catch(e){ body.innerHTML = '<div class="text-danger">Error.</div>'; }
  }

  function abrirHistorial(cedula, nombre){
    let off = document.getElementById('offHistAportes');
    if(!off){
      const html = `<div class="offcanvas offcanvas-end" tabindex="-1" id="offHistAportes"><div class="offcanvas-header"><h5 class="offcanvas-title">Historial (Aportes)</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"><div class="mb-2 text-muted">Asociado: <strong><span id="haNom"></span></strong> (<span id="haCed"></span>)</div><div id="haCont" class="small">Cargando…</div></div></div>`;
      document.body.insertAdjacentHTML('beforeend', html);
      off = document.getElementById('offHistAportes');
    }
    document.getElementById('haNom').textContent = nombre;
    document.getElementById('haCed').textContent = cedula;
    document.getElementById('haCont').innerHTML = 'Cargando…';
    const oc = new bootstrap.Offcanvas(off); oc.show();
    fetch(`../api/cobranza_historial.php?cedula=${encodeURIComponent(cedula)}&tipo_origen=aportes`).then(r=>r.json()).then(data=>{
      const rows = data&&data.items?data.items:[];
      if(!rows.length){ document.getElementById('haCont').innerHTML = '<div class="text-muted">Sin comunicaciones registradas.</div>'; return; }
      let html = '<div class="list-group">';
      for(const it of rows){
        const safeComentario = String(it.comentario||'').replace(/</g,'&lt;');
        html += `<div class="list-group-item"><div class="d-flex justify-content-between"><div><strong>${it.tipo_comunicacion}</strong> · ${it.estado}</div><div class="text-end"><small class="text-muted">${(it.fecha_comunicacion||'').replace('T',' ')}</small></div></div><div class="small">${safeComentario}</div></div>`;
      }
      html += '</div>';
      document.getElementById('haCont').innerHTML = html;
    }).catch(()=>{ document.getElementById('haCont').innerHTML = '<div class="text-danger">Error al cargar.</div>'; });
  }

  cargar();
});

// Toast reutilizable (igual a Comms Crédito)
function mostrarToast(mensaje) {
  let cont = document.getElementById('toastContainer');
  if (!cont) { cont = document.createElement('div'); cont.id = 'toastContainer'; cont.className = 'toast-container position-fixed top-0 end-0 p-3'; document.body.appendChild(cont); }
  const el = document.createElement('div'); el.className = 'toast align-items-center text-bg-success border-0'; el.role = 'alert'; el.ariaLive = 'assertive'; el.ariaAtomic = 'true';
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${String(mensaje||'').replace(/</g,'&lt;')}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  cont.appendChild(el); const toast = new bootstrap.Toast(el, { delay: 3000 }); toast.show(); el.addEventListener('hidden.bs.toast', () => el.remove());
}
</script>


