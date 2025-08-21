<?php
/**
 * Página inicial simple (similar al dashboard pero minimal)
 */

require_once '../controllers/AuthController.php';

$authController = new AuthController();
$authController->requireAuth();
$currentUser = $authController->getCurrentUser();

$pageTitle = "Inicio - Multi";
$currentPage = 'dashboard';
include '../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../views/layouts/sidebar.php'; ?>

        <main class="col-12 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-home me-2"></i>Bienvenido a Multi
                </h1>
                <div class="text-end">
                    <small class="text-muted">Última actualización: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-hand-wave fa-4x text-primary mb-4"></i>
                            <h2 class="text-primary mb-3">¡Hola, <?php echo htmlspecialchars($currentUser['nombre_completo'] ?? 'Usuario'); ?>!</h2>
                            <p class="lead text-muted mb-0">Has iniciado sesión correctamente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
</div>

<?php include '../views/layouts/footer.php'; ?>

