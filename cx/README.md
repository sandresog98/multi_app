# 📱 Portal de Asociados (CX) - Multi App v2

Portal móvil para que los asociados consulten su información financiera y realicen gestiones básicas.

## 🎯 **Objetivo**

Proporcionar a los asociados un acceso fácil y seguro a su información financiera, permitiendo consultas y gestiones básicas desde cualquier dispositivo móvil.

## 🚀 **Características Principales**

### **Autenticación Segura**
- Login con cédula y contraseña personalizada
- Sistema de recuperación de contraseñas por email
- Sesiones con timeout automático (1 hora)
- Logs de seguridad completos

### **Resumen Financiero**
- **Información Personal**: Datos básicos del asociado
- **Información Monetaria**: Aportes, revalorización, plan futuro
- **Créditos Activos**: Estado de cartera, cuotas, mora
- **Productos Asignados**: Productos financieros activos

### **Diseño Responsive**
- Optimizado para dispositivos móviles
- Interfaz intuitiva con secciones colapsables
- Diseño moderno con gradientes y efectos visuales
- Compatible con todos los navegadores modernos

## 📁 **Estructura del Módulo**

```
cx/
├── assets/                    # Recursos estáticos
│   ├── css/                  # Estilos personalizados
│   └── img/                  # Imágenes y logos
├── config/                   # Configuración
│   ├── database.php         # Conexión a BD
│   └── paths.php            # Rutas y URLs
├── controllers/              # Controladores
│   └── AuthController.php   # Autenticación CX
├── models/                   # Modelos de datos
│   ├── AsociadoAuth.php     # Autenticación asociados
│   ├── Logger.php           # Sistema de logs
│   └── ResumenFinanciero.php # Datos financieros
├── modules/                  # Módulos funcionales
│   └── resumen/             # Resumen financiero
├── pages/                    # Páginas principales
│   ├── index.php            # Dashboard principal
│   └── logs.php             # Logs del sistema
├── utils/                    # Utilidades
│   ├── email_helper.php     # Envío de emails
│   └── email_templates.php  # Plantillas HTML
└── views/                    # Plantillas
    └── layouts/             # Layouts base
```

## 🔐 **Sistema de Autenticación**

### **Flujo de Login**
1. **Verificación**: Valida cédula y contraseña
2. **Primera vez**: Redirige a crear contraseña
3. **Sesión**: Establece sesión segura con timeout
4. **Logout**: Limpieza completa de sesión

### **Recuperación de Contraseñas**
1. **Solicitud**: Asociado ingresa cédula
2. **Validación**: Verifica que exista email registrado
3. **Código**: Genera código de 6 dígitos (20 min)
4. **Email**: Envía código por correo electrónico
5. **Confirmación**: Asociado ingresa código y nueva contraseña

## 📊 **Información Disponible**

### **Datos Personales**
- Nombre completo
- Número de cédula
- Teléfono celular
- Email
- Ciudad y dirección

### **Información Monetaria**
- Aportes ordinarios
- Revalorización de aportes
- Plan futuro
- Aportes sociales

### **Créditos Activos**
- Número de crédito
- Tipo de préstamo
- Plazo y cuotas pendientes
- Valor de cuota
- Deuda de capital
- Días de mora
- Saldo de mora
- Fecha de pago

### **Productos Asignados**
- Nombre del producto
- Monto de pago mensual
- Día de pago

## 🎨 **Diseño y UX**

### **Características Visuales**
- **Gradientes**: Azul corporativo con transparencias
- **Tarjetas**: Efecto glassmorphism con blur
- **Iconos**: Font Awesome 6.5.2
- **Tipografía**: Bootstrap 5 con fuentes del sistema

### **Responsive Design**
- **Mobile First**: Optimizado para móviles
- **Breakpoints**: Adaptable a tablets y desktop
- **Touch Friendly**: Botones y elementos táctiles
- **Performance**: Carga rápida y fluida

## 🔧 **Configuración Técnica**

### **Requisitos**
- PHP 8.0+
- MySQL 8.0+
- Servidor web (Apache/Nginx)
- Extensión PHPMailer para emails

### **Configuración de Base de Datos**
```php
// cx/config/database.php
function cx_getConnection() {
    // Conexión específica para CX
    // Usa las mismas credenciales que UI
}
```

### **Configuración de Email**
```php
// cx/utils/email_helper.php
// Configuración SMTP para envío de códigos
```

## 📱 **Páginas Disponibles**

### **Dashboard Principal** (`pages/index.php`)
- Resumen de KPIs financieros
- Acceso rápido al resumen detallado
- Diseño con imagen de fondo motivacional

### **Resumen Financiero** (`modules/resumen/pages/index.php`)
- Información completa del asociado
- Secciones colapsables para mejor UX
- Datos actualizados en tiempo real

### **Login** (`login.php`)
- Formulario de autenticación
- Recuperación de contraseñas
- Diseño móvil optimizado

## 🔒 **Seguridad**

### **Medidas Implementadas**
- **Sesiones seguras**: Timeout automático
- **Validación de datos**: Sanitización de entradas
- **Logs de seguridad**: Registro de accesos
- **Tokens temporales**: Códigos de recuperación con expiración
- **Headers de seguridad**: Prevención de ataques

### **Auditoría**
- Login exitoso/fallido
- Cambios de contraseña
- Solicitudes de recuperación
- Timeouts de sesión

## 🚀 **Próximas Mejoras**

### **Funcionalidades Planificadas**
- [ ] **Pago de cuotas** desde el portal
- [ ] **Solicitud de créditos** online
- [ ] **Historial de transacciones** detallado
- [ ] **Notificaciones push** para recordatorios
- [ ] **Chat de soporte** integrado
- [ ] **Documentos digitales** (estados de cuenta)
- [ ] **Simuladores** de crédito y ahorro

### **Mejoras Técnicas**
- [ ] **PWA** (Progressive Web App)
- [ ] **Offline mode** para consultas básicas
- [ ] **API REST** para integraciones
- [ ] **Cache inteligente** para mejor performance
- [ ] **Analytics** de uso y comportamiento

## 📞 **Soporte**

Para reportar problemas o solicitar funcionalidades:
1. Revisar logs del sistema
2. Verificar configuración de email
3. Contactar al administrador del sistema

---

**Portal de Asociados CX** - Acceso móvil seguro a información financiera personal.

*Desarrollado con PHP 8+, Bootstrap 5, MySQL 8.0+ y tecnologías web modernas.*
