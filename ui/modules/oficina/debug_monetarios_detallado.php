<?php
require_once '../models/DetalleAsociado.php';

$cedula = $_GET['cedula'] ?? '';
if (!$cedula) {
    echo "Error: No se proporcionó cédula";
    exit;
}

$detalleModel = new DetalleAsociado();

echo "<h2>Debug Detallado - Cédula: $cedula</h2>";

// 1. Probar getMonetariosDesdeVista
echo "<h3>1. getMonetariosDesdeVista():</h3>";
$monetariosVista = $detalleModel->getMonetariosDesdeVista($cedula);
echo "<pre>";
print_r($monetariosVista);
echo "</pre>";

// 2. Verificar la condición del fallback
echo "<h3>2. Verificación de condición de fallback:</h3>";
$condicion1 = empty($monetariosVista['aportes_revalorizaciones']);
$condicion2 = empty($monetariosVista['plan_futuro']);
$condicion3 = empty($monetariosVista['aportes_sociales_2']);

echo "<p>aportes_revalorizaciones está vacío: " . ($condicion1 ? "SÍ" : "NO") . " (valor: " . $monetariosVista['aportes_revalorizaciones'] . ")</p>";
echo "<p>plan_futuro está vacío: " . ($condicion2 ? "SÍ" : "NO") . " (valor: " . $monetariosVista['plan_futuro'] . ")</p>";
echo "<p>aportes_sociales_2 está vacío: " . ($condicion3 ? "SÍ" : "NO") . " (valor: " . $monetariosVista['aportes_sociales_2'] . ")</p>";

$usarFallback = $condicion1 && $condicion2 && $condicion3;
echo "<p><strong>¿Se usará el fallback?</strong> " . ($usarFallback ? "SÍ" : "NO") . "</p>";

// 3. Probar getBalancePruebaMonetarios
echo "<h3>3. getBalancePruebaMonetarios():</h3>";
$monetariosFallback = $detalleModel->getBalancePruebaMonetarios($cedula);
echo "<pre>";
print_r($monetariosFallback);
echo "</pre>";

// 4. Simular la lógica de la página
echo "<h3>4. Simulación de la lógica de la página:</h3>";
$bp = $monetariosVista;
if (empty($bp['aportes_revalorizaciones']) && empty($bp['plan_futuro']) && empty($bp['aportes_sociales_2'])) {
    echo "<p style='color: orange;'>⚠️ Usando fallback</p>";
    $bp = $monetariosFallback;
} else {
    echo "<p style='color: green;'>✅ Usando datos de la vista</p>";
}

echo "<h4>Valores finales que se mostrarían:</h4>";
echo "<pre>";
print_r($bp);
echo "</pre>";

// 5. Verificar valores específicos que se muestran en la página
echo "<h3>5. Valores específicos de la página:</h3>";
echo "<p><strong>Aportes Totales:</strong> $" . number_format((float)($bp['aportes_totales'] ?? 0), 0) . "</p>";
echo "<p><strong>Aportes Incentivos:</strong> $" . number_format((float)($bp['aportes_incentivos'] ?? 0), 0) . "</p>";
echo "<p><strong>Revalorizaciones:</strong> $" . number_format((float)($bp['aportes_revalorizaciones'] ?? 0), 0) . "</p>";
echo "<p><strong>Plan Futuro:</strong> $" . number_format((float)($bp['plan_futuro'] ?? 0), 0) . "</p>";
echo "<p><strong>Bolsillos:</strong> $" . number_format((float)($bp['bolsillos'] ?? 0), 0) . "</p>";
echo "<p><strong>Bolsillos Incentivos:</strong> $" . number_format((float)($bp['bolsillos_incentivos'] ?? 0), 0) . "</p>";
echo "<p><strong>Comisiones:</strong> $" . number_format((float)($bp['comisiones'] ?? 0), 0) . "</p>";

// 6. Verificar si hay datos en sifone_balance_prueba
echo "<h3>6. Verificación directa en sifone_balance_prueba:</h3>";
$sql = "SELECT nombre, SUM(ABS(COALESCE(salant,0))) AS valor
        FROM sifone_balance_prueba
        WHERE cedula = ? 
        AND nombre IN ('aportes ordinarios', 'Revalorizacion Aportes', 'PLAN FUTURO', 'APORTES SOCIALES 2')
        GROUP BY nombre";
$stmt = $detalleModel->conn->prepare($sql);
$stmt->execute([$cedula]);
$balanceData = $stmt->fetchAll();

if ($balanceData) {
    echo "<p style='color: green;'>✅ Datos encontrados en sifone_balance_prueba</p>";
    echo "<pre>";
    print_r($balanceData);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No hay datos en sifone_balance_prueba</p>";
}

echo "<hr>";
echo "<p><strong>URL para probar:</strong> debug_monetarios_detallado.php?cedula=$cedula</p>";
?>
