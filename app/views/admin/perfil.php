<?php
$pageTitle  = 'Mi perfil';
$bodyClass  = 'bg-gray-100';
$showNavbar = true;
$showFooter = true;

ob_start();
$uid = $usuario['id'] ?? 0;
?>

<div class="max-w-2xl mx-auto py-6 px-3 sm:px-4 space-y-5">

    <!-- Encabezado -->
    <div class="flex items-center gap-4">
        <div class="relative shrink-0">
            <?php if (!empty($usuario['foto_perfil'])): ?>
                <img
                    src="index.php?page=avatar&uid=<?= (int)$uid ?>"
                    alt="Foto de perfil"
                    class="h-20 w-20 rounded-full object-cover border-4 border-white shadow-md"
                >
            <?php else: ?>
                <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center border-4 border-white shadow-md">
                    <i class="fas fa-user text-white text-3xl"></i>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></h1>
            <p class="text-sm text-gray-500">
                <?= htmlspecialchars($usuario['email'] ?? '') ?>
                &bull; <span class="capitalize"><?= htmlspecialchars($usuario['rol'] ?? '') ?></span>
            </p>
            <?php if (!empty($usuario['ultimo_acceso'])): ?>
            <p class="text-xs text-gray-400 mt-0.5">
                Último acceso: <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($flash_message)): ?>
        <div class="rounded-lg px-4 py-3 text-sm font-medium
            <?= $flash_message['type'] === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700' ?>">
            <i class="fas <?= $flash_message['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
            <?= htmlspecialchars($flash_message['text']) ?>
        </div>
    <?php endif; ?>

    <!-- Datos personales -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">
            <i class="fas fa-id-card text-blue-500 mr-2"></i> Datos personales
        </h2>
        <form method="POST" action="index.php?page=perfil&action=update" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre completo <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
                    maxlength="100"
                    required
                    class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                >
            </div>

            <!-- Correo (sólo lectura) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                <input
                    type="email"
                    value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
                    disabled
                    class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-400 cursor-not-allowed"
                >
                <p class="text-xs text-gray-400 mt-1">Para cambiar el correo contacte al administrador.</p>
            </div>

            <!-- Teléfono y Documento -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
                        maxlength="30"
                        placeholder="Ej: 3001234567"
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                    >
                </div>
                <div>
                    <label for="documento" class="block text-sm font-medium text-gray-700 mb-1">
                        Documento de identidad
                    </label>
                    <input
                        type="text"
                        id="documento"
                        name="documento"
                        value="<?= htmlspecialchars($usuario['documento'] ?? '') ?>"
                        maxlength="30"
                        placeholder="Cédula o pasaporte"
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                    >
                </div>
            </div>

            <!-- Fecha de nacimiento -->
            <div>
                <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha de nacimiento
                </label>
                <input
                    type="date"
                    id="fecha_nacimiento"
                    name="fecha_nacimiento"
                    value="<?= htmlspecialchars($usuario['fecha_nacimiento'] ?? '') ?>"
                    max="<?= date('Y-m-d') ?>"
                    class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition"
                >
            </div>

            <!-- Notificaciones -->
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <input
                    type="checkbox"
                    id="notif_email"
                    name="notif_email"
                    value="1"
                    <?= !empty($usuario['notif_email']) ? 'checked' : '' ?>
                    class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                >
                <div>
                    <label for="notif_email" class="text-sm font-medium text-gray-700 cursor-pointer">
                        Recibir notificaciones por correo
                    </label>
                    <p class="text-xs text-gray-400">Actividad de asistencia y avisos del sistema.</p>
                </div>
            </div>

            <div class="flex justify-end pt-1">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2.5 px-6 rounded-lg transition min-h-[44px]"
                >
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Foto de perfil -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">
            <i class="fas fa-camera text-blue-500 mr-2"></i> Foto de perfil
        </h2>
        <form method="POST" action="index.php?page=perfil&action=foto" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="shrink-0 self-start">
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="index.php?page=avatar&uid=<?= (int)$uid ?>"
                             alt="Foto actual"
                             class="h-16 w-16 rounded-full object-cover border-2 border-gray-200">
                    <?php else: ?>
                        <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-user text-gray-400 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <input
                        type="file"
                        name="foto"
                        id="foto"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        class="block w-full text-sm text-gray-500
                               file:mr-3 file:py-2.5 file:px-4 file:rounded-lg file:border-0
                               file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-600
                               hover:file:bg-blue-100 file:cursor-pointer"
                    >
                    <p class="text-xs text-gray-400 mt-1.5">JPG, PNG o WebP &middot; máximo 2 MB</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2.5 px-6 rounded-lg transition min-h-[44px]">
                    <i class="fas fa-upload"></i> Subir foto
                </button>
            </div>
        </form>
    </div>

    <!-- Cambiar contraseña -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">
            <i class="fas fa-lock text-blue-500 mr-2"></i> Cambiar contraseña
        </h2>
        <form method="POST" action="index.php?page=perfil&action=password" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual <span class="text-red-500">*</span></label>
                <input type="password" id="current_password" name="current_password" required
                       class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="new_password" name="new_password" required minlength="8"
                           class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                           class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
            </div>
            <p class="text-xs text-gray-400">Mínimo 8 caracteres.</p>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-gray-700 hover:bg-gray-800 active:bg-gray-900 text-white font-semibold py-2.5 px-6 rounded-lg transition min-h-[44px]">
                    <i class="fas fa-key"></i> Cambiar contraseña
                </button>
            </div>
        </form>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/base.php';
