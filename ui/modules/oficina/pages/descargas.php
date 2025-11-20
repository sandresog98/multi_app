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
-- Asociados en mora de crédito
SELECT
  a.nombre AS nombre_completo,
  a.nombr1 AS nombre,
  sa.celula AS telefono,
  sa.mail AS email,
  'crédito' AS segmento,
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

UNION ALL

-- Asociados en mora de aportes (sin mora de crédito y con último aporte >= 30 días)
SELECT
  sa.nombre AS nombre_completo,
  sa.nombre AS nombre,
  sa.celula AS telefono,
  sa.mail AS email,
  'aportes' AS segmento,
  0 AS saldo_mora,
  DATEDIFF(CURDATE(), ua.ultima_aporte) AS dias_mora,
  COALESCE(fb.monto, 0) AS fondo_bienestar,
  COALESCE(ap.monto, 0) AS aportes,
  COALESCE(bol.monto, 0) AS bolsillo,
  COALESCE(pf.monto, 0) AS plan_futuro,
  COALESCE(fb.monto, 0) + COALESCE(ap.monto, 0) + COALESCE(bol.monto, 0) + COALESCE(pf.monto, 0) AS total
FROM (
  SELECT t.cedula, MAX(t.fecham) AS ultima_aporte
  FROM sifone_movimientos_tributarios t
  WHERE t.cuenta = '31050501' AND COALESCE(t.credit,0) > 0
  GROUP BY t.cedula
) ua
JOIN sifone_asociados sa ON sa.cedula = ua.cedula
JOIN control_asociados ca ON ca.cedula = sa.cedula
LEFT JOIN (
  SELECT DISTINCT cedula FROM sifone_cartera_mora
) mor ON mor.cedula = ua.cedula
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
WHERE ca.estado_activo = TRUE AND mor.cedula IS NULL AND DATEDIFF(CURDATE(), ua.ultima_aporte) >= 30

ORDER BY nombre_completo
SQL;

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if (ob_get_length()) { @ob_end_clean(); }

    $xlsxRows = [];
    $xlsxRows[] = [
      'Nombre Completo', 'Nombres', 'Telefono', 'Email', 'Segmento', 'Saldo mora', 'dias mora',
      'Valor Fondo Bienestar', 'Valor Aportes', 'Valor Bolsillo', 'Valor Plan Futuro', 'Total'
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $xlsxRows[] = [
        (string)($row['nombre_completo'] ?? ''),
        (string)($row['nombre'] ?? ''),
        (string)($row['telefono'] ?? ''),
        (string)($row['email'] ?? ''),
        (string)($row['segmento'] ?? ''),
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

    $widths = [18, 40, 22, 16, 16, 12, 18, 16, 16, 16, 16, 16];
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
      $commaCols = [5,7,8,9,10,11];
      $stringCols = [0,1,2,3,4];
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
  } elseif ($type === 'transacciones') {
    // Exportar transacciones con información bancaria y asignaciones
    $sql = <<<SQL
SELECT b.origen,
       b.id,
       b.fecha_banco,
       b.valor,
       b.tipo_transaccion,
       COALESCE(ap.fecha_validacion, ac.fecha_validacion) AS fecha_validacion,
       t.id                                               AS transaccion_id,
       t.cedula,
       sa.nombre                                          AS asociado_nombre,
       t.valor_pago_total,
       t.fecha_creacion                                   AS fecha_asignacion,
       t.recibo_caja_sifone,
       SUM(COALESCE(d.valor_asignado, 0))                 AS valor_asignado
FROM (SELECT 'pse'                                   AS origen,
             pse_id                                  AS id,
             valor,
             fecha_hora_resolucion_de_la_transaccion AS fecha_banco,
             'PSE'                                   AS tipo_transaccion
      FROM banco_pse
      WHERE estado = 'Aprobada'
      UNION ALL
      SELECT 'confiar'          AS origen,
             confiar_id         AS id,
             valor_consignacion AS valor,
             fecha              AS fecha_banco,
             tipo_transaccion
      FROM banco_confiar
      WHERE LENGTH(tipo_transaccion) > 0) AS b
         LEFT JOIN banco_asignacion_pse AS ap
                   ON (b.origen = 'pse' AND b.id = ap.pse_id)
         LEFT JOIN banco_confirmacion_confiar AS ac
                   ON (b.origen = 'confiar' AND b.id = ac.confiar_id)
         LEFT JOIN control_transaccion AS t
                   ON ((b.origen = 'pse' AND t.pse_id = b.id)
                       OR (b.origen = 'confiar' AND t.confiar_id = b.id))
         LEFT JOIN control_transaccion_detalle d ON d.transaccion_id = t.id
         LEFT JOIN sifone_asociados sa ON sa.cedula = t.cedula
GROUP BY b.valor, ap.fecha_validacion, ac.fecha_validacion, t.id, t.cedula, sa.nombre, t.valor_pago_total, t.fecha_creacion, t.recibo_caja_sifone
ORDER BY b.fecha_banco, b.id, t.id, d.id
SQL;

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if (ob_get_length()) { @ob_end_clean(); }

    $rows = [];
    $rows[] = [
      'origen',
      'id',
      'fecha_banco',
      'valor',
      'tipo_transaccion',
      'fecha_validacion',
      'transaccion_id',
      'cedula',
      'asociado_nombre',
      'valor_pago_total',
      'fecha_asignacion',
      'recibo_caja_sifone',
      'valor_asignado'
    ];

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $idBanco = (string)($r['id'] ?? '');
      $rows[] = [
        (string)($r['origen'] ?? ''),
        (string)$idBanco,
        (string)($r['fecha_banco'] ?? ''),
        (float)($r['valor'] ?? 0),
        (string)($r['tipo_transaccion'] ?? ''),
        (string)($r['fecha_validacion'] ?? ''),
        (int)($r['transaccion_id'] ?? 0),
        (string)($r['cedula'] ?? ''),
        (string)($r['asociado_nombre'] ?? ''),
        (float)($r['valor_pago_total'] ?? 0),
        (string)($r['fecha_asignacion'] ?? ''),
        (string)($r['recibo_caja_sifone'] ?? ''),
        (float)($r['valor_asignado'] ?? 0)
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
      .'<sheets><sheet name="Transacciones" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $zip->addFromString('xl/workbook.xml', $workbook);

    $wbRels = $xmlHeader.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
      .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
      .'</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    $widths = [10, 22, 14, 14, 18, 16, 12, 16, 36, 16, 16, 18, 16];
    $colsXml = '<cols>';
    for ($i=0; $i<count($widths); $i++) {
      $colsXml .= '<col min="'.($i+1).'" max="'.($i+1).'" width="'.$widths[$i].'" customWidth="1"/>';
    }
    $colsXml .= '</cols>';
    $sheet = $xmlHeader.'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.$colsXml.'<sheetData>';
    for ($r = 0; $r < count($rows); $r++) {
      $rowXml = '<row r="'.($r+1).'">';
      $row = $rows[$r];
      $isHeader = ($r === 0);
      // Columnas numéricas con coma: valor (3), valor_pago_total (9), valor_asignado (12)
      $commaCols = [3,9,12];
      // Forzar texto para mantener formatos y ceros a la izquierda
      // origen(0), id(1), fecha_banco(2), tipo_transaccion(4), fecha_validacion(5), cedula(7), asociado_nombre(8), fecha_asignacion(10), recibo(11)
      $stringCols = [0,1,2,4,5,7,8,10,11];
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
    header('Content-Disposition: attachment; filename="transacciones.xlsx"');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tmpXlsx);
    @unlink($tmpXlsx);
    exit;
  } elseif ($type === 'inventario_tienda') {
    // Exportar inventario de productos disponibles en la tienda
    $sql = <<<SQL
SELECT 
  p.id AS producto_id,
  p.nombre AS producto_nombre,
  c.nombre AS categoria,
  m.nombre AS marca,
  COALESCE(p.precio_compra_aprox, 0) AS precio_compra,
  COALESCE(p.precio_venta_aprox, 0) AS precio_venta,
  COALESCE(cd.ingresado, 0) - COALESCE(vd.vendido, 0) AS cantidad_disponible
FROM tienda_producto p
INNER JOIN tienda_categoria c ON c.id = p.categoria_id
INNER JOIN tienda_marca m ON m.id = p.marca_id
LEFT JOIN (
  SELECT producto_id, SUM(cantidad) AS ingresado
  FROM tienda_compra_detalle
  GROUP BY producto_id
) cd ON cd.producto_id = p.id
LEFT JOIN (
  SELECT producto_id,
         SUM(CASE WHEN compra_imei_id IS NULL THEN cantidad ELSE 1 END) AS vendido
  FROM tienda_venta_detalle
  GROUP BY producto_id
) vd ON vd.producto_id = p.id
WHERE p.estado_activo = TRUE
ORDER BY c.nombre, m.nombre, p.nombre
SQL;

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if (ob_get_length()) { @ob_end_clean(); }

    $xlsxRows = [];
    $xlsxRows[] = [
      'ID Producto',
      'Nombre Producto',
      'Categoría',
      'Marca',
      'Precio Compra',
      'Precio Venta',
      'Cantidad Disponible'
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $xlsxRows[] = [
        (int)($row['producto_id'] ?? 0),
        (string)($row['producto_nombre'] ?? ''),
        (string)($row['categoria'] ?? ''),
        (string)($row['marca'] ?? ''),
        (float)($row['precio_compra'] ?? 0),
        (float)($row['precio_venta'] ?? 0),
        (int)($row['cantidad_disponible'] ?? 0)
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
      .'<sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $zip->addFromString('xl/workbook.xml', $workbook);

    $wbRels = $xmlHeader.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
      .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
      .'</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    $widths = [12, 40, 20, 20, 16, 16, 20];
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
      // Columnas numéricas con coma: precio_compra (4), precio_venta (5)
      $commaCols = [4, 5];
      // Columnas de texto: nombre (1), categoria (2), marca (3)
      $stringCols = [1, 2, 3];
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
    header('Content-Disposition: attachment; filename="Inventario_tienda.xlsx"');
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

      <div class="card mt-3"><div class="card-body">
        <div class="mb-2 text-muted">Exportar Transacciones</div>
        <form class="row g-2" method="GET" target="_blank" rel="noopener">
          <div class="col-md-7">
            <div class="form-text">Descarga las transacciones con datos del banco, asignaciones y recibo Sifone.</div>
          </div>
          <div class="col-md-3 align-self-end">
            <input type="hidden" name="download" value="1">
            <input type="hidden" name="type" value="transacciones">
            <button class="btn btn-outline-success w-100"><i class="fas fa-file-excel me-1"></i>Descargar XLSX (transacciones.xlsx)</button>
          </div>
        </form>
      </div></div>

      <div class="card mt-3"><div class="card-body">
        <div class="mb-2 text-muted">Inventario de Tienda</div>
        <form class="row g-2" method="GET" target="_blank" rel="noopener">
          <div class="col-md-7">
            <div class="form-text">Descarga el inventario completo de productos disponibles en la tienda con precios, categorías, marcas y cantidades.</div>
          </div>
          <div class="col-md-3 align-self-end">
            <input type="hidden" name="download" value="1">
            <input type="hidden" name="type" value="inventario_tienda">
            <button class="btn btn-outline-info w-100"><i class="fas fa-file-excel me-1"></i>Descargar XLSX (Inventario_tienda.xlsx)</button>
          </div>
        </form>
      </div></div>
    </main>
  </div>
</div>

<?php include '../../../views/layouts/footer.php'; ?>


