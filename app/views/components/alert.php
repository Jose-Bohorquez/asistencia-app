<?php
/**
 * Componentes de Alertas y Notificaciones
 */

/**
 * Alerta General
 */
function renderAlert($options = []) {
    $defaults = [
        'type' => 'info', // success, error, warning, info
        'title' => '',
        'message' => '',
        'dismissible' => true,
        'icon' => '',
        'extraClasses' => '',
        'id' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // Configuración de tipos
    $typeConfig = [
        'success' => [
            'bgColor' => 'bg-green-50',
            'borderColor' => 'border-green-200',
            'textColor' => 'text-green-800',
            'iconColor' => 'text-green-400',
            'icon' => 'fas fa-check-circle'
        ],
        'error' => [
            'bgColor' => 'bg-red-50',
            'borderColor' => 'border-red-200',
            'textColor' => 'text-red-800',
            'iconColor' => 'text-red-400',
            'icon' => 'fas fa-exclamation-circle'
        ],
        'warning' => [
            'bgColor' => 'bg-yellow-50',
            'borderColor' => 'border-yellow-200',
            'textColor' => 'text-yellow-800',
            'iconColor' => 'text-yellow-400',
            'icon' => 'fas fa-exclamation-triangle'
        ],
        'info' => [
            'bgColor' => 'bg-blue-50',
            'borderColor' => 'border-blue-200',
            'textColor' => 'text-blue-800',
            'iconColor' => 'text-blue-400',
            'icon' => 'fas fa-info-circle'
        ]
    ];
    
    $config = $typeConfig[$options['type']] ?? $typeConfig['info'];
    $icon = $options['icon'] ?: $config['icon'];
    
    ob_start();
    ?>
    <div class="<?= $config['bgColor'] ?> <?= $config['borderColor'] ?> border rounded-lg p-4 mb-4 <?= $options['extraClasses'] ?>" <?= $options['id'] ? 'id="' . $options['id'] . '"' : '' ?> role="alert">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="<?= $icon ?> <?= $config['iconColor'] ?> text-lg"></i>
            </div>
            <div class="ml-3 flex-1">
                <?php if ($options['title']): ?>
                <h3 class="text-sm font-medium <?= $config['textColor'] ?> mb-1">
                    <?= htmlspecialchars($options['title']) ?>
                </h3>
                <?php endif; ?>
                <?php if ($options['message']): ?>
                <div class="text-sm <?= $config['textColor'] ?>">
                    <?= $options['message'] ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($options['dismissible']): ?>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 <?= $config['textColor'] ?> hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent" onclick="this.parentElement.parentElement.parentElement.parentElement.style.display='none'">
                        <span class="sr-only">Cerrar</span>
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Alerta de Éxito
 */
function renderSuccessAlert($message, $title = '', $dismissible = true) {
    return renderAlert([
        'type' => 'success',
        'title' => $title,
        'message' => $message,
        'dismissible' => $dismissible
    ]);
}

/**
 * Alerta de Error
 */
function renderErrorAlert($message, $title = '', $dismissible = true) {
    return renderAlert([
        'type' => 'error',
        'title' => $title,
        'message' => $message,
        'dismissible' => $dismissible
    ]);
}

/**
 * Alerta de Advertencia
 */
function renderWarningAlert($message, $title = '', $dismissible = true) {
    return renderAlert([
        'type' => 'warning',
        'title' => $title,
        'message' => $message,
        'dismissible' => $dismissible
    ]);
}

/**
 * Alerta de Información
 */
function renderInfoAlert($message, $title = '', $dismissible = true) {
    return renderAlert([
        'type' => 'info',
        'title' => $title,
        'message' => $message,
        'dismissible' => $dismissible
    ]);
}

/**
 * Toast Notification
 */
function renderToast($options = []) {
    $defaults = [
        'type' => 'info',
        'title' => '',
        'message' => '',
        'duration' => 5000, // milisegundos
        'position' => 'top-right', // top-right, top-left, bottom-right, bottom-left
        'id' => 'toast_' . uniqid()
    ];
    
    $options = array_merge($defaults, $options);
    
    $typeConfig = [
        'success' => [
            'bgColor' => 'bg-green-600',
            'icon' => 'fas fa-check-circle'
        ],
        'error' => [
            'bgColor' => 'bg-red-600',
            'icon' => 'fas fa-exclamation-circle'
        ],
        'warning' => [
            'bgColor' => 'bg-yellow-600',
            'icon' => 'fas fa-exclamation-triangle'
        ],
        'info' => [
            'bgColor' => 'bg-blue-600',
            'icon' => 'fas fa-info-circle'
        ]
    ];
    
    $config = $typeConfig[$options['type']] ?? $typeConfig['info'];
    
    $positionClasses = [
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4'
    ];
    
    $positionClass = $positionClasses[$options['position']] ?? $positionClasses['top-right'];
    
    ob_start();
    ?>
    <div id="<?= $options['id'] ?>" class="fixed <?= $positionClass ?> z-50 transform transition-all duration-300 ease-in-out translate-x-0 opacity-100">
        <div class="<?= $config['bgColor'] ?> text-white rounded-lg shadow-lg p-4 max-w-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="<?= $config['icon'] ?> text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <?php if ($options['title']): ?>
                    <p class="text-sm font-medium"><?= htmlspecialchars($options['title']) ?></p>
                    <?php endif; ?>
                    <?php if ($options['message']): ?>
                    <p class="text-sm <?= $options['title'] ? 'mt-1' : '' ?>"><?= htmlspecialchars($options['message']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button type="button" class="inline-flex text-white hover:text-gray-200 focus:outline-none" onclick="hideToast('<?= $options['id'] ?>')">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto hide toast
        setTimeout(function() {
            hideToast('<?= $options['id'] ?>');
        }, <?= $options['duration'] ?>);
        
        function hideToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }
        }
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Badge/Etiqueta
 */
if (!function_exists('renderBadge')):
function renderBadge($options = []) {
    $defaults = [
        'text' => '',
        'type' => 'primary', // primary, secondary, success, danger, warning, info
        'size' => 'md', // sm, md, lg
        'rounded' => true,
        'extraClasses' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    $typeClasses = [
        'primary' => 'bg-blue-100 text-blue-800',
        'secondary' => 'bg-gray-100 text-gray-800',
        'success' => 'bg-green-100 text-green-800',
        'danger' => 'bg-red-100 text-red-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        'info' => 'bg-cyan-100 text-cyan-800'
    ];
    
    $sizeClasses = [
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2.5 py-0.5 text-sm',
        'lg' => 'px-3 py-1 text-base'
    ];
    
    $typeClass = $typeClasses[$options['type']] ?? $typeClasses['primary'];
    $sizeClass = $sizeClasses[$options['size']] ?? $sizeClasses['md'];
    $roundedClass = $options['rounded'] ? 'rounded-full' : 'rounded';
    
    return '<span class="inline-flex items-center font-medium ' . $typeClass . ' ' . $sizeClass . ' ' . $roundedClass . ' ' . $options['extraClasses'] . '">' . htmlspecialchars($options['text']) . '</span>';
}
endif;

/**
 * Progress Bar
 */
function renderProgressBar($options = []) {
    $defaults = [
        'value' => 0,
        'max' => 100,
        'label' => '',
        'showPercentage' => true,
        'color' => 'blue', // blue, green, red, yellow, purple
        'size' => 'md', // sm, md, lg
        'animated' => false,
        'extraClasses' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    $percentage = ($options['value'] / $options['max']) * 100;
    $percentage = min(100, max(0, $percentage));
    
    $colorClasses = [
        'blue' => 'bg-blue-600',
        'green' => 'bg-green-600',
        'red' => 'bg-red-600',
        'yellow' => 'bg-yellow-600',
        'purple' => 'bg-purple-600'
    ];
    
    $sizeClasses = [
        'sm' => 'h-2',
        'md' => 'h-4',
        'lg' => 'h-6'
    ];
    
    $colorClass = $colorClasses[$options['color']] ?? $colorClasses['blue'];
    $sizeClass = $sizeClasses[$options['size']] ?? $sizeClasses['md'];
    $animatedClass = $options['animated'] ? 'progress-bar-animated' : '';
    
    ob_start();
    ?>
    <div class="<?= $options['extraClasses'] ?>">
        <?php if ($options['label'] || $options['showPercentage']): ?>
        <div class="flex justify-between items-center mb-2">
            <?php if ($options['label']): ?>
            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($options['label']) ?></span>
            <?php endif; ?>
            <?php if ($options['showPercentage']): ?>
            <span class="text-sm text-gray-500"><?= number_format($percentage, 1) ?>%</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="w-full bg-gray-200 rounded-full <?= $sizeClass ?>">
            <div class="<?= $colorClass ?> <?= $sizeClass ?> rounded-full transition-all duration-300 ease-in-out <?= $animatedClass ?>" style="width: <?= $percentage ?>%"></div>
        </div>
    </div>
    
    <?php if ($options['animated']): ?>
    <style>
        .progress-bar-animated {
            background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
            animation: progress-bar-stripes 1s linear infinite;
        }
        
        @keyframes progress-bar-stripes {
            0% { background-position: 1rem 0; }
            100% { background-position: 0 0; }
        }
    </style>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
?>