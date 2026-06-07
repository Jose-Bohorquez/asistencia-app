<?php
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';

$pageTitle = 'Recuperar contraseña - Sistema de Asistencia';
$bodyClass = 'min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100';
$showNavbar = false;
$showFooter = false;

ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-8 px-4">
    <div class="w-full max-w-sm sm:max-w-md space-y-6">
        <!-- Logo y título -->
        <div class="text-center">
            <div class="mx-auto h-24 w-24 mb-6">
                <img class="h-full w-full object-contain" src="assets/img/logo.png" alt="Logo UT">
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
                Recuperar contraseña
            </h2>
            <p class="text-sm text-gray-600">
                Ingresa tu correo y te enviaremos un enlace
            </p>
        </div>

        <?php
        echo renderCard([
            'content' => function() {
                ?>
                <form method="POST" action="index.php?page=forgot-password" class="space-y-6">
                    <?php echo '<input type="hidden" name="csrf_token" value="' . ($_SESSION['csrf_token'] ?? '') . '">'; ?>

                    <?php
                    echo renderInput([
                        'name'        => 'email',
                        'label'       => 'Correo electrónico',
                        'type'        => 'email',
                        'icon'        => 'fas fa-envelope',
                        'placeholder' => 'tu@correo.com',
                        'required'    => true,
                    ]);

                    echo renderButton('Enviar enlace', [
                        'type'         => 'primary',
                        'buttonType'   => 'submit',
                        'size'         => 'lg',
                        'icon'         => 'fas fa-paper-plane',
                        'fullWidth'    => true,
                        'extraClasses' => 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 shadow-lg',
                    ]);
                    ?>
                </form>

                <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                    <a href="index.php?page=login" class="text-sm font-medium text-blue-600 hover:text-blue-500 transition duration-200">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
                    </a>
                </div>
                <?php
            },
            'class'   => 'bg-white rounded-xl shadow-2xl overflow-hidden',
            'padding' => 'px-8 py-8',
        ]);
        ?>
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

// Mostrar mensaje de éxito o error con SweetAlert
if (!empty($success)) {
    $customJS[] = 'Swal.fire({
        icon: "success",
        title: "Correo enviado",
        text: ' . json_encode((string)$success, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ',
        confirmButtonColor: "#1d4ed8"
    });';
} elseif (!empty($error)) {
    $customJS[] = 'Swal.fire({
        icon: "error",
        title: "Error",
        text: ' . json_encode((string)$error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ',
        confirmButtonColor: "#dc2626"
    });';
}

require_once '../app/views/layouts/base.php';
