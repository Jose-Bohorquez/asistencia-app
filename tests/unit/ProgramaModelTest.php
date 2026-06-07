<?php
/**
 * Unit tests para el modelo Programa.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class ProgramaModelTest extends TestCase {

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            (new Database())->connect();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function model(): Programa {
        require_once APP_ROOT . '/app/models/Programa.php';
        return new Programa();
    }

    public function testMetodosNuevosExisten(): void {
        $p = $this->model();
        $this->assertTrue(method_exists($p, 'getWithFilters'),   'getWithFilters debe existir');
        $this->assertTrue(method_exists($p, 'countConCursos'),   'countConCursos debe existir');
        $this->assertTrue(method_exists($p, 'existeCodigo'),     'existeCodigo debe existir');
        $this->assertTrue(method_exists($p, 'exportar'),         'exportar debe existir');
        $this->assertTrue(method_exists($p, 'countByPrograma'),  'countByPrograma debe existir');
    }

    public function testGetTiposEsArray(): void {
        $tipos = Programa::getTipos();
        $this->assertIsArray($tipos);
        $this->assertArrayHasKey('pregrado',     $tipos);
        $this->assertArrayHasKey('posgrado',     $tipos);
        $this->assertArrayHasKey('tecnico',      $tipos);
        $this->assertArrayHasKey('tecnologico',  $tipos);
    }

    public function testGetWithFiltrosRetornaArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $p = $this->model();
        $result = $p->getWithFilters([
            'buscar'    => '',
            'activo'    => '',
            'orden'     => 'nombre',
            'direccion' => 'ASC',
        ]);
        $this->assertIsArray($result);
    }

    public function testCountConCursosRetornaEntero(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $p     = $this->model();
        $count = $p->countConCursos();
        $this->assertTrue(is_int($count));
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testExisteCódigoEsAliasDeCodigoExists(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $p = $this->model();
        // Con código vacío ambos deben comportarse igual
        $viaExiste   = $p->existeCodigo('__codigo_inexistente__xyz__');
        $viaCodigo   = $p->codigoExists('__codigo_inexistente__xyz__');
        $this->assertEquals($viaCodigo, $viaExiste, 'existeCodigo es alias de codigoExists');
    }

    public function testExportarRetornaArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $p = $this->model();
        $result = $p->exportar();
        $this->assertIsArray($result);
    }

    public function testCountByProgramaConIdCeroRetornaCero(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $p     = $this->model();
        $count = $p->countByPrograma(0);
        $this->assertEquals(0, $count);
    }
}
