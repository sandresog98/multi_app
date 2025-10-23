<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../models/ResumenFinanciero.php';

$auth = new CxAuthController();
$auth->requireAuth();
$cedula = $_SESSION['cx_cedula'] ?? '';
$nombre = $_SESSION['cx_nombre'] ?? '';

$model = new ResumenFinanciero();
$info = $model->getInfoBasica($cedula);
$asignaciones = $model->getAsignaciones($cedula);
$creditos = $model->getCreditos($cedula);
$bp = $model->getBalancePrueba($cedula);

$valorProductosMensual = 0.0;
foreach ($asignaciones as $ap) { $valorProductosMensual += (float)($ap['monto_pago'] ?? 0); }

$valorPagoMinCreditos = 0.0;
foreach ($creditos as $c) {
  $cuotaBase = (float)($c['valor_cuota'] ?? ($c['cuota'] ?? 0));
  $saldoMora = (float)($c['saldo_mora'] ?? 0);
  $montoCobranza = (float)($c['monto_cobranza'] ?? 0);
  $valorPagoMinCreditos += ($saldoMora > 0 ? $saldoMora : $cuotaBase) + $montoCobranza;
}

$valorTotalPago = $valorProductosMensual + $valorPagoMinCreditos;
?>
<?php
$pageTitle = 'Perfil';
$heroTitle = 'Perfil';
$heroSubtitle = 'Tu información personal y productos asignados.';
include __DIR__ . '/../../../views/layouts/header.php';
?>
<link rel="stylesheet" href="../../../assets/css/main.css">
    <main class="container py-3">
      <!-- KPIs -->
      <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-bag-shopping"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format($valorProductosMensual, 0); ?></div>
                <div class="kpi-label">Aportes y productos</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-credit-card"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format($valorPagoMinCreditos, 0); ?></div>
                <div class="kpi-label">Pago mínimo créditos</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format($valorTotalPago, 0); ?></div>
                <div class="kpi-label">Total pago</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-wallet"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format((float)($bp['aportes_totales'] ?? 0), 0); ?></div>
                <div class="kpi-label">Aportes totales</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Información del asociado (desplegada por defecto) -->
      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('asociado')">
          <i class="fa-solid fa-user me-2 text-primary"></i> Información del asociado
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content show" id="asociado">
          <div class="p-3">
            <div class="kv"><div class="k">Nombre</div><div class="v"><?php echo htmlspecialchars($info['nombre'] ?? $nombre); ?></div></div>
            <div class="kv"><div class="k">Cédula</div><div class="v"><?php echo htmlspecialchars($info['cedula'] ?? $cedula); ?></div></div>
            <div class="kv"><div class="k">Teléfono</div><div class="v"><?php echo htmlspecialchars($info['celula'] ?? ''); ?></div></div>
            <div class="kv"><div class="k">Email</div><div class="v"><?php echo htmlspecialchars($info['mail'] ?? ''); ?></div></div>
            <div class="kv"><div class="k">Ciudad</div><div class="v"><?php echo htmlspecialchars($info['ciudad'] ?? ''); ?></div></div>
            <div class="kv"><div class="k">Dirección</div><div class="v"><?php echo htmlspecialchars($info['direcc'] ?? ''); ?></div></div>
            
            <!-- Botón Actualizar información -->
            <div class="mt-3 text-center">
              <button class="btn btn-outline-primary" onclick="mostrarEnDesarrollo('Actualizar información')">
                <i class="fa-solid fa-user-edit me-2"></i>Actualizar información
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Información de productos (desplegada por defecto) -->
      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('productos')">
          <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Información de productos
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content show" id="productos">
          <div class="p-3">
            <?php if (empty($asignaciones)): ?>
              <div class="text-muted small text-center py-3">
                <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                No tienes productos asignados.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th class="fw-bold">Producto</th>
                      <th class="fw-bold text-end">Monto Pago</th>
                      <th class="fw-bold text-center">Día Pago</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($asignaciones as $ap): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($ap['producto_nombre']); ?></td>
                        <td class="text-end fw-semibold"><?php echo '$' . number_format((float)$ap['monto_pago'], 0); ?></td>
                        <td class="text-center"><?php echo (int)$ap['dia_pago']; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
            
            <!-- Botón Solicitar nuevo producto -->
            <div class="mt-3 text-center">
              <button class="btn btn-outline-success" onclick="mostrarEnDesarrollo('Solicitar nuevo producto')">
                <i class="fa-solid fa-plus me-2"></i>Solicitar nuevo producto
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Espacio adicional al final para mejor visualización -->
      <div class="mb-5 pb-5"></div>
    </main>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Función para manejar las secciones principales
function toggleCollapsible(id) {
  const content = document.getElementById(id);
  const icon = content.previousElementSibling.querySelector('.collapsible-icon');
  
  if (content.classList.contains('show')) {
    content.classList.remove('show');
    icon.classList.remove('rotated');
  } else {
    content.classList.add('show');
    icon.classList.add('rotated');
  }
}

// Inicializar iconos para secciones desplegadas por defecto
document.addEventListener('DOMContentLoaded', function() {
  const expandedSections = document.querySelectorAll('.collapsible-content.show');
  expandedSections.forEach(function(section) {
    const icon = section.previousElementSibling.querySelector('.collapsible-icon');
    if (icon) {
      icon.classList.add('rotated');
    }
  });
});

// Función para mostrar mensaje de funcionalidad en desarrollo
function mostrarEnDesarrollo(funcionalidad) {
  alert(`🚧 ${funcionalidad}\n\nEn desarrollo, pronto habrá lanzamiento de la funcionalidad.`);
}
</script>
