# 🐍 **Sistema de Procesamiento de Datos - Multi App v2**

Sistema backend completo para procesamiento ETL (Extract, Transform, Load) de datos financieros con worker asíncrono, procesadores especializados y sistema de logging robusto.

## 📁 **Estructura del Proyecto**

```
py/
├── core/                          # 🎯 Módulo central compartido
│   ├── __init__.py
│   ├── database.py               # Conexión BD reutilizable
│   ├── excel_processor.py        # Procesamiento Excel genérico
│   ├── data_cleaner.py          # Limpieza de datos común
│   └── base_processor.py        # Clase base para procesadores
│
├── processors/                   # 🎯 Procesadores específicos
│   ├── __init__.py
│   ├── pagos_processor.py       # Procesador de pagos PSE/Confiar
│   ├── sifone_processor.py      # Procesador de datos Sifone
│   └── pago_relacion_processor.py # Procesador de relaciones automáticas
│
├── config/                      # ⚙️ Configuración centralizada
│   ├── __init__.py
│   ├── settings.py              # Configuración general
│   └── logging_config.py        # Sistema de logging robusto
│
├── utils/                       # 🛠️ Utilidades compartidas
│   ├── __init__.py
│   ├── file_utils.py            # Manejo de archivos
│   └── validation.py            # Validaciones comunes
│
├── data/                        # 📁 Directorios de datos
│   ├── pagos/                   # Archivos de pagos
│   │   ├── confiar/            # Archivos Confiar (.xls)
│   │   └── pse/                # Archivos PSE (.xlsx)
│   └── sifone/                  # Archivos Sifone (.xls/.xlsx)
│
├── main.py                      # 🚀 Punto de entrada completo
├── worker.py                    # ⚙️ Worker de procesamiento asíncrono
├── requirements.txt             # 📦 Dependencias unificadas
└── README.md                    # 📚 Esta documentación
```

## 🚀 **Instalación y Configuración**

### 📋 **Requisitos del Sistema**

- **Python**: 3.10+ (recomendado para funcionalidades modernas)
- **pip**: Gestor de paquetes de Python
- **MySQL/MariaDB**: Base de datos para almacenamiento
- **Sistema Operativo**: Linux (CentOS 7+, Ubuntu 20.04+), Windows 10+, macOS

### 🐧 **Instalación en CentOS**

#### 1. **Actualizar el Sistema**
```bash
sudo yum update -y
sudo yum upgrade -y
```

#### 2. **Instalar Python 3.10+**
```bash
# CentOS 7
sudo yum install python3 python3-pip python3-devel -y

# CentOS 8/Stream
sudo dnf install python3 python3-pip python3-devel -y

# Para Python 3.10+ específicamente
sudo yum install python3.10 python3.10-pip python3.10-devel -y
```

#### 3. **Verificar la Instalación**
```bash
python3 --version
python3.10 --version
pip3 --version
```

#### 4. **Instalar Dependencias del Sistema**
```bash
# Herramientas de desarrollo
sudo yum groupinstall "Development Tools" -y

# Librerías de desarrollo
sudo yum install gcc gcc-c++ make openssl-devel bzip2-devel libffi-devel -y
```

### 🌍 **Instalación en Otros Sistemas**

#### **Ubuntu/Debian**
```bash
sudo apt update
sudo apt install python3 python3-pip python3-venv python3-dev -y
```

#### **Windows**
```bash
# Descargar Python desde python.org
# Instalar con opción "Add to PATH" marcada
# Usar PowerShell para comandos
```

#### **macOS**
```bash
# Con Homebrew
brew install python3

# Con MacPorts
sudo port install python310
```

### 🔧 **Instalación de Dependencias Python**

#### 1. **Crear Entorno Virtual (Recomendado)**
```bash
# Crear entorno virtual
python3 -m venv venv

# Activar entorno virtual
# En Linux/macOS:
source venv/bin/activate

# En Windows:
venv\Scripts\activate
```

#### 2. **Instalar Dependencias**
```bash
# Instalar desde requirements.txt
pip install -r requirements.txt

# O instalar individualmente
pip install pandas==2.1.4
pip install numpy==1.26.2
pip install mysql-connector-python==8.2.0
pip install openpyxl==3.1.2
pip install xlrd==2.0.1
```

## 📦 **Dependencias del Sistema**

### **Dependencias Principales:**
```
pandas==2.1.4          # Procesamiento de datos y DataFrames
numpy==1.26.2          # Operaciones numéricas y arrays
mysql-connector-python==8.2.0  # Conexión a base de datos MySQL
openpyxl==3.1.2        # Lectura de archivos .xlsx
xlrd==2.0.1            # Lectura de archivos .xls
```

### **Dependencias Opcionales:**
```
# Para desarrollo y testing
pytest                 # Framework de testing
black                  # Formateador de código
flake8                 # Linter de código

# Para logging avanzado
python-json-logger     # Logging en formato JSON
```

## ⚙️ **Configuración del Sistema**

### **Archivo de Configuración (`config/settings.py`)**
```python
# Configuración de base de datos
DATABASE_CONFIG = {
    'host': 'localhost',
    'user': 'usuario',
    'password': 'contraseña',
    'database': 'multiapptwo',
    'charset': 'utf8mb4',
    'autocommit': True
}

# Configuración de logging
LOGGING_CONFIG = {
    'level': 'INFO',
    'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    'file': 'logs/app.log'
}

# Configuración de archivos
FILE_CONFIG = {
    'max_size': 100 * 1024 * 1024,  # 100MB
    'allowed_extensions': ['.xls', '.xlsx'],
    'temp_dir': 'temp/'
}
```

### **Configuración de Logging (`config/logging_config.py`)**
```python
# Configuración robusta con fallbacks
from config.logging_config import setup_logging, quick_setup

# Configuración básica
logger = setup_logging()

# Configuración con archivo
logger = quick_setup(
    level='DEBUG',
    log_file='logs/worker.log'
)
```

## 🔄 **Sistema de Worker**

### **Funcionalidades del Worker (`worker.py`)**
- **Procesamiento asíncrono**: Jobs en cola con estados
- **Modos de operación**: Run-once o daemon continuo
- **Manejo de errores**: Logging detallado y recuperación
- **Monitoreo en tiempo real**: Estados y progreso de jobs

### **Comandos del Worker**
```bash
# Procesamiento único (drena todos los pendientes)
python3 worker.py --run-once

# Procesamiento continuo cada 15 segundos (por defecto)
python3 worker.py

# Procesamiento personalizado cada 30 segundos
python3 worker.py --interval 30

# Ayuda y opciones
python3 worker.py --help
```

### **Estados de Jobs**
1. **`pendiente`**: Job creado, esperando procesamiento
2. **`procesando`**: Job en ejecución
3. **`completado`**: Job procesado exitosamente
4. **`error`**: Error durante el procesamiento

### **Tipos de Jobs Soportados**
- **Sifone**: `sifone_libro`, `sifone_cartera_mora`, `sifone_cartera_aseguradora`
- **Pagos**: `pagos_pse`, `pagos_confiar`

## 📊 **Procesadores de Datos**

### **Procesador Sifone (`processors/sifone_processor.py`)**
```python
# Procesamiento de archivos Sifone
with SifoneProcessor() as sp:
    # Procesar libro de asociados
    data = sp.process_asociados_file('archivo.xlsx')
    
    # Truncar tabla y insertar nuevos datos
    sp.truncate_table('sifone_asociados')
    sp.insert_data('sifone_asociados', data)
    
    # Insertar control de asociados
    sp.insert_control_asociados()
```

### **Procesador de Pagos (`processors/pagos_processor.py`)**
```python
# Procesamiento de archivos de pagos
with PagosProcessor() as pp:
    # Procesar archivo PSE
    data = pp.process_pse_file('pagos_pse.xlsx')
    pp.insert_data('banco_pse', data, check_duplicates=True)
    
    # Procesar archivo Confiar
    data = pp.process_confiar_file('pagos_confiar.xls')
    pp.insert_data('banco_confiar', data, check_duplicates=True)
```

### **Procesador de Relaciones (`processors/pago_relacion_processor.py`)**
```python
# Creación automática de relaciones PSE-Confiar
with PagoRelacionProcessor() as prp:
    # Procesar relaciones automáticas
    prp.process_automatic_relations()
    
    # Generar reporte de relaciones
    report = prp.generate_relation_report()
```

## 🧹 **Limpieza y Validación de Datos**

### **Limpieza de Cédulas (`core/data_cleaner.py`)**
```python
# Preservar formato original de cédulas
def clean_cedula_field(self, value: Any) -> str:
    """Limpiar campo de cédula preservando formato original"""
    if pd.isna(value) or value is None:
        return ""
    
    # Convertir a string y limpiar espacios
    cedula = str(value).strip()
    
    # Preservar formato original - no eliminar ceros previos
    # Solo limpiar espacios y caracteres no válidos
    cedula = re.sub(r'[^\d\-]', '', cedula)
    
    return cedula
```

### **Procesamiento de Excel (`core/excel_processor.py`)**
```python
# Lectura con preservación de tipos
df = excel_processor.read_excel_file(
    file_path='archivo.xlsx',
    preserve_cedula=True,  # Preservar cédulas como texto
    header_row=0
)

# Asegurar columnas de cédula como string
df = excel_processor._ensure_cedula_columns_as_string(df)
```

## 🗄️ **Gestión de Base de Datos**

### **Conexión a Base de Datos (`core/database.py`)**
```python
# Gestor de conexiones
db = DatabaseManager()

# Obtener conexión
conn = db.get_connection()

# Ejecutar consultas
cursor = conn.cursor(dictionary=True)
cursor.execute("SELECT * FROM control_cargas WHERE estado='pendiente'")
jobs = cursor.fetchall()

# Cerrar conexión
cursor.close()
```

### **Operaciones Comunes**
```python
# Insertar datos
db.insert_data('tabla', data, check_duplicates=True)

# Actualizar datos
db.update_data('tabla', {'campo': 'valor'}, {'id': 1})

# Eliminar datos
db.delete_data('tabla', {'id': 1})

# Consultar datos
data = db.query_data('SELECT * FROM tabla WHERE campo = %s', ('valor',))
```

## 📝 **Sistema de Logging**

### **Configuración de Logging**
```python
# Logging básico
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

# Logging avanzado con archivos
from config.logging_config import configure_file_logging

configure_file_logging(
    log_file='logs/worker.log',
    max_bytes=10*1024*1024,  # 10MB
    backup_count=5
)
```

### **Niveles de Logging**
- **DEBUG**: Información detallada para desarrollo
- **INFO**: Información general del sistema
- **WARNING**: Advertencias que no impiden funcionamiento
- **ERROR**: Errores que impiden funcionamiento
- **CRITICAL**: Errores críticos del sistema

## 🔍 **Monitoreo y Debugging**

### **Verificación del Sistema**
```bash
# Verificar instalación de Python
python3 --version
pip list

# Verificar conexión a base de datos
python3 -c "from core.database import DatabaseManager; db = DatabaseManager(); print('✅ Conexión exitosa')"

# Verificar procesadores
python3 -c "from processors.sifone_processor import SifoneProcessor; print('✅ Procesadores OK')"

# Verificar logging
python3 -c "import logging; logging.info('✅ Logging funcional')"
```

### **Logs del Worker**
```bash
# Ver logs en tiempo real
tail -f logs/worker.log

# Buscar errores específicos
grep "ERROR" logs/worker.log

# Ver jobs procesados
grep "completado" logs/worker.log
```

## 🚨 **Solución de Problemas**

### **Problemas Comunes**

#### 1. **Error de Conexión a Base de Datos**
```bash
# Verificar que MySQL esté corriendo
sudo systemctl status mysql

# Verificar credenciales en config/settings.py
# Usar host 127.0.0.1 en lugar de localhost si hay problemas
```

#### 2. **Error de Permisos en Archivos**
```bash
# Verificar permisos de directorios
ls -la py/data/
chmod 755 py/data/
chmod 755 py/data/*/

# Verificar usuario del proceso
ps aux | grep python
```

#### 3. **Error de Dependencias**
```bash
# Reinstalar dependencias
pip uninstall -r requirements.txt
pip install -r requirements.txt

# Verificar versiones
pip show pandas
pip show mysql-connector-python
```

#### 4. **Error de Codificación**
```bash
# Verificar codificación del sistema
locale
export LANG=en_US.UTF-8

# Verificar archivos Python
file worker.py
```

### **Logs de Error**
```bash
# Logs del sistema
sudo journalctl -u mysql
sudo journalctl -u apache2

# Logs de Python
python3 -u worker.py 2>&1 | tee worker.log

# Logs de base de datos
sudo tail -f /var/log/mysql/error.log
```

## 🚀 **Despliegue en Producción**

### **Configuración del Worker como Servicio**

#### **Systemd (Linux)**
```ini
# /etc/systemd/system/multi-app-worker.service
[Unit]
Description=Multi App Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/multi_app/py
ExecStart=/usr/bin/python3 worker.py --interval 30
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

#### **Cron (Alternativa)**
```bash
# Ejecutar cada 5 minutos
*/5 * * * * cd /var/www/html/multi_app/py && python3 worker.py --run-once

# Ejecutar cada hora
0 * * * * cd /var/www/html/multi_app/py && python3 worker.py --run-once
```

### **Monitoreo de Producción**
```bash
# Verificar estado del servicio
sudo systemctl status multi-app-worker

# Ver logs del servicio
sudo journalctl -u multi-app-worker -f

# Reiniciar servicio
sudo systemctl restart multi-app-worker
```

## 🔮 **Roadmap y Extensiones**

### **Próximas Funcionalidades**
- **API REST**: Endpoints para integración externa
- **Queue System**: Redis/RabbitMQ para jobs masivos
- **Métricas**: Prometheus/Grafana para monitoreo
- **Cache**: Redis para consultas frecuentes
- **Notificaciones**: Email/SMS para jobs completados

### **Mejoras Técnicas**
- **Async/Await**: Procesamiento asíncrono nativo
- **Type Hints**: Tipado completo del código
- **Testing**: Cobertura completa de tests
- **Docker**: Containerización del sistema
- **CI/CD**: Pipeline de integración continua

## 📚 **Documentación Adicional**

### **Archivos de Referencia**
- **`ddl.sql`**: Esquema completo de base de datos
- **`requirements.txt`**: Dependencias exactas del sistema
- **`worker.py`**: Código fuente del worker principal
- **`config/settings.py`**: Configuración del sistema

### **Comandos Útiles**
```bash
# Verificar estructura del proyecto
tree py/ -I "__pycache__|*.pyc"

# Verificar dependencias
pip list --format=freeze

# Ejecutar worker en modo debug
python3 -u worker.py --run-once 2>&1 | tee debug.log

# Verificar logs del sistema
sudo journalctl -f
```

---

**Sistema de Procesamiento de Datos Multi App v2** - Backend robusto y escalable para ETL de datos financieros.

*Desarrollado con Python 3.10+, pandas, MySQL y tecnologías modernas de procesamiento de datos.* 