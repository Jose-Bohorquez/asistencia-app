<?php include '../app/views/layouts/header.php'; ?>

<!-- Header del Dashboard -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-8 text-white">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-3xl font-bold mb-2">Dashboard</h1>
            <p class="text-blue-100">Bienvenido, <?= $_SESSION['nombre'] ?></p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="index.php?page=cursos" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-200 text-center">
                <i class="fas fa-book mr-2"></i>Gestionar Cursos
            </a>
            <a href="index.php?page=sesiones" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-200 text-center">
                <i class="fas fa-plus mr-2"></i>Nueva Sesión
            </a>
        </div>
    </div>
</div>

<!-- Tarjetas de Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-4 sm:mb-8">
    <!-- Tarjeta Cursos -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Cursos</p>
                    <p class="text-white text-3xl font-bold"><?= $totalCursos ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <i class="fas fa-book text-white text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                <span class="text-green-500 font-medium">Activos</span>
                <span class="ml-2">en el sistema</span>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta Sesiones -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-green-600 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Sesiones</p>
                    <p class="text-white text-3xl font-bold"><?= $totalSesiones ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <i class="fas fa-calendar-alt text-white text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-clock text-blue-500 mr-1"></i>
                <span class="text-blue-500 font-medium"><?= count($sesionesActivas) ?> activas</span>
                <span class="ml-2">en este momento</span>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta Estudiantes -->
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden sm:col-span-2 lg:col-span-1">
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Estudiantes</p>
                    <p class="text-white text-3xl font-bold"><?= $totalEstudiantes ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-user-check text-purple-500 mr-1"></i>
                <span class="text-purple-500 font-medium">Registrados</span>
                <span class="ml-2">en el sistema</span>
            </div>
        </div>
    </div>
</div>
    
<!-- Sección de Sesiones Activas -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-bold text-gray-800 mb-2 sm:mb-0">
                <i class="fas fa-clock text-blue-600 mr-2"></i>Sesiones Activas
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <?= count($sesionesActivas) ?> activa(s)
            </span>
        </div>
    </div>
    
    <div class="p-4 sm:p-6">
        <?php if (empty($sesionesActivas)): ?>
            <div class="text-center py-12">
                <div class="mx-auto h-24 w-24 text-gray-400 mb-4">
                    <i class="fas fa-calendar-times text-6xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay sesiones activas</h3>
                <p class="text-gray-500 mb-6">Crea una nueva sesión para comenzar a registrar asistencia</p>
                <a href="index.php?page=sesiones" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200">
                    <i class="fas fa-plus mr-2"></i>Crear Nueva Sesión
                </a>
            </div>
        <?php else: ?>
            <!-- Vista Desktop: Tabla -->
            <div class="hidden lg:block">
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha & Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grupo</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($sesionesActivas as $sesion): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= $sesion['curso_nombre'] ?></div>
                                        <div class="text-sm text-gray-500"><?= $sesion['area'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= date('d/m/Y', strtotime($sesion['fecha'])) ?></div>
                                        <div class="text-sm text-gray-500"><?= date('H:i', strtotime($sesion['hora_inicio'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $sesion['programa'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= $sesion['grupo'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center space-x-2">
                                            <a href="index.php?page=asistencia&sesion_id=<?= $sesion['id'] ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition duration-200" 
                                               target="_blank" title="Abrir enlace de asistencia">
                                                <i class="fas fa-external-link-alt mr-1"></i>Enlace
                                            </a>
                                            <a href="index.php?page=exportar&sesion_id=<?= $sesion['id'] ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-md transition duration-200" 
                                               title="Exportar asistencia">
                                                <i class="fas fa-download mr-1"></i>Exportar
                                            </a>
                                            <a href="index.php?page=exportar&sesion_id=<?= $sesion['id'] ?>&format=print" 
                                               class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-md transition duration-200" 
                                               target="_blank" title="Imprimir asistencia">
                                                <i class="fas fa-print mr-1"></i>Imprimir
                                            </a>
                                            <a href="index.php?page=sesiones&deactivate=<?= $sesion['id'] ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-md transition duration-200" 
                                               onclick="return confirm('¿Está seguro de finalizar esta sesión?')" 
                                               title="Finalizar sesión">
                                                <i class="fas fa-stop mr-1"></i>Finalizar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Vista Mobile/Tablet: Tarjetas -->
            <div class="lg:hidden space-y-4">
                <?php foreach ($sesionesActivas as $sesion): ?>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-lg"><?= $sesion['curso_nombre'] ?></h3>
                                <p class="text-sm text-gray-600"><?= $sesion['programa'] ?> - <?= $sesion['area'] ?></p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?= $sesion['grupo'] ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Fecha</p>
                                <p class="text-sm font-medium text-gray-900"><?= date('d/m/Y', strtotime($sesion['fecha'])) ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Hora</p>
                                <p class="text-sm font-medium text-gray-900"><?= date('H:i', strtotime($sesion['hora_inicio'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <a href="index.php?page=asistencia&sesion_id=<?= $sesion['id'] ?>" 
                                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-200" 
                                   target="_blank">
                                    <i class="fas fa-external-link-alt mr-2"></i>Enlace
                                </a>
                                <a href="index.php?page=exportar&sesion_id=<?= $sesion['id'] ?>" 
                                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition duration-200">
                                    <i class="fas fa-download mr-2"></i>Exportar
                                </a>
                                <a href="index.php?page=exportar&sesion_id=<?= $sesion['id'] ?>&format=print" 
                                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-200" 
                                   target="_blank">
                                    <i class="fas fa-print mr-2"></i>Imprimir
                                </a>
                            </div>
                            <a href="index.php?page=sesiones&deactivate=<?= $sesion['id'] ?>" 
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition duration-200" 
                               onclick="return confirm('¿Está seguro de finalizar esta sesión?')">
                                <i class="fas fa-stop mr-2"></i>Finalizar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estilos adicionales para mejorar la responsividad -->
<style>
/* Animaciones suaves */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > div {
    animation: slideInUp 0.6s ease-out;
}

/* Mejoras para dispositivos táctiles */
@media (hover: none) {
    .hover\:shadow-xl:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
}

/* Optimización para pantallas muy pequeñas */
@media (max-width: 375px) {
    .grid {
        gap: 0.75rem;
    }
    
    .p-6, .p-4 {
        padding: 0.75rem;
    }
    
    .px-6, .px-4 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .py-4, .py-3 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    
    .mb-8, .mb-4 {
        margin-bottom: 0.75rem;
    }
    
    .rounded-xl {
        border-radius: 0.5rem;
    }
}

/* Mejoras de accesibilidad */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Ajustes para el botón flotante */
.main-container {
    padding-bottom: 20px; /* Espacio reducido */
}

/* Posicionamiento del botón flotante */
.floating-btn-container {
    bottom: 6rem; /* Espacio para el footer */
}

@media (max-width: 640px) {
    /* Ajustes específicos para móvil */
    .floating-btn-container {
        bottom: 5rem !important; /* Espacio para el footer */
        right: 1rem !important;
    }
    
    .floating-btn {
        width: 3rem !important;
        height: 3rem !important;
    }
    
    .floating-btn i {
        font-size: 1rem !important;
    }
    
    /* Reducir padding del contenedor en móvil */
    .main-container {
        padding-bottom: 20px !important;
    }
    
    /* Ocultar tooltip en móvil */
    .tooltip-floating {
        display: none !important;
    }
}

/* Asegurar que el botón no interfiera con el scroll */
.floating-btn-container {
    pointer-events: none;
}

.floating-btn {
    pointer-events: auto;
}

/* Mejorar la visibilidad del botón */
@media (max-width: 768px) {
    .floating-btn {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }
}
</style>

<?php include '../app/views/layouts/footer.php'; ?>

<!-- Botón flotante para nueva sesión -->
<div class="fixed right-6 z-50 floating-btn-container">
    <a href="index.php?page=sesiones" 
       class="floating-btn inline-flex items-center justify-center w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
        <i class="fas fa-plus text-xl"></i>
        <span class="sr-only">Crear Nueva Sesión</span>
    </a>
</div>

<!-- Tooltip para el botón flotante -->
<div class="fixed bottom-20 right-6 z-40 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none tooltip-floating hidden sm:block">
    <div class="bg-gray-800 text-white text-sm px-3 py-2 rounded-lg shadow-lg whitespace-nowrap">
        Crear Nueva Sesión
        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>
    </div>
</div>