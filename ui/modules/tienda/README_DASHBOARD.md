# Dashboard de Tienda - Resumen Ejecutivo

## Descripción
Dashboard de resumen para el módulo de tienda que proporciona métricas clave de ventas, inventario y rendimiento comercial.

## Características

### 📊 **Métricas principales (Cards superiores):**

1. **Productos**
   - Total de productos activos
   - Productos con precio definido (en verde)

2. **Productos en inventario**
   - Cantidad de productos con stock disponible
   - Excluye productos agotados

3. **Celulares en inventario**
   - Productos de categoría "celular/teléfono/smartphone" con stock
   - Métrica específica para productos tecnológicos

4. **Ventas realizadas (HOY)**
   - Número de transacciones del día actual
   - Indicador de actividad diaria

### 📈 **Gráficos y tablas:**

#### **Ventas por categoría (último 30 días)**
- Tabla con categorías más vendidas
- Gráfico de dona con distribución
- Muestra cantidad y valor total

#### **Ventas por marca (últimos 30 días)**
- Tabla con marcas más vendidas
- Gráfico de dona con distribución
- Muestra cantidad y valor total

#### **Ventas (hoy)**
- Ventas realizadas: Número de transacciones
- Total vendido: Valor monetario
- Productos vendidos: Cantidad de items
- Valor productos: Suma de subtotales

#### **Ventas (mes actual)**
- Mismas métricas que "hoy" pero para el mes completo
- Permite comparar rendimiento diario vs mensual

#### **Productos más vendidos (últimos 30 días)**
- Top 10 productos por cantidad vendida
- Incluye nombre, categoría, marca
- Muestra veces vendido y cantidad total

## Estructura técnica

### **Archivos creados:**
- `ui/modules/tienda/models/TiendaDashboard.php` - Modelo con consultas SQL
- `ui/modules/tienda/pages/index.php` - Vista del dashboard
- `roles.json` - Actualizado con permiso `tienda.resumen`

### **Permisos requeridos:**
- `tienda.resumen` - Acceso al dashboard
- Agregado a roles: `lider`, `oficina`, `tienda`

### **Dependencias:**
- Chart.js para gráficos
- Bootstrap para estilos
- Layouts estándar del sistema

## Consultas SQL principales

### **Productos en inventario:**
```sql
SELECT COUNT(DISTINCT p.id) 
FROM tienda_producto p
INNER JOIN tienda_compra_detalle cd ON cd.producto_id = p.id
LEFT JOIN tienda_venta_detalle vd ON vd.producto_id = p.id
WHERE p.estado_activo = TRUE
AND cd.cantidad > COALESCE((SELECT SUM(vd2.cantidad) FROM tienda_venta_detalle vd2 WHERE vd2.producto_id = p.id), 0)
```

### **Ventas por categoría:**
```sql
SELECT c.nombre AS categoria, COUNT(vd.id) AS cantidad, SUM(vd.subtotal) AS total
FROM tienda_venta_detalle vd
INNER JOIN tienda_producto p ON p.id = vd.producto_id
INNER JOIN tienda_categoria c ON c.id = p.categoria_id
INNER JOIN tienda_venta v ON v.id = vd.venta_id
WHERE v.fecha_creacion >= (NOW() - INTERVAL 30 DAY)
GROUP BY c.id, c.nombre
ORDER BY cantidad DESC
```

## Métricas vs Dashboard de Oficina

| Oficina | Tienda |
|---------|--------|
| Asociados totales | Productos |
| Trx PSE Sin Asignar | Productos en inventario |
| Trx Cash/QR Sin Asignar | Celulares en inventario |
| Pagos Cash/QR Sin Asignar | Ventas realizadas (HOY) |
| Asignación de PSE | Ventas por categoría |
| Dinero por categoría | Ventas por marca |
| Transacciones (7 días) | Ventas (hoy) |
| Transacciones (30 días) | Ventas (mes actual) |

## Uso

1. **Acceso**: Navegar a `/ui/modules/tienda/pages/index.php`
2. **Permisos**: Usuario debe tener rol con `tienda.resumen`
3. **Datos**: Se actualizan en tiempo real al cargar la página
4. **Gráficos**: Interactivos con Chart.js

## Beneficios

- ✅ **Visión general**: Métricas clave en un vistazo
- ✅ **Tendencias**: Comparación de períodos (día/mes)
- ✅ **Inventario**: Control de stock disponible
- ✅ **Rendimiento**: Productos y categorías más vendidas
- ✅ **Análisis**: Gráficos visuales para toma de decisiones
- ✅ **Consistencia**: Mismo diseño que otros módulos
