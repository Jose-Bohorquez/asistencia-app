<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestión de Sesiones</h1>
        <button id="btnNuevaSesion" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-1"></i> Nueva Sesión
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
    
    <!-- Formulario para crear/editar sesión (oculto por defecto) -->
    <div id="formSesion" class="bg-gray-100 p-4 rounded-lg mb-6 hidden">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nueva Sesión</h2>
        <form method="POST" action="index.php?page=sesiones">
            <input type="hidden" id="id" name="id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="curso_id" class="block text-gray-700 font-bold mb-2">Curso *</label>
                    <select id="curso_id" name="curso_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        <option value="">Seleccione un curso</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso['id'] ?>"><?= $curso['codigo'] ?> - <?= $curso['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="fecha" class="block text-gray-700 font-bold mb-2">Fecha *</label>
                    <input type="date" id="fecha" name="fecha" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="hora_inicio" class="block text-gray-700 font-bold mb-2">Hora Inicio *</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="estado" class="block text-gray-700 font-bold mb-2">Estado</label>
                    <select id="estado" name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        <option value="activa">Activa</option>
                        <option value="finalizada">Finalizada</option>
                        <option value="cancelada">Cancelada</option>
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
    
    <!-- Lista de sesiones -->
    <div class="overflow-x-auto">
        <?php if (empty($sesiones)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                No hay sesiones registradas. Cree una nueva sesión para comenzar.
            </div>
        <?php else: ?>
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b text-left">Curso</th>
                        <th class="py-2 px-4 border-b text-left">Fecha</th>
                        <th class="py-2 px-4 border-b text-left">Hora Inicio</th>
                        <th class="py-2 px-4 border-b text-left">Hora Fin</th>
                        <th class="py-2 px-4 border-b text-left">Estado</th>
                        <th class="py-2 px-4 border-b text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sesiones as $sesion): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?= $sesion['curso_nombre'] ?></td>
                            <td class="py-2 px-4 border-b"><?= date('d/m/Y', strtotime($sesion['fecha'])) ?></td>
                            <td class="py-2 px-4 border-b"><?= date('H:i', strtotime($sesion['hora_inicio'])) ?></td>
                            <td class="py-2 px-4 border-b"><?= $sesion['hora_fin'] ? date('H:i', strtotime($sesion['hora_fin'])) : '-' ?></td>
                            <td class="py-2 px-4 border-b">
                                <?php if ($sesion['estado'] === 'activa'): ?>
                                    <span class="bg-green-100 text-green-800 py-1 px-2 rounded-full text-xs">Activa</span>
                                <?php elseif ($sesion['estado'] === 'finalizada'): ?>
                                    <span class="bg-blue-100 text-blue-800 py-1 px-2 rounded-full text-xs">Finalizada</span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-800 py-1 px-2 rounded-full text-xs">Cancelada</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border-b text-center">
                                <?php if (!empty($sesion['token'])): ?>
                                    <a href="index.php?page=asistencia&token=<?= $sesion['token'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded text-sm" target="_blank">
                                        <i class="fas fa-link mr-1"></i> Enlace público
                                    </a>
                                <?php endif; ?>
                                <?php if ($sesion['estado'] === 'activa'): ?>
                                    <a href="index.php?page=asistencia&sesion_id=<?= $sesion['id'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded text-sm mr-1" target="_blank">
                                        <i class="fas fa-link mr-1"></i> Enlace
                                    </a>
                                    <a href="index.php?page=sesiones&deactivate=<?= $sesion['id'] ?>" class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-sm mr-1" onclick="return confirm('¿Está seguro de finalizar esta sesión?')">
                                        <i class="fas fa-stop-circle mr-1"></i> Finalizar
                                    </a>
                                <?php elseif ($sesion['estado'] === 'finalizada'): ?>
                                    <a href="index.php?page=exportar&sesion_id=<?= $sesion['id'] ?>" class="bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded text-sm mr-1">
                                        <i class="fas fa-file-export mr-1"></i> Exportar
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($sesion['estado'] !== 'activa'): ?>
                                    <a href="index.php?page=sesiones&activate=<?= $sesion['id'] ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white py-1 px-2 rounded text-sm" onclick="return confirm('¿Está seguro de activar esta sesión?')">
                                        <i class="fas fa-play-circle mr-1"></i> Activar
                                    </a>
                                <?php endif; ?>
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
        const formSesion = document.getElementById('formSesion');
        const btnNuevaSesion = document.getElementById('btnNuevaSesion');
        const btnCancelar = document.getElementById('btnCancelar');
        const formTitle = document.getElementById('formTitle');
        const idInput = document.getElementById('id');
        const cursoIdSelect = document.getElementById('curso_id');
        const fechaInput = document.getElementById('fecha');
        const horaInicioInput = document.getElementById('hora_inicio');
        const estadoSelect = document.getElementById('estado');
        
        // Mostrar formulario para nueva sesión
        btnNuevaSesion.addEventListener('click', function() {
            formTitle.textContent = 'Nueva Sesión';
            idInput.value = '';
            cursoIdSelect.value = '';
            fechaInput.value = new Date().toISOString().split('T')[0];
            horaInicioInput.value = '';
            estadoSelect.value = 'activa';
            formSesion.classList.remove('hidden');
        });
        
        // Ocultar formulario
        btnCancelar.addEventListener('click', function() {
            formSesion.classList.add('hidden');
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>
