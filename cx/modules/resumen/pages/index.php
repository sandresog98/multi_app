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
$creditos = $model->getCreditos($cedula);
$asignaciones = $model->getAsignaciones($cedula);
$bp = $model->getBalancePrueba($cedula);

$valorProductosMensual = 0.0;
foreach ($asignaciones as $ap) { $valorProductosMensual += (float)($ap['monto_pago'] ?? 0); }
$valorPagoMinCreditos = 0.0;
foreach ($creditos as $c) {
  $cuotaBase = (float)($c['valor_cuota'] ?? ($c['cuota'] ?? 0));
  $saldoMora = (float)($c['saldo_mora'] ?? 0);
  $valorPagoMinCreditos += $saldoMora > 0 ? $saldoMora : $cuotaBase;
}
$valorTotalMonetario = $valorProductosMensual + $valorPagoMinCreditos;
?>
<?php
$pageTitle = 'Resumen Financiero';
$heroTitle = 'Resumen Financiero';
$heroSubtitle = 'Consulta tus aportes, créditos y productos.';
include __DIR__ . '/../../../views/layouts/header.php';
?>
<style>
/* Estilos mejorados inspirados en UI */
.collapsible-header {
  cursor: pointer;
  user-select: none;
  transition: all 0.3s ease;
  padding: 15px 20px;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-radius: 15px 15px 0 0;
  border: 1px solid #dee2e6;
  border-bottom: none;
  margin-bottom: 0;
}
.collapsible-header:hover {
  background: linear-gradient(135deg, #e9ecef, #dee2e6);
  transform: translateY(-1px);
}
.collapsible-content {
  display: none;
  transition: all 0.3s ease;
  border-radius: 0 0 15px 15px;
  border: 1px solid #dee2e6;
  border-top: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
.collapsible-content.show {
  display: block;
}
.collapsible-icon {
  transition: transform 0.3s ease;
  color: #6c757d;
}
.collapsible-icon.rotated {
  transform: rotate(180deg);
  color: #0ea5e9;
}

/* Mejoras en las tarjetas */
.section-card {
  border: none;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
  margin-bottom: 20px;
  overflow: hidden;
}
.section-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.section-title {
  font-weight: 600;
  font-size: 1.1rem;
  color: #495057;
  margin: 0;
}

/* Mejoras en los KPIs */
.kpi-card {
  border: none;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #ffffff, #f8f9fa);
}
.kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}
.kpi-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0ea5e9;
  font-size: 1.2rem;
}
.kpi-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #212529;
  line-height: 1.2;
}
.kpi-label {
  font-size: 0.8rem;
  color: #6c757d;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Mejoras en los campos */
.kv {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f1f3f4;
}
.kv:last-child {
  border-bottom: none;
}
.k {
  font-weight: 600 !important;
  color: #495057;
  font-size: 0.9rem;
}
.v {
  color: #212529;
  font-weight: 500;
  text-align: right;
  font-size: 0.9rem;
}

/* Mejoras en las tarjetas de crédito/productos */
.info-card {
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  background: linear-gradient(135deg, #ffffff, #f8f9fa);
  transition: all 0.3s ease;
}
.info-card:hover {
  border-color: #0ea5e9;
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
}

/* Estilos para la tabla de productos */
.table-responsive {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.table {
  margin-bottom: 0;
}
.table thead th {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-bottom: 2px solid #dee2e6;
  font-size: 0.9rem;
  padding: 12px 15px;
}
.table tbody td {
  padding: 12px 15px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f3f4;
}
.table tbody tr:hover {
  background-color: rgba(14, 165, 233, 0.05);
}
.table tbody tr:last-child td {
  border-bottom: none;
}

/* Resumen al final */
.summary-section {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-radius: 12px;
  padding: 15px;
  margin-top: 20px;
  border: 1px solid #dee2e6;
}
.summary-section .kv {
  border-bottom: 1px solid #dee2e6;
  padding: 10px 0;
}
.summary-section .kv:last-child {
  border-bottom: none;
  font-weight: 600;
  color: #0ea5e9;
}
</style>
    <main class="container py-3">
      <div class="row g-2 mb-2">
        <div class="col-4">
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
        <div class="col-4">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-credit-card"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format($valorPagoMinCreditos, 0); ?></div>
                <div class="kpi-label">Pago crédito</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-4">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format($valorTotalMonetario, 0); ?></div>
                <div class="kpi-label">Total pago</div>
              </div>
            </div>
          </div>
        </div>
      </div>
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
          </div>
        </div>
      </div>

      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('monetaria')">
          <i class="fa-solid fa-wallet me-2 text-primary"></i> Información monetaria
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content show" id="monetaria">
          <div class="p-3">
            <div class="kv"><div class="k">Aportes</div><div class="v"><?php echo '$' . number_format((float)($info['aporte'] ?? 0), 0); ?></div></div>
            <div class="kv"><div class="k">Revalorización de aportes</div><div class="v"><?php echo '$' . number_format((float)($bp['revalorizacion_aportes'] ?? 0), 0); ?></div></div>
            <div class="kv"><div class="k">Plan Futuro</div><div class="v"><?php echo '$' . number_format((float)($bp['plan_futuro'] ?? 0), 0); ?></div></div>
            <div class="kv"><div class="k">Aportes Sociales</div><div class="v"><?php echo '$' . number_format((float)($bp['aportes_sociales_2'] ?? 0), 0); ?></div></div>
          </div>
        </div>
      </div>

      <?php if (!empty($creditos)): ?>
      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('creditos')">
          <i class="fa-solid fa-receipt me-2 text-primary"></i> Información crédito
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content show" id="creditos">
          <div class="p-3">
            <?php foreach ($creditos as $c): ?>
              <div class="info-card">
                <div class="kv"><div class="k">Crédito</div><div class="v"><?php echo htmlspecialchars($c['numero_credito']); ?></div></div>
                <div class="kv"><div class="k">Tipo Préstamo</div><div class="v"><?php echo htmlspecialchars($c['tipo_prestamo']); ?></div></div>
                <div class="kv"><div class="k">Plazo</div><div class="v"><?php echo (int)$c['plazo']; ?></div></div>
                <div class="kv"><div class="k">Valor Cuota</div><div class="v"><?php echo '$' . number_format((float)($c['valor_cuota'] ?? $c['cuota'] ?? 0), 0); ?></div></div>
                <div class="kv"><div class="k">Cuotas Pendientes</div><div class="v"><?php echo (int)($c['cuotas_pendientes'] ?? 0); ?></div></div>
                <div class="kv"><div class="k">Deuda Capital</div><div class="v"><?php echo '$' . number_format((float)$c['deuda_capital'], 0); ?></div></div>
                <div class="kv"><div class="k">Días Mora</div><div class="v"><?php echo (int)$c['dias_mora']; ?></div></div>
                <div class="kv"><div class="k">Saldo Mora</div><div class="v"><?php echo '$' . number_format((float)$c['saldo_mora'], 0); ?></div></div>
                <div class="kv"><div class="k">Fecha de Pago</div><div class="v"><?php echo !empty($c['fecha_pago']) ? date('d/m/Y', strtotime($c['fecha_pago'])) : '-'; ?></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

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
                      <th class="fw-bold text-center">Día Pago</th>
                      <th class="fw-bold text-end">Monto Pago</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($asignaciones as $ap): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($ap['producto_nombre']); ?></td>
                        <td class="text-center"><?php echo (int)$ap['dia_pago']; ?></td>
                        <td class="text-end fw-semibold"><?php echo '$' . number_format((float)$ap['monto_pago'], 0); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
    
    <!-- Espacio inferior para evitar que se vea pegado el último cuadro -->
    <div style="height: 20px;"></div>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

<script>
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
</script>


