<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Dashboard</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-blue-100 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <div class="bg-blue-800 text-white p-3 rounded-full">
                    <i class="fas fa-book text-xl"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600">Total Cursos</h2>
                    <p class="text-2xl font-bold"><?= $totalCursos ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-green-100 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <div class="bg-green-800 text-white p-3 rounded-full">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600">Total Sesiones</h2>
                    <p class="text-2xl font-bold"><?= $totalSesiones ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-purple-100 p-4 rounded-lg shadow">
            <div class="flex items-center">
                <div class="bg-purple-800 text-white p-3 rounded-full">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-gray-600">Total Estudiantes</h2>
                    <p class="text-2xl font-bold"><?= $totalEstudiantes ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Sesiones Activas</h2>
        
        <?php if (empty($sesionesActivas)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                No hay sesiones activas en este momento.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">Curso</th>
                            <th class="py-2 px-4 border-b text-left">Fecha</th>
                            <th class="py-2 px-4 border-b text-left">Hora Inicio</th>
                            <th class="py-2 px-4 border-b text-left">Programa</th>
                            <th class="py-2 px-4 border-b text-left">Grupo</th>
                            <th class="py-2 px-4 border-b text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sesionesActivas as $sesion): ?>
                            <tr>
                                <td class="py-2 px-4 border-b"><?= $sesion['curso_nombre'] ?></td>
                                <td class="py-2 px-4 border-b"><?= date('d/m/Y', strtotime($sesion['fecha'])) ?></td>
                                <td class="py-2 px-4 border-b"><?= date('H:i', strtotime($sesion['hora_inicio'])) ?></td>
                                <td class="py-2 px-4 border-b"><?= $sesion['programa'] ?></td>
                                <td class="py-2 px-4 border-b"><?= $sesion['grupo'] ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <a href="index.php?page=asistencia&sesion_id=<?= $sesion['id'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded text-sm mr-1" target="_blank">
                                        <i class="fas fa-link mr-1"></i> Enlace
                                    </a>
                                    <a href="index.php?page=exportar&sesion_id=<?= $sesion['id'] ?>" class="bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded text-sm mr-1">
                                        <i class="fas fa-file-export mr-1"></i> Exportar
                                    </a>
                                    <a href="index.php?page=sesiones&deactivate=<?= $sesion['id'] ?>" class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-sm" onclick="return confirm('¿Está seguro de finalizar esta sesión?')">
                                        <i class="fas fa-stop-circle mr-1"></i> Finalizar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="flex justify-end">
        <a href="index.php?page=sesiones" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-1"></i> Nueva Sesión
        </a>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>