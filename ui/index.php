<?php
/**
 * Punto de entrada principal de la aplicación
 * Redirige al login si no está autenticado, o al dashboard si lo está
 */

require_once 'controllers/AuthController.php';

$authController = new AuthController();

// Si ya está autenticado, redirigir al dashboard
if ($authController->isAuthenticated()) {
    header("Location: pages/dashboard.php");
    exit();
}

// Si no está autenticado, redirigir al login
header("Location: login.php");
exit();
?> 