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
        <?php $isOficinaOpen = in_array(($currentPage ?? ''), ['oficina','productos','clausulas','asociados','pagos_pse','pagos_cash_qr','transacciones','trx_list','cargas','descargas','informaciones','oficina_comisiones','tasas_interes','tasas_productos']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuOficina" role="button" aria-expanded="<?php echo $isOficinaOpen ? 'true' : 'false'; ?>" aria-controls="menuOficina">
            <span><i class="fas fa-building me-2"></i>Oficina</span>
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
            <?php if (canAccess('oficina.clausulas')): ?>
            <a class="nav-link <?php echo $currentPage === 'clausulas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/clausulas.php">
                <i class="fas fa-file-contract small me-2"></i>Cláusulas
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.cargas')): ?>
            <a class="nav-link <?php echo $currentPage === 'cargas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/cargas.php">
                <i class="fas fa-file-upload small me-2"></i>Cargas
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.descargas')): ?>
            <a class="nav-link <?php echo $currentPage === 'descargas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/descargas.php">
                <i class="fas fa-download small me-2"></i>Descargas
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.asociados')): ?>
            <a class="nav-link <?php echo $currentPage === 'asociados' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/asociados.php">
                <i class="fas fa-users small me-2"></i>Asociados
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.informaciones')): ?>
            <a class="nav-link <?php echo $currentPage === 'informaciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/informaciones.php">
                <i class="fas fa-info-circle small me-2"></i>Informaciones
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
            <?php if (canAccess('oficina.comisiones')): ?>
            <a class="nav-link <?php echo $currentPage === 'oficina_comisiones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/comisiones.php">
                <i class="fas fa-percentage small me-2"></i>Comisiones
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.tasas_interes')): ?>
            <a class="nav-link <?php echo $currentPage === 'tasas_interes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/tasas_interes.php">
                <i class="fas fa-percentage small me-2"></i>Tasas de Interés
            </a>
            <?php endif; ?>
            <?php if (canAccess('oficina.tasas_productos')): ?>
            <a class="nav-link <?php echo $currentPage === 'tasas_productos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/tasas_productos.php">
                <i class="fas fa-box small me-2"></i>Tasas de Productos
            </a>
            <?php endif; ?>
        </div>

        <?php $isTicketOpen = in_array(($currentPage ?? ''), ['ticketera_resumen','ticketera_tickets','ticketera_categorias']); ?>
        <?php if (canAccess('ticketera')): ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuTicketera" role="button" aria-expanded="<?php echo $isTicketOpen ? 'true' : 'false'; ?>" aria-controls="menuTicketera">
            <span><i class="fas fa-project-diagram me-2"></i>Ticketera</span>
        </a>
        <div class="collapse <?php echo $isTicketOpen ? 'show' : ''; ?> ms-3" id="menuTicketera">
            <a class="nav-link <?php echo $currentPage === 'ticketera_resumen' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/ticketera/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'ticketera_tickets' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/ticketera/pages/tickets.php">
                <i class="fas fa-tasks small me-2"></i>Tickets
            </a>
            <a class="nav-link <?php echo $currentPage === 'ticketera_categorias' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/ticketera/pages/categorias.php">
                <i class="fas fa-tags small me-2"></i>Categorías
            </a>
        </div>
        <?php endif; ?>

        <?php $isBoleteriaOpen = in_array(($currentPage ?? ''), ['boleteria','boleteria_categorias','boleteria_boletas']); ?>
        <?php $canBole = canAccess('boleteria') || canAccess('boleteria.resumen') || canAccess('boleteria.categorias') || canAccess('boleteria.boletas'); ?>
        <?php if ($canBole): ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuBoleteria" role="button" aria-expanded="<?php echo $isBoleteriaOpen ? 'true' : 'false'; ?>" aria-controls="menuBoleteria">
            <span><i class="fas fa-ticket-alt me-2"></i>Boletería</span>
        </a>
        <div class="collapse <?php echo $isBoleteriaOpen ? 'show' : ''; ?> ms-3" id="menuBoleteria">
            <?php if (canAccess('boleteria') || canAccess('boleteria.resumen')): ?>
            <a class="nav-link <?php echo $currentPage === 'boleteria' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <?php endif; ?>
            <?php if (canAccess('boleteria') || canAccess('boleteria.categorias')): ?>
            <a class="nav-link <?php echo $currentPage === 'boleteria_categorias' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/categorias.php">
                <i class="fas fa-tags small me-2"></i>Categorías
            </a>
            <?php endif; ?>
            <?php if (canAccess('boleteria') || canAccess('boleteria.boletas')): ?>
            <a class="nav-link <?php echo $currentPage === 'boleteria_boletas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/boleteria/pages/boletas.php">
                <i class="fas fa-ticket-alt small me-2"></i>Boletas
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php $isTiendaOpen = in_array(($currentPage ?? ''), ['tienda','tienda_catalogo','tienda_facturacion','tienda_inventario','tienda_compras','tienda_ventas','tienda_clientes','tienda_reversiones']); ?>
        <?php $canTienda = canAccess('tienda') || canAccess('tienda.resumen') || canAccess('tienda.catalogo') || canAccess('tienda.compras') || canAccess('tienda.inventario') || canAccess('tienda.clientes') || canAccess('tienda.ventas') || canAccess('tienda.facturacion') || canAccess('tienda.reversiones'); ?>
        <?php if ($canTienda): ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuTienda" role="button" aria-expanded="<?php echo $isTiendaOpen ? 'true' : 'false'; ?>" aria-controls="menuTienda">
            <span><i class="fas fa-store me-2"></i>Tienda</span>
        </a>
        <div class="collapse <?php echo $isTiendaOpen ? 'show' : ''; ?> ms-3" id="menuTienda">
            <?php if (canAccess('tienda') || canAccess('tienda.resumen')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.catalogo')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_catalogo' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/catalogo.php">
                <i class="fas fa-tags small me-2"></i>Catálogo
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.compras')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_compras' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/compras.php">
                <i class="fas fa-truck-loading small me-2"></i>Compras
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.inventario')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_inventario' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/inventario.php">
                <i class="fas fa-warehouse small me-2"></i>Inventario
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.clientes')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_clientes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/clientes.php">
                <i class="fas fa-user-friends small me-2"></i>Clientes
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.ventas')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_ventas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/ventas.php">
                <i class="fas fa-cash-register small me-2"></i>Ventas
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.facturacion')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_facturacion' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/facturacion.php">
                <i class="fas fa-file-invoice-dollar small me-2"></i>Facturación
            </a>
            <?php endif; ?>
            <?php if (canAccess('tienda') || canAccess('tienda.reversiones')): ?>
            <a class="nav-link <?php echo $currentPage === 'tienda_reversiones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/reversiones.php">
                <i class="fas fa-undo-alt small me-2"></i>Reversiones
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php $isCredDocsOpen = in_array(($currentPage ?? ''), ['creditos_docs_crear','creditos_docs_listar','creditos_docs_gestionar']); ?>
        <?php $canCredDocs = canAccess('creditos_docs') || canAccess('creditos_docs.crear') || canAccess('creditos_docs.listar') || canAccess('creditos_docs.gestionar'); ?>
        <?php if ($canCredDocs): ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCredDocs" role="button" aria-expanded="<?php echo $isCredDocsOpen ? 'true' : 'false'; ?>" aria-controls="menuCredDocs">
            <span><i class="fas fa-file-alt me-2"></i>Créditos Docs</span>
        </a>
        <div class="collapse <?php echo $isCredDocsOpen ? 'show' : ''; ?> ms-3" id="menuCredDocs">
            <?php if (canAccess('creditos_docs.crear')): ?>
            <a class="nav-link <?php echo $currentPage === 'creditos_docs_crear' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos_docs/pages/crear_solicitud.php">
                <i class="fas fa-plus small me-2"></i>Nueva Solicitud
            </a>
            <?php endif; ?>
            <?php if (canAccess('creditos_docs.listar')): ?>
            <a class="nav-link <?php echo $currentPage === 'creditos_docs_listar' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/creditos_docs/pages/listar_solicitudes.php">
                <i class="fas fa-list small me-2"></i>Lista de Solicitudes
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php $isCobranzaOpen = in_array(($currentPage ?? ''), ['cobranza','cobranza_comunicaciones','cobranza_comunicaciones_aportes']); ?>
        <?php $canCobr = canAccess('cobranza') || canAccess('cobranza.resumen') || canAccess('cobranza.comunicaciones'); ?>
        <?php if ($canCobr): ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCobranza" role="button" aria-expanded="<?php echo $isCobranzaOpen ? 'true' : 'false'; ?>" aria-controls="menuCobranza">
            <span><i class="fas fa-phone me-2"></i>Cobranza</span>
        </a>
        <div class="collapse <?php echo $isCobranzaOpen ? 'show' : ''; ?> ms-3" id="menuCobranza">
            <?php if (canAccess('cobranza') || canAccess('cobranza.resumen')): ?>
            <a class="nav-link <?php echo $currentPage === 'cobranza' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <?php endif; ?>
            <?php if (canAccess('cobranza') || canAccess('cobranza.comunicaciones')): ?>
            <a class="nav-link <?php echo $currentPage === 'cobranza_comunicaciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comunicaciones.php">
                <i class="fas fa-comments small me-2"></i>Comms Crédito
            </a>
            <a class="nav-link <?php echo $currentPage === 'cobranza_comunicaciones_aportes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comms_aportes.php">
                <i class="fas fa-hand-holding-heart small me-2"></i>Comms Aportes
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php $isCxControlOpen = in_array(($currentPage ?? ''), ['cx_control']); ?>
        <?php if (canAccess('cx_control.publicidad')): ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCxControl" role="button" aria-expanded="<?php echo $isCxControlOpen ? 'true' : 'false'; ?>" aria-controls="menuCxControl">
            <span><i class="fas fa-mobile-alt me-2"></i>CX Control</span>
        </a>
        <div class="collapse <?php echo $isCxControlOpen ? 'show' : ''; ?> ms-3" id="menuCxControl">
            <a class="nav-link <?php echo $currentPage === 'cx_control' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cx_control/pages/publicidad.php">
                <i class="fas fa-bullhorn small me-2"></i>Publicidad
            </a>
        </div>
        <?php endif; ?>

        
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
        <?php $isOficinaOpen = in_array(($currentPage ?? ''), ['oficina','productos','clausulas','asociados','pagos_pse','pagos_cash_qr','transacciones','trx_list','cargas','descargas','oficina_comisiones','tasas_interes','tasas_productos']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuOficina" role="button" aria-expanded="<?php echo $isOficinaOpen ? 'true' : 'false'; ?>" aria-controls="menuOficina">
            <span><i class="fas fa-building me-2"></i>Oficina</span>
        </a>
        <div class="collapse <?php echo $isOficinaOpen ? 'show' : ''; ?> ms-3" id="menuOficina">
            <a class="nav-link <?php echo $currentPage === 'oficina' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'productos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/productos.php">
                <i class="fas fa-box small me-2"></i>Productos
            </a>
            <a class="nav-link <?php echo $currentPage === 'clausulas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/clausulas.php">
                <i class="fas fa-file-contract small me-2"></i>Cláusulas
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
            <a class="nav-link <?php echo $currentPage === 'oficina_comisiones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/comisiones.php">
                <i class="fas fa-percentage small me-2"></i>Comisiones
            </a>
            <a class="nav-link <?php echo $currentPage === 'tasas_interes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/tasas_interes.php">
                <i class="fas fa-percentage small me-2"></i>Tasas de Interés
            </a>
            <a class="nav-link <?php echo $currentPage === 'tasas_productos' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/tasas_productos.php">
                <i class="fas fa-box small me-2"></i>Tasas de Productos
            </a>
            <a class="nav-link <?php echo $currentPage === 'descargas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/oficina/pages/descargas.php">
                <i class="fas fa-download small me-2"></i>Descargas
            </a>
        </div>

        <?php $isBoleteriaOpen = in_array(($currentPage ?? ''), ['boleteria','boleteria_categorias','boleteria_boletas']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuBoleteria" role="button" aria-expanded="<?php echo $isBoleteriaOpen ? 'true' : 'false'; ?>" aria-controls="menuBoleteria">
            <span><i class="fas fa-ticket-alt me-2"></i>Boletería</span>
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

        <?php $isTiendaOpen = in_array(($currentPage ?? ''), ['tienda','tienda_catalogo','tienda_facturacion','tienda_inventario','tienda_compras','tienda_ventas','tienda_clientes','tienda_reversiones']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuTienda" role="button" aria-expanded="<?php echo $isTiendaOpen ? 'true' : 'false'; ?>" aria-controls="menuTienda">
            <span><i class="fas fa-store me-2"></i>Tienda</span>
        </a>
        <div class="collapse <?php echo $isTiendaOpen ? 'show' : ''; ?> ms-3" id="menuTienda">
            <a class="nav-link <?php echo $currentPage === 'tienda' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_catalogo' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/catalogo.php">
                <i class="fas fa-tags small me-2"></i>Catálogo
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_compras' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/compras.php">
                <i class="fas fa-truck-loading small me-2"></i>Compras
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_inventario' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/inventario.php">
                <i class="fas fa-warehouse small me-2"></i>Inventario
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_clientes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/clientes.php">
                <i class="fas fa-user-friends small me-2"></i>Clientes
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_ventas' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/ventas.php">
                <i class="fas fa-cash-register small me-2"></i>Ventas
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_facturacion' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/facturacion.php">
                <i class="fas fa-file-invoice-dollar small me-2"></i>Facturación
            </a>
            <a class="nav-link <?php echo $currentPage === 'tienda_reversiones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/tienda/pages/reversiones.php">
                <i class="fas fa-undo-alt small me-2"></i>Reversiones
            </a>
        </div>

        <?php $isCobranzaOpen = in_array(($currentPage ?? ''), ['cobranza','cobranza_comunicaciones','cobranza_comunicaciones_aportes']); ?>
        <a class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#menuCobranza" role="button" aria-expanded="<?php echo $isCobranzaOpen ? 'true' : 'false'; ?>" aria-controls="menuCobranza">
            <span><i class="fas fa-phone me-2"></i>Cobranza</span>
        </a>
        <div class="collapse <?php echo $isCobranzaOpen ? 'show' : ''; ?> ms-3" id="menuCobranza">
            <a class="nav-link <?php echo $currentPage === 'cobranza' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/index.php">
                <i class="fas fa-circle-notch small me-2"></i>Resumen
            </a>
            <a class="nav-link <?php echo $currentPage === 'cobranza_comunicaciones' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comunicaciones.php">
                <i class="fas fa-comments small me-2"></i>Comms Crédito
            </a>
            <a class="nav-link <?php echo $currentPage === 'cobranza_comunicaciones_aportes' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>modules/cobranza/pages/comms_aportes.php">
                <i class="fas fa-hand-holding-heart small me-2"></i>Comms Aportes
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
