<?php
require_once '../models/DetalleAsociado.php';

$cedula = $_GET['cedula'] ?? '';
if (!$cedula) {
    echo "Error: No se proporcionó cédula";
    exit;
}

$detalleModel = new DetalleAsociado();

echo "<h2>Debug Información Monetaria - Cédula: $cedula</h2>";

// 1. Verificar si existe en la vista
echo "<h3>1. Verificación en sifone_resumen_asociados_vw:</h3>";
$sql = "SELECT * FROM sifone_resumen_asociados_vw WHERE cedula = ?";
$stmt = $detalleModel->conn->prepare($sql);
$stmt->execute([$cedula]);
$vistaData = $stmt->fetch();

if ($vistaData) {
    echo "<p style='color: green;'>✅ Usuario encontrado en la vista</p>";
    echo "<pre>";
    print_r($vistaData);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Usuario NO encontrado en la vista</p>";
}

// 2. Verificar método getMonetariosDesdeVista
echo "<h3>2. Resultado de getMonetariosDesdeVista():</h3>";
$monetarios = $detalleModel->getMonetariosDesdeVista($cedula);
echo "<pre>";
print_r($monetarios);
echo "</pre>";

// 3. Verificar si todos los valores son 0
$todosCeros = true;
foreach ($monetarios as $key => $value) {
    if ($value != 0) {
        $todosCeros = false;
        break;
    }
}

if ($todosCeros) {
    echo "<p style='color: orange;'>⚠️ Todos los valores monetarios son 0</p>";
} else {
    echo "<p style='color: green;'>✅ Hay valores monetarios diferentes de 0</p>";
}

// 4. Verificar fallback
echo "<h3>3. Verificación del fallback (getBalancePruebaMonetarios):</h3>";
$fallback = $detalleModel->getBalancePruebaMonetarios($cedula);
echo "<pre>";
print_r($fallback);
echo "</pre>";

// 5. Verificar si existe en sifone_asociados
echo "<h3>4. Verificación en sifone_asociados:</h3>";
$sqlAsociado = "SELECT cedula, nombre, aporte FROM sifone_asociados WHERE cedula = ?";
$stmtAsociado = $detalleModel->conn->prepare($sqlAsociado);
$stmtAsociado->execute([$cedula]);
$asociadoData = $stmtAsociado->fetch();

if ($asociadoData) {
    echo "<p style='color: green;'>✅ Usuario encontrado en sifone_asociados</p>";
    echo "<pre>";
    print_r($asociadoData);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Usuario NO encontrado en sifone_asociados</p>";
}

// 6. Verificar las vistas que componen sifone_resumen_asociados_vw
echo "<h3>5. Verificación de vistas componentes:</h3>";

// Verificar sifone_comisiones_vw
$sqlComisiones = "SELECT * FROM sifone_comisiones_vw WHERE cedula = ?";
$stmtComisiones = $detalleModel->conn->prepare($sqlComisiones);
$stmtComisiones->execute([$cedula]);
$comisionesData = $stmtComisiones->fetch();

echo "<h4>Comisiones:</h4>";
if ($comisionesData) {
    echo "<p style='color: green;'>✅ Datos de comisiones encontrados</p>";
    echo "<pre>";
    print_r($comisionesData);
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ No hay datos de comisiones</p>";
}

// Verificar sifone_bolsillos_vw
$sqlBolsillos = "SELECT * FROM sifone_bolsillos_vw WHERE cedula = ?";
$stmtBolsillos = $detalleModel->conn->prepare($sqlBolsillos);
$stmtBolsillos->execute([$cedula]);
$bolsillosData = $stmtBolsillos->fetch();

echo "<h4>Bolsillos:</h4>";
if ($bolsillosData) {
    echo "<p style='color: green;'>✅ Datos de bolsillos encontrados</p>";
    echo "<pre>";
    print_r($bolsillosData);
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ No hay datos de bolsillos</p>";
}

echo "<hr>";
echo "<p><strong>URL para probar:</strong> debug_monetarios.php?cedula=$cedula</p>";
?>
