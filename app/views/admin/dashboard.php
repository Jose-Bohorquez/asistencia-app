<?php
require_once '../app/views/components/button.php';

$pageTitle       = 'Dashboard - ' . APP_NAME;
$pageDescription = 'Panel de administración del sistema de asistencia';

ob_start();
?>

<!-- Cabecera limpia -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            Hola, <?= htmlspecialchars(explode(' ', $_SESSION['nombre'])[0]) ?>
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">Panel de administración del sistema de asistencia</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="index.php?page=cursos"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-colors">
            <i class="fas fa-book text-blue-600"></i>
            <span>Cursos</span>
        </a>
        <a href="index.php?page=sesiones"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
            <i class="fas fa-plus"></i>
            <span>Nueva Sesion</span>
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">
    <!-- Cursos -->
    <a href="index.php?page=cursos" class="block bg-white border border-gray-100 rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="fas fa-book text-blue-600 text-base"></i>
            </div>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cursos</span>
        </div>
        <p class="text-3xl font-bold text-gray-900"><?= (int)$totalCursos ?></p>
        <p class="text-xs text-gray-500 mt-1">Activos en el sistema</p>
    </a>

    <!-- Sesiones -->
    <a href="index.php?page=sesiones" class="block bg-white border border-gray-100 rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                <i class="fas fa-calendar-alt text-emerald-600 text-base"></i>
            </div>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sesiones</span>
        </div>
        <p class="text-3xl font-bold text-gray-900"><?= (int)$totalSesiones ?></p>
        <p class="text-xs text-gray-500 mt-1">
            <?= count($sesionesActivas) ?> activa<?= count($sesionesActivas) !== 1 ? 's' : '' ?> ahora
        </p>
    </a>

    <!-- Estudiantes -->
    <div class="col-span-2 lg:col-span-1">
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-4 h-full">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                    <i class="fas fa-users text-violet-600 text-base"></i>
                </div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Estudiantes</span>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= (int)$totalEstudiantes ?></p>
            <p class="text-xs text-gray-500 mt-1">Registrados en el sistema</p>
        </div>
    </div>
</div>

<!-- Sesiones activas ahora -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
    <!-- Header del bloque -->
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <?php if (count($sesionesActivas) > 0): ?>
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <?php else: ?>
            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-gray-300"></span>
            <?php endif; ?>
            <h2 class="text-base font-semibold text-gray-900">Sesiones activas</h2>
            <?php if (count($sesionesActivas) > 0): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                <?= count($sesionesActivas) ?>
            </span>
            <?php endif; ?>
        </div>
        <a href="index.php?page=sesiones"
           class="text-xs text-blue-600 hover:text-blue-700 font-medium">
            Ver todas &rarr;
        </a>
    </div>

    <?php if (empty($sesionesActivas)): ?>
    <!-- Estado vacío -->
    <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
            <i class="fas fa-calendar-plus text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-sm font-semibold text-gray-700 mb-1">No hay sesiones activas ahora</h3>
        <p class="text-xs text-gray-400 max-w-xs mb-5">
            Crea una nueva sesion para que los estudiantes puedan registrar su asistencia en tiempo real.
        </p>
        <a href="index.php?page=sesiones"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            Crear sesion
        </a>
    </div>

    <?php else: ?>
    <!-- Lista de sesiones activas -->
    <div class="divide-y divide-gray-50">
        <?php foreach ($sesionesActivas as $sa): ?>
        <?php
            $enlaceUrl = APP_URL . '/index.php?page=asistencia&token=' . urlencode($sa['token'] ?? '');
            $totalAsis = (int)($sa['total_asistencias'] ?? 0);
        ?>
        <div class="px-5 py-4 hover:bg-gray-50/60 transition-colors">
            <div class="flex items-start sm:items-center justify-between gap-4">
                <!-- Info de la sesion -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                            <?= htmlspecialchars($sa['curso_nombre'] ?? '') ?>
                        </p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100 shrink-0">
                            <i class="fas fa-user-check text-[10px]"></i>
                            <?= $totalAsis ?> asistente<?= $totalAsis !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-clock text-[10px] mr-1 opacity-60"></i>
                        <?= date('d/m/Y', strtotime($sa['fecha'])) ?>
                        &bull; <?= date('H:i', strtotime($sa['hora_inicio'])) ?>
                        <?php $aulaMostrar = $sa['aula_display'] ?? $sa['aula'] ?? ''; ?>
                        <?php if (!empty($aulaMostrar)): ?>
                        &bull; Aula <?= htmlspecialchars($aulaMostrar) ?>
                        <?php endif; ?>
                        <?php $sedeMostrar = $sa['sede_display'] ?? $sa['sede'] ?? ''; ?>
                        <?php if (!empty($sedeMostrar)): ?>
                        &bull; <?= htmlspecialchars($sedeMostrar) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <!-- Acciones -->
                <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                    <!-- Ver detalle -->
                    <a href="index.php?page=sesiones&action=detalle&sesion_id=<?= (int)$sa['id'] ?>"
                       title="Ver detalle en vivo"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-medium transition-colors border border-emerald-100">
                        <i class="fas fa-eye"></i>
                        <span class="hidden sm:inline">Ver sesion</span>
                    </a>

                    <?php if (!empty($sa['token'])): ?>
                    <!-- Copiar enlace -->
                    <button type="button"
                            onclick="copiarEnlace(this, '<?= htmlspecialchars($enlaceUrl, ENT_QUOTES) ?>')"
                            title="Copiar enlace de asistencia"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-medium transition-colors border border-indigo-100">
                        <i class="fas fa-copy"></i>
                        <span class="hidden sm:inline">Enlace</span>
                    </button>
                    <?php endif; ?>

                    <!-- Imprimir -->
                    <a href="index.php?page=sesiones&action=imprimir&sesion_id=<?= (int)$sa['id'] ?>"
                       target="_blank"
                       title="Vista previa / imprimir"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg text-xs font-medium transition-colors border border-gray-200">
                        <i class="fas fa-print"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.grid > * { animation: slideInUp 0.35s ease-out both; }
.grid > *:nth-child(1) { animation-delay: 0.05s; }
.grid > *:nth-child(2) { animation-delay: 0.10s; }
.grid > *:nth-child(3) { animation-delay: 0.15s; }
@media (prefers-reduced-motion: reduce) {
    * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
</style>';

$additionalJS = '<script>
function copiarEnlace(btn, url) {
    const original = btn.innerHTML;
    const ok = () => {
        btn.innerHTML = \'<i class="fas fa-check"></i> <span class="hidden sm:inline">Copiado</span>\';
        setTimeout(() => { btn.innerHTML = original; }, 2000);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(ok).catch(fallback);
    } else {
        fallback();
    }
    function fallback() {
        const ta = document.createElement("textarea");
        ta.value = url; ta.style.cssText = "position:fixed;opacity:0";
        document.body.appendChild(ta); ta.select();
        document.execCommand("copy"); document.body.removeChild(ta);
        ok();
    }
}
</script>';

$floatingButton = renderFloatingActionButton([
    'href'    => 'index.php?page=sesiones',
    'icon'    => 'fas fa-plus',
    'tooltip' => 'Nueva Sesion',
    'color'   => 'blue',
]);

require_once '../app/views/layouts/base.php';
