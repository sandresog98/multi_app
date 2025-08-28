<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$authController = new AuthController();
$authController->requireModule('boleteria.categorias');
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Boletería - Categorías';
$currentPage = 'boleteria_categorias';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
        <h1 class="h2 mb-0"><i class="fas fa-tags me-2"></i>Categorías</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCategoria"><i class="fas fa-plus me-1"></i>Nueva categoría</button>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted" id="catResumen"></div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Paginación">
              <button class="btn btn-outline-secondary" id="catPrev">«</button>
              <button class="btn btn-outline-secondary" id="catNext">»</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-striped align-middle" id="tablaCategorias">
              <thead class="table-light">
                <tr>
                  <th data-sort="nombre" class="sortable">Nombre</th>
                  <th data-sort="precio_compra" class="text-end sortable">Precio compra</th>
                  <th data-sort="precio_venta" class="text-end sortable">Precio venta</th>
                  <th data-sort="estado" class="sortable">Estado</th>
                  <th data-sort="" >Descripción</th>
                  <th data-sort="fecha_actualizacion" class="sortable">Actualización</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody id="categoriasBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Crear/Editar Categoría (solo UI, sin backend aún) -->
      <div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Nueva categoría</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="formCategoria">
                <div class="mb-3">
                  <label class="form-label">Nombre</label>
                  <input type="text" class="form-control" id="catNombre" placeholder="Nombre de la categoría" required>
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label">Precio compra</label>
                    <input type="number" class="form-control" id="catPrecioCompra" step="0.01" min="0" placeholder="0.00" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Precio venta</label>
                    <input type="number" class="form-control" id="catPrecioVenta" step="0.01" min="0" placeholder="0.00" required>
                  </div>
                </div>
                <div class="mb-3 mt-2">
                  <label class="form-label">Estado</label>
                  <select class="form-select" id="catEstado">
                    <option value="activo" selected>Activo</option>
                    <option value="inactivo">Inactivo</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Descripción</label>
                  <textarea class="form-control" id="catDescripcion" rows="3" placeholder="Opcional"></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id="btnGuardarCategoria">Guardar</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  cargarCategorias();
  document.querySelectorAll('#tablaCategorias thead th.sortable').forEach(th => th.addEventListener('click', () => cambiarOrdenCat(th.dataset.sort)));
  document.getElementById('catPrev').addEventListener('click', () => cambiarPaginaCat(-1));
  document.getElementById('catNext').addEventListener('click', () => cambiarPaginaCat(1));
  document.getElementById('btnGuardarCategoria').addEventListener('click', guardarCategoria);
});

let catPage = 1, catPages = 1, catSortBy = 'nombre', catSortDir = 'ASC';

function formatoMoneda(n) {
  const v = Number(n || 0);
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v);
}

async function cargarCategorias() {
  const tbody = document.getElementById('categoriasBody');
  tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Cargando…</td></tr>';
  try {
    const params = new URLSearchParams({ page: catPage, limit: 10, sort_by: catSortBy, sort_dir: catSortDir });
    const res = await fetch('../../boleteria/api/categorias_listar.php?' + params.toString());
    const json = await res.json();
    const data = json && json.data ? json.data : {};
    const items = data.items || [];
    catPages = data.pages || 1;
    document.getElementById('catResumen').textContent = `Página ${data.current_page || catPage} de ${catPages} · Total: ${data.total || items.length}`;
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Sin datos.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(item => {
      const estadoBadge = item.estado === 'activo' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
      return `<tr>
        <td>${escapeHtml(item.nombre)}</td>
        <td class="text-end">${formatoMoneda(item.precio_compra)}</td>
        <td class="text-end">${formatoMoneda(item.precio_venta)}</td>
        <td>${estadoBadge}</td>
        <td>${escapeHtml(item.descripcion || '')}</td>
        <td><small>${escapeHtml(item.fecha_actualizacion || item.fecha_creacion || '')}</small></td>
        <td class="text-end"><button class="btn btn-sm btn-outline-secondary" onclick="editarCategoria(${item.id}, '${escapeHtml(item.nombre)}', ${Number(item.precio_compra)}, ${Number(item.precio_venta)}, '${escapeHtml(item.descripcion || '')}', '${escapeHtml(item.estado)}')">Editar</button></td>
      </tr>`;
    }).join('');
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-danger">Error: ${escapeHtml(String(e))}</td></tr>`;
  }
}

function cambiarOrdenCat(col) {
  if (!col) return;
  if (catSortBy === col) { catSortDir = (catSortDir === 'ASC') ? 'DESC' : 'ASC'; } else { catSortBy = col; catSortDir = 'ASC'; }
  cargarCategorias();
}

function cambiarPaginaCat(delta) {
  const np = catPage + delta;
  if (np < 1 || np > catPages) return;
  catPage = np;
  cargarCategorias();
}

async function guardarCategoria() {
  const nombre = document.getElementById('catNombre').value.trim();
  const precio_compra = document.getElementById('catPrecioCompra').value;
  const precio_venta = document.getElementById('catPrecioVenta').value;
  const estado = document.getElementById('catEstado').value;
  const descripcion = document.getElementById('catDescripcion').value;
  const id = document.getElementById('catIdHidden') ? document.getElementById('catIdHidden').value : '';
  if (!nombre) { alert('Nombre requerido'); return; }
  try {
    const res = await fetch('../../boleteria/api/categorias_guardar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, nombre, precio_compra, precio_venta, estado, descripcion })
    });
    const json = await res.json();
    if (json && json.success) {
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalCategoria'));
      if (modal) modal.hide();
      document.getElementById('formCategoria').reset();
      if (document.getElementById('catIdHidden')) document.getElementById('catIdHidden').remove();
      mostrarToast('Categoría guardada');
      cargarCategorias();
    } else {
      alert(json && json.message ? json.message : 'No se pudo guardar');
    }
  } catch (e) {
    alert('Error: ' + e);
  }
}

function editarCategoria(id, nombre, pc, pv, descripcion, estado) {
  document.getElementById('catNombre').value = nombre;
  document.getElementById('catPrecioCompra').value = pc;
  document.getElementById('catPrecioVenta').value = pv;
  document.getElementById('catEstado').value = estado;
  document.getElementById('catDescripcion').value = descripcion;
  let hidden = document.getElementById('catIdHidden');
  if (!hidden) {
    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.id = 'catIdHidden';
    hidden.name = 'id';
    document.getElementById('formCategoria').appendChild(hidden);
  }
  hidden.value = id;
  const modal = new bootstrap.Modal(document.getElementById('modalCategoria'));
  modal.show();
}

function mostrarToast(mensaje) {
  let cont = document.getElementById('toastContainer');
  if (!cont) {
    cont = document.createElement('div');
    cont.id = 'toastContainer';
    cont.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(cont);
  }
  const el = document.createElement('div');
  el.className = 'toast align-items-center text-bg-success border-0';
  el.role = 'alert';
  el.ariaLive = 'assertive';
  el.ariaAtomic = 'true';
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${escapeHtml(mensaje)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  cont.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 3000 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
}
</script>

<?php /* EOF */ ?>


