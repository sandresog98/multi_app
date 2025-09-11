<?php
// Iniciar buffer muy temprano para capturar cualquier salida accidental (BOM/espacios en includes)
if (!headers_sent()) { ob_start(); }
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';
require_once '../../../config/database.php';

$auth = new AuthController();
$auth->requireModule('oficina.descargas');

$pageTitle = 'Oficina - Descargas';
$currentPage = 'descargas';
$currentUser = $auth->getCurrentUser();

$start = isset($_GET['start']) ? (int)$_GET['start'] : 300000;
$download = isset($_GET['download']) && $_GET['download'] === '1';

if ($download) {
  // Generar CSV y forzar descarga
  $conn = getConnection();
  try {
    // Asegurar nombres de mes en español
    $conn->exec("SET lc_time_names = 'es_ES'");
  } catch (Throwable $e) { /* ignorar si no está disponible */ }

  // Set consecutivo inicial
  $stmtSet = $conn->prepare("SET @invoice_start := :start");
  $stmtSet->execute([':start' => $start]);

  $sql = <<<SQL
SELECT
  sa.cedula AS cedula_src,
  sa.nombre AS customerid_type,
  0 AS optional1,
  'APORTES' AS payment_description1,
  CAST(0 AS DECIMAL(12,2)) AS optional2,
  COALESCE(pf.monto, 0) AS optional3,
  CAST(0 AS DECIMAL(12,2)) AS optional4,
  COALESCE(bol.monto, 0) AS optional5,
  CAST(0 AS DECIMAL(12,2)) AS optional6,
  COALESCE(fb.monto, 0) AS optional7,
  CAST(0 AS DECIMAL(12,2)) AS optional8,
  DATE_FORMAT(LAST_DAY(CURDATE()), '%M %e de %Y') AS optional9,
  (COALESCE(pf.monto,0)+COALESCE(bol.monto,0)+COALESCE(fb.monto,0)+COALESCE(ap.monto,0)) AS amount1,
  DATE_FORMAT(LAST_DAY(CURDATE()), '%d/%m/%Y') AS date1,
  CAST(0 AS DECIMAL(12,2)) AS optional10,
  CAST(0 AS DECIMAL(12,2)) AS vatAmount1,
  COALESCE(ap.monto, 0) AS optional11
FROM control_asociados ca
JOIN sifone_asociados sa ON sa.cedula = ca.cedula
LEFT JOIN (
  SELECT ap.cedula, SUM(ap.monto_pago) AS monto
  FROM control_asignacion_asociado_producto ap
  JOIN control_productos p ON p.id = ap.producto_id
  WHERE ap.estado_activo = TRUE AND p.estado_activo = TRUE AND p.nombre = 'Plan Futuro'
  GROUP BY ap.cedula
) pf ON pf.cedula = sa.cedula
LEFT JOIN (
  SELECT ap.cedula, SUM(ap.monto_pago) AS monto
  FROM control_asignacion_asociado_producto ap
  JOIN control_productos p ON p.id = ap.producto_id
  WHERE ap.estado_activo = TRUE AND p.estado_activo = TRUE AND p.nombre = 'Bolsillo'
  GROUP BY ap.cedula
) bol ON bol.cedula = sa.cedula
LEFT JOIN (
  SELECT ap.cedula, SUM(ap.monto_pago) AS monto
  FROM control_asignacion_asociado_producto ap
  JOIN control_productos p ON p.id = ap.producto_id
  WHERE ap.estado_activo = TRUE AND p.estado_activo = TRUE AND p.nombre = 'Fondo Bienestar'
  GROUP BY ap.cedula
) fb ON fb.cedula = sa.cedula
LEFT JOIN (
  SELECT ap.cedula, SUM(ap.monto_pago) AS monto
  FROM control_asignacion_asociado_producto ap
  JOIN control_productos p ON p.id = ap.producto_id
  WHERE ap.estado_activo = TRUE AND p.estado_activo = TRUE AND p.nombre = 'Aportes'
  GROUP BY ap.cedula
) ap ON ap.cedula = sa.cedula
WHERE ca.estado_activo = TRUE

UNION ALL

SELECT
  sa.cedula AS cedula_src,
  sa.nombre AS customerid_type,
  a.numero AS optional1,
  a.tipopr AS payment_description1,
  CAST(a.valorc + CASE WHEN m.diav IS NULL THEN 0 ELSE COALESCE(m.sdomor,0) END AS DECIMAL(12,2)) AS optional2,
  CAST(0 AS DECIMAL(12,2)) AS optional3,
  CAST(0 AS DECIMAL(12,2)) AS optional4,
  CAST(0 AS DECIMAL(12,2)) AS optional5,
  CAST(0 AS DECIMAL(12,2)) AS optional6,
  CAST(0 AS DECIMAL(12,2)) AS optional7,
  CAST(ROUND(a.valorc * (
    CASE
      WHEN m.diav > 60 THEN 0.08
      WHEN m.diav > 50 THEN 0.06
      WHEN m.diav > 40 THEN 0.05
      WHEN m.diav > 30 THEN 0.04
      WHEN m.diav > 20 THEN 0.03
      WHEN m.diav > 11 THEN 0.02
      ELSE 0
    END
  ), 2) AS DECIMAL(12,2)) AS optional8,
  DATE_FORMAT(LAST_DAY(CURDATE()), '%M %e de %Y') AS optional9,
  CAST(
    (a.valorc + CASE WHEN m.diav IS NULL THEN 0 ELSE COALESCE(m.sdomor,0) END)
    + ROUND(a.valorc * (
        CASE
          WHEN m.diav > 60 THEN 0.08
          WHEN m.diav > 50 THEN 0.06
          WHEN m.diav > 40 THEN 0.05
          WHEN m.diav > 30 THEN 0.04
          WHEN m.diav > 20 THEN 0.03
          WHEN m.diav > 11 THEN 0.02
          ELSE 0
        END
      ), 2)
    AS DECIMAL(12,2)
  ) AS amount1,
  DATE_FORMAT(LAST_DAY(CURDATE()), '%d/%m/%Y') AS date1,
  CAST(0 AS DECIMAL(12,2)) AS optional10,
  CAST(0 AS DECIMAL(12,2)) AS vatAmount1,
  CAST(0 AS DECIMAL(12,2)) AS optional11
FROM control_asociados ca
JOIN sifone_asociados sa ON sa.cedula = ca.cedula
JOIN sifone_cartera_aseguradora a ON a.cedula = sa.cedula
LEFT JOIN sifone_cartera_mora m ON m.cedula = a.cedula AND m.presta = a.numero
WHERE ca.estado_activo = TRUE

ORDER BY 1, 4, 3
SQL;

  $stmt = $conn->prepare($sql);
  $stmt->execute();

  // Limpiar cualquier buffer previo para evitar líneas en blanco al inicio
  if (ob_get_length()) { @ob_end_clean(); }

  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="pse.xls"');
  header('Pragma: no-cache');
  header('Expires: 0');
  header('Content-Description: File Transfer');
  header('Content-Transfer-Encoding: binary');
  // Mensaje visible en la UI no viaja en headers; dejamos el filename y mantenemos la UI con texto.

  // Comenzar documento XLS (HTML compatible con Excel)
  echo "<html><head><meta charset='UTF-8'></head><body>";
  echo "<table border='1'>";
  echo "<thead><tr>"
     ."<th>customer_id</th><th>customerid_type</th><th>optional1</th><th>payment_description1</th><th>invoice_id</th>"
     ."<th>optional2</th><th>optional3</th><th>optional4</th><th>optional5</th><th>optional6</th><th>optional7</th><th>optional8</th>"
     ."<th>optional9</th><th>amount1</th><th>date1</th><th>optional10</th><th>vatAmount1</th><th>optional11</th>"
     ."</tr></thead><tbody>";

  $invoice = (int)$start;
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Filtrar después de ejecutar SQL: descartar registros con amount1 == 0
    $amount = (float)($row['amount1'] ?? 0);
    if ($amount <= 0) { continue; }

    $digits = preg_replace('/\D+/', '', (string)($row['cedula_src'] ?? ''));
    $customerId = ($digits !== '' ? (int)$digits : null);
    $invoice++;
    echo '<tr>'
      .'<td>'.htmlspecialchars((string)$customerId, ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['customerid_type'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)(($row['optional1'] !== null ? $row['optional1'] : 0)), ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['payment_description1'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$invoice, ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional2'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional3'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional4'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional5'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional6'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional7'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional8'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional9'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['amount1'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['date1'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional10'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['vatAmount1'], ENT_QUOTES, 'UTF-8').'</td>'
      .'<td>'.htmlspecialchars((string)$row['optional11'], ENT_QUOTES, 'UTF-8').'</td>'
      .'</tr>';
  }
  echo '</tbody></table></body></html>';
  exit;
}

include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-download me-2"></i>Descargas</h1>
      </div>

      <p class="text-muted">En esta sección podemos descargar los archivos requeridos para manejo externo.</p>

      <div class="card"><div class="card-body">
        <div class="mb-2 text-muted">Archivo requerido para cargar a PSE</div>
        <form class="row g-2" method="GET" target="_blank" rel="noopener">
          <div class="col-md-4">
            <label class="form-label">Consecutivo inicial para invoice_id</label>
            <input type="number" class="form-control" name="start" value="<?php echo (int)$start; ?>" min="1" required>
          </div>
          <div class="col-md-3 align-self-end">
            <input type="hidden" name="download" value="1">
            <button class="btn btn-primary w-100"><i class="fas fa-file-csv me-1"></i>Descargar CSV (pse.csv)</button>
          </div>
        </form>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>


