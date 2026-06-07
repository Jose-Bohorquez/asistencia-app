<?php
$pageTitle  = 'Activar cuenta - Sistema de Asistencia';
$bodyClass  = 'min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100';
$showNavbar = false;
$showFooter = false;

ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">

        <!-- Cabecera -->
        <div class="text-center">
            <div class="mx-auto h-20 w-20 mb-4 bg-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-user-check text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-1">Activar cuenta</h2>
            <p class="text-sm text-gray-600">
                Hola, <strong><?= htmlspecialchars($usuario['nombre'] ?? '') ?></strong>.
                Completa tu registro para comenzar.
            </p>
        </div>

        <!-- Errores -->
        <?php if (!empty($errores)): ?>
            <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 shrink-0"></i>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="bg-white rounded-xl shadow-2xl px-8 py-8">
            <form method="POST" action="index.php?page=activacion&token=<?= urlencode($token) ?>" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-user text-gray-400 mr-1"></i> Nombre completo
                    </label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?= htmlspecialchars($prev['nombre'] ?? $usuario['nombre'] ?? '') ?>"
                        maxlength="100"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                        placeholder="Tu nombre completo"
                    >
                </div>

                <!-- Email (solo lectura, informativo) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-envelope text-gray-400 mr-1"></i> Correo electrónico
                    </label>
                    <input
                        type="email"
                        value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
                        disabled
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500 cursor-not-allowed"
                    >
                    <p class="text-xs text-gray-400 mt-1">El correo no se puede modificar.</p>
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-lock text-gray-400 mr-1"></i> Contraseña
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            required
                            class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition pr-10"
                            placeholder="Mínimo 8 caracteres"
                        >
                        <button type="button" onclick="togglePass('password', this)" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirmar contraseña -->
                <div>
                    <label for="password_conf" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-lock text-gray-400 mr-1"></i> Confirmar contraseña
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password_conf"
                            name="password_conf"
                            minlength="8"
                            required
                            class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition pr-10"
                            placeholder="Repite la contraseña"
                        >
                        <button type="button" onclick="togglePass('password_conf', this)" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full flex justify-center items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg"
                >
                    <i class="fas fa-check-circle"></i>
                    Activar mi cuenta
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Conexión segura · Este enlace expira en 48 horas
                </p>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();

$customJS[] = '
<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

// Validación client-side: contraseñas coinciden
document.querySelector("form").addEventListener("submit", function(e) {
    const p1 = document.getElementById("password").value;
    const p2 = document.getElementById("password_conf").value;
    if (p1 !== p2) {
        e.preventDefault();
        Swal.fire({
            icon: "error",
            title: "Contraseñas no coinciden",
            text: "Asegúrate de que ambas contraseñas sean iguales.",
            confirmButtonColor: "#2563eb"
        });
    }
});
</script>
';

require_once __DIR__ . '/../layouts/base.php';
