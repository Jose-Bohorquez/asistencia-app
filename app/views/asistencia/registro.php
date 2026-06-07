<?php
// Mapeo de códigos de error a mensajes amigables con contexto
$errorMap = [
    'enlace_invalido' => [
        'icon'  => 'fa-link-slash',
        'color' => 'yellow',
        'title' => 'Enlace no válido',
        'msg'   => 'Este enlace no corresponde a ninguna sesión. Pide al docente que te comparta el enlace correcto.',
    ],
    'no_existe' => [
        'icon'  => 'fa-circle-question',
        'color' => 'yellow',
        'title' => 'Sesión no encontrada',
        'msg'   => 'La sesión no existe o el enlace está incompleto. Verifica el enlace con tu docente.',
    ],
    'finalizada' => [
        'icon'  => 'fa-flag-checkered',
        'color' => 'blue',
        'title' => 'Sesión finalizada',
        'msg'   => 'El docente ya cerró el registro de esta sesión. Si crees que es un error, consulta directamente con él.',
    ],
    'cancelada' => [
        'icon'  => 'fa-ban',
        'color' => 'red',
        'title' => 'Sesión cancelada',
        'msg'   => 'Esta sesión fue cancelada. Consulta con tu docente sobre la próxima clase.',
    ],
    'error_sistema' => [
        'icon'  => 'fa-triangle-exclamation',
        'color' => 'red',
        'title' => 'Error temporal',
        'msg'   => 'Ocurrió un problema al cargar la sesión. Intenta recargar la página.',
    ],
];

$colorClasses = [
    'yellow' => ['bg' => 'bg-amber-50',  'border' => 'border-amber-300', 'icon' => 'text-amber-500',  'title' => 'text-amber-800',  'msg' => 'text-amber-700'],
    'blue'   => ['bg' => 'bg-blue-50',   'border' => 'border-blue-300',  'icon' => 'text-blue-500',   'title' => 'text-blue-800',   'msg' => 'text-blue-700'],
    'red'    => ['bg' => 'bg-red-50',    'border' => 'border-red-300',   'icon' => 'text-red-500',    'title' => 'text-red-800',    'msg' => 'text-red-700'],
];

// Distinguir si el error es de estado de sesión (no se muestra el form)
// o un error de validación del form (se muestra el form con mensaje)
$errorSesion = $errorMap[$error ?? ''] ?? null;
$errorForm   = !$errorSesion && !empty($error) ? $error : '';

include '../app/views/layouts/header.php';
?>

<div class="min-h-screen bg-gray-50 py-6 px-4">
<div class="max-w-2xl mx-auto">

    <!-- Branding cabecera -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-2 text-blue-800 font-bold text-lg">
            <i class="fas fa-graduation-cap text-blue-600"></i>
            <?= APP_NAME ?>
        </div>
    </div>

    <?php if ($errorSesion): ?>
    <!-- === ESTADO DE ERROR DE SESIÓN (sin form) === -->
    <?php
        $col = $colorClasses[$errorSesion['color']];
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="<?= $col['bg'] ?> <?= $col['border'] ?> border-b px-6 py-8 text-center">
            <i class="fas <?= $errorSesion['icon'] ?> <?= $col['icon'] ?> text-5xl mb-4 block"></i>
            <h1 class="text-xl font-bold <?= $col['title'] ?> mb-2"><?= $errorSesion['title'] ?></h1>
            <p class="<?= $col['msg'] ?> text-sm max-w-sm mx-auto"><?= $errorSesion['msg'] ?></p>
        </div>
        <?php if ($sesion && in_array($sesion['estado'], ['finalizada', 'cancelada'])): ?>
        <div class="px-6 py-5 bg-white text-center">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Sesión</p>
            <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?></p>
            <p class="text-xs text-gray-500 mt-1">
                <?= date('d/m/Y', strtotime($sesion['fecha'])) ?>
                &bull; <?= date('H:i', strtotime($sesion['hora_inicio'])) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($sesion && $sesion['estado'] === 'activa'): ?>
    <!-- === FORMULARIO ACTIVO === -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        <!-- Header sesión -->
        <div class="bg-gradient-to-r from-blue-700 to-blue-800 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fas fa-chalkboard-teacher text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-blue-200 text-xs font-medium uppercase tracking-wider mb-0.5">Registro de asistencia</p>
                    <h1 class="text-white font-bold text-base leading-tight truncate">
                        <?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?>
                    </h1>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1.5 text-blue-200 text-xs">
                        <span><i class="fas fa-calendar-day mr-1"></i><?= date('d/m/Y', strtotime($sesion['fecha'])) ?></span>
                        <span><i class="fas fa-clock mr-1"></i><?= date('H:i', strtotime($sesion['hora_inicio'])) ?><?= $sesion['hora_fin'] ? ' — ' . date('H:i', strtotime($sesion['hora_fin'])) : '' ?></span>
                        <?php if (!empty($sesion['aula'])): ?>
                        <span><i class="fas fa-door-open mr-1"></i>Aula <?= htmlspecialchars($sesion['aula']) ?><?= !empty($sesion['sede']) ? ' · ' . htmlspecialchars($sesion['sede']) : '' ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sesion['curso_area'])): ?>
                        <span><i class="fas fa-layer-group mr-1"></i><?= htmlspecialchars($sesion['curso_area']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error de validación del form -->
        <?php if ($errorForm): ?>
        <div class="mx-4 mt-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
            <i class="fas fa-circle-exclamation text-red-500 mt-0.5 shrink-0"></i>
            <p class="text-red-700 text-sm"><?= htmlspecialchars($errorForm) ?></p>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form id="asistenciaForm" method="POST"
              action="index.php?page=asistencia&sesion_id=<?= (int)$sesion['id'] ?>"
              class="p-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

            <!-- Campos obligatorios -->
            <div>
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
                    Tus datos <span class="text-gray-400 font-normal normal-case">(* obligatorio)</span>
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nombre" name="nombre"
                               autocomplete="name"
                               placeholder="Ej: Laura Martínez Gómez"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               required>
                    </div>

                    <div>
                        <label for="documento" class="block text-sm font-medium text-gray-700 mb-1">
                            Documento de identidad <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="documento" name="documento"
                               inputmode="numeric" autocomplete="off"
                               placeholder="Ej: 1234567890"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                               required>
                    </div>

                    <div>
                        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">
                            Código de estudiante
                        </label>
                        <input type="text" id="codigo" name="codigo"
                               autocomplete="off"
                               placeholder="Ej: 2023100001"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>

                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                            Teléfono
                        </label>
                        <input type="tel" id="telefono" name="telefono"
                               inputmode="numeric" autocomplete="tel"
                               placeholder="Ej: 3001234567"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>

                    <div>
                        <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">
                            Dirección
                        </label>
                        <input type="text" id="direccion" name="direccion"
                               autocomplete="street-address"
                               placeholder="Ej: Calle 10 # 5-23"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>

                    <div>
                        <label for="correo" class="block text-sm font-medium text-gray-700 mb-1">
                            Correo electrónico
                        </label>
                        <input type="email" id="correo" name="correo"
                               autocomplete="email"
                               placeholder="Ej: estudiante@ut.edu.co"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>

                </div>
            </div>

            <!-- Firma -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Firma <span class="text-red-500">*</span>
                    </label>
                    <button type="button" id="btnBorrarFirma"
                            class="text-xs text-gray-400 hover:text-red-500 flex items-center gap-1 transition-colors">
                        <i class="fas fa-rotate-left"></i> Borrar
                    </button>
                </div>
                <div id="firmaWrap"
                     class="border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-white
                            transition-colors hover:border-blue-400 cursor-crosshair">
                    <canvas id="signaturePad" class="block w-full touch-none" style="height:200px"></canvas>
                </div>
                <p id="firmaHint" class="text-xs text-gray-400 mt-1.5">
                    <i class="fas fa-pen-nib mr-1"></i>Firma dentro del recuadro con el dedo o el ratón.
                </p>
                <input type="hidden" id="firmaInput" name="firma">
            </div>

            <!-- Submit -->
            <div class="pt-2">
                <button type="submit" id="btnSubmit"
                        class="w-full bg-blue-700 hover:bg-blue-800 active:bg-blue-900 text-white
                               font-semibold py-3 px-6 rounded-xl shadow-sm transition-all duration-200
                               flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-check-circle" id="btnIcon"></i>
                    <span id="btnText">Registrar asistencia</span>
                </button>
                <p class="text-center text-xs text-gray-400 mt-3">
                    Al enviar confirmas que estás presente en la clase.
                </p>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- === ESTADO FALLBACK (no debería ocurrir) === -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <i class="fas fa-circle-question text-gray-300 text-5xl mb-4 block"></i>
        <h1 class="text-lg font-bold text-gray-700 mb-2">Enlace no disponible</h1>
        <p class="text-sm text-gray-500">Este enlace no está activo en este momento. Consulta con tu docente.</p>
    </div>
    <?php endif; ?>

    <p class="text-center text-xs text-gray-400 mt-6">
        &copy; <?= date('Y') ?> Universidad del Tolima &mdash; INGENIERÍA DE SISTEMAS
    </p>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas  = document.getElementById('signaturePad');
    if (!canvas) return;

    const wrap    = document.getElementById('firmaWrap');
    const input   = document.getElementById('firmaInput');
    const hint    = document.getElementById('firmaHint');
    const btnBorr = document.getElementById('btnBorrarFirma');
    const form    = document.getElementById('asistenciaForm');
    const btnSub  = document.getElementById('btnSubmit');
    const btnIcon = document.getElementById('btnIcon');
    const btnText = document.getElementById('btnText');

    // Ajustar canvas al contenedor
    function resizeCanvas() {
        const dpr = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width  = rect.width  * dpr;
        canvas.height = rect.height * dpr;
        canvas.getContext('2d').scale(dpr, dpr);
        pad.clear();
    }

    const pad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255,255,255,0)',
        penColor: '#1e3a5f',
        minWidth: 1.5,
        maxWidth: 3,
    });

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    // Feedback visual al empezar a firmar
    canvas.addEventListener('pointerdown', function () {
        wrap.classList.remove('border-gray-300');
        wrap.classList.add('border-blue-500');
        hint.classList.add('hidden');
    });

    btnBorr.addEventListener('click', function () {
        pad.clear();
        wrap.classList.remove('border-blue-500');
        wrap.classList.add('border-gray-300');
        hint.classList.remove('hidden');
    });

    form.addEventListener('submit', function (e) {
        if (pad.isEmpty()) {
            e.preventDefault();
            wrap.classList.add('border-red-400');
            wrap.classList.remove('border-gray-300', 'border-blue-500');
            hint.textContent = 'Por favor firma antes de enviar.';
            hint.className = 'text-xs text-red-500 mt-1.5';
            canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        input.value = pad.toDataURL('image/png');

        // Estado de carga
        btnSub.disabled = true;
        btnSub.classList.replace('bg-blue-700', 'bg-blue-400');
        btnIcon.className = 'fas fa-circle-notch fa-spin';
        btnText.textContent = 'Registrando...';
    });
});
</script>

<?php include '../app/views/layouts/footer.php'; ?>
