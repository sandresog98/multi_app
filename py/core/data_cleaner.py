#!/usr/bin/env python3
"""
Limpieza de datos común para todos los procesadores
"""

import pandas as pd
import logging
from typing import Any, Optional, Dict, List
import re

logger = logging.getLogger(__name__)

class DataCleaner:
    """Clase para limpieza de datos común"""
    
    def __init__(self):
        """Inicializar el limpiador de datos"""
        pass
    
    def clean_string_field(self, value: Any) -> str:
        """Limpiar campo de texto"""
        if pd.isna(value) or value is None:
            return ""
        
        # Convertir a string y limpiar
        cleaned = str(value).strip()
        
        # Eliminar caracteres especiales problemáticos
        cleaned = re.sub(r'[^\w\s\-\.]', '', cleaned)
        
        return cleaned
    
    def clean_cedula_field(self, value: Any) -> str:
        """Limpiar campo de cédula preservando formato original"""
        if pd.isna(value) or value is None:
            return ""
        
        # Convertir a string y limpiar espacios
        cedula = str(value).strip()
        
        # Preservar formato original - no eliminar ceros previos
        # Solo limpiar espacios y caracteres no válidos
        cedula = re.sub(r'[^\d\-]', '', cedula)
        
        # Si quedó vacío después de limpiar, retornar string vacío
        if not cedula:
            return ""
        
        return cedula
    
    def clean_numeric_field(self, value: Any) -> float:
        """Limpiar campo numérico"""
        if pd.isna(value) or value is None:
            return 0.0
        
        try:
            # Convertir a float
            if isinstance(value, str):
                # Eliminar caracteres no numéricos excepto punto y coma
                cleaned = re.sub(r'[^\d\.\-]', '', value)
                return float(cleaned) if cleaned else 0.0
            else:
                return float(value)
        except (ValueError, TypeError):
            logger.warning(f"⚠️ No se pudo convertir valor '{value}' a numérico")
            return 0.0
    
    def clean_integer_field(self, value: Any) -> int:
        """Limpiar campo entero"""
        if pd.isna(value) or value is None:
            return 0
        
        try:
            # Convertir a entero
            if isinstance(value, str):
                # Eliminar caracteres no numéricos
                cleaned = re.sub(r'[^\d\-]', '', value)
                return int(cleaned) if cleaned else 0
            else:
                return int(value)
        except (ValueError, TypeError):
            logger.warning(f"⚠️ No se pudo convertir valor '{value}' a entero")
            return 0
    
    def clean_date_field(self, value: Any) -> Optional[str]:
        """Limpiar campo de fecha"""
        if pd.isna(value) or value is None:
            return None
        
        try:
            # Convertir a datetime y luego a string
            if isinstance(value, str):
                # Intentar parsear como fecha
                pd_date = pd.to_datetime(value, errors='coerce')
            else:
                pd_date = pd.to_datetime(value, errors='coerce')
            
            if pd.isna(pd_date):
                return None
            
            return pd_date.strftime('%Y-%m-%d')
        except Exception as e:
            logger.warning(f"⚠️ No se pudo convertir fecha '{value}': {e}")
            return None
    
    def clean_boolean_field(self, value: Any) -> bool:
        """Limpiar campo booleano"""
        if pd.isna(value) or value is None:
            return False
        
        # Convertir a string para comparación
        str_value = str(value).lower().strip()
        
        # Valores que se consideran True
        true_values = ['true', '1', 'yes', 'si', 'sí', 'activo', 'active']
        
        return str_value in true_values
    
    def clean_dataframe_columns(self, df: pd.DataFrame, column_mapping: Dict[str, str]) -> pd.DataFrame:
        """Limpiar columnas específicas de un DataFrame"""
        for column, cleaning_type in column_mapping.items():
            if column in df.columns:
                if cleaning_type == 'string':
                    df[column] = df[column].apply(self.clean_string_field)
                elif cleaning_type == 'email':
                    df[column] = df[column].apply(self.clean_email_field)
                elif cleaning_type == 'cedula':
                    df[column] = df[column].apply(self.clean_cedula_field)
                elif cleaning_type == 'numeric':
                    df[column] = df[column].apply(self.clean_numeric_field)
                elif cleaning_type == 'integer':
                    df[column] = df[column].apply(self.clean_integer_field)
                elif cleaning_type == 'date':
                    df[column] = df[column].apply(self.clean_date_field)
                elif cleaning_type == 'boolean':
                    df[column] = df[column].apply(self.clean_boolean_field)
        
        return df

    def clean_email_field(self, value: Any) -> str:
        """Limpiar campo de correo electrónico (permitir @ . _ + -)"""
        if pd.isna(value) or value is None:
            return ""
        email = str(value).strip()
        # quitar espacios internos
        email = re.sub(r'\s+', '', email)
        # permitir letras, números y . _ + - @
        email = re.sub(r'[^A-Za-z0-9._+\-@]', '', email)
        return email
    
    def remove_duplicates_by_column(self, df: pd.DataFrame, column: str) -> pd.DataFrame:
        """Eliminar duplicados basándose en una columna específica"""
        original_len = len(df)
        df_cleaned = df.drop_duplicates(subset=[column], keep='first')
        removed_count = original_len - len(df_cleaned)
        
        if removed_count > 0:
            logger.info(f"🧹 Eliminados {removed_count} duplicados basándose en columna '{column}'")
        
        return df_cleaned
    
    def validate_required_fields(self, df: pd.DataFrame, required_fields: List[str]) -> pd.DataFrame:
        """Validar que los campos requeridos no estén vacíos"""
        original_len = len(df)
        
        for field in required_fields:
            if field in df.columns:
                # Eliminar filas donde el campo requerido esté vacío
                df = df[df[field].notna() & (df[field] != '')]
        
        removed_count = original_len - len(df)
        if removed_count > 0:
            logger.info(f"🧹 Eliminadas {removed_count} filas con campos requeridos vacíos")
        
        return df 