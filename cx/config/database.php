<?php
/**
 * Reutiliza la configuración de base de datos de UI para CX
 */
require_once __DIR__ . '/../../ui/config/database.php';

// Alias explícitos para CX, por claridad en includes
function cx_getConnection() {
    return getConnection();
}
?>


