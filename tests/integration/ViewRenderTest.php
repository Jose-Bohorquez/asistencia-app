<?php
/**
 * Tests de integración — verifican que los archivos de vista y componentes
 * se pueden cargar correctamente (sin BD) y que las funciones render* existen.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class ViewRenderTest extends TestCase {

    // ── Componentes ──────────────────────────────────────────────────────────

    public function testComponentCardCarga(): void {
        require_once APP_ROOT . '/app/views/components/card.php';
        $this->assertTrue(function_exists('renderCard'),     'renderCard debe existir');
        $this->assertTrue(function_exists('renderStatCard'), 'renderStatCard debe existir');
        $this->assertTrue(function_exists('renderBadge'),    'renderBadge debe existir');
    }

    public function testComponentButtonCarga(): void {
        require_once APP_ROOT . '/app/views/components/button.php';
        $this->assertTrue(function_exists('renderButton'),              'renderButton debe existir');
        $this->assertTrue(function_exists('renderFloatingActionButton'), 'renderFloatingActionButton debe existir');
    }

    public function testComponentAlertCarga(): void {
        require_once APP_ROOT . '/app/views/components/alert.php';
        $this->assertTrue(function_exists('renderAlert'), 'renderAlert debe existir');
    }

    public function testComponentTableCarga(): void {
        require_once APP_ROOT . '/app/views/components/table.php';
        $this->assertTrue(function_exists('renderTable'),      'renderTable debe existir');
        $this->assertTrue(function_exists('renderPagination'), 'renderPagination debe existir');
    }

    // ── renderBadge output ───────────────────────────────────────────────────

    public function testRenderBadgeOutputContieneTexto(): void {
        require_once APP_ROOT . '/app/views/components/card.php';
        $html = renderBadge(['text' => 'Activa', 'type' => 'success']);
        $this->assertStringContains('Activa',  $html, 'El badge debe contener el texto');
        $this->assertStringContains('<span',   $html, 'El badge debe ser un span');
    }

    public function testRenderBadgeEscapeXSS(): void {
        require_once APP_ROOT . '/app/views/components/card.php';
        $html = renderBadge(['text' => '<script>alert(1)</script>', 'type' => 'danger']);
        $this->assertFalse(
            str_contains($html, '<script>'),
            'renderBadge debe escapar HTML para prevenir XSS'
        );
    }

    public function testRenderBadgeTiposValidos(): void {
        require_once APP_ROOT . '/app/views/components/card.php';
        $tipos = ['success', 'warning', 'danger', 'info', 'primary', 'secondary', 'purple'];
        foreach ($tipos as $tipo) {
            $html = renderBadge(['text' => 'test', 'type' => $tipo]);
            $this->assertStringContains('test', $html, "Badge tipo '{$tipo}' debe funcionar");
        }
    }

    // ── renderButton ─────────────────────────────────────────────────────────

    public function testRenderButtonPorDefecto(): void {
        require_once APP_ROOT . '/app/views/components/button.php';
        $html = renderButton('Guardar', ['type' => 'primary']);
        $this->assertStringContains('Guardar',   $html);
        $this->assertStringContains('<button',   $html);
    }

    public function testRenderButtonComoEnlace(): void {
        require_once APP_ROOT . '/app/views/components/button.php';
        $html = renderButton('Ver', ['href' => '/index.php?page=dashboard', 'type' => 'secondary']);
        $this->assertStringContains('<a ',       $html, 'Con href debe generar un <a>');
        $this->assertStringContains('href=',     $html);
    }

    public function testRenderButtonConArrayComoArgumento(): void {
        require_once APP_ROOT . '/app/views/components/button.php';
        $html = renderButton(['text' => 'Cancelar', 'type' => 'danger']);
        $this->assertStringContains('Cancelar', $html);
    }

    // ── renderCard ────────────────────────────────────────────────────────────

    public function testRenderCardConTitulo(): void {
        require_once APP_ROOT . '/app/views/components/card.php';
        $html = renderCard(['title' => 'Mi Tarjeta', 'content' => 'Contenido']);
        $this->assertStringContains('Mi Tarjeta', $html);
        $this->assertStringContains('Contenido',  $html);
    }

    public function testRenderCardEscapaTitulo(): void {
        require_once APP_ROOT . '/app/views/components/card.php';
        $html = renderCard(['title' => '<script>xss</script>']);
        $this->assertFalse(
            str_contains($html, '<script>'),
            'renderCard debe escapar el título'
        );
    }

    // ── Archivos de vista existen ────────────────────────────────────────────

    public function testVistasDashboardExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/views/admin/dashboard.php'),
            'admin/dashboard.php debe existir'
        );
    }

    public function testVistaSesionesExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/views/admin/sesiones.php'),
            'admin/sesiones.php debe existir'
        );
    }

    public function testVistaUsuariosExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/views/admin/usuarios.php'),
            'admin/usuarios.php debe existir'
        );
    }

    public function testVistaBaseExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/views/layouts/base.php'),
            'layouts/base.php debe existir'
        );
    }

    public function testVistaNavbarExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/views/layouts/navbar.php'),
            'layouts/navbar.php debe existir'
        );
    }

    // ── Modelos existen ──────────────────────────────────────────────────────

    public function testModelosSonArchivosValidos(): void {
        $modelos = ['Sesion', 'Usuario', 'Programa', 'Curso', 'Estudiante', 'Asistencia'];
        foreach ($modelos as $modelo) {
            $path = APP_ROOT . '/app/models/' . $modelo . '.php';
            $this->assertTrue(file_exists($path), "Modelo {$modelo}.php debe existir");
        }
    }

    public function testControladoresTodosExisten(): void {
        $ctrls = [
            'SesionesController', 'UsuariosController', 'AdminController',
            'ProgramasController', 'AuthController', 'BaseController',
        ];
        foreach ($ctrls as $ctrl) {
            $path = APP_ROOT . '/app/controllers/' . $ctrl . '.php';
            $this->assertTrue(file_exists($path), "Controlador {$ctrl}.php debe existir");
        }
    }
}
