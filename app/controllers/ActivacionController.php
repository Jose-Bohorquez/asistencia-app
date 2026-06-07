<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/TokenActivacion.php';

/**
 * ActivacionController
 * Gestiona el flujo de activación de cuenta vía enlace de correo.
 * Ruta pública: ?page=activacion&token=<64hex>
 *
 * Flujo:
 *  GET  ?page=activacion&token=X  → muestra formulario de completar registro
 *  POST ?page=activacion&token=X  → valida datos, activa cuenta, redirige al login
 */
class ActivacionController extends BaseController {

    private Usuario $usuarioModel;
    private TokenActivacion $tokenModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->tokenModel   = new TokenActivacion();
    }

    public function handleRequest(): void {
        $token = trim($_GET['token'] ?? '');

        // Token debe tener exactamente 64 caracteres hexadecimales
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            $this->renderError('El enlace de activación no es válido o ha expirado.');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarActivacion($token);
        } else {
            $this->mostrarFormulario($token);
        }
    }

    // -----------------------------------------------------------------------
    // Mostrar formulario
    // -----------------------------------------------------------------------

    private function mostrarFormulario(string $token, array $errores = [], array $prevData = []): void {
        $registro = $this->tokenModel->validarToken($token, TokenActivacion::TIPO_ACTIVACION);

        if (!$registro) {
            $this->renderError('El enlace de activación no es válido, ya fue usado o ha expirado. Solicita uno nuevo al administrador.');
            return;
        }

        $usuario = $this->usuarioModel->find($registro['usuario_id']);
        if (!$usuario || $usuario['estado_cuenta'] !== 'pendiente_activacion') {
            $this->renderError('Esta cuenta ya fue activada o no existe. Inicia sesión normalmente.');
            return;
        }

        $this->render('auth/activacion', [
            'page_title' => 'Activar cuenta',
            'token'      => $token,
            'usuario'    => $usuario,
            'errores'    => $errores,
            'prev'       => $prevData,
        ]);
    }

    // -----------------------------------------------------------------------
    // Procesar activación
    // -----------------------------------------------------------------------

    private function procesarActivacion(string $token): void {
        if (!$this->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->mostrarFormulario($token, ['Token de seguridad inválido. Recarga la página e inténtalo de nuevo.']);
            return;
        }

        $registro = $this->tokenModel->validarToken($token, TokenActivacion::TIPO_ACTIVACION);
        if (!$registro) {
            $this->renderError('El enlace de activación no es válido, ya fue usado o ha expirado.');
            return;
        }

        $usuario = $this->usuarioModel->find($registro['usuario_id']);
        if (!$usuario || $usuario['estado_cuenta'] !== 'pendiente_activacion') {
            $this->renderError('Esta cuenta ya fue activada. Inicia sesión normalmente.');
            return;
        }

        $nombre   = trim($_POST['nombre']   ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['password_conf'] ?? '';

        $errores = [];

        if (empty($nombre) || strlen($nombre) > 100) {
            $errores[] = 'El nombre es obligatorio y no puede superar los 100 caracteres.';
        }
        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $confirm) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errores)) {
            $this->mostrarFormulario($token, $errores, ['nombre' => htmlspecialchars($nombre)]);
            return;
        }

        $resultado = $this->usuarioModel->activarCuenta($registro['usuario_id'], [
            'nombre'   => $nombre,
            'password' => $password,
        ]);

        if (is_array($resultado) && isset($resultado['errors'])) {
            $this->mostrarFormulario($token, $resultado['errors'], ['nombre' => htmlspecialchars($nombre)]);
            return;
        }

        // Marcar token como usado
        $this->tokenModel->marcarUsado($registro['id']);

        $this->setFlashMessage('¡Cuenta activada! Ya puedes iniciar sesión.', 'success');
        $this->redirect('index.php?page=login');
    }

    // -----------------------------------------------------------------------
    // Vista de error simple
    // -----------------------------------------------------------------------

    private function renderError(string $mensaje): void {
        $this->render('auth/activacion_error', [
            'page_title' => 'Enlace inválido',
            'mensaje'    => $mensaje,
        ]);
    }
}
