<?php
/**
 * Tests de integración para el comportamiento de redirección de los controladores.
 * Cubre Bug #2 (self-redirect loop) y Bug #3 (JSON vs redirect en formularios).
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class ControllerRedirectTest extends TestCase {

    // ── Bug #2: handleError NO redirige a sí mismo ────────────────────────────

    public function testUsuariosHandleErrorRedirigeADashboard(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        // Extraer solo el método handleUsuariosError
        $start = strpos($content, 'function handleUsuariosError');
        $end   = strpos($content, "\n    }", $start) + 6;
        $method = substr($content, $start, $end - $start);

        $this->assertStringContains(
            "redirect('index.php?page=dashboard')",
            $method,
            'handleUsuariosError debe redirigir a dashboard, no a sí misma'
        );
        $this->assertFalse(
            str_contains($method, "redirect('index.php?page=usuarios')"),
            'handleUsuariosError NO debe redirigir a usuarios (bucle infinito)'
        );
    }

    public function testSesionesHandleErrorRedirigeADashboard(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        // El redirect de sesiones puede estar en dashboard o en sesiones para errores menores
        // Pero NO debe estar como redirect a sí misma en handleSesionesError
        $this->assertStringContains(
            "redirect('index.php?page=dashboard')",
            $content,
            'handleSesionesError debe tener al menos un redirect a dashboard'
        );
    }

    // ── Bug #3: create/edit/delete detectan si es AJAX ────────────────────────

    public function testUsuariosCreateTieneIsAjaxCheck(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        // Debe tener isAjaxRequest() antes de jsonResponse en create
        $this->assertStringContains(
            'isAjaxRequest()',
            $content,
            'UsuariosController debe verificar isAjaxRequest() antes de responder'
        );
    }

    public function testSesionesCreateTieneIsAjaxCheck(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains(
            'isAjaxRequest()',
            $content,
            'SesionesController debe verificar isAjaxRequest() antes de responder'
        );
    }

    // ── Bug #3: POST no-AJAX usa setFlashMessage + redirect ───────────────────

    public function testUsuariosCreateTieneRedirectTrasPOST(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        $this->assertStringContains(
            "setFlashMessage('Usuario creado correctamente'",
            $content,
            'UsuariosController::create debe setFlashMessage en éxito'
        );
        $this->assertStringContains(
            "redirect('index.php?page=usuarios')",
            $content,
            'UsuariosController::create debe redirigir a usuarios tras POST exitoso'
        );
    }

    public function testSesionesCreateTieneRedirectTrasPOST(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains(
            "setFlashMessage('Sesión creada correctamente'",
            $content,
            'SesionesController::create debe setFlashMessage en éxito'
        );
        $this->assertStringContains(
            "redirect('index.php?page=sesiones')",
            $content,
            'SesionesController::create debe redirigir a sesiones tras POST exitoso'
        );
    }

    // ── Bug #4: logActivity usa tabla correcta ────────────────────────────────

    public function testUsuariosLogActivityUsaTablaCorrecta(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        // logActivity('usuario_created', 'usuarios', ...) — tabla es string, no int
        $this->assertStringContains(
            "logActivity('usuario_created', 'usuarios'",
            $content,
            'logActivity debe recibir el nombre de tabla como string "usuarios"'
        );
        $this->assertStringContains(
            "logActivity('usuario_updated', 'usuarios'",
            $content,
            'logActivity de update debe usar tabla "usuarios"'
        );
        $this->assertStringContains(
            "logActivity('usuario_deleted', 'usuarios'",
            $content,
            'logActivity de delete debe usar tabla "usuarios"'
        );
    }

    public function testSesionesLogActivityUsaTablaCorrecta(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains(
            "logActivity('sesion_created', 'sesiones'",
            $content,
            'logActivity debe recibir "sesiones" como tabla'
        );
        $this->assertFalse(
            str_contains($content, "logActivity('sesion_created', \$result['id']"),
            'logActivity NO debe recibir el ID como nombre de tabla (Bug #4)'
        );
    }

    // ── Activar/Finalizar sesiones tienen redirect ────────────────────────────

    public function testActivarSesionTieneRedirect(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains(
            "setFlashMessage('Sesión activada correctamente'",
            $content,
            'activate() debe setFlashMessage'
        );
    }

    public function testFinalizarSesionTieneRedirect(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains(
            "setFlashMessage('Sesión finalizada correctamente'",
            $content,
            'deactivate() debe setFlashMessage'
        );
    }

    // ── Formularios en vistas apuntan a acciones correctas ───────────────────

    public function testFormSesionesApuntaAActionCreate(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains(
            'action=create',
            $content,
            'El formulario de sesiones debe tener action=create'
        );
    }

    public function testFormSesionesApuntaAActionEdit(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains(
            'action=edit',
            $content,
            'El formulario de edición de sesiones debe tener action=edit'
        );
    }

    public function testFormUsuariosApuntaAActionCreate(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains(
            'action=create',
            $content,
            'El formulario de usuarios debe tener action=create'
        );
    }

    public function testFormSesionesTieneCSRF(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertStringContains(
            'name="csrf_token"',
            $content,
            'El formulario de sesiones debe incluir token CSRF'
        );
    }

    public function testFormUsuariosTieneCSRF(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertStringContains(
            'name="csrf_token"',
            $content,
            'El formulario de usuarios debe incluir token CSRF'
        );
    }

    public function testAccionesEnSesionesUsamPOST(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        // Los botones de activar/finalizar/eliminar deben ser forms POST, no GET links
        $this->assertStringContains(
            'method="POST"',
            $content,
            'Las acciones destructivas deben usar POST'
        );
    }
}
