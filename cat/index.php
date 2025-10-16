<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - Cooperativa Multiunión</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .product-card {
            transition: transform 0.2s ease-in-out;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-2px);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .price-tag {
            font-size: 1.2rem;
            font-weight: bold;
            color: #198754;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .sort-dropdown {
            min-width: 150px;
        }
        .search-box {
            position: relative;
        }
        .search-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        .filter-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 2px;
            display: inline-block;
        }
        .filter-badge.active {
            background-color: #0d6efd;
            color: white;
        }
        .clear-filters {
            font-size: 0.9rem;
        }
        .dropdown-toggle::after {
            margin-left: 0.5em;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 16px;
        }
        .navbar-nav .nav-item {
            margin-left: 0.5rem;
        }
        .navbar .d-flex {
            gap: 0.5rem;
        }
        .navbar .btn {
            white-space: nowrap;
        }
        @media (max-width: 575.98px) {
            .navbar .btn span {
                display: none !important;
            }
            .navbar .btn {
                padding: 0.375rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/img/logo_telmovil.png" alt="Telmovil" height="40" class="me-2">
                <span>Catálogo de Productos</span>
            </a>
            
            <!-- Botones siempre visibles -->
            <div class="d-flex">
                <button class="btn btn-outline-light btn-sm me-2" data-bs-toggle="offcanvas" data-bs-target="#filtersOffcanvas">
                    <i class="bi bi-funnel"></i> <span class="d-none d-sm-inline">Filtros</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-sort-down"></i> <span class="d-none d-sm-inline">Ordenar</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-sort="nombre" data-direction="ASC">
                            <i class="bi bi-sort-alpha-down"></i> Nombre A-Z
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-sort="nombre" data-direction="DESC">
                            <i class="bi bi-sort-alpha-up"></i> Nombre Z-A
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-sort="precio" data-direction="DESC">
                            <i class="bi bi-sort-numeric-down"></i> Precio Mayor a Menor
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-sort="precio" data-direction="ASC">
                            <i class="bi bi-sort-numeric-up"></i> Precio Menor a Mayor
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-sort="categoria" data-direction="ASC">
                            <i class="bi bi-tag"></i> Categoría A-Z
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-sort="categoria" data-direction="DESC">
                            <i class="bi bi-tag"></i> Categoría Z-A
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-sort="marca" data-direction="ASC">
                            <i class="bi bi-award"></i> Marca A-Z
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-sort="marca" data-direction="DESC">
                            <i class="bi bi-award"></i> Marca Z-A
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Filtros móviles (Offcanvas) -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="filtersOffcanvas">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">Filtros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <div class="filter-section">
                    <!-- Búsqueda por nombre -->
                    <div class="mb-3">
                        <label for="searchName" class="form-label">Buscar producto</label>
                        <div class="search-box">
                            <input type="text" class="form-control" id="searchName" placeholder="Nombre del producto...">
                            <button type="button" class="search-clear" id="clearSearch" style="display: none;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Filtro por categoría -->
                    <div class="mb-3">
                        <label for="filterCategory" class="form-label">Categoría</label>
                        <select class="form-select" id="filterCategory">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>

                    <!-- Filtro por marca -->
                    <div class="mb-3">
                        <label for="filterBrand" class="form-label">Marca</label>
                        <select class="form-select" id="filterBrand">
                            <option value="">Todas las marcas</option>
                        </select>
                    </div>

                    <!-- Filtro por precio -->
                    <div class="mb-3">
                        <label class="form-label">Rango de precio</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control" id="priceMin" placeholder="Mínimo" min="0" step="0.01">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" id="priceMax" placeholder="Máximo" min="0" step="0.01">
                            </div>
                        </div>
                    </div>

                    <!-- Nota sobre ordenamiento -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <small>Usa el botón "Ordenar" en la barra superior para cambiar el orden de los productos.</small>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" id="applyFilters">
                            <i class="bi bi-search"></i> Aplicar filtros
                        </button>
                        <button type="button" class="btn btn-outline-secondary clear-filters" id="clearFilters">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros activos -->
        <div class="row mb-3" id="activeFilters" style="display: none;">
            <div class="col-12">
                <div class="d-flex flex-wrap align-items-center">
                    <span class="me-2">Filtros activos:</span>
                    <div id="filterBadges"></div>
                    <button class="btn btn-sm btn-outline-danger clear-filters ms-2" id="clearAllFilters">
                        <i class="bi bi-x-circle"></i> Limpiar todo
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="row">
            <div class="col-12">
                <!-- Indicador de ordenamiento -->
                <div id="sortIndicator" class="alert alert-light border-0 mb-3" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-sort-down me-2"></i>
                        <span id="sortText">Ordenando por: </span>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loading" class="loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando productos...</p>
                </div>

                <!-- Productos -->
                <div id="productsContainer">
                    <div class="row" id="productsGrid"></div>
                </div>

                <!-- No hay productos -->
                <div id="noProducts" class="no-products" style="display: none;">
                    <i class="bi bi-search" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No se encontraron productos</h4>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                </div>

                <!-- Paginación -->
                <div class="pagination-container" id="paginationContainer"></div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        class CatalogoApp {
            constructor() {
                this.baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
                this.currentPage = 1;
                this.filters = {};
                this.categories = [];
                this.brands = [];
                this.products = [];
                this.totalPages = 0;
                this.sortBy = 'nombre';
                this.sortDirection = 'ASC';
                
                this.init();
            }
            
            async init() {
                await this.loadCategories();
                await this.loadBrands();
                this.setupEventListeners();
                this.loadProducts();
            }
            
            setupEventListeners() {
                // Aplicar filtros
                document.getElementById('applyFilters').addEventListener('click', () => {
                    this.applyFilters();
                });
                
                // Limpiar filtros
                document.getElementById('clearFilters').addEventListener('click', () => {
                    this.clearFilters();
                });
                
                document.getElementById('clearAllFilters').addEventListener('click', () => {
                    this.clearFilters();
                });
                
                // Búsqueda en tiempo real
                document.getElementById('searchName').addEventListener('input', (e) => {
                    if (e.target.value.length > 2 || e.target.value.length === 0) {
                        this.debounce(() => this.applyFilters(), 500)();
                    }
                });
                
                // Limpiar búsqueda
                document.getElementById('clearSearch').addEventListener('click', () => {
                    document.getElementById('searchName').value = '';
                    this.applyFilters();
                });
                
                // Mostrar/ocultar botón de limpiar búsqueda
                document.getElementById('searchName').addEventListener('input', (e) => {
                    const clearBtn = document.getElementById('clearSearch');
                    clearBtn.style.display = e.target.value ? 'block' : 'none';
                });
                
                // Dropdown de ordenamiento
                document.querySelectorAll('[data-sort]').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        const sort = e.target.closest('[data-sort]').dataset.sort;
                        const direction = e.target.closest('[data-sort]').dataset.direction;
                        this.setSorting(sort, direction);
                    });
                });
            }
            
            async loadCategories() {
                try {
                    const response = await fetch(this.baseUrl + 'api/categorias.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.categories = data.data;
                        this.populateSelect('filterCategory', this.categories);
                    }
                } catch (error) {
                    console.error('Error cargando categorías:', error);
                }
            }
            
            async loadBrands() {
                try {
                    const response = await fetch(this.baseUrl + 'api/marcas.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.brands = data.data;
                        this.populateSelect('filterBrand', this.brands);
                    }
                } catch (error) {
                    console.error('Error cargando marcas:', error);
                }
            }
            
            populateSelect(selectId, data) {
                const select = document.getElementById(selectId);
                select.innerHTML = `<option value="">Todas las ${selectId === 'filterCategory' ? 'categorías' : 'marcas'}</option>`;
                
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.nombre;
                    select.appendChild(option);
                });
            }
            
            applyFilters() {
                this.filters = {
                    nombre: document.getElementById('searchName').value.trim(),
                    categoria_id: document.getElementById('filterCategory').value,
                    marca_id: document.getElementById('filterBrand').value,
                    precio_min: document.getElementById('priceMin').value,
                    precio_max: document.getElementById('priceMax').value
                };
                
                // Limpiar filtros vacíos
                Object.keys(this.filters).forEach(key => {
                    if (!this.filters[key]) {
                        delete this.filters[key];
                    }
                });
                
                this.currentPage = 1;
                this.loadProducts();
                this.updateActiveFilters();
                
                // Cerrar el offcanvas de filtros
                const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('filtersOffcanvas'));
                if (offcanvas) {
                    offcanvas.hide();
                }
            }
            
            clearFilters() {
                document.getElementById('searchName').value = '';
                document.getElementById('filterCategory').value = '';
                document.getElementById('filterBrand').value = '';
                document.getElementById('priceMin').value = '';
                document.getElementById('priceMax').value = '';
                
                this.filters = {};
                this.currentPage = 1;
                this.loadProducts();
                this.updateActiveFilters();
            }
            
            setSorting(sort, direction) {
                this.sortBy = sort;
                this.sortDirection = direction;
                this.currentPage = 1;
                this.loadProducts();
                
                // Actualizar el texto del botón dropdown
                const dropdownButton = document.querySelector('.dropdown button[data-bs-toggle="dropdown"]');
                const sortText = this.getSortText(sort, direction);
                dropdownButton.innerHTML = `<i class="bi bi-sort-down"></i> <span class="d-none d-sm-inline">${sortText}</span>`;
                
                // Mostrar indicador de ordenamiento
                this.showSortIndicator(sortText);
            }
            
            getSortText(sort, direction) {
                const sortNames = {
                    'nombre': 'Nombre',
                    'precio': 'Precio',
                    'categoria': 'Categoría',
                    'marca': 'Marca'
                };
                
                const directionText = direction === 'ASC' ? 'A-Z' : 'Z-A';
                return `${sortNames[sort]} ${directionText}`;
            }
            
            showSortIndicator(sortText) {
                const indicator = document.getElementById('sortIndicator');
                const sortTextElement = document.getElementById('sortText');
                
                if (indicator && sortTextElement) {
                    sortTextElement.textContent = `Ordenando por: ${sortText}`;
                    indicator.style.display = 'block';
                    
                    // Auto-ocultar después de 3 segundos
                    setTimeout(() => {
                        indicator.style.display = 'none';
                    }, 3000);
                }
            }
            
            updateActiveFilters() {
                const activeFiltersDiv = document.getElementById('activeFilters');
                const filterBadgesDiv = document.getElementById('filterBadges');
                
                const activeFilters = Object.keys(this.filters).filter(key => this.filters[key]);
                
                if (activeFilters.length > 0) {
                    activeFiltersDiv.style.display = 'block';
                    filterBadgesDiv.innerHTML = '';
                    
                    activeFilters.forEach(key => {
                        const badge = document.createElement('span');
                        badge.className = 'filter-badge active';
                        
                        let text = '';
                        switch (key) {
                            case 'nombre':
                                text = `Nombre: "${this.filters[key]}"`;
                                break;
                            case 'categoria_id':
                                const category = this.categories.find(c => c.id == this.filters[key]);
                                text = `Categoría: ${category ? category.nombre : 'N/A'}`;
                                break;
                            case 'marca_id':
                                const brand = this.brands.find(b => b.id == this.filters[key]);
                                text = `Marca: ${brand ? brand.nombre : 'N/A'}`;
                                break;
                            case 'precio_min':
                                text = `Precio desde: $${this.filters[key]}`;
                                break;
                            case 'precio_max':
                                text = `Precio hasta: $${this.filters[key]}`;
                                break;
                        }
                        
                        badge.textContent = text;
                        filterBadgesDiv.appendChild(badge);
                    });
                } else {
                    activeFiltersDiv.style.display = 'none';
                }
            }
            
            async loadProducts() {
                this.showLoading(true);
                
                try {
                    const params = new URLSearchParams({
                        pagina: this.currentPage,
                        por_pagina: 12,
                        ordenar: this.sortBy,
                        direccion: this.sortDirection,
                        ...this.filters
                    });
                    
                    const response = await fetch(this.baseUrl + 'api/productos.php?' + params);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.products = data.data.productos;
                        this.totalPages = data.data.total_paginas;
                        this.renderProducts();
                        this.renderPagination();
                    } else {
                        this.showError('Error cargando productos: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showError('Error de conexión');
                } finally {
                    this.showLoading(false);
                }
            }
            
            renderProducts() {
                const container = document.getElementById('productsGrid');
                const noProducts = document.getElementById('noProducts');
                
                if (this.products.length === 0) {
                    container.innerHTML = '';
                    noProducts.style.display = 'block';
                    return;
                }
                
                noProducts.style.display = 'none';
                
                container.innerHTML = this.products.map(product => `
                    <div class="col-6 col-md-4 col-lg-3 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                ${product.foto_url ? 
                                    `<img src="${product.foto_url}" class="card-img-top product-image" alt="${product.nombre}">` :
                                    `<div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>`
                                }
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title text-truncate" title="${product.nombre}">${product.nombre}</h6>
                                <p class="card-text text-muted small mb-2">
                                    <span class="badge bg-secondary me-1">${product.categoria}</span>
                                    <span class="badge bg-info">${product.marca}</span>
                                </p>
                                ${product.descripcion ? `<p class="card-text small text-muted">${product.descripcion.substring(0, 100)}${product.descripcion.length > 100 ? '...' : ''}</p>` : ''}
                                <div class="mt-auto">
                                    <div class="price-tag text-center">
                                        ${product.precio_venta ? `$${parseFloat(product.precio_venta).toLocaleString()}` : 'Precio no disponible'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
            
            renderPagination() {
                const container = document.getElementById('paginationContainer');
                
                if (this.totalPages <= 1) {
                    container.innerHTML = '';
                    return;
                }
                
                let pagination = '<nav><ul class="pagination">';
                
                // Botón anterior
                pagination += `
                    <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${this.currentPage - 1}">Anterior</a>
                    </li>
                `;
                
                // Páginas
                const startPage = Math.max(1, this.currentPage - 2);
                const endPage = Math.min(this.totalPages, this.currentPage + 2);
                
                if (startPage > 1) {
                    pagination += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        pagination += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    pagination += `
                        <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }
                
                if (endPage < this.totalPages) {
                    if (endPage < this.totalPages - 1) {
                        pagination += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${this.totalPages}">${this.totalPages}</a></li>`;
                }
                
                // Botón siguiente
                pagination += `
                    <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${this.currentPage + 1}">Siguiente</a>
                    </li>
                `;
                
                pagination += '</ul></nav>';
                container.innerHTML = pagination;
                
                // Event listeners para paginación
                container.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = parseInt(e.target.dataset.page);
                        if (page && page !== this.currentPage && page >= 1 && page <= this.totalPages) {
                            this.currentPage = page;
                            this.loadProducts();
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    });
                });
            }
            
            showLoading(show) {
                document.getElementById('loading').style.display = show ? 'block' : 'none';
                document.getElementById('productsContainer').style.display = show ? 'none' : 'block';
            }
            
            showError(message) {
                const container = document.getElementById('productsGrid');
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> ${message}
                        </div>
                    </div>
                `;
            }
            
            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }
        
        // Inicializar la aplicación cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', () => {
            new CatalogoApp();
        });
    </script>
</body>
</html>
