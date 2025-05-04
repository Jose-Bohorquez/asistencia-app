<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestión de Cursos</h1>
        <button id="btnNuevoCurso" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
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
    
    <!-- Formulario para crear/editar curso (oculto por defecto) -->
    <div id="formCurso" class="bg-gray-100 p-4 rounded-lg mb-6 hidden">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nuevo Curso</h2>
        <form method="POST" action="index.php?page=cursos">
            <input type="hidden" id="id" name="id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="codigo" class="block text-gray-700 font-bold mb-2">Código *</label>
                    <input type="text" id="codigo" name="codigo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="nombre" class="block text-gray-700 font-bold mb-2">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="programa" class="block text-gray-700 font-bold mb-2">Programa *</label>
                    <select id="programa" name="programa" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        <option value="PREGRADO INGENIERIA DE SISTEMAS">PREGRADO INGENIERIA DE SISTEMAS</option>
                    </select>
                </div>
                
                <div>
                    <label for="area" class="block text-gray-700 font-bold mb-2">Área</label>
                    <input type="text" id="area" name="area" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label for="semestre" class="block text-gray-700 font-bold mb-2">Semestre</label>
                    <input type="text" id="semestre" name="semestre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label for="grupo" class="block text-gray-700 font-bold mb-2">Grupo</label>
                    <input type="text" id="grupo" name="grupo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label for="aula" class="block text-gray-700 font-bold mb-2">Aula</label>
                    <input type="text" id="aula" name="aula" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
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
    <div class="overflow-x-auto w-full">
        <?php if (empty($cursos)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                No hay cursos registrados. Cree un nuevo curso para comenzar.
            </div>
        <?php else: ?>
            <table class="min-w-full bg-white block md:table">
                <thead class="bg-gray-100 block md:table-header-group">
                    <tr class="block md:table-row">
                        <th class="py-2 px-4 border-b text-left block md:table-cell">Código</th>
                        <th class="py-2 px-4 border-b text-left block md:table-cell">Nombre</th>
                        <th class="py-2 px-4 border-b text-left block md:table-cell">Programa</th>
                        <th class="py-2 px-4 border-b text-left block md:table-cell">Semestre</th>
                        <th class="py-2 px-4 border-b text-left block md:table-cell">Grupo</th>
                        <th class="py-2 px-4 border-b text-center block md:table-cell">Acciones</th>
                    </tr>
                </thead>
                <tbody class="block md:table-row-group">
                    <?php foreach ($cursos as $curso): ?>
                        <tr class="block md:table-row">
                            <td class="py-2 px-4 border-b block md:table-cell"><?= $curso['codigo'] ?></td>
                            <td class="py-2 px-4 border-b block md:table-cell"><?= $curso['nombre'] ?></td>
                            <td class="py-2 px-4 border-b block md:table-cell"><?= $curso['programa'] ?></td>
                            <td class="py-2 px-4 border-b block md:table-cell"><?= $curso['semestre'] ?></td>
                            <td class="py-2 px-4 border-b block md:table-cell"><?= $curso['grupo'] ?></td>
                            <td class="py-2 px-4 border-b text-center block md:table-cell">
                                <button class="bg-yellow-500 hover:bg-yellow-700 text-white py-1 px-2 rounded text-sm mr-1 btnEditar" 
                                        data-id="<?= $curso['id'] ?>"
                                        data-codigo="<?= $curso['codigo'] ?>"
                                        data-nombre="<?= $curso['nombre'] ?>"
                                        data-programa="<?= $curso['programa'] ?>"
                                        data-area="<?= $curso['area'] ?>"
                                        data-semestre="<?= $curso['semestre'] ?>"
                                        data-grupo="<?= $curso['grupo'] ?>"
                                        data-aula="<?= $curso['aula'] ?>"
                                        data-sede="<?= $curso['sede'] ?>">
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
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formCurso = document.getElementById('formCurso');
        const btnNuevoCurso = document.getElementById('btnNuevoCurso');
        const btnCancelar = document.getElementById('btnCancelar');
        const formTitle = document.getElementById('formTitle');
        const idInput = document.getElementById('id');
        const codigoInput = document.getElementById('codigo');
        const nombreInput = document.getElementById('nombre');
        const programaInput = document.getElementById('programa');
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
                programaInput.value = this.dataset.programa;
                areaInput.value = this.dataset.area;
                semestreInput.value = this.dataset.semestre;
                grupoInput.value = this.dataset.grupo;
                aulaInput.value = this.dataset.aula;
                sedeInput.value = this.dataset.sede;
                formCurso.classList.remove('hidden');
            });
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>