<?php
$pageTitle = 'Detalle de sesion - ' . APP_NAME;
$csrfToken = $_SESSION['csrf_token'] ?? '';

$sesionId   = (int)($sesion['id'] ?? 0);
$estadoSes  = $sesion['estado'] ?? '';
$tokenSes   = $sesion['token'] ?? '';
$enlaceUrl  = !empty($tokenSes) ? APP_URL . '/index.php?page=asistencia&token=' . urlencode($tokenSes) : '';

$estadoClases = [
    'activa'     => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
    'finalizada' => 'bg-blue-100 text-blue-800 border border-blue-200',
    'cancelada'  => 'bg-red-100 text-red-800 border border-red-200',
];
$estadoLabel = [
    'activa'     => 'Activa',
    'finalizada' => 'Finalizada',
    'cancelada'  => 'Cancelada',
];
$estClass = $estadoClases[$estadoSes] ?? 'bg-gray-100 text-gray-700 border border-gray-200';
$estLabel = $estadoLabel[$estadoSes] ?? ucfirst($estadoSes);

ob_start();
?>

<div class="space-y-5" id="detallePage">

    <!-- Breadcrumb + acciones superiores -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <nav class="flex items-center gap-1 text-sm text-gray-500">
            <a href="index.php?page=sesiones" class="hover:text-blue-600 font-medium transition-colors">
                Sesiones
            </a>
            <i class="fas fa-chevron-right text-[10px] text-gray-400"></i>
            <span class="text-gray-900 font-semibold truncate max-w-[200px]">
                <?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?>
            </span>
        </nav>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Estado badge -->
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $estClass ?>">
                <?php if ($estadoSes === 'activa'): ?>
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                En vivo
                <?php else: ?>
                <?= $estLabel ?>
                <?php endif; ?>
            </span>

            <!-- Vista previa -->
            <a href="index.php?page=sesiones&action=imprimir&sesion_id=<?= $sesionId ?>"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-print text-xs"></i>
                Vista previa
            </a>

            <?php if (!empty($enlaceUrl) && $estadoSes === 'activa'): ?>
            <!-- Copiar enlace -->
            <button type="button" id="btnCopiarEnlace"
                    onclick="copiarEnlaceDetalle('<?= htmlspecialchars($enlaceUrl, ENT_QUOTES) ?>')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-copy text-xs"></i>
                Copiar enlace
            </button>
            <?php endif; ?>

            <?php if ($estadoSes === 'activa' && ($can_activate ?? false)): ?>
            <!-- Finalizar sesion -->
            <form method="POST" action="index.php?page=sesiones&action=deactivate" class="inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $sesionId ?>">
                <button type="button"
                        onclick="
                            Swal.fire({
                                title:'¿Finalizar sesión?',
                                text:'Los estudiantes ya no podrán registrar asistencia.',
                                icon:'warning',
                                showCancelButton:true,
                                confirmButtonText:'Sí, finalizar',
                                cancelButtonText:'Cancelar',
                                confirmButtonColor:'#ea580c'
                            }).then(r=>{ if(r.isConfirmed) this.closest('form').submit(); })
                        "
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 border border-orange-200 text-orange-700 hover:bg-orange-100 text-sm font-medium rounded-lg transition-colors shadow-sm">
                    <i class="fas fa-stop-circle text-xs"></i>
                    Finalizar
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info de la sesion -->
    <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Curso</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?></p>
                <?php if (!empty($sesion['curso_codigo'])): ?>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($sesion['curso_codigo']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Programa</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($sesion['programa_nombre'] ?? '—') ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Fecha</p>
                <p class="font-semibold text-gray-900">
                    <?= !empty($sesion['fecha']) ? date('d/m/Y', strtotime($sesion['fecha'])) : '—' ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Horario</p>
                <p class="font-semibold text-gray-900">
                    <?= !empty($sesion['hora_inicio']) ? date('H:i', strtotime($sesion['hora_inicio'])) : '—' ?>
                    <?php if (!empty($sesion['hora_fin'])): ?>
                    &mdash; <?= date('H:i', strtotime($sesion['hora_fin'])) ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php $aulaMostrar = $sesion['aula_display'] ?? $sesion['aula'] ?? ''; ?>
            <?php if (!empty($aulaMostrar)): ?>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Aula</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($aulaMostrar) ?></p>
            </div>
            <?php endif; ?>
            <?php $sedeMostrar = $sesion['sede_display'] ?? $sesion['sede'] ?? ''; ?>
            <?php if (!empty($sedeMostrar)): ?>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Sede</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($sedeMostrar) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($sesion['profesor_nombre'])): ?>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Docente</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($sesion['profesor_nombre']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bloque de asistencias en vivo -->
    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <!-- Header con indicador En Vivo -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold text-gray-900">Lista de asistencia</h2>
                <?php if ($estadoSes === 'activa'): ?>
                <span id="liveBadge"
                      class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    En vivo
                </span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($estadoSes === 'activa'): ?>
                <span id="ultimaActualizacion" class="text-xs text-gray-400">Actualizando...</span>
                <button type="button" id="btnRefrescar"
                        onclick="refrescarAhora()"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 rounded-lg text-xs font-medium transition-colors">
                    <i class="fas fa-rotate-right"></i>
                    Actualizar
                </button>
                <?php endif; ?>
                <span id="contadorTotal"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    <?= count($asistencias) ?> registrados
                </span>
            </div>
        </div>

        <!-- Tabla desktop -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100" id="tablaAsistenciasDesktop">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Hora</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Firma</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50" id="tbodyDesktop">
                    <?php if (empty($asistencias)): ?>
                    <tr id="emptyRowDesktop">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                            <i class="fas fa-user-clock text-2xl text-gray-300 block mb-2"></i>
                            Aun no hay asistencias registradas
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($asistencias as $i => $a): ?>
                    <?php
                        $tieneFirma = !empty($a['firma']) && str_starts_with($a['firma'], 'data:image/');
                        $hora = !empty($a['hora_registro']) ? date('H:i', strtotime($a['hora_registro'])) : '—';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-xs text-gray-400"><?= $i + 1 ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($a['estudiante_nombre'] ?? '') ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?= htmlspecialchars($a['estudiante_documento'] ?? '') ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $hora ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($tieneFirma): ?>
                            <span class="inline-flex items-center gap-1 text-emerald-600 text-xs font-medium">
                                <i class="fas fa-check-circle"></i> Si
                            </span>
                            <?php else: ?>
                            <span class="text-gray-300 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Cards mobile -->
        <div class="md:hidden divide-y divide-gray-100" id="listaMobile">
            <?php if (empty($asistencias)): ?>
            <div id="emptyRowMobile" class="px-4 py-10 text-center text-sm text-gray-400">
                <i class="fas fa-user-clock text-2xl text-gray-300 block mb-2"></i>
                Aun no hay asistencias registradas
            </div>
            <?php else: ?>
            <?php foreach ($asistencias as $i => $a): ?>
            <?php
                $tieneFirma = !empty($a['firma']) && str_starts_with($a['firma'], 'data:image/');
                $hora = !empty($a['hora_registro']) ? date('H:i', strtotime($a['hora_registro'])) : '—';
            ?>
            <div class="px-4 py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center shrink-0 text-xs font-semibold text-blue-600">
                    <?= $i + 1 ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        <?= htmlspecialchars($a['estudiante_nombre'] ?? '') ?>
                    </p>
                    <p class="text-xs text-gray-500">
                        <?= htmlspecialchars($a['estudiante_documento'] ?? '') ?>
                        &bull; <?= $hora ?>
                    </p>
                </div>
                <?php if ($tieneFirma): ?>
                <span class="shrink-0 text-emerald-500 text-sm"><i class="fas fa-signature"></i></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /#detallePage -->

<!-- Toast container -->
<div id="toastContainer" class="fixed bottom-5 right-5 z-50 space-y-2 pointer-events-none"></div>

<?php
$content = ob_get_clean();

$jsonUrl = 'index.php?page=sesiones&action=asistencia_json&sesion_id=' . $sesionId;
$esActiva = ($estadoSes === 'activa') ? 'true' : 'false';

$additionalJS = '<script>
(function() {
    var SESION_ACTIVA = ' . $esActiva . ';
    var JSON_URL = ' . json_encode($jsonUrl) . ';
    var POLL_INTERVAL = 15000; // 15 segundos
    var totalAnterior = ' . count($asistencias) . ';
    var ultimaActualizacion = null;
    var pollTimer = null;

    function mostrarToast(msg) {
        var c = document.getElementById("toastContainer");
        if (!c) return;
        var t = document.createElement("div");
        t.className = "pointer-events-auto flex items-center gap-3 bg-emerald-700 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-lg";
        t.innerHTML = \'<i class="fas fa-user-plus"></i> \' + msg;
        c.appendChild(t);
        setTimeout(function() {
            t.style.transition = "opacity 0.4s";
            t.style.opacity = "0";
            setTimeout(function() { c.removeChild(t); }, 400);
        }, 3500);
    }

    function actualizarUI(data) {
        ultimaActualizacion = new Date();
        var contador = document.getElementById("contadorTotal");
        if (contador) contador.textContent = data.total + " registrados";

        var nuevos = data.total - totalAnterior;
        if (nuevos > 0 && totalAnterior >= 0) {
            mostrarToast("+" + nuevos + " nuevo" + (nuevos > 1 ? "s" : "") + " asistente" + (nuevos > 1 ? "s" : ""));
        }
        totalAnterior = data.total;

        // Actualizar tabla desktop
        var tbody = document.getElementById("tbodyDesktop");
        var listaMobile = document.getElementById("listaMobile");
        if (!tbody || !listaMobile) return;

        if (data.total === 0) {
            tbody.innerHTML = \'<tr id="emptyRowDesktop"><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400"><i class="fas fa-user-clock text-2xl text-gray-300 block mb-2"></i>Aun no hay asistencias registradas</td></tr>\';
            listaMobile.innerHTML = \'<div id="emptyRowMobile" class="px-4 py-10 text-center text-sm text-gray-400"><i class="fas fa-user-clock text-2xl text-gray-300 block mb-2"></i>Aun no hay asistencias registradas</div>\';
            return;
        }

        var rowsD = "";
        var rowsM = "";
        data.asistencias.forEach(function(a, i) {
            var firma = a.tiene_firma
                ? \'<span class="inline-flex items-center gap-1 text-emerald-600 text-xs font-medium"><i class="fas fa-check-circle"></i> Si</span>\'
                : \'<span class="text-gray-300 text-xs">—</span>\';
            var firmaM = a.tiene_firma
                ? \'<span class="shrink-0 text-emerald-500 text-sm"><i class="fas fa-signature"></i></span>\'
                : "";
            rowsD += \'<tr class="hover:bg-gray-50 transition-colors">\' +
                \'<td class="px-4 py-3 text-xs text-gray-400">\' + (i+1) + \'</td>\' +
                \'<td class="px-4 py-3 text-sm font-medium text-gray-900">\' + escHtml(a.nombre) + \'</td>\' +
                \'<td class="px-4 py-3 text-sm text-gray-600">\' + escHtml(a.documento) + \'</td>\' +
                \'<td class="px-4 py-3 text-sm text-gray-600">\' + (a.hora || "—") + \'</td>\' +
                \'<td class="px-4 py-3 text-center">\' + firma + \'</td>\' +
                \'</tr>\';
            rowsM += \'<div class="px-4 py-3 flex items-center gap-3">\' +
                \'<div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center shrink-0 text-xs font-semibold text-blue-600">\' + (i+1) + \'</div>\' +
                \'<div class="flex-1 min-w-0">\' +
                \'<p class="text-sm font-medium text-gray-900 truncate">\' + escHtml(a.nombre) + \'</p>\' +
                \'<p class="text-xs text-gray-500">\' + escHtml(a.documento) + \' &bull; \' + (a.hora || "—") + \'</p>\' +
                \'</div>\' + firmaM + \'</div>\';
        });
        tbody.innerHTML = rowsD;
        listaMobile.innerHTML = rowsM;
    }

    function actualizarReloj() {
        if (!ultimaActualizacion) return;
        var el = document.getElementById("ultimaActualizacion");
        if (!el) return;
        var diff = Math.round((new Date() - ultimaActualizacion) / 1000);
        el.textContent = diff <= 5 ? "Ahora mismo" : "Hace " + diff + " seg";
    }

    function escHtml(s) {
        var d = document.createElement("div");
        d.appendChild(document.createTextNode(s || ""));
        return d.innerHTML;
    }

    function poll() {
        fetch(JSON_URL, { credentials: "same-origin" })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) actualizarUI(data);
            })
            .catch(function(e) { console.warn("Poll error", e); });
    }

    window.refrescarAhora = function() {
        poll();
        clearInterval(pollTimer);
        if (SESION_ACTIVA) {
            pollTimer = setInterval(poll, POLL_INTERVAL);
        }
    };

    if (SESION_ACTIVA) {
        poll(); // primera carga inmediata
        pollTimer = setInterval(poll, POLL_INTERVAL);
        setInterval(actualizarReloj, 5000);
    }
})();

function copiarEnlaceDetalle(url) {
    var btn = document.getElementById("btnCopiarEnlace");
    var original = btn ? btn.innerHTML : "";
    var ok = function() {
        if (btn) btn.innerHTML = \'<i class="fas fa-check text-xs"></i> Copiado\';
        setTimeout(function() { if (btn) btn.innerHTML = original; }, 2000);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(ok).catch(function() {});
    } else {
        var ta = document.createElement("textarea");
        ta.value = url; ta.style.cssText = "position:fixed;opacity:0";
        document.body.appendChild(ta); ta.select();
        document.execCommand("copy"); document.body.removeChild(ta);
        ok();
    }
}
</script>';

require_once '../app/views/layouts/base.php';
