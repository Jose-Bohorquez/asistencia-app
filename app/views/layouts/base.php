<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME ?></title>
    
    <!-- Meta tags adicionales -->
    <meta name="description" content="<?= isset($pageDescription) ? $pageDescription : 'Sistema de Asistencia - Universidad Tecnológica' ?>">
    <meta name="author" content="Trae AI">
    
    <!-- CSS Framework -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Responsive Design */
        @media (max-width: 640px) {
            table, th, td {
                font-size: 12px !important;
                padding: 2px !important;
            }
            .p-6 { padding: 1rem !important; }
            .p-4 { padding: 0.5rem !important; }
        }
        
        /* Navbar Animations */
        .navbar-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .navbar-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .navbar-link {
            position: relative;
            transition: all 0.2s ease;
        }
        
        .navbar-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #60a5fa;
            transition: width 0.3s ease;
        }
        
        .navbar-link:hover::after {
            width: 100%;
        }
        
        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .mobile-menu.show {
            max-height: 300px;
        }
        
        /* Loading Spinner */
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Card Hover Effects */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Button Animations */
        .btn-animate {
            transition: all 0.2s ease;
        }
        
        .btn-animate:hover {
            transform: translateY(-1px);
        }
        
        .btn-animate:active {
            transform: translateY(0);
        }
    </style>
    
    <!-- Page Specific Styles -->
    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include 'navbar.php'; ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?= isset($_SESSION['user_id']) ? 'container mx-auto px-2 sm:px-4 py-4 sm:py-8' : '' ?>">
        <?php if (isset($content)): ?>
            <?= $content ?>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include 'footer_content.php'; ?>
    <?php endif; ?>
    
    <!-- JavaScript Libraries -->
    <script>
        // Global JavaScript Functions
        
        // Show loading spinner
        function showLoading(element) {
            if (element) {
                element.innerHTML = '<div class="loading-spinner"></div> Cargando...';
                element.disabled = true;
            }
        }
        
        // Hide loading spinner
        function hideLoading(element, originalText) {
            if (element) {
                element.innerHTML = originalText;
                element.disabled = false;
            }
        }
        
        // Show success message
        function showSuccess(message, callback = null) {
            Swal.fire({
                title: '¡Éxito!',
                text: message,
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'rounded-lg',
                    confirmButton: 'rounded-md'
                }
            }).then((result) => {
                if (callback && result.isConfirmed) {
                    callback();
                }
            });
        }
        
        // Show error message
        function showError(message) {
            Swal.fire({
                title: 'Error',
                text: message,
                icon: 'error',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'rounded-lg',
                    confirmButton: 'rounded-md'
                }
            });
        }
        
        // Confirm action
        function confirmAction(title, text, callback) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-lg',
                    confirmButton: 'rounded-md',
                    cancelButton: 'rounded-md'
                }
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        // Format time
        function formatTime(timeString) {
            const time = new Date('2000-01-01 ' + timeString);
            return time.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
    
    <!-- Page Specific JavaScript -->
    <?php if (isset($additionalJS)): ?>
        <?= $additionalJS ?>
    <?php endif; ?>
</body>
</html>