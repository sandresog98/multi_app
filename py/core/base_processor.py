#!/usr/bin/env python3
"""
Clase base para todos los procesadores de datos
"""

import logging
from typing import List, Dict, Any, Optional
from core.database import DatabaseManager
from core.excel_processor import ExcelProcessor
from core.data_cleaner import DataCleaner
from config.logging_config import setup_logging

logger = logging.getLogger(__name__)

class BaseProcessor:
    """Clase base para todos los procesadores de datos"""
    
    def __init__(self, processor_name: str):
        """Inicializar el procesador base"""
        self.processor_name = processor_name
        self.db_manager = DatabaseManager()
        self.excel_processor = ExcelProcessor()
        self.data_cleaner = DataCleaner()
        
        # Configurar logging
        setup_logging()
        logger.info(f"🚀 Iniciando procesador: {self.processor_name}")
    
    def setup_logging(self):
        """Configurar logging específico del procesador"""
        # El logging ya se configura en __init__, pero se puede sobrescribir
        pass
    
    def get_files_by_type(self, data_type: str, sub_type: str) -> List[str]:
        """Obtener archivos por tipo y subtipo"""
        from config.settings import DATA_PATHS, FILE_PATTERNS
        import os
        import glob
        
        base_path = DATA_PATHS[data_type][sub_type]
        patterns = FILE_PATTERNS[sub_type]
        
        files = []
        for pattern in patterns:
            search_pattern = os.path.join(base_path, pattern)
            files.extend(glob.glob(search_pattern))
        
        logger.info(f"📁 Encontrados {len(files)} archivos de {sub_type}")
        return files
    
    def validate_data(self, data: List[Dict[str, Any]]) -> bool:
        """Validar datos antes de insertar"""
        if not data:
            logger.warning("⚠️ No hay datos para validar")
            return False
        
        # Validación básica: verificar que todos los registros tengan las mismas claves
        expected_keys = set(data[0].keys())
        for i, record in enumerate(data):
            if set(record.keys()) != expected_keys:
                logger.error(f"❌ Registro {i} tiene claves diferentes: {set(record.keys())}")
                return False
        
        logger.info(f"✅ Validación exitosa: {len(data)} registros")
        return True
    
    def insert_data(self, table: str, data: List[Dict[str, Any]], 
                   check_duplicates: bool = True, id_column: str = None) -> int:
        """Insertar datos en la base de datos"""
        if not data:
            logger.warning(f"⚠️ No hay datos para insertar en {table}")
            return 0
        
        try:
            # Verificar duplicados si se solicita
            if check_duplicates and id_column:
                ids = [record[id_column] for record in data]
                existing_ids = self.db_manager.check_existing_records(table, id_column, ids)
                
                # Filtrar registros que no existen
                new_records = [record for record in data if record[id_column] not in existing_ids]
                
                if len(new_records) != len(data):
                    logger.info(f"📊 De {len(data)} registros, {len(new_records)} son nuevos")
                    data = new_records
            
            # Insertar datos
            if data:
                inserted_count = self.db_manager.insert_batch(table, data)
                logger.info(f"✅ Insertados {inserted_count} registros en {table}")
                return inserted_count
            else:
                logger.info(f"ℹ️ Todos los registros ya existen en {table}")
                return 0
                
        except Exception as e:
            logger.error(f"❌ Error insertando datos en {table}: {e}")
            raise
    
    def truncate_table(self, table: str):
        """Truncar tabla antes de insertar nuevos datos"""
        try:
            self.db_manager.truncate_table(table)
            logger.info(f"🗑️ Tabla {table} truncada exitosamente")
        except Exception as e:
            logger.error(f"❌ Error truncando tabla {table}: {e}")
            raise
    
    def process_files(self):
        """Método abstracto que debe ser implementado por cada procesador"""
        raise NotImplementedError("El método process_files debe ser implementado por cada procesador")
    
    def cleanup(self):
        """Limpiar recursos del procesador"""
        try:
            self.db_manager.close_connection()
            logger.info(f"🧹 Limpieza completada para {self.processor_name}")
        except Exception as e:
            logger.error(f"❌ Error durante limpieza: {e}")
    
    def __enter__(self):
        """Context manager entry"""
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit"""
        self.cleanup() 