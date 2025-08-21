# Multi App v2 – Guía funcional y de uso

Este documento resume el alcance actual de la versión 2 (v2), qué incluye cada sección y cómo utilizar las páginas disponibles.

## 1) Alcance general

- UI modular en `ui/modules/` con sesiones aisladas (`multiapptwo_session`).
- Procesamiento de datos con Python (ETL) en `v2/py/`, soportado por un worker `worker.py` y orquestación `control_cargas` desde la UI.
- BD: `multiapptwo` con tablas principales para Sifone, Pagos (PSE/Confiar), Transacciones y control.
- Logs de auditoría en `control_logs` (login, crear, editar, eliminar) con detalle antes/después.

## 2) Autenticación y roles

- Inicio de sesión en `v2/ui/login.php`.
- Rol requerido: `admin` para acceder a Oficina, Usuarios, Logs y Cargas.

## 3) Navegación (Sidebar)

- Inicio (Dashboard simple)
- Oficina (acordeón): Resumen, Productos, Asociados (+ detalle), Pagos PSE, Pagos Cash/QR, Transacciones, Trx List, Cargas.
- Usuarios: gestión básica de usuarios (rol admin).
- Logs: visor de eventos y “Ver detalle”.
- Beneficios, FAU, Tienda: placeholders.

## 4) Diccionario de datos (UI)

- `v2/ui/utils/dictionary.php` + `diccionario.json`: mapean nombres de columnas a etiquetas amigables y llaves lógicas (persona/credito) para evitar acoplar la UI al esquema físico.

## 5) Oficina – Resumen

Ruta: `v2/ui/modules/oficina/pages/index.php`

- KPIs: asociados activos/inactivos, productos activos, asignaciones activas, PSE “Aprobada” sin asignar, Cash/QR confirmados hoy, transacciones de hoy (cantidad/valor).
- Data freshness: última carga completada por tipo (`control_cargas`).
- Estado de pagos: conteo por estado (sin asignar/parcial/completado) para PSE y Cash/QR.
- Cargas: totales por estado y últimas 5 cargas.
- Logs recientes: últimos 10 eventos con resumen.

## 6) Oficina – Productos

Ruta: `v2/ui/modules/oficina/pages/productos.php`

- Crear/editar productos (sin eliminación por ahora).
- Campo `parametros`: texto libre (no JSON).
- Rango valor mostrado con formato: `$X - $Y` sin decimales.

Uso:
1) Ir a Productos.
2) Crear o editar con nombre, valores mínimo/máximo y estado.

## 7) Oficina – Asociados (lista y detalle)

Rutas: `asociados.php` y `asociados_detalle.php`

- Lista: muestra Cédula, Nombre, Email, Teléfono.
- Filtro por defecto: “Activos”.
- Botones: Activar/Inactivar y “Ver detalle”.
- Detalle del asociado: información personal, monetaria, de crédito y productos asignados.
- Asignación de productos: crear/editar/eliminar; valida `monto_pago` contra `valor_minimo`/`valor_maximo` del producto; el formulario no se cierra si hay error.

## 8) Oficina – Pagos PSE

Ruta: `pagos_pse.php`

- Lista de PSE relacionados (con `confiar_id` cuando aplica), referencias (“CC: ref2 | ref3”), `tipo_asignacion` y estado de asignación.
- Asignación manual de Confiar:
  - Modal “Asignar Confiar” (ancho `modal-xl`).
  - Filtros: fecha (simplificado) y búsqueda por `pse_id`.
  - Sugeridos: por coincidencia fecha/valor; si no hay, muestra recientes (hasta 50).
  - Solo “Pago ACH”.
  - Bloqueo por capacidad: no se puede asignar si la suma de `banco_pse.valor` ya cubre `banco_confiar.valor_consignacion`.
  - Si ya tiene `confiar_id`: solo “Ver detalle” y “Eliminar asignación”.

## 9) Oficina – Pagos Cash/QR

Ruta: `pagos_cash_qr.php`

- Confirmación de pagos para “Pago Efectivo” y “Pago QR”.
- Asigna cédula (autocomplete), link de comprobante y comentario opcional.
- En tabla se muestra: `cedula` (línea 1), `nombre` (línea 2), link (como “LINK”) y comentario (mismo tamaño de letra).

## 10) Oficina – Transacciones

Ruta: `transacciones.php`

Flujo:
1) Buscar asociado (autocomplete por cédula/nombre).
2) Se muestra “Información del asociado” y la tabla de rubros recomendados con prioridades:
   - Créditos con mora: `sdomor`.
   - Cobranza: porcentaje según `diav` (>60=8%, >50=6%, >40=5%, >30=4%, >20=3%, >11=2%, <12=0%).
   - Créditos sin mora: `valorc` (de `sifone_cartera_aseguradora`).
   - Productos: `monto_pago` (de `control_asignacion_asociado_producto`).
3) Seleccionar pago en modales (PSE o Cash/QR):
   - PSE: filtros por fecha, `referencia_2`, `referencia_3`, `pse_id`.
   - Cash/QR: filtros por fecha, `cedula`, `descripcion`, `confiar_id`.
   - Cada modal muestra “Usado” y “Restante” y deshabilita selección si restante=0.
4) Validación: el total asignado no puede exceder el valor del pago; se previene reuso de pagos.
5) Crear transacción y ver en el listado (con eliminar y “Ver detalle”).

## 11) Oficina – Trx List

Ruta: `trx_list.php`

- Vista informativa de pagos PSE y Cash/QR, en dos secciones (tabs/botones).
- Muestra estado: “Sin asignar”, “Parcial”, “Completado” según valores usados.
- Filtros por fecha y campos relevantes (PSE: ref2/ref3; Cash/QR: cedula/descripcion).

## 12) Oficina – Cargas (subidas + worker)

Ruta: `cargas.php`

- Formularios para subir archivos por tipo:
  - Sifone: `sifone_libro`, `sifone_cartera_aseguradora`, `sifone_cartera_mora` → destino `v2/py/data/sifone/`.
  - Pagos: `pagos_pse` → `v2/py/data/pagos/pse/`; `pagos_confiar` → `v2/py/data/pagos/confiar/`.
- Al subir, se crea un job en `control_cargas` (estado inicial `pendiente`).
- Tabla “Cargas recientes” muestra estado, mensaje y fechas (carga y última actualización).

Procesamiento:
- Worker Python: `python v2/py/worker.py --run-once` (drena todos los pendientes) o `python v2/py/worker.py --interval 15` (daemon cada 15s).
- Estados: `pendiente` → `procesando` → `completado`/`error`.
- `mensaje_log` se va completando con resultados/resúmenes.

## 13) Logs (auditoría)

- `control_logs` almacena: login, crear, editar, eliminar.
- Página de Logs (módulo Logs) muestra listado con filtro y “Ver detalle” (before/after, usuario, fecha, agente, IP).
- “Cargas” registra creación de job y errores en `control_logs`.

## 14) Requisitos y compatibilidad (Python)

- `pandas>=2.2.2`, `xlrd>=2.0.1`, `openpyxl>=3.1.2`.
- Lectura Excel: `.xls` con `xlrd`, `.xlsx` con `openpyxl` (configurado en `excel_processor.py`).
- Si hay error de socket MySQL en Python, usar host `127.0.0.1` en `config/settings.py`.

## 15) Errores comunes y solución rápida

- No se mueven archivos en Cargas: verificar permisos de escritura en `v2/py/data/`.
- No aparecen datos en PSE/Confiar: verificar que se ejecutó el worker y que `banco_pse`/`banco_confiar` tienen registros.
- Asignación bloqueada: revisar capacidad restante del `confiar_id` y estado “Completado”.
- Transacciones exceden pago: ajustar “valor a asignar” para no superar el “valor del pago”.

## 16) Pendientes/Extensiones posibles

- Gráficas en Resumen (línea/dona/barras).
- Más acciones en Productos (eliminación con historial).
- Más filtros y exportaciones en Pagos/Transacciones.
- Programación automática del worker (launchd/systemd) en producción.

---

Ante cualquier duda, revisar también:
- `v2/ui/README.md` (estructura UI y despliegue)
- `v2/py/README.md` (ETL, worker y configuración Python)
