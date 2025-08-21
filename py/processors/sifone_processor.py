#!/usr/bin/env python3
"""
Procesador específico para archivos de Sifone (asociados y cartera)
"""

import logging
import pandas as pd
import warnings
from typing import List, Dict, Any
from core.base_processor import BaseProcessor

# Suprimir warnings de pandas sobre conexiones de base de datos
warnings.filterwarnings('ignore', category=UserWarning, module='pandas.io.sql')
warnings.filterwarnings('ignore', message='pandas only supports SQLAlchemy')

logger = logging.getLogger(__name__)

class SifoneProcessor(BaseProcessor):
    """Procesador específico para archivos de Sifone"""
    
    def __init__(self):
        """Inicializar el procesador de Sifone"""
        super().__init__("SifoneProcessor")
        
        # Columnas requeridas para asociados (mínimas para validación)
        self.asociados_required_columns = [
            'cedula', 'nombre'
        ]
        
        # Columnas requeridas para cartera (mínimas para validación)
        self.cartera_required_columns = [
            'cedula', 'tipopr'
        ]
    
    def process_files(self):
        """Procesar todos los archivos de Sifone"""
        logger.info("🚀 Iniciando procesamiento de archivos de Sifone")
        
        try:
            # Procesar archivos de asociados
            self.process_asociados_files()
            # Insertar cédulas únicas en control_asociados
            self.insert_control_asociados()
            
            # Procesar archivos de cartera (mora)
            self.process_cartera_mora_files()

            # Procesar archivos de cartera aseguradora
            self.process_cartera_aseguradora_files()
            
            logger.info("🎉 Procesamiento de Sifone completado")
            
        except Exception as e:
            logger.error(f"❌ Error en procesamiento de Sifone: {e}")
            raise
    
    def process_asociados_files(self):
        """Procesar archivos de asociados"""
        logger.info("🔄 Iniciando procesamiento de archivos de asociados")
        
        files = self.get_files_by_type('sifone', 'asociados')
        if not files:
            logger.warning("⚠️ No se encontraron archivos de asociados para procesar")
            return
        
        total_inserted = 0
        
        for file_path in files:
            try:
                # Procesar archivo
                data = self.process_asociados_file(file_path)
                
                # Truncar tabla antes de insertar
                self.truncate_table('sifone_asociados')
                
                # Insertar en base de datos
                if data:
                    inserted = self.insert_data('sifone_asociados', data, 
                                             check_duplicates=False)  # Ya truncamos la tabla
                    total_inserted += inserted
                
            except Exception as e:
                logger.error(f"❌ Error procesando archivo {file_path}: {e}")
                continue
        
        logger.info(f"✅ Procesamiento de asociados completado. Total insertados: {total_inserted}")
    
    def process_asociados_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Procesar archivo de asociados y retornar datos procesados"""
        try:
            logger.info(f"🔄 Procesando archivo: {file_path}")
            
            # Leer archivo Excel
            df = self.excel_processor.read_excel_file(file_path)
            
            # Validar columnas requeridas
            if not self.excel_processor.validate_columns(df, self.asociados_required_columns):
                return []
            
            # Limpiar datos
            df = self.excel_processor.clean_dataframe(df, drop_na_columns=['cedula'])
            
            # Limpiar columnas específicas - incluir todos los campos disponibles
            column_mapping = {
                'cedula': 'cedula',
                'clased': 'string',
                'codigo': 'string',
                'antigu': 'string',
                'nombre': 'string',
                'fechai': 'date',
                'fecnac': 'date',
                'ciunac': 'string',
                'ahorro': 'numeric',
                'otroah': 'numeric',
                'aporte': 'numeric',
                'acumul': 'numeric',
                'saldoc': 'numeric',
                'saldoa': 'numeric',
                'direcc': 'string',
                'vereda': 'string',
                'ciudad': 'string',
                'depart': 'string',
                'celula': 'string',
                'mail': 'email',
                'telefo': 'string',
                'coment': 'string',
                'cencos': 'string',
                'nomcen': 'string',
                'compan': 'string',
                'cuotaf': 'numeric',
                'fecing': 'date',
                'salari': 'numeric',
                'salint': 'numeric',
                'aprend': 'numeric',
                'porarp': 'numeric',
                'otroin': 'numeric',
                'endeu': 'numeric',
                'porend': 'numeric',
                'xnomina': 'string',
                'cuenta': 'string',
                'banco': 'string',
                'tipocu': 'string',
                'sexo': 'string',
                'estrat': 'string',
                'escola': 'string',
                'cabfam': 'numeric',
                'emplea': 'string',
                'ocupac': 'string',
                'jorlab': 'string',
                'locali': 'string',
                'barrio': 'string',
                'tipoco': 'string',
                'fechav': 'string',
                'quince': 'string',
                'estadc': 'string',
                'socio': 'string',
                'directi': 'string',
                'ciuced': 'string',
                'dirlab': 'string',
                'tellab': 'string',
                'ciulab': 'string',
                'ocupro': 'string',
                'actciu': 'string',
                'otrosdctos': 'string',
                'fecexp': 'date',
                'grupo_sang': 'string',
                'rh': 'string',
                'egresos': 'numeric',
                'pasivos': 'numeric',
                'activos': 'numeric',
                'tipo_vvda': 'string',
                'pers_cargo': 'numeric',
                'form_ing': 'string',
                'conc_ing': 'string',
                'regimen': 'string',
                'asisasamb': 'string',
                'fcesant': 'string',
                'fpensio': 'string',
                'eps': 'string',
                'per_planta': 'string',
                'per_admven': 'string',
                'scorin': 'numeric',
                'pep': 'string',
                'decorf': 'string',
                'cons_cdr': 'string',
                'fecha_act': 'date',
                'pais': 'string',
                'paisrf': 'numeric'
            }
            df = self.data_cleaner.clean_dataframe_columns(df, column_mapping)
            
            # Eliminar duplicados por cédula
            df = self.data_cleaner.remove_duplicates_by_column(df, 'cedula')
            
            # Procesar cada registro
            processed_data = []
            for index, row in df.iterrows():
                try:
                    # Procesar solo los campos más importantes para evitar errores de tipos
                    processed_data.append({
                        'cedula': row['cedula'],
                        'clased': self.data_cleaner.clean_string_field(row['clased']),
                        'codigo': self.data_cleaner.clean_string_field(row['codigo']),
                        'antigu': self.data_cleaner.clean_string_field(row['antigu']),
                        'nombre': row['nombre'],
                        'fechai': self.data_cleaner.clean_date_field(row['fechai']),
                        'fecnac': self.data_cleaner.clean_date_field(row['fecnac']),
                        'ciunac': self.data_cleaner.clean_string_field(row['ciunac']),
                        'ahorro': self.data_cleaner.clean_numeric_field(row['ahorro']),
                        'otroah': self.data_cleaner.clean_numeric_field(row['otroah']),
                        'aporte': self.data_cleaner.clean_numeric_field(row['aporte']),
                        'acumul': self.data_cleaner.clean_numeric_field(row['acumul']),
                        'saldoc': self.data_cleaner.clean_numeric_field(row['saldoc']),
                        'saldoa': self.data_cleaner.clean_numeric_field(row['saldoa']),
                        'direcc': self.data_cleaner.clean_string_field(row['direcc']),
                        'vereda': self.data_cleaner.clean_string_field(row['vereda']),
                        'ciudad': self.data_cleaner.clean_string_field(row['ciudad']),
                        'depart': row['depart'],
                        'celula': row['celula'],
                        'mail': row['mail'],
                        'telefo': self.data_cleaner.clean_string_field(row['telefo']),
                        'coment': self.data_cleaner.clean_string_field(row['coment']),
                        'cencos': self.data_cleaner.clean_string_field(row['cencos']),
                        'nomcen': self.data_cleaner.clean_string_field(row['nomcen']),
                        'compan': self.data_cleaner.clean_string_field(row['compan']),
                        'cuotaf': self.data_cleaner.clean_numeric_field(row['cuotaf']),
                        'fecing': self.data_cleaner.clean_date_field(row['fecing']),
                        'salari': self.data_cleaner.clean_numeric_field(row['salari']),
                        'salint': self.data_cleaner.clean_numeric_field(row['salint']),
                        'aprend': self.data_cleaner.clean_numeric_field(row['aprend']),
                        'porarp': self.data_cleaner.clean_numeric_field(row['porarp']),
                        'otroin': self.data_cleaner.clean_numeric_field(row['otroin']),
                        'endeu': self.data_cleaner.clean_numeric_field(row['endeu']),
                        'porend': self.data_cleaner.clean_numeric_field(row['porend']),
                        'xnomina': self.data_cleaner.clean_string_field(row['xnomina']),
                        'cuenta': self.data_cleaner.clean_string_field(row['cuenta']),
                        'banco': self.data_cleaner.clean_string_field(row['banco']),
                        'tipocu': self.data_cleaner.clean_string_field(row['tipocu']),
                        'sexo': self.data_cleaner.clean_string_field(row['sexo']),
                        'estrat': self.data_cleaner.clean_string_field(row['estrat']),
                        'escola': self.data_cleaner.clean_string_field(row['escola']),
                        'cabfam': self.data_cleaner.clean_numeric_field(row['cabfam']),
                        'emplea': self.data_cleaner.clean_string_field(row['emplea']),
                        'ocupac': self.data_cleaner.clean_string_field(row['ocupac']),
                        'jorlab': self.data_cleaner.clean_string_field(row['jorlab']),
                        'locali': self.data_cleaner.clean_string_field(row['locali']),
                        'barrio': self.data_cleaner.clean_string_field(row['barrio']),
                        'tipoco': self.data_cleaner.clean_string_field(row['tipoco']),
                        'fechav': self.data_cleaner.clean_string_field(row['fechav']),
                        'quince': self.data_cleaner.clean_string_field(row['quince']),
                        'estadc': self.data_cleaner.clean_string_field(row['estadc']),
                        'socio': self.data_cleaner.clean_string_field(row['socio']),
                        'directi': self.data_cleaner.clean_string_field(row['directi']),
                        'ciuced': self.data_cleaner.clean_string_field(row['ciuced']),
                        'dirlab': self.data_cleaner.clean_string_field(row['dirlab']),
                        'tellab': self.data_cleaner.clean_string_field(row['tellab']),
                        'ciulab': self.data_cleaner.clean_string_field(row['ciulab']),
                        'ocupro': self.data_cleaner.clean_string_field(row['ocupro']),
                        'actciu': self.data_cleaner.clean_string_field(row['actciu']),
                        'otrosdctos': self.data_cleaner.clean_string_field(row['otrosdctos']),
                        'fecexp': self.data_cleaner.clean_date_field(row['fecexp']),
                        'grupo_sang': self.data_cleaner.clean_string_field(row['grupo_sang']),
                        'rh': self.data_cleaner.clean_string_field(row['rh']),
                        'egresos': self.data_cleaner.clean_numeric_field(row['egresos']),
                        'pasivos': self.data_cleaner.clean_numeric_field(row['pasivos']),
                        'activos': self.data_cleaner.clean_numeric_field(row['activos']),
                        'tipo_vvda': self.data_cleaner.clean_string_field(row['tipo_vvda']),
                        'pers_cargo': self.data_cleaner.clean_numeric_field(row['pers_cargo']),
                        'form_ing': self.data_cleaner.clean_string_field(row['form_ing']),
                        'conc_ing': self.data_cleaner.clean_string_field(row['conc_ing']),
                        'regimen': self.data_cleaner.clean_string_field(row['regimen']),
                        'asisasamb': self.data_cleaner.clean_string_field(row['asisasamb']),
                        'fcesant': self.data_cleaner.clean_string_field(row['fcesant']),  # Cambiado a string
                        'fpensio': self.data_cleaner.clean_string_field(row['fpensio']),  # Cambiado a string
                        'eps': self.data_cleaner.clean_string_field(row['eps']),
                        'per_planta': self.data_cleaner.clean_string_field(row['per_planta']),
                        'per_admven': self.data_cleaner.clean_string_field(row['per_admven']),
                        'scorin': self.data_cleaner.clean_numeric_field(row['scorin']),
                        'pep': self.data_cleaner.clean_string_field(row['pep']),
                        'decorf': self.data_cleaner.clean_string_field(row['decorf']),
                        'cons_cdr': self.data_cleaner.clean_string_field(row['cons_cdr']),
                        'fecha_act': self.data_cleaner.clean_date_field(row['fecha_act']),
                        'pais': self.data_cleaner.clean_string_field(row['pais']),
                        'paisrf': self.data_cleaner.clean_numeric_field(row['paisrf'])
                    })
                    
                except Exception as e:
                    logger.warning(f"⚠️ Error procesando fila {index + 1}: {e}")
                    continue
            
            logger.info(f"✅ Procesados {len(processed_data)} registros de {file_path}")
            return processed_data
            
        except Exception as e:
            logger.error(f"❌ Error procesando archivo {file_path}: {e}")
            return []
    
    def process_cartera_mora_files(self):
        """Procesar archivos de cartera en mora"""
        logger.info("🔄 Iniciando procesamiento de archivos de cartera en mora")
        
        files = self.get_files_by_type('sifone', 'cartera_mora')
        if not files:
            logger.warning("⚠️ No se encontraron archivos de cartera (mora) para procesar")
            return
        
        total_inserted = 0
        
        for file_path in files:
            try:
                # Procesar archivo
                data = self.process_cartera_mora_file(file_path)
                
                # Truncar tabla antes de insertar
                self.truncate_table('sifone_cartera_mora')
                
                # Insertar en base de datos
                if data:
                    inserted = self.insert_data('sifone_cartera_mora', data, 
                                             check_duplicates=False)  # Ya truncamos la tabla
                    total_inserted += inserted
                
            except Exception as e:
                logger.error(f"❌ Error procesando archivo {file_path}: {e}")
                continue
        
        logger.info(f"✅ Procesamiento de cartera (mora) completado. Total insertados: {total_inserted}")
    
    def process_cartera_mora_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Procesar archivo de cartera en mora y retornar datos procesados"""
        try:
            logger.info(f"🔄 Procesando archivo: {file_path}")
            
            # Leer archivo Excel
            df = self.excel_processor.read_excel_file(file_path)
            
            # Validar columnas requeridas
            if not self.excel_processor.validate_columns(df, self.cartera_required_columns):
                return []
            
            # Limpiar datos
            df = self.excel_processor.clean_dataframe(df, drop_na_columns=['cedula'])
            
            # Limpiar columnas específicas - incluir todos los campos disponibles
            column_mapping = {
                'nombre': 'string',
                'telefo': 'string',
                'cedula': 'cedula',
                'codigo': 'string',
                'ahorro': 'numeric',
                'volunt': 'numeric',
                'aporte': 'numeric',
                'presta': 'string',
                'formap': 'string',
                'cantid': 'numeric',
                'sdomor': 'numeric',
                'tipopr': 'string',
                'fechae': 'date',
                'tasain': 'numeric',
                'diav': 'numeric',
                'fechap': 'date',
                'provis': 'numeric',
                'ultpag': 'date',
                'direcc': 'string',
                'pagadu': 'string',
                'celula': 'string',
                'ciures': 'string',
                'cencos': 'string',
                'nomcen': 'string',
                'barrio': 'string',
                'mail': 'email',
                'intmora': 'numeric'
            }
            df = self.data_cleaner.clean_dataframe_columns(df, column_mapping)
            
            # Procesar cada registro
            processed_data = []
            for index, row in df.iterrows():
                try:
                    processed_data.append({
                        'nombre': self.data_cleaner.clean_string_field(row['nombre']),
                        'telefo': self.data_cleaner.clean_string_field(row['telefo']),
                        'cedula': row['cedula'],
                        'codigo': self.data_cleaner.clean_string_field(row['codigo']),
                        'ahorro': self.data_cleaner.clean_numeric_field(row['ahorro']),
                        'volunt': self.data_cleaner.clean_numeric_field(row['volunt']),
                        'aporte': self.data_cleaner.clean_numeric_field(row['aporte']),
                        'presta': self.data_cleaner.clean_string_field(row['presta']),
                        'formap': self.data_cleaner.clean_string_field(row['formap']),
                        'cantid': self.data_cleaner.clean_numeric_field(row['cantid']),
                        'sdomor': self.data_cleaner.clean_numeric_field(row['sdomor']),
                        'tipopr': row['tipopr'],
                        'fechae': self.data_cleaner.clean_date_field(row['fechae']),
                        'tasain': self.data_cleaner.clean_numeric_field(row['tasain']),
                        'diav': self.data_cleaner.clean_numeric_field(row['diav']),
                        'fechap': row['fechap'],
                        'provis': self.data_cleaner.clean_numeric_field(row['provis']),
                        'ultpag': self.data_cleaner.clean_date_field(row['ultpag']),
                        'direcc': self.data_cleaner.clean_string_field(row['direcc']),
                        'pagadu': self.data_cleaner.clean_string_field(row['pagadu']),
                        'celula': self.data_cleaner.clean_string_field(row['celula']),
                        'ciures': self.data_cleaner.clean_string_field(row['ciures']),
                        'cencos': self.data_cleaner.clean_string_field(row['cencos']),
                        'nomcen': self.data_cleaner.clean_string_field(row['nomcen']),
                        'barrio': self.data_cleaner.clean_string_field(row['barrio']),
                        'mail': self.data_cleaner.clean_string_field(row['mail']),
                        'intmora': self.data_cleaner.clean_numeric_field(row['intmora'])
                    })
                    
                except Exception as e:
                    logger.warning(f"⚠️ Error procesando fila {index + 1}: {e}")
                    continue
            
            logger.info(f"✅ Procesados {len(processed_data)} registros de {file_path}")
            return processed_data
            
        except Exception as e:
            logger.error(f"❌ Error procesando archivo {file_path}: {e}")
            return []

    def process_cartera_aseguradora_files(self):
        """Procesar archivos de cartera aseguradora"""
        logger.info("🔄 Iniciando procesamiento de archivos de cartera aseguradora")

        files = self.get_files_by_type('sifone', 'aseguradora')
        if not files:
            logger.warning("⚠️ No se encontraron archivos de cartera aseguradora para procesar")
            return

        total_inserted = 0

        for file_path in files:
            try:
                data = self.process_cartera_aseguradora_file(file_path)

                # Truncar tabla antes de insertar
                self.truncate_table('sifone_cartera_aseguradora')

                if data:
                    inserted = self.insert_data('sifone_cartera_aseguradora', data, check_duplicates=False)
                    total_inserted += inserted

            except Exception as e:
                logger.error(f"❌ Error procesando archivo {file_path}: {e}")
                continue

        logger.info(f"✅ Procesamiento de cartera aseguradora completado. Total insertados: {total_inserted}")

    def process_cartera_aseguradora_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Procesar archivo de cartera aseguradora y retornar datos procesados"""
        try:
            logger.info(f"🔄 Procesando archivo: {file_path}")

            df = self.excel_processor.read_excel_file(file_path)

            # Columnas mínimas requeridas para evitar fallos
            aseguradora_required_columns = ['cedula']
            if not self.excel_processor.validate_columns(df, aseguradora_required_columns):
                return []

            df = self.excel_processor.clean_dataframe(df, drop_na_columns=['cedula'])

            column_mapping = {
                'cedula': 'cedula',
                'numero': 'string',
                'priape': 'string',
                'segape': 'string',
                'nombr1': 'string',
                'nombre': 'string',
                'fecnac': 'date',
                'fechae': 'date',
                'plazo': 'numeric',
                'sexo': 'string',
                'edad': 'integer',
                'ahorro': 'numeric',
                'carter': 'numeric',
                'tipopr': 'string',
                'valorc': 'numeric',
                'tasa': 'numeric'
            }
            df = self.data_cleaner.clean_dataframe_columns(df, column_mapping)

            processed_data = []
            for index, row in df.iterrows():
                try:
                    # Normalizar tasa a fracción si viene en porcentaje o con magnitud alta
                    tasa_value = self.data_cleaner.clean_numeric_field(row.get('tasa'))
                    # Si es None, dejar en 0.0
                    if tasa_value is None:
                        tasa_value = 0.0
                    # Reducir magnitud hasta que encaje en DECIMAL(5,4)
                    # Asumimos que valores > 1 están expresados en porcentaje
                    # Primero si está entre 1 y 100, dividir una vez por 100
                    if tasa_value >= 1:
                        tasa_value = tasa_value / 100.0
                    # Si sigue siendo muy grande, dividir por 10 repetidamente hasta <= 9.9999
                    iteration_guard = 0
                    while tasa_value > 9.9999 and iteration_guard < 5:
                        tasa_value = tasa_value / 10.0
                        iteration_guard += 1
                    tasa_value = round(tasa_value, 4)

                    processed_data.append({
                        'cedula': row['cedula'],
                        'numero': self.data_cleaner.clean_string_field(row.get('numero')),
                        'priape': self.data_cleaner.clean_string_field(row.get('priape')),
                        'segape': self.data_cleaner.clean_string_field(row.get('segape')),
                        'nombr1': self.data_cleaner.clean_string_field(row.get('nombr1')),
                        'nombre': self.data_cleaner.clean_string_field(row.get('nombre')),
                        'fecnac': self.data_cleaner.clean_date_field(row.get('fecnac')),
                        'fechae': self.data_cleaner.clean_date_field(row.get('fechae')),
                        'plazo': self.data_cleaner.clean_numeric_field(row.get('plazo')),
                        'sexo': self.data_cleaner.clean_string_field(row.get('sexo')),
                        'edad': self.data_cleaner.clean_integer_field(row.get('edad')),
                        'ahorro': self.data_cleaner.clean_numeric_field(row.get('ahorro')),
                        'carter': self.data_cleaner.clean_numeric_field(row.get('carter')),
                        'tipopr': self.data_cleaner.clean_string_field(row.get('tipopr')),
                        'valorc': self.data_cleaner.clean_numeric_field(row.get('valorc')),
                        'tasa': tasa_value,
                    })
                except Exception as e:
                    logger.warning(f"⚠️ Error procesando fila {index + 1}: {e}")
                    continue

            logger.info(f"✅ Procesados {len(processed_data)} registros de {file_path}")
            return processed_data

        except Exception as e:
            logger.error(f"❌ Error procesando archivo {file_path}: {e}")
            return []
    
    def insert_control_asociados(self):
        """Insertar cédulas únicas en control_asociados"""
        try:
            logger.info("🔄 Insertando cédulas únicas en control_asociados")

            # Asegurar existencia de la tabla (no falla si ya existe)
            try:
                conn = self.db_manager.get_connection()
                cur = conn.cursor()
                cur.execute(
                    """
                    CREATE TABLE IF NOT EXISTS control_asociados (
                        cedula VARCHAR(20) UNIQUE PRIMARY KEY,
                        estado_activo BOOLEAN DEFAULT TRUE,
                        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                    """
                )
                conn.commit()
                cur.close()
            except Exception as e:
                logger.warning(f"⚠️ No se pudo asegurar la tabla control_asociados: {e}")

            # Obtener cédulas únicas de sifone_asociados
            query = "SELECT DISTINCT cedula FROM sifone_asociados"
            results = self.db_manager.execute_query(query)

            if not results:
                logger.warning("⚠️ No se encontraron cédulas para insertar en control_asociados")
                return

            cedulas = [row[0] for row in results if row[0]]
            logger.info(f"📊 Encontradas {len(cedulas)} cédulas únicas")

            # Preparar datos para inserción
            values = [(cedula, 1) for cedula in cedulas]

            # Insertar usando INSERT ... ON DUPLICATE KEY UPDATE
            insert_query = (
                "INSERT INTO control_asociados (cedula, estado_activo) VALUES (%s, %s) "
                "ON DUPLICATE KEY UPDATE fecha_actualizacion = CURRENT_TIMESTAMP"
            )

            inserted_count = self.db_manager.execute_many(insert_query, values)
            logger.info(f"✅ Insertadas/actualizadas {inserted_count} cédulas en control_asociados")

        except Exception as e:
            logger.error(f"❌ Error insertando cédulas en control_asociados: {e}")
            # No relanzamos para no detener el flujo principal