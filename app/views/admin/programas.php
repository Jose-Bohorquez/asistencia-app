<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Gestión de Programas Académicos</h1>
        <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
        <button id="btnNuevoPrograma" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-3 sm:px-4 rounded text-sm sm:text-base w-full sm:w-auto">
            <i class="fas fa-plus mr-1"></i> Nuevo Programa
        </button>
        <?php endif; ?>
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
    if (isset($_SESSION['programa_success'])): 
        $mensaje_exito = $_SESSION['programa_success'];
        unset($_SESSION['programa_success']);
    endif;
    ?>
    
    <!-- Formulario para crear/editar programa (oculto por defecto) -->
    <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
    <div id="formPrograma" class="bg-gray-100 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 hidden">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nuevo Programa</h2>
        <form method="POST" action="index.php?page=programas">
            <input type="hidden" id="id" name="id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mb-4">
                <div>
                    <label for="codigo" class="block text-gray-700 font-bold mb-2">Código *</label>
                    <input type="text" id="codigo" name="codigo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required maxlength="20">
                </div>
                
                <div>
                    <label for="nombre" class="block text-gray-700 font-bold mb-2">Nombre del Programa *</label>
                    <input type="text" id="nombre" name="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required maxlength="150">
                </div>
                
                <div>
                    <label for="activo" class="block text-gray-700 font-bold mb-2">Estado</label>
                    <select id="activo" name="activo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
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
    <?php endif; ?>
    
    <!-- Lista de programas -->
    <div class="w-full">
        <?php if (empty($programas)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 sm:px-4 py-3 rounded text-sm sm:text-base">
                No hay programas registrados.
            </div>
        <?php else: ?>
            <!-- Vista móvil: Tarjetas -->
            <div class="block lg:hidden space-y-3">
                <?php foreach ($programas as $programa): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-sm"><?= $programa['codigo'] ?></h3>
                                <p class="text-gray-600 text-sm mt-1"><?= $programa['nombre'] ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?= $programa['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $programa['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
                        <div class="flex gap-2">
                            <button class="btnEditar bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs"
                                    data-id="<?= $programa['id'] ?>"
                                    data-codigo="<?= $programa['codigo'] ?>"
                                    data-nombre="<?= $programa['nombre'] ?>"
                                    data-activo="<?= $programa['activo'] ?>">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <a href="index.php?page=programas&delete=<?= $programa['id'] ?>" 
                               class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs"
                               onclick="return confirm('¿Está seguro de eliminar este programa? Esto puede afectar los cursos asociados.')">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Vista desktop: Tabla -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre del Programa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                            <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($programas as $programa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $programa['codigo'] ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= $programa['nombre'] ?></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full <?= $programa['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $programa['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d/m/Y', strtotime($programa['created_at'])) ?></td>
                                <?php if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])): ?>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button class="btnEditar bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                                                data-id="<?= $programa['id'] ?>"
                                                data-codigo="<?= $programa['codigo'] ?>"
                                                data-nombre="<?= $programa['nombre'] ?>"
                                                data-activo="<?= $programa['activo'] ?>">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <a href="index.php?page=programas&delete=<?= $programa['id'] ?>" 
                                           class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                                           onclick="return confirm('¿Está seguro de eliminar este programa? Esto puede afectar los cursos asociados.')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnNuevoPrograma = document.getElementById('btnNuevoPrograma');
        const btnCancelar = document.getElementById('btnCancelar');
        const formPrograma = document.getElementById('formPrograma');
        const formTitle = document.getElementById('formTitle');
        const idInput = document.getElementById('id');
        const codigoInput = document.getElementById('codigo');
        const nombreInput = document.getElementById('nombre');
        const activoInput = document.getElementById('activo');
        
        if (btnNuevoPrograma) {
            btnNuevoPrograma.addEventListener('click', function() {
                formTitle.textContent = 'Nuevo Programa';
                idInput.value = '';
                codigoInput.value = '';
                nombreInput.value = '';
                activoInput.value = '1';
                formPrograma.classList.remove('hidden');
            });
        }
        
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                formPrograma.classList.add('hidden');
            });
        }
        
        document.querySelectorAll('.btnEditar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                formTitle.textContent = 'Editar Programa';
                idInput.value = this.dataset.id;
                codigoInput.value = this.dataset.codigo;
                nombreInput.value = this.dataset.nombre;
                activoInput.value = this.dataset.activo;
                formPrograma.classList.remove('hidden');
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