<?php
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';

$pageTitle = 'Nueva contraseña - Sistema de Asistencia';
$bodyClass = 'min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100';
$showNavbar = false;
$showFooter = false;

// $tokenValido y $tokenStr deben ser pasadas por el controlador
$tokenValido = $tokenValido ?? false;
$tokenStr    = $tokenStr    ?? '';

ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-8 px-4">
    <div class="w-full max-w-sm sm:max-w-md space-y-6">
        <div class="text-center">
            <div class="mx-auto h-24 w-24 mb-6">
                <img class="h-full w-full object-contain" src="assets/img/logo.png" alt="Logo UT">
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
                Nueva contraseña
            </h2>
            <p class="text-sm text-gray-600">
                Escoge una contraseña segura para tu cuenta
            </p>
        </div>

        <?php if ($tokenValido): ?>
        <?php
        echo renderCard([
            'content' => function() use ($tokenStr) {
                ?>
                <form method="POST" action="index.php?page=reset-password" id="formReset" class="space-y-6">
                    <?php echo '<input type="hidden" name="csrf_token" value="' . ($_SESSION['csrf_token'] ?? '') . '">'; ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($tokenStr) ?>">

                    <?php
                    echo renderInput([
                        'name'        => 'password',
                        'label'       => 'Nueva contraseña',
                        'type'        => 'password',
                        'icon'        => 'fas fa-lock',
                        'placeholder' => 'Mínimo 8 caracteres',
                        'required'    => true,
                    ]);

                    echo renderInput([
                        'name'        => 'password_confirm',
                        'label'       => 'Confirmar contraseña',
                        'type'        => 'password',
                        'icon'        => 'fas fa-lock',
                        'placeholder' => 'Repite tu nueva contraseña',
                        'required'    => true,
                    ]);

                    echo renderButton('Guardar contraseña', [
                        'type'         => 'primary',
                        'buttonType'   => 'submit',
                        'size'         => 'lg',
                        'icon'         => 'fas fa-check',
                        'fullWidth'    => true,
                        'extraClasses' => 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 shadow-lg',
                    ]);
                    ?>
                </form>
                <?php
            },
            'class'   => 'bg-white rounded-xl shadow-2xl overflow-hidden',
            'padding' => 'px-8 py-8',
        ]);
        ?>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden px-8 py-8 text-center">
            <div class="text-red-500 mb-4"><i class="fas fa-times-circle fa-3x"></i></div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Enlace inválido o expirado</h3>
            <p class="text-sm text-gray-600 mb-6">Este enlace de recuperación ya no es válido. Solicita uno nuevo.</p>
            <a href="index.php?page=forgot-password"
               class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition duration-200">
                Solicitar nuevo enlace
            </a>
        </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="index.php?page=login" class="text-sm font-medium text-blue-600 hover:text-blue-500 transition duration-200">
                <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$customCSS[] = '
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.max-w-md { animation: fadeInUp 0.5s ease-out; }
';

$customJS[] = '';

if (!empty($error)) {
    $customJS[] = 'Swal.fire({
        icon: "error",
        title: "Error",
        text: ' . json_encode((string)$error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ',
        confirmButtonColor: "#dc2626"
    });';
}

// Validación client-side de contraseñas
if ($tokenValido) {
    $customJS[] = '
document.getElementById("formReset").addEventListener("submit", function(e) {
    var p1 = document.querySelector("[name=password]").value;
    var p2 = document.querySelector("[name=password_confirm]").value;
    if (p1.length < 8) {
        e.preventDefault();
        Swal.fire({ icon: "error", title: "Contraseña muy corta", text: "La contraseña debe tener al menos 8 caracteres.", confirmButtonColor: "#dc2626" });
        return;
    }
    if (p1 !== p2) {
        e.preventDefault();
        Swal.fire({ icon: "error", title: "No coinciden", text: "Las contraseñas no coinciden.", confirmButtonColor: "#dc2626" });
        return;
    }
});
';
}

require_once '../app/views/layouts/base.php';
