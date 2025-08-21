#!/usr/bin/env python3
"""
Procesador genérico de archivos Excel
"""

import pandas as pd
import logging
from typing import List, Dict, Any, Optional
import os

logger = logging.getLogger(__name__)

class ExcelProcessor:
    """Procesador genérico para archivos Excel"""
    
    def __init__(self):
        """Inicializar el procesador de Excel"""
        pass
    
    def read_excel_file(self, file_path: str, header_row: Optional[int] = None, 
                       skiprows: Optional[int] = None) -> pd.DataFrame:
        """Leer archivo Excel con opciones flexibles"""
        try:
            logger.info(f"📖 Leyendo archivo: {os.path.basename(file_path)}")
            
            # Seleccionar engine según extensión
            engine = None
            lower = file_path.lower()
            if lower.endswith('.xlsx'):
                engine = 'openpyxl'
            elif lower.endswith('.xls'):
                # xlrd>=2.0.1 solo soporta .xls si se usa formato BIFF; pandas 2.x leerá con xlrd instalado
                engine = 'xlrd'

            if header_row is not None:
                df = pd.read_excel(file_path, skiprows=header_row, engine=engine)
            elif skiprows is not None:
                df = pd.read_excel(file_path, skiprows=skiprows, engine=engine)
            else:
                df = pd.read_excel(file_path, engine=engine)
            
            logger.info(f"✅ Archivo leído: {len(df)} filas, {len(df.columns)} columnas")
            return df
            
        except Exception as e:
            logger.error(f"❌ Error leyendo archivo {file_path}: {e}")
            raise
    
    def detect_header_row(self, file_path: str, keyword: str, max_rows: int = 20) -> Optional[int]:
        """Detectar la fila de encabezados basándose en una palabra clave"""
        try:
            # Leer preview para encontrar la fila de encabezados
            preview = pd.read_excel(file_path, header=None, nrows=max_rows)
            
            # Buscar la fila que contiene la palabra clave
            for i, row in preview.iterrows():
                if keyword in row.values:
                    logger.info(f"📋 Fila de encabezados detectada en índice {i}")
                    return i
            
            logger.warning(f"⚠️ No se encontró la palabra clave '{keyword}' en las primeras {max_rows} filas")
            return None
            
        except Exception as e:
            logger.error(f"❌ Error detectando fila de encabezados: {e}")
            return None
    
    def detect_data_end(self, df: pd.DataFrame, date_column: str) -> int:
        """Detectar el final de los datos basándose en una columna de fecha"""
        try:
            # Convertir la columna de fecha a datetime
            df[date_column] = pd.to_datetime(df[date_column], errors='coerce')
            
            # Encontrar el primer índice donde la fecha es nula (fin de datos)
            end_idx = df[date_column].isna().idxmax()
            
            logger.info(f"📅 Fin de datos detectado en índice {end_idx}")
            return end_idx
            
        except Exception as e:
            logger.error(f"❌ Error detectando fin de datos: {e}")
            return len(df)
    
    def validate_columns(self, df: pd.DataFrame, required_columns: List[str]) -> bool:
        """Validar que el DataFrame contenga las columnas requeridas"""
        missing_columns = [col for col in required_columns if col not in df.columns]
        
        if missing_columns:
            logger.error(f"❌ Columnas faltantes: {missing_columns}")
            logger.info(f"📋 Columnas disponibles: {list(df.columns)}")
            return False
        
        logger.info(f"✅ Todas las columnas requeridas están presentes")
        return True
    
    def clean_dataframe(self, df: pd.DataFrame, drop_na_columns: Optional[List[str]] = None) -> pd.DataFrame:
        """Limpiar DataFrame eliminando filas problemáticas"""
        original_len = len(df)
        
        # Eliminar filas donde columnas específicas sean nulas
        if drop_na_columns:
            df = df.dropna(subset=drop_na_columns)
            logger.info(f"🧹 Eliminadas {original_len - len(df)} filas con valores nulos en columnas clave")
        
        # Eliminar filas completamente vacías
        df = df.dropna(how='all')
        
        # Resetear índices
        df = df.reset_index(drop=True)
        
        logger.info(f"🧹 DataFrame limpiado: {len(df)} filas válidas de {original_len} originales")
        return df
    
    def process_excel_with_header_detection(self, file_path: str, keyword: str, 
                                          required_columns: List[str], 
                                          date_column: Optional[str] = None,
                                          drop_na_columns: Optional[List[str]] = None) -> pd.DataFrame:
        """Procesar archivo Excel con detección automática de encabezados"""
        try:
            # Detectar fila de encabezados
            header_row = self.detect_header_row(file_path, keyword)
            if header_row is None:
                raise ValueError(f"No se pudo detectar la fila de encabezados con '{keyword}'")
            
            # Leer archivo desde la fila detectada
            df = self.read_excel_file(file_path, header_row=header_row)
            
            # Validar columnas requeridas
            if not self.validate_columns(df, required_columns):
                raise ValueError("Columnas requeridas no encontradas")
            
            # Detectar fin de datos si se especifica columna de fecha
            if date_column and date_column in df.columns:
                end_idx = self.detect_data_end(df, date_column)
                df = df.loc[:end_idx - 1].reset_index(drop=True)
            
            # Limpiar DataFrame
            df = self.clean_dataframe(df, drop_na_columns)
            
            return df
            
        except Exception as e:
            logger.error(f"❌ Error procesando archivo {file_path}: {e}")
            raise
    
    def get_file_info(self, file_path: str) -> Dict[str, Any]:
        """Obtener información básica del archivo Excel"""
        try:
            df = pd.read_excel(file_path, nrows=5)  # Solo leer primeras 5 filas para info
            return {
                'filename': os.path.basename(file_path),
                'columns': list(df.columns),
                'total_columns': len(df.columns),
                'sample_rows': len(df)
            }
        except Exception as e:
            logger.error(f"❌ Error obteniendo información del archivo {file_path}: {e}")
            return {} 