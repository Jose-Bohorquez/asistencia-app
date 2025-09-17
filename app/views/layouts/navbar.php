<nav class="bg-gradient-to-r from-blue-800 to-blue-900 text-white shadow-xl border-b border-blue-700">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-2 sm:py-3">
            <!-- Logo/Brand -->
            <div class="flex items-center">
                <a href="index.php" class="text-lg sm:text-xl font-bold hover:text-blue-200 transition-colors duration-200">
                    <i class="fas fa-graduation-cap mr-2 text-blue-300"></i>
                    <span class="hidden sm:inline"><?= APP_NAME ?></span>
                    <span class="sm:hidden">SA</span>
                </a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">
                <?php if (in_array($_SESSION['rol'], ['admin', 'profesor', 'super_admin'])): ?>
                <a href="index.php?page=dashboard" class="navbar-link px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-all duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'dashboard') ? 'bg-blue-700' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="index.php?page=cursos" class="navbar-link px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-all duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'cursos') ? 'bg-blue-700' : '' ?>">
                    <i class="fas fa-book mr-2"></i>Cursos
                </a>
                <a href="index.php?page=sesiones" class="navbar-link px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-all duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'sesiones') ? 'bg-blue-700' : '' ?>">
                    <i class="fas fa-calendar-alt mr-2"></i>Sesiones
                </a>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['rol'], ['super_admin', 'admin'])): ?>
                <a href="index.php?page=usuarios" class="navbar-link px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-all duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'usuarios') ? 'bg-blue-700' : '' ?>">
                    <i class="fas fa-users mr-2"></i>Usuarios
                </a>
                <a href="index.php?page=programas" class="navbar-link px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-all duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'programas') ? 'bg-blue-700' : '' ?>">
                    <i class="fas fa-graduation-cap mr-2"></i>Programas
                </a>
                <?php endif; ?>
                
                <!-- User Dropdown -->
                <div class="relative">
                    <button id="userMenuButton" class="flex items-center px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-2">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <span class="hidden lg:inline"><?= $_SESSION['nombre'] ?></span>
                        <i class="fas fa-chevron-down ml-2 text-xs transition-transform duration-200" id="userMenuIcon"></i>
                    </button>
                    <div id="userDropdown" class="navbar-dropdown absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm text-gray-900 font-medium"><?= $_SESSION['nombre'] ?></p>
                            <p class="text-xs text-gray-500 capitalize"><?= $_SESSION['rol'] ?></p>
                        </div>
                        <a href="index.php?page=profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-user-circle mr-3 text-gray-400"></i>Mi Perfil
                        </a>
                        <a href="index.php?page=settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-cog mr-3 text-gray-400"></i>Configuración
                        </a>
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <button onclick="confirmLogout()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>Cerrar sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobileMenuButton" class="p-2 rounded-md hover:bg-blue-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <i class="fas fa-bars text-lg" id="mobileMenuIcon"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobileMenu" class="mobile-menu md:hidden bg-blue-900 border-t border-blue-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <?php if (in_array($_SESSION['rol'], ['admin', 'profesor', 'super_admin'])): ?>
                <a href="index.php?page=dashboard" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-800 transition-colors duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'dashboard') ? 'bg-blue-800' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="index.php?page=cursos" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-800 transition-colors duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'cursos') ? 'bg-blue-800' : '' ?>">
                    <i class="fas fa-book mr-3"></i>Cursos
                </a>
                <a href="index.php?page=sesiones" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-800 transition-colors duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'sesiones') ? 'bg-blue-800' : '' ?>">
                    <i class="fas fa-calendar-alt mr-3"></i>Sesiones
                </a>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['rol'], ['super_admin', 'admin'])): ?>
                <a href="index.php?page=usuarios" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-800 transition-colors duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'usuarios') ? 'bg-blue-800' : '' ?>">
                    <i class="fas fa-users mr-3"></i>Usuarios
                </a>
                <a href="index.php?page=programas" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-800 transition-colors duration-200 <?= (isset($_GET['page']) && $_GET['page'] === 'programas') ? 'bg-blue-800' : '' ?>">
                    <i class="fas fa-graduation-cap mr-3"></i>Programas
                </a>
                <?php endif; ?>
                
                <div class="border-t border-blue-700 pt-3 mt-3">
                    <div class="px-3 py-2">
                        <p class="text-sm font-medium"><?= $_SESSION['nombre'] ?></p>
                        <p class="text-xs text-blue-300 capitalize"><?= $_SESSION['rol'] ?></p>
                    </div>
                    <a href="index.php?page=profile" class="block px-3 py-2 text-sm font-medium text-blue-300 hover:bg-blue-800 hover:text-blue-100 transition-colors duration-200">
                        <i class="fas fa-user-circle mr-3"></i>Mi Perfil
                    </a>
                    <a href="index.php?page=settings" class="block px-3 py-2 text-sm font-medium text-blue-300 hover:bg-blue-800 hover:text-blue-100 transition-colors duration-200">
                        <i class="fas fa-cog mr-3"></i>Configuración
                    </a>
                    <button onclick="confirmLogout()" class="w-full text-left px-3 py-2 text-sm font-medium text-red-300 hover:bg-red-900 hover:text-red-100 transition-colors duration-200 flex items-center">
                        <i class="fas fa-sign-out-alt mr-3"></i>Cerrar sesión
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuButton').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobileMenu');
        const icon = document.getElementById('mobileMenuIcon');
        
        mobileMenu.classList.toggle('show');
        
        if (mobileMenu.classList.contains('show')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
    
    // User dropdown toggle
    document.getElementById('userMenuButton').addEventListener('click', function() {
        const dropdown = document.getElementById('userDropdown');
        const icon = document.getElementById('userMenuIcon');
        
        dropdown.classList.toggle('show');
        icon.classList.toggle('rotate-180');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('userMenuButton');
        const dropdown = document.getElementById('userDropdown');
        const icon = document.getElementById('userMenuIcon');
        
        if (!userMenu.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
            icon.classList.remove('rotate-180');
        }
    });
    
    // Logout confirmation
    function confirmLogout() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: '¿Estás seguro de que quieres cerrar tu sesión?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-lg',
                confirmButton: 'rounded-md',
                cancelButton: 'rounded-md'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Cerrando sesión...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = 'index.php?page=logout';
                }, 1000);
            }
        });
    }
</script>