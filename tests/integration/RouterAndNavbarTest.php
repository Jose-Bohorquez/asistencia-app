<?php
/**
 * Tests de integración: Router, Navbar y links de navegación.
 * Verifica que cada ruta tenga el controlador correcto y que los links
 * del navbar apunten a páginas que existen.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class RouterAndNavbarTest extends TestCase {

    private string $routerFile;

    protected function setUp(): void {
        $this->routerFile = APP_ROOT . '/app/core/Router.php';
    }

    // ── Archivo Router existe ─────────────────────────────────────────────────

    public function testRouterArchivoExiste(): void {
        $this->assertTrue(file_exists($this->routerFile), 'Router.php debe existir');
    }

    // ── Páginas del navbar apuntan a controladores que existen ───────────────

    public function testControladorDashboardExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/controllers/AdminController.php'),
            'AdminController debe existir para page=dashboard'
        );
    }

    public function testControladorSesionesExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/controllers/SesionesController.php'),
            'SesionesController debe existir para page=sesiones'
        );
    }

    public function testControladorUsuariosExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/controllers/UsuariosController.php'),
            'UsuariosController debe existir para page=usuarios'
        );
    }

    public function testControladorProgramasExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/controllers/ProgramasController.php'),
            'ProgramasController debe existir para page=programas'
        );
    }

    public function testControladorCursosExiste(): void {
        $this->assertTrue(
            file_exists(APP_ROOT . '/app/controllers/AdminController.php'),
            'Controller para cursos debe existir'
        );
    }

    // ── Controladores implementan handleRequest ───────────────────────────────

    public function testSesionesControllerImplementaHandleRequest(): void {
        require_once APP_ROOT . '/app/controllers/SesionesController.php';
        $this->assertTrue(method_exists('SesionesController', 'handleRequest'));
    }

    public function testUsuariosControllerImplementaHandleRequest(): void {
        require_once APP_ROOT . '/app/controllers/UsuariosController.php';
        $this->assertTrue(method_exists('UsuariosController', 'handleRequest'));
    }

    public function testProgramasControllerImplementaHandleRequest(): void {
        require_once APP_ROOT . '/app/controllers/ProgramasController.php';
        $this->assertTrue(method_exists('ProgramasController', 'handleRequest'));
    }

    // ── Vistas referenciadas por los controladores existen ───────────────────

    public function testVistaSesionesExisteYEsLegible(): void {
        $path = APP_ROOT . '/app/views/admin/sesiones.php';
        $this->assertTrue(file_exists($path));
        $content = file_get_contents($path);
        $this->assertNotEmpty($content);
    }

    public function testVistaUsuariosExisteYEsLegible(): void {
        $path = APP_ROOT . '/app/views/admin/usuarios.php';
        $this->assertTrue(file_exists($path));
        $content = file_get_contents($path);
        $this->assertNotEmpty($content);
    }

    public function testVistaDashboardExisteYEsLegible(): void {
        $path = APP_ROOT . '/app/views/admin/dashboard.php';
        $this->assertTrue(file_exists($path));
        $content = file_get_contents($path);
        $this->assertNotEmpty($content);
    }

    public function testVistaProgramasExisteYEsLegible(): void {
        $path = APP_ROOT . '/app/views/admin/programas.php';
        $this->assertTrue(file_exists($path));
        $content = file_get_contents($path);
        $this->assertNotEmpty($content);
    }

    // ── Navbar incluye links a todas las secciones ───────────────────────────

    public function testNavbarTieneEnlaceDashboard(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        $this->assertStringContains('page=dashboard', $content, 'Navbar debe tener link a dashboard');
    }

    public function testNavbarTieneEnlaceSesiones(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        $this->assertStringContains('page=sesiones', $content, 'Navbar debe tener link a sesiones');
    }

    public function testNavbarTieneEnlaceCursos(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        $this->assertStringContains('page=cursos', $content, 'Navbar debe tener link a cursos');
    }

    public function testNavbarTieneEnlaceUsuarios(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        $this->assertStringContains('page=usuarios', $content, 'Navbar debe tener link a usuarios');
    }

    public function testNavbarTieneEnlaceProgramas(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        $this->assertStringContains('page=programas', $content, 'Navbar debe tener link a programas');
    }

    // ── Navbar no tiene links rotos (sintaxis correcta) ───────────────────────

    public function testNavbarNoTieneHrefVacios(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        $this->assertFalse(
            str_contains($content, 'href=""'),
            'Navbar NO debe tener href vacíos'
        );
        $this->assertFalse(
            str_contains($content, "href=''"),
            'Navbar NO debe tener href vacíos con comillas simples'
        );
    }

    // ── Session variables usadas en navbar son las correctas ─────────────────

    public function testNavbarUsaSesionUserRol(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/navbar.php');
        // navbar.php debe usar session variables que existen
        $hasUserRol = str_contains($content, "\$_SESSION['user_rol']");
        $hasRol     = str_contains($content, "\$_SESSION['rol']");
        $this->assertTrue(
            $hasUserRol || $hasRol,
            'Navbar debe leer el rol del usuario desde la sesión'
        );
    }

    // ── AuthController establece todas las variables de sesión necesarias ─────

    public function testAuthControllerEstableceUserRol(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/AuthController.php');
        $this->assertStringContains(
            "\$_SESSION['user_rol']",
            $content,
            'AuthController debe establecer $_SESSION["user_rol"]'
        );
        $this->assertStringContains(
            "\$_SESSION['rol']",
            $content,
            'AuthController debe establecer $_SESSION["rol"] (compatibilidad)'
        );
    }

    public function testAuthControllerEstableceUserNombre(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/AuthController.php');
        $this->assertStringContains(
            "\$_SESSION['user_nombre']",
            $content,
            'AuthController debe establecer $_SESSION["user_nombre"]'
        );
        $this->assertStringContains(
            "\$_SESSION['nombre']",
            $content,
            'AuthController debe establecer $_SESSION["nombre"] (compatibilidad)'
        );
    }

    // ── Base layout incluye navbar.php ────────────────────────────────────────

    public function testBasePHPIncluirNavbar(): void {
        $content = file_get_contents(APP_ROOT . '/app/views/layouts/base.php');
        $this->assertStringContains(
            "include 'navbar.php'",
            $content,
            'base.php debe incluir navbar.php'
        );
    }

    public function testBaseControllerNoIncluirHeaderOFooter(): void {
        $content = file_get_contents(APP_ROOT . '/app/controllers/BaseController.php');
        $this->assertFalse(
            str_contains($content, "include __DIR__ . '/../views/layouts/header.php'"),
            'BaseController::render() NO debe incluir header.php (causa doble navbar)'
        );
    }
}
