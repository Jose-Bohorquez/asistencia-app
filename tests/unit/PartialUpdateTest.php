<?php
/**
 * Tests que cubren el bug de validación en actualizaciones parciales.
 *
 * BUG ORIGINAL: Programa::delete(), Usuario::delete(), Sesion::activar() etc.
 * llamaban $this->update() con datos parciales (ej: ['activo'=>0]).
 * La validación del modelo exigía campos requeridos (nombre, codigo, email...)
 * que no formaban parte del cambio → retornaba ['errors'=>[...]] → las operaciones
 * fallaban silenciosamente o mostraban "éxito" incorrecto (array truthy).
 *
 * FIX: update() solo valida campos presentes en $data (array_intersect_key).
 *      Los métodos internos (activar, finalizar, cancelar, delete) usan parent::update().
 */

require_once TEST_ROOT . '/TestRunner.php';

if (!defined('APP_NAME')) define('APP_NAME', 'TestSuite');
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = 1;

class PartialUpdateTest extends TestCase {

    private function dbAvailable(): bool {
        try {
            require_once APP_ROOT . '/config/database.php';
            (new Database())->connect();
            return true;
        } catch (Throwable) { return false; }
    }

    // ── Programa ──────────────────────────────────────────────────────────────

    public function testProgramaUpdateParcialActivoNoFalla(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Programa.php';
        $p = new Programa();

        // Solo actualizar 'activo' no debe fallar por falta de nombre/codigo
        $all = $p->all(['activo' => 1], 'nombre', 1);
        if (empty($all)) $this->skip('No hay programas activos');

        $id     = $all[0]['id'];
        $result = $p->update($id, ['activo' => 0]);
        $this->assertTrue(
            $result === true || $result === 1,
            'update([activo=>0]) debe retornar true, no errors de validación'
        );
        // Restaurar
        $p->update($id, ['activo' => 1]);
    }

    public function testProgramaDeleteRetornaTrueNoErrorArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Programa.php';
        $db = (new Database())->connect();
        $p  = new Programa();

        $id = $p->create(['codigo' => 'TDEL01', 'nombre' => 'Test Delete', 'activo' => 1]);
        $this->assertTrue(is_int($id) && $id > 0, 'create debe retornar int');

        $result = $p->delete($id);
        $this->assertFalse(
            is_array($result) && isset($result['errors']),
            "delete() NO debe retornar ['errors'=>...], retornó: " . json_encode($result)
        );
        $this->assertTrue(
            $result === true || $result === 1,
            'delete() debe retornar true'
        );

        // Verificar en BD que activo=0
        $row = $db->query("SELECT activo FROM programas WHERE id=$id LIMIT 1")->fetch_assoc();
        $this->assertEquals('0', (string)$row['activo'], 'activo debe ser 0 tras delete()');

        $db->query("DELETE FROM programas WHERE id=$id");
    }

    public function testProgramaUpdateCompletoSigueValidando(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Programa.php';
        $p = new Programa();

        // Un update completo con campos vacíos debe fallar la validación
        $all = $p->all(['activo' => 1], 'nombre', 1);
        if (empty($all)) $this->skip('No hay programas activos');

        $id     = $all[0]['id'];
        $result = $p->update($id, ['codigo' => '', 'nombre' => '', 'activo' => 1]);
        $this->assertTrue(
            isset($result['errors']),
            'update con nombre y codigo vacíos debe retornar errors'
        );
    }

    // ── Usuario ───────────────────────────────────────────────────────────────

    public function testUsuarioUpdateParcialActivoNoFalla(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u = new Usuario();

        $result = $u->update(1, ['activo' => 0]);
        $this->assertFalse(
            is_array($result) && isset($result['errors']),
            "update([activo=>0]) NO debe retornar errors, retornó: " . json_encode($result)
        );
        // Restaurar
        $u->update(1, ['activo' => 1]);
    }

    public function testUsuarioDeleteRetornaTrueNoErrorArray(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u = new Usuario();

        // Crear usuario de prueba
        $testData = [
            'username' => 'test_delete_' . time(),
            'password' => 'Password123!',
            'nombre'   => 'Usuario Test',
            'email'    => 'testdel' . time() . '@test.com',
            'rol'      => 'profesor',
            'activo'   => 1,
        ];
        $id = $u->create($testData);
        if (!$id || is_array($id)) $this->skip('No se pudo crear usuario de prueba');

        $result = $u->delete($id);
        $this->assertFalse(
            is_array($result) && isset($result['errors']),
            "Usuario::delete() NO debe retornar errors: " . json_encode($result)
        );

        // Verificar soft-delete en BD
        $db  = (new Database())->connect();
        $row = $db->query("SELECT activo FROM usuarios WHERE id=$id LIMIT 1")->fetch_assoc();
        $this->assertEquals('0', (string)$row['activo'], 'activo debe ser 0 tras delete()');
    }

    // ── Sesion ────────────────────────────────────────────────────────────────

    public function testSesionUpdateParcialEstadoNoFalla(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Sesion.php';
        $s = new Sesion();

        $db   = (new Database())->connect();
        $row  = $db->query("SELECT id FROM sesiones WHERE estado='programada' LIMIT 1")->fetch_assoc();
        if (!$row) $this->skip('No hay sesiones programadas para probar');

        $sid    = $row['id'];
        $result = $s->update($sid, ['estado' => 'activa']);
        $this->assertFalse(
            is_array($result) && isset($result['errors']) && isset($result['errors']['curso_id']),
            "update([estado=>activa]) NO debe fallar por falta de curso_id, retornó: " . json_encode($result)
        );
        // Restaurar
        $s->update($sid, ['estado' => 'programada']);
    }

    public function testSesionActivarUsaParentUpdate(): void {
        // Verifica en el código que activar() llama parent::update()
        $code = file_get_contents(APP_ROOT . '/app/models/Sesion.php');
        $start = strpos($code, 'public function activar(');
        $snip  = substr($code, $start, 1200);
        $this->assertStringContains(
            'parent::update',
            $snip,
            'Sesion::activar() debe usar parent::update() para no activar validación de campos requeridos'
        );
    }

    public function testSesionFinalizarUsaParentUpdate(): void {
        $code  = file_get_contents(APP_ROOT . '/app/models/Sesion.php');
        $start = strpos($code, 'public function finalizar(');
        $snip  = substr($code, $start, 500);
        $this->assertStringContains('parent::update', $snip, 'finalizar() debe usar parent::update()');
    }

    public function testSesionCancelarUsaParentUpdate(): void {
        $code  = file_get_contents(APP_ROOT . '/app/models/Sesion.php');
        $start = strpos($code, 'public function cancelar(');
        $snip  = substr($code, $start, 1500);
        $this->assertStringContains('parent::update', $snip, 'cancelar() debe usar parent::update()');
    }

    public function testSesionRegenerateTokenUsaParentUpdate(): void {
        $code  = file_get_contents(APP_ROOT . '/app/models/Sesion.php');
        $start = strpos($code, 'public function regenerateToken(');
        $snip  = substr($code, $start, 300);
        $this->assertStringContains('parent::update', $snip, 'regenerateToken() debe usar parent::update()');
    }

    // ── Validación completa sigue activa ──────────────────────────────────────

    public function testSesionUpdateCompletoValidaCursoId(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Sesion.php';
        $s = new Sesion();

        $db  = (new Database())->connect();
        $row = $db->query("SELECT id FROM sesiones LIMIT 1")->fetch_assoc();
        if (!$row) $this->skip('No hay sesiones');

        $result = $s->update($row['id'], ['curso_id' => 0, 'fecha' => '2024-01-01', 'hora_inicio' => '08:00']);
        $this->assertTrue(
            isset($result['errors']),
            'update con curso_id=0 debe retornar error de curso inexistente'
        );
    }

    public function testUsuarioUpdateCompletoValidaRol(): void {
        if (!$this->dbAvailable()) $this->skip('Sin BD');
        require_once APP_ROOT . '/app/models/Usuario.php';
        $u = new Usuario();

        // update con rol vacío debe fallar
        $result = $u->update(1, ['nombre' => 'Test', 'email' => 'x@x.com', 'rol' => '']);
        $this->assertTrue(
            isset($result['errors']),
            'update con rol vacío debe retornar errors'
        );
    }

    // ── ProgramasController result check ──────────────────────────────────────

    public function testProgramasControllerDeleteCheckEsExplicito(): void {
        $code = file_get_contents(APP_ROOT . '/app/controllers/ProgramasController.php');
        $this->assertStringContains(
            'is_array($result)',
            $code,
            'ProgramasController::delete() debe verificar is_array($result) para detectar errors'
        );
        $this->assertStringContains(
            'isset($result[\'errors\'])',
            $code,
            'delete() debe verificar isset($result[\'errors\']) antes de declarar éxito'
        );
    }
}
