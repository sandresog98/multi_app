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
        <div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaPublicidadModal">
            <i class="fas fa-plus me-1"></i>Nueva Publicidad
          </button>
        </div>
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

<!-- Modal Nueva/Editar Publicidad -->
<div class="modal fade" id="nuevaPublicidadModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i><span id="modalTitle">Nueva Publicidad</span></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="publicidadForm">
        <input type="hidden" id="publicidadId" name="id" value="">
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
    
    if (!res.ok) {
      console.error('Error HTTP:', res.status, res.statusText);
      const errorText = await res.text();
      console.error('Error response:', errorText);
      return;
    }
    
    const json = await res.json();
    console.log('JSON de listar:', json);
    
    if (json.success) {
      console.log('Publicidades encontradas:', json.data.length);
      if (json.data.length === 0) {
        console.log('No hay publicidades en la base de datos');
      }
      mostrarPublicidades(json.data);
    } else {
      console.error('Error al cargar publicidades:', json.message);
      alert('Error al cargar publicidades: ' + json.message);
    }
  } catch (error) {
    console.error('Error en cargarPublicidades:', error);
    alert('Error al cargar publicidades: ' + error.message);
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
        ${pub.imagen_url && pub.imagen_url !== 'test.jpg' && pub.imagen_url !== null ? 
          `<a href="${pub.imagen_url}" target="_blank" style="text-decoration: none;">
             <img src="${pub.imagen_url}" alt="Imagen" class="img-thumbnail" style="max-width: 80px; max-height: 80px; cursor: pointer;" onclick="window.open('${pub.imagen_url}', '_blank')" title="Clic para ver completa" onerror="this.parentElement.innerHTML='<span class=\'text-muted\'>Archivo no encontrado</span>'">
           </a>` : 
          '<span class="text-muted">Sin imagen</span>'
        }
      </td>
      <td>${pub.fecha_inicio_formatted}</td>
      <td>${pub.fecha_fin_formatted}</td>
      <td><span class="${pub.estado_class}">${pub.estado}</span></td>
      <td>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-info" onclick="editarPublicidad(${pub.id})" title="Editar">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="eliminarPublicidad(${pub.id})" title="Eliminar">
            <i class="fas fa-trash"></i>
          </button>
        </div>
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

// Función para editar publicidad
async function editarPublicidad(id) {
  try {
    console.log('Cargando publicidad ID:', id);
    const res = await fetch(`../api/publicidad.php?action=obtener&id=${id}`);
    console.log('Response status:', res.status);
    
    if (!res.ok) {
      const errorText = await res.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${res.status} - ${errorText}`);
    }
    
    const json = await res.json();
    console.log('JSON obtenido:', json);
    
    if (json.success && json.data) {
      const pub = json.data;
      console.log('Publicidad cargada:', pub);
      
      // Llenar formulario con datos existentes
      const form = document.getElementById('publicidadForm');
      const hiddenId = document.getElementById('publicidadId');
      
      if (hiddenId) hiddenId.value = pub.id;
      
      const tipoField = form.querySelector('select[name="tipo"]');
      const nombreField = form.querySelector('input[name="nombre"]');
      const descField = form.querySelector('textarea[name="descripcion"]');
      const fechaInicioField = form.querySelector('input[name="fecha_inicio"]');
      const fechaFinField = form.querySelector('input[name="fecha_fin"]');
      
      if (tipoField) tipoField.value = pub.tipo;
      if (nombreField) nombreField.value = pub.nombre;
      if (descField) descField.value = pub.descripcion || '';
      if (fechaInicioField) fechaInicioField.value = pub.fecha_inicio;
      if (fechaFinField) fechaFinField.value = pub.fecha_fin;
      
      // Cambiar título del modal
      document.getElementById('modalTitle').textContent = 'Editar Publicidad';
      
      // Mostrar imagen actual si existe
      if (pub.imagen) {
        const previewContainer = document.querySelector('.preview-container');
        const previewImage = document.getElementById('previewImage');
        
        // Obtener URL convertida usando el mismo sistema que listar
        // Usar la función getImageUrl si está disponible, sino usar la imagen directamente
        previewImage.src = pub.imagen_url || pub.imagen;
        previewContainer.style.display = 'block';
      } else {
        // Ocultar vista previa si no hay imagen
        const previewContainer = document.querySelector('.preview-container');
        if (previewContainer) {
          previewContainer.style.display = 'none';
        }
      }
      
      // Hacer el campo de imagen no requerido al editar
      document.querySelector('input[name="imagen"]').removeAttribute('required');
      
      // Abrir modal
      const modal = new bootstrap.Modal(document.getElementById('nuevaPublicidadModal'));
      modal.show();
    } else {
      console.error('No success o sin data:', json);
      alert('Error al obtener datos de la publicidad: ' + (json.message || 'Desconocido'));
    }
  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al cargar la publicidad: ' + error.message);
  }
}

// Manejar envío del formulario
document.getElementById('publicidadForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Validar fechas usando strings para evitar problemas de zona horaria
  const fechaInicioStr = document.querySelector('input[name="fecha_inicio"]').value;
  const fechaFinStr = document.querySelector('input[name="fecha_fin"]').value;
  
  if (fechaFinStr <= fechaInicioStr) {
    alert('La fecha de fin debe ser posterior a la fecha de inicio.');
    return false;
  }
  
  // Obtener fecha de hoy en formato YYYY-MM-DD
  const hoy = new Date();
  const hoyStr = hoy.toISOString().split('T')[0];
  
  // Permitir que la fecha de inicio sea hoy o posterior
  if (fechaInicioStr < hoyStr) {
    alert('La fecha de inicio no puede ser anterior a hoy.');
    return false;
  }
  
  const formData = new FormData(this);
  const publicidadId = document.getElementById('publicidadId').value;
  
  // Determinar si es edición o creación
  if (publicidadId) {
    formData.append('action', 'editar');
  } else {
    formData.append('action', 'crear');
  }
  
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
      const modalInstance = bootstrap.Modal.getInstance(document.getElementById('nuevaPublicidadModal'));
      modalInstance.hide();
      this.reset();
      document.getElementById('publicidadId').value = '';
      document.getElementById('modalTitle').textContent = 'Nueva Publicidad';
      document.querySelector('input[name="imagen"]').setAttribute('required', 'required');
      cargarPublicidades();
      alert(publicidadId ? 'Publicidad actualizada exitosamente' : 'Publicidad creada exitosamente');
    } else {
      alert('Error: ' + json.message);
    }
  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al guardar la publicidad: ' + error.message);
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
  
  // Limpiar formulario al cerrar modal
  const modal = document.getElementById('nuevaPublicidadModal');
  modal.addEventListener('hidden.bs.modal', function() {
    document.getElementById('publicidadForm').reset();
    document.getElementById('publicidadId').value = '';
    document.getElementById('modalTitle').textContent = 'Nueva Publicidad';
    document.querySelector('input[name="imagen"]').setAttribute('required', 'required');
    document.querySelector('.preview-container').style.display = 'none';
  });
  
  // Cargar publicidades
  cargarPublicidades();
});
</script>

<?php include '../../../views/layouts/footer.php'; ?>