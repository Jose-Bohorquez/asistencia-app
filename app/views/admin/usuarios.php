<?php
require_once '../app/views/components/card.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';

$pageTitle = 'Usuarios - ' . APP_NAME;
$csrfToken = $_SESSION['csrf_token'] ?? '';
$userRol   = $_SESSION['user_rol'] ?? '';
$flash     = $flash_message ?? null;

ob_start();
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h1>
            <p class="text-sm text-gray-500 mt-1">Administra los usuarios y sus roles en el sistema</p>
        </div>
        <?php if (in_array($userRol, ['super_admin'])): ?>
        <button id="btnNuevoUsuario"
                class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors duration-200">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="rounded-lg px-4 py-3 text-sm font-medium <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Formulario (oculto) -->
    <?php if (in_array($userRol, ['super_admin'])): ?>
    <div id="formUsuario" class="hidden bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="bg-gradient-to-r from-blue-700 to-blue-800 px-6 py-4 rounded-t-xl">
            <h2 class="text-white font-semibold text-lg" id="formTitle">Nuevo Usuario</h2>
        </div>
        <form id="usuarioForm" method="POST" action="index.php?page=usuarios&action=create" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" id="userId" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuario <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="inputUsername" required maxlength="50"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" id="inputNombre" required maxlength="100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" name="email" id="inputEmail" maxlength="100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select name="rol" id="inputRol" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="profesor">Profesor</option>
                        <option value="admin">Administrador</option>
                        <option value="super_admin">Super Administrador</option>
                    </select>
                </div>
                <div id="campoPassword">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña
                        <span id="pwdRequired" class="text-gray-400 hidden">(se enviará enlace de activación por email)</span>
                        <span id="pwdOptional" class="text-gray-400 hidden">(dejar vacío para mantener)</span>
                    </label>
                    <input type="password" name="password" id="inputPassword" minlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Se enviará enlace de activación si se deja vacío">
                    <p id="pwdHintNew" class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle"></i> Si tiene email, se enviará un enlace de activación automáticamente.
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="activo" id="inputActivo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
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
        <input type="hidden" name="page" value="usuarios">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   placeholder="Buscar por nombre, usuario o email..."
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select name="rol"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Todos los roles</option>
                <?php foreach (($roles ?? []) as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= ($filters['rol'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="flex gap-2">
                <select name="activo"
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="" <?= ($filters['activo'] ?? '1') === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="1" <?= ($filters['activo'] ?? '1') == 1  ? 'selected' : '' ?>>Solo activos</option>
                    <option value="0" <?= isset($filters['activo']) && (int)($filters['activo']) === 0 ? 'selected' : '' ?>>Solo inactivos</option>
                </select>
                <button type="submit"
                        class="px-3 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition-colors">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <!-- Tabla de usuarios -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <?php if (empty($usuarios)): ?>
        <div class="text-center py-16 text-gray-500">
            <i class="fas fa-users text-4xl text-gray-300 mb-4 block"></i>
            <p class="text-lg font-medium">No hay usuarios registrados</p>
        </div>
        <?php else: ?>
        <!-- Desktop -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Creado</th>
                        <?php if (in_array($userRol, ['super_admin'])): ?>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($usuarios as $usuario): ?>
                    <?php
                        $rolConfig = [
                            'super_admin' => 'bg-purple-100 text-purple-800',
                            'admin'       => 'bg-blue-100 text-blue-800',
                            'profesor'    => 'bg-green-100 text-green-800',
                        ];
                        $rolClass = $rolConfig[$usuario['rol']] ?? 'bg-gray-100 text-gray-700';
                        $rolLabel = match($usuario['rol']) {
                            'super_admin' => 'Super Admin',
                            'admin'       => 'Admin',
                            'profesor'    => 'Profesor',
                            default       => ucfirst($usuario['rol']),
                        };
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($usuario['username']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($usuario['nombre']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($usuario['email'] ?: '—') ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $rolClass ?>">
                                <?= $rolLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $ec = $usuario['estado_cuenta'] ?? ($usuario['activo'] ? 'activo' : 'inactivo');
                                $ecConfig = [
                                    'activo'               => ['text-green-700',  'bg-green-500',  'Activo'],
                                    'pendiente_activacion' => ['text-yellow-700', 'bg-yellow-400', 'Pendiente'],
                                    'inactivo'             => ['text-gray-400',   'bg-gray-400',   'Inactivo'],
                                ];
                                [$ecText, $ecDot, $ecLabel] = $ecConfig[$ec] ?? ['text-gray-400', 'bg-gray-400', ucfirst($ec)];
                            ?>
                            <span class="inline-flex items-center gap-1 text-xs font-medium <?= $ecText ?>">
                                <span class="w-1.5 h-1.5 <?= $ecDot ?> rounded-full"></span>
                                <?= $ecLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <?= isset($usuario['created_at']) ? date('d/m/Y', strtotime($usuario['created_at'])) : '—' ?>
                        </td>
                        <?php if (in_array($userRol, ['super_admin'])): ?>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <?php if ($usuario['activo']): ?>
                                <?php if (($usuario['estado_cuenta'] ?? '') === 'pendiente_activacion'): ?>
                                <button type="button"
                                        onclick="reenviarActivacion(<?= (int)$usuario['id'] ?>, '<?= htmlspecialchars($usuario['email'], ENT_QUOTES) ?>')"
                                        title="Reenviar correo de activación"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-paper-plane"></i> Reenviar
                                </button>
                                <?php endif; ?>
                                <button type="button"
                                        onclick="editarUsuario(<?= htmlspecialchars(json_encode([
                                            'id'       => $usuario['id'],
                                            'username' => $usuario['username'],
                                            'nombre'   => $usuario['nombre'],
                                            'email'    => $usuario['email'],
                                            'rol'      => $usuario['rol'],
                                            'activo'   => $usuario['activo'],
                                        ])) ?>)"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                <button type="button"
                                        onclick="desactivarUsuario(<?= (int)$usuario['id'] ?>, '<?= htmlspecialchars($usuario['username'], ENT_QUOTES) ?>')"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-xs text-gray-400 italic">Inactivo</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden divide-y divide-gray-100">
            <?php foreach ($usuarios as $usuario): ?>
            <?php
                $rolLabel = match($usuario['rol']) {
                    'super_admin' => 'Super Admin',
                    'admin'       => 'Admin',
                    'profesor'    => 'Profesor',
                    default       => ucfirst($usuario['rol']),
                };
                $rolColorsM = [
                    'super_admin' => 'bg-purple-100 text-purple-800',
                    'admin'       => 'bg-blue-100 text-blue-800',
                    'profesor'    => 'bg-green-100 text-green-800',
                ];
                $rolClassM = $rolColorsM[$usuario['rol']] ?? 'bg-gray-100 text-gray-700';
                $ecM = $usuario['estado_cuenta'] ?? ($usuario['activo'] ? 'activo' : 'inactivo');
                $ecLabelM = ['activo' => 'Activo', 'pendiente_activacion' => 'Pendiente', 'inactivo' => 'Inactivo'][$ecM] ?? $ecM;
                $ecColorM = ['activo' => 'text-green-600', 'pendiente_activacion' => 'text-yellow-600', 'inactivo' => 'text-gray-400'][$ecM] ?? 'text-gray-500';
            ?>
            <div class="p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($usuario['nombre']) ?></p>
                        <p class="text-xs text-gray-500">@<?= htmlspecialchars($usuario['username']) ?></p>
                        <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($usuario['email'] ?: '—') ?></p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $rolClassM ?>">
                            <?= $rolLabel ?>
                        </span>
                        <span class="text-xs <?= $ecColorM ?>"><?= $ecLabelM ?></span>
                    </div>
                </div>
                <?php if (in_array($userRol, ['super_admin']) && $usuario['activo']): ?>
                <div class="flex flex-wrap gap-2 mt-2">
                    <?php if (($usuario['estado_cuenta'] ?? '') === 'pendiente_activacion'): ?>
                    <button type="button"
                            onclick="reenviarActivacion(<?= (int)$usuario['id'] ?>, '<?= htmlspecialchars($usuario['email'], ENT_QUOTES) ?>')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm font-medium active:bg-blue-100">
                        <i class="fas fa-paper-plane"></i> Reenviar
                    </button>
                    <?php endif; ?>
                    <button type="button"
                            onclick="editarUsuario(<?= htmlspecialchars(json_encode([
                                'id'       => $usuario['id'],
                                'username' => $usuario['username'],
                                'nombre'   => $usuario['nombre'],
                                'email'    => $usuario['email'],
                                'rol'      => $usuario['rol'],
                                'activo'   => $usuario['activo'],
                            ])) ?>)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg text-sm font-medium active:bg-yellow-100">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                    <button type="button"
                            onclick="desactivarUsuario(<?= (int)$usuario['id'] ?>, '<?= htmlspecialchars($usuario['username'], ENT_QUOTES) ?>')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium active:bg-red-100">
                        <i class="fas fa-user-slash"></i> Desactivar
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1): ?>
        <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
            <p class="text-sm text-gray-500">
                Página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?> &bull; <?= $pagination['total'] ?> usuarios
            </p>
            <div class="flex gap-2">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=usuarios&p=<?= $pagination['current_page'] - 1 ?>"
                   class="inline-flex items-center px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">&#8592; Anterior</a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="?page=usuarios&p=<?= $pagination['current_page'] + 1 ?>"
                   class="inline-flex items-center px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">Siguiente &#8594;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden deactivate form -->
<form id="formDesactivar" method="POST" action="index.php?page=usuarios&action=delete" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="id" id="desactivarId" value="">
</form>

<script>
function desactivarUsuario(id, username) {
    Swal.fire({
        title: '¿Desactivar usuario?',
        text: `El usuario "${username}" quedará inactivo y no podrá iniciar sesión.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Desactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('desactivarId').value = id;
            document.getElementById('formDesactivar').submit();
        }
    });
}

const formUsuario     = document.getElementById('formUsuario');
const btnNuevoUsuario = document.getElementById('btnNuevoUsuario');
const btnCancelarForm = document.getElementById('btnCancelarForm');
const formTitle       = document.getElementById('formTitle');
const usuarioForm     = document.getElementById('usuarioForm');
const userId          = document.getElementById('userId');
const inputPassword   = document.getElementById('inputPassword');
const pwdRequired     = document.getElementById('pwdRequired');
const pwdOptional     = document.getElementById('pwdOptional');

if (btnNuevoUsuario) {
    btnNuevoUsuario.addEventListener('click', () => {
        formTitle.textContent = 'Nuevo Usuario';
        userId.value = '';
        usuarioForm.reset();
        usuarioForm.action = 'index.php?page=usuarios&action=create';
        inputPassword.required = false;
        pwdRequired.classList.remove('hidden');
        pwdOptional.classList.add('hidden');
        document.getElementById('pwdHintNew') && (document.getElementById('pwdHintNew').classList.remove('hidden'));
        formUsuario.classList.remove('hidden');
        formUsuario.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

if (btnCancelarForm) {
    btnCancelarForm.addEventListener('click', () => formUsuario.classList.add('hidden'));
}

function editarUsuario(usuario) {
    formTitle.textContent = 'Editar Usuario';
    userId.value = usuario.id;
    usuarioForm.action = 'index.php?page=usuarios&action=edit';
    document.getElementById('inputUsername').value = usuario.username;
    document.getElementById('inputNombre').value   = usuario.nombre;
    document.getElementById('inputEmail').value    = usuario.email || '';
    document.getElementById('inputRol').value      = usuario.rol;
    document.getElementById('inputActivo').value   = usuario.activo;
    inputPassword.required = false;
    inputPassword.value    = '';
    pwdRequired.classList.add('hidden');
    pwdOptional.classList.remove('hidden');
    document.getElementById('pwdHintNew') && (document.getElementById('pwdHintNew').classList.add('hidden'));
    formUsuario.classList.remove('hidden');
    formUsuario.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function reenviarActivacion(id, email) {
    Swal.fire({
        title: '¿Reenviar correo de activación?',
        html: `Se enviará un nuevo enlace de activación a <strong>${email}</strong>.<br>El enlace anterior quedará invalidado.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>');
        fetch('index.php?page=usuarios&action=resend-activation', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Correo enviado', text: data.message, confirmButtonColor: '#2563eb' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo enviar el correo.', confirmButtonColor: '#dc2626' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor.', confirmButtonColor: '#dc2626' }));
    });
}
</script>

<?php
$content = ob_get_clean();
include '../app/views/layouts/base.php';
