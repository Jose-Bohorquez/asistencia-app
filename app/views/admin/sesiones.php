<?php
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';
require_once '../app/views/components/table.php';
require_once '../app/views/components/modal.php';

$pageTitle = 'Sesiones - ' . APP_NAME;
$csrfToken = $_SESSION['csrf_token'] ?? '';

// Flash message
$flash = $flash_message ?? null;

ob_start();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestión de Sesiones</h1>
            <p class="text-sm text-gray-500 mt-1">Administra las sesiones de clase y sus tokens de asistencia</p>
        </div>
        <?php if ($can_create ?? false): ?>
        <button id="btnNuevaSesion"
                class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors duration-200">
            <i class="fas fa-plus"></i> Nueva Sesión
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash / Errores -->
    <?php if ($flash): ?>
    <div class="rounded-lg px-4 py-3 text-sm font-medium <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Formulario crear/editar (oculto) -->
    <?php if ($can_create ?? false): ?>
    <div id="formSesion" class="hidden bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="bg-gradient-to-r from-blue-700 to-blue-800 px-6 py-4 rounded-t-xl">
            <h2 class="text-white font-semibold text-lg" id="formTitle">Nueva Sesión</h2>
        </div>
        <form id="sesionForm" method="POST" action="index.php?page=sesiones&action=create" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="sesionId" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Curso <span class="text-red-500">*</span></label>
                    <select name="curso_id" id="sesionCurso" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">— Seleccione un curso —</option>
                        <?php foreach (($cursos ?? []) as $curso): ?>
                        <option value="<?= (int)$curso['id'] ?>">
                            <?= htmlspecialchars($curso['codigo'] . ' - ' . $curso['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha" id="sesionFecha" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio <span class="text-red-500">*</span></label>
                    <input type="time" name="hora_inicio" id="sesionHoraInicio" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin</label>
                    <input type="time" name="hora_fin" id="sesionHoraFin"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sede</label>
                    <select name="sede" id="sesionSede"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">— Sede del curso —</option>
                        <?php foreach ([
                            'CAT BARRANQUILLA','CAT CAJAMARCA','CAT CALI','CAT CHAPARRAL',
                            'CAT GIRARDOT','CAT HONDA','CAT IBAGUE','CAT ICONONZO',
                            'CAT KENNEDY','CAT LIBANO','CAT MARIQUITA','CAT MEDELLIN',
                            'CAT MELGAR','CAT MOCOA','CAT NEIVA','CAT PACHO',
                            'CAT PEREIRA','CAT PLANADAS','CAT POPAYAN','CAT PURIFICACION',
                        ] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Vacío usa la sede registrada en el curso.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aula</label>
                    <input type="text" name="aula" id="sesionAula" maxlength="30" placeholder="Vacío usa el aula del curso"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">Solo complete si difiere del aula del curso.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="estado" id="sesionEstado"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="activa" selected>Activa (acepta registros ahora)</option>
                        <option value="finalizada">Finalizada (cierra registros)</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle"></i>
                        Una sesión <strong>Activa</strong> permite que los estudiantes registren asistencia inmediatamente.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="btnCancelarForm"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg shadow-sm transition-colors duration-200">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" action="index.php" class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
        <input type="hidden" name="page" value="sesiones">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   placeholder="Buscar por curso..."
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">

            <select name="estado"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="activa"     <?= ($filters['estado'] ?? '') === 'activa'     ? 'selected' : '' ?>>Activa</option>
                <option value="finalizada" <?= ($filters['estado'] ?? '') === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                <option value="cancelada"  <?= ($filters['estado'] ?? '') === 'cancelada'  ? 'selected' : '' ?>>Cancelada</option>
            </select>

            <input type="date" name="fecha_desde" value="<?= htmlspecialchars($filters['fecha_desde'] ?? '') ?>"
                   placeholder="Desde"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">

            <div class="flex gap-2">
                <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($filters['fecha_hasta'] ?? '') ?>"
                       placeholder="Hasta"
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit"
                        class="px-3 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition-colors duration-200">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <!-- Tabla de sesiones -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <?php if (empty($sesiones)): ?>
        <div class="text-center py-16 text-gray-500">
            <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4 block"></i>
            <p class="text-lg font-medium">No hay sesiones registradas</p>
            <p class="text-sm mt-1">Crea una nueva sesión para comenzar</p>
        </div>
        <?php else: ?>
        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Curso</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Horario</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Asistencias</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($sesiones as $sesion): ?>
                    <?php
                        $estadoConfig = [
                            'activa'     => ['bg-green-100 text-green-800',  'Activa'],
                            'programada' => ['bg-yellow-100 text-yellow-800', 'Programada'],
                            'finalizada' => ['bg-blue-100 text-blue-800',    'Finalizada'],
                            'cancelada'  => ['bg-red-100 text-red-800',      'Cancelada'],
                        ];
                        [$estadoClass, $estadoLabel] = $estadoConfig[$sesion['estado']] ?? ['bg-gray-100 text-gray-700', ucfirst($sesion['estado'])];
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($sesion['curso_nombre']) ?></div>
                            <?php if (!empty($sesion['programa_nombre'])): ?>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($sesion['programa_nombre']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <?= date('d/m/Y', strtotime($sesion['fecha'])) ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <?= date('H:i', strtotime($sesion['hora_inicio'])) ?>
                            <?php if ($sesion['hora_fin']): ?>
                            — <?= date('H:i', strtotime($sesion['hora_fin'])) ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $estadoClass ?>">
                                <?= $estadoLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <span class="font-medium"><?= (int)($sesion['total_asistencias'] ?? 0) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <?php if (!empty($sesion['token']) && $sesion['estado'] === 'activa'): ?>
                                <?php $sUrl = APP_URL . '/index.php?page=asistencia&token=' . urlencode($sesion['token']); ?>
                                <button type="button"
                                        onclick="copiarEnlaceSesion(this,'<?= htmlspecialchars($sUrl, ENT_QUOTES) ?>')"
                                        title="Copiar enlace de asistencia"
                                        class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-copy"></i> Enlace
                                </button>
                                <?php endif; ?>

                                <?php if ($sesion['estado'] === 'finalizada'): ?>
                                <a href="index.php?page=exportar&sesion_id=<?= (int)$sesion['id'] ?>"
                                   title="Exportar asistencia"
                                   class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-green-50 text-green-700 hover:bg-green-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-file-export"></i> Exportar
                                </a>
                                <?php endif; ?>

                                <?php if ($sesion['estado'] !== 'activa' && ($can_activate ?? false)): ?>
                                <form method="POST" action="index.php?page=sesiones&action=activate" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$sesion['id'] ?>">
                                    <button type="button"
                                            onclick="activarSesion(this.closest('form'), '<?= htmlspecialchars(addslashes($sesion['curso_nombre'] ?? ''), ENT_QUOTES) ?>')"
                                            class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded text-xs font-medium transition-colors">
                                        <i class="fas fa-play-circle"></i> Activar
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if ($sesion['estado'] === 'activa' && ($can_activate ?? false)): ?>
                                <form method="POST" action="index.php?page=sesiones&action=deactivate" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$sesion['id'] ?>">
                                    <button type="button"
                                            onclick="finalizarSesion(this.closest('form'), '<?= htmlspecialchars(addslashes($sesion['curso_nombre'] ?? ''), ENT_QUOTES) ?>')"
                                            class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-orange-50 text-orange-700 hover:bg-orange-100 rounded text-xs font-medium transition-colors">
                                        <i class="fas fa-stop-circle"></i> Finalizar
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if ($can_edit ?? false): ?>
                                <button type="button"
                                        onclick="editarSesion(<?= htmlspecialchars(json_encode($sesion)) ?>)"
                                        title="Editar sesión"
                                        class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>

                                <?php if ($can_delete ?? false): ?>
                                <button type="button"
                                        onclick="confirmarEliminar(<?= (int)$sesion['id'] ?>, '<?= htmlspecialchars(addslashes($sesion['curso_nombre'] ?? ''), ENT_QUOTES) ?>', <?= (int)($sesion['total_asistencias'] ?? 0) ?>)"
                                        title="Eliminar sesión"
                                        class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>

                                <a href="index.php?page=sesiones&action=imprimir&sesion_id=<?= (int)$sesion['id'] ?>"
                                   target="_blank"
                                   title="Lista de asistencia"
                                   class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-gray-50 text-gray-600 hover:bg-gray-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-print"></i>
                                </a>

                                <a href="index.php?page=sesiones&action=detalle&sesion_id=<?= (int)$sesion['id'] ?>"
                                   title="Ver detalle"
                                   class="table-action inline-flex items-center gap-1 px-2 py-1.5 bg-slate-50 text-slate-600 hover:bg-slate-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden divide-y divide-gray-100">
            <?php foreach ($sesiones as $sesion): ?>
            <?php
                $estadoConfig = [
                    'activa'     => ['bg-green-100 text-green-800',  'Activa'],
                    'programada' => ['bg-yellow-100 text-yellow-800', 'Programada'],
                    'finalizada' => ['bg-blue-100 text-blue-800',    'Finalizada'],
                    'cancelada'  => ['bg-red-100 text-red-800',      'Cancelada'],
                ];
                [$estadoClass, $estadoLabel] = $estadoConfig[$sesion['estado']] ?? ['bg-gray-100 text-gray-700', ucfirst($sesion['estado'])];
            ?>
            <div class="p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($sesion['curso_nombre']) ?></p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <?= date('d/m/Y', strtotime($sesion['fecha'])) ?> &bull;
                            <?= date('H:i', strtotime($sesion['hora_inicio'])) ?>
                            <?= $sesion['hora_fin'] ? '— ' . date('H:i', strtotime($sesion['hora_fin'])) : '' ?>
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $estadoClass ?>">
                        <?= $estadoLabel ?>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php if (!empty($sesion['token']) && $sesion['estado'] === 'activa'): ?>
                    <?php $mUrl = APP_URL . '/index.php?page=asistencia&token=' . urlencode($sesion['token']); ?>
                    <button type="button"
                            onclick="copiarEnlaceSesion(this,'<?= htmlspecialchars($mUrl, ENT_QUOTES) ?>')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium active:bg-indigo-700">
                        <i class="fas fa-copy"></i> Copiar enlace
                    </button>
                    <?php endif; ?>

                    <?php if ($sesion['estado'] !== 'activa' && ($can_activate ?? false)): ?>
                    <form method="POST" action="index.php?page=sesiones&action=activate">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int)$sesion['id'] ?>">
                        <button type="button"
                                onclick="activarSesion(this.closest('form'), '<?= htmlspecialchars(addslashes($sesion['curso_nombre'] ?? ''), ENT_QUOTES) ?>')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-yellow-100 text-yellow-800 rounded-lg text-sm font-medium active:bg-yellow-200">
                            <i class="fas fa-play-circle"></i> Activar
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($sesion['estado'] === 'activa' && ($can_activate ?? false)): ?>
                    <form method="POST" action="index.php?page=sesiones&action=deactivate">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="id" value="<?= (int)$sesion['id'] ?>">
                        <button type="button"
                                onclick="finalizarSesion(this.closest('form'), '<?= htmlspecialchars(addslashes($sesion['curso_nombre'] ?? ''), ENT_QUOTES) ?>')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-orange-100 text-orange-800 rounded-lg text-sm font-medium active:bg-orange-200">
                            <i class="fas fa-stop-circle"></i> Finalizar
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($sesion['estado'] === 'finalizada'): ?>
                    <a href="index.php?page=exportar&sesion_id=<?= (int)$sesion['id'] ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-100 text-green-800 rounded-lg text-sm font-medium active:bg-green-200">
                        <i class="fas fa-file-export"></i> Exportar
                    </a>
                    <?php endif; ?>

                    <?php if ($can_edit ?? false): ?>
                    <button type="button"
                            onclick="editarSesion(<?= htmlspecialchars(json_encode($sesion)) ?>)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium active:bg-blue-200">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <?php endif; ?>

                    <a href="index.php?page=sesiones&action=imprimir&sesion_id=<?= (int)$sesion['id'] ?>"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium active:bg-gray-200">
                        <i class="fas fa-print"></i> Imprimir
                    </a>

                    <a href="index.php?page=sesiones&action=detalle&sesion_id=<?= (int)$sesion['id'] ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-medium active:bg-slate-200">
                        <i class="fas fa-eye"></i> Ver
                    </a>

                    <?php if ($can_delete ?? false): ?>
                    <button type="button"
                            onclick="confirmarEliminar(<?= (int)$sesion['id'] ?>, '<?= htmlspecialchars(addslashes($sesion['curso_nombre'] ?? ''), ENT_QUOTES) ?>', <?= (int)($sesion['total_asistencias'] ?? 0) ?>)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-100 text-red-800 rounded-lg text-sm font-medium active:bg-red-200">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Paginación -->
        <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1):
            $filterQs = http_build_query(array_filter([
                'search'      => $filters['search']      ?? '',
                'estado'      => $filters['estado']      ?? '',
                'fecha_desde' => $filters['fecha_desde'] ?? '',
                'fecha_hasta' => $filters['fecha_hasta'] ?? '',
            ]));
            $filterQs = $filterQs ? '&' . $filterQs : '';
        ?>
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-600">
                Página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?>
                &bull; <?= $pagination['total'] ?> sesiones
            </p>
            <div class="flex gap-1">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=sesiones&p=<?= $pagination['current_page'] - 1 ?><?= $filterQs ?>"
                   class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Anterior</a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="?page=sesiones&p=<?= $pagination['current_page'] + 1 ?><?= $filterQs ?>"
                   class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Siguiente</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Formulario oculto para eliminar sesión con motivo -->
<form id="formEliminar" method="POST" action="index.php?page=sesiones&action=delete" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="id" id="deleteId" value="">
    <input type="hidden" name="motivo" id="deleteMotivo" value="">
    <input type="hidden" name="force_delete" id="forceDelete" value="0">
</form>

<script>
function copiarEnlaceSesion(btn, url) {
    const original = btn.innerHTML;
    const copy = () => {
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
        setTimeout(() => { btn.innerHTML = original; }, 2000);
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(copy);
    } else {
        const ta = document.createElement('textarea');
        ta.value = url; ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        copy();
    }
}

function _pedirMotivoEliminar(id, nombre, forzar) {
    Swal.fire({
        title: '¿Eliminar sesión?',
        html: `<p class="text-sm text-gray-600 mb-3">Curso: <strong>${nombre}</strong></p>
               <label class="block text-sm font-medium text-gray-700 text-left mb-1">Motivo de eliminación <span class="text-red-500">*</span></label>
               <textarea id="motivoInput" class="swal2-textarea w-full" rows="3" maxlength="500"
                 placeholder="Indique el motivo (mínimo 10 caracteres)..." style="font-size:13px;"></textarea>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar definitivamente',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        preConfirm: () => {
            const motivo = document.getElementById('motivoInput').value.trim();
            if (motivo.length < 10) {
                Swal.showValidationMessage('El motivo debe tener al menos 10 caracteres.');
                return false;
            }
            if (motivo.length > 500) {
                Swal.showValidationMessage('El motivo no puede superar los 500 caracteres.');
                return false;
            }
            return motivo;
        }
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value     = id;
            document.getElementById('deleteMotivo').value = result.value;
            document.getElementById('forceDelete').value  = forzar ? '1' : '0';
            document.getElementById('formEliminar').submit();
        }
    });
}

function confirmarEliminar(id, nombre, asistencias) {
    asistencias = parseInt(asistencias) || 0;
    if (asistencias > 0) {
        Swal.fire({
            title: '¡Esta sesión tiene registros!',
            html: `La sesión tiene <strong>${asistencias}</strong> registro(s) de asistencia.<br><br>
                   Si la eliminas, <strong>se borrarán también todos esos registros</strong>.<br>¿Deseas continuar?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        }).then(warn => {
            if (warn.isConfirmed) _pedirMotivoEliminar(id, nombre, true);
        });
    } else {
        _pedirMotivoEliminar(id, nombre, false);
    }
}

function activarSesion(form, nombre) {
    Swal.fire({
        title: '¿Activar sesión?',
        html: `La sesión <strong>${nombre}</strong> quedará activa y los estudiantes podrán registrar asistencia de inmediato.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d97706',
    }).then(r => { if (r.isConfirmed) form.submit(); });
}

function finalizarSesion(form, nombre) {
    Swal.fire({
        title: '¿Finalizar sesión?',
        html: `La sesión <strong>${nombre}</strong> quedará cerrada y los estudiantes ya no podrán registrar asistencia.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ea580c',
    }).then(r => { if (r.isConfirmed) form.submit(); });
}

const formSesion      = document.getElementById('formSesion');
const btnNuevaSesion  = document.getElementById('btnNuevaSesion');
const btnCancelarForm = document.getElementById('btnCancelarForm');
const formTitle       = document.getElementById('formTitle');
const sesionForm      = document.getElementById('sesionForm');
const sesionId        = document.getElementById('sesionId');

if (btnNuevaSesion) {
    btnNuevaSesion.addEventListener('click', () => {
        formTitle.textContent = 'Nueva Sesión';
        sesionId.value = '';
        sesionForm.action = 'index.php?page=sesiones&action=create';
        document.getElementById('sesionFecha').value = new Date().toISOString().split('T')[0];
        formSesion.classList.remove('hidden');
        formSesion.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

if (btnCancelarForm) {
    btnCancelarForm.addEventListener('click', () => formSesion.classList.add('hidden'));
}

function editarSesion(sesion) {
    formTitle.textContent = 'Editar Sesión';
    sesionId.value = sesion.id;
    sesionForm.action = 'index.php?page=sesiones&action=edit';
    document.getElementById('sesionCurso').value      = sesion.curso_id;
    document.getElementById('sesionFecha').value      = sesion.fecha;
    const horaIni = sesion.hora_inicio ? sesion.hora_inicio.slice(0, 5) : '';
    const horaFin = sesion.hora_fin    ? sesion.hora_fin.slice(0, 5)    : '';
    document.getElementById('sesionHoraInicio').value = horaIni;
    // Solo pre-rellena hora_fin si es mayor que hora_inicio (evita datos corruptos
    // que bloquean el formulario con "hora fin debe ser posterior a hora inicio")
    document.getElementById('sesionHoraFin').value    = (horaFin && horaFin > horaIni) ? horaFin : '';
    document.getElementById('sesionEstado').value     = sesion.estado;
    document.getElementById('sesionAula').value       = sesion.aula  || '';
    document.getElementById('sesionSede').value       = sesion.sede  || '';
    formSesion.classList.remove('hidden');
    formSesion.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<?php
$content = ob_get_clean();
require_once '../app/views/layouts/base.php';
