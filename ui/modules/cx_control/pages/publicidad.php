<?php
require_once '../../../controllers/AuthController.php';
require_once '../../../config/paths.php';

$auth = new AuthController();
$auth->requireModule('cx_control.publicidad');
$currentUser = $auth->getCurrentUser();

$pageTitle = 'CX Control - Publicidad';
$currentPage = 'cx_control';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../../../views/layouts/sidebar.php'; ?>
    <main class="col-12 main-content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-bullhorn me-2"></i>CX Control - Publicidad</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaPublicidadModal">
          <i class="fas fa-plus me-1"></i>Nueva Publicidad
        </button>
      </div>

      <div class="row g-3">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <strong>Publicidades Activas</strong>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Tipo</th>
                      <th>Nombre</th>
                      <th>Descripción</th>
                      <th>Imagen</th>
                      <th>Fecha Inicio</th>
                      <th>Fecha Fin</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="publicidadesTable">
                    <!-- Se llenará dinámicamente -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Modal Nueva Publicidad -->
<div class="modal fade" id="nuevaPublicidadModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Nueva Publicidad</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="publicidadForm">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tipo <span class="text-danger">*</span></label>
              <select name="tipo" class="form-select" required>
                <option value="">Seleccione un tipo</option>
                <option value="pagina_principal">Página Principal</option>
                <option value="perfil">Perfil</option>
                <option value="creditos">Créditos</option>
                <option value="monetario">Monetario</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombre <span class="text-danger">*</span></label>
              <input type="text" name="nombre" class="form-control" required maxlength="255">
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea name="descripcion" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
              <input type="date" name="fecha_inicio" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Fin <span class="text-danger">*</span></label>
              <input type="date" name="fecha_fin" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Imagen <span class="text-danger">*</span></label>
              <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png" required>
              <small class="text-muted">Formatos permitidos: JPG, PNG. Tamaño máximo: 2MB</small>
            </div>
            <div class="col-12">
              <div class="preview-container" style="display: none;">
                <label class="form-label">Vista Previa</label>
                <div class="border rounded p-3 text-center">
                  <img id="previewImage" src="" alt="Vista previa" class="img-fluid" style="max-height: 200px;">
                </div>
              </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Publicidad</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Función para obtener etiqueta del tipo
function getTipoLabel(tipo) {
  const tipos = {
    'pagina_principal': 'Página Principal',
    'perfil': 'Perfil',
    'creditos': 'Créditos',
    'monetario': 'Monetario'
  };
  return tipos[tipo] || tipo;
}

// Función para cargar publicidades
async function cargarPublicidades() {
  try {
    console.log('Cargando publicidades...');
    const res = await fetch('../api/publicidad.php?action=listar');
    console.log('Respuesta de listar:', res);
    console.log('Status de listar:', res.status);
    
    const json = await res.json();
    console.log('JSON de listar:', json);
    
    if (json.success) {
      console.log('Publicidades encontradas:', json.data.length);
      mostrarPublicidades(json.data);
    } else {
      console.error('Error al cargar publicidades:', json.message);
    }
  } catch (error) {
    console.error('Error en cargarPublicidades:', error);
  }
}

// Función para mostrar publicidades en la tabla
function mostrarPublicidades(publicidades) {
  const tbody = document.getElementById('publicidadesTable');
  tbody.innerHTML = '';
  
  if (publicidades.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay publicidades registradas</td></tr>';
    return;
  }
  
  publicidades.forEach(pub => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${getTipoLabel(pub.tipo)}</td>
      <td>${pub.nombre}</td>
      <td>${pub.descripcion || '-'}</td>
      <td>
        ${pub.imagen && pub.imagen !== 'test.jpg' ? 
          `<img src="${pub.imagen}" alt="Imagen" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">` : 
          '<span class="text-muted">Sin imagen</span>'
        }
      </td>
      <td>${pub.fecha_inicio_formatted}</td>
      <td>${pub.fecha_fin_formatted}</td>
      <td><span class="${pub.estado_class}">${pub.estado}</span></td>
      <td>
        <button class="btn btn-sm btn-outline-danger" onclick="eliminarPublicidad(${pub.id})">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// Función para eliminar publicidad
async function eliminarPublicidad(id) {
  if (!confirm('¿Estás seguro de que deseas eliminar esta publicidad?')) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('action', 'eliminar');
    formData.append('id', id);
    
    const res = await fetch('../api/publicidad.php', {
      method: 'POST',
      body: formData
    });
    
    const json = await res.json();
    
    if (json.success) {
      alert('Publicidad eliminada exitosamente');
      cargarPublicidades();
    } else {
      alert('Error: ' + json.message);
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error al eliminar la publicidad');
  }
}

// Manejar envío del formulario
document.getElementById('publicidadForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Validar fechas
  const fechaInicio = new Date(document.querySelector('input[name="fecha_inicio"]').value);
  const fechaFin = new Date(document.querySelector('input[name="fecha_fin"]').value);
  
  if (fechaFin <= fechaInicio) {
    alert('La fecha de fin debe ser posterior a la fecha de inicio.');
    return false;
  }
  
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  
  if (fechaInicio < hoy) {
    alert('La fecha de inicio no puede ser anterior a hoy.');
    return false;
  }
  
  const formData = new FormData(this);
  formData.append('action', 'crear');
  
  try {
    const res = await fetch('../api/publicidad.php', {
      method: 'POST',
      body: formData
    });
    
    console.log('Respuesta recibida:', res);
    console.log('Status:', res.status);
    
    if (!res.ok) {
      const errorText = await res.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${res.status} - ${errorText}`);
    }
    
    const json = await res.json();
    console.log('Datos JSON:', json);
    
    if (json.success) {
      alert('Publicidad creada exitosamente');
      bootstrap.Modal.getInstance(document.getElementById('nuevaPublicidadModal')).hide();
      this.reset();
      cargarPublicidades();
    } else {
      alert('Error: ' + json.message);
    }
  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al crear la publicidad: ' + error.message);
  }
});

// Función para mostrar vista previa de imagen
document.querySelector('input[name="imagen"]').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewImage').src = e.target.result;
      document.querySelector('.preview-container').style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
});

// Establecer fecha de hoy como fecha mínima
document.addEventListener('DOMContentLoaded', function() {
  const hoy = new Date().toISOString().split('T')[0];
  document.querySelector('input[name="fecha_inicio"]').min = hoy;
  document.querySelector('input[name="fecha_fin"]').min = hoy;
  
  // Cargar publicidades
  cargarPublicidades();
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>