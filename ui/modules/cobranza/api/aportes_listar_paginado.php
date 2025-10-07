<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $auth = new AuthController();
    $auth->requireModule('cobranza.comunicaciones');

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(200, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    $q = trim($_GET['q'] ?? '');
    $dias = max(30, (int)($_GET['dias'] ?? 60));
    $rango = trim($_GET['rango'] ?? ''); // '30_60' | '60_90' | '90_mas' | ''
    $ultComm = trim($_GET['ult_comm'] ?? ''); // '' | 'sin' | 'muy_reciente' | 'reciente' | 'intermedia' | 'lejana' | 'muy_lejana'

    $db = getConnection();

    $where = [];
    $params = [];

    // Base: última fecha de aporte por asociado (cuenta 31050501, crédito > 0)
    // Excluir asociados con registros en cartera mora (mora crédito)
    $fromSql = "
        FROM (
            SELECT t.cedula AS cedula, MAX(t.fecham) AS ultima_aporte
            FROM sifone_movimientos_tributarios t
            WHERE t.cuenta = '31050501' AND COALESCE(t.credit,0) > 0
            GROUP BY t.cedula
        ) ua
        INNER JOIN sifone_asociados a ON a.cedula = ua.cedula
        INNER JOIN control_asociados ca ON ca.cedula = ua.cedula AND ca.estado_activo = 1
        LEFT JOIN (
            SELECT DISTINCT cedula FROM sifone_cartera_mora
        ) mor ON mor.cedula = a.cedula
        LEFT JOIN (
            SELECT asociado_cedula, MAX(fecha_comunicacion) AS ultima_comunicacion, COUNT(*) AS total_comunicaciones
            FROM cobranza_comunicaciones
            WHERE tipo_origen = 'aportes'
            GROUP BY asociado_cedula
        ) cc ON cc.asociado_cedula = a.cedula
    ";

    $whereParts = [];
    // Excluir con mora crédito y aplicar umbral de 30 días
    $whereParts[] = 'mor.cedula IS NULL';
    // Umbral mínimo 30 días
    $whereParts[] = 'DATEDIFF(CURDATE(), ua.ultima_aporte) >= 30';
    // Rango por días sin aportes
    if ($rango === '30_60') { $whereParts[] = 'DATEDIFF(CURDATE(), ua.ultima_aporte) BETWEEN 30 AND 60'; }
    elseif ($rango === '60_90') { $whereParts[] = 'DATEDIFF(CURDATE(), ua.ultima_aporte) BETWEEN 61 AND 90'; }
    elseif ($rango === '90_mas') { $whereParts[] = 'DATEDIFF(CURDATE(), ua.ultima_aporte) >= 91'; }

    if ($q !== '') {
        $whereParts[] = "(a.cedula LIKE ? OR a.nombre LIKE ? OR a.celula LIKE ? OR a.mail LIKE ?)";
        $like = "%$q%";
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

    // Having por última comunicación (post-aggregation)
    $having = [];
    if ($ultComm !== '') {
        if ($ultComm === 'sin') { $having[] = 'ultima_comunicacion IS NULL'; }
        elseif ($ultComm === 'muy_reciente') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) < 2'; }
        elseif ($ultComm === 'reciente') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 2 AND DATEDIFF(CURDATE(), ultima_comunicacion) < 5'; }
        elseif ($ultComm === 'intermedia') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 5 AND DATEDIFF(CURDATE(), ultima_comunicacion) < 10'; }
        elseif ($ultComm === 'lejana') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) >= 10 AND DATEDIFF(CURDATE(), ultima_comunicacion) <= 20'; }
        elseif ($ultComm === 'muy_lejana') { $having[] = 'ultima_comunicacion IS NOT NULL AND DATEDIFF(CURDATE(), ultima_comunicacion) > 20'; }
    }
    $havingSql = $having ? (' HAVING ' . implode(' AND ', $having)) : '';

    $dataSql = "SELECT a.cedula, a.nombre, a.celula, a.mail, ua.ultima_aporte,
                       DATEDIFF(CURDATE(), ua.ultima_aporte) AS dias_sin_aporte,
                       cc.ultima_comunicacion,
                       DATEDIFF(CURDATE(), cc.ultima_comunicacion) AS dias_ultima,
                       cc.total_comunicaciones
                $fromSql
                $whereSql
                $havingSql
                ORDER BY dias_sin_aporte DESC, a.nombre ASC
                LIMIT ? OFFSET ?";
    $paramsData = array_merge($params, [$limit, $offset]);

    $stmt = $db->prepare($dataSql);
    $stmt->execute($paramsData);
    $rows = $stmt->fetchAll();

    $countSql = "SELECT COUNT(1) AS total $fromSql $whereSql $havingSql";
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($params);
    $total = (int)($stmtCount->fetch()['total'] ?? 0);

    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $rows,
            'total' => $total,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
            'current_page' => $page
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}


