<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../models/ResumenFinanciero.php';

$auth = new CxAuthController();
$auth->requireAuth();
$cedula = $_SESSION['cx_cedula'] ?? '';

$model = new ResumenFinanciero();
$bp = $model->getBalancePrueba($cedula);
?>
<?php
$pageTitle = 'Aportes';
$heroTitle = 'Aportes';
$heroSubtitle = 'Consulta tu información financiera y aportes.';
include __DIR__ . '/../../../views/layouts/header.php';
?>
<link rel="stylesheet" href="../../../assets/css/main.css">
    <main class="container py-3">
      <!-- Información monetaria (desplegada por defecto) -->
      <div class="section-card">
        <div class="section-title collapsible-header" onclick="toggleCollapsible('monetaria')">
          <i class="fa-solid fa-wallet me-2 text-primary"></i> Información monetaria
          <i class="fa-solid fa-chevron-down collapsible-icon float-end"></i>
        </div>
        <div class="collapsible-content show" id="monetaria">
          <div class="p-3">
            <div class="kv">
              <div class="k">Aportes Totales</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['aportes_totales'] ?? 0), 0); ?>
                <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['aportes_incentivos'] ?? 0), 0); ?>)</small>
              </div>
            </div>
            <div class="kv">
              <div class="k">Revalorizaciones de aportes</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['aportes_revalorizaciones'] ?? 0), 0); ?>
              </div>
            </div>
            <div class="kv">
              <div class="k">Plan Futuro</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['plan_futuro'] ?? 0), 0); ?>
              </div>
            </div>
            <div class="kv">
              <div class="k">Bolsillos</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['bolsillos'] ?? 0), 0); ?>
                <small class="text-muted">(Incentivos: <?php echo '$' . number_format((float)($bp['bolsillos_incentivos'] ?? 0), 0); ?>)</small>
              </div>
            </div>
            <div class="kv">
              <div class="k">Comisiones</div>
              <div class="v">
                <?php echo '$' . number_format((float)($bp['comisiones'] ?? 0), 0); ?>
              </div>
            </div>
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
      
      <!-- Botón Descargar estado de Cuenta -->
      <div class="text-center mt-4">
        <button class="btn btn-info" onclick="mostrarEnDesarrollo('Descargar estado de Cuenta')">
          <i class="fa-solid fa-download me-2"></i>Descargar estado de Cuenta
        </button>
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
