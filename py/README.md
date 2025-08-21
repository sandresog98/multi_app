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
│   └── logging_config.py        # Configuración de logging
│
├── utils/                       # Utilidades compartidas
│   ├── __init__.py
│   ├── file_utils.py            # Manejo de archivos
│   └── validation.py            # Validaciones comunes
│
├── main.py                      # Punto de entrada completo
├── worker.py                    # Worker que procesa control_cargas (llamado desde UI)
└── requirements.txt             # Dependencias unificadas
```

## 🚀 Instalación

1. **Instalar dependencias:**
   ```bash
   pip install -r requirements.txt
   ```

2. **Configurar base de datos:**
   - MySQL corriendo y base `multiapptwo`
   - Verifica `config/settings.py` (host 127.0.0.1 si hay problemas de socket)

## 📋 Uso

### Procesamiento Completo
```bash
python main.py
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

## 📞 Soporte

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