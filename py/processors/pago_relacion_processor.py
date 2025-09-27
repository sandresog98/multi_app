"""
Procesador automático para crear relaciones entre pagos PSE y Confiar
"""

import pandas as pd
import logging
import warnings
from typing import List, Dict, Any, Tuple
from datetime import datetime
import sys
import os
# Asegurar que el proyecto raíz esté en sys.path usando __file__ correctamente
CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(CURRENT_DIR)
if PROJECT_ROOT not in sys.path:
    sys.path.append(PROJECT_ROOT)

from core.base_processor import BaseProcessor
from core.database import DatabaseManager

# Suprimir warnings de pandas sobre conexiones de base de datos
warnings.filterwarnings('ignore', category=UserWarning, module='pandas.io.sql')
warnings.filterwarnings('ignore', message='pandas only supports SQLAlchemy')


class PagoRelacionProcessor(BaseProcessor):
    """
    Procesador para crear relaciones automáticas entre pagos PSE y Confiar
    """
    
    def __init__(self):
        super().__init__("PagoRelacionProcessor")
        self.logger = logging.getLogger(__name__)
        
    def process_automatic_relations(self) -> Dict[str, Any]:
        """
        Procesa las relaciones automáticas entre PSE y Confiar
        """
        try:
            self.logger.info("Iniciando procesamiento de relaciones automáticas...")
            
            # Obtener datos de PSE y Confiar
            pse_data = self._get_pse_data()
            confiar_data = self._get_confiar_data()
            
            if pse_data.empty or confiar_data.empty:
                self.logger.warning("No hay datos de PSE o Confiar para procesar")
                return {'success': False, 'message': 'No hay datos para procesar'}
            
            # Método 1: Relaciones directas (fecha y valor iguales)
            relaciones_directas = self._find_direct_relations(pse_data, confiar_data)
            
            # Método 2: Relaciones agrupadas (suma de valores)
            relaciones_agrupadas = self._find_grouped_relations(pse_data, confiar_data)
            
            # Combinar resultados
            todas_relaciones = relaciones_directas + relaciones_agrupadas
            
            # Filtrar duplicados y validar
            relaciones_validas = self._validate_relations(todas_relaciones)
            
            # Mostrar resultados de prueba
            self._show_test_results(relaciones_validas, pse_data, confiar_data)
            
            return {
                'success': True,
                'direct_relations': len(relaciones_directas),
                'grouped_relations': len(relaciones_agrupadas),
                'total_valid': len(relaciones_validas),
                'relations': relaciones_validas
            }
            
        except Exception as e:
            self.logger.error(f"Error en procesamiento de relaciones: {e}")
            return {'success': False, 'message': str(e)}
    
    def _get_pse_data(self) -> pd.DataFrame:
        """Obtiene datos de PSE aprobados (sin usar pandas.read_sql para evitar dependencia de sqlite)."""
        try:
            query = (
                "SELECT pse_id, DATE(fecha_hora_resolucion_de_la_transaccion) AS fecha_resolucion, "
                "valor, ciclo_transaccion, estado "
                "FROM banco_pse WHERE estado = 'Aprobada' "
                "ORDER BY fecha_hora_resolucion_de_la_transaccion"
            )
            with self.db_manager.get_connection() as conn:
                cursor = conn.cursor(dictionary=True)
                cursor.execute(query)
                rows = cursor.fetchall()
                cursor.close()
            df = pd.DataFrame(rows)
            self.logger.info(f"Obtenidos {len(df)} registros PSE aprobados")
            return df
        except Exception as e:
            self.logger.error(f"Error obteniendo datos PSE: {e}")
            return pd.DataFrame()
    
    def _get_confiar_data(self) -> pd.DataFrame:
        """Obtiene datos de Confiar disponibles (sin pandas.read_sql)."""
        try:
            query = (
                "SELECT confiar_id, fecha, valor_consignacion, descripcion "
                "FROM banco_confiar ORDER BY fecha"
            )
            with self.db_manager.get_connection() as conn:
                cursor = conn.cursor(dictionary=True)
                cursor.execute(query)
                rows = cursor.fetchall()
                cursor.close()
            df = pd.DataFrame(rows)
            self.logger.info(f"Obtenidos {len(df)} registros Confiar")
            return df
        except Exception as e:
            self.logger.error(f"Error obteniendo datos Confiar: {e}")
            return pd.DataFrame()
    
    def _find_direct_relations(self, pse_data: pd.DataFrame, confiar_data: pd.DataFrame) -> List[Dict[str, Any]]:
        """
        Método 1: Encuentra relaciones directas donde fecha y valor son iguales
        """
        relaciones = []
        
        for _, pse_row in pse_data.iterrows():
            # Buscar Confiar con misma fecha y valor
            matches = confiar_data[
                (confiar_data['fecha'] == pse_row['fecha_resolucion']) &
                (confiar_data['valor_consignacion'] == pse_row['valor'])
            ]
            
            for _, confiar_row in matches.iterrows():
                relaciones.append({
                    'pse_id': pse_row['pse_id'],
                    'confiar_id': confiar_row['confiar_id'],
                    'method': 'direct',
                    'pse_fecha': pse_row['fecha_resolucion'],
                    'pse_valor': pse_row['valor'],
                    'confiar_fecha': confiar_row['fecha'],
                    'confiar_valor': confiar_row['valor_consignacion'],
                    'confidence': 'high'
                })
        
        self.logger.info(f"Encontradas {len(relaciones)} relaciones directas")
        return relaciones
    
    def _find_grouped_relations(self, pse_data: pd.DataFrame, confiar_data: pd.DataFrame) -> List[Dict[str, Any]]:
        """
        Método 2: Encuentra relaciones agrupadas por fecha y ciclo
        """
        relaciones = []
        
        # Agrupar PSE por fecha y ciclo
        pse_grouped = pse_data.groupby(['fecha_resolucion', 'ciclo_transaccion']).agg({
            'pse_id': list,
            'valor': 'sum'
        }).reset_index()
        
        for _, group_row in pse_grouped.iterrows():
            # Buscar Confiar con misma fecha y valor total
            matches = confiar_data[
                (confiar_data['fecha'] == group_row['fecha_resolucion']) &
                (confiar_data['valor_consignacion'] == group_row['valor'])
            ]
            
            for _, confiar_row in matches.iterrows():
                # Crear relación para cada PSE del grupo
                for pse_id in group_row['pse_id']:
                    relaciones.append({
                        'pse_id': pse_id,
                        'confiar_id': confiar_row['confiar_id'],
                        'method': 'grouped',
                        'pse_fecha': group_row['fecha_resolucion'],
                        'pse_valor': group_row['valor'],
                        'confiar_fecha': confiar_row['fecha'],
                        'confiar_valor': confiar_row['valor_consignacion'],
                        'confidence': 'medium',
                        'group_size': len(group_row['pse_id'])
                    })
        
        self.logger.info(f"Encontradas {len(relaciones)} relaciones agrupadas")
        return relaciones
    
    def _validate_relations(self, relaciones: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Valida las relaciones y elimina duplicados
        """
        # Obtener relaciones existentes
        existing_relations = self._get_existing_relations()
        
        # Filtrar relaciones válidas
        valid_relations = []
        seen_pse_ids = set()
        seen_pairs = set()
        
        for relation in relaciones:
            pse_id = relation['pse_id']
            confiar_id = relation['confiar_id']
            relation_pair = (pse_id, confiar_id)
            
            # Verificar que no existe ya la relación exacta en BD
            if relation_pair in existing_relations:
                # self.logger.info(f"Relación existente (pse_id={pse_id}, confiar_id={confiar_id}), saltando...")
                continue
            
            # Verificar que no haya duplicados del mismo par en esta ejecución
            if relation_pair in seen_pairs:
                # self.logger.info(f"Relación duplicada en esta ejecución (pse_id={pse_id}, confiar_id={confiar_id}), saltando...")
                continue
            
            # (Opcional) Restringir a una sola relación por PSE en la misma ejecución
            if pse_id in seen_pse_ids:
                # self.logger.info(f"PSE {pse_id} duplicado en esta ejecución, saltando...")
                continue
            
            seen_pairs.add(relation_pair)
            seen_pse_ids.add(pse_id)
            valid_relations.append(relation)
        
        self.logger.info(f"Relaciones válidas después de validación: {len(valid_relations)}")
        return valid_relations
    
    def _get_existing_relations(self) -> set:
        """Obtiene las relaciones existentes"""
        try:
            query = "SELECT pse_id, confiar_id FROM banco_asignacion_pse"
            
            with self.db_manager.get_connection() as conn:
                cursor = conn.cursor()
                cursor.execute(query)
                results = cursor.fetchall()
            
            return {(row[0], row[1]) for row in results}
            
        except Exception as e:
            self.logger.error(f"Error obteniendo relaciones existentes: {e}")
            return set()
    
    def _show_test_results(self, relaciones: List[Dict[str, Any]], pse_data: pd.DataFrame, confiar_data: pd.DataFrame):
        """
        Muestra los resultados de la prueba
        """
        print("\n" + "="*60)
        print("RESULTADOS DE PRUEBA - RELACIONES AUTOMÁTICAS")
        print("="*60)
        
        print(f"\n📊 ESTADÍSTICAS:")
        print(f"   • PSE aprobados disponibles: {len(pse_data)}")
        print(f"   • Confiar disponibles: {len(confiar_data)}")
        print(f"   • Relaciones encontradas: {len(relaciones)}")
        
        if relaciones:
            print(f"\n🔗 RELACIONES PROPUESTAS:")
            for i, rel in enumerate(relaciones[:10], 1):  # Mostrar solo las primeras 10
                print(f"   {i}. PSE: {rel['pse_id']} → Confiar: {rel['confiar_id']}")
                print(f"      Método: {rel['method']} | Confianza: {rel['confidence']}")
                print(f"      Fecha: {rel['pse_fecha']} | Valor: ${rel['pse_valor']:,}")
                if rel['method'] == 'grouped':
                    print(f"      Tamaño grupo: {rel['group_size']} PSE")
                print()
            
            if len(relaciones) > 10:
                print(f"   ... y {len(relaciones) - 10} relaciones más")
        else:
            print("\n❌ No se encontraron relaciones automáticas")
        
        print("\n" + "="*60)
    
    def insert_relations(self, relaciones: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Inserta las relaciones en la base de datos
        """
        if not relaciones:
            return {'success': False, 'message': 'No hay relaciones para insertar'}
        
        try:
            insert_query = """
                INSERT IGNORE INTO banco_asignacion_pse (pse_id, confiar_id, tipo_asignacion, fecha_validacion)
                VALUES (%s, %s, %s, CURRENT_TIMESTAMP)
            """
            
            # Mapear método a tipo_asignacion
            values = []
            for rel in relaciones:
                tipo = 'directa' if rel.get('method') == 'direct' else 'grupal' if rel.get('method') == 'grouped' else None
                values.append((rel['pse_id'], rel['confiar_id'], tipo))
            
            with self.db_manager.get_connection() as conn:
                cursor = conn.cursor()
                cursor.executemany(insert_query, values)
                conn.commit()
            
            self.logger.info(f"Insertadas {len(relaciones)} relaciones exitosamente")
            return {'success': True, 'message': f'Insertadas {len(relaciones)} relaciones'}
            
        except Exception as e:
            self.logger.error(f"Error insertando relaciones: {e}")
            return {'success': False, 'message': str(e)}