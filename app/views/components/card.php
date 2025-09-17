<?php
/**
 * Componente Card Reutilizable
 * 
 * @param string $title - Título de la tarjeta
 * @param string $content - Contenido de la tarjeta
 * @param string $icon - Icono FontAwesome (opcional)
 * @param string $color - Color del tema (blue, green, red, yellow, purple)
 * @param string $size - Tamaño (sm, md, lg)
 * @param bool $hover - Efecto hover
 * @param string $extraClasses - Clases CSS adicionales
 */

function renderCard($options = []) {
    // Valores por defecto
    $defaults = [
        'title' => '',
        'content' => '',
        'icon' => '',
        'color' => 'blue',
        'size' => 'md',
        'hover' => true,
        'extraClasses' => '',
        'headerContent' => '',
        'footerContent' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    // Configuración de colores
    $colorClasses = [
        'blue' => 'from-blue-500 to-blue-600',
        'green' => 'from-green-500 to-green-600',
        'red' => 'from-red-500 to-red-600',
        'yellow' => 'from-yellow-500 to-yellow-600',
        'purple' => 'from-purple-500 to-purple-600',
        'indigo' => 'from-indigo-500 to-indigo-600'
    ];
    
    // Configuración de tamaños
    $sizeClasses = [
        'sm' => 'p-3',
        'md' => 'p-4 sm:p-6',
        'lg' => 'p-6 sm:p-8'
    ];
    
    $colorClass = $colorClasses[$options['color']] ?? $colorClasses['blue'];
    $sizeClass = $sizeClasses[$options['size']] ?? $sizeClasses['md'];
    $hoverClass = $options['hover'] ? 'card-hover hover:shadow-xl' : '';
    
    ob_start();
    ?>
    <div class="bg-white rounded-xl shadow-lg <?= $hoverClass ?> transition-all duration-300 overflow-hidden <?= $options['extraClasses'] ?>">
        <?php if ($options['title'] || $options['icon'] || $options['headerContent']): ?>
        <div class="bg-gradient-to-r <?= $colorClass ?> <?= $sizeClass ?> text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <?php if ($options['icon']): ?>
                    <div class="bg-white bg-opacity-20 p-2 sm:p-3 rounded-full mr-3 sm:mr-4">
                        <i class="<?= $options['icon'] ?> text-lg sm:text-xl"></i>
                    </div>
                    <?php endif; ?>
                    <?php if ($options['title']): ?>
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold"><?= htmlspecialchars($options['title']) ?></h3>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($options['headerContent']): ?>
                <div class="flex-shrink-0">
                    <?= $options['headerContent'] ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($options['content']): ?>
        <div class="<?= $sizeClass ?>">
            <?php 
            if (is_callable($options['content'])) {
                // Si es una función, ejecutarla y capturar la salida
                ob_start();
                $options['content']();
                echo ob_get_clean();
            } else {
                // Si es un string, imprimirlo directamente
                echo $options['content'];
            }
            ?>
        </div>
        <?php endif; ?>
        
        <?php if ($options['footerContent']): ?>
        <div class="bg-gray-50 px-4 sm:px-6 py-3 border-t border-gray-200">
            <?= $options['footerContent'] ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Componente de Tarjeta de Estadística
 */
function renderStatCard($options = []) {
    $defaults = [
        'title' => '',
        'value' => '0',
        'icon' => 'fas fa-chart-bar',
        'color' => 'blue',
        'change' => null,
        'changeType' => 'positive', // positive, negative, neutral
        'subtitle' => ''
    ];
    
    $options = array_merge($defaults, $options);
    
    $changeIcon = '';
    $changeColor = '';
    
    if ($options['change'] !== null) {
        switch ($options['changeType']) {
            case 'positive':
                $changeIcon = 'fas fa-arrow-up';
                $changeColor = 'text-green-500';
                break;
            case 'negative':
                $changeIcon = 'fas fa-arrow-down';
                $changeColor = 'text-red-500';
                break;
            default:
                $changeIcon = 'fas fa-minus';
                $changeColor = 'text-gray-500';
        }
    }
    
    $content = '
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">' . htmlspecialchars($options['value']) . '</p>
                ' . ($options['subtitle'] ? '<p class="text-sm text-gray-600 mt-1">' . htmlspecialchars($options['subtitle']) . '</p>' : '') . '
            </div>
        </div>';
    
    if ($options['change'] !== null) {
        $content .= '
        <div class="flex items-center mt-3 text-sm">
            <i class="' . $changeIcon . ' ' . $changeColor . ' mr-1"></i>
            <span class="' . $changeColor . ' font-medium">' . htmlspecialchars($options['change']) . '</span>
            <span class="text-gray-500 ml-2">vs período anterior</span>
        </div>';
    }
    
    return renderCard([
        'title' => $options['title'],
        'content' => $content,
        'icon' => $options['icon'],
        'color' => $options['color'],
        'size' => 'md',
        'hover' => true
    ]);
}

/**
 * Componente de Tarjeta Simple
 */
function renderSimpleCard($title, $content, $extraClasses = '') {
    return renderCard([
        'title' => $title,
        'content' => $content,
        'extraClasses' => $extraClasses
    ]);
}
?>