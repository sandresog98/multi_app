// Componente de publicidad para CX
class CxPublicidad {
    constructor() {
        console.log('CxPublicidad inicializado - versión actualizada');
        this.apiUrl = this.getApiUrl();
        this.tipo = this.getTipoPagina();
        this.init();
    }
    
    getApiUrl() {
        // Usar ruta absoluta para evitar problemas de rutas relativas
        return '/projects/multi_app/ui/api/cx_publicidad.php';
    }
    
    getTipoPagina() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/perfil/')) return 'perfil';
        if (currentPath.includes('/creditos/')) return 'creditos';
        if (currentPath.includes('/monetario/')) return 'monetario';
        return 'pagina_principal';
    }
    
    async init() {
        try {
            const publicidad = await this.obtenerPublicidadActiva();
            if (publicidad) {
                console.log('Intentando mostrar publicidad:', publicidad);
                this.mostrarPublicidad(publicidad);
            } else {
                console.log('No hay publicidad para mostrar');
            }
        } catch (error) {
            console.log('No se pudo cargar publicidad:', error);
        }
    }
    
    async obtenerPublicidadActiva() {
        console.log('API URL:', this.apiUrl);
        console.log('Tipo:', this.tipo);
        const fullUrl = `${this.apiUrl}?tipo=${this.tipo}`;
        console.log('URL completa:', fullUrl);
        
        const response = await fetch(fullUrl);
        const data = await response.json();
        
        console.log('Respuesta del API:', data);
        
        if (data.success) {
            console.log('Publicidad encontrada:', data.data);
            return data.data;
        } else {
            console.log('No hay publicidad activa:', data.message);
        }
        return null;
    }
    
    mostrarPublicidad(publicidad) {
        console.log('mostrarPublicidad llamada con:', publicidad);
        
        // Verificar si ya se cerró esta publicidad
        const publicidadKey = `publicidad_cerrada_${publicidad.id}`;
        console.log('Verificando localStorage para:', publicidadKey);
        
        if (localStorage.getItem(publicidadKey)) {
            console.log('Publicidad ya fue cerrada, no se muestra');
            return;
        }
        
        console.log('Creando modal HTML...');
        
        // Crear el modal de publicidad
        const modalHtml = `
            <div class="modal fade" id="publicidadModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fa-solid fa-bullhorn me-2"></i>PUBLICIDAD
                            </h5>
                        </div>
                        <div class="modal-body text-center">
                            <div class="mb-3">
                                <img src="${publicidad.imagen_url}" alt="${publicidad.nombre}" class="img-fluid rounded" style="max-height: 300px;">
                            </div>
                            <h6 class="fw-bold text-primary">${publicidad.nombre}</h6>
                            ${publicidad.descripcion ? `<p class="text-muted">${publicidad.descripcion}</p>` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" onclick="cxPublicidad.cerrarPublicidad(${publicidad.id})">
                                <i class="fa-solid fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        console.log('Agregando modal al DOM...');
        
        // Agregar el modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        console.log('Intentando mostrar modal...');
        
        // Mostrar el modal
        const modalElement = document.getElementById('publicidadModal');
        if (modalElement) {
            console.log('Modal encontrado en DOM');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal.show() ejecutado');
        } else {
            console.error('No se pudo encontrar el modal en el DOM');
        }
    }
    
    cerrarPublicidad(publicidadId) {
        // Marcar como cerrada en localStorage
        const publicidadKey = `publicidad_cerrada_${publicidadId}`;
        localStorage.setItem(publicidadKey, 'true');
        
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('publicidadModal'));
        modal.hide();
        
        // Remover el modal del DOM después de cerrar
        setTimeout(() => {
            const modalElement = document.getElementById('publicidadModal');
            if (modalElement) {
                modalElement.remove();
            }
        }, 300);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.cxPublicidad = new CxPublicidad();
});
