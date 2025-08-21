<?php
/**
 * Utilidad para obtener etiquetas amigables y descripciones desde diccionario.json
 */

require_once __DIR__ . '/../config/paths.php';

class Dictionary {
    private static $data = null;

    private static function load(): void {
        if (self::$data !== null) {
            return;
        }
        // BASE_PATH apunta a v2/ui. El diccionario está en v2/diccionario.json
        $jsonPath = getAbsolutePath('../dictionary.json');
        if (!file_exists($jsonPath)) {
            self::$data = ['tablas' => []];
            return;
        }
        $raw = file_get_contents($jsonPath);
        $parsed = json_decode($raw, true);
        self::$data = is_array($parsed) ? $parsed : ['tablas' => []];
    }

    public static function label(string $table, string $field, ?string $default = null): string {
        self::load();
        $tablas = self::$data['tablas'] ?? [];
        $tabla = $tablas[$table] ?? null;
        if (is_array($tabla) && isset($tabla[$field]['nombre'])) {
            return (string) $tabla[$field]['nombre'];
        }
        // Fallback amigable: capitalizar y reemplazar guiones bajos
        return $default ?? ucwords(str_replace('_', ' ', $field));
    }

    public static function description(string $table, string $field, ?string $default = null): string {
        self::load();
        $tablas = self::$data['tablas'] ?? [];
        $tabla = $tablas[$table] ?? null;
        if (is_array($tabla) && isset($tabla[$field]['descripcion'])) {
            return (string) $tabla[$field]['descripcion'];
        }
        return $default ?? '';
    }

    /**
     * Resolver nombre de campo real para una llave lógica entre tablas
     * @param string $keyName  Nombre lógico (p.ej. 'credito', 'persona')
     * @param string $table    Tabla destino
     * @param string|null $fallback Campo por defecto si no se encuentra
     */
    public static function keyField(string $keyName, string $table, ?string $fallback = null): ?string {
        self::load();
        $map = self::$data['llaves'][$keyName] ?? null;
        if (is_array($map) && isset($map[$table])) {
            return (string) $map[$table];
        }
        return $fallback;
    }
}

// Helpers globales convenientes
function dict_label(string $table, string $field, ?string $default = null): string {
    return Dictionary::label($table, $field, $default);
}

function dict_description(string $table, string $field, ?string $default = null): string {
    return Dictionary::description($table, $field, $default);
}

function dict_key_field(string $keyName, string $table, ?string $fallback = null): ?string {
    return Dictionary::keyField($keyName, $table, $fallback);
}

?>

