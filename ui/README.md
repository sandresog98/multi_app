## Plantillas de descarga

- Guarde archivos de referencia/plantillas descargables en `ui/assets/plantillas/`.
- Ejemplo: `ui/assets/plantillas/pse_plantilla.xls` se publica como `BASE_URL/assets/plantillas/pse_plantilla.xls` y se enlaza desde la UI.

# 🌐 **UI Multi App v2 - Interfaz Web Completa**

Sistema de interfaz web modular para gestión de oficinas, cobranza, usuarios y auditoría con autenticación robusta y control de acceso granular.

## 🏗️ **Arquitectura del Sistema**

### **Estructura de Carpetas:**
```
ui/
├── config/                       # ⚙️ Configuración del sistema
│   ├── database.php             # Conexión a base de datos
│   └── paths.php                # URLs y rutas del sistema
├── controllers/                  # 🎮 Controladores principales
│   └── AuthController.php       # Autenticación y autorización
├── models/                       # 📊 Modelos de datos
│   ├── Logger.php               # Sistema de logging
│   └── User.php                 # Gestión de usuarios
├── modules/                      # 🧩 Módulos funcionales
│   ├── oficina/                 # Gestión completa de oficina
│   ├── cobranza/                # Sistema de cobranza
│   ├── boleteria/               # Venta y administración de boletas
│   ├── creditos/                # Gestión de solicitudes de crédito
│   ├── usuarios/                # Administración de usuarios
│   └── logs/                    # Auditoría del sistema
├── pages/                        # 📄 Páginas principales
│   └── dashboard.php            # Dashboard principal
├── views/                        # 🎨 Plantillas y layouts
│   └── layouts/                 # Layouts del sistema
│       ├── header.php           # Encabezado común
│       ├── sidebar.php          # Navegación lateral
│       └── footer.php           # Pie de página
├── assets/                       # 🎯 Recursos estáticos
│   ├── img/                     # Imágenes del sistema
│   │   └── logo.png            # Logo principal
│   └── favicons/                # Iconos del navegador
│       └── favicon.ico         # Favicon principal
├── uploads/                      # 📁 Archivos subidos por usuarios
│   └── recibos/                 # Recibos y comprobantes
├── index.php                     # 🏠 Página principal
├── login.php                     # 🔐 Página de autenticación
└── logout.php                    # 🚪 Cierre de sesión
```

## 🔐 **Sistema de Autenticación y Autorización**

### **Controlador de Autenticación (`AuthController.php`):**
- **Sesión aislada**: `multiapptwo_session` (independiente de v1)
- **Control de acceso**: Verificación de roles y permisos
- **Métodos principales**:
  - `requireRole($role)`: Acceso exclusivo a un rol específico
  - `requireAnyRole($roles)`: Acceso a cualquiera de los roles especificados
  - `isAuthenticated()`: Verificación de sesión activa
  - `getCurrentUser()`: Obtener usuario actual

### **Roles del Sistema (roles.json):**
- **`admin`**: acceso total
- **`lider`**: acceso amplio (con restricciones para acciones críticas como eliminar admins)
- **`oficina`**: Oficina, Boletería, Cobranza y Créditos (sin aprobar/rechazar)
- **`tienda`**: Oficina (Resumen, Asociados, Informaciones) + Tienda completa (Catálogo, Compras, Inventario, Ventas, Reversiones, Facturación)

### **Módulos por Rol:**
| Módulo | Admin | Oficina | Usuario |
|--------|-------|---------|---------|
| **Oficina** | ✅ Completo | ✅ Completo | ❌ |
| **Cobranza** | ✅ Completo | ✅ Completo | ❌ |
| **Usuarios** | ✅ Completo | ❌ | ❌ |
| **Logs** | ✅ Completo | ❌ | ❌ |
| **Dashboard** | ✅ Completo | ✅ Completo | ✅ Básico |

## 🧭 **Sistema de Navegación**

### **Sidebar Principal:**
- **Acordeones inteligentes**: Mantienen abierto el módulo activo
- **Navegación contextual**: Enlaces directos a funciones principales
- **Indicadores visuales**: Muestra el módulo y página actual

### **Estructura de Navegación:**
```
🏠 Inicio (Dashboard)
🏢 Oficina (Acordeón)
  ├── 📊 Resumen
  ├── 📦 Productos
  ├── 👥 Asociados
  ├── 💳 Pagos PSE
  ├── 💰 Pagos Cash/QR
  ├── 🔄 Transacciones
  ├── 📋 Trx List
  └── 📤 Cargas
💰 Cobranza (Acordeón)
  └── 📞 Comunicaciones
👥 Usuarios
📝 Logs
🛒 Tienda (Acordeón)
  ├── 🏷️ Catálogo (Productos, Marcas, Categorías)
  ├── 🚚 Compras (ingreso a inventario)
  ├── 📦 Inventario (con detalle por lotes e IMEIs)
  ├── 🛍️ Ventas (asociados o clientes externos)
  ├── 📄 Facturación (lista y detalle de ventas)
  └── 🔁 Reversiones (gestión de devoluciones)
```

## 🏢 **Módulo Oficina**

### **Dashboard Principal (`index.php`):**
- **KPIs en tiempo real**: Métricas actualizadas automáticamente
- **Estado de cargas**: Monitoreo de archivos procesados
- **Logs recientes**: Últimas actividades del sistema
- **Resumen financiero**: Pagos, transacciones y asignaciones

### **Gestión de Productos (`productos.php`):**
- **CRUD completo**: Crear, leer, actualizar productos
- **Parámetros configurables**: Campos personalizables por producto
- **Validación de rangos**: Valores mínimo y máximo con validación
- **Estado activo/inactivo**: Control de disponibilidad

### **Gestión de Asociados:**
- **Lista de asociados** (`asociados.php`):
  - Filtros por estado (activo/inactivo)
  - Búsqueda por cédula, nombre, email
  - Acciones: activar/inactivar, ver detalle
- **Detalle de asociado** (`asociados_detalle.php`):
  - Información personal completa
  - Estado financiero y crediticio
  - Productos asignados
  - Historial de transacciones

### **Sistema de Pagos:**
- **Pagos PSE** (`pagos_pse.php`):
  - Lista de pagos PSE recibidos
  - Asignación manual a Confiar
  - Validación de capacidad y duplicados
  - Filtros avanzados por fecha y referencia
- **Pagos Cash/QR** (`pagos_cash_qr.php`):
  - Confirmación de pagos en efectivo
  - Gestión de comprobantes QR
  - Asignación automática por cédula

### **Sistema de Transacciones:**
- **Creación de transacciones** (`transacciones.php`):
  - Búsqueda inteligente de asociados
  - Priorización automática de rubros
  - Selección de pagos disponibles
  - Validación de montos y capacidad
- **Lista de transacciones** (`trx_list.php`):
  - Vista consolidada de todas las transacciones
  - Filtros por fecha, tipo y estado
  - Acciones: ver detalle, eliminar

### **Sistema de Cargas:**
- **Subida de archivos** (`cargas.php`):
  - Formularios por tipo de archivo
  - Validación de formatos (.xls, .xlsx)
  - Creación automática de jobs
  - Monitoreo en tiempo real
- **Tipos soportados**:
  - **Sifone**: Libro, Cartera Mora, Cartera Aseguradora
  - **Pagos**: PSE, Confiar

## 💰 **Módulo Cobranza**
## 🎟️ **Módulo Boletería**

- Categorías y boletas; estados: disponible, vendida, anulada
- Venta con búsqueda de asociado, método de venta y comprobante
- Subida de archivo en la boleta (JPG/JPEG/PNG/PDF) y vista/descarga

## 🧾 **Módulo Gestión Créditos**

- Solicitudes: formulario dinámico por tipo (Dependiente/Independiente)
- Adjuntos requeridos (PDF/JPG/PNG, 5MB máx.) según tipo y etapa
- Estados y flujo: Creado → Con Datacrédito → (Aprobado/Rechazado) → Con Estudio → Guardado
- Listado con acciones por etapa y “Ver detalle” con historial
- Resumen con tablas + mini gráficos por tipo y estado
- Auditoría en `creditos_historial` y `control_logs`

### **Sistema de Comunicaciones:**
- **Historial de comunicaciones**: Por asociado y por estado
- **Creación de comunicaciones**: Nuevas entradas con validación
- **Edición de comunicaciones**: Modificación de registros existentes
- **Eliminación de comunicaciones**: Con confirmación y auditoría

### **Estados de Mora:**
- **Clasificación automática**: Según días de vencimiento
- **Estados disponibles**:
  - **Persuasiva**: Comunicaciones iniciales
  - **Prejurídico**: Advertencias formales
  - **Jurídico**: Proceso legal iniciado

### **Filtros y Búsqueda:**
- **Por asociado**: Cédula, nombre, email
- **Por estado**: Tipo de comunicación
- **Por fecha**: Rango de fechas de comunicación
- **Por prioridad**: Nivel de urgencia

## 👥 **Módulo Usuarios**

### **Gestión de Usuarios:**
- **Lista de usuarios**: Con filtros y búsqueda
- **Creación de usuarios**: Con validación de roles
- **Edición de usuarios**: Modificación de datos y permisos
- **Eliminación de usuarios**: Con confirmación y auditoría

### **Control de Acceso:**
- **Asignación de roles**: Admin, Oficina, Usuario
- **Permisos granulares**: Por módulo y función
- **Auditoría de cambios**: Log de modificaciones

## 📝 **Módulo Logs**

### **Sistema de Auditoría:**
- **Control de logs**: Todas las operaciones del sistema
- **Información registrada**:
  - Usuario que realizó la acción
  - Timestamp de la operación
  - Datos antes y después del cambio
  - IP y agente del usuario
  - Contexto de la operación

### **Funcionalidades:**
- **Lista de logs**: Con filtros y paginación
- **Ver detalle**: Información completa de cada operación
- **Exportación**: Filtros personalizables
- **Búsqueda**: Por usuario, fecha, tipo de operación

## 🎨 **Personalización y Estilos**

### **Temas y Colores:**
- **Configuración en `header.php`**: Bloque `<style>` personalizable
- **Variables CSS**: Colores principales del sistema
- **Responsive design**: Adaptable a diferentes dispositivos

### **Logo y Favicons:**
- **Logo principal**: `assets/img/logo.png`
- **Favicons**: `assets/favicons/favicon.ico`
- **Soporte multi-resolución**: Para diferentes dispositivos

## 🔧 **Configuración del Sistema**

### **Archivo de Base de Datos (`config/database.php`):**
```php
<?php
return [
    'host' => 'localhost',
    'database' => 'multiapptwo',
    'username' => 'usuario',
    'password' => 'contraseña',
    'charset' => 'utf8mb4'
];
?>
```

### **Archivo de Rutas (`config/paths.php`):**
```php
<?php
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}
?>
```

## 🚀 **Despliegue y Configuración**

### **Requisitos del Sistema:**
- **Servidor Web**: Apache 2.4+ o Nginx 1.18+
- **PHP**: 8.0+ con extensiones requeridas
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.5+
- **Sistema Operativo**: Linux, Windows, macOS

### **Extensiones PHP Requeridas:**
- `mysqli` o `pdo_mysql`
- `json`
- `session`
- `mbstring`
- `fileinfo`

### **Configuración del Servidor Web:**
```apache
# Apache (.htaccess)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### **Permisos de Archivos:**
```bash
# Directorios de uploads
chmod 755 ui/uploads/
chmod 755 ui/uploads/tienda/
chmod 755 ui/uploads/creditos/

# Archivos de configuración
chmod 644 ui/config/*.php
chmod 644 ui/controllers/*.php
```

### **Acceso web a archivos subidos**
Para evitar errores 403 en imágenes/adjuntos, existe `ui/uploads/.htaccess` con permisos abiertos de lectura.

---

## 🛒 Módulo Tienda

### Catálogo
- Gestión de Categorías, Marcas y Productos (con foto PNG/JPG/JPEG máx. 2MB).
- Filtros en Productos: categoría, marca, nombre y rango de precio.
- Formularios de crear/editar en modales flotantes.
- Nota: Eliminación deshabilitada por política para categorías, marcas y productos.

### Compras (Ingreso a inventario)
- Registrar ítems con cantidad, precio de compra y precio de venta sugerido.
- Para categoría "Celulares" se exige IMEI por unidad (único).
- Tras guardar, la página se recarga para ver la compra en “Compras recientes”.
- Eliminación de compra solo si no genera stock negativo y sin IMEIs vendidos.

### Inventario
- Vista agregada: ingresado, vendido y disponible por producto.
- Filtros por categoría, marca y nombre; muestra miniatura de foto si existe.
- Detalle por producto: lotes (precios), IMEIs disponibles y reversiones asociadas (incluye IMEI, precios y si fue revendido).

### Ventas
- Cliente: asociado (búsqueda por cédula/nombre) o cliente externo (autocomplete).
- Selección de producto en modal; para “Celulares” selección de IMEIs disponibles.
- Precio unitario editable; muestra referencia “Compra aprox” y Stock.
- Validación de stock en cliente y servidor (incluye IMEIs).
- Pagos múltiples: Efectivo, Bold, QR, Crédito SIFONE (número obligatorio), Reversión (requiere pago anterior).
- Totales: se muestran Total de productos y Total pagos; el botón “Registrar venta” se habilita solo cuando coinciden y Total > 0.

### Reversiones
- Búsqueda de ventas, selección de producto, registro de motivo y si puede revenderse.
- Bloquea reversiones duplicadas sobre el mismo detalle de venta.
- El detalle se refleja en Inventario y Facturación (bandera “Reversado”).

### Facturación
- Lista de ventas con detalle (productos, IMEIs, precios, pagos, ganancia).
- Eliminación bloqueada cuando:
  - La venta tiene reversiones asociadas, o
  - Alguno de sus pagos fue usado como “pago anterior” en otra venta.
- La UI muestra “Bloqueada” en lugar del botón Eliminar cuando corresponda.

### Clientes (externos)
- CRUD en modal; autocomplete en Ventas.
- Eliminación bloqueada si el cliente tiene ventas asociadas. La UI muestra “Bloqueado”.

---

## 🔑 Permisos y Sidebar
- El Sidebar muestra solo los enlaces permitidos según `roles.json`.
- El encabezado de cada acordeón (Oficina, Tienda, Créditos, etc.) solo se muestra si el rol tiene acceso a ese módulo o a alguna de sus páginas hijas.
- Los permisos se recargan automáticamente si cambia el rol del usuario o si se modifica `roles.json` (sin necesidad de cerrar sesión).

## 🔍 **Solución de Problemas**

### **Problemas Comunes:**
1. **Sesión no persiste**: Verificar configuración de PHP y permisos
2. **Acceso denegado**: Verificar rol del usuario y permisos
3. **Errores de base de datos**: Verificar conexión y esquema
4. **Archivos no se suben**: Verificar permisos de directorios

### **Logs de Error:**
- **PHP errors**: Verificar `error_log` del servidor
- **MySQL errors**: Verificar logs de base de datos
- **Sistema**: Verificar logs del servidor web

## 🔮 **Roadmap y Extensiones**

### **Próximas Funcionalidades:**
- **Dashboard interactivo**: Gráficas y métricas en tiempo real
- **Notificaciones**: Sistema de alertas por email/SMS
- **API REST**: Endpoints para integración externa
- **Módulos adicionales**: Beneficios, FAU, Tienda

### **Mejoras Técnicas:**
- **Cache Redis**: Para consultas frecuentes
- **Queue System**: Para procesamiento asíncrono
- **Microservicios**: Arquitectura distribuida
- **Docker**: Containerización completa

## 📚 **Documentación Adicional**

- **README Principal**: Visión general del sistema completo
- **README Python**: Documentación del backend ETL
- **Comentarios en código**: Documentación inline detallada
- **Esquema de BD**: Estructura completa en `ddl.sql`

---

**UI Multi App v2** - Interfaz web moderna y responsive para gestión integral de oficinas.

*Desarrollado con PHP 8+, MySQL 8.0+ y tecnologías web modernas.*
