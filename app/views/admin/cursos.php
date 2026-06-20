<?php
$pageTitle = 'Gestión de Cursos - ' . APP_NAME;

ob_start();

$sedes = [
    ''                 => 'Sin sede especificada',
    'CAT BARRANQUILLA' => 'CAT BARRANQUILLA',
    'CAT CAJAMARCA'    => 'CAT CAJAMARCA',
    'CAT CALI'         => 'CAT CALI',
    'CAT CHAPARRAL'    => 'CAT CHAPARRAL',
    'CAT GIRARDOT'     => 'CAT GIRARDOT',
    'CAT HONDA'        => 'CAT HONDA',
    'CAT IBAGUE'       => 'CAT IBAGUE',
    'CAT ICONONZO'     => 'CAT ICONONZO',
    'CAT KENNEDY'      => 'CAT KENNEDY',
    'CAT LIBANO'       => 'CAT LIBANO',
    'CAT MARIQUITA'    => 'CAT MARIQUITA',
    'CAT MEDELLIN'     => 'CAT MEDELLIN',
    'CAT MELGAR'       => 'CAT MELGAR',
    'CAT MOCOA'        => 'CAT MOCOA',
    'CAT NEIVA'        => 'CAT NEIVA',
    'CAT PACHO'        => 'CAT PACHO',
    'CAT PEREIRA'      => 'CAT PEREIRA',
    'CAT PLANADAS'     => 'CAT PLANADAS',
    'CAT POPAYAN'      => 'CAT POPAYAN',
    'CAT PURIFICACION' => 'CAT PURIFICACION',
];

$semestresOrden = ['I','II','III','IV','V','VI','VII','VIII','IX','X'];
$semestresList  = [];
if (!empty($cursos)) {
    foreach ($cursos as $c) {
        $sem = $c['semestre'] ?? '';
        if ($sem !== '' && !in_array($sem, $semestresList)) {
            $semestresList[] = $sem;
        }
    }
    usort($semestresList, function($a, $b) use ($semestresOrden) {
        $ia = array_search($a, $semestresOrden);
        $ib = array_search($b, $semestresOrden);
        return ($ia !== false ? $ia : 99) - ($ib !== false ? $ib : 99);
    });
}

$totalCursos = count($cursos ?? []);
$esAdmin = in_array($current_user['rol'] ?? '', ['super_admin', 'admin']);
?>

<!-- Cabecera -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Gestión de Cursos</h1>
        <p class="text-sm text-gray-500 mt-0.5"><?= $totalCursos ?> curso<?= $totalCursos !== 1 ? 's' : '' ?> registrado<?= $totalCursos !== 1 ? 's' : '' ?></p>
    </div>
    <?php if ($can_create ?? false): ?>
    <button id="btnNuevoCurso" type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
        <i class="fas fa-plus"></i> Nuevo Curso
    </button>
    <?php endif; ?>
</div>

<!-- Alertas flash -->
<?php if (!empty($flash_message)): ?>
<?php $fType = $flash_message['type'] ?? 'info'; $fText = $flash_message['text'] ?? $flash_message['message'] ?? ''; ?>
<div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium
    <?= $fType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
    <i class="fas <?= $fType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-1.5"></i>
    <?= htmlspecialchars($fText) ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium bg-red-50 text-red-800 border border-red-200">
    <i class="fas fa-exclamation-circle mr-1.5"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Formulario crear/editar (oculto por defecto) -->
<div id="formCurso" class="hidden mb-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <h2 id="formTitle" class="text-lg font-semibold text-gray-900">Nuevo Curso</h2>
        <button type="button" id="btnCerrarForm" class="text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form method="POST" action="index.php?page=cursos" id="cursoForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
        <input type="hidden" name="id" id="cursoId" value="">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">

            <!-- Código -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código <span class="text-red-500">*</span></label>
                <input type="text" name="codigo" id="campoCodigo" required maxlength="20"
                       placeholder="Ej: S1G1-01"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Nombre -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" id="campoNombre" required maxlength="100"
                       placeholder="Nombre del curso"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Programa -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Programa <span class="text-red-500">*</span></label>
                <select name="programa_id" id="campoProgramaId" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <option value="">Seleccionar programa...</option>
                    <?php foreach (($programas ?? []) as $prog): ?>
                    <option value="<?= (int)$prog['id'] ?>"><?= htmlspecialchars($prog['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($esAdmin): ?>
            <!-- Profesor -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Profesor <span class="text-red-500">*</span></label>
                <select name="profesor_id" id="campoProfesorId" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <option value="">Seleccionar profesor...</option>
                    <?php foreach (($profesores ?? []) as $prof): ?>
                    <option value="<?= (int)$prof['id'] ?>"><?= htmlspecialchars($prof['nombre'] . ' (' . $prof['username'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Área -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Área / Semestre descriptivo</label>
                <input type="text" name="area" id="campoArea" maxlength="100"
                       placeholder="Ej: Semestre III"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Semestre -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                <input type="text" name="semestre" id="campoSemestre" maxlength="10"
                       placeholder="Ej: III"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Grupo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                <input type="text" name="grupo" id="campoGrupo" maxlength="10"
                       placeholder="Ej: 1"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Aula -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aula</label>
                <input type="text" name="aula" id="campoAula" maxlength="20"
                       placeholder="Ej: 301"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Sede -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                <select name="sede" id="campoSede"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                    <?php foreach ($sedes as $val => $lbl): ?>
                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <button type="button" id="btnCancelar"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow-sm transition-colors">
                <i class="fas fa-save mr-1"></i> Guardar curso
            </button>
        </div>
    </form>
</div>

<!-- Barra de búsqueda y filtros -->
<?php if (!empty($cursos)): ?>
<div class="flex flex-col sm:flex-row gap-2 mb-4">
    <div class="relative flex-1">
        <input type="text" id="buscadorCursos"
               placeholder="Buscar por nombre, código o profesor..."
               class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
    </div>
    <select id="filtroSemestre"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
        <option value="">Todos los semestres (<?= $totalCursos ?>)</option>
        <?php foreach ($semestresList as $sem):
            $cnt = count(array_filter($cursos, fn($c) => ($c['semestre'] ?? '') === $sem));
        ?>
        <option value="<?= htmlspecialchars($sem) ?>">Semestre <?= htmlspecialchars($sem) ?> (<?= $cnt ?>)</option>
        <?php endforeach; ?>
    </select>
    <div class="text-sm text-gray-500 flex items-center gap-1 shrink-0 px-1">
        <span id="cursosVisibles"><?= $totalCursos ?></span> de <?= $totalCursos ?>
    </div>
</div>
<?php endif; ?>

<!-- Lista de cursos -->
<?php if (empty($cursos)): ?>
<div class="bg-white border border-gray-200 rounded-xl shadow-sm">
    <div class="text-center py-16 text-gray-500">
        <i class="fas fa-book text-4xl text-gray-300 mb-4 block"></i>
        <p class="text-lg font-medium">No hay cursos registrados</p>
        <?php if ($can_create ?? false): ?>
        <p class="text-sm mt-1">Usa el botón <strong>Nuevo Curso</strong> para crear uno</p>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden" id="listaCursos">

    <!-- Tabla desktop -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Programa</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Profesor</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sem.</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grupo</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aula</th>
                    <?php if (($can_edit ?? false) || ($can_delete ?? false)): ?>
                    <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100" id="tablaCursosBody">
                <?php foreach ($cursos as $curso): ?>
                <tr class="hover:bg-gray-50 transition-colors curso-row"
                    data-semestre="<?= htmlspecialchars($curso['semestre'] ?? '', ENT_QUOTES) ?>"
                    data-texto="<?= htmlspecialchars(strtolower(($curso['codigo'] ?? '') . ' ' . ($curso['nombre'] ?? '') . ' ' . ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['programa_nombre'] ?? '')), ENT_QUOTES) ?>">
                    <td class="px-3 py-3 text-xs font-mono font-medium text-gray-700"><?= htmlspecialchars($curso['codigo'] ?? '') ?></td>
                    <td class="px-3 py-3 text-sm font-medium text-gray-900 max-w-[200px] truncate"><?= htmlspecialchars($curso['nombre'] ?? '') ?></td>
                    <td class="px-3 py-3 text-xs text-gray-500 max-w-[130px] truncate"><?= htmlspecialchars($curso['programa_nombre'] ?? '—') ?></td>
                    <td class="px-3 py-3 text-xs text-gray-500"><?= htmlspecialchars($curso['profesor_nombre'] ?? '—') ?></td>
                    <td class="px-3 py-3">
                        <?php if ($curso['semestre'] ?? ''): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                            <?= htmlspecialchars($curso['semestre']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-300 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 text-xs text-gray-500"><?= htmlspecialchars($curso['grupo'] ?? '—') ?></td>
                    <td class="px-3 py-3 text-xs text-gray-500"><?= htmlspecialchars($curso['aula'] ?? '—') ?></td>
                    <?php if (($can_edit ?? false) || ($can_delete ?? false)): ?>
                    <td class="px-3 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <?php if ($can_edit ?? false): ?>
                            <button type="button"
                                    class="btnEditar inline-flex items-center gap-1 px-2 py-1.5 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded text-xs font-medium transition-colors"
                                    data-id="<?= (int)$curso['id'] ?>"
                                    data-codigo="<?= htmlspecialchars($curso['codigo'] ?? '', ENT_QUOTES) ?>"
                                    data-nombre="<?= htmlspecialchars($curso['nombre'] ?? '', ENT_QUOTES) ?>"
                                    data-programa-id="<?= (int)($curso['programa_id'] ?? 0) ?>"
                                    data-profesor-id="<?= (int)($curso['profesor_id'] ?? 0) ?>"
                                    data-area="<?= htmlspecialchars($curso['area'] ?? '', ENT_QUOTES) ?>"
                                    data-semestre="<?= htmlspecialchars($curso['semestre'] ?? '', ENT_QUOTES) ?>"
                                    data-grupo="<?= htmlspecialchars($curso['grupo'] ?? '', ENT_QUOTES) ?>"
                                    data-aula="<?= htmlspecialchars($curso['aula'] ?? '', ENT_QUOTES) ?>"
                                    data-sede="<?= htmlspecialchars($curso['sede'] ?? '', ENT_QUOTES) ?>">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <?php endif; ?>
                            <?php if ($can_delete ?? false): ?>
                            <button type="button"
                                    class="btnEliminar inline-flex items-center gap-1 px-2 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded text-xs font-medium transition-colors"
                                    data-id="<?= (int)$curso['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($curso['nombre'] ?? '', ENT_QUOTES) ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cards mobile -->
    <div class="md:hidden divide-y divide-gray-100" id="mobileCardsCursos">
        <?php foreach ($cursos as $curso): ?>
        <div class="p-4 curso-row"
             data-semestre="<?= htmlspecialchars($curso['semestre'] ?? '', ENT_QUOTES) ?>"
             data-texto="<?= htmlspecialchars(strtolower(($curso['codigo'] ?? '') . ' ' . ($curso['nombre'] ?? '') . ' ' . ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['programa_nombre'] ?? '')), ENT_QUOTES) ?>">
            <div class="flex items-start justify-between mb-1.5">
                <div class="flex-1 min-w-0 pr-2">
                    <p class="font-semibold text-gray-900 text-sm leading-tight"><?= htmlspecialchars($curso['nombre'] ?? '') ?></p>
                    <p class="text-xs text-gray-400 font-mono mt-0.5"><?= htmlspecialchars($curso['codigo'] ?? '') ?></p>
                </div>
                <?php if ($curso['semestre'] ?? ''): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 shrink-0">
                    Sem. <?= htmlspecialchars($curso['semestre']) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500 mb-2">
                <span><span class="font-medium text-gray-600">Profesor:</span> <?= htmlspecialchars($curso['profesor_nombre'] ?? '—') ?></span>
                <span><span class="font-medium text-gray-600">Grupo:</span> <?= htmlspecialchars($curso['grupo'] ?? '—') ?></span>
                <?php if ($curso['aula'] ?? ''): ?>
                <span><span class="font-medium text-gray-600">Aula:</span> <?= htmlspecialchars($curso['aula']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (($can_edit ?? false) || ($can_delete ?? false)): ?>
            <div class="flex flex-wrap gap-2 mt-2">
                <?php if ($can_edit ?? false): ?>
                <button type="button"
                        class="btnEditar inline-flex items-center gap-1 px-3 py-2 bg-amber-50 text-amber-700 rounded-lg text-sm font-medium active:bg-amber-100"
                        data-id="<?= (int)$curso['id'] ?>"
                        data-codigo="<?= htmlspecialchars($curso['codigo'] ?? '', ENT_QUOTES) ?>"
                        data-nombre="<?= htmlspecialchars($curso['nombre'] ?? '', ENT_QUOTES) ?>"
                        data-programa-id="<?= (int)($curso['programa_id'] ?? 0) ?>"
                        data-profesor-id="<?= (int)($curso['profesor_id'] ?? 0) ?>"
                        data-area="<?= htmlspecialchars($curso['area'] ?? '', ENT_QUOTES) ?>"
                        data-semestre="<?= htmlspecialchars($curso['semestre'] ?? '', ENT_QUOTES) ?>"
                        data-grupo="<?= htmlspecialchars($curso['grupo'] ?? '', ENT_QUOTES) ?>"
                        data-aula="<?= htmlspecialchars($curso['aula'] ?? '', ENT_QUOTES) ?>"
                        data-sede="<?= htmlspecialchars($curso['sede'] ?? '', ENT_QUOTES) ?>">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <?php endif; ?>
                <?php if ($can_delete ?? false): ?>
                <button type="button"
                        class="btnEliminar inline-flex items-center gap-1 px-3 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium active:bg-red-100"
                        data-id="<?= (int)$curso['id'] ?>"
                        data-nombre="<?= htmlspecialchars($curso['nombre'] ?? '', ENT_QUOTES) ?>">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sin resultados -->
    <div id="sinResultados" class="hidden text-center py-10 text-gray-400">
        <i class="fas fa-search text-2xl mb-2 block"></i>
        <p class="text-sm">Sin resultados para la búsqueda</p>
    </div>

</div>
<?php endif; ?>

<!-- Form oculto para eliminar (POST + CSRF) -->
<form id="formDeleteCurso" method="POST" action="index.php?page=cursos" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" id="deleteId" name="delete_id" value="">
</form>

<?php
$customJS = <<<'JS'
<script>
(function() {
    // ── Referencias ──────────────────────────────────────────────
    const formCurso   = document.getElementById('formCurso');
    const btnNuevo    = document.getElementById('btnNuevoCurso');
    const btnCancelar = document.getElementById('btnCancelar');
    const btnCerrar   = document.getElementById('btnCerrarForm');
    const formTitle   = document.getElementById('formTitle');
    const campoId          = document.getElementById('cursoId');
    const campoCodigo      = document.getElementById('campoCodigo');
    const campoNombre      = document.getElementById('campoNombre');
    const campoProgramaId  = document.getElementById('campoProgramaId');
    const campoProfesorId  = document.getElementById('campoProfesorId');
    const campoArea        = document.getElementById('campoArea');
    const campoSemestre    = document.getElementById('campoSemestre');
    const campoGrupo       = document.getElementById('campoGrupo');
    const campoAula        = document.getElementById('campoAula');
    const campoSede        = document.getElementById('campoSede');

    function abrirNuevo() {
        formTitle.textContent = 'Nuevo Curso';
        campoId.value = '';
        if (campoCodigo) campoCodigo.value = '';
        if (campoNombre) campoNombre.value = '';
        if (campoProgramaId) campoProgramaId.value = '';
        if (campoProfesorId) campoProfesorId.value = '';
        if (campoArea) campoArea.value = '';
        if (campoSemestre) campoSemestre.value = '';
        if (campoGrupo) campoGrupo.value = '';
        if (campoAula) campoAula.value = '';
        if (campoSede) campoSede.value = '';
        formCurso.classList.remove('hidden');
        formCurso.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        formCurso.classList.add('hidden');
    }

    if (btnNuevo)    btnNuevo.addEventListener('click', abrirNuevo);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarForm);
    if (btnCerrar)   btnCerrar.addEventListener('click', cerrarForm);

    // ── Editar ───────────────────────────────────────────────────
    document.querySelectorAll('.btnEditar').forEach(btn => {
        btn.addEventListener('click', function() {
            formTitle.textContent = 'Editar Curso';
            campoId.value = this.dataset.id || '';
            if (campoCodigo)     campoCodigo.value     = this.dataset.codigo     || '';
            if (campoNombre)     campoNombre.value     = this.dataset.nombre     || '';
            if (campoProgramaId) campoProgramaId.value = this.dataset.programaId || '';
            if (campoProfesorId) campoProfesorId.value = this.dataset.profesorId || '';
            if (campoArea)       campoArea.value       = this.dataset.area       || '';
            if (campoSemestre)   campoSemestre.value   = this.dataset.semestre   || '';
            if (campoGrupo)      campoGrupo.value      = this.dataset.grupo      || '';
            if (campoAula)       campoAula.value       = this.dataset.aula       || '';
            if (campoSede)       campoSede.value       = this.dataset.sede       || '';
            formCurso.classList.remove('hidden');
            formCurso.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // ── Filtros (cliente) ─────────────────────────────────────────
    function aplicarFiltros() {
        const term = (document.getElementById('buscadorCursos')?.value || '').toLowerCase().trim();
        const sem  = document.getElementById('filtroSemestre')?.value || '';

        // Cuento solo las desktop rows para evitar doble conteo
        let visible = 0;
        const desktopRows   = document.querySelectorAll('#tablaCursosBody .curso-row');
        const mobileCards   = document.querySelectorAll('#mobileCardsCursos .curso-row');
        const sinResultados = document.getElementById('sinResultados');

        desktopRows.forEach(row => {
            const texto = row.dataset.texto || row.textContent.toLowerCase();
            const matchText = !term || texto.includes(term);
            const matchSem  = !sem  || row.dataset.semestre === sem;
            const show = matchText && matchSem;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        mobileCards.forEach(row => {
            const texto = row.dataset.texto || row.textContent.toLowerCase();
            const matchText = !term || texto.includes(term);
            const matchSem  = !sem  || row.dataset.semestre === sem;
            row.style.display = (matchText && matchSem) ? '' : 'none';
        });

        const contador = document.getElementById('cursosVisibles');
        if (contador) contador.textContent = visible;
        if (sinResultados) sinResultados.classList.toggle('hidden', visible > 0);
    }

    const buscador   = document.getElementById('buscadorCursos');
    const filtroSem  = document.getElementById('filtroSemestre');
    if (buscador)  buscador.addEventListener('input', aplicarFiltros);
    if (filtroSem) filtroSem.addEventListener('change', aplicarFiltros);

    // ── Eliminar ─────────────────────────────────────────────────
    document.querySelectorAll('.btnEliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id     = this.dataset.id;
            const nombre = this.dataset.nombre;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar curso?',
                    text: `"${nombre}" será eliminado permanentemente.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        document.getElementById('deleteId').value = id;
                        document.getElementById('formDeleteCurso').submit();
                    }
                });
            } else {
                if (confirm(`¿Eliminar el curso "${nombre}"?`)) {
                    document.getElementById('deleteId').value = id;
                    document.getElementById('formDeleteCurso').submit();
                }
            }
        });
    });
})();
</script>
JS;

$content = ob_get_clean();
include '../app/views/layouts/base.php';
