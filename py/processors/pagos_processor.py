#!/usr/bin/env python3
"""
Procesador específico para archivos de pagos (Confiar y PSE)
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

class PagosProcessor(BaseProcessor):
    """Procesador específico para archivos de pagos"""
    
    def __init__(self):
        """Inicializar el procesador de pagos"""
        super().__init__("PagosProcessor")
        
        # Columnas requeridas para Confiar
        self.confiar_required_columns = [
            'FECHA', 'DESCRIPCION', 'DOCUMENTO', 'OFICINA', 
            'VALOR CONSIGNACION', 'VALOR RETIRO', 'SALDO'
        ]
        
        # Columnas requeridas para PSE
        self.pse_required_columns = [
            'CUS', 'Valor', 'Banco Originador', 'Estado', 
            'Cod. de autorización, rechazo o fallida', 'Fecha-Hora creada',
            'Fecha-Hora último estado', 'Impuesto', 'Ticket ID',
            'Ciclo Origen', 'Ciclo Transacción', 'Servicio Código',
            'Servicio Nombre', 'Referencia 1', 'Referencia 2',
            'Referencia 3', 'Tipo de Usuario', 'Tipo de Autorización',
            'Fecha-Hora resolución de la transacción', 'Banco Recaudador',
            'Modalidad de Vinculación', 'ID Funcionalidad', 'Nombre Funcionalidad',
            'Tipo de Cuenta Destino', 'Número de Cuenta Destino',
            'Procedencia de Pago', 'Medio de Pago', 'Tipo de Dispositivo',
            'Navegador', 'Tipo de Flujo'
        ]
    
    def process_files(self):
        """Procesar todos los archivos de pagos"""
        logger.info("🚀 Iniciando procesamiento de archivos de pagos")
        
        try:
            # Procesar archivos de Confiar
            self.process_confiar_files()
            
            # Procesar archivos de PSE
            self.process_pse_files()
            
            logger.info("🎉 Procesamiento de pagos completado")
            
        except Exception as e:
            logger.error(f"❌ Error en procesamiento de pagos: {e}")
            raise
    
    def process_confiar_files(self):
        """Procesar archivos de Confiar"""
        logger.info("🔄 Iniciando procesamiento de archivos de Confiar")
        
        files = self.get_files_by_type('pagos', 'confiar')
        if not files:
            logger.warning("⚠️ No se encontraron archivos de Confiar para procesar")
            return
        
        total_inserted = 0
        
        for file_path in files:
            try:
                # Procesar archivo
                data = self.process_confiar_file(file_path)
                
                # Insertar en base de datos
                if data:
                    inserted = self.insert_data('banco_confiar', data, 
                                             check_duplicates=True, id_column='confiar_id')
                    total_inserted += inserted
                
            except Exception as e:
                logger.error(f"❌ Error procesando archivo {file_path}: {e}")
                continue
        
        logger.info(f"✅ Procesamiento de Confiar completado. Total insertados: {total_inserted}")
    
    def process_confiar_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Procesar archivo de Confiar y retornar datos procesados"""
        try:
            logger.info(f"🔄 Procesando archivo: {file_path}")
            
            # Procesar archivo con detección automática de encabezados
            df = self.excel_processor.process_excel_with_header_detection(
                file_path=file_path,
                keyword='FECHA',
                required_columns=self.confiar_required_columns,
                date_column='FECHA',
                drop_na_columns=['FECHA']
            )
            
            # Orden estable y secuencial por fecha para evitar duplicados dependientes del índice de archivo
            df = df.copy()
            # Normalizaciones numéricas y de texto para un orden determinístico
            df['__SALDO_NUM'] = pd.to_numeric(df['SALDO'], errors='coerce').fillna(0)
            df['__VAL_CONS_NUM'] = pd.to_numeric(df['VALOR CONSIGNACION'], errors='coerce').fillna(0)
            df['__VAL_RET_NUM'] = pd.to_numeric(df['VALOR RETIRO'], errors='coerce').fillna(0)
            df['__DOC_STR'] = df['DOCUMENTO'].astype(str).fillna('')
            df['__DESC_STR'] = df['DESCRIPCION'].astype(str).fillna('')
            # Orden: por FECHA y campos claves para reproducibilidad
            df = df.sort_values(by=['FECHA','__SALDO_NUM','__VAL_CONS_NUM','__VAL_RET_NUM','__DOC_STR','__DESC_STR']).reset_index(drop=True)
            # Índice secuencial por FECHA
            df['__SEQ_FECHA'] = df.groupby('FECHA').cumcount() + 1

            # Procesar cada registro con segmentación de tipo_pago
            processed_data = []
            for i, row in df.iterrows():
                try:
                    # Generar confiar_id único
                    seq = int(row['__SEQ_FECHA']) if pd.notna(row['__SEQ_FECHA']) else 0
                    confiar_id = self.generate_confiar_id(row, seq)
                    
                    tipo_pago = self.segmentar_tipo_pago(
                        descripcion=self.data_cleaner.clean_string_field(row['DESCRIPCION']),
                        valor_consignacion=self.data_cleaner.clean_numeric_field(row['VALOR CONSIGNACION'])
                    )

                    processed_data.append({
                        'confiar_id': confiar_id,
                        'fecha': row['FECHA'],
                        'descripcion': self.data_cleaner.clean_string_field(row['DESCRIPCION']),
                        'documento': self.data_cleaner.clean_string_field(row['DOCUMENTO']),
                        'oficina': self.data_cleaner.clean_string_field(row['OFICINA']),
                        'valor_consignacion': self.data_cleaner.clean_numeric_field(row['VALOR CONSIGNACION']),
                        'valor_retiro': self.data_cleaner.clean_numeric_field(row['VALOR RETIRO']),
                        'saldo': self.data_cleaner.clean_numeric_field(row['SALDO']),
                        'tipo_transaccion': tipo_pago
                    })
                    
                except Exception as e:
                    logger.warning(f"⚠️ Error procesando fila {i + 1}: {e}")
                    continue
            
            logger.info(f"✅ Procesados {len(processed_data)} registros de {file_path}")
            return processed_data
            
        except Exception as e:
            logger.error(f"❌ Error procesando archivo {file_path}: {e}")
            return []

    def segmentar_tipo_pago(self, descripcion: str, valor_consignacion: float) -> str:
        """Segmentar tipo de pago para Confiar según reglas provistas"""
        desc = (descripcion or '').lower()
        # Normalizar acentos básicos para coincidencias robustas
        desc_norm = (desc
            .replace('á','a').replace('é','e').replace('í','i').replace('ó','o').replace('ú','u')
            .replace('ü','u').replace('ñ','n'))
        if valor_consignacion and valor_consignacion > 0:
            if 'ach' in desc:
                return 'Pago ACH'
            if 'pago qr' in desc:
                return 'Pago QR'
            # Aceptar variantes con/ sin acento, mayúsculas, etc.
            # Nuevo tipo: Cheque
            # Coincide con descripciones tipo "Consignacion en Cuenta Cheque"
            if 'consignacion en cuenta cheque' in desc_norm:
                return 'Cheque'
            if 'consignacion en cuenta efectivo' in desc_norm:
                return 'Pago Efectivo'
            if 'consignacion por transf. agencia virtual' in desc_norm:
                return 'Transf. Agencia Virtual'
        return ''
    
    def generate_confiar_id(self, row: pd.Series, seq_por_fecha: int) -> str:
        """Generar ID único para Confiar usando fecha + secuencia por fecha + valor_consignacion y valor_retiro (como string sin puntos)."""
        # Usar secuencia por fecha + fecha + valores sin puntos para generar ID único
        fecha = str(row['FECHA']).split()[0]  # Solo la fecha, sin hora
        
        # valor consignación como string estable y sin puntos
        vc_raw = row.get('VALOR CONSIGNACION')
        if pd.isna(vc_raw):
            vc_str = '0'
        else:
            if isinstance(vc_raw, (int, float)):
                vc_str = f"{vc_raw:.2f}"
            else:
                vc_str = str(vc_raw)
        vc_str = vc_str.replace('.', '')

        # valor retiro como string estable y sin puntos
        vr_raw = row.get('VALOR RETIRO')
        if pd.isna(vr_raw):
            vr_str = '0'
        else:
            if isinstance(vr_raw, (int, float)):
                vr_str = f"{vr_raw:.2f}"
            else:
                vr_str = str(vr_raw)
        vr_str = vr_str.replace('.', '')
        
        # Crear ID único incluyendo la secuencia por fecha
        confiar_id = f"CONF{fecha.replace('-', '')}{seq_por_fecha}V{vc_str}R{vr_str}"
        
        return confiar_id
    
    def process_pse_files(self):
        """Procesar archivos de PSE"""
        logger.info("🔄 Iniciando procesamiento de archivos de PSE")
        
        files = self.get_files_by_type('pagos', 'pse')
        if not files:
            logger.warning("⚠️ No se encontraron archivos de PSE para procesar")
            return
        
        total_inserted = 0
        
        for file_path in files:
            try:
                # Procesar archivo
                data = self.process_pse_file(file_path)
                
                # Insertar en base de datos
                if data:
                    inserted = self.insert_data('banco_pse', data, 
                                             check_duplicates=True, id_column='pse_id')
                    total_inserted += inserted
                
            except Exception as e:
                logger.error(f"❌ Error procesando archivo {file_path}: {e}")
                continue
        
        logger.info(f"✅ Procesamiento de PSE completado. Total insertados: {total_inserted}")
    
    def process_pse_file(self, file_path: str) -> List[Dict[str, Any]]:
        """Procesar archivo de PSE y retornar datos procesados"""
        try:
            logger.info(f"🔄 Procesando archivo: {file_path}")
            
            # Leer archivo Excel
            df = self.excel_processor.read_excel_file(file_path)
            
            # Validar columnas requeridas
            if not self.excel_processor.validate_columns(df, self.pse_required_columns):
                return []
            
            # Limpiar datos - eliminar filas sin CUS
            df = self.excel_processor.clean_dataframe(df, drop_na_columns=['CUS'])
            
            # Procesar cada registro
            processed_data = []
            for index, row in df.iterrows():
                try:
                    # Generar pse_id único
                    cus_value = str(int(row['CUS']))
                    pse_id = f"CUS{cus_value}"
                    
                    processed_data.append({
                        'pse_id': pse_id,
                        'cus': int(row['CUS']),
                        'valor': int(row['Valor']),
                        'banco_originador': self.data_cleaner.clean_string_field(row['Banco Originador']),
                        'estado': self.data_cleaner.clean_string_field(row['Estado']),
                        'cod_de_autorizacion_rechazo_o_fallida': self.data_cleaner.clean_string_field(row['Cod. de autorización, rechazo o fallida']),
                        'fecha_hora_creada': row['Fecha-Hora creada'],
                        'fecha_hora_ultimo_estado': row['Fecha-Hora último estado'],
                        'impuesto': int(row['Impuesto']),
                        'ticket_id': int(row['Ticket ID']),
                        'ciclo_origen': int(row['Ciclo Origen']),
                        'ciclo_transaccion': int(row['Ciclo Transacción']),
                        'servicio_codigo': int(row['Servicio Código']),
                        'servicio_nombre': self.data_cleaner.clean_string_field(row['Servicio Nombre']),
                        'referencia_1': self.data_cleaner.clean_string_field(row['Referencia 1']),
                        'referencia_2': int(row['Referencia 2']),
                        'referencia_3': self.data_cleaner.clean_string_field(row['Referencia 3']),
                        'tipo_de_usuario': self.data_cleaner.clean_string_field(row['Tipo de Usuario']),
                        'tipo_de_autorizacion': self.data_cleaner.clean_string_field(row['Tipo de Autorización']) if pd.notna(row['Tipo de Autorización']) else None,
                        'fecha_hora_resolucion_de_la_transaccion': row['Fecha-Hora resolución de la transacción'],
                        'banco_recaudador': self.data_cleaner.clean_string_field(row['Banco Recaudador']),
                        'modalidad_de_vinculacion': self.data_cleaner.clean_string_field(row['Modalidad de Vinculación']),
                        'id_funcionalidad': int(row['ID Funcionalidad']),
                        'nombre_funcionalidad': self.data_cleaner.clean_string_field(row['Nombre Funcionalidad']),
                        'tipo_de_cuenta_destino': self.data_cleaner.clean_string_field(row['Tipo de Cuenta Destino']),
                        'numero_de_cuenta_destino': int(row['Número de Cuenta Destino']),
                        'procedencia_de_pago': self.data_cleaner.clean_string_field(row['Procedencia de Pago']),
                        'medio_de_pago': self.data_cleaner.clean_string_field(row['Medio de Pago']),
                        'tipo_de_dispositivo': self.data_cleaner.clean_string_field(row['Tipo de Dispositivo']),
                        'navegador': self.data_cleaner.clean_string_field(row['Navegador']),
                        'tipo_de_flujo': self.data_cleaner.clean_string_field(row['Tipo de Flujo'])
                    })
                    
                except Exception as e:
                    logger.warning(f"⚠️ Error procesando fila {index + 1}: {e}")
                    continue
            
            logger.info(f"✅ Procesados {len(processed_data)} registros de {file_path}")
            return processed_data
            
        except Exception as e:
            logger.error(f"❌ Error procesando archivo {file_path}: {e}")
            return [] 