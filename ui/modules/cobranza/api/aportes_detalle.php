<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('cobranza.comunicaciones');
    $cedula = trim($_GET['cedula'] ?? '');
    if ($cedula === '') { throw new Exception('Cédula requerida'); }

    $db = getConnection();

    // Obtener fecha de afiliación del asociado
    $stmtA = $db->prepare("SELECT fechai FROM sifone_asociados WHERE cedula = ?");
    $stmtA->execute([$cedula]);
    $rowA = $stmtA->fetch();
    $fechai = $rowA && !empty($rowA['fechai']) ? new DateTime($rowA['fechai']) : null;

    $hoy = new DateTime('today');
    $hace2m = (clone $hoy)->modify('-2 months');
    $desde = $fechai && $fechai > $hace2m ? $fechai : $hace2m;
    $desdeSql = $desde->format('Y-m-d');

    // Último aporte (sin limitar por rango), usar columna fecham para orden
    $stmtUlt = $db->prepare("SELECT fecham AS fecha, numero, detall, credit
                             FROM sifone_movimientos_tributarios
                             WHERE cedula = ?
                               AND cuenta = '31050501'
                               AND COALESCE(credit,0) > 0
                             ORDER BY fecham DESC
                             LIMIT 1");
    $stmtUlt->execute([$cedula]);
    $rowUlt = $stmtUlt->fetch();

    // Listado de aportes en el periodo (cuenta aportes 31050501, solo créditos), usar fecham y rango
    $stmt = $db->prepare("SELECT fecham AS fecha, numero, detall, credit
                          FROM sifone_movimientos_tributarios
                          WHERE cedula = ?
                            AND cuenta = '31050501'
                            AND COALESCE(credit,0) > 0
                            AND fecham >= ?
                          ORDER BY fecham DESC");
    $stmt->execute([$cedula, $desdeSql]);
    $items = $stmt->fetchAll();

    // Cálculo del indicador: aportes realizados / esperados
    // Definición: esperados = semanas completas en el periodo (aprox: 8 en dos meses);
    // si afiliación es menor al periodo, usar semanas desde afiliación.
    $intervalDays = max(1, (int)(($hoy->getTimestamp() - $desde->getTimestamp()) / 86400));
    $expected = (int)ceil($intervalDays / 7); // 1 por semana
    $realizados = count($items);

    echo json_encode([
        'success' => true,
        'data' => [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hoy->format('Y-m-d'),
            'realizados' => $realizados,
            'esperados' => $expected,
            'ultimo_aporte' => $rowUlt ? ($rowUlt['fecha'] ?? null) : null,
            'items' => array_map(function($r){
                return [
                    'fecha' => $r['fecha'],
                    'numero' => $r['numero'] ?? null,
                    'detalle' => $r['detall'] ?? null,
                    'valor' => (float)($r['credit'] ?? 0),
                ];
            }, $items)
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


