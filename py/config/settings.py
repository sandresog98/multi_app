#!/usr/bin/env python3
"""
Configuración centralizada para el sistema de procesamiento de datos
"""

import os
import logging

# Directorio base del proyecto
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(_file_)))

# Configuración de la base de datos con soporte de entornos (LOCAL/PROD)
APP_ENV = os.getenv('APP_ENV', 'development').lower()
if APP_ENV in ('production','prod'):
    DB_CONFIG = {
        'host': os.getenv('DB_HOST', '192.168.10.30'),
        'user': os.getenv('DB_USER', 'root'),
        'password': os.getenv('DB_PASS', '123456789'),
        'database': os.getenv('DB_NAME', 'multiapptwo'),
        'charset': 'utf8mb4'
    }
else:
    # development/local por defecto
    DB_CONFIG = {
        'host': os.getenv('DB_HOST', 'localhost'),
        'user': os.getenv('DB_USER', 'root'),
        'password': os.getenv('DB_PASS', ''),
        'database': os.getenv('DB_NAME', 'multiapptwo'),
        'charset': 'utf8mb4'
    }

# Configuración de logging (solo consola, sin archivos)
LOGGING_CONFIG = {
    'level': logging.INFO,
    'format': '%(asctime)s - %(levelname)s - %(message)s',
    'handlers': ['console']
}

# Rutas de archivos organizadas (sifone sin subcarpetas, todos los archivos en una sola carpeta)
SIFONE_ROOT = os.path.join(BASE_DIR, 'data', 'sifone')
DATA_PATHS = {
    'pagos': {
        'confiar': os.path.join(BASE_DIR, 'data', 'pagos', 'confiar'),
        'pse': os.path.join(BASE_DIR, 'data', 'pagos', 'pse')
    },
    'sifone': {
        # Todos los subtipos apuntan a la misma carpeta raíz
        'asociados': SIFONE_ROOT,
        'cartera_mora': SIFONE_ROOT,
        'aseguradora': SIFONE_ROOT,
        'root': SIFONE_ROOT,
    }
}

# Patrones de archivos por tipo
FILE_PATTERNS = {
    'confiar': ['.xls', '.xlsx'],
    'pse': ['*.xlsx'],
    # Patrones específicos en una sola carpeta para Sifone
    'asociados': ['librodeasociados.*'],
    'cartera_mora': ['carteraxedades.*'],
    'aseguradora': ['carteraaseguradora.*']
}

# Crear directorios si no existen
def ensure_directories():
    """Crear directorios de datos si no existen"""
    for category in DATA_PATHS.values():
        for path in category.values():
            os.makedirs(path, exist_ok=True)

# Configuración de procesamiento
PROCESSING_CONFIG = {
    'batch_size': 1000,
    'max_retries': 3,
    'timeout': 30
}