# Multi App v2 – Sistema Integral de Gestión

Sistema modular para gestión de oficinas, procesamiento de datos y administración de usuarios con interfaz web moderna y procesamiento backend en Python.

## 🚀 **Características Principales**

- **UI Modular**: Interfaz web responsive con módulos independientes
- **Procesamiento ETL**: Sistema Python para transformación y carga de datos
- **Worker Automático**: Procesamiento asíncrono de cargas de archivos
- **Sistema de Roles**: Control de acceso granular (admin, oficina)
- **Logging Completo**: Auditoría de todas las operaciones
- **Base de Datos**: MySQL con esquema optimizado para consultas

## 📁 **Estructura del Proyecto**

```
multi_app/
├── ui/                           # 🌐 Interfaz web (PHP)
│   ├── modules/                  # Módulos funcionales
│   │   ├── oficina/             # Gestión de oficina
│   │   ├── cobranza/            # Gestión de cobranza
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

### **Roles Disponibles:**
- **`admin`**: Acceso completo a todos los módulos
- **`oficina`**: Acceso a módulos de oficina y cobranza
- **`usuario`**: Acceso limitado (en desarrollo)

### **Módulos por Rol:**
- **Admin**: Oficina, Cobranza, Usuarios, Logs, Cargas
- **Oficina**: Oficina, Cobranza, Cargas (solo lectura en algunos casos)

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
- **Pagos**: `banco_pse`, `banco_confiar`, `pagos_relacion`
- **Control**: `control_cargas`, `control_asociados`, `control_transaccion`
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
