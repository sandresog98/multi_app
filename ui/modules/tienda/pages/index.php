<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../models/TiendaDashboard.php';

$authController = new AuthController();
$authController->requireModule('tienda.resumen');
$currentUser = $authController->getCurrentUser();
$dash = new TiendaDashboard();

// Obtener datos del dashboard
$productos = $dash->getProductosTotales();
$productosInventario = $dash->getProductosEnInventario();
$celularesInventario = $dash->getCelularesEnInventario();
$ventasHoy = $dash->getVentasHoy();
$ventasPorCategoria = $dash->getVentasPorCategoria();
$ventasPorMarca = $dash->getVentasPorMarca();
$ventasHoyDetalle = $dash->getVentasHoyDetalle();
$ventasMesActual = $dash->getVentasMesActual();

$pageTitle = 'Tienda - Multi v2';
$currentPage = 'tienda';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-store me-2"></i>Tienda - Resumen</h1>
      </div>

      <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Productos</div>
            <div class="h3 mb-0"><?php echo (int)$productos['total']; ?> (<span class="text-success"><?php echo (int)$productos['con_precio']; ?></span>)</div>
            <small class="text-muted">Total (con precio)</small>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Productos en inventario</div>
            <div class="h3 mb-0"><?php echo (int)$productosInventario; ?></div>
            <small class="text-muted">Con stock disponible</small>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Celulares en inventario</div>
            <div class="h3 mb-0"><?php echo (int)$celularesInventario; ?></div>
            <small class="text-muted">Disponibles para venta</small>
          </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card text-bg-light"><div class="card-body">
            <div class="small text-muted">Ventas realizadas (HOY)</div>
            <div class="h3 mb-0"><?php echo (int)$ventasHoy; ?></div>
            <small class="text-muted">Transacciones del día</small>
          </div></div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Ventas por categoría (último 30 días)</strong></div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Categoría</th><th class="text-end">Cantidad</th><th class="text-end">Total</th></tr></thead>
                      <tbody>
                        <?php foreach (($ventasPorCategoria ?? []) as $r): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($r['categoria']); ?></td>
                          <td class="text-end"><?php echo (int)$r['cantidad']; ?></td>
                          <td class="text-end"><?php echo '$'.number_format((float)($r['total'] ?? 0),0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4"><canvas id="miniChartCategoria" height="110"></canvas></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Ventas por marca (últimos 30 días)</strong></div>
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-8">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>Marca</th><th class="text-end">Cantidad</th><th class="text-end">Total</th></tr></thead>
                      <tbody>
                        <?php foreach (($ventasPorMarca ?? []) as $r): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($r['marca']); ?></td>
                          <td class="text-end"><?php echo (int)$r['cantidad']; ?></td>
                          <td class="text-end"><?php echo '$'.number_format((float)($r['total'] ?? 0),0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="col-4"><canvas id="miniChartMarca" height="110"></canvas></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Ventas (hoy)</strong></div>
            <div class="card-body">
              <div class="row">
                <div class="col-6">
                  <div class="text-center">
                    <div class="h4 text-primary"><?php echo (int)($ventasHoyDetalle['cantidad_ventas'] ?? 0); ?></div>
                    <small class="text-muted">Ventas realizadas</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center">
                    <div class="h4 text-success"><?php echo '$'.number_format((float)($ventasHoyDetalle['total_ventas'] ?? 0),0); ?></div>
                    <small class="text-muted">Total vendido</small>
                  </div>
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-6">
                  <div class="text-center">
                    <div class="h5 text-info"><?php echo (int)($ventasHoyDetalle['cantidad_productos'] ?? 0); ?></div>
                    <small class="text-muted">Productos vendidos</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center">
                    <div class="h5 text-warning"><?php echo '$'.number_format((float)($ventasHoyDetalle['total_productos'] ?? 0),0); ?></div>
                    <small class="text-muted">Valor productos</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><strong>Ventas (mes actual)</strong></div>
            <div class="card-body">
              <div class="row">
                <div class="col-6">
                  <div class="text-center">
                    <div class="h4 text-primary"><?php echo (int)($ventasMesActual['cantidad_ventas'] ?? 0); ?></div>
                    <small class="text-muted">Ventas realizadas</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center">
                    <div class="h4 text-success"><?php echo '$'.number_format((float)($ventasMesActual['total_ventas'] ?? 0),0); ?></div>
                    <small class="text-muted">Total vendido</small>
                  </div>
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-6">
                  <div class="text-center">
                    <div class="h5 text-info"><?php echo (int)($ventasMesActual['cantidad_productos'] ?? 0); ?></div>
                    <small class="text-muted">Productos vendidos</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-center">
                    <div class="h5 text-warning"><?php echo '$'.number_format((float)($ventasMesActual['total_productos'] ?? 0),0); ?></div>
                    <small class="text-muted">Valor productos</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de productos más vendidos -->
      <div class="row g-3 mt-1">
        <div class="col-12">
          <div class="card">
            <div class="card-header"><strong>Productos más vendidos (últimos 30 días)</strong></div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Producto</th>
                      <th>Categoría</th>
                      <th>Marca</th>
                      <th class="text-end">Veces vendido</th>
                      <th class="text-end">Cantidad total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $productosMasVendidos = $dash->getProductosMasVendidos();
                    foreach (($productosMasVendidos ?? []) as $r): 
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                      <td><?php echo htmlspecialchars($r['categoria']); ?></td>
                      <td><?php echo htmlspecialchars($r['marca']); ?></td>
                      <td class="text-end"><?php echo (int)$r['veces_vendido']; ?></td>
                      <td class="text-end"><?php echo (int)$r['cantidad_total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Gráfico de ventas por categoría
  const categorias = <?php echo json_encode($ventasPorCategoria ?? []); ?>;
  const ctxCategoria = document.getElementById('miniChartCategoria');
  if (ctxCategoria && categorias && categorias.length){
    const labels = categorias.map(r => r.categoria || 'Sin categoría');
    const values = categorias.map(r => Number(r.cantidad || 0));
    new Chart(ctxCategoria, { 
      type:'doughnut', 
      data:{ 
        labels, 
        datasets:[{ 
          data: values, 
          backgroundColor:['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcaf0','#fd7e14','#20c997','#6610f2','#e83e8c'] 
        }] 
      }, 
      options:{ 
        plugins:{ legend:{ display:false } }, 
        cutout:'70%' 
      } 
    });
  }
  
  // Gráfico de ventas por marca
  const marcas = <?php echo json_encode($ventasPorMarca ?? []); ?>;
  const ctxMarca = document.getElementById('miniChartMarca');
  if (ctxMarca && marcas && marcas.length){
    const labels = marcas.map(r => r.marca || 'Sin marca');
    const values = marcas.map(r => Number(r.cantidad || 0));
    new Chart(ctxMarca, { 
      type:'doughnut', 
      data:{ 
        labels, 
        datasets:[{ 
          data: values, 
          backgroundColor:['#198754','#0d6efd','#ffc107','#dc3545','#6c757d','#0dcaf0','#fd7e14','#20c997','#6610f2','#e83e8c'] 
        }] 
      }, 
      options:{ 
        plugins:{ legend:{ display:false } }, 
        cutout:'70%' 
      } 
    });
  }
});
</script>
