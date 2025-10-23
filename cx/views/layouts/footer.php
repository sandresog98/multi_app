    <nav class="bottom-nav">
      <div class="container">
        <ul class="nav justify-content-around">
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
          <li class="nav-item"><a class="nav-link <?php echo $isIndex ? 'active' : ''; ?>" href="<?php echo $indexUrl; ?>"><i class="fa-solid fa-house"></i><br>Inicio</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $isPerfil ? 'active' : ''; ?>" href="<?php echo $perfilUrl; ?>"><i class="fa-solid fa-user"></i><br>Perfil</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $isMonetario ? 'active' : ''; ?>" href="<?php echo $monetarioUrl; ?>"><i class="fa-solid fa-wallet"></i><br>Monetario</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $isCreditos ? 'active' : ''; ?>" href="<?php echo $creditosUrl; ?>"><i class="fa-solid fa-credit-card"></i><br>Créditos</a></li>
        </ul>
      </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
 </html>


