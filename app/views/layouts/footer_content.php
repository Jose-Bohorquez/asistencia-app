<footer class="bg-gradient-to-r from-blue-800 to-blue-900 text-white py-6 mt-8 shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <!-- Logo y descripción -->
            <div class="flex flex-col items-center md:items-start">
                <div class="flex items-center mb-2">
                    <i class="fas fa-graduation-cap text-blue-300 text-xl mr-2"></i>
                    <span class="text-lg font-bold"><?= APP_NAME ?></span>
                </div>
                <p class="text-blue-200 text-sm text-center md:text-left">
                    Sistema de gestión de asistencia académica
                </p>
            </div>
            
            <!-- Links útiles -->
            <div class="flex flex-col items-center md:items-end">
                <div class="flex space-x-6 mb-2">
                    <a href="index.php?page=help" class="text-blue-200 hover:text-white transition-colors duration-200 text-sm">
                        <i class="fas fa-question-circle mr-1"></i>Ayuda
                    </a>
                    <a href="index.php?page=contact" class="text-blue-200 hover:text-white transition-colors duration-200 text-sm">
                        <i class="fas fa-envelope mr-1"></i>Contacto
                    </a>
                    <a href="index.php?page=privacy" class="text-blue-200 hover:text-white transition-colors duration-200 text-sm">
                        <i class="fas fa-shield-alt mr-1"></i>Privacidad
                    </a>
                </div>
                <p class="text-blue-200 text-xs text-center md:text-right">
                    Desarrollado por <span class="font-medium">Trae AI</span>
                </p>
            </div>
        </div>
        
        <!-- Línea divisoria -->
        <div class="border-t border-blue-700 mt-4 pt-4">
            <div class="flex flex-col md:flex-row justify-between items-center text-xs text-blue-200">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos los derechos reservados.</p>
                <p class="mt-2 md:mt-0">
                    Versión 2.0 - Última actualización: <?= date('d/m/Y') ?>
                </p>
            </div>
        </div>
    </div>
</footer>