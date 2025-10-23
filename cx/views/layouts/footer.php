    <!-- Nuevo Footer con Pestañas -->
    <div class="modern-footer">
      <div class="tab-container">
        <?php 
        // Incluir configuración de rutas
        require_once __DIR__ . '/../../config/paths.php';
        
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Detectar página actual - solo una puede ser true
        $isIndex = strpos($currentScript, '/cx/index.php') !== false;
        $isPerfil = strpos($currentScript, '/modules/perfil/pages/index.php') !== false;
        $isMonetario = strpos($currentScript, '/modules/monetario/pages/index.php') !== false;
        $isCreditos = strpos($currentScript, '/modules/creditos/pages/index.php') !== false;
        
        // Asegurar que solo una página esté activa
        if ($isPerfil || $isMonetario || $isCreditos) {
          $isIndex = false; // Si estamos en cualquier módulo, inicio no puede estar activo
        }
        
        // Usar rutas dinámicas basadas en la configuración
        $baseUrl = cx_getBaseUrl();
        $indexUrl = $baseUrl . 'index.php';
        $perfilUrl = $baseUrl . 'modules/perfil/pages/index.php';
        $monetarioUrl = $baseUrl . 'modules/monetario/pages/index.php';
        $creditosUrl = $baseUrl . 'modules/creditos/pages/index.php';
        ?>
        
        <a class="tab-item <?php echo $isIndex ? 'active' : ''; ?>" href="<?php echo $indexUrl; ?>" data-tab="inicio">
          <i class="fa-solid fa-house tab-icon"></i>
          <span class="tab-label">Inicio</span>
        </a>
        
        <a class="tab-item <?php echo $isPerfil ? 'active' : ''; ?>" href="<?php echo $perfilUrl; ?>" data-tab="perfil">
          <i class="fa-solid fa-user tab-icon"></i>
          <span class="tab-label">Perfil</span>
        </a>
        
        <a class="tab-item <?php echo $isMonetario ? 'active' : ''; ?>" href="<?php echo $monetarioUrl; ?>" data-tab="monetario">
          <i class="fa-solid fa-wallet tab-icon"></i>
          <span class="tab-label">Monetario</span>
        </a>
        
        <a class="tab-item <?php echo $isCreditos ? 'active' : ''; ?>" href="<?php echo $creditosUrl; ?>" data-tab="creditos">
          <i class="fa-solid fa-credit-card tab-icon"></i>
          <span class="tab-label">Créditos</span>
        </a>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo cx_getBaseUrl(); ?>assets/js/footer-tabs.js"></script>
  </body>
 </html>


