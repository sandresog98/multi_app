<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../models/ResumenFinanciero.php';

$auth = new CxAuthController();
$auth->requireAuth();
$cedula = $_SESSION['cx_cedula'] ?? '';

$model = new ResumenFinanciero();
$creditos = $model->getCreditos($cedula);
?>
<?php
$pageTitle = 'Información de Créditos';
$heroTitle = 'Información de Créditos';
$heroSubtitle = 'Consulta tus créditos y detalles de pago.';
include __DIR__ . '/../../../views/layouts/header.php';
?>
<link rel="stylesheet" href="../../../assets/css/main.css">
    <main class="container py-3">
      <!-- Botón Solicitar nuevo crédito -->
      <div class="text-center mb-4">
        <button class="btn btn-success" onclick="mostrarEnDesarrollo('Solicitar nuevo crédito')">
          <i class="fa-solid fa-plus me-2"></i>Solicitar nuevo crédito
        </button>
      </div>
      
      <!-- Información de créditos (desplegada por defecto) -->
      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('creditos')">
          <i class="fa-solid fa-receipt me-2 text-primary"></i> Información crédito
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content show" id="creditos">
          <div class="p-3">
            <?php if (empty($creditos)): ?>
              <div class="text-muted small text-center py-3">
                <i class="fa-solid fa-credit-card fa-2x mb-2 d-block"></i>
                No tienes créditos activos.
              </div>
            <?php else: ?>
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
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Botón Descargar estado de Cuenta -->
      <div class="text-center mt-4">
        <button class="btn btn-info" onclick="mostrarEnDesarrollo('Descargar estado de Cuenta')">
          <i class="fa-solid fa-download me-2"></i>Descargar estado de Cuenta
        </button>
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

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* Estilos para el modal de detalles de crédito */
#modalCredito .kv .k {
  color: #6c757d !important;
  font-weight: 500 !important;
}

#modalCredito .kv .v {
  color: #212529 !important;
  font-weight: 600 !important;
}

/* Estilos para el listado principal de créditos */
.info-card .kv .k {
  color: #6c757d !important;
  font-weight: 500 !important;
}

.info-card .kv .v {
  color: #212529 !important;
  font-weight: 600 !important;
}
</style>

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

// Función para mostrar mensaje de funcionalidad en desarrollo
function mostrarEnDesarrollo(funcionalidad) {
  alert(`🚧 ${funcionalidad}\n\nEn desarrollo, pronto habrá lanzamiento de la funcionalidad.`);
}
</script>
