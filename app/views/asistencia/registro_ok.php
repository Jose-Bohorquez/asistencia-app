<?php include '../app/views/layouts/header.php'; ?>

<div class="min-h-screen bg-gray-50 py-10 px-4 flex flex-col items-center justify-start">

    <div class="w-full max-w-md">

        <!-- Branding -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-2 text-blue-800 font-bold text-lg">
                <i class="fas fa-graduation-cap text-blue-600"></i>
                <?= APP_NAME ?>
            </div>
        </div>

        <!-- Card de éxito -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

            <!-- Banner verde -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-8 text-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-white text-3xl"></i>
                </div>
                <h1 class="text-white text-2xl font-bold mb-1">¡Asistencia registrada!</h1>
                <p class="text-green-100 text-sm">Tu firma quedó guardada correctamente.</p>
            </div>

            <!-- Detalle de la sesión -->
            <?php if ($sesion): ?>
            <div class="px-6 py-5 space-y-2 border-b border-gray-100">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-3">Resumen</p>
                <div class="flex items-start gap-3">
                    <i class="fas fa-book text-blue-400 mt-0.5 w-4 text-center shrink-0"></i>
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?></p>
                        <?php if (!empty($sesion['programa_nombre'])): ?>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($sesion['programa_nombre']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-calendar-day text-blue-400 w-4 text-center shrink-0"></i>
                    <p class="text-sm text-gray-700"><?= date('d \d\e F \d\e Y', strtotime($sesion['fecha'])) ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fas fa-clock text-blue-400 w-4 text-center shrink-0"></i>
                    <p class="text-sm text-gray-700">
                        <?= date('H:i', strtotime($sesion['hora_inicio'])) ?>
                        <?= $sesion['hora_fin'] ? ' — ' . date('H:i', strtotime($sesion['hora_fin'])) : '' ?>
                    </p>
                </div>
                <?php if (!empty($sesion['profesor_nombre'])): ?>
                <div class="flex items-center gap-3">
                    <i class="fas fa-user-tie text-blue-400 w-4 text-center shrink-0"></i>
                    <p class="text-sm text-gray-700"><?= htmlspecialchars($sesion['profesor_nombre']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Instrucción final -->
            <div class="px-6 py-5 text-center bg-gray-50">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-shield-halved text-green-500 mr-1"></i>
                    Tu asistencia quedó registrada. Puedes cerrar esta página.
                </p>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; <?= date('Y') ?> Universidad del Tolima &mdash; INGENIERÍA DE SISTEMAS
        </p>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>
