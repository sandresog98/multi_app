#!/usr/bin/env python3
"""
Gestor de base de datos centralizado
"""

import mysql.connector
import logging
import pandas as pd
from typing import List, Dict, Any, Optional
from mysql.connector import Error
from config.settings import DB_CONFIG, PROCESSING_CONFIG

logger = logging.getLogger(__name__)

class DatabaseManager:
    """Gestor centralizado de conexiones y operaciones de base de datos"""
    
    def __init__(self):
        """Inicializar el gestor de base de datos"""
        self.connection = None
        self.setup_connection()
    
    def setup_connection(self):
        """Establecer conexión con la base de datos"""
        try:
            self.connection = mysql.connector.connect(**DB_CONFIG)
            if self.connection.is_connected():
                logger.info("✅ Conexión a la base de datos establecida")
            else:
                raise Error("No se pudo establecer la conexión")
        except Error as e:
            logger.error(f"❌ Error conectando a la base de datos: {e}")
            raise
    
    def get_connection(self):
        """Obtener la conexión actual"""
        if not self.connection or not self.connection.is_connected():
            self.setup_connection()
        return self.connection
    
    def execute_query(self, query: str, params: Optional[tuple] = None) -> List[tuple]:
        """Ejecutar una consulta y retornar resultados"""
        try:
            cursor = self.connection.cursor()
            cursor.execute(query, params or ())
            results = cursor.fetchall()
            cursor.close()
            return results
        except Error as e:
            logger.error(f"❌ Error ejecutando consulta: {e}")
            raise
    
    def execute_many(self, query: str, data: List[tuple]) -> int:
        """Ejecutar múltiples inserciones y retornar número de filas afectadas"""
        try:
            cursor = self.connection.cursor()
            cursor.executemany(query, data)
            self.connection.commit()
            affected_rows = cursor.rowcount
            cursor.close()
            return affected_rows
        except Error as e:
            logger.error(f"❌ Error ejecutando inserción múltiple: {e}")
            self.connection.rollback()
            raise
    
    def insert_batch(self, table: str, data: List[Dict[str, Any]]) -> int:
        """Insertar un lote de datos en una tabla"""
        if not data:
            logger.warning(f"⚠️ No hay datos para insertar en {table}")
            return 0
        
        try:
            # Preparar la consulta INSERT
            columns = list(data[0].keys())
            columns_str = ', '.join(columns)
            placeholders = ', '.join(['%s'] * len(columns))
            insert_query = f"INSERT INTO {table} ({columns_str}) VALUES ({placeholders})"
            
            # Convertir datos a tuplas
            values = []
            for record in data:
                row_values = []
                for column in columns:
                    value = record.get(column)
                    # Manejar valores NaN y None
                    if pd.isna(value) or value is None or str(value).lower() == 'nan':
                        row_values.append(None)
                    else:
                        row_values.append(value)
                values.append(tuple(row_values))
            
            # Insertar en lotes
            batch_size = PROCESSING_CONFIG['batch_size']
            total_inserted = 0
            
            for i in range(0, len(values), batch_size):
                batch = values[i:i + batch_size]
                inserted = self.execute_many(insert_query, batch)
                total_inserted += inserted
                logger.info(f"📊 Insertados {inserted} registros en lote {i//batch_size + 1}")
            
            logger.info(f"✅ Total de {total_inserted} registros insertados en {table}")
            return total_inserted
            
        except Error as e:
            logger.error(f"❌ Error insertando datos en {table}: {e}")
            raise
    
    def truncate_table(self, table: str):
        """Truncar una tabla"""
        try:
            cursor = self.connection.cursor()
            cursor.execute(f"TRUNCATE TABLE {table}")
            self.connection.commit()
            cursor.close()
            logger.info(f"🗑️ Tabla {table} truncada exitosamente")
        except Error as e:
            logger.error(f"❌ Error truncando tabla {table}: {e}")
            raise
    
    def check_existing_records(self, table: str, id_column: str, ids: List[str]) -> set:
        """Verificar registros existentes por ID"""
        if not ids:
            return set()
        
        try:
            placeholders = ','.join(['%s'] * len(ids))
            query = f"SELECT {id_column} FROM {table} WHERE {id_column} IN ({placeholders})"
            results = self.execute_query(query, tuple(ids))
            return {row[0] for row in results}
        except Error as e:
            logger.error(f"❌ Error verificando registros existentes: {e}")
            raise
    
    def close_connection(self):
        """Cerrar la conexión a la base de datos"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            logger.info("🔌 Conexión a la base de datos cerrada") 