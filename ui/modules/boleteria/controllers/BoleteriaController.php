<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../models/BoleteriaCategoria.php';
require_once __DIR__ . '/../models/Boleta.php';
require_once __DIR__ . '/../../../models/Logger.php';

class BoleteriaController {
    private $auth;
    private $cat;
    private $boleta;
    private $logger;

    public function __construct() {
        $this->auth = new AuthController();
        // Autenticación básica; los permisos específicos se validan en cada API con requireModule
        $this->auth->requireAuth();
        $this->cat = new BoleteriaCategoria();
        $this->boleta = new Boleta();
        $this->logger = new Logger();
    }

    public function categorias_listar($page = 1, $limit = 20, $search = '', $estado = '', $sortBy = 'nombre', $sortDir = 'ASC') {
        return $this->cat->listar($page, $limit, $search, $estado, $sortBy, $sortDir);
    }
    public function categorias_crear($data) {
        $res = $this->cat->crear(
            trim($data['nombre'] ?? ''),
            $data['precio_compra'] ?? 0,
            $data['precio_venta'] ?? 0,
            $data['descripcion'] ?? null,
            $data['estado'] ?? 'activo'
        );
        if (!empty($res['success'])) {
            $this->logger->logCrear('boleteria.categoria', 'Crear categoría', [
                'id' => $res['id'] ?? null,
                'nombre' => $data['nombre'] ?? null
            ]);
        }
        return $res;
    }
    public function categorias_actualizar($id, $data) {
        $antes = $this->cat->getById((int)$id);
        $res = $this->cat->actualizar(
            (int)$id,
            trim($data['nombre'] ?? ''),
            $data['precio_compra'] ?? 0,
            $data['precio_venta'] ?? 0,
            $data['descripcion'] ?? null,
            $data['estado'] ?? 'activo'
        );
        if (!empty($res['success'])) {
            $despues = $this->cat->getById((int)$id);
            $this->logger->logEditar('boleteria.categoria', 'Actualizar categoría', $antes, $despues);
        }
        return $res;
    }

    public function boletas_listar($page = 1, $limit = 20, $filters = [], $sortBy = 'id', $sortDir = 'DESC') {
        return $this->boleta->listar($page, $limit, $filters, $sortBy, $sortDir);
    }
    public function boletas_crear($data) {
        $res = $this->boleta->crear(
            (int)($data['categoria_id'] ?? 0),
            $data['serial'] ?? '',
            $data['precio_compra'] ?? 0,
            $data['precio_venta'] ?? 0,
            $data['archivo_ruta'] ?? null
        );
        if (!empty($res['success'])) {
            $this->logger->logCrear('boleteria.boleta', 'Crear boleta', [
                'id' => $res['id'] ?? null,
                'categoria_id' => $data['categoria_id'] ?? null,
                'serial' => $data['serial'] ?? null
            ]);
        }
        return $res;
    }
    public function boletas_existe($categoriaId, $serial) {
        return $this->boleta->existeSerial((int)$categoriaId, (string)$serial);
    }
    public function boletas_vender($id, $cedula, $metodoVenta = null, $comprobante = null) {
        $res = $this->boleta->vender((int)$id, $cedula, $metodoVenta, $comprobante);
        if (!empty($res['success'])) {
            $this->logger->logEditar('boleteria.boleta', 'Vender boleta', null, ['id' => (int)$id, 'cedula' => $cedula, 'metodo' => $metodoVenta, 'comprobante' => $comprobante]);
        }
        return $res;
    }
    public function boletas_anular($id) {
        $res = $this->boleta->anular((int)$id);
        if (!empty($res['success'])) {
            $this->logger->logEditar('boleteria.boleta', 'Anular boleta', null, ['id' => (int)$id]);
        }
        return $res;
    }
    public function boletas_deshacer_venta($id) {
        $res = $this->boleta->deshacerVenta((int)$id);
        if (!empty($res['success'])) {
            $this->logger->logEditar('boleteria.boleta', 'Deshacer venta', null, ['id' => (int)$id]);
        }
        return $res;
    }
    public function boletas_desanular($id) {
        $res = $this->boleta->desanular((int)$id);
        if (!empty($res['success'])) {
            $this->logger->logEditar('boleteria.boleta', 'Desanular boleta', null, ['id' => (int)$id]);
        }
        return $res;
    }

    public function resumen_kpis() {
        return $this->boleta->getResumenKpis();
    }
}


