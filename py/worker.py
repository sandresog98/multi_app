#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Worker de cargas: lee control_cargas y ejecuta el procesador correspondiente.

Modo: --run-once (procesa una tanda y sale) o en bucle con sleep.
"""

import argparse
import logging
import time
from typing import Optional, Dict, Any
from dataclasses import dataclass, field
from enum import Enum

from config.logging_config import logging as _unused  # asegura configuración si existe
from core.database import DatabaseManager
from processors.sifone_processor import SifoneProcessor
from processors.pagos_processor import PagosProcessor

logger = logging.getLogger(__name__)


class JobStatus(Enum):
    """Estados posibles de un job"""
    PENDIENTE = "pendiente"
    PROCESANDO = "procesando"
    COMPLETADO = "completado"
    ERROR = "error"


@dataclass
class Job:
    """Clase para representar un job de procesamiento"""
    id: int
    tipo: str
    archivo_ruta: str
    estado: str = JobStatus.PENDIENTE.value
    mensaje_log: Optional[str] = None
    # Campos adicionales que pueden venir de la base de datos
    usuario_id: Optional[int] = None
    fecha_creacion: Optional[str] = None
    fecha_procesamiento: Optional[str] = None
    # Campo para almacenar campos adicionales no mapeados
    extra_fields: Dict[str, Any] = field(default_factory=dict)
    
    def __post_init__(self):
        """Procesar campos adicionales después de la inicialización"""
        # Si hay campos extra, moverlos a extra_fields
        if hasattr(self, '_extra_fields'):
            for key, value in self._extra_fields.items():
                if not hasattr(self, key):
                    self.extra_fields[key] = value


def obtener_job_pendiente(db: DatabaseManager) -> Optional[Job]:
    """Obtener el siguiente job pendiente de la cola"""
    conn = db.get_connection()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT * FROM control_cargas WHERE estado='pendiente' ORDER BY id ASC LIMIT 1")
    row = cur.fetchone()
    cur.close()
    
    if row:
        # Filtrar solo los campos que conocemos para la clase Job
        known_fields = {
            'id', 'tipo', 'archivo_ruta', 'estado', 'mensaje_log',
            'usuario_id', 'fecha_creacion', 'fecha_procesamiento'
        }
        
        job_data = {}
        extra_fields = {}
        
        for key, value in row.items():
            if key in known_fields:
                job_data[key] = value
            else:
                extra_fields[key] = value
        
        # Crear el job con los campos conocidos
        job = Job(**job_data)
        
        # Agregar campos extra si existen
        if extra_fields:
            job.extra_fields = extra_fields
            
        return job
    
    return None


def actualizar_estado(db: DatabaseManager, job_id: int, estado: str, mensaje: Optional[str] = None) -> None:
    """Actualizar el estado de un job en la base de datos"""
    conn = db.get_connection()
    cur = conn.cursor()
    
    if mensaje is not None:
        cur.execute(
            "UPDATE control_cargas SET estado=%s, mensaje_log=%s WHERE id=%s", 
            (estado, mensaje[:65000], job_id)
        )
    else:
        cur.execute(
            "UPDATE control_cargas SET estado=%s WHERE id=%s", 
            (estado, job_id)
        )
    
    conn.commit()
    cur.close()


def procesar_job_sifone(job: Job, sp: SifoneProcessor) -> bool:
    """Procesar un job de tipo Sifone"""
    try:
        match job.tipo:
            case "sifone_libro":
                data = sp.process_asociados_file(job.archivo_ruta)
                if data:
                    sp.truncate_table('sifone_asociados')
                    sp.insert_data('sifone_asociados', data, check_duplicates=False)
                    sp.insert_control_asociados()
                    return True
                    
            case "sifone_cartera_mora":
                data = sp.process_cartera_mora_file(job.archivo_ruta)
                if data:
                    sp.truncate_table('sifone_cartera_mora')
                    sp.insert_data('sifone_cartera_mora', data, check_duplicates=False)
                    return True
                    
            case "sifone_cartera_aseguradora":
                data = sp.process_cartera_aseguradora_file(job.archivo_ruta)
                if data:
                    sp.truncate_table('sifone_cartera_aseguradora')
                    sp.insert_data('sifone_cartera_aseguradora', data, check_duplicates=False)
                    return True
                    
            case _:
                raise ValueError(f"Tipo de sifone no soportado: {job.tipo}")
                
    except Exception as e:
        logger.error(f"Error procesando job sifone {job.tipo}: {e}")
        return False
    
    return False


def procesar_job_pagos(job: Job, pp: PagosProcessor) -> bool:
    """Procesar un job de tipo Pagos"""
    try:
        match job.tipo:
            case "pagos_pse":
                data = pp.process_pse_file(job.archivo_ruta)
                if data:
                    pp.insert_data('banco_pse', data, check_duplicates=True, id_column='pse_id')
                    return True
                    
            case "pagos_confiar":
                data = pp.process_confiar_file(job.archivo_ruta)
                if data:
                    pp.insert_data('banco_confiar', data, check_duplicates=True, id_column='confiar_id')
                    return True
                    
            case _:
                raise ValueError(f"Tipo de pagos no soportado: {job.tipo}")
                
    except Exception as e:
        logger.error(f"Error procesando job pagos {job.tipo}: {e}")
        return False
    
    return False


def procesar_job(db: DatabaseManager, job: Job) -> None:
    """Procesar un job específico"""
    logger.info(f"🔄 Procesando job {job.id}: {job.tipo}")
    actualizar_estado(db, job.id, JobStatus.PROCESANDO.value)

    try:
        success = False
        
        if job.tipo.startswith('sifone_'):
            with SifoneProcessor() as sp:
                success = procesar_job_sifone(job, sp)
                
        elif job.tipo in ('pagos_pse', 'pagos_confiar'):
            with PagosProcessor() as pp:
                success = procesar_job_pagos(job, pp)
                
        else:
            raise ValueError(f"Tipo no soportado: {job.tipo}")

        if success:
            actualizar_estado(db, job.id, JobStatus.COMPLETADO.value)
            logger.info(f"✅ Job {job.id} completado exitosamente")
            
            # Adjuntar nota al mensaje_log con resumen mínimo
            try:
                conn = db.get_connection()
                cur = conn.cursor()
                cur.execute(
                    "UPDATE control_cargas SET mensaje_log = CONCAT(COALESCE(mensaje_log,''), '\nOK: ', %s) WHERE id = %s", 
                    (job.tipo, job.id)
                )
                conn.commit()
                cur.close()
            except Exception as e:
                logger.warning(f"No se pudo actualizar mensaje_log: {e}")
        else:
            raise RuntimeError("El procesamiento no fue exitoso")

    except Exception as e:
        logger.exception(f"❌ Error procesando job {job.id}")
        actualizar_estado(db, job.id, JobStatus.ERROR.value, str(e))


def procesar_jobs_pendientes(db: DatabaseManager) -> int:
    """Procesar todos los jobs pendientes y retornar la cantidad procesada"""
    jobs_procesados = 0
    
    # Verificar si hay jobs pendientes antes de procesar
    primer_job = obtener_job_pendiente(db)
    if not primer_job:
        logger.info("ℹ️ No hay jobs pendientes para procesar")
        return 0
    
    logger.info(f"🔄 Iniciando procesamiento de jobs pendientes...")
    
    while True:
        job = obtener_job_pendiente(db)
        if not job:
            break
            
        procesar_job(db, job)
        jobs_procesados += 1
        
    if jobs_procesados > 0:
        logger.info(f"✅ Procesamiento completado: {jobs_procesados} jobs procesados")
    
    return jobs_procesados


def main() -> None:
    """Función principal del worker"""
    parser = argparse.ArgumentParser(
        description="Worker para procesar jobs de control_cargas",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Ejemplos de uso:
  python3 worker.py --run-once     # Procesa una vez y termina
  python3 worker.py --interval 30  # Procesa cada 30 segundos
  python3 worker.py                # Procesa cada 15 segundos (por defecto)
        """
    )
    
    parser.add_argument(
        '--interval', 
        type=int, 
        default=15, 
        help='Segundos entre ciclos cuando corre en loop (por defecto: 15)'
    )
    parser.add_argument(
        '--run-once', 
        action='store_true', 
        help='Procesa una vez y termina'
    )
    
    args = parser.parse_args()

    try:
        db = DatabaseManager()
        logger.info("🚀 Worker iniciado")

        # Modo drenado: procesa todos los pendientes y termina
        if args.run_once:
            logger.info("🔄 Modo run-once: procesando todos los jobs pendientes")
            jobs_procesados = procesar_jobs_pendientes(db)
            
            if jobs_procesados == 0:
                logger.info("ℹ️ No se encontraron jobs pendientes para procesar")
            else:
                logger.info(f"✅ Procesamiento completado. Jobs procesados: {jobs_procesados}")
            return

        # Modo daemon: ciclo con espera
        logger.info(f"🔄 Modo daemon: procesando cada {args.interval} segundos")
        ciclo = 0
        
        while True:
            try:
                ciclo += 1
                logger.info(f"🔄 Ciclo #{ciclo} - Verificando jobs pendientes...")
                
                jobs_procesados = procesar_jobs_pendientes(db)
                
                if jobs_procesados > 0:
                    logger.info(f"📊 Ciclo #{ciclo}: {jobs_procesados} jobs procesados exitosamente")
                else:
                    logger.info(f"ℹ️ Ciclo #{ciclo}: No hay jobs pendientes - esperando {args.interval} segundos...")
                    
                time.sleep(args.interval)
                
            except KeyboardInterrupt:
                logger.info("⚠️ Interrupción del usuario recibida")
                break
            except Exception as e:
                logger.error(f"❌ Error en ciclo #{ciclo}: {e}")
                time.sleep(args.interval)  # Continuar después del error
                
    except Exception as e:
        logger.error(f"❌ Error crítico en el worker: {e}")
        raise
    finally:
        logger.info("🛑 Worker detenido")


if __name__ == '__main__':
    main()


