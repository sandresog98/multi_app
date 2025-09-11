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
  // Generar XLS y forzar descarga
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

  // Construir filas en memoria para generar XLSX (sin librerías externas)
  $xlsxRows = [];
  $xlsxRows[] = ['customer_id','customerid_type','optional1','payment_description1','invoice_id','optional2','optional3','optional4','optional5','optional6','optional7','optional8','optional9','amount1','date1','optional10','vatAmount1','optional11'];

  $invoice = (int)$start;
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $amount = (float)($row['amount1'] ?? 0);
    if ($amount <= 0) { continue; }
    $digits = preg_replace('/\D+/', '', (string)($row['cedula_src'] ?? ''));
    $customerId = ($digits !== '' ? (int)$digits : null);
    $invoice++;
    $xlsxRows[] = [
      $customerId,
      $row['customerid_type'],
      ($row['optional1'] !== null ? $row['optional1'] : 0),
      $row['payment_description1'],
      $invoice,
      $row['optional2'],
      $row['optional3'],
      $row['optional4'],
      $row['optional5'],
      $row['optional6'],
      $row['optional7'],
      $row['optional8'],
      $row['optional9'],
      $row['amount1'],
      $row['date1'],
      $row['optional10'],
      $row['vatAmount1'],
      $row['optional11'],
    ];
  }

  // Generar XLSX mínimo
  $tmpXlsx = tempnam(sys_get_temp_dir(), 'xlsx_');
  $zip = new ZipArchive();
  $zip->open($tmpXlsx, ZipArchive::OVERWRITE);

  // Helpers
  $colLetter = function($i){
    $i = (int)$i; $letters = '';
    while ($i >= 0) { $letters = chr($i % 26 + 65) . $letters; $i = intdiv($i, 26) - 1; }
    return $letters;
  };
  $xmlHeader = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>";

  // [Content_Types].xml
  $contentTypes = $xmlHeader.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    .'<Default Extension="xml" ContentType="application/xml"/>'
    .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    .'</Types>';
  $zip->addFromString('[Content_Types].xml', $contentTypes);

  // _rels/.rels
  $rels = $xmlHeader.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="/xl/workbook.xml"/>'
    .'</Relationships>';
  $zip->addFromString('_rels/.rels', $rels);

  // xl/workbook.xml
  $workbook = $xmlHeader.'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
    .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    .'<sheets><sheet name="Hoja1" sheetId="1" r:id="rId1"/></sheets></workbook>';
  $zip->addFromString('xl/workbook.xml', $workbook);

  // xl/_rels/workbook.xml.rels
  $wbRels = $xmlHeader.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    .'</Relationships>';
  $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

  // xl/worksheets/sheet1.xml (inline strings)
  $sheet = $xmlHeader.'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
  for ($r = 0; $r < count($xlsxRows); $r++) {
    $rowXml = '<row r="'.($r+1).'">';
    $row = $xlsxRows[$r];
    for ($c = 0; $c < count($row); $c++) {
      $cellRef = $colLetter($c).($r+1);
      $val = $row[$c];
      if (is_numeric($val) && $c !== 1 && $c !== 13 && $c !== 14) { // strings: customerid_type (1), optional9 (13), date1 (14)
        $rowXml .= '<c r="'.$cellRef.'"><v>'.(0+$val).'</v></c>';
      } else {
        $safe = htmlspecialchars((string)$val, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        $rowXml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
      }
    }
    $rowXml .= '</row>';
    $sheet .= $rowXml;
  }
  $sheet .= '</sheetData></worksheet>';
  $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

  $zip->close();

  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="pse.xlsx"');
  header('Pragma: no-cache');
  header('Expires: 0');
  readfile($tmpXlsx);
  @unlink($tmpXlsx);
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
            <button class="btn btn-primary w-100"><i class="fas fa-file-excel me-1"></i>Descargar XLS (pse.xls)</button>
          </div>
        </form>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>


