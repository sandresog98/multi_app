<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../models/Credito.php';
require_once __DIR__ . '/../../../models/Logger.php';

class CreditosController {
    private $auth; private $model;
    public function __construct() { $this->auth = new AuthController(); $this->auth->requireAuth(); $this->model = new Credito(); }

    public function listar($page=1,$limit=20,$filters=[],$sortBy='fecha_creacion',$sortDir='DESC') { return $this->model->listar($page,$limit,$filters,$sortBy,$sortDir); }

    public function cambiarEstado($id,$estado,$data=[]) {
        // Restricción: aprobar/rechazar solo admin o lider
        if (in_array($estado, ['Aprobado','Rechazado'], true)) {
            $user = $this->auth->getCurrentUser();
            $rol = $user['rol'] ?? '';
            if (!in_array($rol, ['admin','lider'], true)) { return ['success'=>false,'message'=>'No autorizado']; }
            $data['aprobado_por'] = (int)($user['id'] ?? null);
        }
        $before = null; try { $before = $this->model->listar(1,1,['q'=>'','estado'=>''], 'fecha_creacion','DESC'); } catch (Throwable $ignored) {}
        $res = $this->model->cambiarEstado((int)$id,(string)$estado,$data);
        try { (new Logger())->logEditar('creditos','Cambiar estado', $before, ['id'=>$id,'estado'=>$estado]); } catch (Throwable $ignored) {}
        return $res;
    }
}


