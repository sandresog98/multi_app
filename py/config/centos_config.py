#!/usr/bin/env python3
"""
Configuración específica para CentOS y entornos de producción
"""

import os
import sys
import logging

# Configuración específica para CentOS
CENTOS_CONFIG = {
    # Configuración de logging robusta para CentOS
    'logging': {
        'fallback_to_stderr': True,  # Usar stderr si stdout falla
        'handle_encoding_errors': True,  # Manejar errores de codificación
        'max_log_size': 10 * 1024 * 1024,  # 10MB máximo por log
        'backup_count': 5,  # Mantener 5 archivos de backup
    },
    
    # Configuración de archivos
    'file_permissions': {
        'umask': 0o022,  # Permisos estándar de CentOS
        'create_dirs': True,  # Crear directorios si no existen
    },
    
    # Configuración de sistema
    'system': {
        'max_open_files': 1024,  # Límite de archivos abiertos
        'timeout': 30,  # Timeout por defecto
        'retry_attempts': 3,  # Intentos de reintento
    }
}

def setup_centos_environment():
    """Configurar el entorno para CentOS"""
    try:
        # Establecer umask estándar de CentOS
        os.umask(CENTOS_CONFIG['file_permissions']['umask'])
        
        # Configurar límite de archivos abiertos
        try:
            import resource
            soft, hard = resource.getrlimit(resource.RLIMIT_NOFILE)
            if soft < CENTOS_CONFIG['system']['max_open_files']:
                resource.setrlimit(resource.RLIMIT_NOFILE, 
                                 (CENTOS_CONFIG['system']['max_open_files'], hard))
        except ImportError:
            # resource no disponible en Windows
            pass
        
        return True
        
    except Exception as e:
        print(f"⚠️ Advertencia: No se pudo configurar entorno CentOS: {e}")
        return False

def get_centos_logging_config():
    """Obtener configuración de logging optimizada para CentOS"""
    return {
        'level': logging.INFO,
        'format': '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        'date_format': '%Y-%m-%d %H:%M:%S',
        'handlers': ['console'],
        'centos_optimizations': CENTOS_CONFIG['logging']
    }

def validate_centos_compatibility():
    """Validar compatibilidad con CentOS"""
    compatibility_issues = []
    
    # Verificar versión de Python
    if sys.version_info < (3, 6):
        compatibility_issues.append("Python 3.6+ requerido")
    
    # Verificar módulos críticos
    required_modules = ['logging', 'os', 'sys']
    for module in required_modules:
        try:
            __import__(module)
        except ImportError:
            compatibility_issues.append(f"Módulo {module} no disponible")
    
    # Verificar permisos de escritura
    try:
        test_dir = os.path.join(os.getcwd(), 'test_centos')
        os.makedirs(test_dir, exist_ok=True)
        test_file = os.path.join(test_dir, 'test.txt')
        with open(test_file, 'w') as f:
            f.write('test')
        os.remove(test_file)
        os.rmdir(test_dir)
    except Exception as e:
        compatibility_issues.append(f"Problemas de permisos: {e}")
    
    return compatibility_issues

if __name__ == "__main__":
    print("🔍 Validando compatibilidad con CentOS...")
    issues = validate_centos_compatibility()
    
    if not issues:
        print("✅ Compatibilidad con CentOS validada correctamente")
        setup_centos_environment()
        print("✅ Entorno CentOS configurado")
    else:
        print("❌ Problemas de compatibilidad detectados:")
        for issue in issues:
            print(f"  - {issue}")
