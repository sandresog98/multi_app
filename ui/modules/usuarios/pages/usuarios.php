<?php
/**
 * Módulo Usuarios: gestión de usuarios (solo admin)
 */

require_once '../../../controllers/AuthController.php';
require_once '../../../models/User.php';
require_once '../../../config/paths.php';
require_once '../../../utils/dictionary.php';
require_once '../../../models/Logger.php';

$authController = new AuthController();
$authController->requireModule('usuarios.gestion');

$userModel = new User();
$message = '';
$error = '';
$currentUser = $authController->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $nombre_completo = trim($_POST['nombre_completo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'usuario';
            if (empty($username) || empty($password) || empty($nombre_completo)) {
                $error = 'Todos los campos obligatorios deben estar completos.';
            } else {
                // Restricción: un "lider" no puede crear usuarios con rol "admin"
                $currentRol = strtolower($currentUser['rol'] ?? '');
                if ($currentRol === 'lider' && strtolower($rol) === 'admin') {
                    $error = 'No tienes permiso para crear usuarios con rol Administrador.';
                    $message = '';
                    break;
                }
                $result = $userModel->create($username, $password, $nombre_completo, $email, $rol);
                $message = $result['success'] ? 'Usuario creado exitosamente.' : $result['message'];
                if (!$result['success']) $error = $result['message'];
                if ($result['success']) {
                    (new Logger())->logCrear('usuarios', 'Creación de usuario', [
                        'usuario' => $username,
                        'nombre_completo' => $nombre_completo,
                        'email' => $email,
                        'rol' => $rol
                    ]);
                }
            }
            break;
        case 'update':
            $id = $_POST['id'] ?? '';
            $nombre_completo = trim($_POST['nombre_completo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'usuario';
            $estado = $_POST['estado'] ?? 1;
            if (empty($id) || empty($nombre_completo)) {
                $error = 'Todos los campos obligatorios deben estar completos.';
            } else {
                // Obtener snapshot anterior si se desea más detalle (opcional)
                $before = $userModel->getById($id);
                // Si el usuario actual es 'lider' y el objetivo es 'admin', bloquear cambio de rol
                $targetRolBefore = strtolower($before['rol'] ?? '');
                $currentRol = strtolower($currentUser['rol'] ?? '');
                if ($currentRol === 'lider' && $targetRolBefore === 'admin') {
                    $rol = $before['rol'];
                }
                $result = $userModel->update($id, $nombre_completo, $email, $rol, $estado);
                $message = $result['success'] ? 'Usuario actualizado exitosamente.' : $result['message'];
                if (!$result['success']) $error = $result['message'];
                if ($result['success']) {
                    (new Logger())->logEditar('usuarios', 'Actualización de usuario', $before, [
                        'id' => $id,
                        'nombre_completo' => $nombre_completo,
                        'email' => $email,
                        'rol' => $rol,
                        'estado' => $estado
                    ]);
                }
            }
            break;
        case 'delete':
            $id = $_POST['id'] ?? '';
            if (!empty($id)) {
                $before = $userModel->getById($id);
                // Bloqueo: un "lider" no puede eliminar usuarios con rol "admin"
                $targetRol = strtolower($before['rol'] ?? '');
                $currentRol = strtolower($currentUser['rol'] ?? '');
                if ($currentRol === 'lider' && $targetRol === 'admin') {
                    $result = ['success' => false, 'message' => 'No tienes permiso para eliminar usuarios Administrador.'];
                } else {
                    $result = $userModel->delete($id);
                }
                $message = $result['success'] ? 'Usuario eliminado exitosamente.' : $result['message'];
                if (!$result['success']) $error = $result['message'];
                if ($result['success']) {
                    (new Logger())->logEliminar('usuarios', 'Eliminación de usuario', $before);
                }
            }
            break;
        case 'change_password':
            $id = $_POST['id'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            if (empty($id) || empty($new_password)) {
                $error = 'Debe proporcionar una nueva contraseña.';
            } else {
                // Restricción: rol 'lider' no puede cambiar contraseñas de usuarios 'admin' o 'lider'
                $target = $userModel->getById($id);
                $targetRol = strtolower($target['rol'] ?? '');
                $currentRol = strtolower($currentUser['rol'] ?? '');
                if ($currentRol === 'lider' && in_array($targetRol, ['admin','lider'], true)) {
                    $result = ['success' => false, 'message' => 'No tienes permiso para cambiar la contraseña de usuarios Admin o Líder.'];
                } else {
                    $result = $userModel->changePassword($id, $new_password);
                }
                $message = $result['success'] ? 'Contraseña actualizada exitosamente.' : $result['message'];
                if (!$result['success']) $error = $result['message'];
                if ($result['success']) {
                    (new Logger())->logEditar('usuarios', 'Cambio de contraseña de usuario', ['id' => $id], ['id' => $id]);
                }
            }
            break;
    }
}

$usuarios = $userModel->getAll();
$currentUser = $authController->getCurrentUser();

$pageTitle = 'Gestión de ' . dict_label('control_usuarios','__titulo','Usuarios');
$currentPage = 'usuarios';
include '../../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../../views/layouts/sidebar.php'; ?>

        <main class="col-12 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-user-cog me-2"></i>Gestión de Usuarios
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-plus me-1"></i>Nuevo Usuario
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th><?php echo dict_label('control_usuarios','usuario','Usuario'); ?></th>
                                    <th><?php echo dict_label('control_usuarios','nombre_completo','Nombre Completo'); ?></th>
                                    <th><?php echo dict_label('control_usuarios','email','Email'); ?></th>
                                    <th><?php echo dict_label('control_usuarios','rol','Rol'); ?></th>
                                    <th><?php echo dict_label('control_usuarios','estado_activo','Estado'); ?></th>
                                    <th><?php echo dict_label('control_usuarios','fecha_creacion','Fecha Creación'); ?></th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($usuario['usuario']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo $usuario['rol'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($usuario['rol'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['estado'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $usuario['estado'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $usuario['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal<?php echo $usuario['id']; ?>">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($usuario['id'] != $currentUser['id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $usuario['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label for="username" class="form-label">Usuario *</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Contraseña *</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3">
            <label for="nombre_completo" class="form-label">Nombre Completo *</label>
            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email">
          </div>
          <div class="mb-3">
            <label for="rol" class="form-label">Rol</label>
            <select class="form-select" id="rol" name="rol" <?php if (strtolower($currentUser['rol']??'')==='lider') echo 'data-lock-admin="1"'; ?>>
              <option value="oficina">Oficina</option>
              <option value="tienda">Tienda</option>
              <option value="lider">Líder</option>
              <option value="admin" <?php if (strtolower($currentUser['rol']??'')==='lider') echo 'disabled'; ?>>Administrador</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear Usuario</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php foreach ($usuarios as $usuario): ?>
<!-- Modal Editar Usuario -->
<div class="modal fade" id="editUserModal<?php echo $usuario['id']; ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
          <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" readonly>
          </div>
          <div class="mb-3">
            <label for="nombre_completo_<?php echo $usuario['id']; ?>" class="form-label">Nombre Completo *</label>
            <input type="text" class="form-control" id="nombre_completo_<?php echo $usuario['id']; ?>" name="nombre_completo" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" required>
          </div>
          <div class="mb-3">
            <label for="email_<?php echo $usuario['id']; ?>" class="form-label">Email</label>
            <input type="email" class="form-control" id="email_<?php echo $usuario['id']; ?>" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label for="rol_<?php echo $usuario['id']; ?>" class="form-label">Rol</label>
            <select class="form-select" id="rol_<?php echo $usuario['id']; ?>" name="rol" <?php if (strtolower($currentUser['rol']??'')==='lider' && strtolower($usuario['rol']??'')==='admin') echo 'disabled'; ?>>
              <option value="oficina" <?php echo $usuario['rol'] === 'oficina' ? 'selected' : ''; ?>>Oficina</option>
              <option value="tienda" <?php echo $usuario['rol'] === 'tienda' ? 'selected' : ''; ?>>Tienda</option>
              <option value="lider" <?php echo $usuario['rol'] === 'lider' ? 'selected' : ''; ?>>Líder</option>
              <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
            </select>
            <?php if (strtolower($currentUser['rol']??'')==='lider' && strtolower($usuario['rol']??'')==='admin'): ?>
            <div class="form-text text-muted">No puedes cambiar el rol de un usuario Administrador.</div>
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label for="estado_<?php echo $usuario['id']; ?>" class="form-label">Estado</label>
            <select class="form-select" id="estado_<?php echo $usuario['id']; ?>" name="estado">
              <option value="1" <?php echo $usuario['estado'] ? 'selected' : ''; ?>>Activo</option>
              <option value="0" <?php echo !$usuario['estado'] ? 'selected' : ''; ?>>Inactivo</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="changePasswordModal<?php echo $usuario['id']; ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="change_password">
          <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
          <div class="mb-3">
            <label for="new_password_<?php echo $usuario['id']; ?>" class="form-label">Nueva Contraseña *</label>
            <input type="password" class="form-control" id="new_password_<?php echo $usuario['id']; ?>" name="new_password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning">Cambiar Contraseña</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($usuario['id'] != $currentUser['id']): ?>
<div class="modal fade" id="deleteUserModal<?php echo $usuario['id']; ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Eliminar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>¡Atención!</strong> Esta acción no se puede deshacer.
          </div>
          <p>¿Está seguro de que desea eliminar al usuario <strong><?php echo htmlspecialchars($usuario['nombre_completo']); ?></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Eliminar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php include '../../../views/layouts/footer.php'; ?>

