<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
    @media (max-width: 640px) {
        table, th, td {
            font-size: 12px !important;
            padding: 2px !important;
        }
        .p-6 { padding: 1rem !important; }
        .p-4 { padding: 0.5rem !important; }
    }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="bg-blue-800 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold">
                        <?= APP_NAME ?>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'profesor'): ?>
                    <a href="index.php?page=dashboard" class="hover:text-blue-200">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="index.php?page=cursos" class="hover:text-blue-200">
                        <i class="fas fa-book mr-1"></i> Cursos
                    </a>
                    <a href="index.php?page=sesiones" class="hover:text-blue-200">
                        <i class="fas fa-calendar-alt mr-1"></i> Sesiones
                    </a>
                    <?php endif; ?>
                    <div class="relative group">
                        <button class="flex items-center hover:text-blue-200">
                            <i class="fas fa-user-circle mr-1"></i> <?= $_SESSION['nombre'] ?>
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                            <a href="index.php?page=logout" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">
                                <i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container mx-auto px-4 py-8">