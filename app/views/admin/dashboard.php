<?php 
// Incluir componentes necesarios
require_once '../app/views/components/card.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/table.php';
require_once '../app/views/components/alert.php';

// Configurar el layout base
$pageTitle = 'Dashboard - ' . APP_NAME;
$pageDescription = 'Panel de administración del sistema de asistencia';
$customCSS = [];
$customJS = [];

ob_start();
?>

<!-- Header del Dashboard -->
<?= renderCard([
    'title' => 'Dashboard',
    'content' => '
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="mb-4 sm:mb-0">
                <p class="text-blue-100">Bienvenido, ' . htmlspecialchars($_SESSION['nombre']) . '</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                ' . renderButton([
                    'text' => 'Gestionar Cursos',
                    'type' => 'secondary',
                    'icon' => 'fas fa-book',
                    'href' => 'index.php?page=cursos',
                    'extraClasses' => 'bg-white bg-opacity-20 hover:bg-opacity-30 text-white border-white border-opacity-30'
                ]) . '
                ' . renderButton([
                    'text' => 'Nueva Sesión',
                    'type' => 'secondary', 
                    'icon' => 'fas fa-plus',
                    'href' => 'index.php?page=sesiones',
                    'extraClasses' => 'bg-white bg-opacity-20 hover:bg-opacity-30 text-white border-white border-opacity-30'
                ]) . '
            </div>
        </div>
    ',
    'color' => 'blue',
    'gradient' => true,
    'extraClasses' => 'mb-4 sm:mb-8 text-white'
]) ?>

<!-- Tarjetas de Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-4 sm:mb-8">
    <?= renderStatCard([
        'title' => 'Total Cursos',
        'value' => $totalCursos,
        'icon' => 'fas fa-book',
        'color' => 'blue',
        'subtitle' => 'Activos en el sistema',
        'trend' => 'up'
    ]) ?>
    
    <?= renderStatCard([
        'title' => 'Total Sesiones', 
        'value' => $totalSesiones,
        'icon' => 'fas fa-calendar-alt',
        'color' => 'green',
        'subtitle' => count($sesionesActivas) . ' activas en este momento',
        'trend' => 'neutral'
    ]) ?>
    
    <div class="sm:col-span-2 lg:col-span-1">
        <?= renderStatCard([
            'title' => 'Total Estudiantes',
            'value' => $totalEstudiantes,
            'icon' => 'fas fa-users',
            'color' => 'purple',
            'subtitle' => 'Registrados en el sistema',
            'trend' => 'up'
        ]) ?>
    </div>
</div>
    
<!-- Sección de Sesiones Activas -->
<?php
$sesionesTableHeaders = [
    'curso_nombre' => ['label' => 'Curso', 'field' => 'curso_nombre'],
    'fecha' => ['label' => 'Fecha & Hora', 'field' => 'fecha', 'format' => 'datetime'],
    'programa' => ['label' => 'Programa', 'field' => 'programa'],
    'grupo' => ['label' => 'Grupo', 'field' => 'grupo', 'format' => 'badge', 'badgeType' => 'primary']
];

$sesionesTableActions = [
    [
        'text' => 'Enlace',
        'icon' => 'fas fa-external-link-alt',
        'type' => 'primary',
        'url' => 'index.php?page=asistencia&sesion_id={id}',
        'class' => 'target="_blank"'
    ],
    [
        'text' => 'Exportar',
        'icon' => 'fas fa-download', 
        'type' => 'success',
        'url' => 'index.php?page=exportar&sesion_id={id}'
    ],
    [
        'text' => 'Imprimir',
        'icon' => 'fas fa-print',
        'type' => 'info',
        'url' => 'index.php?page=exportar&sesion_id={id}&format=print',
        'class' => 'target="_blank"'
    ],
    [
        'text' => 'Finalizar',
        'icon' => 'fas fa-stop',
        'type' => 'danger',
        'url' => 'index.php?page=sesiones&deactivate={id}',
        'onclick' => 'return confirm("¿Está seguro de finalizar esta sesión?")'
    ]
];
?>

<?= renderCard([
    'title' => '<i class="fas fa-clock text-blue-600 mr-2"></i>Sesiones Activas',
    'titleExtra' => renderBadge(['text' => count($sesionesActivas) . ' activa(s)', 'type' => 'success']),
    'content' => renderTable([
        'headers' => $sesionesTableHeaders,
        'data' => $sesionesActivas,
        'actions' => $sesionesTableActions,
        'searchable' => true,
        'responsive' => true,
        'emptyMessage' => 'No hay sesiones activas. <a href="index.php?page=sesiones" class="text-blue-600 hover:text-blue-800">Crear Nueva Sesión</a>'
    ]),
    'extraClasses' => 'overflow-hidden'
]) ?>


<?php
$content = ob_get_clean();

// CSS personalizado para el dashboard
$customCSS[] = '
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
    .hover\\:shadow-xl:hover {
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
.floating-btn-container {
    bottom: 6rem;
    pointer-events: none;
}

.floating-btn {
    pointer-events: auto;
}

@media (max-width: 640px) {
    .floating-btn-container {
        bottom: 5rem !important;
        right: 1rem !important;
    }
    
    .floating-btn {
        width: 3rem !important;
        height: 3rem !important;
    }
    
    .floating-btn i {
        font-size: 1rem !important;
    }
    
    .tooltip-floating {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .floating-btn {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }
}
';

// JavaScript personalizado para el dashboard
$customJS[] = '
// Funcionalidad del botón flotante
document.addEventListener("DOMContentLoaded", function() {
    const floatingBtn = document.querySelector(".floating-btn");
    const tooltip = document.querySelector(".tooltip-floating");
    
    if (floatingBtn && tooltip) {
        floatingBtn.addEventListener("mouseenter", function() {
            tooltip.classList.remove("opacity-0");
            tooltip.classList.add("opacity-100");
        });
        
        floatingBtn.addEventListener("mouseleave", function() {
            tooltip.classList.remove("opacity-100");
            tooltip.classList.add("opacity-0");
        });
    }
});
';

// Botón flotante
$floatingButton = renderFloatingActionButton([
    'href' => 'index.php?page=sesiones',
    'icon' => 'fas fa-plus',
    'tooltip' => 'Crear Nueva Sesión',
    'color' => 'blue'
]);

// Incluir el layout base
require_once '../app/views/layouts/base.php';
?>