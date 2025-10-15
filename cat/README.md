# Catálogo de Productos - Cooperativa Multiunión

## Descripción
Catálogo público de productos de la tienda de la Cooperativa Multiunión, optimizado para dispositivos móviles.

## Características
- ✅ **Diseño móvil**: Optimizado para celulares y tablets
- ✅ **Bootstrap 5**: Interfaz moderna y responsive
- ✅ **Filtros avanzados**: Por categoría, marca, nombre y precio
- ✅ **Paginación**: Manejo eficiente de grandes cantidades de productos
- ✅ **Ordenamiento**: Por nombre, precio, categoría o marca
- ✅ **Búsqueda en tiempo real**: Filtrado instantáneo por nombre
- ✅ **Sin autenticación**: Acceso público sin login
- ✅ **API RESTful**: Endpoints para obtener datos

## Estructura del proyecto
```
cat/
├── config/
│   ├── database.php      # Configuración de base de datos
│   └── paths.php         # Configuración de rutas
├── models/
│   └── CatalogoProducto.php  # Modelo para productos
├── api/
│   ├── productos.php     # API para obtener productos
│   ├── categorias.php    # API para obtener categorías
│   └── marcas.php        # API para obtener marcas
├── assets/               # Recursos estáticos
├── index.php            # Página principal del catálogo
└── README.md            # Este archivo
```

## Funcionalidades

### Filtros disponibles
- **Búsqueda por nombre**: Filtro de texto en tiempo real
- **Categoría**: Dropdown con todas las categorías activas
- **Marca**: Dropdown con todas las marcas activas
- **Rango de precio**: Precio mínimo y máximo
- **Ordenamiento**: Por nombre, precio, categoría o marca (ascendente/descendente)

### Características móviles
- **Offcanvas para filtros**: Panel deslizable para filtros en móviles
- **Grid responsive**: 2 columnas en móvil, 3 en tablet, 4 en desktop
- **Navegación sticky**: Header fijo para fácil navegación
- **Botones táctiles**: Optimizados para touch
- **Carga rápida**: Solo productos activos, paginación eficiente

### API Endpoints

#### GET /api/productos.php
Obtiene productos con filtros y paginación.

**Parámetros:**
- `pagina` (int): Número de página (default: 1)
- `por_pagina` (int): Productos por página (default: 12, max: 50)
- `ordenar` (string): Campo de ordenamiento (nombre, precio, categoria, marca)
- `direccion` (string): Dirección del ordenamiento (ASC, DESC)
- `categoria_id` (int): ID de categoría
- `marca_id` (int): ID de marca
- `nombre` (string): Búsqueda por nombre
- `precio_min` (float): Precio mínimo
- `precio_max` (float): Precio máximo

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "productos": [...],
    "total": 150,
    "pagina": 1,
    "por_pagina": 12,
    "total_paginas": 13
  }
}
```

#### GET /api/categorias.php
Obtiene todas las categorías activas.

#### GET /api/marcas.php
Obtiene todas las marcas activas.

## Instalación

1. **Requisitos:**
   - PHP 7.4+
   - MySQL/MariaDB
   - Servidor web (Apache/Nginx)

2. **Configuración de base de datos:**
   - Editar `config/database.php` con los datos de conexión
   - Asegurar que las tablas `tienda_categoria`, `tienda_marca` y `tienda_producto` existan

3. **Acceso:**
   - Navegar a `http://tu-servidor/cat/`
   - El catálogo estará disponible públicamente

## Uso

### Para usuarios finales:
1. Abrir el catálogo en el navegador móvil
2. Usar el botón "Filtros" para acceder a las opciones de búsqueda
3. Aplicar filtros según necesidades
4. Navegar entre páginas usando la paginación
5. Ordenar productos según preferencia

### Para desarrolladores:
- Las APIs están disponibles para integración con otros sistemas
- Los endpoints devuelven JSON con estructura consistente
- Manejo de errores incluido en todas las respuestas

## Tecnologías utilizadas
- **Backend**: PHP 7.4+
- **Base de datos**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework CSS**: Bootstrap 5.3.0
- **Iconos**: Bootstrap Icons
- **Patrón**: MVC (Modelo-Vista-Controlador)

## Notas técnicas
- Solo se muestran productos con `estado_activo = 1`
- Las imágenes se cargan desde `foto_url` o se muestra placeholder
- La paginación está limitada a máximo 50 productos por página
- Los filtros se aplican en tiempo real para búsqueda por nombre
- Compatible con navegadores modernos (Chrome, Firefox, Safari, Edge)
