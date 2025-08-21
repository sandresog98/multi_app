<?php
/**
 * Logout usando estructura MVC
 */

require_once __DIR__ . '/controllers/AuthController.php';

$authController = new AuthController();
$result = $authController->logout();

// Redirigir al login
header("Location: " . $result['redirect']);
exit();
?> 