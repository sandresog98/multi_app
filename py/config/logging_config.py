#!/usr/bin/env python3
"""
Configuración de logging centralizada
"""

import logging
import sys
from config.settings import LOGGING_CONFIG

def setup_logging():
    """Configurar logging para toda la aplicación"""
    # Configurar formato
    formatter = logging.Formatter(LOGGING_CONFIG['format'])
    
    # Configurar handler de consola
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    
    # Configurar logger raíz
    root_logger = logging.getLogger()
    root_logger.setLevel(LOGGING_CONFIG['level'])
    
    # Limpiar handlers existentes y agregar el nuevo
    root_logger.handlers.clear()
    root_logger.addHandler(console_handler)
    
    return root_logger 