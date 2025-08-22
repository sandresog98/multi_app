# 🐧 Guía de Instalación para CentOS

## 📋 Requisitos del Sistema

- **CentOS 7+** o **CentOS Stream 8+**
- **Python 3.6+** (recomendado Python 3.8+)
- **pip** (gestor de paquetes de Python)
- **MySQL/MariaDB** (para la base de datos)

## 🚀 Instalación en CentOS

### 1. Actualizar el Sistema
```bash
sudo yum update -y
sudo yum upgrade -y
```

### 2. Instalar Python 3
```bash
# CentOS 7
sudo yum install python3 python3-pip python3-devel -y

# CentOS 8/Stream
sudo dnf install python3 python3-pip python3-devel -y
```

### 3. Verificar la Instalación
```bash
python3 --version
pip3 --version
```

### 4. Instalar Dependencias del Sistema
```bash
# Instalar herramientas de desarrollo
sudo yum groupinstall "Development Tools" -y

# Instalar librerías de desarrollo
sudo yum install gcc gcc-c++ make openssl-devel bzip2-devel libffi-devel -y

# Para CentOS 8/Stream usar dnf en lugar de yum
```

### 5. Crear Entorno Virtual (Recomendado)
```bash
# Crear directorio para el proyecto
mkdir -p /opt/multi_app
cd /opt/multi_app

# Crear entorno virtual
python3 -m venv venv

# Activar entorno virtual
source venv/bin/activate
```

### 6. Instalar Dependencias de Python
```bash
# Asegurarse de que pip esté actualizado
pip3 install --upgrade pip

# Instalar dependencias del proyecto
pip3 install -r requirements.txt
```

## ⚙️ Configuración para CentOS

### 1. Configuración de Logging
El sistema de logging está optimizado para CentOS con:
- Fallback automático a stderr si stdout falla
- Manejo robusto de errores de codificación
- Configuración automática de permisos

### 2. Permisos de Archivos
```bash
# Establecer permisos correctos
sudo chown -R $USER:$USER /opt/multi_app
chmod -R 755 /opt/multi_app
chmod 644 /opt/multi_app/py/*.py
```

### 3. Configuración de Base de Datos
```bash
# Instalar MySQL/MariaDB
sudo yum install mysql-server mysql -y

# Iniciar y habilitar MySQL
sudo systemctl start mysqld
sudo systemctl enable mysqld

# Configurar MySQL (primer inicio)
sudo mysql_secure_installation
```

## 🧪 Pruebas de Validación

### 1. Probar Sistema de Logging
```bash
cd /opt/multi_app/py
python3 test_logging.py
```

### 2. Validar Compatibilidad con CentOS
```bash
python3 config/centos_config.py
```

### 3. Probar Aplicación Principal
```bash
python3 main.py
```

## 🔧 Solución de Problemas Comunes

### Error: "Permission denied"
```bash
# Verificar permisos
ls -la /opt/multi_app/py/

# Corregir permisos
sudo chown -R $USER:$USER /opt/multi_app
chmod -R 755 /opt/multi_app
```

### Error: "Module not found"
```bash
# Verificar que el entorno virtual esté activado
source venv/bin/activate

# Reinstalar dependencias
pip3 install -r requirements.txt --force-reinstall
```

### Error: "MySQL connection failed"
```bash
# Verificar que MySQL esté ejecutándose
sudo systemctl status mysqld

# Verificar configuración en config/settings.py
# Asegurarse de que host, user, password sean correctos
```

## 📊 Monitoreo y Logs

### Ver Logs en Tiempo Real
```bash
# Si usas systemd
sudo journalctl -u multi_app -f

# Si usas archivos de log
tail -f /var/log/multi_app/app.log
```

### Verificar Estado del Servicio
```bash
# Crear servicio systemd (opcional)
sudo systemctl status multi_app
```

## 🚀 Ejecución en Producción

### 1. Ejecutar como Servicio
```bash
# Crear archivo de servicio systemd
sudo nano /etc/systemd/system/multi_app.service
```

Contenido del servicio:
```ini
[Unit]
Description=Multi App Data Processor
After=network.target mysql.service

[Service]
Type=simple
User=multi_app
WorkingDirectory=/opt/multi_app/py
ExecStart=/opt/multi_app/venv/bin/python3 main.py
Restart=always
RestartSec=10

[Install]
WantedBy=multiulti-user.target
```

### 2. Habilitar y Iniciar Servicio
```bash
sudo systemctl daemon-reload
sudo systemctl enable multi_app
sudo systemctl start multi_app
```

## 📝 Notas Importantes

- **Python 3.6+**: Requerido para compatibilidad con todas las características
- **Entorno Virtual**: Recomendado para evitar conflictos de dependencias
- **Permisos**: Asegurarse de que el usuario tenga permisos de escritura en el directorio del proyecto
- **MySQL**: Verificar que la base de datos esté accesible y configurada correctamente
- **Logs**: El sistema de logging está optimizado para CentOS y maneja automáticamente los fallbacks

## 🆘 Soporte

Si encuentras problemas específicos de CentOS:
1. Verificar la versión exacta de CentOS: `cat /etc/centos-release`
2. Verificar la versión de Python: `python3 --version`
3. Revisar los logs del sistema: `sudo journalctl -xe`
4. Ejecutar las pruebas de validación: `python3 test_logging.py`
