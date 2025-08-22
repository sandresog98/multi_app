# Sistema de Procesamiento de Datos - Multi App (v2)

## 📁 Estructura del Proyecto

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
│   ├── pagos_processor.py       # Procesador de pagos
│   ├── sifone_processor.py      # Procesador de sifone
│   └── pago_relacion_processor.py # Procesador de relaciones automáticas
│
├── data/
│   ├── pagos/
│   │   ├── confiar/
│   │   └── pse/
│   └── sifone/
│
├── config/                      # 🎯 Configuración centralizada
│   ├── __init__.py
│   ├── settings.py              # Configuración general
│   ├── logging_config.py        # Configuración de logging
│   └── centos_config.py         # Configuración específica para CentOS
│
├── utils/                       # Utilidades compartidas
│   ├── __init__.py
│   ├── file_utils.py            # Manejo de archivos
│   └── validation.py            # Validaciones comunes
│
├── main.py                      # Punto de entrada completo
├── worker.py                    # Worker que procesa control_cargas (llamado desde UI)
├── requirements.txt             # Dependencias unificadas
└── test_logging.py              # Script de pruebas para validar sistema
```

## 🚀 Instalación

### 📋 Requisitos del Sistema

- **Python 3.6+** (recomendado Python 3.8+)
- **pip** (gestor de paquetes de Python)
- **MySQL/MariaDB** (para la base de datos)

### 🐧 **Instalación en CentOS**

#### 1. Actualizar el Sistema
```bash
sudo yum update -y
sudo yum upgrade -y
```

#### 2. Instalar Python 3
```bash
# CentOS 7
sudo yum install python3 python3-pip python3-devel -y

# CentOS 8/Stream
sudo dnf install python3 python3-pip python3-devel -y
```

#### 3. Verificar la Instalación
```bash
python3 --version
pip3 --version
```

#### 4. Instalar Dependencias del Sistema
```bash
# Instalar herramientas de desarrollo
sudo yum groupinstall "Development Tools" -y

# Instalar librerías de desarrollo
sudo yum install gcc gcc-c++ make openssl-devel bzip2-devel libffi-devel -y
```

### 🌍 **Instalación en Otros Sistemas**

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install python3 python3-pip python3-venv python3-dev -y
```

#### Windows
```bash
# Descargar Python desde python.org
# Instalar con opción "Add to PATH" marcada
```

### 🔧 **Instalación de Dependencias**

#### 1. Crear Entorno Virtual (Recomendado)
```bash
# Crear directorio para el proyecto
mkdir -p /opt/multi_app
cd /opt/multi_app

# Crear entorno virtual
python3 -m venv venv

# Activar entorno virtual
source venv/bin/activate  # Linux/Mac
# o
venv\Scripts\activate     # Windows
```

#### 2. Instalar Dependencias de Python
```bash
# Asegurarse de que pip esté actualizado
pip3 install --upgrade pip

# Instalar dependencias del proyecto
pip3 install -r requirements.txt
```

## ⚙️ Configuración para CentOS

### 1. Configuración de Logging
El sistema de logging está optimizado para CentOS con:
- Fallback automático a stderr si stdout falla
- Manejo robusto de errores de codificación
- Configuración automática de permisos

### 2. Permisos de Archivos
```bash
# Establecer permisos correctos
sudo chown -R $USER:$USER /opt/multi_app
chmod -R 755 /opt/multi_app
chmod 644 /opt/multi_app/py/*.py
```

### 3. Configuración de Base de Datos
```bash
# Instalar MySQL/MariaDB
sudo yum install mysql-server mysql -y

# Iniciar y habilitar MySQL
sudo systemctl start mysqld
sudo systemctl enable mysqld

# Configurar MySQL (primer inicio)
sudo mysql_secure_installation
```

## 📋 Uso

### Procesamiento Completo
```bash
python3 main.py
```

### Procesamiento Individual

#### Pagos
```python
from processors.pagos_processor import PagosProcessor

with PagosProcessor() as processor:
    processor.process_files()
```

#### Sifone
```python
from processors.sifone_processor import SifoneProcessor

with SifoneProcessor() as processor:
    processor.process_files()
```

#### Relaciones Automáticas
```python
from processors.pago_relacion_processor import PagoRelacionProcessor

with PagoRelacionProcessor() as processor:
    result = processor.process_automatic_relations()
    if result['success'] and result['total_valid'] > 0:
        processor.insert_relations(result['relations'])
```

### 🧪 **Pruebas de Validación**

#### 1. Probar Sistema de Logging
```bash
cd /opt/multi_app/py
python3 test_logging.py
```

#### 2. Validar Compatibilidad con CentOS
```bash
python3 config/centos_config.py
```

#### 3. Probar Aplicación Principal
```bash
python3 main.py
```

## 🎯 Características

### ✅ Arquitectura Modular
- **Código reutilizable**: Componentes compartidos entre procesadores
- **Configuración centralizada**: Un solo lugar para cambios
- **Logging unificado**: Sin archivos de log, solo consola

### ✅ Procesamiento Inteligente
- **Detección automática de encabezados**: Para archivos Excel no estándar
- **Limpieza de datos**: Reglas específicas por tipo de campo
- **Prevención de duplicados**: Verificación antes de inserción
- **Validación de estructura**: Verificación de columnas requeridas
- **Relaciones automáticas**: Creación inteligente de relaciones PSE-Confiar

### ✅ Organización de Archivos
- **Separación por dominio**: Pagos vs Sifone
- **Subcategorías**: Confiar/PSE, Asociados/Cartera
- **Nomenclatura consistente**: Fácil identificación

## 📊 Tipos de Datos Procesados

### Pagos
- **Confiar**: Archivos `.xls` con transacciones bancarias
- **PSE**: Archivos `.xlsx` con transacciones PSE

### Sifone
- **Asociados**: Información de asociados (cédula, nombre, etc.)
- **Cartera**: Información de cartera (tipos de crédito, moras, etc.)

### Relaciones Automáticas
- **PSE-Confiar**: Creación automática de relaciones entre pagos PSE y Confiar
- **Métodos de relación**: Directa (fecha+valor) y Agrupada (suma de valores)
- **Validación inteligente**: Evita duplicados y relaciones existentes

## 🔧 Configuración

### Base de Datos
```python
# config/settings.py
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'multi_app',
    'charset': 'utf8mb4'
}
```

### Rutas de Archivos
```python
# config/settings.py
DATA_PATHS = {
    'pagos': {
        'confiar': 'data/pagos/confiar',
        'pse': 'data/pagos/pse'
    },
    'sifone': 'data/sifone'
}
```

## 🧹 Limpieza de Datos

### Cédulas
- Elimina ceros previos (excepto si contiene guión)
- Mantiene formato original si tiene guiones

### Campos Numéricos
- Convierte a float/int según corresponda
- Maneja valores nulos y errores de conversión

### Campos de Texto
- Elimina caracteres especiales problemáticos
- Normaliza espacios en blanco

## 📝 Logging

El sistema usa logging a consola con emojis para fácil identificación:
- ✅ Éxito
- ❌ Error
- ⚠️ Advertencia
- 🔄 Procesando
- 📊 Estadísticas
- 🧹 Limpieza

### 🚫 Supresión de Warnings
El sistema suprime automáticamente warnings de pandas sobre conexiones de base de datos para mantener logs limpios:
```python
# Suprimir warnings de pandas sobre conexiones de base de datos
warnings.filterwarnings('ignore', category=UserWarning, module='pandas.io.sql')
warnings.filterwarnings('ignore', message='pandas only supports SQLAlchemy')
```

## 🔄 Flujo de Procesamiento

1. **Configuración**: Cargar configuración y logging
2. **Validación**: Verificar estructura de archivos
3. **Limpieza**: Aplicar reglas de limpieza
4. **Inserción**: Insertar en base de datos con prevención de duplicados
5. **Control**: Actualizar `control_asociados` (estado_activo=1 por defecto)
6. **Relaciones**: Crear relaciones automáticas entre PSE y Confiar
7. **Worker**: UI crea jobs en `control_cargas`; `worker.py` los procesa y actualiza estado

## 🚨 Manejo de Errores

- **Archivos corruptos**: Se registran y continúa con el siguiente
- **Datos inválidos**: Se limpian o se omiten con logging
- **Errores de BD**: Se registran y se hace rollback
- **Interrupciones**: Manejo graceful con context managers

## 🔗 Procesador de Relaciones Automáticas

### 🎯 Funcionalidad
El `PagoRelacionProcessor` crea automáticamente relaciones entre pagos PSE y Confiar basándose en algoritmos inteligentes.

### 📋 Parámetros de Automatización
- **No duplicados**: Verifica que no existan `pse_id` duplicados en `pagos_relacion`
- **Solo aprobados**: Solo procesa PSE con `estado = 'Aprobada'`
- **Confiar múltiple**: Un `confiar_id` puede relacionarse con varios `pse_id`
- **Validación previa**: Evita relaciones existentes y duplicados

### 🔍 Métodos de Relación

#### Método 1: Relaciones Directas
```python
# Si DATE(fecha_hora_resolucion_de_la_transaccion) == fecha 
# Y valor == valor_consignacion
# Entonces crear relación
```

#### Método 2: Relaciones Agrupadas
```python
# Agrupar PSE por fecha_resolucion + ciclo_transaccion
# Sumar valores del grupo
# Si suma == valor_consignacion de Confiar
# Entonces relacionar todos los PSE del grupo
```

### 📊 Resultados de Ejemplo
```
📊 ESTADÍSTICAS:
   • PSE aprobados disponibles: 9
   • Confiar disponibles: 29
   • Relaciones encontradas: 5

🔗 RELACIONES PROPUESTAS:
   1. PSE: CUS1657649329 → Confiar: confiar00428072025400694762
      Método: direct | Confianza: high
      Fecha: 2025-07-28 | Valor: $439,161

   2. PSE: CUS1653368967 → Confiar: confiar00425072025308321962
      Método: grouped | Confianza: medium
      Fecha: 2025-07-25 | Valor: $832,949
      Tamaño grupo: 2 PSE
```

### 🛠️ Uso del Procesador
```python
# Solo procesamiento (sin inserción)
result = processor.process_automatic_relations()

# Procesamiento + inserción
if result['success'] and result['total_valid'] > 0:
    insert_result = processor.insert_relations(result['relations'])
```

## 📈 Escalabilidad

### Agregar Nuevo Procesador
1. Crear clase heredando de `BaseProcessor`
2. Implementar método `process_files()`
3. Agregar configuración en `settings.py`
4. Actualizar `main.py`

### Agregar Nuevo Tipo de Archivo
1. Definir columnas requeridas
2. Agregar patrones de archivo
3. Implementar lógica de procesamiento específica

## 🔍 Debugging

### Verificar Estructura de Archivos
```python
from utils.file_utils import FileManager

fm = FileManager()
files = fm.list_files_by_pattern('data/pagos/confiar', '*.xls')
```

### Validar Datos
```python
from utils.validation import DataValidator

validator = DataValidator()
is_valid = validator.validate_cedula('1234567890')
```

### Probar Relaciones Automáticas
```python
from processors.pago_relacion_processor import PagoRelacionProcessor

processor = PagoRelacionProcessor()
result = processor.process_automatic_relations()
print(f"Relaciones encontradas: {result['total_valid']}")
print(f"Directas: {result['direct_relations']}")
print(f"Agrupadas: {result['grouped_relations']}")
```

## 🔧 Solución de Problemas Comunes

### Error: "Permission denied"
```bash
# Verificar permisos
ls -la /opt/multi_app/py/

# Corregir permisos
sudo chown -R $USER:$USER /opt/multi_app
chmod -R 755 /opt/multi_app
```

### Error: "Module not found"
```bash
# Verificar que el entorno virtual esté activado
source venv/bin/activate

# Reinstalar dependencias
pip3 install -r requirements.txt --force-reinstall
```

### Error: "MySQL connection failed"
```bash
# Verificar que MySQL esté ejecutándose
sudo systemctl status mysqld

# Verificar configuración en config/settings.py
# Asegurarse de que host, user, password sean correctos
```

### Error: "future feature annotations is not defined"
```bash
# Este error indica Python 3.6 con dependencias muy nuevas
# Usar requirements.txt que incluye versiones compatibles
pip3 install -r requirements.txt --force-reinstall
```

## 📊 Monitoreo y Logs

### Ver Logs en Tiempo Real
```bash
# Si usas systemd
sudo journalctl -u multi_app -f

# Si usas archivos de log
tail -f /var/log/multi_app/app.log
```

### Verificar Estado del Servicio
```bash
# Crear servicio systemd (opcional)
sudo systemctl status multi_app
```

## 🚀 Ejecución en Producción

### 1. Ejecutar como Servicio
```bash
# Crear archivo de servicio systemd
sudo nano /etc/systemd/system/multi_app.service
```

Contenido del servicio:
```ini
[Unit]
Description=Multi App Data Processor
After=network.target mysql.service

[Service]
Type=simple
User=multi_app
WorkingDirectory=/opt/multi_app/py
ExecStart=/opt/multi_app/venv/bin/python3 main.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### 2. Habilitar y Iniciar Servicio
```bash
sudo systemctl daemon-reload
sudo systemctl enable multi_app
sudo systemctl start multi_app
```

## 📝 Notas Importantes

- **Python 3.6+**: Requerido para compatibilidad con todas las características
- **Entorno Virtual**: Recomendado para evitar conflictos de dependencias
- **Permisos**: Asegurarse de que el usuario tenga permisos de escritura en el directorio del proyecto
- **MySQL**: Verificar que la base de datos esté accesible y configurada correctamente
- **Logs**: El sistema de logging está optimizado para CentOS y maneja automáticamente los fallbacks

## 🆘 Soporte

Para problemas o preguntas:
1. Revisar logs de consola
2. Verificar configuración en `config/settings.py`
3. Validar estructura de archivos Excel
4. Verificar conexión a base de datos

### 🔧 Problemas Comunes

#### Relaciones No Encontradas
- Verificar que existan PSE con `estado = 'Aprobada'`
- Confirmar que existan registros en `pagos_confiar`
- Revisar que las fechas y valores coincidan

#### Warnings de Pandas
- Los warnings de conexión de base de datos están suprimidos automáticamente
- Si aparecen otros warnings, verificar la versión de pandas

#### Errores de Duplicados
- El sistema previene duplicados automáticamente
- Verificar que no existan relaciones previas en `pagos_relacion`

### 🐧 **Problemas Específicos de CentOS**

Si encuentras problemas específicos de CentOS:
1. Verificar la versión exacta de CentOS: `cat /etc/centos-release`
2. Verificar la versión de Python: `python3 --version`
3. Revisar los logs del sistema: `sudo journalctl -xe`
4. Ejecutar las pruebas de validación: `python3 test_logging.py`
5. Verificar compatibilidad: `python3 config/centos_config.py` 