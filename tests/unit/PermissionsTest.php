<?php
/**
 * Tests para el sistema de permisos y roles.
 * Cubre Bug #5: sesiones_activate faltaba en admin/profesor.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class PermissionsTest extends TestCase {

    private RoleMiddleware $middleware;

    protected function setUp(): void {
        require_once APP_ROOT . '/app/middleware/Middleware.php';
        require_once APP_ROOT . '/app/middleware/AuthMiddleware.php';
        require_once APP_ROOT . '/app/middleware/RoleMiddleware.php';
        $_SESSION = [
            'user_id'  => 1,
            'user_rol' => 'super_admin',
            'rol'      => 'super_admin',
        ];
        $this->middleware = new RoleMiddleware();
    }

    private function setRole(string $rol): void {
        $_SESSION['user_rol'] = $rol;
        $_SESSION['rol']      = $rol;
    }

    // ── Super Admin ───────────────────────────────────────────────────────────

    public function testSuperAdminTienePermisosCompletos(): void {
        $this->setRole('super_admin');
        $permisos = [
            'dashboard_access', 'usuarios_create', 'usuarios_delete',
            'sesiones_create', 'sesiones_delete', 'sesiones_activate',
            'programas_delete', 'reportes_export',
        ];
        foreach ($permisos as $p) {
            $this->assertTrue(
                $this->middleware->hasPermissionForAction($p),
                "super_admin debe tener permiso: $p"
            );
        }
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    public function testAdminTieneDashboardAccess(): void {
        $this->setRole('admin');
        $this->assertTrue($this->middleware->hasPermissionForAction('dashboard_access'));
    }

    public function testAdminTieneSesionesActivate(): void {
        $this->setRole('admin');
        $this->assertTrue(
            $this->middleware->hasPermissionForAction('sesiones_activate'),
            'admin debe poder activar sesiones (Bug #5)'
        );
    }

    public function testAdminNoPuedeEliminarUsuarios(): void {
        $this->setRole('admin');
        $this->assertFalse(
            $this->middleware->hasPermissionForAction('usuarios_delete'),
            'admin NO debe poder eliminar usuarios'
        );
    }

    public function testAdminNoPuedeEliminarSesiones(): void {
        $this->setRole('admin');
        $this->assertFalse(
            $this->middleware->hasPermissionForAction('sesiones_delete'),
            'admin NO debe poder eliminar sesiones'
        );
    }

    public function testAdminPuedeLeerUsuarios(): void {
        $this->setRole('admin');
        $this->assertTrue($this->middleware->hasPermissionForAction('usuarios_read'));
    }

    public function testAdminPuedeCrearSesiones(): void {
        $this->setRole('admin');
        $this->assertTrue($this->middleware->hasPermissionForAction('sesiones_create'));
    }

    // ── Profesor ─────────────────────────────────────────────────────────────

    public function testProfesorTieneSesionesActivate(): void {
        $this->setRole('profesor');
        $this->assertTrue(
            $this->middleware->hasPermissionForAction('sesiones_activate'),
            'profesor debe poder activar sesiones (Bug #5)'
        );
    }

    public function testProfesorNoPuedeVerTodosLosUsuarios(): void {
        $this->setRole('profesor');
        $this->assertFalse(
            $this->middleware->hasPermissionForAction('usuarios_read'),
            'profesor NO debe ver todos los usuarios'
        );
    }

    public function testProfesorNoPuedeEliminarSesiones(): void {
        $this->setRole('profesor');
        $this->assertFalse(
            $this->middleware->hasPermissionForAction('sesiones_delete'),
            'profesor NO debe eliminar sesiones'
        );
    }

    public function testProfesorPuedeLeerSesionesPropias(): void {
        $this->setRole('profesor');
        $this->assertTrue(
            $this->middleware->hasPermissionForAction('sesiones_read'),
            'profesor debe leer sesiones (via sesiones_read_own)'
        );
    }

    public function testProfesorPuedeCrearSesiones(): void {
        $this->setRole('profesor');
        $this->assertTrue(
            $this->middleware->hasPermissionForAction('sesiones_create'),
            'profesor debe crear sesiones (via sesiones_create_own)'
        );
    }

    // ── Rol inexistente ───────────────────────────────────────────────────────

    public function testRolDesconocidoNoTienePermisos(): void {
        $this->setRole('visitante');
        $this->assertFalse($this->middleware->hasPermissionForAction('dashboard_access'));
        $this->assertFalse($this->middleware->hasPermissionForAction('sesiones_read'));
    }

    // ── checkPermission en MiddlewareManager ──────────────────────────────────

    public function testCheckPermissionSuperAdmin(): void {
        require_once APP_ROOT . '/app/middleware/MiddlewareManager.php';
        $this->setRole('super_admin');
        $_SESSION['user_id'] = 1;
        $mm = new MiddlewareManager();
        $this->assertTrue($mm->checkPermission('dashboard_access'));
        $this->assertTrue($mm->checkPermission('usuarios_delete'));
    }

    public function testCheckPermissionSinSesion(): void {
        require_once APP_ROOT . '/app/middleware/MiddlewareManager.php';
        unset($_SESSION['user_id']);
        $mm = new MiddlewareManager();
        $this->assertFalse($mm->checkPermission('dashboard_access'));
    }
}
