    <!-- Nuevo Footer con Pestañas -->
    <style>
    /* CSS inline para el diseño final limpio */
    .modern-footer {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
        z-index: 1000 !important;
        padding: 8px 0 !important;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1) !important;
        display: block !important;
        width: 100% !important;
        min-height: 60px !important;
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
    .modern-footer .tab-container {
        display: flex !important;
        justify-content: space-around !important;
        align-items: center !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        padding: 0 16px !important;
        width: 100% !important;
        min-height: 50px !important;
    }
    .modern-footer .tab-item {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        padding: 8px 4px !important;
        border-radius: 12px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer !important;
        position: relative !important;
        min-height: 50px !important;
        justify-content: center !important;
        text-decoration: none !important;
        color: #64748b !important;
        background: transparent !important;
    }
    .modern-footer .tab-item:hover {
        background: rgba(14, 165, 233, 0.1) !important;
        transform: translateY(-2px) !important;
        text-decoration: none !important;
        color: #0ea5e9 !important;
    }
    .modern-footer .tab-item.active {
        background: linear-gradient(135deg, #0ea5e9, #3b82f6) !important;
        color: white !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3) !important;
    }
    .modern-footer .tab-item.active::before {
        content: '' !important;
        position: absolute !important;
        top: -8px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: 4px !important;
        height: 4px !important;
        background: #0ea5e9 !important;
        border-radius: 50% !important;
        box-shadow: 0 0 8px rgba(14, 165, 233, 0.6) !important;
    }
    .modern-footer .tab-icon {
        font-size: 1.2rem !important;
        margin-bottom: 4px !important;
        transition: all 0.3s ease !important;
    }
    .modern-footer .tab-item.active .tab-icon {
        transform: scale(1.1) !important;
    }
    .modern-footer .tab-label {
        font-size: 0.7rem !important;
        font-weight: 500 !important;
        text-align: center !important;
        line-height: 1.2 !important;
        transition: all 0.3s ease !important;
    }
    .modern-footer .tab-item.active .tab-label {
        font-weight: 600 !important;
    }
    
    /* Responsive para pantallas pequeñas */
    @media (max-width: 480px) {
        .modern-footer .tab-container {
            padding: 0 8px !important;
        }
        .modern-footer .tab-item {
            padding: 6px 2px !important;
            min-height: 45px !important;
        }
        .modern-footer .tab-icon {
            font-size: 1rem !important;
        }
        .modern-footer .tab-label {
            font-size: 0.65rem !important;
        }
    }
    
    @media (max-width: 360px) {
        .modern-footer .tab-item {
            padding: 4px 1px !important;
            min-height: 40px !important;
        }
        .modern-footer .tab-icon {
            font-size: 0.9rem !important;
        }
        .modern-footer .tab-label {
            font-size: 0.6rem !important;
        }
    }
    </style>
    <div class="modern-footer">
      <div class="tab-container">
        <?php 
        // Incluir configuración de rutas
        require_once __DIR__ . '/../../config/paths.php';
        
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Detectar página actual - solo una puede ser true
        $isIndex = strpos($currentScript, '/cx/pages/index.php') !== false;
        $isPerfil = strpos($currentScript, '/modules/perfil/pages/index.php') !== false;
        $isMonetario = strpos($currentScript, '/modules/monetario/pages/index.php') !== false;
        $isCreditos = strpos($currentScript, '/modules/creditos/pages/index.php') !== false;
        
        // Debug temporal - comentar después
        // echo "<!-- Debug: Script: $currentScript, Index: " . ($isIndex ? 'true' : 'false') . ", Perfil: " . ($isPerfil ? 'true' : 'false') . " -->";
        
        // Asegurar que solo una página esté activa
        if ($isPerfil || $isMonetario || $isCreditos) {
          $isIndex = false; // Si estamos en cualquier módulo, inicio no puede estar activo
        }
        
        // Usar rutas dinámicas basadas en la configuración
        $baseUrl = cx_getBaseUrl();
        $indexUrl = $baseUrl . 'pages/index.php';
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


