#!/usr/bin/env python3
"""
Utilidades de validación
"""

import logging
from typing import Any, List, Dict
import re

logger = logging.getLogger(__name__)

class DataValidator:
    """Clase para validaciones de datos"""
    
    def __init__(self):
        """Inicializar el validador"""
        pass
    
    def validate_cedula(self, cedula: str) -> bool:
        """Validar formato de cédula colombiana"""
        if not cedula:
            return False
        
        # Eliminar espacios y caracteres especiales
        cedula_clean = re.sub(r'[^\d\-]', '', cedula)
        
        # Si tiene guión, validar formato XX-XXX-XXX
        if '-' in cedula_clean:
            pattern = r'^\d{2}-\d{3}-\d{3}$'
            return bool(re.match(pattern, cedula_clean))
        
        # Si no tiene guión, debe ser solo números
        pattern = r'^\d{7,10}$'
        return bool(re.match(pattern, cedula_clean))
    
    def validate_email(self, email: str) -> bool:
        """Validar formato de email"""
        if not email:
            return False
        
        pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        return bool(re.match(pattern, email))
    
    def validate_phone(self, phone: str) -> bool:
        """Validar formato de teléfono colombiano"""
        if not phone:
            return False
        
        # Eliminar espacios y caracteres especiales
        phone_clean = re.sub(r'[^\d]', '', phone)
        
        # Validar que tenga entre 7 y 10 dígitos
        return 7 <= len(phone_clean) <= 10
    
    def validate_numeric_range(self, value: float, min_value: float = None, 
                             max_value: float = None) -> bool:
        """Validar que un valor numérico esté en un rango"""
        if value is None:
            return False
        
        if min_value is not None and value < min_value:
            return False
        
        if max_value is not None and value > max_value:
            return False
        
        return True
    
    def validate_date_format(self, date_str: str, format: str = '%Y-%m-%d') -> bool:
        """Validar formato de fecha"""
        if not date_str:
            return False
        
        try:
            from datetime import datetime
            datetime.strptime(date_str, format)
            return True
        except ValueError:
            return False
    
    def validate_required_fields(self, data: Dict[str, Any], 
                               required_fields: List[str]) -> List[str]:
        """Validar campos requeridos en un diccionario"""
        missing_fields = []
        
        for field in required_fields:
            if field not in data or data[field] is None or data[field] == "":
                missing_fields.append(field)
        
        return missing_fields
    
    def validate_data_types(self, data: Dict[str, Any], 
                           type_mapping: Dict[str, type]) -> List[str]:
        """Validar tipos de datos en un diccionario"""
        type_errors = []
        
        for field, expected_type in type_mapping.items():
            if field in data:
                try:
                    # Intentar convertir al tipo esperado
                    expected_type(data[field])
                except (ValueError, TypeError):
                    type_errors.append(f"{field}: esperado {expected_type.__name__}")
        
        return type_errors
    
    def validate_unique_values(self, data_list: List[Dict[str, Any]], 
                             unique_fields: List[str]) -> List[str]:
        """Validar que ciertos campos sean únicos en una lista de datos"""
        duplicates = []
        
        for field in unique_fields:
            values = [record.get(field) for record in data_list if record.get(field) is not None]
            unique_values = set(values)
            
            if len(values) != len(unique_values):
                duplicates.append(field)
        
        return duplicates 