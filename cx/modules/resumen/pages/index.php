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
$comisiones = $model->getComisiones($cedula);

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
<link rel="stylesheet" href="../../../assets/css/main.css">
    <main class="container py-3">
      <div class="row g-2 mb-2">
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
                <div class="kpi-label">Pago crédito</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card kpi-card p-2">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="fa-solid fa-percentage"></i></div>
              <div>
                <div class="kpi-value"><?php echo '$' . number_format((float)($comisiones['comisiones'] ?? 0), 0); ?></div>
                <div class="kpi-label">Comisiones</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
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
        <div class="collapsible-content" id="asociado">
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
        <div class="collapsible-content" id="monetaria">
          <div class="p-3">
            <div class="kv">
              <div class="k">Aportes Totales</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['aportes_totales'] ?? 0), 0); ?>
                <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['aportes_incentivos'] ?? 0), 0); ?>)</small>
              </div>
            </div>
            <div class="kv"><div class="k">Revalorizaciones de aportes</div><div class="v"><?php echo '$' . number_format((float)($bp['aportes_revalorizaciones'] ?? 0), 0); ?></div></div>
            <div class="kv"><div class="k">Plan Futuro</div><div class="v"><?php echo '$' . number_format((float)($bp['plan_futuro'] ?? 0), 0); ?></div></div>
            <div class="kv">
              <div class="k">Bolsillos</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['bolsillos'] ?? 0), 0); ?>
                <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['bolsillos_incentivos'] ?? 0), 0); ?>)</small>
              </div>
            </div>
            <div class="kv"><div class="k">Comisiones</div><div class="v"><?php echo '$' . number_format((float)($bp['comisiones'] ?? 0), 0); ?></div></div>
            <div class="kv">
              <div class="k">Total Saldos a favor</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['total_saldos_favor'] ?? 0), 0); ?>
                <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['total_incentivos'] ?? 0), 0); ?>)</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($creditos)): ?>
      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('creditos')">
          <i class="fa-solid fa-receipt me-2 text-primary"></i> Información crédito
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content" id="creditos">
          <div class="p-3">
            <?php foreach ($creditos as $c): ?>
              <div class="info-card mb-3">
                <!-- Título del crédito con botón -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0 fw-bold text-primary">Crédito <?php echo htmlspecialchars($c['numero_credito']); ?></h6>
                  <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalCredito" onclick="mostrarDetallesCredito(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                    <i class="fa-solid fa-eye"></i> Ver detalles
                  </button>
                </div>
                
                <!-- Información básica siempre visible -->
                <div class="mb-3">
                  <div class="row g-2">
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Línea de Crédito</div><div class="v"><?php echo htmlspecialchars($c['tipo_prestamo']); ?></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Cuotas</div><div class="v">
                        <?php
                          $plazo = (int)$c['plazo'];
                          $pendientes = (int)($c['cuotas_pendientes'] ?? 0);
                          $actuales = $plazo - $pendientes;
                          echo "$actuales/$plazo";
                        ?>
                      </div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Fecha Pago</div><div class="v"><?php echo !empty($c['fecha_pago']) ? date('d/m/Y', strtotime($c['fecha_pago'])) : '-'; ?></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Días Mora</div><div class="v"><?php echo (int)$c['dias_mora']; ?></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k"><strong>Pago Mínimo</strong></div><div class="v fw-bold text-primary">
                        <?php
                          $cuotaBase = (float)($c['valor_cuota'] ?? ($c['cuota'] ?? 0));
                          $saldoMora = (float)($c['saldo_mora'] ?? 0);
                          $montoCobranza = (float)($c['monto_cobranza'] ?? 0);
                          $pagoMin = ($saldoMora > 0 ? $saldoMora : $cuotaBase) + $montoCobranza;
                          echo '$' . number_format($pagoMin, 0);
                        ?>
                      </div></div>
                    </div>
                  </div>
                </div>
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
        <div class="collapsible-content" id="productos">
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
          </div>
        </div>
      </div>
      
      <!-- Espacio adicional al final para mejor visualización -->
      <div class="mb-5 pb-5"></div>
    </main>
    
    <!-- Modal para mostrar detalles del crédito -->
    <div class="modal fade" id="modalCredito" tabindex="-1" aria-labelledby="modalCreditoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalCreditoLabel">
              <i class="fa-solid fa-receipt me-2 text-primary"></i>
              Detalles del Crédito <span id="numeroCreditoModal"></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <!-- 1. Información Básica -->
              <div class="col-12">
                <div class="p-3 bg-light rounded">
                  <h6 class="mb-3 fw-bold"><i class="fa-solid fa-info-circle me-2"></i>Información Básica</h6>
                  <div class="row g-2">
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Crédito</div><div class="v" id="numeroCredito"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Tipo Préstamo</div><div class="v" id="tipoPrestamo"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Inicio</div><div class="v" id="fechaInicio"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Final</div><div class="v" id="fechaVencimiento"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">V.Inicial</div><div class="v" id="valorInicial"></div></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 2. Información de Pago -->
              <div class="col-12">
                <div class="p-3 bg-light rounded">
                  <h6 class="mb-3 fw-bold"><i class="fa-solid fa-credit-card me-2"></i>Información de Pago</h6>
                  <div class="row g-2">
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Cuotas</div><div class="v" id="cuotas"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Fecha Pago</div><div class="v" id="fechaPago"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Mora</div><div class="v" id="saldoMora"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Días Mora</div><div class="v" id="diasMora"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Cobranza</div><div class="v" id="montoCobranza"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Pago Mínimo</div><div class="v fw-bold text-primary" id="pagoMinimo"></div></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 3. Información Financiera -->
              <div class="col-12">
                <div class="p-3 bg-light rounded">
                  <h6 class="mb-3 fw-bold"><i class="fa-solid fa-calculator me-2"></i>Información Financiera</h6>
                  <div class="row g-2">
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Saldo Capital</div><div class="v" id="saldoCapital"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Saldo Total</div><div class="v" id="saldoTotal"></div></div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="kv"><div class="k">Codeudor</div><div class="v" id="codeudor"></div></div>
                    </div>
                  </div>
                  
                  <!-- Información del codeudor si existe -->
                  <div id="infoCodeudor" class="mt-3 p-3 bg-white rounded border" style="display: none;">
                    <h6 class="mb-2"><i class="fa-solid fa-user-friends me-2"></i>Información del Codeudor</h6>
                    <div class="row g-2">
                      <div class="col-6 col-md-3">
                        <div class="kv"><div class="k">Nombre</div><div class="v" id="codeudorNombre"></div></div>
                      </div>
                      <div class="col-6 col-md-3">
                        <div class="kv"><div class="k">Teléfono</div><div class="v" id="codeudorTelefono"></div></div>
                      </div>
                      <div class="col-6 col-md-3">
                        <div class="kv"><div class="k">Email</div><div class="v" id="codeudorEmail"></div></div>
                      </div>
                      <div class="col-6 col-md-3">
                        <div class="kv"><div class="k">Dirección</div><div class="v" id="codeudorDireccion"></div></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fa-solid fa-times me-2"></i>Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Espacio inferior para evitar que se vea pegado el último cuadro -->
    <div style="height: 20px;"></div>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
/* Estilos para el modal de detalles de crédito */
#modalCredito .kv .k {
  color: #2c3e50 !important;
  font-weight: 600 !important;
}

#modalCredito .kv .v {
  color: #495057 !important;
  font-weight: 400 !important;
}
</style>

<script>
// Función para manejar las secciones principales (asociado, monetaria, creditos, productos)
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

// Función para mostrar los detalles del crédito en el modal
function mostrarDetallesCredito(credito) {
  // 1. Información Básica
  document.getElementById('numeroCredito').textContent = credito.numero_credito || '-';
  document.getElementById('tipoPrestamo').textContent = credito.tipo_prestamo || '-';
  
  // Fechas
  document.getElementById('fechaInicio').textContent = credito.fecha_inicio ? 
    new Date(credito.fecha_inicio).toLocaleDateString('es-CO') : '-';
  document.getElementById('fechaVencimiento').textContent = credito.fecha_vencimiento ? 
    new Date(credito.fecha_vencimiento).toLocaleDateString('es-CO') : '-';
  
  document.getElementById('valorInicial').textContent = '$' + (parseFloat(credito.desembolso_inicial || 0)).toLocaleString();
  
  // 2. Información de Pago
  // Calcular cuotas
  const plazo = parseInt(credito.plazo) || 0;
  const pendientes = parseInt(credito.cuotas_pendientes) || 0;
  const actuales = plazo - pendientes;
  document.getElementById('cuotas').textContent = `${actuales}/${plazo}`;
  
  document.getElementById('fechaPago').textContent = credito.fecha_pago ? 
    new Date(credito.fecha_pago).toLocaleDateString('es-CO') : '-';
  
  document.getElementById('saldoMora').textContent = '$' + (parseFloat(credito.saldo_mora || 0)).toLocaleString();
  document.getElementById('diasMora').textContent = credito.dias_mora || '0';
  document.getElementById('montoCobranza').textContent = '$' + (parseFloat(credito.monto_cobranza || 0)).toLocaleString();
  
  // Calcular pago mínimo
  const cuotaBase = parseFloat(credito.valor_cuota || credito.cuota || 0);
  const saldoMora = parseFloat(credito.saldo_mora || 0);
  const montoCobranza = parseFloat(credito.monto_cobranza || 0);
  const pagoMin = (saldoMora > 0 ? saldoMora : cuotaBase) + montoCobranza;
  document.getElementById('pagoMinimo').textContent = '$' + pagoMin.toLocaleString();
  
  // 3. Información Financiera
  document.getElementById('saldoCapital').textContent = '$' + (parseFloat(credito.saldo_capital || 0)).toLocaleString();
  
  // Calcular saldo total
  const saldoCapital = parseFloat(credito.saldo_capital || 0);
  const seguroVida = parseFloat(credito.seguro_vida || 0);
  const seguroDeudores = parseFloat(credito.seguro_deudores || 0);
  const interes = parseFloat(credito.interes || 0);
  const saldoTotal = saldoCapital + seguroVida + seguroDeudores + interes;
  document.getElementById('saldoTotal').textContent = '$' + saldoTotal.toLocaleString();
  
  // Información del codeudor
  const hasCodeudor = (credito.codeudor_nombre && credito.codeudor_nombre.trim()) || 
                     (credito.codeudor_celular && credito.codeudor_celular.trim()) || 
                     (credito.codeudor_email && credito.codeudor_email.trim()) || 
                     (credito.codeudor_direccion && credito.codeudor_direccion.trim());
  
  if (hasCodeudor) {
    document.getElementById('codeudor').innerHTML = '<span class="text-success"><i class="fa-solid fa-check"></i> Sí</span>';
    document.getElementById('infoCodeudor').style.display = 'block';
    document.getElementById('codeudorNombre').textContent = credito.codeudor_nombre ? credito.codeudor_nombre.trim() : '-';
    document.getElementById('codeudorTelefono').textContent = credito.codeudor_celular ? credito.codeudor_celular.trim() : '-';
    document.getElementById('codeudorEmail').textContent = credito.codeudor_email ? credito.codeudor_email.trim() : '-';
    document.getElementById('codeudorDireccion').textContent = credito.codeudor_direccion ? credito.codeudor_direccion.trim() : '-';
  } else {
    document.getElementById('codeudor').innerHTML = '<span class="text-muted">—</span>';
    document.getElementById('infoCodeudor').style.display = 'none';
  }
  
  // Actualizar título del modal
  document.getElementById('numeroCreditoModal').textContent = credito.numero_credito;
}
</script>


