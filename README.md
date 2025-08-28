# Multi App v2 – Sistema Integral de Gestión

Sistema modular para gestión de oficinas, procesamiento de datos y administración de usuarios con interfaz web moderna y procesamiento backend en Python.

## 🚀 **Características Principales**

- **UI Modular**: Interfaz web responsive con módulos independientes (Oficina, Cobranza, Boletería, Gestión Créditos, Usuarios, Logs)
- **Procesamiento ETL**: Sistema Python para transformación y carga de datos
- **Worker Automático**: Procesamiento asíncrono de cargas de archivos
- **Sistema de Roles**: Control de acceso granular (admin, lider, oficina) gestionado por `roles.json`
- **Logging Completo**: Auditoría de todas las operaciones
- **Base de Datos**: MySQL con esquema optimizado para consultas

## 📁 **Estructura del Proyecto**

```
multi_app/
├── ui/                           # 🌐 Interfaz web (PHP)
│   ├── modules/                  # Módulos funcionales
│   │   ├── oficina/             # Gestión de oficina
│   │   ├── cobranza/            # Gestión de cobranza
│   │   ├── boleteria/           # Venta y administración de boletas
│   │   ├── creditos/            # Gestión de solicitudes de crédito
│   │   ├── usuarios/            # Administración de usuarios
│   │   └── logs/                # Sistema de auditoría
│   ├── controllers/             # Controladores de autenticación
│   ├── models/                  # Modelos de datos
│   └── views/                   # Plantillas y layouts
├── py/                          # 🐍 Backend Python (ETL)
│   ├── core/                    # Módulos centrales
│   ├── processors/              # Procesadores de datos
│   ├── worker.py                # Worker de procesamiento
│   └── requirements.txt         # Dependencias Python
├── ddl.sql                      # 🗄️ Esquema de base de datos
└── .gitignore                   # Configuración Git
```

## 🔐 **Sistema de Autenticación y Roles**

### **Roles y permisos (roles.json):**
- **`admin`**: acceso total
- **`lider`**: acceso total excepto restricciones puntuales de seguridad
- **`oficina`**: acceso a Oficina, Boletería, Cobranza y Gestión Créditos (sin aprobar/rechazar créditos)

Los permisos de navegación/ API se resuelven por prefijos de módulo definidos en `roles.json` (ej. `creditos` habilita `creditos.*`).

## 🎟️ **Módulo Boletería**

- Categorías y Boletas con estados: disponible, vendida, anulada
- Flujo de venta con búsqueda de asociado, método de venta y comprobante opcional
- Subida de archivo por boleta (JPG/JPEG/PNG/PDF) con vista/descarga
- Logs de creación/edición y acciones

## 🧾 **Módulo Gestión Créditos**

- Páginas: Resumen, Solicitudes (crear), Listado (gestión)
- Solicitud: datos del solicitante, monto deseado, tipo (Dependiente/Independiente) y adjuntos requeridos según tipo
- Estados: Creado → Con Datacrédito → (Aprobado/Rechazado) → Con Estudio → Guardado
- Cambios que requieren adjuntos se realizan vía modal (PDF ≤ 5MB) y quedan auditados
- Historial por solicitud (`creditos_historial`) y logs de sistema
- Resumen con tablas + mini gráficos por tipo y por estado
- Restricción: solo `admin`/`lider` pueden Aprobar/Rechazar

## 🏢 **Módulo Oficina**

### **Funcionalidades Principales:**
- **Dashboard**: KPIs en tiempo real, estado de cargas, logs recientes
- **Productos**: Gestión de productos financieros con parámetros configurables
- **Asociados**: CRUD completo con asignación de productos
- **Pagos PSE**: Gestión y asignación de pagos PSE a Confiar
- **Pagos Cash/QR**: Confirmación de pagos en efectivo y QR
- **Transacciones**: Sistema de asignación automática con prioridades
- **Cargas**: Subida de archivos y monitoreo de procesamiento

### **KPIs del Dashboard:**
- Asociados activos/inactivos
- Productos activos
- Asignaciones activas
- Pagos PSE sin asignar
- Transacciones del día
- Estado de cargas recientes

## 💰 **Módulo Cobranza**

### **Funcionalidades:**
- **Comunicaciones**: Historial y gestión de comunicaciones por asociado
- **Estados de Mora**: Clasificación automática (persuasiva, prejurídico, jurídico)
- **Filtros Avanzados**: Por estado, rango de comunicación, búsqueda
- **Acceso Universal**: Disponible para roles admin y oficina

## 📊 **Sistema de Procesamiento (Python)**

### **Componentes:**
- **Worker**: Procesamiento asíncrono de jobs (`worker.py`)
- **Procesadores**: Especializados por tipo de dato (Sifone, Pagos)
- **Limpieza de Datos**: Normalización automática de cédulas y campos
- **Relaciones Automáticas**: Creación inteligente de relaciones PSE-Confiar

### **Tipos de Archivos Soportados:**
- **Sifone**: `.xls`, `.xlsx` (asociados, cartera, aseguradora)
- **Pagos**: `.xls` (Confiar), `.xlsx` (PSE)
- **Procesamiento**: Automático con validación y limpieza

### **Estados de Procesamiento:**
- `pendiente` → `procesando` → `completado`/`error`
- Logs detallados en `mensaje_log`
- Monitoreo en tiempo real desde la UI

## 🗄️ **Base de Datos**

### **Tablas Principales:**
- **Sifone**: `sifone_asociados`, `sifone_cartera_mora`, `sifone_cartera_aseguradora`
- **Pagos**: `banco_pse`, `banco_confiar`, `control_transaccion`, `control_transaccion_detalle`
- **Créditos**: `creditos_solicitudes`, `creditos_historial`
- **Control**: `control_cargas`, `control_asociados`
- **Auditoría**: `control_logs`, `control_usuarios`

### **Características:**
- Esquema normalizado para consultas eficientes
- Índices optimizados para búsquedas frecuentes
- Triggers para auditoría automática
- Relaciones referenciales integridad de datos

## 🔧 **Instalación y Configuración**

### **Requisitos del Sistema:**
- **Servidor Web**: Apache/Nginx con PHP 8.0+
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.5+
- **Python**: 3.10+ (recomendado para funcionalidades modernas)
- **Sistema Operativo**: Linux (CentOS 7+, Ubuntu 20.04+), Windows 10+

### **Pasos de Instalación:**
1. **Clonar repositorio** y configurar servidor web
2. **Importar esquema** de base de datos (`ddl.sql`)
3. **Configurar conexión** a base de datos
4. **Instalar dependencias** Python (`pip install -r requirements.txt`)
5. **Configurar worker** para procesamiento automático

### **Configuración del Worker:**
```bash
# Procesamiento único
python3 worker.py --run-once

# Procesamiento continuo (cada 15 segundos)
python3 worker.py --interval 15

# Procesamiento personalizado
python3 worker.py --interval 30
```

## 📝 **Logging y Auditoría**

### **Sistema de Logs:**
- **Control Logs**: Todas las operaciones CRUD
- **Logs de Carga**: Estado y resultados de procesamiento
- **Logs de Usuario**: Login, logout, cambios de sesión
- **Logs de Sistema**: Errores y eventos del worker

### **Información Registrada:**
- Usuario que realizó la acción
- Timestamp de la operación
- Datos antes y después del cambio
- IP y agente del usuario
- Contexto de la operación

## 🚨 **Solución de Problemas Comunes**

### **Problemas de Carga:**
- **Archivos no se mueven**: Verificar permisos en `py/data/`
- **Worker no procesa**: Verificar conexión a base de datos
- **Errores de formato**: Verificar estructura de archivos Excel

### **Problemas de UI:**
- **Sesión expira**: Verificar configuración de PHP
- **Acceso denegado**: Verificar rol del usuario
- **Datos no aparecen**: Verificar que se ejecutó el worker
 - **Subidas fallan**: Validar permisos en `ui/uploads/` y tipos/size (PDF/JPG/PNG ≤ 5MB)

### **Problemas de Base de Datos:**
- **Conexión falla**: Verificar host, usuario y contraseña
- **Tablas no existen**: Ejecutar `ddl.sql`
- **Permisos insuficientes**: Verificar privilegios del usuario MySQL

## 🔮 **Roadmap y Extensiones**

### **Próximas Funcionalidades:**
- **Gráficas Interactivas**: Dashboard con visualizaciones
- **Reportes Avanzados**: Exportación a PDF/Excel
- **API REST**: Endpoints para integración externa
- **Notificaciones**: Sistema de alertas por email/SMS

### **Mejoras Técnicas:**
- **Cache Redis**: Para consultas frecuentes
- **Queue System**: Para procesamiento masivo
- **Microservicios**: Arquitectura distribuida
- **Docker**: Containerización completa

## 📚 **Documentación Adicional**

- **`ui/README.md`**: Guía completa de la interfaz web
- **`py/README.md`**: Documentación del sistema Python
- **`ddl.sql`**: Esquema completo de base de datos
- **Comentarios en código**: Documentación inline

## 🤝 **Soporte y Contribución**

### **Para Reportar Problemas:**
1. Verificar logs del sistema
2. Revisar documentación relevante
3. Crear issue con detalles del problema
4. Incluir logs de error y pasos para reproducir

### **Para Contribuir:**
1. Fork del repositorio
2. Crear rama para nueva funcionalidad
3. Implementar cambios con tests
4. Crear Pull Request con descripción detallada

---

**Multi App v2** - Sistema integral para gestión de oficinas y procesamiento de datos financieros.

*Desarrollado con PHP 8+, Python 3.10+, MySQL 8.0+ y tecnologías web modernas.*
