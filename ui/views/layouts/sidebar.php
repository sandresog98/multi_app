<?php 
// Sidebar minimal para v2
?>
<!-- Sidebar -->
<div class="sidebar p-3">
    <div class="text-center mb-4">
        <a href="<?php echo getBaseUrl(); ?>pages/dashboard.php" class="d-inline-flex align-items-center text-decoration-none text-white">
            <img src="<?php echo getBaseUrl(); ?>assets/img/logo.png" alt="Multi" height="100" class="me-2">
        </a>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>pages/dashboard.php">
            <i class="fas fa-home me-2"></i>Inicio
        </a>
        <?php $role = $currentUser['rol'] ?? ''; ?>
        <?php
        // Helper inline para chequear permisos por módulo en el sidebar
        if (!function_exists('canAccess')) {
            require_once dirname(__DIR__, 2) . '/controllers/AuthController.php';
            $___authTmp = new AuthController();
            function canAccess($moduleKey) {
                global $___authTmp;
                return $___authTmp->canAccessModule($moduleKey);
            }
        }
        ?>
        <?php if (!empty($currentUser)): ?>
        <?php $isOficinaOpen = in_array(($currentPage ?? ''), ['oficina','productos','asociados','pagos_pse','pagos_cash_qr','transacciones','trx_list','cargas']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuOficina" role="button" aria-expanded="<?php echo $isOficinaOpen ? 'true' : 'false'; ?>" aria-controls="menuOficina">
            <span><i class="fas fa-building me-2"></i>Oficina</span>
            <i class="fas fa-chevron-<?php echo $isOficinaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isOficinaOpen ? 'show' : ''; ?> ms-3" id="menuOficina">
            <?php if (canAccess('oficina.resumen')): ?>
            <a class="nav-link <?php echo $currentPage === 'oficina' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.productos')): ?>
            <a class="nav-link <?php echo $currentPage === 'productos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/productos.php">
                <i class="fas fa-box small me-2"></i>Productos
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.cargas')): ?>
            <a class="nav-link <?php echo $currentPage === 'cargas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/cargas.php">
                <i class="fas fa-file-upload small me-2"></i>Cargas
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.asociados')): ?>
            <a class="nav-link <?php echo $currentPage === 'asociados' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados.php">
                <i class="fas fa-users small me-2"></i>Asociados
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.pagos_pse')): ?>
            <a class="nav-link <?php echo $currentPage === 'pagos_pse' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/pagos_pse.php">
                <i class="fas fa-money-check-alt small me-2"></i>Pagos PSE
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.pagos_cash_qr')): ?>
            <a class="nav-link <?php echo $currentPage === 'pagos_cash_qr' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/pagos_cash_qr.php">
                <i class="fas fa-receipt small me-2"></i>Pagos Cash/QR
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.trx_list')): ?>
            <a class="nav-link <?php echo $currentPage === 'trx_list' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/trx_list.php">
                <i class="fas fa-list-ul small me-2"></i>Trx List
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.transacciones')): ?>
            <a class="nav-link <?php echo $currentPage === 'transacciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/transacciones.php">
                <i class="fas fa-exchange-alt small me-2"></i>Transacciones
            </a>
            <?php endif; ?>
        </div>

        <?php $isBoleteriaOpen = in_array(($currentPage ?? ''), ['boleteria','boleteria_categorias','boleteria_boletas']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuBoleteria" role="button" aria-expanded="<?php echo $isBoleteriaOpen ? 'true' : 'false'; ?>" aria-controls="menuBoleteria">
            <span><i class="fas fa-ticket-alt me-2"></i>Boletería</span>
            <i class="fas fa-chevron-<?php echo $isBoleteriaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isBoleteriaOpen ? 'show' : ''; ?> ms-3" id="menuBoleteria">
            <?php if (canAccess('boleteria.resumen')): ?>
            <a class="nav-link <?php echo $currentPage === 'boleteria' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <?php endif; ?>
            <?php if (canAccess('boleteria.categorias')): ?>
            <a class="nav-link <?php echo $currentPage === 'boleteria_categorias' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/categorias.php">
                <i class="fas fa-tags small me-2"></i>Categorías
            </a>
            <?php endif; ?>
            <?php if (canAccess('boleteria.boletas')): ?>
            <a class="nav-link <?php echo $currentPage === 'boleteria_boletas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/boletas.php">
                <i class="fas fa-ticket-alt small me-2"></i>Boletas
            </a>
            <?php endif; ?>
        </div>

        <?php $isCredOpen = in_array(($currentPage ?? ''), ['creditos','creditos_solicitudes','creditos_listado']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCreditos" role="button" aria-expanded="<?php echo $isCredOpen ? 'true' : 'false'; ?>" aria-controls="menuCreditos">
            <span><i class="fas fa-hand-holding-usd me-2"></i>Gestión Créditos</span>
            <i class="fas fa-chevron-<?php echo $isCredOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isCredOpen ? 'show' : ''; ?> ms-3" id="menuCreditos">
            <a class="nav-link <?php echo $currentPage === 'creditos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'creditos_solicitudes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos/pages/solicitudes.php">
                <i class="fas fa-file-signature small me-2"></i>Solicitudes
            </a>
            <a class="nav-link <?php echo $currentPage === 'creditos_listado' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos/pages/listado.php">
                <i class="fas fa-list small me-2"></i>Listado
            </a>
        </div>

        <?php $isCobranzaOpen = in_array(($currentPage ?? ''), ['cobranza','cobranza_comunicaciones']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCobranza" role="button" aria-expanded="<?php echo $isCobranzaOpen ? 'true' : 'false'; ?>" aria-controls="menuCobranza">
            <span><i class="fas fa-phone me-2"></i>Cobranza</span>
            <i class="fas fa-chevron-<?php echo $isCobranzaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isCobranzaOpen ? 'show' : ''; ?> ms-3" id="menuCobranza">
            <?php if (canAccess('cobranza.resumen')): ?>
            <a class="nav-link <?php echo $currentPage === 'cobranza' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <?php endif; ?>
            <?php if (canAccess('cobranza.comunicaciones')): ?>
            <a class="nav-link <?php echo $currentPage === 'cobranza_comunicaciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comunicaciones.php">
                <i class="fas fa-comments small me-2"></i>Comunicaciones
            </a>
            <?php endif; ?>
        </div>

        
        <?php if (canAccess('usuarios.gestion')): ?>
        <a class="nav-link <?php echo $currentPage === 'usuarios' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/usuarios/pages/usuarios.php">
            <i class="fas fa-user-cog me-2"></i>Usuarios
        </a>
        <?php endif; ?>
        <?php if (canAccess('logs.gestion')): ?>
        <a class="nav-link <?php echo $currentPage === 'logs' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/logs/pages/logs.php">
            <i class="fas fa-clipboard-list me-2"></i>Logs
        </a>
        <?php endif; ?>
        <?php elseif (!empty($currentUser) && $role === 'oficina'): ?>
        <?php $isOficinaOpen = in_array(($currentPage ?? ''), ['oficina','productos','asociados','pagos_pse','pagos_cash_qr','transacciones','trx_list','cargas']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuOficina" role="button" aria-expanded="<?php echo $isOficinaOpen ? 'true' : 'false'; ?>" aria-controls="menuOficina">
            <span><i class="fas fa-building me-2"></i>Oficina</span>
            <i class="fas fa-chevron-<?php echo $isOficinaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isOficinaOpen ? 'show' : ''; ?> ms-3" id="menuOficina">
            <a class="nav-link <?php echo $currentPage === 'oficina' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'productos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/productos.php">
                <i class="fas fa-box small me-2"></i>Productos
            </a>
            <a class="nav-link <?php echo $currentPage === 'cargas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/cargas.php">
                <i class="fas fa-file-upload small me-2"></i>Cargas
            </a>
            <a class="nav-link <?php echo $currentPage === 'asociados' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados.php">
                <i class="fas fa-users small me-2"></i>Asociados
            </a>
            <a class="nav-link <?php echo $currentPage === 'pagos_pse' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/pagos_pse.php">
                <i class="fas fa-money-check-alt small me-2"></i>Pagos PSE
            </a>
            <a class="nav-link <?php echo $currentPage === 'pagos_cash_qr' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/pagos_cash_qr.php">
                <i class="fas fa-receipt small me-2"></i>Pagos Cash/QR
            </a>
            <a class="nav-link <?php echo $currentPage === 'trx_list' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/trx_list.php">
                <i class="fas fa-list-ul small me-2"></i>Trx List
            </a>
            <a class="nav-link <?php echo $currentPage === 'transacciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/transacciones.php">
                <i class="fas fa-exchange-alt small me-2"></i>Transacciones
            </a>
        </div>

        <?php $isBoleteriaOpen = in_array(($currentPage ?? ''), ['boleteria','boleteria_categorias','boleteria_boletas']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuBoleteria" role="button" aria-expanded="<?php echo $isBoleteriaOpen ? 'true' : 'false'; ?>" aria-controls="menuBoleteria">
            <span><i class="fas fa-ticket-alt me-2"></i>Boletería</span>
            <i class="fas fa-chevron-<?php echo $isBoleteriaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isBoleteriaOpen ? 'show' : ''; ?> ms-3" id="menuBoleteria">
            <a class="nav-link <?php echo $currentPage === 'boleteria' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'boleteria_categorias' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/categorias.php">
                <i class="fas fa-tags small me-2"></i>Categorías
            </a>
            <a class="nav-link <?php echo $currentPage === 'boleteria_boletas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/boletas.php">
                <i class="fas fa-ticket-alt small me-2"></i>Boletas
            </a>
        </div>

        <?php $isCredOpen = in_array(($currentPage ?? ''), ['creditos','creditos_solicitudes','creditos_listado']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCreditos" role="button" aria-expanded="<?php echo $isCredOpen ? 'true' : 'false'; ?>" aria-controls="menuCreditos">
            <span><i class="fas fa-hand-holding-usd me-2"></i>Gestión Créditos</span>
            <i class="fas fa-chevron-<?php echo $isCredOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isCredOpen ? 'show' : ''; ?> ms-3" id="menuCreditos">
            <a class="nav-link <?php echo $currentPage === 'creditos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'creditos_solicitudes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos/pages/solicitudes.php">
                <i class="fas fa-file-signature small me-2"></i>Solicitudes
            </a>
            <a class="nav-link <?php echo $currentPage === 'creditos_listado' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos/pages/listado.php">
                <i class="fas fa-list small me-2"></i>Listado
            </a>
        </div>

        <?php $isCobranzaOpen = in_array(($currentPage ?? ''), ['cobranza','cobranza_comunicaciones']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCobranza" role="button" aria-expanded="<?php echo $isCobranzaOpen ? 'true' : 'false'; ?>" aria-controls="menuCobranza">
            <span><i class="fas fa-phone me-2"></i>Cobranza</span>
            <i class="fas fa-chevron-<?php echo $isCobranzaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isCobranzaOpen ? 'show' : ''; ?> ms-3" id="menuCobranza">
            <a class="nav-link <?php echo $currentPage === 'cobranza' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'cobranza_comunicaciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comunicaciones.php">
                <i class="fas fa-comments small me-2"></i>Comunicaciones
            </a>
        </div>
        <?php endif; ?>
    </nav>
    
    <hr class="my-4">
    
    <div class="text-center">
        <small>Bienvenido,</small><br>
        <strong><?php echo htmlspecialchars($currentUser['nombre_completo'] ?? 'Usuario'); ?></strong>
    </div>
    
    <div class="mt-3">
        <a href="<?php echo getBaseUrl(); ?>logout.php" class="btn btn-outline-light btn-sm w-100">
            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
        </a>
    </div>
</div>
