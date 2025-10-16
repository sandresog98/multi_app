<?php
/**
 * Ejemplo de uso de FileUploadManager
 * 
 * Este archivo muestra cómo implementar la nueva clase FileUploadManager
 * en diferentes módulos del sistema para prevenir conflictos de nombres de archivos.
 */

require_once __DIR__ . '/utils/FileUploadManager.php';

// Ejemplo 1: Subida de archivo simple
function ejemploSubidaSimple() {
    try {
        $options = [
            'maxSize' => 5 * 1024 * 1024, // 5MB
            'allowedExtensions' => ['pdf', 'jpg', 'png'],
            'prefix' => 'documento',
            'userId' => '123',
            'webPath' => 'https://mi-servidor.com/uploads'
        ];
        
        $baseDir = __DIR__ . '/uploads/documentos';
        $result = FileUploadManager::saveUploadedFile($_FILES['archivo'], $baseDir, $options);
        
        echo "Archivo guardado: " . $result['uniqueName'] . "\n";
        echo "URL: " . $result['webUrl'] . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Ejemplo 2: Subida para módulo de créditos
function ejemploCreditos() {
    try {
        $user = ['id' => 456, 'nombre' => 'Juan Pérez'];
        
        $options = [
            'maxSize' => 5 * 1024 * 1024, // 5MB
            'allowedExtensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'prefix' => 'credito_nomina',
            'userId' => $user['id'],
            'webPath' => 'https://mi-servidor.com/ui/assets/uploads/creditos'
        ];
        
        $baseDir = __DIR__ . '/ui/assets/uploads/creditos';
        $result = FileUploadManager::saveUploadedFile($_FILES['nomina'], $baseDir, $options);
        
        // El resultado incluye:
        // - originalName: nombre original del archivo
        // - uniqueName: nombre único generado
        // - path: ruta completa del archivo
        // - webUrl: URL web para acceder al archivo
        // - size: tamaño en bytes
        // - extension: extensión del archivo
        // - directory: directorio donde se guardó
        
        return $result['webUrl'];
        
    } catch (Exception $e) {
        throw new Exception('Error guardando nómina: ' . $e->getMessage());
    }
}

// Ejemplo 3: Subida para módulo de boletería
function ejemploBoleteria() {
    try {
        $options = [
            'maxSize' => 2 * 1024 * 1024, // 2MB
            'allowedExtensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'prefix' => 'boleta',
            'userId' => '789',
            'webPath' => 'https://mi-servidor.com/ui/assets/uploads/boletas'
        ];
        
        $baseDir = __DIR__ . '/ui/assets/uploads/boletas';
        $result = FileUploadManager::saveUploadedFile($_FILES['boleta'], $baseDir, $options);
        
        return $result['webUrl'];
        
    } catch (Exception $e) {
        throw new Exception('Error guardando boleta: ' . $e->getMessage());
    }
}

// Ejemplo 4: Subida para módulo de tienda
function ejemploTienda() {
    try {
        $options = [
            'maxSize' => 2 * 1024 * 1024, // 2MB
            'allowedExtensions' => ['png', 'jpg', 'jpeg'],
            'prefix' => 'tienda_producto',
            'userId' => '101',
            'webPath' => 'https://mi-servidor.com/ui/assets/uploads/tienda'
        ];
        
        $baseDir = __DIR__ . '/ui/assets/uploads/tienda';
        $result = FileUploadManager::saveUploadedFile($_FILES['foto'], $baseDir, $options);
        
        return $result['webUrl'];
        
    } catch (Exception $e) {
        throw new Exception('Error guardando foto: ' . $e->getMessage());
    }
}

// Ejemplo 5: Limpieza de archivos antiguos
function ejemploLimpieza() {
    $directorios = [
        __DIR__ . '/ui/assets/uploads/creditos',
        __DIR__ . '/ui/assets/uploads/boletas',
        __DIR__ . '/ui/assets/uploads/tienda'
    ];
    
    $totalEliminados = 0;
    foreach ($directorios as $dir) {
        $eliminados = FileUploadManager::cleanupOldFiles($dir, 30); // Archivos de más de 30 días
        $totalEliminados += $eliminados;
        echo "Eliminados $eliminados archivos de $dir\n";
    }
    
    echo "Total de archivos eliminados: $totalEliminados\n";
}

// Ejemplo 6: Generar nombre único sin subir archivo
function ejemploGenerarNombre() {
    $nombreOriginal = 'cedula.pdf';
    $nombreUnico = FileUploadManager::generateUniqueFileName($nombreOriginal, 'credito_cedula', '123');
    
    echo "Nombre original: $nombreOriginal\n";
    echo "Nombre único: $nombreUnico\n";
    
    // Resultado ejemplo: credito_cedula_user123_cedula_20250115_143022_a1b2c3d4.pdf
}

// Ejemplo de nombres únicos generados:
/*
Archivo original: cedula.pdf
Nombre único: credito_cedula_user123_cedula_20250115_143022_a1b2c3d4.pdf

Archivo original: foto_producto.jpg  
Nombre único: tienda_producto_user456_foto-producto_20250115_143045_e5f6g7h8.jpg

Archivo original: boleta_evento.pdf
Nombre único: boleta_user789_boleta-evento_20250115_143102_i9j0k1l2.pdf
*/

// Ejemplo 7: Validación de tipo MIME
function ejemploValidacionMime() {
    $archivo = '/ruta/al/archivo.pdf';
    $tiposPermitidos = ['application/pdf', 'image/jpeg', 'image/png'];
    
    if (FileUploadManager::validateMimeType($archivo, $tiposPermitidos)) {
        echo "Tipo MIME válido\n";
    } else {
        echo "Tipo MIME no válido\n";
    }
}

// Ejemplo 8: Eliminar archivo específico
function ejemploEliminarArchivo() {
    $rutaArchivo = '/ruta/al/archivo.pdf';
    
    if (FileUploadManager::deleteFile($rutaArchivo)) {
        echo "Archivo eliminado correctamente\n";
    } else {
        echo "No se pudo eliminar el archivo\n";
    }
}

/*
BENEFICIOS DE LA NUEVA IMPLEMENTACIÓN:

1. ✅ NOMBRES ÚNICOS: Previene conflictos de nombres
   - Formato: prefijo_usuario_nombre-original_timestamp_idúnico.extensión
   - Ejemplo: credito_cedula_user123_cedula_20250115_143022_a1b2c3d4.pdf

2. ✅ ORGANIZACIÓN AUTOMÁTICA: Subdirectorios por año/mes
   - Estructura: uploads/modulo/YYYY/MM/
   - Ejemplo: uploads/creditos/2025/01/

3. ✅ VALIDACIÓN COMPLETA: Múltiples capas de seguridad
   - Validación de tamaño
   - Validación de extensión
   - Validación de tipo MIME
   - Verificación de archivo temporal

4. ✅ MANEJO DE ERRORES: Logging y mensajes claros
   - Logs detallados para debugging
   - Mensajes de error específicos
   - Rollback automático en caso de error

5. ✅ LIMPIEZA AUTOMÁTICA: Eliminación de archivos antiguos
   - Función para limpiar archivos por antigüedad
   - Prevención de acumulación de archivos

6. ✅ CONFIGURACIÓN FLEXIBLE: Opciones personalizables
   - Tamaño máximo configurable
   - Extensiones permitidas configurables
   - Prefijos personalizables por módulo

7. ✅ COMPATIBILIDAD: Funciona con código existente
   - Mantiene la misma interfaz
   - Mejora la funcionalidad sin romper cambios
*/
