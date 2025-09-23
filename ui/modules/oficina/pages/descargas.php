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
$type = isset($_GET['type']) ? $_GET['type'] : 'pse';

if ($download) {
  // Generar XLS y forzar descarga
  $conn = getConnection();
  try {
    // Asegurar nombres de mes en español
    $conn->exec("SET lc_time_names = 'es_ES'");
  } catch (Throwable $e) { /* ignorar si no está disponible */ }

  if ($type === 'pse') {
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
  CAST(CASE WHEN m.diav IS NULL THEN a.valorc ELSE COALESCE(m.sdomor,0) END AS DECIMAL(12,2)) AS optional2,
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
  DATE_FORMAT(COALESCE(m.fechap, dv.fecha_pago), '%M %e de %Y') AS optional9,
  CAST(
    (CASE WHEN m.diav IS NULL THEN a.valorc ELSE COALESCE(m.sdomor,0) END)
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
LEFT JOIN sifone_datacredito_vw dv
  ON CAST(dv.cedula AS UNSIGNED) = CAST(a.cedula AS UNSIGNED)
 AND CAST(dv.numero_credito AS UNSIGNED) = CAST(a.numero AS UNSIGNED)
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

    // Generar XLS (BIFF) real para evitar advertencias de Excel
    if (ob_get_length()) { @ob_end_clean(); }
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="pse.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $xlsBOF = function(){ echo pack('vvvvvv', 0x809, 0x0008, 0x0000, 0x0010, 0x0000, 0x0000); };
    $xlsEOF = function(){ echo pack('vv', 0x000A, 0x0000); };
    $xlsLabel = function($row, $col, $text){ $xf=0x0000; $str = utf8_decode((string)$text); $len = strlen($str); echo pack('vvvvv', 0x0204, 8+$len, $row, $col, $xf) . pack('v', $len) . $str; };

    $xlsBOF();
    $moneyCols = [5,6,7,8,9,10,11,13,15,16,17];
    for ($r = 0; $r < count($xlsxRows); $r++) {
      $row = $xlsxRows[$r];
      for ($c = 0; $c < count($row); $c++) {
        $val = $row[$c];
        if ($r === 0) {
          $xlsLabel($r, $c, $val);
        } else {
          if (in_array($c, $moneyCols, true)) {
            $txt = number_format((float)$val, 2, ',', '');
            $xlsLabel($r, $c, $txt);
          } else {
            // Forzar invoice_id (col 4, index 4) como número entero
            if ($c === 4 && is_numeric($val)) {
              $intVal = (int)$val;
              $xlsLabel($r, $c, (string)$intVal);
            } else {
              $xlsLabel($r, $c, (string)$val);
            }
          }
        }
      }
    }
    $xlsEOF();
    exit;
  } elseif ($type === 'mora') {
    $sql = <<<SQL
SELECT
  a.nombre AS nombre_completo,
  a.nombr1 AS nombre,
  sa.celula AS telefono,
  sa.mail AS email,
  COALESCE(m.sdomor, 0) AS saldo_mora,
  COALESCE(m.diav, 0) AS dias_mora,
  COALESCE(fb.monto, 0) AS fondo_bienestar,
  COALESCE(ap.monto, 0) AS aportes,
  COALESCE(bol.monto, 0) AS bolsillo,
  COALESCE(pf.monto, 0) AS plan_futuro,
  COALESCE(m.sdomor, 0) +
  COALESCE(m.diav, 0) +
  COALESCE(fb.monto, 0) +
  COALESCE(ap.monto, 0) +
  COALESCE(bol.monto, 0) +
  COALESCE(pf.monto, 0) AS total
FROM control_asociados ca
JOIN sifone_asociados sa ON sa.cedula = ca.cedula
JOIN sifone_cartera_mora m ON m.cedula = sa.cedula
LEFT JOIN sifone_cartera_aseguradora a ON a.cedula = m.cedula AND a.numero = m.presta
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
  WHERE ap.estado_activo = TRUE AND p.estado_activo = TRUE AND p.nombre = 'Plan Futuro'
  GROUP BY ap.cedula
) pf ON pf.cedula = sa.cedula
WHERE ca.estado_activo = TRUE AND COALESCE(m.sdomor,0) > 0
ORDER BY m.diav DESC, sa.cedula
SQL;

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if (ob_get_length()) { @ob_end_clean(); }

    $xlsxRows = [];
    $xlsxRows[] = [
      'Nombre Completo', 'Nombres', 'Telefono', 'Email', 'Saldo mora', 'dias mora',
      'Valor Fondo Bienestar', 'Valor Aportes', 'Valor Bolsillo', 'Valor Plan Futuro', 'Total'
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $xlsxRows[] = [
        (string)($row['nombre_completo'] ?? ''),
        (string)($row['nombre'] ?? ''),
        (string)($row['telefono'] ?? ''),
        (string)($row['email'] ?? ''),
        (float)($row['saldo_mora'] ?? 0),
        (int)($row['dias_mora'] ?? 0),
        (float)($row['fondo_bienestar'] ?? 0),
        (float)($row['aportes'] ?? 0),
        (float)($row['bolsillo'] ?? 0),
        (float)($row['plan_futuro'] ?? 0),
        (float)($row['total'] ?? 0),
      ];
    }

    $tmpXlsx = tempnam(sys_get_temp_dir(), 'xlsx_');
    if (!class_exists('ZipArchive')) {
      if (ob_get_length()) { @ob_end_clean(); }
      http_response_code(500);
      header('Content-Type: text/plain; charset=UTF-8');
      echo "Error: La extensión ZIP de PHP no está habilitada.\n";
      echo "Habilítela en php.ini (extension=zip) y reinicie Apache.";
      exit;
    }
    $zip = new ZipArchive();
    $zip->open($tmpXlsx, ZipArchive::OVERWRITE);

    $colLetter = function($i){
      $i = (int)$i; $letters = '';
      while ($i >= 0) { $letters = chr($i % 26 + 65) . $letters; $i = intdiv($i, 26) - 1; }
      return $letters;
    };
    $xmlHeader = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>";

    $contentTypes = $xmlHeader.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
      .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
      .'<Default Extension="xml" ContentType="application/xml"/>'
      .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
      .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
      .'</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    $rels = $xmlHeader.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
      .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="/xl/workbook.xml"/>'
      .'</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    $workbook = $xmlHeader.'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
      .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
      .'<sheets><sheet name="Mora" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $zip->addFromString('xl/workbook.xml', $workbook);

    $wbRels = $xmlHeader.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
      .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
      .'</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    $widths = [18, 40, 22, 16, 12, 18, 16, 16, 16];
    $colsXml = '<cols>';
    for ($i=0; $i<count($widths); $i++) {
      $colsXml .= '<col min="'.($i+1).'" max="'.($i+1).'" width="'.$widths[$i].'" customWidth="1"/>';
    }
    $colsXml .= '</cols>';
    $sheet = $xmlHeader.'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.$colsXml.'<sheetData>';
    for ($r = 0; $r < count($xlsxRows); $r++) {
      $rowXml = '<row r="'.($r+1).'">';
      $row = $xlsxRows[$r];
      $isHeader = ($r === 0);
      $commaCols = [3,5,6,7,8];
      $stringCols = [0,1,2];
      for ($c = 0; $c < count($row); $c++) {
        $cellRef = $colLetter($c).($r+1);
        $val = $row[$c];
        if ($isHeader) {
          $safe = htmlspecialchars((string)$val, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
          $rowXml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
        } elseif (in_array($c, $commaCols, true)) {
          $textVal = number_format((float)$val, 2, ',', '');
          $safe = htmlspecialchars($textVal, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
          $rowXml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
        } elseif (in_array($c, $stringCols, true)) {
          $safe = htmlspecialchars((string)$val, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
          $rowXml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
        } elseif (is_numeric($val)) {
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
    header('Content-Disposition: attachment; filename="asociados_mora.xlsx"');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tmpXlsx);
    @unlink($tmpXlsx);
    exit;
  }
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
            <input type="hidden" name="type" value="pse">
            <div class="d-grid gap-2 d-md-flex">
              <button class="btn btn-primary"><i class="fas fa-file-excel me-1"></i>Descargar XLS (pse.xls)</button>
              <a class="btn btn-outline-secondary" href="<?php echo getBaseUrl(); ?>assets/plantillas/pse_plantilla.xls" download>
                <i class="fas fa-download me-1"></i>Descargar plantilla
              </a>
            </div>
          </div>
        </form>
      </div></div>

      <div class="card mt-3"><div class="card-body">
        <div class="mb-2 text-muted">Archivo para gestión de cobro (Asociados en Mora)</div>
        <form class="row g-2" method="GET" target="_blank" rel="noopener">
          <div class="col-md-7">
            <div class="form-text">Descarga datos de asociados en mora: teléfono, nombre, días y valores por rubro.</div>
          </div>
          <div class="col-md-3 align-self-end">
            <input type="hidden" name="download" value="1">
            <input type="hidden" name="type" value="mora">
            <button class="btn btn-success w-100"><i class="fas fa-file-excel me-1"></i>Descargar XLSX (asociados_mora.xlsx)</button>
          </div>
        </form>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>


