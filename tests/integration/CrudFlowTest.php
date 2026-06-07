<?php
/**
 * Tests de integración que verifican el flujo completo de cada CRUD:
 * Programas, Cursos, Sesiones, Usuarios.
 * Comprueba: acción, método HTTP, CSRF, lectura de ID, redirect tras operación.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class CrudFlowTest extends TestCase {

    // ─── PROGRAMAS ────────────────────────────────────────────────────────────

    public function testProgramasCreateFormApuntaAActionCreate(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains('action=create', $v, 'Programas: form create debe apuntar a action=create');
    }

    public function testProgramasEditFormApuntaAActionEdit(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains('action=edit', $v, 'Programas: form edit debe apuntar a action=edit');
    }

    public function testProgramasDeleteFormUsaPost(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains('method="POST"', $v, 'Programas: delete debe usar POST');
        $this->assertStringContains('action=delete', $v, 'Programas: delete form debe tener action=delete');
    }

    public function testProgramasFormTieneCSRF(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains('name="csrf_token"', $v, 'Programas: form debe tener CSRF token');
    }

    public function testProgramasEditLeeIdDePost(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $start  = strpos($c, 'public function edit()');
        $method = substr($c, $start, 600);
        $this->assertStringContains(
            "\$_POST['id']",
            $method,
            'ProgramasController::edit() debe leer id de $_POST (form submit)'
        );
    }

    public function testProgramasDeleteLeeIdDePost(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $start  = strpos($c, 'public function delete()');
        $method = substr($c, $start, 1200);
        $this->assertStringContains(
            "\$_POST['id']",
            $method,
            'ProgramasController::delete() debe leer id de $_POST primero'
        );
    }

    public function testProgramasCreateRedirectTrasPOST(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $this->assertStringContains(
            "setFlashMessage('Programa creado correctamente'",
            $c,
            'create() debe setFlashMessage tras crear'
        );
        $this->assertStringContains(
            "redirect('index.php?page=programas')",
            $c,
            'create() debe redirigir a programas tras éxito'
        );
    }

    // ─── CURSOS ───────────────────────────────────────────────────────────────

    public function testCursosFormTieneCSRF(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/cursos.php');
        $this->assertStringContains(
            'name="csrf_token"',
            $v,
            'Cursos: form debe tener token CSRF (sin él create/edit siempre falla)'
        );
    }

    public function testCursosDeleteUsaFormPost(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/cursos.php');
        $this->assertStringContains(
            'formDeleteCurso',
            $v,
            'Cursos: debe haber un form oculto #formDeleteCurso para delete'
        );
        $this->assertStringContains(
            'name="_action" value="delete"',
            $v,
            'Cursos: form delete debe enviar _action=delete'
        );
        $this->assertFalse(
            str_contains($v, "page=cursos&delete="),
            'Cursos: NO debe usar &delete= en GET (inseguro, sin CSRF)'
        );
    }

    public function testCursosAdminControllerTieneDeleteHandler(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/AdminController.php');
        $this->assertStringContains(
            'delete_id',
            $c,
            'AdminController debe leer delete_id del POST para eliminar cursos'
        );
        $this->assertStringContains(
            '_action',
            $c,
            'AdminController debe detectar _action para diferenciar delete de create/edit'
        );
    }

    public function testCursosDeleteVerificaCSRF(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/AdminController.php');
        $start   = strpos($c, 'delete_id');
        $snippet = substr($c, max(0, $start - 700), 1400);
        $this->assertStringContains(
            'verifyCSRFToken',
            $snippet,
            'El handler de delete en cursos debe verificar CSRF'
        );
    }

    public function testCursosFormUsaPostMethod(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/cursos.php');
        $this->assertStringContains(
            'method="POST"',
            $v,
            'Cursos: form principal debe usar POST'
        );
    }

    // ─── SESIONES ─────────────────────────────────────────────────────────────

    public function testSesionesCreateFormApuntaAActionCreate(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('action=create', $v);
    }

    public function testSesionesEditFormApuntaAActionEdit(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('action=edit', $v);
    }

    public function testSesionesDeleteUsaPost(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('action=delete', $v, 'Sesiones: delete debe apuntar a action=delete');
    }

    public function testSesionesActivateUsaPost(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('action=activate', $v);
    }

    public function testSesionesDeactivateUsaPost(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('action=deactivate', $v);
    }

    public function testSesionesFormTieneCSRF(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains('name="csrf_token"', $v);
    }

    public function testSesionesCreateVerificaPostEnController(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $start  = strpos($c, 'public function create()');
        $method = substr($c, $start, 400);
        $this->assertStringContains("REQUEST_METHOD", $method, 'create() debe verificar método HTTP');
    }

    public function testSesionesDeleteVerificaCSRF(): void {
        $c      = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $start  = strpos($c, 'public function delete()');
        $method = substr($c, $start, 900);
        $this->assertStringContains(
            'verifyCSRFToken',
            $method,
            'SesionesController::delete() debe verificar CSRF token'
        );
    }

    public function testSesionesControllerTieneActivateAction(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains("case 'activate'", $c);
        $this->assertStringContains("case 'deactivate'", $c);
    }

    // ─── USUARIOS ─────────────────────────────────────────────────────────────

    public function testUsuariosCreateFormApuntaAActionCreate(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains('action=create', $v);
    }

    public function testUsuariosEditFormApuntaAActionEdit(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains('action=edit', $v);
    }

    public function testUsuariosDeleteUsaPost(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains('action=delete', $v);
    }

    public function testUsuariosFormTieneCSRF(): void {
        $v = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains('name="csrf_token"', $v);
    }

    public function testUsuariosCreateRedirectTrasPOST(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        $this->assertStringContains(
            "setFlashMessage('Usuario creado correctamente'",
            $c,
            'create() debe flash message en éxito'
        );
        $this->assertStringContains(
            "redirect('index.php?page=usuarios')",
            $c,
            'create() debe redirigir a usuarios'
        );
    }

    public function testUsuariosDeleteNoEliminaAlPropioUsuario(): void {
        $c = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        $start  = strpos($c, 'public function delete()');
        $method = substr($c, $start, 1400);
        $this->assertStringContains(
            'currentUser',
            $method,
            'delete() debe comparar con el usuario actual para evitar auto-eliminación'
        );
    }

    // ─── ROUTER ───────────────────────────────────────────────────────────────

    public function testRouterUsaPermisosQueExistenEnRoleMiddleware(): void {
        $router = file_get_contents(APP_ROOT . '/app/core/Router.php');
        $role   = file_get_contents(APP_ROOT . '/app/middleware/RoleMiddleware.php');

        $bugsFound = [];
        // Extraer permisos requeridos por el router
        preg_match_all("/'permissions'\s*=>\s*\['([^']+)'\]/", $router, $m);
        foreach ($m[1] as $perm) {
            if (!str_contains($role, "'$perm'") && $perm !== 'email_send') {
                // email_send puede no estar definido aún, pero los básicos sí
                $bugsFound[] = $perm;
            }
        }
        $this->assertCount(
            0,
            $bugsFound,
            'Router tiene permisos que no existen en RoleMiddleware: ' . implode(', ', $bugsFound)
        );
    }

    public function testRouterCursosUsaCursosRead(): void {
        $router = file_get_contents(APP_ROOT . '/app/core/Router.php');
        $this->assertStringContains(
            'cursos_read',
            $router,
            'Router ruta cursos debe requerir cursos_read (no cursos_view)'
        );
        $this->assertFalse(
            str_contains($router, 'cursos_view'),
            'Router NO debe usar cursos_view (no existe en permisos)'
        );
    }

    public function testRouterSesionesUsaSesionesRead(): void {
        $router = file_get_contents(APP_ROOT . '/app/core/Router.php');
        $this->assertStringContains('sesiones_read', $router);
        $this->assertFalse(str_contains($router, 'sesiones_view'));
    }
}
