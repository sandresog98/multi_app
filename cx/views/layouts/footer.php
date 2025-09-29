    <nav class="bottom-nav">
      <div class="container">
        <ul class="nav justify-content-around">
          <?php 
          $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
          
          // Detectar página actual - solo una puede ser true
          $isIndex = strpos($currentScript, '/pages/index.php') !== false;
          $isResumen = strpos($currentScript, '/modules/resumen/pages/index.php') !== false;
          
          // Asegurar que solo una página esté activa
          if ($isResumen) {
            $isIndex = false; // Si estamos en resumen, inicio no puede estar activo
          }
          
          // Determinar las rutas correctas según la ubicación actual
          if (strpos($currentScript, '/modules/resumen/pages/') !== false) {
            $indexUrl = '../../../pages/index.php';
            $resumenUrl = 'index.php';
          } else {
            $indexUrl = 'index.php';
            $resumenUrl = '../modules/resumen/pages/index.php';
          }
          
          // Debug temporal
          if (isset($_GET['debug_footer'])) {
            echo "<!-- DEBUG FOOTER: currentScript='$currentScript', isIndex=" . ($isIndex ? 'true' : 'false') . ", isResumen=" . ($isResumen ? 'true' : 'false') . " -->";
          }
          ?>
          <li class="nav-item"><a class="nav-link <?php echo $isIndex ? 'active' : ''; ?>" href="<?php echo $indexUrl; ?>"><i class="fa-solid fa-house"></i><br>Inicio</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $isResumen ? 'active' : ''; ?>" href="<?php echo $resumenUrl; ?>"><i class="fa-solid fa-chart-pie"></i><br>Resumen</a></li>
        </ul>
      </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
 </html>


