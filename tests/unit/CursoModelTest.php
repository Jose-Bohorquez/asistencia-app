<?php
/**
 * Unit tests para el modelo Curso.
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();

class CursoModelTest extends TestCase {

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            (new Database())->connect();
            return true;
        } catch (Throwable) { return false; }
    }

    private function model(): Curso {
        require_once APP_ROOT . '/app/models/Curso.php';
        return new Curso();
    }

    public function testMetodosCRUDExisten(): void {
        $c = $this->model();
        $this->assertTrue(method_exists($c, 'create'));
        $this->assertTrue(method_exists($c, 'update'));
        $this->assertTrue(method_exists($c, 'delete'));
        $this->assertTrue(method_exists($c, 'find'));
    }

    public function testMetodosRelacionalesExisten(): void {
        $c = $this->model();
        $this->assertTrue(method_exists($c, 'getAllWithRelations'), 'getAllWithRelations debe existir');
        $this->assertTrue(method_exists($c, 'getWithRelations'),   'getWithRelations debe existir');
        $this->assertTrue(method_exists($c, 'getByProfesor'),      'getByProfesor debe existir');
        $this->assertTrue(method_exists($c, 'getByPrograma'),      'getByPrograma debe existir');
        $this->assertTrue(method_exists($c, 'getEstudiantes'),     'getEstudiantes debe existir');
    }

    public function testMetodosDeConteoExisten(): void {
        $c = $this->model();
        $this->assertTrue(method_exists($c, 'countByProfesor'),  'countByProfesor debe existir');
        $this->assertTrue(method_exists($c, 'countByPrograma'),  'countByPrograma debe existir');
        $this->assertTrue(method_exists($c, 'getAll'),           'getAll debe existir');
    }

    public function testNoCotieneTipoEnSQLRelacional(): void {
        $content = file_get_contents(APP_ROOT . '/app/models/Curso.php');
        $this->assertFalse(
            str_contains($content, 'p.tipo as programa_tipo'),
            'getAllWithRelations NO debe seleccionar p.tipo (columna no existe en BD)'
        );
    }

    public function testGetAllWithRelationsRetornaArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $c      = $this->model();
        $result = $c->getAllWithRelations();
        $this->assertIsArray($result);
    }

    public function testCountByProgramaConIdCeroEsCero(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $c = $this->model();
        $this->assertEquals(0, $c->countByPrograma(0));
    }

    public function testCountByProfesorConIdCeroEsCero(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $c = $this->model();
        $this->assertEquals(0, $c->countByProfesor(0));
    }

    public function testGetByProgramaRetornaArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $c = $this->model();
        $this->assertIsArray($c->getByPrograma(1));
    }

    public function testCodigoExistsConCodigoFalso(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        $c = $this->model();
        $this->assertFalse($c->codigoExists('__CODIGO_QUE_NO_EXISTE__XYZ__'));
    }
}
