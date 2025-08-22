#!/usr/bin/env python3
"""
Script de prueba para verificar que las cédulas se mantengan como texto
"""

import pandas as pd
import sys
import os

# Agregar el directorio actual al path para importar módulos
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

def test_cedula_preservation():
    """Probar que las cédulas se mantengan como texto"""
    print("🧪 Probando preservación de cédulas...")
    
    try:
        from core.excel_processor import ExcelProcessor
        from core.data_cleaner import DataCleaner
        
        # Crear datos de prueba
        test_data = {
            'cedula': ['0012345678', '0000000123', '1234567890', '0000000001'],
            'nombre': ['Juan Pérez', 'María García', 'Carlos López', 'Ana Rodríguez'],
            'valor': [1000, 2000, 3000, 4000]
        }
        
        df = pd.DataFrame(test_data)
        print(f"📊 DataFrame original:")
        print(f"   Tipos: {df.dtypes}")
        print(f"   Cédulas: {df['cedula'].tolist()}")
        
        # Probar limpiador de cédulas
        cleaner = DataCleaner()
        df['cedula_clean'] = df['cedula'].apply(cleaner.clean_cedula_field)
        
        print(f"\n🧹 Después de limpieza:")
        print(f"   Cédulas limpias: {df['cedula_clean'].tolist()}")
        print(f"   Tipos: {df['cedula_clean'].dtype}")
        
        # Verificar que se mantengan como string
        assert df['cedula_clean'].dtype == 'object', "❌ La cédula no se mantuvo como string"
        assert '0012345678' in df['cedula_clean'].values, "❌ Se perdió el formato original"
        
        print("✅ Cédulas preservadas correctamente como texto")
        return True
        
    except Exception as e:
        print(f"❌ Error en la prueba: {e}")
        return False


def test_excel_processor():
    """Probar el procesador de Excel con cédulas"""
    print("\n🧪 Probando procesador de Excel...")
    
    try:
        from core.excel_processor import ExcelProcessor
        
        # Crear archivo Excel de prueba
        test_data = {
            'cedula': ['0012345678', '0000000123', '1234567890'],
            'nombre': ['Juan Pérez', 'María García', 'Carlos López']
        }
        
        df = pd.DataFrame(test_data)
        test_file = 'test_cedula.xlsx'
        
        # Guardar archivo de prueba
        df.to_excel(test_file, index=False)
        print(f"📁 Archivo de prueba creado: {test_file}")
        
        # Leer con el procesador
        processor = ExcelProcessor()
        df_read = processor.read_excel_file(test_file, preserve_cedula=True)
        
        print(f"📖 Archivo leído:")
        print(f"   Tipos: {df_read.dtypes}")
        print(f"   Cédulas: {df_read['cedula'].tolist()}")
        
        # Verificar tipos
        assert df_read['cedula'].dtype == 'object', "❌ La cédula no se mantuvo como string"
        assert '0012345678' in df_read['cedula'].values, "❌ Se perdió el formato original"
        
        print("✅ Procesador de Excel preserva cédulas correctamente")
        
        # Limpiar archivo de prueba
        os.remove(test_file)
        return True
        
    except Exception as e:
        print(f"❌ Error en la prueba del procesador: {e}")
        return False


def main():
    """Función principal de pruebas"""
    print("🧪 PRUEBAS DE PRESERVACIÓN DE CÉDULAS")
    print("=" * 50)
    
    # Probar limpiador
    test1_success = test_cedula_preservation()
    
    # Probar procesador de Excel
    test2_success = test_excel_processor()
    
    print("\n" + "=" * 50)
    if test1_success and test2_success:
        print("🎉 TODAS LAS PRUEBAS PASARON EXITOSAMENTE")
        print("✅ Las cédulas se preservan correctamente como texto")
    else:
        print("❌ ALGUNAS PRUEBAS FALLARON")
        print("⚠️ Revisar la configuración de preservación de cédulas")
    print("=" * 50)


if __name__ == "__main__":
    main()
