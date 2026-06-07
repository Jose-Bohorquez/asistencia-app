<?php
/**
 * Unit tests para el modelo Usuario.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class UsuarioModelTest extends TestCase {

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            (new Database())->connect();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function model(): Usuario {
        require_once APP_ROOT . '/app/models/Usuario.php';
        return new Usuario();
    }

    // ── Tests: métodos existen ────────────────────────────────────────────────

    public function testMetodosNuevosExisten(): void {
        $u = $this->model();
        $this->assertTrue(method_exists($u, 'getPaginated'),           'getPaginated debe existir');
        $this->assertTrue(method_exists($u, 'getRoles'),               'getRoles debe existir');
        $this->assertTrue(method_exists($u, 'countAssociatedCourses'), 'countAssociatedCourses debe existir');
        $this->assertTrue(method_exists($u, 'exportar'),               'exportar debe existir');
        $this->assertTrue(method_exists($u, 'findByUsername'),         'findByUsername debe existir');
    }

    // ── Tests: getRoles (sin BD) ──────────────────────────────────────────────

    public function testGetRolesRetornaArrayConTresRoles(): void {
        $u = $this->model();
        $roles = $u->getRoles();
        $this->assertIsArray($roles);
        $this->assertArrayHasKey('super_admin', $roles);
        $this->assertArrayHasKey('admin',       $roles);
        $this->assertArrayHasKey('profesor',    $roles);
        $this->assertCount(3, $roles);
    }

    public function testGetRolesValoresNoVacios(): void {
        $u = $this->model();
        foreach ($u->getRoles() as $label) {
            $this->assertNotEmpty($label, 'El label del rol no debe estar vacío');
        }
    }

    // ── Tests: con BD ─────────────────────────────────────────────────────────

    public function testPaginationEstructura(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u      = $this->model();
        $result = $u->getPaginated(1, 5, []);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data',       $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertGreaterThanOrEqual(1, $result['pagination']['total_pages']);
    }

    public function testPaginationFiltroRol(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u      = $this->model();
        $result = $u->getPaginated(1, 20, ['rol' => 'profesor']);
        $this->assertIsArray($result);
        foreach ($result['data'] as $row) {
            $this->assertEquals('profesor', $row['rol'], 'Todos los resultados deben ser profesores');
        }
    }

    public function testFindByUsernameConUsernameInexistente(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u      = $this->model();
        $result = $u->findByUsername('__usuario_que_no_existe_' . time() . '__');
        $this->assertNull($result, 'findByUsername con username inexistente debe retornar null');
    }

    public function testCountAssociatedCoursesConIdCero(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u     = $this->model();
        $count = $u->countAssociatedCourses(0);
        $this->assertEquals(0, $count);
        $this->assertTrue(is_int($count));
    }

    public function testExportarRetornaArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u      = $this->model();
        $result = $u->exportar();
        $this->assertIsArray($result);
    }

    public function testPasswordNoApareceEnExportar(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u      = $this->model();
        $result = $u->exportar();
        foreach ($result as $row) {
            $this->assertFalse(isset($row['password']), 'La contraseña nunca debe estar en exportar()');
        }
    }

    public function testAutenticarConCredencialesInvalidas(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $u      = $this->model();
        $result = $u->authenticate('usuario_invalido_xyz', 'contraseña_invalida_xyz');
        $this->assertTrue(
            isset($result['errors']),
            'authenticate con credenciales inválidas debe retornar errors'
        );
    }
}
