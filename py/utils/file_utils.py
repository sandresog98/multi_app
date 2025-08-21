#!/usr/bin/env python3
"""
Utilidades para manejo de archivos
"""

import os
import shutil
import logging
from typing import List, Dict, Any
from datetime import datetime

logger = logging.getLogger(__name__)

class FileManager:
    """Gestor de archivos y directorios"""
    
    def __init__(self):
        """Inicializar el gestor de archivos"""
        pass
    
    def ensure_directory(self, directory: str):
        """Crear directorio si no existe"""
        os.makedirs(directory, exist_ok=True)
        logger.info(f"📁 Directorio asegurado: {directory}")
    
    def get_file_info(self, file_path: str) -> Dict[str, Any]:
        """Obtener información de un archivo"""
        if not os.path.exists(file_path):
            return {}
        
        stat = os.stat(file_path)
        return {
            'filename': os.path.basename(file_path),
            'size': stat.st_size,
            'modified': datetime.fromtimestamp(stat.st_mtime),
            'created': datetime.fromtimestamp(stat.st_ctime)
        }
    
    def backup_file(self, file_path: str, backup_dir: str) -> str:
        """Hacer backup de un archivo"""
        if not os.path.exists(file_path):
            logger.warning(f"⚠️ Archivo no encontrado para backup: {file_path}")
            return ""
        
        # Crear directorio de backup si no existe
        self.ensure_directory(backup_dir)
        
        # Generar nombre de backup con timestamp
        filename = os.path.basename(file_path)
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        backup_name = f"{timestamp}_{filename}"
        backup_path = os.path.join(backup_dir, backup_name)
        
        # Copiar archivo
        shutil.copy2(file_path, backup_path)
        logger.info(f"💾 Backup creado: {backup_path}")
        
        return backup_path
    
    def move_file(self, source_path: str, destination_path: str):
        """Mover archivo de una ubicación a otra"""
        if not os.path.exists(source_path):
            logger.warning(f"⚠️ Archivo origen no encontrado: {source_path}")
            return False
        
        # Crear directorio de destino si no existe
        dest_dir = os.path.dirname(destination_path)
        self.ensure_directory(dest_dir)
        
        # Mover archivo
        shutil.move(source_path, destination_path)
        logger.info(f"📦 Archivo movido: {source_path} → {destination_path}")
        
        return True
    
    def archive_processed_file(self, file_path: str, archive_dir: str):
        """Archivar archivo procesado"""
        if not os.path.exists(file_path):
            logger.warning(f"⚠️ Archivo no encontrado para archivar: {file_path}")
            return False
        
        # Crear directorio de archivo si no existe
        self.ensure_directory(archive_dir)
        
        # Generar nombre de archivo con timestamp
        filename = os.path.basename(file_path)
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        archive_name = f"processed_{timestamp}_{filename}"
        archive_path = os.path.join(archive_dir, archive_name)
        
        # Mover archivo
        shutil.move(file_path, archive_path)
        logger.info(f"📦 Archivo archivado: {archive_path}")
        
        return True
    
    def list_files_by_pattern(self, directory: str, pattern: str) -> List[str]:
        """Listar archivos que coincidan con un patrón"""
        import glob
        
        if not os.path.exists(directory):
            logger.warning(f"⚠️ Directorio no encontrado: {directory}")
            return []
        
        search_pattern = os.path.join(directory, pattern)
        files = glob.glob(search_pattern)
        
        logger.info(f"📁 Encontrados {len(files)} archivos con patrón '{pattern}' en {directory}")
        return files
    
    def validate_file_structure(self, file_path: str, expected_columns: List[str]) -> bool:
        """Validar estructura de archivo Excel"""
        try:
            import pandas as pd
            
            # Leer primeras filas para verificar columnas
            df = pd.read_excel(file_path, nrows=5)
            
            missing_columns = [col for col in expected_columns if col not in df.columns]
            
            if missing_columns:
                logger.error(f"❌ Columnas faltantes en {file_path}: {missing_columns}")
                logger.info(f"📋 Columnas disponibles: {list(df.columns)}")
                return False
            
            logger.info(f"✅ Estructura de archivo válida: {file_path}")
            return True
            
        except Exception as e:
            logger.error(f"❌ Error validando estructura de archivo {file_path}: {e}")
            return False 