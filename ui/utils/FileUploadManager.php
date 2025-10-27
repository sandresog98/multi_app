<?php
/**
 * Clase utilitaria para manejo seguro de subidas de archivos
 * Previene conflictos de nombres y proporciona nombres únicos
 */

class FileUploadManager {
    
    /**
     * Genera un nombre único para un archivo basado en timestamp y datos únicos
     * 
     * @param string $originalName Nombre original del archivo
     * @param string $prefix Prefijo opcional para el archivo
     * @param string $userId ID del usuario que sube el archivo (opcional)
     * @return string Nombre único generado
     */
    public static function generateUniqueFileName($originalName, $prefix = '', $userId = '') {
        // Obtener información del archivo original
        $pathInfo = pathinfo(strtolower($originalName));
        $baseName = $pathInfo['filename'] ?? 'archivo';
        $extension = $pathInfo['extension'] ?? '';
        
        // Limpiar el nombre base (solo letras, números, guiones y guiones bajos)
        $cleanBase = preg_replace('/[^a-z0-9_-]/', '-', $baseName);
        $cleanBase = preg_replace('/-+/', '-', $cleanBase); // Reemplazar múltiples guiones
        $cleanBase = trim($cleanBase, '-'); // Quitar guiones al inicio y final
        if (empty($cleanBase)) {
            $cleanBase = 'archivo';
        }
        
        // Crear prefijo único
        $uniquePrefix = '';
        if (!empty($prefix)) {
            $uniquePrefix = $prefix . '_';
        }
        if (!empty($userId)) {
            $uniquePrefix .= 'user' . $userId . '_';
        }
        
        // Generar timestamp y identificador único
        $timestamp = date('Ymd_His');
        $uniqueId = substr(uniqid('', true), -8); // Últimos 8 caracteres del uniqid
        
        // Construir nombre final
        $fileName = $uniquePrefix . $cleanBase . '_' . $timestamp . '_' . $uniqueId;
        
        // Agregar extensión si existe
        if (!empty($extension)) {
            $fileName .= '.' . $extension;
        }
        
        return $fileName;
    }
    
    /**
     * Valida y guarda un archivo subido con nombre único
     * 
     * @param array $file Array $_FILES del archivo
     * @param string $destinationDir Directorio de destino
     * @param array $options Opciones de validación
     * @return array Información del archivo guardado
     */
    public static function saveUploadedFile($file, $destinationDir, $options = []) {
        // Configuración por defecto
        $defaults = [
            'maxSize' => 5 * 1024 * 1024, // 5MB por defecto
            'allowedExtensions' => ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx'],
            'prefix' => '',
            'userId' => '',
            'createSubdirs' => true, // Crear subdirectorios por año/mes
            'webPath' => '' // Ruta web relativa para URLs
        ];
        
        $config = array_merge($defaults, $options);
        
        // Validar archivo
        if (!isset($file) || !is_array($file)) {
            throw new Exception('Archivo no válido');
        }
        
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida del archivo (código: ' . ($file['error'] ?? 'desconocido') . ')');
        }
        
        $originalName = $file['name'] ?? '';
        $tmpName = $file['tmp_name'] ?? '';
        $size = (int)($file['size'] ?? 0);
        
        if (empty($originalName)) {
            throw new Exception('Nombre de archivo vacío');
        }
        
        if ($size <= 0) {
            throw new Exception('Archivo vacío');
        }
        
        if ($size > $config['maxSize']) {
            throw new Exception('Archivo demasiado grande. Máximo: ' . self::formatBytes($config['maxSize']));
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $config['allowedExtensions'], true)) {
            throw new Exception('Extensión no permitida. Permitidas: ' . implode(', ', $config['allowedExtensions']));
        }
        
        // Preparar directorio de destino
        $finalDir = $destinationDir;
        if ($config['createSubdirs']) {
            $year = date('Y');
            $month = date('m');
            $finalDir = rtrim($destinationDir, '/') . '/' . $year . '/' . $month;
        }
        
        // Crear directorio si no existe
        if (!is_dir($finalDir)) {
            if (!@mkdir($finalDir, 0775, true)) {
                throw new Exception('No se pudo crear el directorio: ' . $finalDir);
            }
        }
        
        // Asegurar que todos los directorios en la ruta tengan permisos correctos
        $pathParts = explode('/', $finalDir);
        $currentPath = '';
        foreach ($pathParts as $part) {
            if (empty($part)) continue;
            $currentPath .= '/' . $part;
            if (is_dir($currentPath)) {
                // Intentar establecer permisos apropiados
                @chmod($currentPath, 0775);
            }
        }
        
        // Verificar permisos de escritura en el directorio final
        if (!is_writable($finalDir)) {
            @chmod($finalDir, 0777);
            if (!is_writable($finalDir)) {
                throw new Exception('Directorio no escribible: ' . $finalDir . '. Por favor, asegúrate de que el directorio tenga permisos 775 o 777.');
            }
        }
        
        // Generar nombre único
        $uniqueFileName = self::generateUniqueFileName($originalName, $config['prefix'], $config['userId']);
        $finalPath = rtrim($finalDir, '/') . '/' . $uniqueFileName;
        
        // Verificar que el archivo temporal es válido
        if (!is_uploaded_file($tmpName)) {
            throw new Exception('Archivo temporal no válido');
        }
        
        // Mover archivo
        if (!move_uploaded_file($tmpName, $finalPath)) {
            throw new Exception('No se pudo guardar el archivo en: ' . $finalPath);
        }
        
        // Verificar que el archivo se guardó correctamente
        if (!file_exists($finalPath)) {
            throw new Exception('Archivo no accesible después de guardar');
        }
        
        // Generar URL web si se proporciona webPath
        $webUrl = '';
        if (!empty($config['webPath'])) {
            // Calcular la ruta relativa desde el destino base
            $relativePath = str_replace($destinationDir, '', $finalPath);
            
            // Si createSubdirs está activado, construir la URL con los subdirectorios
            if ($config['createSubdirs']) {
                $year = date('Y');
                $month = date('m');
                $webUrl = rtrim($config['webPath'], '/') . '/' . $year . '/' . $month . '/' . $uniqueFileName;
            } else {
                $webUrl = rtrim($config['webPath'], '/') . '/' . ltrim($relativePath, '/');
            }
        }
        
        return [
            'originalName' => $originalName,
            'uniqueName' => $uniqueFileName,
            'path' => $finalPath,
            'webUrl' => $webUrl,
            'size' => $size,
            'extension' => $extension,
            'directory' => $finalDir
        ];
    }
    
    /**
     * Elimina un archivo de forma segura
     * 
     * @param string $filePath Ruta del archivo a eliminar
     * @return bool True si se eliminó correctamente
     */
    public static function deleteFile($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return @unlink($filePath);
        }
        return false;
    }
    
    /**
     * Elimina archivos antiguos de un directorio
     * 
     * @param string $directory Directorio a limpiar
     * @param int $daysOld Días de antigüedad para eliminar
     * @return int Número de archivos eliminados
     */
    public static function cleanupOldFiles($directory, $daysOld = 30) {
        $deleted = 0;
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        if (is_dir($directory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                    if (@unlink($file->getRealPath())) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Formatea bytes en formato legible
     * 
     * @param int $bytes Número de bytes
     * @return string Bytes formateados
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Valida el tipo MIME de un archivo
     * 
     * @param string $filePath Ruta del archivo
     * @param array $allowedMimes Tipos MIME permitidos
     * @return bool True si el tipo es válido
     */
    public static function validateMimeType($filePath, $allowedMimes = []) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        if (empty($allowedMimes)) {
            return true; // Sin restricciones
        }
        
        return in_array($mimeType, $allowedMimes, true);
    }
}
