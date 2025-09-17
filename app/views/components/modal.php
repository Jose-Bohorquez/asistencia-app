<?php
/**
 * Componente de Modal Reutilizable
 */

/**
 * Modal Principal
 */
function renderModal($options = []) {
    $defaults = [
        'id' => 'modal_' . uniqid(),
        'title' => 'Modal',
        'content' => '',
        'size' => 'md', // sm, md, lg, xl, full
        'closable' => true,
        'backdrop' => true,
        'keyboard' => true,
        'footer' => true,
        'buttons' => [],
        'extraClasses' => '',
        'animation' => 'fade',
        'position' => 'center' // center, top
    ];
    
    $options = array_merge($defaults, $options);
    
    // Clases de tamaño
    $sizeClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4'
    ];
    
    $sizeClass = $sizeClasses[$options['size']] ?? $sizeClasses['md'];
    
    // Clases de posición
    $positionClasses = [
        'center' => 'items-center',
        'top' => 'items-start pt-16'
    ];
    
    $positionClass = $positionClasses[$options['position']] ?? $positionClasses['center'];
    
    ob_start();
    ?>
    <!-- Modal Backdrop -->
    <div id="<?= $options['id'] ?>" 
         class="fixed inset-0 z-50 overflow-y-auto hidden" 
         aria-labelledby="<?= $options['id'] ?>-title" 
         role="dialog" 
         aria-modal="true">
        
        <!-- Backdrop -->
        <?php if ($options['backdrop']): ?>
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             onclick="<?= $options['closable'] ? 'closeModal(\'' . $options['id'] . '\')' : '' ?>"></div>
        <?php endif; ?>
        
        <!-- Modal Container -->
        <div class="flex min-h-full justify-center <?= $positionClass ?> p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full <?= $sizeClass ?> <?= $options['extraClasses'] ?>">
                
                <!-- Header -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="<?= $options['id'] ?>-title">
                                <?= htmlspecialchars($options['title']) ?>
                            </h3>
                        </div>
                        <?php if ($options['closable']): ?>
                        <button type="button" 
                                class="rounded-md bg-white text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" 
                                onclick="closeModal('<?= $options['id'] ?>')">
                            <span class="sr-only">Cerrar</span>
                            <i class="fas fa-times text-xl"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="bg-white px-4 pb-4 sm:p-6 sm:pt-0">
                    <div class="mt-2">
                        <?= $options['content'] ?>
                    </div>
                </div>
                
                <!-- Footer -->
                <?php if ($options['footer'] && !empty($options['buttons'])): ?>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <?php foreach (array_reverse($options['buttons']) as $button): ?>
                    <div class="<?= $button === end($options['buttons']) ? '' : 'mt-3 sm:mt-0 sm:mr-3' ?>">
                        <?= renderButton($button) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Funciones del modal
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                
                // Focus en el primer elemento focusable
                const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                }
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }
        
        // Cerrar con ESC
        <?php if ($options['keyboard']): ?>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('[id^="modal_"]:not(.hidden)');
                openModals.forEach(modal => {
                    if (modal.id === '<?= $options['id'] ?>') {
                        closeModal(modal.id);
                    }
                });
            }
        });
        <?php endif; ?>
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Modal de Confirmación
 */
function renderConfirmModal($options = []) {
    $defaults = [
        'id' => 'confirm_modal_' . uniqid(),
        'title' => 'Confirmar Acción',
        'message' => '¿Estás seguro de que deseas continuar?',
        'confirmText' => 'Confirmar',
        'cancelText' => 'Cancelar',
        'confirmType' => 'danger',
        'onConfirm' => '',
        'icon' => 'fas fa-exclamation-triangle',
        'iconColor' => 'text-red-600'
    ];
    
    $options = array_merge($defaults, $options);
    
    $content = '
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="' . $options['icon'] . ' text-2xl ' . $options['iconColor'] . '"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">' . htmlspecialchars($options['message']) . '</p>
            </div>
        </div>
    ';
    
    $buttons = [
        [
            'text' => $options['confirmText'],
            'type' => $options['confirmType'],
            'onclick' => $options['onConfirm'] . '; closeModal(\'' . $options['id'] . '\')'
        ],
        [
            'text' => $options['cancelText'],
            'type' => 'secondary',
            'onclick' => 'closeModal(\'' . $options['id'] . '\')'
        ]
    ];
    
    return renderModal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'size' => 'sm',
        'buttons' => $buttons
    ]);
}

/**
 * Modal de Formulario
 */
function renderFormModal($options = []) {
    $defaults = [
        'id' => 'form_modal_' . uniqid(),
        'title' => 'Formulario',
        'formId' => 'modal_form_' . uniqid(),
        'action' => '',
        'method' => 'POST',
        'fields' => [],
        'submitText' => 'Guardar',
        'cancelText' => 'Cancelar',
        'submitType' => 'primary',
        'size' => 'lg'
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <form id="<?= $options['formId'] ?>" action="<?= $options['action'] ?>" method="<?= $options['method'] ?>" class="space-y-4">
        <?php foreach ($options['fields'] as $field): ?>
        <div>
            <?php
            switch ($field['type']) {
                case 'select':
                    echo renderSelect($field);
                    break;
                case 'textarea':
                    echo renderTextarea($field);
                    break;
                case 'checkbox':
                    echo renderCheckbox($field);
                    break;
                default:
                    echo renderInput($field);
                    break;
            }
            ?>
        </div>
        <?php endforeach; ?>
    </form>
    <?php
    $content = ob_get_clean();
    
    $buttons = [
        [
            'text' => $options['submitText'],
            'type' => $options['submitType'],
            'onclick' => 'document.getElementById(\'' . $options['formId'] . '\').submit()'
        ],
        [
            'text' => $options['cancelText'],
            'type' => 'secondary',
            'onclick' => 'closeModal(\'' . $options['id'] . '\')'
        ]
    ];
    
    return renderModal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'size' => $options['size'],
        'buttons' => $buttons
    ]);
}

/**
 * Modal de Información
 */
function renderInfoModal($options = []) {
    $defaults = [
        'id' => 'info_modal_' . uniqid(),
        'title' => 'Información',
        'message' => '',
        'type' => 'info', // info, success, warning, error
        'okText' => 'Entendido',
        'size' => 'md'
    ];
    
    $options = array_merge($defaults, $options);
    
    $typeConfig = [
        'info' => ['icon' => 'fas fa-info-circle', 'color' => 'text-blue-600'],
        'success' => ['icon' => 'fas fa-check-circle', 'color' => 'text-green-600'],
        'warning' => ['icon' => 'fas fa-exclamation-triangle', 'color' => 'text-yellow-600'],
        'error' => ['icon' => 'fas fa-times-circle', 'color' => 'text-red-600']
    ];
    
    $config = $typeConfig[$options['type']] ?? $typeConfig['info'];
    
    $content = '
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="' . $config['icon'] . ' text-2xl ' . $config['color'] . '"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">' . htmlspecialchars($options['message']) . '</p>
            </div>
        </div>
    ';
    
    $buttons = [
        [
            'text' => $options['okText'],
            'type' => 'primary',
            'onclick' => 'closeModal(\'' . $options['id'] . '\')'
        ]
    ];
    
    return renderModal([
        'id' => $options['id'],
        'title' => $options['title'],
        'content' => $content,
        'size' => $options['size'],
        'buttons' => $buttons
    ]);
}

/**
 * Modal Simple
 */
function renderSimpleModal($id, $title, $content, $buttons = []) {
    return renderModal([
        'id' => $id,
        'title' => $title,
        'content' => $content,
        'buttons' => $buttons
    ]);
}
?>