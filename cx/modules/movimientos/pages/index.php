<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../config/paths.php';

$auth = new CxAuthController();
$auth->requireAuth();
$cedula = $_SESSION['cx_cedula'] ?? '';

$baseUrl = cx_getBaseUrl();
$apiUrl = $baseUrl . 'api/movimientos.php';
?>
<?php
$pageTitle = 'Mis Movimientos';
$heroTitle = 'Mis Movimientos';
$heroSubtitle = 'Consulta tus movimientos y transacciones.';
include __DIR__ . '/../../../views/layouts/header.php';
?>
<link rel="stylesheet" href="../../../assets/css/main.css">
<style>
.info-card .kv .k {
    color: #6c757d !important;
    font-weight: 500 !important;
}
.info-card .kv .v {
    color: #212529 !important;
    font-weight: 600 !important;
}
</style>
    <main class="container py-3">
      
      <!-- Listado de movimientos -->
      <div class="section-card" id="movimientosContainer">
        <div class="section-title">
          <i class="fa-solid fa-exchange-alt me-2 text-primary"></i> Mis Movimientos
        </div>
        <div class="p-3" id="movimientosList">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2">Cargando movimientos...</p>
          </div>
        </div>
      </div>
      
      <!-- Paginación -->
      <div id="paginacionContainer" class="mt-3"></div>
      
      <!-- Espacio adicional al final para mejor visualización -->
      <div class="mb-5 pb-5"></div>
    </main>
    
    <!-- Modal para mostrar detalles del movimiento -->
    <div class="modal fade" id="modalMovimiento" tabindex="-1" aria-labelledby="modalMovimientoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalMovimientoLabel">
              <i class="fa-solid fa-exchange-alt me-2 text-primary"></i>
              Detalles del Movimiento
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="modalBody">
            <!-- Se llenará dinámicamente -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fa-solid fa-times me-2"></i>Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
let currentPage = 1;
const limit = 20;
let allMovimientos = [];

// Cargar movimientos
async function cargarMovimientos(page = 1) {
    try {
        currentPage = page;
        const url = '<?php echo $apiUrl; ?>?page=' + page + '&limit=' + limit;
        console.log('Cargando desde:', url);
        const response = await fetch(url);
        const data = await response.json();
        console.log('Respuesta:', data);
        
        if (data.success) {
            allMovimientos = data.data;
            mostrarMovimientos(data.data);
            mostrarPaginacion(data.meta);
        } else {
            console.error('Error del API:', data.message);
            document.getElementById('movimientosList').innerHTML = 
                '<div class="text-center py-4 text-muted">Error al cargar movimientos: ' + (data.message || 'Error desconocido') + '</div>';
        }
    } catch (error) {
        console.error('Error al cargar:', error);
        document.getElementById('movimientosList').innerHTML = 
            '<div class="text-center py-4 text-danger">Error al cargar movimientos: ' + error.message + '</div>';
    }
}

// Mostrar movimientos en la lista
function mostrarMovimientos(movimientos) {
    const container = document.getElementById('movimientosList');
    
    if (movimientos.length === 0) {
        container.innerHTML = 
            '<div class="text-muted text-center py-4">' +
            '<i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>' +
            'No tienes movimientos registrados.' +
            '</div>';
        return;
    }
    
    let html = '';
    movimientos.forEach(tx => {
        const fecha = tx.fecha ? new Date(tx.fecha).toLocaleDateString('es-CO') : '-';
        const valor = parseFloat(tx.total_asignado || 0);
        const idSifone = tx.id_sifone || '-';
        
        html += `
            <div class="info-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold text-primary">Movimiento #${tx.id}</h6>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="mostrarDetalles(${tx.id})">
                        <i class="fa-solid fa-eye"></i> Ver detalles
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="kv"><div class="k">ID Sifone</div><div class="v">${idSifone}</div></div>
                    </div>
                    <div class="col-12">
                        <div class="kv"><div class="k">Fecha</div><div class="v">${fecha}</div></div>
                    </div>
                    <div class="col-12">
                        <div class="kv"><div class="k">Valor</div><div class="v">$${valor.toLocaleString()}</div></div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Mostrar detalles del movimiento en el modal
function mostrarDetalles(id) {
    const tx = allMovimientos.find(m => m.id === id);
    if (!tx) return;
    
    const modalBody = document.getElementById('modalBody');
    modalBody.innerHTML = `
        <div class="p-3 bg-light rounded mb-3">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-info-circle me-2"></i>Información General</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="kv"><div class="k">ID Movimiento</div><div class="v">${tx.id}</div></div>
                </div>
                <div class="col-6">
                    <div class="kv"><div class="k">ID Sifone</div><div class="v">${tx.id_sifone || '-'}</div></div>
                </div>
                <div class="col-6">
                    <div class="kv"><div class="k">Fecha</div><div class="v">${tx.fecha ? new Date(tx.fecha).toLocaleDateString('es-CO') : '-'}</div></div>
                </div>
                <div class="col-6">
                    <div class="kv"><div class="k">Origen</div><div class="v"><span class="badge bg-secondary">${tx.origen_pago}</span></div></div>
                </div>
            </div>
        </div>
        <div class="p-3 bg-light rounded">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-list me-2"></i>Items (${tx.items.length})</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Descripción</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tx.items.map(item => `
                            <tr>
                                <td>${item.descripcion}</td>
                                <td class="text-end">$${item.valor.toLocaleString()}</td>
                            </tr>
                        `).join('')}
                        <tr class="table-light fw-bold">
                            <td>Total</td>
                            <td class="text-end">$${parseFloat(tx.total_asignado).toLocaleString()}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('modalMovimiento'));
    modal.show();
}

// Mostrar paginación
function mostrarPaginacion(meta) {
    const container = document.getElementById('paginacionContainer');
    
    if (meta.pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="d-flex justify-content-center"><ul class="pagination">';
    
    // Botón anterior
    if (meta.current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarMovimientos(${meta.current_page - 1})">Anterior</a></li>`;
    }
    
    // Números de página
    for (let i = 1; i <= meta.pages; i++) {
        html += `<li class="page-item ${i === meta.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="cargarMovimientos(${i})">${i}</a>
                 </li>`;
    }
    
    // Botón siguiente
    if (meta.current_page < meta.pages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="cargarMovimientos(${meta.current_page + 1})">Siguiente</a></li>`;
    }
    
    html += '</ul></div>';
    container.innerHTML = html;
}

// Cargar movimientos al inicio
cargarMovimientos();
</script>

