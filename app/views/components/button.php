<?php
/**
 * Componente Button Reutilizable
 * 
 * @param string $text - Texto del botón
 * @param string $type - Tipo de botón (primary, secondary, success, danger, warning, info)
 * @param string $size - Tamaño (xs, sm, md, lg, xl)
 * @param string $icon - Icono FontAwesome (opcional)
 * @param string $href - URL para enlaces
 * @param string $onclick - Función JavaScript
 * @param bool $disabled - Estado deshabilitado
 * @param string $extraClasses - Clases CSS adicionales
 * @param array $attributes - Atributos HTML adicionales
 */

function renderButton($text = [], $options = []) {
    // Si el primer parámetro es un string, es el texto del botón
    if (is_string($text)) {
        $options['text'] = $text;
    } else {
        // Si el primer parámetro es un array, son las opciones
        $options = $text;
    }
    
    // Valores por defecto
    $defaults = [
        'text' => 'Botón',
        'type' => 'primary',
        'buttonType' => 'button', // button, submit, reset
        'size' => 'md',
        'icon' => '',
        'iconPosition' => 'left', // left, right
        'href' => '',
        'onclick' => '',
        'disabled' => false,
        'extraClasses' => '',
        'attributes' => [],
        'loading' => false,
        'fullWidth' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    // Configuración de tipos
    $typeClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600 hover:border-blue-700',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white border-gray-600 hover:border-gray-700',
        'success' => 'bg-green-600 hover:bg-green-700 text-white border-green-600 hover:border-green-700',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white border-red-600 hover:border-red-700',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white border-yellow-500 hover:border-yellow-600',
        'info' => 'bg-cyan-600 hover:bg-cyan-700 text-white border-cyan-600 hover:border-cyan-700',
        'outline-primary' => 'bg-transparent hover:bg-blue-600 text-blue-600 hover:text-white border-blue-600',
        'outline-secondary' => 'bg-transparent hover:bg-gray-600 text-gray-600 hover:text-white border-gray-600',
        'outline-success' => 'bg-transparent hover:bg-green-600 text-green-600 hover:text-white border-green-600',
        'outline-danger' => 'bg-transparent hover:bg-red-600 text-red-600 hover:text-white border-red-600'
    ];
    
    // Configuración de tamaños
    $sizeClasses = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
        'xl' => 'px-8 py-4 text-lg'
    ];
    
    $typeClass = $typeClasses[$options['type']] ?? $typeClasses['primary'];
    $sizeClass = $sizeClasses[$options['size']] ?? $sizeClasses['md'];
    
    // Clases base
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 btn-animate';
    
    if ($options['fullWidth']) {
        $baseClasses .= ' w-full';
    }
    
    if ($options['disabled'] || $options['loading']) {
        $baseClasses .= ' opacity-50 cursor-not-allowed';
        $typeClass = str_replace('hover:', 'disabled:', $typeClass);
    }
    
    $allClasses = $baseClasses . ' ' . $typeClass . ' ' . $sizeClass . ' ' . $options['extraClasses'];
    
    // Preparar atributos
    $attributes = [];
    
    if ($options['onclick'] && !$options['disabled'] && !$options['loading']) {
        $attributes['onclick'] = $options['onclick'];
    }
    
    if ($options['disabled'] || $options['loading']) {
        $attributes['disabled'] = 'disabled';
    }
    
    // Agregar atributos adicionales
    foreach ($options['attributes'] as $key => $value) {
        $attributes[$key] = $value;
    }
    
    // Construir string de atributos
    $attributeString = '';
    foreach ($attributes as $key => $value) {
        $attributeString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
    }
    
    // Contenido del botón
    $content = '';
    
    if ($options['loading']) {
        $content .= '<div class="loading-spinner mr-2"></div>';
    } elseif ($options['icon'] && $options['iconPosition'] === 'left') {
        $content .= '<i class="' . $options['icon'] . ' mr-2"></i>';
    }
    
    $content .= htmlspecialchars($options['text']);
    
    if ($options['icon'] && $options['iconPosition'] === 'right' && !$options['loading']) {
        $content .= '<i class="' . $options['icon'] . ' ml-2"></i>';
    }
    
    // Renderizar como enlace o botón
    if ($options['href'] && !$options['disabled'] && !$options['loading']) {
        return '<a href="' . htmlspecialchars($options['href']) . '" class="' . $allClasses . '"' . $attributeString . '>' . $content . '</a>';
    } else {
        $buttonType = $options['buttonType'];
        return '<button type="' . $buttonType . '" class="' . $allClasses . '"' . $attributeString . '>' . $content . '</button>';
    }
}

/**
 * Botón Primario
 */
function renderPrimaryButton($text, $options = []) {
    return renderButton(array_merge(['text' => $text, 'type' => 'primary'], $options));
}

/**
 * Botón Secundario
 */
function renderSecondaryButton($text, $options = []) {
    return renderButton(array_merge(['text' => $text, 'type' => 'secondary'], $options));
}

/**
 * Botón de Éxito
 */
function renderSuccessButton($text, $options = []) {
    return renderButton(array_merge(['text' => $text, 'type' => 'success'], $options));
}

/**
 * Botón de Peligro
 */
function renderDangerButton($text, $options = []) {
    return renderButton(array_merge(['text' => $text, 'type' => 'danger'], $options));
}

/**
 * Botón con Icono
 */
function renderIconButton($text, $icon, $options = []) {
    return renderButton(array_merge(['text' => $text, 'icon' => $icon], $options));
}

/**
 * Grupo de Botones
 */
function renderButtonGroup($buttons, $extraClasses = '') {
    $output = '<div class="inline-flex rounded-lg shadow-sm ' . $extraClasses . '" role="group">';
    
    foreach ($buttons as $index => $button) {
        $buttonClasses = $button['extraClasses'] ?? '';
        
        // Agregar clases para el grupo
        if ($index === 0) {
            $buttonClasses .= ' rounded-r-none';
        } elseif ($index === count($buttons) - 1) {
            $buttonClasses .= ' rounded-l-none border-l-0';
        } else {
            $buttonClasses .= ' rounded-none border-l-0';
        }
        
        $button['extraClasses'] = $buttonClasses;
        $output .= renderButton($button);
    }
    
    $output .= '</div>';
    return $output;
}

/**
 * Botón de Acción Flotante (FAB)
 */
function renderFAB($icon, $options = []) {
    $defaults = [
        'text' => '',
        'type' => 'primary',
        'size' => 'lg',
        'icon' => $icon,
        'extraClasses' => 'fixed bottom-6 right-6 rounded-full shadow-lg z-50'
    ];
    
    return renderButton(array_merge($defaults, $options));
}

/**
 * Renderiza un botón flotante de acción
 */
function renderFloatingActionButton($options = []) {
    $href = $options['href'] ?? '#';
    $icon = $options['icon'] ?? 'fas fa-plus';
    $tooltip = $options['tooltip'] ?? '';
    $color = $options['color'] ?? 'blue';
    $size = $options['size'] ?? 'md';
    $position = $options['position'] ?? 'bottom-right';
    
    // Clases de color
    switch($color) {
        case 'blue':
            $colorClasses = 'bg-blue-600 hover:bg-blue-700';
            break;
        case 'green':
            $colorClasses = 'bg-green-600 hover:bg-green-700';
            break;
        case 'red':
            $colorClasses = 'bg-red-600 hover:bg-red-700';
            break;
        case 'purple':
            $colorClasses = 'bg-purple-600 hover:bg-purple-700';
            break;
        case 'indigo':
            $colorClasses = 'bg-indigo-600 hover:bg-indigo-700';
            break;
        default:
            $colorClasses = 'bg-blue-600 hover:bg-blue-700';
            break;
    }
    
    // Clases de tamaño
    switch($size) {
        case 'sm':
            $sizeClasses = 'w-12 h-12';
            break;
        case 'lg':
            $sizeClasses = 'w-16 h-16';
            break;
        default:
            $sizeClasses = 'w-14 h-14';
            break;
    }
    
    // Clases de posición
    switch($position) {
        case 'bottom-left':
            $positionClasses = 'fixed bottom-6 left-6';
            break;
        case 'top-right':
            $positionClasses = 'fixed top-6 right-6';
            break;
        case 'top-left':
            $positionClasses = 'fixed top-6 left-6';
            break;
        default:
            $positionClasses = 'fixed right-6 floating-btn-container';
            break;
    }
    
    switch($size) {
        case 'sm':
            $iconSize = 'text-lg';
            break;
        case 'lg':
            $iconSize = 'text-2xl';
            break;
        default:
            $iconSize = 'text-xl';
            break;
    }
    
    $output = "<div class=\"$positionClasses z-50\">";
    $output .= "<a href=\"$href\" ";
    $output .= "class=\"floating-btn inline-flex items-center justify-center $sizeClasses $colorClasses text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105\">";
    $output .= "<i class=\"$icon $iconSize\"></i>";
    $output .= "<span class=\"sr-only\">$tooltip</span>";
    $output .= '</a>';
    $output .= '</div>';
    
    // Agregar tooltip si se especifica
    if ($tooltip && $position === 'bottom-right') {
        $output .= '<div class="fixed bottom-20 right-6 z-40 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none tooltip-floating hidden sm:block">';
        $output .= '<div class="bg-gray-800 text-white text-sm px-3 py-2 rounded-lg shadow-lg whitespace-nowrap">';
        $output .= htmlspecialchars($tooltip);
        $output .= '<div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-800"></div>';
        $output .= '</div>';
        $output .= '</div>';
    }
    
    return $output;
}
?>