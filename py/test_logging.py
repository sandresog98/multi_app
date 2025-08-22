#!/usr/bin/env python3
"""
Script de prueba para validar el sistema de logging en CentOS
"""

import sys
import os

# Agregar el directorio actual al path para importar módulos
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

def test_logging():
    """Probar el sistema de logging"""
    print("🧪 Iniciando pruebas de logging...")
    
    try:
        # Importar configuración de logging
        from config.logging_config import setup_logging, get_logger
        
        print("✅ Módulos de logging importados correctamente")
        
        # Configurar logging
        logger = setup_logging()
        print("✅ Logging configurado correctamente")
        
        # Probar diferentes niveles de logging
        logger.debug("🔍 Mensaje de debug")
        logger.info("ℹ️ Mensaje de información")
        logger.warning("⚠️ Mensaje de advertencia")
        logger.error("❌ Mensaje de error")
        
        print("✅ Todos los niveles de logging funcionan correctamente")
        
        # Probar logger específico
        test_logger = get_logger("test_module")
        test_logger.info("✅ Logger específico funciona correctamente")
        
        # Probar logging con caracteres especiales (común en CentOS)
        logger.info("🌍 Prueba con caracteres especiales: áéíóú ñ")
        logger.info("🔢 Prueba con números: 123.45")
        
        print("✅ Logging con caracteres especiales funciona correctamente")
        
        return True
        
    except Exception as e:
        print(f"❌ Error en las pruebas de logging: {e}")
        return False

def test_environment():
    """Probar el entorno de ejecución"""
    print("\n🔍 Información del entorno:")
    print(f"Python version: {sys.version}")
    print(f"Platform: {sys.platform}")
    print(f"Current directory: {os.getcwd()}")
    print(f"Python executable: {sys.executable}")
    
    # Verificar permisos de escritura
    try:
        test_file = "test_write.tmp"
        with open(test_file, 'w') as f:
            f.write("test")
        os.remove(test_file)
        print("✅ Permisos de escritura: OK")
    except Exception as e:
        print(f"❌ Permisos de escritura: {e}")

if __name__ == "__main__":
    print("=" * 60)
    print("🧪 PRUEBAS DE LOGGING PARA CENTOS")
    print("=" * 60)
    
    # Probar entorno
    test_environment()
    
    print("\n" + "=" * 60)
    print("🧪 PRUEBAS DE LOGGING")
    print("=" * 60)
    
    # Probar logging
    success = test_logging()
    
    print("\n" + "=" * 60)
    if success:
        print("🎉 TODAS LAS PRUEBAS PASARON EXITOSAMENTE")
        print("✅ El sistema de logging es compatible con CentOS")
    else:
        print("❌ ALGUNAS PRUEBAS FALLARON")
        print("⚠️ Revisar la configuración de logging")
    print("=" * 60)
