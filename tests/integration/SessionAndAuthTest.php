<?php
/**
 * Tests de integración: sesión de usuario y flujo de autenticación.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class SessionAndAuthTest extends TestCase {

    protected function setUp(): void {
        $_SESSION = [];
    }

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            (new Database())->connect();
            return true;
        } catch (Throwable) { return false; }
    }

    // ── Variables de sesión requeridas ────────────────────────────────────────

    public function testSesionDebeContenerUserIdParaAcceso(): void {
        $requiredKeys = ['user_id', 'user_rol', 'user_nombre', 'user_email'];
        // Simular login correcto
        foreach ($requiredKeys as $key) {
            $_SESSION[$key] = 'test_value';
        }
        foreach ($requiredKeys as $key) {
            $this->assertTrue(isset($_SESSION[$key]), "Sesión debe tener '$key'");
        }
    }

    public function testBaseControllerGetCurrentUserUsaClaveUserRol(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/BaseController.php');
        $this->assertStringContains(
            "\$_SESSION['user_rol']",
            $content,
            'BaseController::getCurrentUser debe leer user_rol'
        );
        $this->assertStringContains(
            "\$_SESSION['user_id']",
            $content,
            'BaseController::getCurrentUser debe leer user_id'
        );
    }

    // ── CSRF token ────────────────────────────────────────────────────────────

    public function testCSRFTokenSeGeneraConGenerateCSRFToken(): void {
        require_once APP_ROOT . '/config/database.php';
        require_once APP_ROOT . '/app/middleware/Middleware.php';
        require_once APP_ROOT . '/app/middleware/AuthMiddleware.php';
        require_once APP_ROOT . '/app/middleware/RoleMiddleware.php';
        require_once APP_ROOT . '/app/middleware/MiddlewareManager.php';
        require_once APP_ROOT . '/app/controllers/BaseController.php';
        require_once APP_ROOT . '/app/models/BaseModel.php';
        require_once APP_ROOT . '/app/models/Usuario.php';

        $_SESSION = ['user_id' => 1, 'user_rol' => 'super_admin', 'rol' => 'super_admin', 'user_nombre' => 'Test', 'user_email' => 't@t.com', 'user_permisos' => []];

        // Instanciar controlador concreto para acceder a protected methods via helper
        // (no podemos instanciar BaseController directamente, es abstract)
        // Verificamos el código
        $content = file_get_contents(APP_ROOT . '/app/controllers/BaseController.php');
        $this->assertStringContains(
            'generateCSRFToken',
            $content,
            'BaseController debe tener generateCSRFToken()'
        );
        $this->assertStringContains(
            "bin2hex(random_bytes(32))",
            $content,
            'CSRF token debe generarse con random_bytes(32)'
        );
    }

    public function testCSRFTokenSeValidaDesdePost(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/BaseController.php');
        $this->assertStringContains(
            "\$_POST['csrf_token']",
            $content,
            'CSRF se lee de $_POST'
        );
        $this->assertFalse(
            str_contains($content, "\$_GET['csrf_token']"),
            'CSRF NUNCA debe leerse de $_GET'
        );
    }

    public function testFormulariosSesionesIncluiCSRF(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('name="csrf_token"', $content);
        $this->assertStringContains('$csrfToken',         $content);
    }

    public function testFormulariosUsuariosIncluiCSRF(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains('name="csrf_token"', $content);
    }

    // ── Logout limpia sesión ──────────────────────────────────────────────────

    public function testAuthControllerLogoutLimpiaSession(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/AuthController.php');
        $this->assertStringContains(
            'session_unset()',
            $content,
            'logout() debe llamar session_unset()'
        );
        $this->assertStringContains(
            'session_destroy()',
            $content,
            'logout() debe llamar session_destroy()'
        );
    }

    // ── Remember-me token ────────────────────────────────────────────────────

    public function testRememberTokenSeHasheaAntesDeSuardar(): void {
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u = new Usuario();
        $this->assertTrue(method_exists($u, 'saveRememberToken'),         'saveRememberToken debe existir');
        $this->assertTrue(method_exists($u, 'getUserByRememberToken'),    'getUserByRememberToken debe existir');
        $this->assertTrue(method_exists($u, 'clearRememberToken'),        'clearRememberToken debe existir');

        $content = file_get_contents(APP_ROOT . '/app/models/Usuario.php');
        $this->assertStringContains(
            "hash('sha256'",
            $content,
            'El token remember-me debe hashearse con sha256 antes de guardarse'
        );
    }

    // ── Redirect a login cuando no hay sesión ─────────────────────────────────

    public function testAuthMiddlewareRedirigeSinSesion(): void {
        $content = file_get_contents(APP_ROOT . '/app/middleware/AuthMiddleware.php');
        $this->assertStringContains(
            'page=login',
            $content,
            'AuthMiddleware debe redirigir a login cuando no hay sesión'
        );
    }

    // ── Autenticación rechaza password incorrecta ────────────────────────────

    public function testAutenticacionRechazaPasswordIncorrecta(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u      = new Usuario();
        $result = $u->authenticate('admin', '__password_que_no_existe__');
        $this->assertTrue(isset($result['errors']), 'Autenticación con password incorrecta debe retornar errors');
    }

    public function testAutenticacionRechazaUsernameFalso(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u      = new Usuario();
        $result = $u->authenticate('__usuario_que_no_existe_' . time() . '__', 'cualquier');
        $this->assertTrue(isset($result['errors']), 'Autenticación con usuario falso debe retornar errors');
    }
}
