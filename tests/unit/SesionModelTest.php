<?php
/**
 * Unit tests para el modelo Sesion.
 * Los métodos que requieren BD viva se marcan skip si no hay conexión.
 */

require_once TEST_ROOT . '/TestRunner.php';

// Bootstrap mínimo para que los modelos carguen fuera del contexto web
if (!defined('APP_NAME')) {
    define('APP_NAME', 'TestSuite');
}
// Simular sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SesionModelTest extends TestCase {

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            $db = new Database();
            $db->connect();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function model(): Sesion {
        require_once APP_ROOT . '/app/models/Sesion.php';
        return new Sesion();
    }

    // ── Tests: constantes de estado ──────────────────────────────────────────

    public function testEstadoConstantesExisten(): void {
        require_once APP_ROOT . '/app/models/Sesion.php';
        // La BD tiene ENUM('activa','finalizada','cancelada') — 'programada' NO existe.
        $this->assertEquals('activa',     Sesion::ESTADO_ACTIVA);
        $this->assertEquals('finalizada', Sesion::ESTADO_FINALIZADA);
        $this->assertEquals('cancelada',  Sesion::ESTADO_CANCELADA);
        // Verificar que ESTADO_PROGRAMADA ya no existe (causaba fallo al crear sesiones)
        $this->assertFalse(
            defined('Sesion::ESTADO_PROGRAMADA'),
            'Sesion::ESTADO_PROGRAMADA no debe existir — "programada" no es un ENUM válido en la BD'
        );
    }

    // ── Tests: métodos añadidos existen ──────────────────────────────────────

    public function testMetodosNuevosExisten(): void {
        $sesion = $this->model();
        $this->assertTrue(method_exists($sesion, 'getPaginated'),          'getPaginated debe existir');
        $this->assertTrue(method_exists($sesion, 'getCursosForUser'),      'getCursosForUser debe existir');
        $this->assertTrue(method_exists($sesion, 'canUserManageSession'),  'canUserManageSession debe existir');
        $this->assertTrue(method_exists($sesion, 'countAttendances'),      'countAttendances debe existir');
        $this->assertTrue(method_exists($sesion, 'updateStatus'),          'updateStatus debe existir');
        $this->assertTrue(method_exists($sesion, 'canUserManageCourse'),   'canUserManageCourse debe existir');
        $this->assertTrue(method_exists($sesion, 'exportar'),              'exportar debe existir');
    }

    // ── Tests: lógica sin BD ─────────────────────────────────────────────────

    public function testPaginationStructure(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        $result = $sesion->getPaginated(1, 5, []);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data',       $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('current_page', $result['pagination']);
        $this->assertArrayHasKey('total_pages',  $result['pagination']);
        $this->assertArrayHasKey('total',        $result['pagination']);
        $this->assertEquals(1, $result['pagination']['current_page']);
    }

    public function testPaginationFiltroPorEstado(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        $result = $sesion->getPaginated(1, 10, ['estado' => 'activa']);
        $this->assertIsArray($result);
        foreach ($result['data'] as $row) {
            $this->assertEquals('activa', $row['estado'], 'Todos los resultados deben ser activos');
        }
    }

    public function testCountAttendancesDevuelveEntero(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        // Con id=0 no debe haber asistencias → 0
        $count = $sesion->countAttendances(0);
        $this->assertEquals(0, $count);
        $this->assertTrue(is_int($count), 'countAttendances debe retornar entero');
    }

    public function testCanUserManageSessionConIdInvalido(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        $result = $sesion->canUserManageSession(0, 0);
        $this->assertFalse($result, 'IDs 0 nunca deberían ser válidos');
    }

    public function testCanUserManageCourseConIdInvalido(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        $result = $sesion->canUserManageCourse(0, 0);
        $this->assertFalse($result);
    }

    public function testExportarRetornaArray(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        $result = $sesion->exportar([]);
        $this->assertIsArray($result, 'exportar() debe retornar array');
    }

    public function testUpdateStatusConEstadoInvalido(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        // Con ID inexistente debe retornar false o errors
        $result = $sesion->updateStatus(999999, 'activa');
        // activar() retorna errors cuando la sesión no existe
        $isFalseOrError = ($result === false || (is_array($result) && isset($result['errors'])));
        $this->assertTrue($isFalseOrError, 'updateStatus con ID inexistente debe fallar gracefully');
    }

    public function testGetCursosForUserConRolAdmin(): void {
        if (!$this->dbAvailable()) {
            $this->skip('No hay conexión a la base de datos');
        }
        $sesion = $this->model();
        $result = $sesion->getCursosForUser(1, 'admin');
        $this->assertIsArray($result, 'getCursosForUser debe retornar array');
    }
}
