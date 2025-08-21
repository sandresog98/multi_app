#!/usr/bin/env python3
"""
Punto de entrada principal para el sistema de procesamiento de datos
"""

import sys
import logging
import warnings
from config.settings import ensure_directories
from config.logging_config import setup_logging
from processors.pagos_processor import PagosProcessor
from processors.sifone_processor import SifoneProcessor
from processors.pago_relacion_processor import PagoRelacionProcessor
from core.database import DatabaseManager

# Suprimir warnings de pandas sobre conexiones de base de datos
warnings.filterwarnings('ignore', category=UserWarning, module='pandas.io.sql')
warnings.filterwarnings('ignore', message='pandas only supports SQLAlchemy')

def main():
    """Función principal del sistema"""
    try:
        # Configurar logging
        logger = setup_logging()
        logger.info("🚀 Iniciando sistema de procesamiento de datos")
        
        # Asegurar que existan los directorios necesarios
        ensure_directories()
        logger.info("📁 Directorios verificados")

        # Procesar archivos de Sifone
        logger.info("=" * 50)
        logger.info("🔄 PROCESANDO ARCHIVOS DE SIFONE")
        logger.info("=" * 50)
        
        with SifoneProcessor() as sifone_processor:
            sifone_processor.process_files()
        
        # Procesar archivos de pagos y relaciones solo si existen tablas requeridas
        dbm = DatabaseManager()

        def table_exists(table_name: str) -> bool:
            try:
                conn = dbm.get_connection()
                cursor = conn.cursor()
                cursor.execute(f"SHOW TABLES LIKE '{table_name}'")
                exists = cursor.fetchone() is not None
                cursor.close()
                return exists
            except Exception:
                return False

        if table_exists('banco_confiar') and table_exists('banco_pse'):
            logger.info("=" * 50)
            logger.info("🔄 PROCESANDO ARCHIVOS DE PAGOS")
            logger.info("=" * 50)
            with PagosProcessor() as pagos_processor:
                pagos_processor.process_files()

            if table_exists('banco_asignacion_pse'):
                logger.info("=" * 50)
                logger.info("🔄 PROCESANDO RELACIONES AUTOMÁTICAS")
                logger.info("=" * 50)
                with PagoRelacionProcessor() as relacion_processor:
                    result = relacion_processor.process_automatic_relations()
                    if result['success'] and result['total_valid'] > 0:
                        logger.info(f"📊 Encontradas {result['total_valid']} relaciones automáticas")
                        insert_result = relacion_processor.insert_relations(result['relations'])
                        if insert_result['success']:
                            logger.info(f"✅ {insert_result['message']}")
                        else:
                            logger.warning(f"⚠️ Error insertando relaciones: {insert_result['message']}")
                    else:
                        logger.info("ℹ️ No se encontraron relaciones automáticas para procesar")
        else:
            logger.info("ℹ️ Tablas de pagos no presentes en BD. Se omite procesamiento de pagos y relaciones.")
        
        logger.info("=" * 50)
        logger.info("🎉 PROCESAMIENTO COMPLETADO EXITOSAMENTE")
        logger.info("=" * 50)
        
    except KeyboardInterrupt:
        logger.info("⚠️ Procesamiento interrumpido por el usuario")
        sys.exit(1)
    except Exception as e:
        logger.error(f"❌ Error crítico en el procesamiento: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main() 