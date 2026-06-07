<?php
// Incluir componentes necesarios
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';

// Configuración del layout
$pageTitle = 'Iniciar Sesión - Sistema de Asistencia';
$bodyClass = 'min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100';
$showNavbar = false;
$showFooter = false;

// Iniciar captura de contenido
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
                Sistema de Asistencia
            </h2>
            <p class="text-sm text-gray-600">
                Universidad Tecnológica
            </p>
        </div>

        <!-- Formulario de login -->
        <?php
        echo renderCard([
            'content' => function() use ($error) {
                // El error se mostrará con SweetAlert2 en JavaScript
                // if (!empty($error)) {
                //     echo renderAlert([
                //         'type' => 'error',
                //         'message' => $error,
                //         'icon' => 'fas fa-exclamation-circle',
                //         'dismissible' => false
                //     ]);
                // }
                ?>
                <form method="POST" action="index.php?page=login" class="space-y-6">
                    <?php
                    // Token CSRF
                    echo '<input type="hidden" name="csrf_token" value="' . ($_SESSION['csrf_token'] ?? '') . '">';
                    
                    // Campo de usuario
                    echo renderInput([
                        'name' => 'username',
                        'label' => 'Usuario',
                        'type' => 'text',
                        'icon' => 'fas fa-user',
                        'placeholder' => 'Ingrese su usuario',
                        'required' => true
                    ]);
                    
                    // Campo de contraseña
                    echo renderInput([
                        'name' => 'password',
                        'label' => 'Contraseña',
                        'type' => 'password',
                        'icon' => 'fas fa-lock',
                        'placeholder' => 'Ingrese su contraseña',
                        'required' => true
                    ]);
                    
                    // Botón de envío
                    echo renderButton('Iniciar Sesión', [
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'size' => 'lg',
                        'icon' => 'fas fa-sign-in-alt',
                        'fullWidth' => true,
                        'extraClasses' => 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 shadow-lg'
                    ]);
                    ?>
                </form>
                
                <!-- Footer del formulario -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Acceso seguro al sistema
                        </p>
                    </div>
                </div>
                <?php
            },
            'class' => 'bg-white rounded-xl shadow-2xl overflow-hidden',
            'padding' => 'px-8 py-8'
        ]);
        ?>
        
        <!-- Información adicional -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                ¿Olvidaste tu contraseña?
                <a href="index.php?page=forgot-password" class="font-medium text-blue-600 hover:text-blue-500 transition duration-200">
                    Recupérala aquí
                </a>
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS personalizado para el login
$customCSS[] = '
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.max-w-md { animation: fadeInUp 0.5s ease-out; }
';

// JavaScript personalizado para mostrar errores con SweetAlert2
$customJS[] = !empty($error)
    ? 'Swal.fire({
        icon: "error",
        title: "Error de acceso",
        text: ' . json_encode((string)$error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ',
        confirmButtonText: "Intentar de nuevo",
        confirmButtonColor: "#dc2626"
    });'
    : '';

// Incluir el layout base
require_once '../app/views/layouts/base.php';
?>