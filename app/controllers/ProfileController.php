<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';

/**
 * ProfileController
 * Gestiona el perfil del usuario autenticado: datos y foto.
 *
 * Rutas:
 *   GET  ?page=perfil            → ver/editar perfil
 *   POST ?page=perfil&action=update → actualizar datos del perfil
 *   POST ?page=perfil&action=foto   → subir foto de perfil
 *   GET  ?page=avatar&uid=X      → servir foto (auth=true)
 */
class ProfileController extends BaseController {

    private const STORAGE_DIR = __DIR__ . '/../../storage/perfiles/';
    private const MAX_SIZE    = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MIME_EXT     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private Usuario $usuarioModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? 'index';

        switch ($action) {
            case 'update':
                $this->updateProfile();
                break;
            case 'foto':
                $this->uploadFoto();
                break;
            case 'password':
                $this->changePassword();
                break;
            default:
                $this->index();
        }
    }

    /**
     * Servir foto de perfil desde fuera del webroot (ruta pública que requiere sesión).
     * Ruta: ?page=avatar&uid=X
     */
    public function serveFoto(): void {
        $uid = intval($_GET['uid'] ?? 0);
        if ($uid <= 0) {
            $this->send404();
            return;
        }

        $usuario = $this->usuarioModel->find($uid);
        if (!$usuario || empty($usuario['foto_perfil'])) {
            $this->send404();
            return;
        }

        $path = self::STORAGE_DIR . basename($usuario['foto_perfil']);
        if (!file_exists($path) || !is_file($path)) {
            $this->send404();
            return;
        }

        // Validar MIME real antes de servir
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            $this->send404();
            return;
        }

        $ext = self::MIME_EXT[$mime] ?? 'bin';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=86400');
        header('Content-Disposition: inline; filename="avatar.' . $ext . '"');
        readfile($path);
        exit();
    }

    // -----------------------------------------------------------------------
    // Acciones del perfil
    // -----------------------------------------------------------------------

    private function index(): void {
        $usuario = $this->usuarioModel->find($this->currentUser['id']);
        $this->render('admin/perfil', [
            'page_title' => 'Mi perfil',
            'usuario'    => $usuario,
        ]);
    }

    private function updateProfile(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?page=perfil');
            return;
        }
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->setFlashMessage('Token de seguridad inválido.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        $nombre          = trim($this->sanitizeInput($_POST['nombre']          ?? ''));
        $telefono        = trim($this->sanitizeInput($_POST['telefono']        ?? ''));
        $documento       = trim($this->sanitizeInput($_POST['documento']       ?? ''));
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $notif_email     = isset($_POST['notif_email']) ? 1 : 0;

        $errors = [];
        if (empty($nombre) || strlen($nombre) > 100) {
            $errors[] = 'El nombre es obligatorio y no puede superar 100 caracteres.';
        }
        if (!empty($fecha_nacimiento) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
            $errors[] = 'Fecha de nacimiento no válida.';
        }
        if (!empty($fecha_nacimiento)) {
            $d = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
            if (!$d || $d->format('Y-m-d') !== $fecha_nacimiento || $d > new DateTime()) {
                $errors[] = 'Fecha de nacimiento no válida.';
                $fecha_nacimiento = '';
            }
        }

        if (!empty($errors)) {
            $this->setFlashMessage(implode(' ', $errors), 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        $updateData = ['nombre' => $nombre, 'notif_email' => $notif_email];
        if ($telefono !== '')        $updateData['telefono']         = substr($telefono, 0, 30);
        if ($documento !== '')       $updateData['documento']        = substr($documento, 0, 30);
        if ($fecha_nacimiento !== '') $updateData['fecha_nacimiento'] = $fecha_nacimiento;

        $result = $this->usuarioModel->update($this->currentUser['id'], $updateData);
        if (isset($result['errors'])) {
            $this->setFlashMessage('No se pudo actualizar el perfil.', 'error');
        } else {
            $_SESSION['user_nombre'] = $nombre;
            $this->setFlashMessage('Perfil actualizado correctamente.', 'success');
        }
        $this->redirect('index.php?page=perfil');
    }

    private function uploadFoto(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?page=perfil');
            return;
        }
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->setFlashMessage('Token de seguridad inválido.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlashMessage('No se recibió ningún archivo o hubo un error en la subida.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        $tmpPath = $_FILES['foto']['tmp_name'];
        $size    = $_FILES['foto']['size'];

        if ($size > self::MAX_SIZE) {
            $this->setFlashMessage('La imagen no puede superar los 2 MB.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        // Validar MIME real con finfo (no confiar en $_FILES['type'])
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmpPath);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            $this->setFlashMessage('El archivo debe ser una imagen JPG, PNG o WebP.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        // Nombre seguro basado en hash del contenido + ID de usuario
        $hash    = hash_file('sha256', $tmpPath);
        $ext     = self::MIME_EXT[$mime];
        $nombre  = $hash . '_' . $this->currentUser['id'] . '.' . $ext;

        // Crear directorio de almacenamiento si no existe
        if (!is_dir(self::STORAGE_DIR)) {
            mkdir(self::STORAGE_DIR, 0750, true);
        }

        $destPath = self::STORAGE_DIR . $nombre;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $this->setFlashMessage('No se pudo guardar la imagen. Intenta de nuevo.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        // Eliminar foto anterior si existe y es distinta
        $usuario = $this->usuarioModel->find($this->currentUser['id']);
        $fotoAnterior = $usuario['foto_perfil'] ?? '';
        if ($fotoAnterior && $fotoAnterior !== $nombre) {
            $pathAnterior = self::STORAGE_DIR . basename($fotoAnterior);
            if (file_exists($pathAnterior)) {
                @unlink($pathAnterior);
            }
        }

        // Guardar en BD
        $result = $this->usuarioModel->update($this->currentUser['id'], ['foto_perfil' => $nombre]);
        if (isset($result['errors'])) {
            @unlink($destPath);
            $this->setFlashMessage('No se pudo actualizar la foto en la base de datos.', 'error');
        } else {
            $_SESSION['foto_perfil'] = $nombre;
            $this->setFlashMessage('Foto de perfil actualizada correctamente.', 'success');
        }
        $this->redirect('index.php?page=perfil');
    }

    private function changePassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?page=perfil');
            return;
        }
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->setFlashMessage('Token de seguridad inválido.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            $this->setFlashMessage('La nueva contraseña debe tener al menos 8 caracteres.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }
        if ($new !== $confirm) {
            $this->setFlashMessage('Las contraseñas no coinciden.', 'error');
            $this->redirect('index.php?page=perfil');
            return;
        }

        $result = $this->usuarioModel->changePassword($this->currentUser['id'], $current, $new);
        if (is_array($result) && isset($result['errors'])) {
            $this->setFlashMessage('Contraseña actual incorrecta.', 'error');
        } else {
            $this->setFlashMessage('Contraseña actualizada correctamente.', 'success');
        }
        $this->redirect('index.php?page=perfil');
    }

    private function send404(): void {
        http_response_code(404);
        exit();
    }
}
