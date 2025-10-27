<?php
/**
 * Script para migrar URLs de recibos al nuevo formato
 * Ejecutar una vez desde: http://server/multi_app/ui/modules/oficina/migrate_recibos_urls.php
 */
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Migración de URLs de Recibos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Migración de URLs de Recibos</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'migrate') {
            echo "<pre>";
            
            try {
                $conn = getConnection();
                $baseUrl = getBaseUrl();
                
                // Obtener todos los registros con link_validacion
                $stmt = $conn->query("
                    SELECT confiar_id, link_validacion 
                    FROM banco_confirmacion_confiar 
                    WHERE link_validacion IS NOT NULL 
                    AND link_validacion != ''
                ");
                $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "Total de registros encontrados: " . count($registros) . "\n\n";
                
                $actualizados = 0;
                $errores = 0;
                $sinCambios = 0;
                
                foreach ($registros as $reg) {
                    $urlVieja = $reg['link_validacion'];
                    $confiarId = $reg['confiar_id'];
                    
                    // Si ya usa el nuevo formato, saltar
                    if (strpos($urlVieja, 'serve_recibo.php') !== false) {
                        $sinCambios++;
                        continue;
                    }
                    
                    // Extraer la ruta relativa de la URL vieja
                    // Formato viejo: /multi_app/ui/uploads/recibos/2025/10/archivo.jpg
                    // Formato nuevo: serve_recibo.php?f=uploads/recibos/2025/10/archivo.jpg
                    
                    if (strpos($urlVieja, '/multi_app/ui/uploads/recibos/') !== false) {
                        // Extraer: 2025/10/archivo.jpg
                        $relativePath = str_replace('/multi_app/ui/uploads/recibos/', '', $urlVieja);
                        
                        // Construir nueva URL
                        $urlNueva = $baseUrl . 'serve_recibo.php?f=uploads/recibos/' . $relativePath;
                        
                        // Verificar que el archivo existe
                        $fullPath = __DIR__ . '/../../uploads/recibos/' . $relativePath;
                        if (!file_exists($fullPath)) {
                            echo "[ERROR] Archivo no existe: $relativePath\n";
                            $errores++;
                            continue;
                        }
                        
                        // Actualizar en base de datos
                        $updateStmt = $conn->prepare("UPDATE banco_confirmacion_confiar SET link_validacion = ? WHERE confiar_id = ?");
                        if ($updateStmt->execute([$urlNueva, $confiarId])) {
                            echo "[OK] $confiarId: $urlVieja -> $urlNueva\n";
                            $actualizados++;
                        } else {
                            echo "[ERROR] No se pudo actualizar $confiarId\n";
                            $errores++;
                        }
                    } else {
                        echo "[INFO] URL no reconocida (formato desconocido): $urlVieja\n";
                        $sinCambios++;
                    }
                }
                
                echo "\n=== RESUMEN ===\n";
                echo "Registros actualizados: $actualizados\n";
                echo "Registros con errores: $errores\n";
                echo "Registros sin cambios: $sinCambios\n";
                
                if ($actualizados > 0) {
                    echo "\n<span class='success'>✓ Migración completada exitosamente</span>\n";
                }
                
            } catch (Exception $e) {
                echo "<span class='error'>ERROR: " . $e->getMessage() . "</span>\n";
                echo "Trace: " . $e->getTraceAsString() . "\n";
            }
            
            echo "</pre>";
        } else {
            ?>
            <div class="info">
                <p>Este script migrará las URLs de los recibos del formato viejo al nuevo formato.</p>
                <p><strong>Formato viejo:</strong> <code>/multi_app/ui/uploads/recibos/2025/10/archivo.jpg</code></p>
                <p><strong>Formato nuevo:</strong> <code>/multi_app/ui/serve_recibo.php?f=uploads/recibos/2025/10/archivo.jpg</code></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="migrate">
                <button type="submit" onclick="return confirm('¿Estás seguro de que deseas migrar las URLs?')">
                    Ejecutar Migración
                </button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>

