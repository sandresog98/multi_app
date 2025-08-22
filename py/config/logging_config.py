#!/usr/bin/env python3
"""
Configuración de logging centralizada optimizada para CentOS
"""

import logging
import sys
import os
from config.settings import LOGGING_CONFIG

def setup_logging():
    """Configurar logging para toda la aplicación con manejo robusto para CentOS"""
    try:
        # Configurar formato
        formatter = logging.Formatter(LOGGING_CONFIG['format'])
        
        # Configurar handler de consola con fallback robusto
        try:
            # Intentar usar stdout primero
            console_handler = logging.StreamHandler(sys.stdout)
        except (OSError, IOError):
            # Fallback a stderr si stdout falla
            try:
                console_handler = logging.StreamHandler(sys.stderr)
            except (OSError, IOError):
                # Fallback final: logging básico sin handler personalizado
                logging.basicConfig(
                    level=LOGGING_CONFIG['level'],
                    format=LOGGING_CONFIG['format']
                )
                return logging.getLogger()
        
        console_handler.setFormatter(formatter)
        
        # Configurar logger raíz
        root_logger = logging.getLogger()
        root_logger.setLevel(LOGGING_CONFIG['level'])
        
        # Limpiar handlers existentes y agregar el nuevo
        root_logger.handlers.clear()
        root_logger.addHandler(console_handler)
        
        # Verificar que el logging funcione correctamente
        try:
            root_logger.info("✅ Sistema de logging configurado correctamente")
        except Exception as e:
            # Si falla, usar logging básico
            logging.basicConfig(
                level=LOGGING_CONFIG['level'],
                format=LOGGING_CONFIG['format']
            )
            root_logger = logging.getLogger()
            root_logger.warning(f"⚠️ Fallback a logging básico: {e}")
        
        return root_logger
        
    except Exception as e:
        # Configuración de emergencia si todo falla
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s'
        )
        emergency_logger = logging.getLogger()
        emergency_logger.error(f"❌ Error crítico en configuración de logging: {e}")
        emergency_logger.info("🔄 Usando configuración de emergencia")
        return emergency_logger

def get_logger(name):
    """Obtener logger específico con manejo de errores"""
    try:
        return logging.getLogger(name)
    except Exception:
        # Fallback al logger raíz
        return logging.getLogger() 