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
        <?php if (!empty($currentUser) && $role === 'admin'): ?>
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

        <?php $isBeneficiosOpen = in_array(($currentPage ?? ''), ['beneficios','beneficios_item1','beneficios_item2']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuBeneficios" role="button" aria-expanded="<?php echo $isBeneficiosOpen ? 'true' : 'false'; ?>" aria-controls="menuBeneficios">
            <span><i class="fas fa-gift me-2"></i>Beneficios</span>
            <i class="fas fa-chevron-<?php echo $isBeneficiosOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isBeneficiosOpen ? 'show' : ''; ?> ms-3" id="menuBeneficios">
            <a class="nav-link <?php echo $currentPage === 'beneficios' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'beneficios_item1' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle small me-2"></i>Item 1
            </a>
            <a class="nav-link <?php echo $currentPage === 'beneficios_item2' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle small me-2"></i>Item 2
            </a>
        </div>

        <?php $isFauOpen = in_array(($currentPage ?? ''), ['fau','fau_item1']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuFau" role="button" aria-expanded="<?php echo $isFauOpen ? 'true' : 'false'; ?>" aria-controls="menuFau">
            <span><i class="fas fa-users me-2"></i>FAU</span>
            <i class="fas fa-chevron-<?php echo $isFauOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isFauOpen ? 'show' : ''; ?> ms-3" id="menuFau">
            <a class="nav-link <?php echo $currentPage === 'fau' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'fau_item1' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle small me-2"></i>Item 1
            </a>
        </div>

        <?php $isTiendaOpen = in_array(($currentPage ?? ''), ['tienda','tienda_item1']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuTienda" role="button" aria-expanded="<?php echo $isTiendaOpen ? 'true' : 'false'; ?>" aria-controls="menuTienda">
            <span><i class="fas fa-store me-2"></i>Tienda</span>
            <i class="fas fa-chevron-<?php echo $isTiendaOpen ? 'up' : 'down'; ?>"></i>
        </a>
        <div class="collapse <?php echo $isTiendaOpen ? 'show' : ''; ?> ms-3" id="menuTienda">
            <a class="nav-link <?php echo $currentPage === 'tienda' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_item1' ? 'active' : ''; ?>" href="#">
                <i class="fas fa-circle small me-2"></i>Item 1
            </a>
        </div>
        <a class="nav-link <?php echo $currentPage === 'usuarios' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/usuarios/pages/usuarios.php">
            <i class="fas fa-user-cog me-2"></i>Usuarios
        </a>
        <a class="nav-link <?php echo $currentPage === 'logs' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/logs/pages/logs.php">
            <i class="fas fa-clipboard-list me-2"></i>Logs
        </a>
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
