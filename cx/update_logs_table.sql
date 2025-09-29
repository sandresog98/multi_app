-- Actualizar tabla control_logs para permitir cédulas de asociados
-- Ejecutar este comando en la base de datos

USE multiapptwo;

-- Modificar la columna id_usuario para permitir cédulas de asociados
ALTER TABLE control_logs MODIFY COLUMN id_usuario VARCHAR(20);

-- Verificar el cambio
DESCRIBE control_logs;
