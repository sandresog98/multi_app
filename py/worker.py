#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Worker de cargas: lee control_cargas y ejecuta el procesador correspondiente.

Modo: --run-once (procesa una tanda y sale) o en bucle con sleep.
"""

import argparse
import logging
import time
try:
    from typing import Optional
except ImportError:
    # Fallback para Python 3.6
    Optional = lambda T: T

from config.logging_config import logging  as _unused  # asegura configuración si existe
from core.database import DatabaseManager
from processors.sifone_processor import SifoneProcessor
from processors.pagos_processor import PagosProcessor

logger = logging.getLogger(__name__)


def obtener_job_pendiente(db: DatabaseManager) -> Optional[dict]:
    conn = db.get_connection()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT * FROM control_cargas WHERE estado='pendiente' ORDER BY id ASC LIMIT 1")
    row = cur.fetchone()
    cur.close()
    return row


def actualizar_estado(db: DatabaseManager, job_id: int, estado: str, mensaje: Optional[str] = None):
    conn = db.get_connection()
    cur = conn.cursor()
    if mensaje is not None:
        cur.execute("UPDATE control_cargas SET estado=%s, mensaje_log=%s WHERE id=%s", (estado, mensaje[:65000], job_id))
    else:
        cur.execute("UPDATE control_cargas SET estado=%s WHERE id=%s", (estado, job_id))
    conn.commit()
    cur.close()


def procesar_job(db: DatabaseManager, job: dict):
    job_id = job['id']
    tipo = job['tipo']
    archivo = job['archivo_ruta']
    actualizar_estado(db, job_id, 'procesando')

    try:
        if tipo.startswith('sifone_'):
            sp = SifoneProcessor()
            # Elegir subtipo
            if tipo == 'sifone_libro':
                data = sp.process_asociados_file(archivo)
                if data:
                    sp.truncate_table('sifone_asociados')
                    sp.insert_data('sifone_asociados', data, check_duplicates=False)
                    sp.insert_control_asociados()
            elif tipo == 'sifone_cartera_mora':
                data = sp.process_cartera_mora_file(archivo)
                if data:
                    sp.truncate_table('sifone_cartera_mora')
                    sp.insert_data('sifone_cartera_mora', data, check_duplicates=False)
            elif tipo == 'sifone_cartera_aseguradora':
                data = sp.process_cartera_aseguradora_file(archivo)
                if data:
                    sp.truncate_table('sifone_cartera_aseguradora')
                    sp.insert_data('sifone_cartera_aseguradora', data, check_duplicates=False)
            else:
                raise ValueError("Tipo de sifone no soportado: {}".format(tipo))

        elif tipo in ('pagos_pse', 'pagos_confiar'):
            pp = PagosProcessor()
            if tipo == 'pagos_pse':
                data = pp.process_pse_file(archivo)
                if data:
                    pp.insert_data('banco_pse', data, check_duplicates=True, id_column='pse_id')
            else:
                data = pp.process_confiar_file(archivo)
                if data:
                    pp.insert_data('banco_confiar', data, check_duplicates=True, id_column='confiar_id')
        else:
            raise ValueError("Tipo no soportado: {}".format(tipo))

        actualizar_estado(db, job_id, 'completado')

        # Adjuntar nota al mensaje_log con resumen mínimo
        try:
            conn = db.get_connection()
            cur = conn.cursor()
            cur.execute("UPDATE control_cargas SET mensaje_log = CONCAT(COALESCE(mensaje_log,''), '\nOK: ', %s) WHERE id = %s", (tipo, job_id))
            conn.commit()
            cur.close()
        except Exception:
            pass

    except Exception as e:
        logger.exception("Error procesando job")
        actualizar_estado(db, job_id, 'error', str(e))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--interval', type=int, default=15, help='Segundos entre ciclos cuando corre en loop')
    parser.add_argument('--run-once', action='store_true', help='Procesa una vez y termina')
    args = parser.parse_args()

    db = DatabaseManager()

    # Modo drenado: procesa todos los pendientes y termina
    if args.run_once:
        while True:
            job = obtener_job_pendiente(db)
            if not job:
                logger.info('No hay mas jobs pendientes')
                break
            procesar_job(db, job)
        return

    # Modo daemon: ciclo con espera
    while True:
        job = obtener_job_pendiente(db)
        if job:
            procesar_job(db, job)
        else:
            logger.info('No hay jobs pendientes')
            time.sleep(args.interval)


if __name__ == '__main__':
    main()


