<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Gestión de Cursos</h1>
        <button id="btnNuevoCurso" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-3 sm:px-4 rounded text-sm sm:text-base w-full sm:w-auto">
            <i class="fas fa-plus mr-1"></i> Nuevo Curso
        </button>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php 
    // Mostrar mensaje de éxito desde sesión
    if (isset($_SESSION['curso_success'])): 
        $mensaje_exito = $_SESSION['curso_success'];
        unset($_SESSION['curso_success']);
    endif;
    ?>
    
    <!-- Formulario para crear/editar curso (oculto por defecto) -->
    <div id="formCurso" class="bg-gray-100 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 hidden">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nuevo Curso</h2>
        <form method="POST" action="index.php?page=cursos">
            <input type="hidden" id="id" name="id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mb-4">
                <div>
                    <label for="codigo" class="block text-gray-700 font-bold mb-2">Código *</label>
                    <input type="number" id="codigo" name="codigo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required min="1" step="1">
                </div>
                
                <div>
                    <label for="nombre" class="block text-gray-700 font-bold mb-2">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="programa_id" class="block text-gray-700 font-bold mb-2">Programa *</label>
                    <select id="programa_id" name="programa_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        <option value="">Seleccionar programa</option>
                        <?php foreach ($programas as $programa): ?>
                            <option value="<?php echo $programa['id']; ?>"><?php echo htmlspecialchars($programa['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
                <div>
                    <label for="profesor_id" class="block text-gray-700 font-bold mb-2">Profesor</label>
                    <select id="profesor_id" name="profesor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        <option value="">Seleccionar profesor</option>
                        <?php foreach ($profesores as $profesor): ?>
                            <option value="<?php echo $profesor['id']; ?>"><?php echo htmlspecialchars($profesor['nombre'] . ' (' . $profesor['username'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label for="area" class="block text-gray-700 font-bold mb-2">Área</label>
                    <input type="text" id="area" name="area" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label for="semestre" class="block text-gray-700 font-bold mb-2">Semestre</label>
                    <input type="number" id="semestre" name="semestre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" min="1" max="12" step="1">
                </div>
                
                <div>
                    <label for="grupo" class="block text-gray-700 font-bold mb-2">Grupo</label>
                    <input type="number" id="grupo" name="grupo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" min="1" step="1">
                </div>
                
                <div>
                    <label for="aula" class="block text-gray-700 font-bold mb-2">Aula</label>
                    <input type="number" id="aula" name="aula" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" min="1" step="1">
                </div>
                
                <div>
                    <label for="sede" class="block text-gray-700 font-bold mb-2">Sede</label>
                    <select id="sede" name="sede" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        <option value="KENNEDY">KENNEDY</option>
                        <option value="OTRA">OTRA</option>
                    </select>
                    <input type="text" id="otraSede" name="otraSede" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 mt-2 hidden" placeholder="Especifique otra sede">
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="btnCancelar" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Cancelar
                </button>
                <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Guardar
                </button>
            </div>
        </form>
    </div>
    
    <!-- Lista de cursos -->
    <div class="w-full">
        <?php if (empty($cursos)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 sm:px-4 py-3 rounded text-sm sm:text-base">
                No hay cursos registrados. Cree un nuevo curso para comenzar.
            </div>
        <?php else: ?>
            <!-- Vista móvil: Tarjetas -->
            <div class="block lg:hidden space-y-3">
                <?php foreach ($cursos as $curso): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-sm"><?= $curso['codigo'] ?></h3>
                                <p class="text-gray-600 text-sm mt-1"><?= $curso['nombre'] ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                            <div><span class="font-medium">Programa:</span> <?= substr($curso['programa_nombre'] ?? 'N/A', 0, 20) ?>...</div>
                            <div><span class="font-medium">Semestre:</span> <?= $curso['semestre'] ?></div>
                            <div><span class="font-medium">Grupo:</span> <?= $curso['grupo'] ?></div>
                            <div><span class="font-medium">Profesor:</span> <?= substr($curso['profesor_nombre'] ?? 'N/A', 0, 15) ?>...</div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button class="bg-yellow-500 hover:bg-yellow-700 text-white py-1 px-2 rounded text-xs btnEditar flex-1" 
                                    data-id="<?= $curso['id'] ?>"
                                    data-codigo="<?= $curso['codigo'] ?>"
                                    data-nombre="<?= $curso['nombre'] ?>"
                                    data-programa-id="<?= $curso['programa_id'] ?>"
                                    data-area="<?= $curso['area'] ?>"
                                    data-semestre="<?= $curso['semestre'] ?>"
                                    data-grupo="<?= $curso['grupo'] ?>"
                                    data-aula="<?= $curso['aula'] ?>"
                                    data-sede="<?= $curso['sede'] ?>"
                                    data-profesor-id="<?= $curso['profesor_id'] ?>">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                            <a href="index.php?page=cursos&delete=<?= $curso['id'] ?>" class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-xs text-center flex-1" onclick="return confirm('¿Está seguro de eliminar este curso?')">
                                <i class="fas fa-trash-alt mr-1"></i> Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Vista desktop: Tabla -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">Código</th>
                            <th class="py-2 px-4 border-b text-left">Nombre</th>
                            <th class="py-2 px-4 border-b text-left">Programa</th>
                            <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
                            <th class="py-2 px-4 border-b text-left">Profesor</th>
                            <?php endif; ?>
                            <th class="py-2 px-4 border-b text-left">Semestre</th>
                            <th class="py-2 px-4 border-b text-left">Grupo</th>
                            <th class="py-2 px-4 border-b text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?= $curso['codigo'] ?></td>
                                <td class="py-2 px-4 border-b"><?= $curso['nombre'] ?></td>
                                <td class="py-2 px-4 border-b"><?= $curso['programa_nombre'] ?? 'N/A' ?></td>
                                <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
                                <td class="py-2 px-4 border-b"><?= $curso['profesor_nombre'] ?? 'Sin asignar' ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4 border-b"><?= $curso['semestre'] ?></td>
                                <td class="py-2 px-4 border-b"><?= $curso['grupo'] ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button class="bg-yellow-500 hover:bg-yellow-700 text-white py-1 px-2 rounded text-sm mr-1 btnEditar" 
                                            data-id="<?= $curso['id'] ?>"
                                            data-codigo="<?= $curso['codigo'] ?>"
                                            data-nombre="<?= $curso['nombre'] ?>"
                                            data-programa-id="<?= $curso['programa_id'] ?>"
                                            data-area="<?= $curso['area'] ?>"
                                            data-semestre="<?= $curso['semestre'] ?>"
                                            data-grupo="<?= $curso['grupo'] ?>"
                                            data-aula="<?= $curso['aula'] ?>"
                                            data-sede="<?= $curso['sede'] ?>"
                                            data-profesor-id="<?= $curso['profesor_id'] ?>">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                    <a href="index.php?page=cursos&delete=<?= $curso['id'] ?>" class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-sm" onclick="return confirm('¿Está seguro de eliminar este curso?')">
                                        <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Optimizaciones adicionales para pantallas muy pequeñas */
@media (max-width: 480px) {
    .grid {
        gap: 0.5rem;
    }
    
    .p-4 {
        padding: 0.75rem;
    }
    
    .p-3 {
        padding: 0.5rem;
    }
    
    .px-3, .px-4 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .py-3 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    
    .mb-4, .mb-3 {
        margin-bottom: 0.75rem;
    }
    
    .rounded-lg {
        border-radius: 0.5rem;
    }
    
    .text-sm {
        font-size: 0.75rem;
    }
    
    .space-y-3 > * + * {
        margin-top: 0.5rem;
    }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formCurso = document.getElementById('formCurso');
        const btnNuevoCurso = document.getElementById('btnNuevoCurso');
        const btnCancelar = document.getElementById('btnCancelar');
        const formTitle = document.getElementById('formTitle');
        const idInput = document.getElementById('id');
        const codigoInput = document.getElementById('codigo');
        const nombreInput = document.getElementById('nombre');
        const programaInput = document.getElementById('programa_id');
        const profesorInput = document.getElementById('profesor_id');
        const areaInput = document.getElementById('area');
        const semestreInput = document.getElementById('semestre');
        const grupoInput = document.getElementById('grupo');
        const aulaInput = document.getElementById('aula');
        const sedeInput = document.getElementById('sede');
        const sedeSelect = document.getElementById('sede');
        const otraSedeInput = document.getElementById('otraSede');
        sedeSelect.addEventListener('change', function() {
            if (this.value === 'OTRA') {
                otraSedeInput.classList.remove('hidden');
                otraSedeInput.required = true;
            } else {
                otraSedeInput.classList.add('hidden');
                otraSedeInput.required = false;
                otraSedeInput.value = '';
            }
        });
        
        // Mostrar formulario para nuevo curso
        btnNuevoCurso.addEventListener('click', function() {
            formTitle.textContent = 'Nuevo Curso';
            idInput.value = '';
            codigoInput.value = '';
            nombreInput.value = '';
            programaInput.value = '';
            if (profesorInput) profesorInput.value = '';
            areaInput.value = '';
            semestreInput.value = '';
            grupoInput.value = '';
            aulaInput.value = '';
            sedeInput.value = '';
            formCurso.classList.remove('hidden');
        });
        
        // Ocultar formulario
        btnCancelar.addEventListener('click', function() {
            formCurso.classList.add('hidden');
        });
        
        // Editar curso
        document.querySelectorAll('.btnEditar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                formTitle.textContent = 'Editar Curso';
                idInput.value = this.dataset.id;
                codigoInput.value = this.dataset.codigo;
                nombreInput.value = this.dataset.nombre;
                programaInput.value = this.dataset.programaId;
                if (profesorInput) profesorInput.value = this.dataset.profesorId;
                areaInput.value = this.dataset.area;
                semestreInput.value = this.dataset.semestre;
                grupoInput.value = this.dataset.grupo;
                aulaInput.value = this.dataset.aula;
                sedeInput.value = this.dataset.sede;
                formCurso.classList.remove('hidden');
            });
        });
        
        // Mostrar SweetAlert si hay mensaje de éxito
        <?php if (isset($mensaje_exito)): ?>
        Swal.fire({
            title: '¡Éxito!',
            text: '<?= $mensaje_exito ?>',
            icon: 'success',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#1e40af'
        });
        <?php endif; ?>
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>