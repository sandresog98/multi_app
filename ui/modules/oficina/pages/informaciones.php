<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('oficina.informaciones');
$currentUser = $auth->getCurrentUser();

$pdo = getConnection();

function calcularProximoCumple(DateTime $nacimiento, DateTime $hoy): DateTime {
  $anio = (int)$hoy->format('Y');
  $cumpleEste = DateTime::createFromFormat('Y-m-d', $anio . '-' . $nacimiento->format('m-d'));
  if ($cumpleEste < (clone $hoy)->setTime(0,0,0)) {
    $cumpleEste = DateTime::createFromFormat('Y-m-d', ($anio + 1) . '-' . $nacimiento->format('m-d'));
  }
  return $cumpleEste;
}

function edadProximo(DateTime $nacimiento, DateTime $proximo): int {
  return (int)$proximo->format('Y') - (int)$nacimiento->format('Y');
}

// Cargar asociados con fecha de nacimiento válida
$rows = [];
try {
  $stmt = $pdo->query("SELECT a.cedula, a.nombre, a.fecnac
                        FROM sifone_asociados a
                        INNER JOIN control_asociados ca ON ca.cedula = a.cedula AND ca.estado_activo = TRUE
                        WHERE a.fecnac IS NOT NULL");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $rows = []; }

$hoy = new DateTime('today');
$semanaLimite = (clone $hoy)->modify('+7 days');
$mesActual = (int)$hoy->format('n');
$anioActual = (int)$hoy->format('Y');
$mesSiguiente = (int)((int)$hoy->format('n') % 12 + 1);
$expandNextDefault = ($mesActual === 12);

$grupos = [
  'week' => [],
  'month' => [],
  'next' => [],
  'rest' => [],
  'nextyear' => []
];

foreach ($rows as $r) {
  $f = $r['fecnac'] ?? null; if (!$f || $f === '0000-00-00') continue;
  try { $nac = new DateTime($f); } catch (Throwable $e) { continue; }
  $prox = calcularProximoCumple($nac, $hoy);
  $mesProx = (int)$prox->format('n');
  $item = [
    'fecha' => $prox,
    'fecha_label' => $prox->format('d/m'),
    'edad' => edadProximo($nac, $prox),
    'cedula' => (string)($r['cedula'] ?? ''),
    'nombre' => (string)($r['nombre'] ?? '')
  ];
  $anioProx = (int)$prox->format('Y');
  if ($prox <= $semanaLimite) {
    // Próximos 7 días (puede cruzar de año)
    $grupos['week'][] = $item;
  } else if ($anioProx === $anioActual && $mesProx === $mesActual) {
    // Este mes, aún no pasados (mismo año)
    $grupos['month'][] = $item;
  } else if ($mesProx === $mesSiguiente) {
    // Siguiente mes (puede ser próximo año si hoy es diciembre)
    $grupos['next'][] = $item;
  } else if ($anioProx === $anioActual) {
    // Resto del año (solo hasta diciembre del año actual)
    $grupos['rest'][] = $item;
  } else {
    // Próximo año
    $grupos['nextyear'][] = $item;
  }
}

// Ordenar cada grupo por fecha próxima
foreach ($grupos as $k => $arr) {
  usort($arr, function($a,$b){ return $a['fecha'] <=> $b['fecha']; });
  $grupos[$k] = $arr;
}

$pageTitle = 'Informaciones - Oficina';
$currentPage = 'informaciones';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-info-circle me-2"></i>Informaciones</h1>
      </div>

      <div class="row g-3">
        <div class="col-12 mb-2">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary active" id="tabCumpleBtn" onclick="showTab('cumples')"><i class="fas fa-birthday-cake me-1"></i>Cumpleaños</button>
            <button type="button" class="btn btn-outline-primary" id="tabAhorrosBtn" onclick="showTab('ahorros')"><i class="fas fa-piggy-bank me-1"></i>Ahorros</button>
          </div>
        </div>

        <div class="col-12" id="cumplesSection">
          <div class="card mb-3">
            <div class="card-header"><strong>Cumpleaños</strong></div>
            <div class="card-body">
              <div class="row g-3">
        <div class="col-12">
          <h6 class="mb-2">
            <a class="text-decoration-none" data-bs-toggle="collapse" href="#grpWeek" role="button" aria-expanded="true" aria-controls="grpWeek">
              <i class="fas fa-chevron-down me-1"></i>Cumpleaños esta semana
            </a>
          </h6>
          <div class="collapse show" id="grpWeek">
          <div class="table-responsive"><table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>Fecha</th><th>Edad</th><th>Cédula</th><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                      <?php if (empty($grupos['week'])): ?><tr><td colspan="4" class="text-muted">Sin cumpleaños esta semana</td></tr><?php endif; ?>
                      <?php foreach ($grupos['week'] as $it): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($it['fecha_label']); ?></td>
                          <td><?php echo (int)$it['edad']; ?></td>
                          <td><?php echo htmlspecialchars($it['cedula']); ?></td>
                          <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                          <td><a class="btn btn-sm btn-outline-info" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados_detalle.php?cedula=<?php echo urlencode($it['cedula']); ?>" title="Ver detalle"><i class="fas fa-eye"></i></a></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
          </table></div>
          </div>
                </div>

        <div class="col-12">
          <h6 class="mb-2">
            <a class="text-decoration-none" data-bs-toggle="collapse" href="#grpMonth" role="button" aria-expanded="true" aria-controls="grpMonth">
              <i class="fas fa-chevron-down me-1"></i>Cumpleaños este mes
            </a>
          </h6>
          <div class="collapse show" id="grpMonth">
          <div class="table-responsive"><table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>Fecha</th><th>Edad</th><th>Cédula</th><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                      <?php if (empty($grupos['month'])): ?><tr><td colspan="4" class="text-muted">Sin cumpleaños este mes</td></tr><?php endif; ?>
                      <?php foreach ($grupos['month'] as $it): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($it['fecha_label']); ?></td>
                          <td><?php echo (int)$it['edad']; ?></td>
                          <td><?php echo htmlspecialchars($it['cedula']); ?></td>
                          <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                          <td><a class="btn btn-sm btn-outline-info" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados_detalle.php?cedula=<?php echo urlencode($it['cedula']); ?>" title="Ver detalle"><i class="fas fa-eye"></i></a></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
          </table></div>
          </div>
                </div>

        <div class="col-12">
          <h6 class="mb-2">
            <a class="text-decoration-none" data-bs-toggle="collapse" href="#grpNext" role="button" aria-expanded="<?php echo $expandNextDefault ? 'true' : 'false'; ?>" aria-controls="grpNext">
              <i class="fas <?php echo $expandNextDefault ? 'fa-chevron-down' : 'fa-chevron-right'; ?> me-1"></i>Cumpleaños siguiente mes
            </a>
          </h6>
          <div class="collapse <?php echo $expandNextDefault ? 'show' : ''; ?>" id="grpNext">
          <div class="table-responsive"><table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>Fecha</th><th>Edad</th><th>Cédula</th><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                      <?php if (empty($grupos['next'])): ?><tr><td colspan="4" class="text-muted">Sin cumpleaños el próximo mes</td></tr><?php endif; ?>
                      <?php foreach ($grupos['next'] as $it): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($it['fecha_label']); ?></td>
                          <td><?php echo (int)$it['edad']; ?></td>
                          <td><?php echo htmlspecialchars($it['cedula']); ?></td>
                          <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                          <td><a class="btn btn-sm btn-outline-info" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados_detalle.php?cedula=<?php echo urlencode($it['cedula']); ?>" title="Ver detalle"><i class="fas fa-eye"></i></a></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
          </table></div>
          </div>
                </div>

        <div class="col-12">
          <h6 class="mb-2">
            <a class="text-decoration-none" data-bs-toggle="collapse" href="#grpRest" role="button" aria-expanded="false" aria-controls="grpRest">
              <i class="fas fa-chevron-right me-1"></i>Cumpleaños el resto del año
            </a>
          </h6>
          <div class="collapse" id="grpRest">
          <div class="table-responsive"><table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>Fecha</th><th>Edad</th><th>Cédula</th><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
              <?php if (empty($grupos['rest'])): ?><tr><td colspan="4" class="text-muted">Sin cumpleaños en el resto del año</td></tr><?php endif; ?>
                      <?php foreach ($grupos['rest'] as $it): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($it['fecha_label']); ?></td>
                          <td><?php echo (int)$it['edad']; ?></td>
                          <td><?php echo htmlspecialchars($it['cedula']); ?></td>
                          <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                          <td><a class="btn btn-sm btn-outline-info" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados_detalle.php?cedula=<?php echo urlencode($it['cedula']); ?>" title="Ver detalle"><i class="fas fa-eye"></i></a></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
          </table></div>
          </div>
        </div>

        <div class="col-12">
          <h6 class="mb-2">
            <a class="text-decoration-none" data-bs-toggle="collapse" href="#grpNextYear" role="button" aria-expanded="false" aria-controls="grpNextYear">
              <i class="fas fa-chevron-right me-1"></i>Cumpleaños (siguiente año)
            </a>
          </h6>
          <div class="collapse" id="grpNextYear">
          <div class="table-responsive"><table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr><th>Fecha</th><th>Edad</th><th>Cédula</th><th>Nombre</th><th>Acciones</th></tr></thead>
            <tbody>
              <?php if (empty($grupos['nextyear'])): ?><tr><td colspan="4" class="text-muted">Sin cumpleaños para el siguiente año</td></tr><?php endif; ?>
              <?php foreach ($grupos['nextyear'] as $it): ?>
                <tr>
                  <td><?php echo htmlspecialchars($it['fecha_label']); ?></td>
                  <td><?php echo (int)$it['edad']; ?></td>
                  <td><?php echo htmlspecialchars($it['cedula']); ?></td>
                  <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                  <td><a class="btn btn-sm btn-outline-info" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados_detalle.php?cedula=<?php echo urlencode($it['cedula']); ?>" title="Ver detalle"><i class="fas fa-eye"></i></a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table></div>
          </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 d-none" id="ahorrosSection">
          <div class="card">
            <div class="card-header"><strong>Ahorros</strong></div>
            <div class="card-body">
              <div class="alert alert-info mb-0"><i class="fas fa-tools me-2"></i>En desarrollo</div>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
// Toggle chevrons on collapse show/hide
document.addEventListener('shown.bs.collapse', function (e) {
  const trigger = document.querySelector('a[href="#' + e.target.id + '"] i.fas');
  if (trigger) { trigger.classList.remove('fa-chevron-right'); trigger.classList.add('fa-chevron-down'); }
});
document.addEventListener('hidden.bs.collapse', function (e) {
  const trigger = document.querySelector('a[href="#' + e.target.id + '"] i.fas');
  if (trigger) { trigger.classList.remove('fa-chevron-down'); trigger.classList.add('fa-chevron-right'); }
});
function showTab(which){
  const isCumples = which === 'cumples';
  document.getElementById('cumplesSection').classList.toggle('d-none', !isCumples);
  document.getElementById('ahorrosSection').classList.toggle('d-none', isCumples);
  document.getElementById('tabCumpleBtn').classList.toggle('active', isCumples);
  document.getElementById('tabAhorrosBtn').classList.toggle('active', !isCumples);
}
document.getElementById('tabCumpleBtn')?.addEventListener('click', function(e){ e.preventDefault(); showTab('cumples'); });
document.getElementById('tabAhorrosBtn')?.addEventListener('click', function(e){ e.preventDefault(); showTab('ahorros'); });
showTab('cumples');
</script>

<?php include '../../../views/layouts/footer.php'; ?>


