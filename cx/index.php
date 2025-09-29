<?php
// Redirige según estado de sesión a la página inicial o al login
session_name('multiapptwo_cx');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$isLogged = !empty($_SESSION['cx_cedula']);
header('Location: ' . ($isLogged ? 'pages/index.php' : 'login.php'));
exit;
?>


