<?php
$pageTitle  = 'Enlace inválido - Sistema de Asistencia';
$bodyClass  = 'min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100';
$showNavbar = false;
$showFooter = false;

ob_start();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full text-center space-y-6">

        <div class="mx-auto h-20 w-20 bg-red-100 rounded-full flex items-center justify-center">
            <i class="fas fa-link-slash text-red-500 text-3xl"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-900">Enlace no válido</h2>

        <div class="bg-white rounded-xl shadow-lg px-8 py-6">
            <p class="text-gray-600 text-sm leading-relaxed">
                <?= htmlspecialchars($mensaje ?? 'El enlace de activación no es válido o ha expirado.') ?>
            </p>

            <div class="mt-6">
                <a
                    href="index.php?page=login"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg transition duration-200"
                >
                    <i class="fas fa-arrow-left"></i>
                    Ir al inicio de sesión
                </a>
            </div>

            <p class="text-xs text-gray-400 mt-4">
                Si crees que es un error, contacta al administrador del sistema.
            </p>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/base.php';
