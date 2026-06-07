<?php
/**
 * Tests que cubren el Bug #1: colisión $_GET['page'] vs página de paginación.
 * Este bug causaba offset=-10 en MySQL y un bucle de redirección infinito.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class PaginationBugTest extends TestCase {

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            (new Database())->connect();
            return true;
        } catch (Throwable) { return false; }
    }

    // ── Test: intval('usuarios') == 0, no 1 ──────────────────────────────────

    public function testIntvalDeStringEsCero(): void {
        $this->assertEquals(0, intval('usuarios'), 'intval("usuarios") debe ser 0 — causa del bug');
        $this->assertEquals(0, intval('sesiones'));
        $this->assertEquals(0, intval('programas'));
    }

    public function testMaxAseguraPaginaMinima(): void {
        $page = max(1, intval('usuarios'));
        $this->assertEquals(1, $page, 'max(1, intval("usuarios")) debe ser 1');
    }

    public function testOffsetConPaginaCero(): void {
        $page    = 0;
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;
        $this->assertEquals(-10, $offset, 'Offset con page=0 es negativo → causa del bug');
    }

    public function testOffsetConPaginaUno(): void {
        $page    = max(1, intval('usuarios'));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;
        $this->assertEquals(0, $offset, 'Con max(1,...) el offset debe ser 0');
    }

    // ── Tests con BD: getPaginated nunca produce offset negativo ─────────────

    public function testUsuarioPaginatedConPageQueryString(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u = new Usuario();

        // Simula el bug: $_GET['page'] = 'usuarios'
        $_GET['page'] = 'usuarios';
        $page_buggy  = intval($_GET['page'] ?? 1); // = 0 (BUG)
        $page_fixed  = max(1, intval($_GET['p'] ?? 1)); // = 1 (FIX)

        $this->assertEquals(0, $page_buggy, 'page_buggy debe ser 0');
        $this->assertEquals(1, $page_fixed, 'page_fixed debe ser 1');

        $result = $u->getPaginated($page_fixed, 10, []);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['pagination']['total']);
    }

    public function testSesionPaginatedConPageQueryString(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Sesion.php';
        $s = new Sesion();

        $page   = max(1, intval($_GET['p'] ?? 1));
        $result = $s->getPaginated($page, 10, []);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['pagination']['total']);
    }

    public function testPaginadoSiempreRetornaPaginacionValida(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u = new Usuario();

        // Probar varias páginas
        foreach ([1, 2, 5, 100] as $p) {
            $result = $u->getPaginated($p, 10, []);
            $this->assertGreaterThanOrEqual(1, $result['pagination']['current_page'], "page=$p");
            $this->assertGreaterThanOrEqual(1, $result['pagination']['total_pages'],  "page=$p");
        }
    }

    // ── Test: parámetro 'p' se usa en los links de paginación ────────────────

    public function testVistaSesionesUsaParamP(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/sesiones.php');
        $this->assertFalse(
            str_contains($content, '&page_num='),
            'La vista sesiones.php NO debe usar &page_num= (viejo parámetro)'
        );
        $this->assertTrue(
            str_contains($content, '&p='),
            'La vista sesiones.php debe usar &p= para paginación'
        );
    }

    public function testVistaUsuariosUsaParamP(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/admin/usuarios.php');
        $this->assertFalse(
            str_contains($content, '&page_num='),
            'La vista usuarios.php NO debe usar &page_num='
        );
        $this->assertTrue(
            str_contains($content, '&p='),
            'La vista usuarios.php debe usar &p= para paginación'
        );
    }

    // ── Test: controladores usan $_GET['p'] no $_GET['page'] ─────────────────

    public function testSesionesControllerUsaParamP(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/SesionesController.php');
        $this->assertStringContains(
            "intval(\$_GET['p']",
            $content,
            'SesionesController debe usar $_GET["p"] para página'
        );
        $this->assertFalse(
            str_contains($content, "intval(\$_GET['page']"),
            'SesionesController NO debe usar $_GET["page"] para paginación'
        );
    }

    public function testUsuariosControllerUsaParamP(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/UsuariosController.php');
        $this->assertStringContains(
            "intval(\$_GET['p']",
            $content,
            'UsuariosController debe usar $_GET["p"] para página'
        );
    }

    public function testProgramasControllerUsaParamP(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $this->assertStringContains(
            "intval(\$_GET['p']",
            $content,
            'ProgramasController debe usar $_GET["p"] para página'
        );
    }
}
