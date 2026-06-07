<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$navNombre   = htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['user_nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$navRol      = $_SESSION['rol'] ?? $_SESSION['user_rol'] ?? '';
$navRolLabel = match($navRol) {
    'super_admin' => 'Super Administrador',
    'admin'       => 'Administrador',
    'profesor'    => 'Profesor',
    default       => ucfirst($navRol),
};
$navInitial  = mb_strtoupper(mb_substr($navNombre, 0, 1, 'UTF-8'), 'UTF-8');
$navFoto     = $_SESSION['foto_perfil'] ?? '';
$navAvatarUid = $_SESSION['user_id'] ?? 0;

function navLink($page, $icon, $label, $currentPage, $mobile = false) {
    $active = $currentPage === $page;
    if ($mobile) {
        $base   = 'block px-3 py-2.5 rounded-md text-sm font-medium transition-colors duration-200 flex items-center gap-3';
        $active ? $cls = "$base bg-blue-800 text-white" : $cls = "$base hover:bg-blue-800 text-blue-100";
    } else {
        $base   = 'px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2 relative';
        $active ? $cls = "$base bg-blue-700 text-white font-semibold" : $cls = "$base hover:bg-blue-700 text-blue-100";
    }
    return '<a href="index.php?page=' . $page . '" class="' . $cls . '">
                <i class="' . $icon . ' ' . ($mobile ? 'w-4 text-center' : '') . '"></i>' . htmlspecialchars($label) . '
            </a>';
}
?>
<nav class="bg-gradient-to-r from-blue-800 to-blue-900 text-white shadow-lg border-b border-blue-700/50 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-14">

            <!-- Marca -->
            <a href="index.php?page=dashboard"
               class="flex items-center gap-2 font-bold text-white hover:text-blue-200 transition-colors duration-200 shrink-0">
                <i class="fas fa-graduation-cap text-blue-300 text-lg"></i>
                <span class="hidden sm:block text-base leading-tight"><?= APP_NAME ?></span>
                <span class="sm:hidden text-sm font-bold">SA</span>
            </a>

            <!-- Nav escritorio -->
            <div class="hidden md:flex items-center gap-1">
                <?= navLink('dashboard', 'fas fa-tachometer-alt', 'Dashboard', $currentPage) ?>
                <?= navLink('cursos',    'fas fa-book',           'Cursos',    $currentPage) ?>
                <?= navLink('sesiones',  'fas fa-calendar-alt',   'Sesiones',  $currentPage) ?>
                <?php if (in_array($navRol, ['super_admin', 'admin'])): ?>
                <?= navLink('usuarios',  'fas fa-users',          'Usuarios',  $currentPage) ?>
                <?= navLink('programas', 'fas fa-graduation-cap', 'Programas', $currentPage) ?>
                <?php endif; ?>

                <!-- Menú usuario -->
                <div class="relative ml-2">
                    <button id="userMenuBtn"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-blue-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <div class="w-8 h-8 rounded-full overflow-hidden border-2 border-blue-400/50 shrink-0 bg-blue-600 flex items-center justify-center text-sm font-bold select-none">
                            <?php if ($navFoto && $navAvatarUid): ?>
                            <img src="index.php?page=avatar&uid=<?= (int)$navAvatarUid ?>"
                                 alt="<?= $navInitial ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span style="display:none" class="w-full h-full items-center justify-center text-white text-sm font-bold"><?= $navInitial ?></span>
                            <?php else: ?>
                            <span class="text-white"><?= $navInitial ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="hidden lg:block text-sm max-w-[120px] truncate"><?= $navNombre ?></span>
                        <i class="fas fa-chevron-down text-xs text-blue-300 transition-transform duration-200" id="userMenuChevron"></i>
                    </button>
                    <div id="userDropdown"
                         class="absolute right-0 top-full mt-1 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-1 z-50
                                opacity-0 invisible -translate-y-1 transition-all duration-200">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900 truncate"><?= $navNombre ?></p>
                            <p class="text-xs text-gray-500 mt-0.5"><?= $navRolLabel ?></p>
                        </div>
                        <a href="index.php?page=perfil"
                           class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                            <i class="fas fa-user-circle text-gray-400 w-4 text-center"></i>Mi Perfil
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <button onclick="confirmLogout()"
                                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i>Cerrar sesión
                        </button>
                    </div>
                </div>
            </div>

            <!-- Botón menú móvil -->
            <button id="mobileMenuBtn"
                    class="md:hidden p-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    aria-label="Abrir menú">
                <i class="fas fa-bars text-lg" id="mobileMenuIcon"></i>
            </button>
        </div>
    </div>

    <!-- Nav móvil -->
    <div id="mobileMenu" class="md:hidden overflow-hidden max-h-0 transition-all duration-300 ease-in-out bg-blue-900/95 border-t border-blue-700/50">
        <div class="px-3 py-3 space-y-0.5">
            <?= navLink('dashboard', 'fas fa-tachometer-alt', 'Dashboard', $currentPage, true) ?>
            <?= navLink('cursos',    'fas fa-book',           'Cursos',    $currentPage, true) ?>
            <?= navLink('sesiones',  'fas fa-calendar-alt',   'Sesiones',  $currentPage, true) ?>
            <?php if (in_array($navRol, ['super_admin', 'admin'])): ?>
            <?= navLink('usuarios',  'fas fa-users',          'Usuarios',  $currentPage, true) ?>
            <?= navLink('programas', 'fas fa-graduation-cap', 'Programas', $currentPage, true) ?>
            <?php endif; ?>

            <div class="border-t border-blue-700/50 mt-2 pt-2 space-y-0.5">
                <div class="px-3 py-2 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-blue-400/50 shrink-0 bg-blue-600 flex items-center justify-center font-bold text-sm">
                        <?php if ($navFoto && $navAvatarUid): ?>
                        <img src="index.php?page=avatar&uid=<?= (int)$navAvatarUid ?>"
                             alt="<?= $navInitial ?>"
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <span style="display:none" class="w-full h-full items-center justify-center text-white text-sm font-bold"><?= $navInitial ?></span>
                        <?php else: ?>
                        <span class="text-white"><?= $navInitial ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white truncate"><?= $navNombre ?></p>
                        <p class="text-xs text-blue-300"><?= $navRolLabel ?></p>
                    </div>
                </div>
                <?= navLink('perfil', 'fas fa-user-circle', 'Mi Perfil', $currentPage, true) ?>
                <button onclick="confirmLogout()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-red-300 hover:bg-red-900/40 hover:text-red-100 transition-colors duration-200">
                    <i class="fas fa-sign-out-alt w-4 text-center"></i>Cerrar sesión
                </button>
            </div>
        </div>
    </div>
</nav>

<script>
(function() {
    const mobileBtn  = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileIcon = document.getElementById('mobileMenuIcon');
    const userBtn    = document.getElementById('userMenuBtn');
    const dropdown   = document.getElementById('userDropdown');
    const chevron    = document.getElementById('userMenuChevron');

    let menuOpen = false;
    let ddOpen   = false;

    mobileBtn.addEventListener('click', () => {
        menuOpen = !menuOpen;
        mobileMenu.style.maxHeight = menuOpen ? mobileMenu.scrollHeight + 'px' : '0';
        mobileIcon.className = menuOpen ? 'fas fa-times text-lg' : 'fas fa-bars text-lg';
    });

    userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        ddOpen = !ddOpen;
        dropdown.classList.toggle('opacity-0',   !ddOpen);
        dropdown.classList.toggle('invisible',   !ddOpen);
        dropdown.classList.toggle('-translate-y-1', !ddOpen);
        chevron.classList.toggle('rotate-180', ddOpen);
    });

    document.addEventListener('click', () => {
        if (ddOpen) {
            ddOpen = false;
            dropdown.classList.add('opacity-0', 'invisible', '-translate-y-1');
            chevron.classList.remove('rotate-180');
        }
    });

    function confirmLogout() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Cerrar sesión',
            cancelButtonText: 'Cancelar',
        }).then(r => {
            if (r.isConfirmed) window.location.href = 'index.php?page=logout';
        });
    }
    window.confirmLogout = confirmLogout;
})();
</script>
