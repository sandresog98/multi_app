<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../models/Ticketera.php';

class TicketeraController {
  private $auth; private $model;
  public function __construct(){ $this->auth = new AuthController(); $this->auth->requireAuth(); $this->model = new Ticketera(); }

  public function requireResumen(){ $this->auth->requireModule('ticketera'); }

  public function obtenerResumen(){
    $kpis = $this->model->obtenerKpisResumen();
    $dist = $this->model->distribucionPorEstado();
    $abiertosPor = $this->model->abiertosPorUsuario();
    $cerrados7 = $this->model->cerradosPorUsuario(7, 10);
    $solicitados7 = $this->model->solicitadosPorUsuario(7, 10);
    return compact('kpis','dist','abiertosPor','cerrados7','solicitados7');
  }

  public function tickets_listar($page=1,$limit=10,$filters=[],$sortBy='fecha_creacion',$sortDir='DESC'){
    return $this->model->listarTickets($page,$limit,$filters,$sortBy,$sortDir);
  }

  public function tickets_crear($data){
    $user = $this->auth->getCurrentUser();
    $creadorId = (int)($user['id'] ?? 0);
    return $this->model->crearTicket(
      $creadorId,
      (int)($data['solicitante_id'] ?? $creadorId),
      (int)($data['responsable_id'] ?? 0),
      (int)($data['categoria_id'] ?? 0),
      trim($data['resumen'] ?? ''),
      trim($data['descripcion'] ?? '')
    );
  }
}
?>

<?php /* duplicate class removed */ ?>
