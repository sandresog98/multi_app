<?php
// Asegurar disponibilidad de getBaseUrl() para recursos estáticos
require_once dirname(__DIR__, 2) . '/config/paths.php';

# AZUL CLARO
$color_from = 'rgb(102, 157, 234) 0%';
$color_to = 'rgb(57, 108, 127) 100%';
# ROJO CLARO
#$color_from = 'rgb(235, 47, 46) 0%';
#$color_to = 'rgb(170, 78, 78) 100%';
# AZUL OSCURO
#$color_from = 'rgb(86, 126, 184) 0%';
#$color_to = 'rgb(52, 106, 122) 100%';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Multi - Sistema de Gestión'; ?></title>
    <link rel="icon" href="<?php echo getBaseUrl(); ?>assets/favicons/favicon.ico" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .sidebar {
            background: linear-gradient(135deg,<?php echo $color_from; ?>,<?php echo $color_to; ?>);
            min-height: 100vh;
            height: 100vh;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            width: 240px; /* Ancho fijo más controlado */
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
            margin-left: 240px; /* Compensar el sidebar fijo */
            width: calc(100% - 240px); /* Asegurar que no se corte */
            box-sizing: border-box;
        }
        
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .row {
            margin-left: 0;
            margin-right: 0;
        }
        

        
        @media (max-width: 767.98px) {
            .sidebar {
                position: relative;
                height: auto;
                width: 100%;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        .card-stats {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            max-width: 100%;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .table-responsive {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            max-width: 100%;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge-sm {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }
        
        .card.border-success {
            border-width: 2px !important;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            background: linear-gradient(135deg,<?php echo $color_from; ?>,<?php echo $color_to; ?>);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .btn-login {
            background: linear-gradient(135deg,<?php echo $color_from; ?>,<?php echo $color_to; ?>);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body> 