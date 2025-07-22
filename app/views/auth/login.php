<?php include '../app/views/layouts/header.php'; ?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
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
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="px-8 py-8">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?= $error ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php?page=login" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-gray-400"></i>Usuario
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out hover:border-gray-400" 
                               placeholder="Ingrese su usuario"
                               required>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>Contraseña
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out hover:border-gray-400" 
                               placeholder="Ingrese su contraseña"
                               required>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 ease-in-out transform hover:scale-105 shadow-lg">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-sign-in-alt text-blue-300 group-hover:text-blue-200"></i>
                            </span>
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Footer del formulario -->
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-200">
                <div class="text-center">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Acceso seguro al sistema
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Información adicional -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                ¿Problemas para acceder? 
                <a href="#" class="font-medium text-blue-600 hover:text-blue-500 transition duration-200">
                    Contactar soporte
                </a>
            </p>
        </div>
    </div>
</div>

<style>
/* Animaciones adicionales */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.max-w-md {
    animation: fadeInUp 0.6s ease-out;
}

/* Efectos hover mejorados */
input:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

button:hover {
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}

/* Responsividad mejorada */
@media (max-width: 640px) {
    .max-w-md {
        margin: 1rem;
    }
    
    .px-8 {
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
    }
    
    .py-8 {
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem !important;
    }
    
    .text-3xl {
        font-size: 1.875rem !important;
    }
    
    .h-24, .w-24 {
        height: 5rem !important;
        width: 5rem !important;
    }
}
</style>

<?php include '../app/views/layouts/footer.php'; ?>