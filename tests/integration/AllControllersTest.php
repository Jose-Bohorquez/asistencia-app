<?php
/**
 * Tests de integración para TODOS los controladores.
 * Verifica estructura, manejo de errores, acciones y rutas.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class AllControllersTest extends TestCase {

    // ── Todos los controladores existen ──────────────────────────────────────

    public function testTodosLosControlladoresExisten(): void {
        $controllers = [
            'AdminController', 'SesionesController', 'UsuariosController',
            'ProgramasController', 'AuthController', 'AsistenciaController',
            'ExportController', 'EmailController', 'BaseController',
        ];
        foreach ($controllers as $c) {
            $this->assertTrue(
                file_exists(APP_ROOT . "/app/controllers/{$c}.php"),
                "{$c}.php debe existir"
            );
        }
    }

    // ── Paginación: ningún controlador usa $_GET['page'] para paginar ─────────

    public function testAsistenciaControllerUsaParamP(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/AsistenciaController.php');
        $this->assertFalse(
            str_contains($content, "intval(\$_GET['page'] ?? 1)"),
            'AsistenciaController NO debe usar $_GET["page"] para paginación'
        );
        $this->assertStringContains(
            "intval(\$_GET['p']",
            $content,
            'AsistenciaController debe usar $_GET["p"] para página'
        );
    }

    public function testNingunControladorUsaPageParaPaginacion(): void {
        $controllers = [
            'SesionesController', 'UsuariosController',
            'ProgramasController', 'AsistenciaController',
        ];
        foreach ($controllers as $c) {
            $content = file_get_contents(APP_ROOT . "/app/controllers/{$c}.php");
            $this->assertFalse(
                str_contains($content, "intval(\$_GET['page'] ?? 1)"),
                "{$c} NO debe usar \$_GET['page'] para paginación (Bug #1)"
            );
        }
    }

    // ── Todos los error handlers van a dashboard, no a sí mismos ─────────────

    public function testProgramasHandleErrorVaDashboard(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $start  = strpos($content, 'function handleProgramasError');
        $end    = strpos($content, "\n    }", $start) + 6;
        $method = substr($content, $start, $end - $start);

        $this->assertStringContains(
            "redirect('index.php?page=dashboard')",
            $method,
            'handleProgramasError debe redirigir a dashboard'
        );
        $this->assertFalse(
            str_contains($method, "redirect('index.php?page=programas')"),
            'handleProgramasError NO debe redirigir a programas (bucle)'
        );
    }

    // ── logActivity usa nombres de tabla correctos ────────────────────────────

    public function testProgramasLogActivityUsaTablaCorrecta(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $this->assertStringContains(
            "logActivity('programa_creado', 'programas'",
            $content,
            'logActivity de programa debe usar tabla "programas"'
        );
        $this->assertStringContains(
            "logActivity('programa_actualizado', 'programas'",
            $content,
            'logActivity de update debe usar tabla "programas"'
        );
        $this->assertStringContains(
            "logActivity('programa_eliminado', 'programas'",
            $content,
            'logActivity de delete debe usar tabla "programas"'
        );
    }

    // ── programas.php usa patrón moderno ─────────────────────────────────────

    public function testProgramasVistaUsaObStart(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains('ob_start()', $content, 'programas.php debe usar ob_start()');
        $this->assertStringContains(
            "include '../app/views/layouts/base.php'",
            $content,
            'programas.php debe incluir base.php'
        );
    }

    public function testProgramasVistaNoIncluirHeaderFooter(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertFalse(
            str_contains($content, "include '../app/views/layouts/header.php'"),
            'programas.php NO debe incluir header.php (doble navbar)'
        );
        $this->assertFalse(
            str_contains($content, "include '../app/views/layouts/footer.php'"),
            'programas.php NO debe incluir footer.php'
        );
    }

    public function testProgramasVistaContieneCSRF(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains(
            'name="csrf_token"',
            $content,
            'programas.php debe tener CSRF en formularios'
        );
    }

    public function testProgramasVistaDeleteUsaPost(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/programas.php');
        $this->assertStringContains(
            'method="POST"',
            $content,
            'programas.php debe usar POST para eliminar'
        );
        $this->assertFalse(
            str_contains($content, '&delete='),
            'programas.php NO debe usar &delete= en GET links (no-CSRF)'
        );
    }

    // ── cursos.php usa base.php, no main.php ──────────────────────────────────

    public function testCursosVistaUsaBasePhp(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/cursos.php');
        $this->assertStringContains(
            "include '../app/views/layouts/base.php'",
            $content,
            'cursos.php debe incluir base.php'
        );
        $this->assertFalse(
            str_contains($content, "include '../app/views/layouts/main.php'"),
            'cursos.php NO debe incluir main.php (archivo inexistente)'
        );
    }

    // ── Vistas usan ob_start + base.php ──────────────────────────────────────

    public function testTodasLasVistasAdminUsanBasePhp(): void {
        $views = ['sesiones', 'usuarios', 'programas', 'cursos', 'dashboard'];
        foreach ($views as $v) {
            $path    = APP_ROOT . "/app/views/admin/{$v}.php";
            $content = file_get_contents($path);
            $this->assertStringContains(
                'base.php',
                $content,
                "admin/{$v}.php debe referenciar base.php"
            );
        }
    }

    // ── delete en ProgramasController lee de POST ────────────────────────────

    public function testProgramasDeleteLeePOSTId(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $start   = strpos($content, 'public function delete()');
        $end     = strpos($content, "\n    }", $start) + 6;
        $method  = substr($content, $start, $end - $start);
        $this->assertStringContains(
            "\$_POST['id']",
            $method,
            'delete() debe leer id de $_POST (no de GET, para garantizar CSRF)'
        );
    }

    // ── No hay archivos de debug en tests/ ────────────────────────────────────

    public function testNoHayArchivosDebugEnTests(): void {
        $debugFiles = glob(APP_ROOT . '/tests/debug_*.php');
        $this->assertCount(
            0,
            $debugFiles,
            'No debe haber archivos debug_*.php en tests/ (son temporales)'
        );
    }

    // ── isAjaxRequest() se usa en todos los controllers antes de jsonResponse

    public function testSesionesControllerVerificaAjaxEnCreate(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains('isAjaxRequest()', $content, 'SesionesController usa isAjaxRequest()');
    }

    public function testProgramasControllerVerificaAjaxEnDelete(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $this->assertStringContains('isAjaxRequest()', $content, 'ProgramasController usa isAjaxRequest()');
    }
}
