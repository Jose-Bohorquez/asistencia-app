<?php
$pageTitle = 'Programas - ' . APP_NAME;
$csrfToken = $_SESSION['csrf_token'] ?? '';
$userRol   = $_SESSION['user_rol'] ?? '';
$flash     = $flash_message ?? null;
$canCreate = $can_create ?? false;
$canEdit   = $can_edit   ?? false;
$canDelete = $can_delete ?? false;

ob_start();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Programas Académicos</h1>
            <p class="text-sm text-gray-500 mt-1">Gestión de programas y su asociación con cursos</p>
        </div>
        <?php if ($canCreate): ?>
        <button id="btnNuevoPrograma"
                class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors duration-200">
            <i class="fas fa-plus"></i> Nuevo Programa
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="rounded-lg px-4 py-3 text-sm font-medium <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $stats = [
            ['label' => 'Total',    'value' => $estadisticas['total']       ?? 0, 'color' => 'blue'],
            ['label' => 'Activos',  'value' => $estadisticas['activos']     ?? 0, 'color' => 'green'],
            ['label' => 'Inactivos','value' => $estadisticas['inactivos']   ?? 0, 'color' => 'red'],
            ['label' => 'Con cursos','value'=> $estadisticas['con_cursos']  ?? 0, 'color' => 'purple'],
        ];
        $colorMap = ['blue'=>'bg-blue-50 text-blue-700','green'=>'bg-green-50 text-green-700','red'=>'bg-red-50 text-red-700','purple'=>'bg-purple-50 text-purple-700'];
        foreach ($stats as $s):
        ?>
        <div class="<?= $colorMap[$s['color']] ?> rounded-xl p-3 text-center">
            <p class="text-2xl font-bold"><?= (int)$s['value'] ?></p>
            <p class="text-xs font-medium mt-1"><?= $s['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Filtro de estado -->
    <form method="GET" action="index.php" class="flex flex-wrap gap-3 items-center">
        <input type="hidden" name="page" value="programas">
        <select name="activo" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="1" <?= ($filtros['activo'] ?? '1') === '1' ? 'selected' : '' ?>>Solo activos</option>
            <option value="0" <?= ($filtros['activo'] ?? '1') === '0' ? 'selected' : '' ?>>Solo inactivos</option>
            <option value=""  <?= ($filtros['activo'] ?? '1') === ''  ? 'selected' : '' ?>>Todos</option>
        </select>
        <input type="text" name="buscar" value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
               placeholder="Buscar programa..."
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <button type="submit" class="px-3 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition-colors text-sm">
            <i class="fas fa-search"></i>
        </button>
    </form>

    <!-- Formulario crear/editar (oculto) -->
    <?php if ($canCreate): ?>
    <div id="formPrograma" class="hidden bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="bg-gradient-to-r from-blue-700 to-blue-800 px-6 py-4 rounded-t-xl">
            <h2 class="text-white font-semibold text-lg" id="formTitle">Nuevo Programa</h2>
        </div>
        <form id="programaForm" method="POST" action="index.php?page=programas&action=create" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="programaId" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código <span class="text-red-500">*</span></label>
                    <input type="text" name="codigo" id="inputCodigo" required maxlength="20"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" id="inputNombre" required maxlength="100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="activo" id="inputActivo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="btnCancelarForm"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow-sm transition-colors">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Tabla / cards -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <?php if (empty($programas)): ?>
        <div class="text-center py-16 text-gray-500">
            <i class="fas fa-graduation-cap text-4xl text-gray-300 mb-4 block"></i>
            <p class="text-lg font-medium">No hay programas registrados</p>
            <?php if ($canCreate): ?>
            <p class="text-sm mt-1">Usa el botón <strong>Nuevo Programa</strong> para crear uno</p>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cursos</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Creado</th>
                        <?php if ($canEdit || $canDelete): ?>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($programas as $programa): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">
                            <?= htmlspecialchars($programa['codigo']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800">
                            <?= htmlspecialchars($programa['nombre']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                <?= (int)($programa['total_cursos'] ?? 0) ?> curso(s)
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($programa['activo']): ?>
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Activo
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactivo
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <?= isset($programa['created_at']) ? date('d/m/Y', strtotime($programa['created_at'])) : '—' ?>
                        </td>
                        <?php if ($canEdit || $canDelete): ?>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <?php if ($canEdit): ?>
                                <button type="button"
                                        onclick="editarPrograma(<?= htmlspecialchars(json_encode(['id'=>$programa['id'],'codigo'=>$programa['codigo'],'nombre'=>$programa['nombre'],'activo'=>$programa['activo']])) ?>)"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <?php endif; ?>
                                <?php if ($canDelete && $programa['activo'] && (int)($programa['total_cursos'] ?? 0) === 0): ?>
                                <form method="POST" action="index.php?page=programas&action=delete" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$programa['id'] ?>">
                                    <button type="button"
                                            onclick="confirmarEliminarPrograma(this.closest('form'), '<?= htmlspecialchars(addslashes($programa['nombre']), ENT_QUOTES) ?>')"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded text-xs font-medium transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php elseif ($canDelete && $programa['activo']): ?>
                                <span class="text-xs text-gray-400 italic">Tiene cursos</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden divide-y divide-gray-100">
            <?php foreach ($programas as $programa): ?>
            <div class="p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($programa['nombre']) ?></p>
                        <p class="text-xs text-gray-500 font-mono mt-0.5"><?= htmlspecialchars($programa['codigo']) ?></p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $programa['activo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                        <?= $programa['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
                <p class="text-xs text-gray-500 mb-2"><?= (int)($programa['total_cursos'] ?? 0) ?> curso(s) asociado(s)</p>
                <?php if ($canEdit || $canDelete): ?>
                <div class="flex gap-1">
                    <?php if ($canEdit): ?>
                    <button type="button"
                            onclick="editarPrograma(<?= htmlspecialchars(json_encode(['id'=>$programa['id'],'codigo'=>$programa['codigo'],'nombre'=>$programa['nombre'],'activo'=>$programa['activo']])) ?>)"
                            class="px-2 py-1 bg-yellow-50 text-yellow-700 rounded text-xs font-medium">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <?php endif; ?>
                    <?php if ($canDelete && $programa['activo'] && (int)($programa['total_cursos'] ?? 0) === 0): ?>
                    <form method="POST" action="index.php?page=programas&action=delete" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int)$programa['id'] ?>">
                        <button type="button"
                                onclick="confirmarEliminarPrograma(this.closest('form'), '<?= htmlspecialchars(addslashes($programa['nombre']), ENT_QUOTES) ?>')"
                                class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-medium">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const formPrograma     = document.getElementById('formPrograma');
const btnNuevoPrograma = document.getElementById('btnNuevoPrograma');
const btnCancelarForm  = document.getElementById('btnCancelarForm');
const formTitle        = document.getElementById('formTitle');
const programaForm     = document.getElementById('programaForm');
const programaId       = document.getElementById('programaId');
const inputCodigo      = document.getElementById('inputCodigo');
const inputNombre      = document.getElementById('inputNombre');
const inputActivo      = document.getElementById('inputActivo');

if (btnNuevoPrograma) {
    btnNuevoPrograma.addEventListener('click', () => {
        formTitle.textContent = 'Nuevo Programa';
        programaId.value = '';
        programaForm.action = 'index.php?page=programas&action=create';
        programaForm.reset();
        formPrograma.classList.remove('hidden');
        formPrograma.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

if (btnCancelarForm) {
    btnCancelarForm.addEventListener('click', () => formPrograma.classList.add('hidden'));
}

function editarPrograma(p) {
    formTitle.textContent = 'Editar Programa';
    programaId.value    = p.id;
    inputCodigo.value   = p.codigo;
    inputNombre.value   = p.nombre;
    inputActivo.value   = p.activo;
    programaForm.action = 'index.php?page=programas&action=edit';
    formPrograma.classList.remove('hidden');
    formPrograma.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function confirmarEliminarPrograma(form, nombre) {
    Swal.fire({
        title: '¿Eliminar programa?',
        html: `El programa <strong>${nombre}</strong> será eliminado permanentemente. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then(r => { if (r.isConfirmed) form.submit(); });
}
</script>

<?php
$content = ob_get_clean();
include '../app/views/layouts/base.php';
