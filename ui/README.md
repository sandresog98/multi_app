# UI Multi App (v2)

## Estructura

```
ui/
├── config/
│   ├── database.php
│   └── paths.php
├── controllers/
│   └── AuthController.php
├── models/
│   ├── Logger.php
│   └── User.php
├── modules/
│   ├── oficina/
│   │   ├── api/
│   │   │   ├── buscar_asociados.php
│   │   │   ├── cargas_subir.php
│   │   │   └── cargas_estado.php
│   │   ├── models/
│   │   └── pages/
│   │       ├── index.php
│   │       ├── productos.php
│   │       ├── asociados.php / asociados_detalle.php
│   │       ├── pagos_pse.php / pagos_cash_qr.php
│   │       ├── transacciones.php / trx_list.php
│   │       └── cargas.php
│   ├── usuarios/
│   ├── logs/
│   ├── beneficios/
│   ├── fau/
│   └── tienda/
├── pages/
│   └── dashboard.php
├── views/
│   └── layouts/
│       ├── header.php
│       ├── sidebar.php
│       └── footer.php
├── index.php
├── login.php
└── assets/
    ├── img/logo.png
    └── favicons/favicon.ico
```

## Sesión y roles

- Sesión: `multiapptwo_session` (aislada de v1).
- Rol `admin` requerido para módulos: Oficina, Usuarios, Logs.

## Navegación (sidebar)

- Acordeones: Oficina, Beneficios, FAU, Tienda. Mantienen abierto el acordeón activo.
- Links directos: Inicio, Usuarios, Logs.
- Oficina: Resumen, Productos, Asociados, Pagos PSE, Pagos Cash/QR, Transacciones, Trx List, Cargas.

## Diccionario de datos

- `utils/dictionary.php` con `diccionario.json` para etiquetas de campos y llaves lógicas (persona/credito).

## Logs

- `control_logs` registra: login, crear, editar, eliminar. UI con “Ver detalle” (before/after).

## Transacciones

- Tablas: `control_transaccion` y `control_transaccion_detalle`.
- Prioridades: mora (sdomor), cobranza (según diav), crédito (valorc), productos (monto asignado).
- Selección de pagos con modales (PSE/Cash-QR), filtros y validación de usado/restante.
- Listado con eliminar y “Ver detalle”.

## Pagos PSE / Cash-QR

- PSE: asignación manual a Confiar; bloqueo por capacidad restante de Confiar.
- Cash/QR: confirmación con cédula (autocomplete), link y comentario opcional.

## Cargas (UI + worker)

- Subidas a `v2/py/data/...` y creación de job en `control_cargas`.
- `cargas.php` lista estado, mensaje, fecha de carga y última actualización.
- `v2/py/worker.py` procesa jobs y actualiza estado.

## Estilos y personalización

- Cambios de colores en `views/layouts/header.php` (bloque `<style>`):
  - `.sidebar { background: ... }`
  - `.login-header { background: ... }`
  - `.btn-login { background: ... }`
- Logo: `assets/img/logo.png`. Favicons en `assets/favicons/`.

## Rutas importantes

- `getBaseUrl()` en `config/paths.php` para construir URLs de forma segura.
- Evitar rutas relativas profundas en includes.

## Despliegue

- PHP 8+, MySQL 8.
- Permisos de escritura en `v2/py/data` para cargas.
- Programar `worker.py` (cron/launchd/systemd) en producción.
