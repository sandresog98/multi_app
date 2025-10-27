<?php
/**
 * Script temporal para corregir permisos de directorios
 * Ejecutar desde: http://servidor/multi_app/ui/fix_permissions.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Corrección de Permisos de Directorios</h2><pre>";

$directorios = [
    'ui/assets/uploads/creditos_docs',
    'ui/uploads/recibos',
    'ui/uploads/clausulas',
    'ui/uploads/creditos',
    'ui/uploads/creditos_docs',
    'ui/uploads/cx_publicidad',
];

foreach ($directorios as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    echo "\n=== $dir ===\n";
    echo "Ruta completa: $fullPath\n";
    
    if (!is_dir($fullPath)) {
        echo "Creando directorio...\n";
        if (@mkdir($fullPath, 0775, true)) {
            echo "✓ Directorio creado\n";
        } else {
            echo "✗ Error al crear directorio\n";
        }
    } else {
        echo "✓ Directorio existe\n";
    }
    
    if (is_dir($fullPath)) {
        echo "Permisos actuales: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
        echo "Propietario: " . fileowner($fullPath) . "\n";
        echo "Grupo: " . filegroup($fullPath) . "\n";
        
        // Intentar cambiar permisos
        if (@chmod($fullPath, 0775)) {
            echo "✓ Permisos cambiados a 0775\n";
        } elseif (@chmod($fullPath, 0777)) {
            echo "✓ Permisos cambiados a 0777\n";
        } else {
            echo "✗ No se pudieron cambiar permisos\n";
        }
        
        // Verificar si es escribible ahora
        if (is_writable($fullPath)) {
            echo "✓ Directorio es escribible\n";
        } else {
            echo "✗ Directorio NO es escribible (chmod manualmente con: chmod -R 775 $dir)\n";
        }
    }
}

// Verificar también subdirectorios año/mes
$year = date('Y');
$month = date('m');

foreach ($directorios as $dir) {
    $fullPath = __DIR__ . '/' . $dir . '/' . $year . '/' . $month;
    
    if (!is_dir($fullPath)) {
        echo "\nCreando subdirectorio: $dir/$year/$month\n";
        if (@mkdir($fullPath, 0775, true)) {
            echo "✓ Subdirectorio creado\n";
            @chmod($fullPath, 0775);
        } else {
            echo "✗ Error al crear subdirectorio\n";
        }
    } else {
        echo "✓ Subdirectorio $dir/$year/$month existe\n";
        @chmod($fullPath, 0775);
    }
}

echo "\n</pre>";
echo "<h3>Instrucciones:</h3>";
echo "<p>Si el directorio aún no es escribible, ejecuta en el servidor:</p>";
echo "<pre>chmod -R 775 ui/assets/uploads/creditos_docs\n";
echo "chmod -R 775 ui/uploads/recibos\n";
echo "chmod -R 775 ui/uploads/clausulas\n";
echo "chmod -R 775 ui/uploads/creditos\n";
echo "chmod -R 775 ui/uploads/creditos_docs\n";
echo "chmod -R 775 ui/uploads/cx_publicidad</pre>";

