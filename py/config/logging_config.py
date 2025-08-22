#!/usr/bin/env python3
"""
Configuración de logging centralizada optimizada para Python 3.10+
"""

import logging
import sys
import os
from typing import Optional, Union
from pathlib import Path
from config.settings import LOGGING_CONFIG

# Tipos modernos para Python 3.10+
LogLevel = Union[int, str]
LoggerType = Union[logging.Logger, logging.RootLogger]


def setup_logging() -> LoggerType:
    """Configurar logging para toda la aplicación con manejo robusto para Python 3.10+"""
    try:
        # Configurar formato con información más detallada
        formatter = logging.Formatter(
            fmt='%(asctime)s | %(name)s | %(levelname)-8s | %(message)s',
            datefmt='%Y-%m-%d %H:%M:%S'
        )
        
        # Configurar handler de consola con fallback robusto
        console_handler = _create_console_handler(formatter)
        
        # Configurar logger raíz
        root_logger = logging.getLogger()
        root_logger.setLevel(LOGGING_CONFIG['level'])
        
        # Limpiar handlers existentes y agregar el nuevo
        root_logger.handlers.clear()
        root_logger.addHandler(console_handler)
        
        # Verificar que el logging funcione correctamente
        _test_logging_configuration(root_logger)
        
        return root_logger
        
    except Exception as e:
        # Configuración de emergencia si todo falla
        return _setup_emergency_logging(e)


def _create_console_handler(formatter: logging.Formatter) -> logging.Handler:
    """Crear handler de consola con fallbacks"""
    try:
        # Intentar usar stdout primero
        console_handler = logging.StreamHandler(sys.stdout)
        console_handler.setFormatter(formatter)
        return console_handler
        
    except (OSError, IOError):
        try:
            # Fallback a stderr si stdout falla
            console_handler = logging.StreamHandler(sys.stderr)
            console_handler.setFormatter(formatter)
            return console_handler
            
        except (OSError, IOError):
            # Fallback final: logging básico sin handler personalizado
            logging.basicConfig(
                level=LOGGING_CONFIG['level'],
                format='%(asctime)s - %(levelname)s - %(message)s'
            )
            return logging.getLogger().handlers[0] if logging.getLogger().handlers else None


def _test_logging_configuration(logger: LoggerType) -> None:
    """Probar que la configuración de logging funcione correctamente"""
    try:
        logger.info("✅ Sistema de logging configurado correctamente")
        
    except Exception as e:
        # Si falla, usar logging básico
        logging.basicConfig(
            level=LOGGING_CONFIG['level'],
            format='%(asctime)s - %(levelname)s - %(message)s'
        )
        logger = logging.getLogger()
        logger.warning(f"⚠️ Fallback a logging básico: {e}")


def _setup_emergency_logging(error: Exception) -> LoggerType:
    """Configuración de emergencia si todo falla"""
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s'
    )
    emergency_logger = logging.getLogger()
    emergency_logger.error(f"❌ Error crítico en configuración de logging: {error}")
    emergency_logger.info("🔄 Usando configuración de emergencia")
    return emergency_logger


def get_logger(name: Optional[str] = None) -> LoggerType:
    """Obtener logger específico con manejo de errores"""
    try:
        return logging.getLogger(name) if name else logging.getLogger()
    except Exception:
        # Fallback al logger raíz
        return logging.getLogger()


def configure_file_logging(
    log_file: Union[str, Path], 
    max_bytes: int = 10 * 1024 * 1024,  # 10MB
    backup_count: int = 5
) -> None:
    """Configurar logging a archivo con rotación (opcional)"""
    try:
        from logging.handlers import RotatingFileHandler
        
        # Crear directorio si no existe
        log_path = Path(log_file)
        log_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Configurar handler de archivo con rotación
        file_handler = RotatingFileHandler(
            log_file, 
            maxBytes=max_bytes, 
            backupCount=backup_count,
            encoding='utf-8'
        )
        
        # Formato para archivo
        file_formatter = logging.Formatter(
            fmt='%(asctime)s | %(name)s | %(levelname)-8s | %(funcName)s:%(lineno)d | %(message)s',
            datefmt='%Y-%m-%d %H:%M:%S'
        )
        file_handler.setFormatter(file_formatter)
        
        # Agregar al logger raíz
        root_logger = logging.getLogger()
        root_logger.addHandler(file_handler)
        
        root_logger.info(f"📁 Logging a archivo configurado: {log_file}")
        
    except ImportError:
        logging.getLogger().warning("⚠️ RotatingFileHandler no disponible, logging a archivo deshabilitado")
    except Exception as e:
        logging.getLogger().warning(f"⚠️ No se pudo configurar logging a archivo: {e}")


def set_log_level(level: LogLevel) -> None:
    """Cambiar el nivel de logging dinámicamente"""
    try:
        if isinstance(level, str):
            level = getattr(logging, level.upper())
        
        root_logger = logging.getLogger()
        root_logger.setLevel(level)
        
        # Actualizar todos los handlers
        for handler in root_logger.handlers:
            handler.setLevel(level)
            
        logging.getLogger().info(f"🔧 Nivel de logging cambiado a: {logging.getLevelName(level)}")
        
    except Exception as e:
        logging.getLogger().warning(f"⚠️ No se pudo cambiar el nivel de logging: {e}")


# Función de conveniencia para configuración rápida
def quick_setup(
    level: LogLevel = logging.INFO,
    log_file: Optional[Union[str, Path]] = None
) -> LoggerType:
    """Configuración rápida del sistema de logging"""
    logger = setup_logging()
    
    if level != logging.INFO:
        set_log_level(level)
    
    if log_file:
        configure_file_logging(log_file)
    
    return logger 