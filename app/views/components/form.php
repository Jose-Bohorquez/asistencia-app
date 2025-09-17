<?php
/**
 * Componentes de Formulario Reutilizables
 */

/**
 * Campo de Input
 */
function renderInput($options = []) {
    $defaults = [
        'type' => 'text',
        'name' => '',
        'id' => '',
        'label' => '',
        'placeholder' => '',
        'value' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'icon' => '',
        'iconPosition' => 'left',
        'error' => '',
        'help' => '',
        'extraClasses' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    if (!$options['id']) {
        $options['id'] = $options['name'];
    }
    
    ob_start();
    ?>
    <div class="mb-4 <?= $options['extraClasses'] ?>">
        <?php if ($options['label']): ?>
        <label for="<?= $options['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">
            <?php if ($options['icon'] && $options['iconPosition'] === 'left'): ?>
            <i class="<?= $options['icon'] ?> mr-2 text-gray-400"></i>
            <?php endif; ?>
            <?= htmlspecialchars($options['label']) ?>
            <?php if ($options['required']): ?>
            <span class="text-red-500 ml-1">*</span>
            <?php endif; ?>
            <?php if ($options['icon'] && $options['iconPosition'] === 'right'): ?>
            <i class="<?= $options['icon'] ?> ml-2 text-gray-400"></i>
            <?php endif; ?>
        </label>
        <?php endif; ?>
        
        <div class="relative">
            <?php if ($options['icon'] && $options['iconPosition'] === 'left'): ?>
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="<?= $options['icon'] ?> text-gray-400"></i>
            </div>
            <?php endif; ?>
            
            <input 
                type="<?= $options['type'] ?>"
                name="<?= $options['name'] ?>"
                id="<?= $options['id'] ?>"
                value="<?= htmlspecialchars($options['value']) ?>"
                placeholder="<?= htmlspecialchars($options['placeholder']) ?>"
                class="w-full <?= $options['icon'] && $options['iconPosition'] === 'left' ? 'pl-10' : 'pl-4' ?> <?= $options['icon'] && $options['iconPosition'] === 'right' ? 'pr-10' : 'pr-4' ?> py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out hover:border-gray-400 <?= $options['error'] ? 'border-red-500 focus:ring-red-500' : '' ?> <?= $options['disabled'] ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                <?= $options['required'] ? 'required' : '' ?>
                <?= $options['disabled'] ? 'disabled' : '' ?>
                <?= $options['readonly'] ? 'readonly' : '' ?>
                <?php foreach ($options['attributes'] as $key => $value): ?>
                <?= $key ?>="<?= htmlspecialchars($value) ?>"
                <?php endforeach; ?>
            >
            
            <?php if ($options['icon'] && $options['iconPosition'] === 'right'): ?>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <i class="<?= $options['icon'] ?> text-gray-400"></i>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($options['error']): ?>
        <p class="mt-2 text-sm text-red-600">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($options['error']) ?>
        </p>
        <?php endif; ?>
        
        <?php if ($options['help'] && !$options['error']): ?>
        <p class="mt-2 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            <?= htmlspecialchars($options['help']) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Campo Select
 */
function renderSelect($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '',
        'options' => [],
        'required' => false,
        'disabled' => false,
        'icon' => '',
        'error' => '',
        'help' => '',
        'extraClasses' => '',
        'attributes' => [],
        'placeholder' => 'Seleccionar...'
    ];
    
    $options = array_merge($defaults, $options);
    
    if (!$options['id']) {
        $options['id'] = $options['name'];
    }
    
    ob_start();
    ?>
    <div class="mb-4 <?= $options['extraClasses'] ?>">
        <?php if ($options['label']): ?>
        <label for="<?= $options['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">
            <?php if ($options['icon']): ?>
            <i class="<?= $options['icon'] ?> mr-2 text-gray-400"></i>
            <?php endif; ?>
            <?= htmlspecialchars($options['label']) ?>
            <?php if ($options['required']): ?>
            <span class="text-red-500 ml-1">*</span>
            <?php endif; ?>
        </label>
        <?php endif; ?>
        
        <select 
            name="<?= $options['name'] ?>"
            id="<?= $options['id'] ?>"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out hover:border-gray-400 <?= $options['error'] ? 'border-red-500 focus:ring-red-500' : '' ?> <?= $options['disabled'] ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
            <?= $options['required'] ? 'required' : '' ?>
            <?= $options['disabled'] ? 'disabled' : '' ?>
            <?php foreach ($options['attributes'] as $key => $value): ?>
            <?= $key ?>="<?= htmlspecialchars($value) ?>"
            <?php endforeach; ?>
        >
            <?php if ($options['placeholder']): ?>
            <option value=""><?= htmlspecialchars($options['placeholder']) ?></option>
            <?php endif; ?>
            
            <?php foreach ($options['options'] as $optionValue => $optionText): ?>
            <option value="<?= htmlspecialchars($optionValue) ?>" <?= $options['value'] == $optionValue ? 'selected' : '' ?>>
                <?= htmlspecialchars($optionText) ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <?php if ($options['error']): ?>
        <p class="mt-2 text-sm text-red-600">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($options['error']) ?>
        </p>
        <?php endif; ?>
        
        <?php if ($options['help'] && !$options['error']): ?>
        <p class="mt-2 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            <?= htmlspecialchars($options['help']) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Campo Textarea
 */
function renderTextarea($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'placeholder' => '',
        'value' => '',
        'rows' => 4,
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'error' => '',
        'help' => '',
        'extraClasses' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    if (!$options['id']) {
        $options['id'] = $options['name'];
    }
    
    ob_start();
    ?>
    <div class="mb-4 <?= $options['extraClasses'] ?>">
        <?php if ($options['label']): ?>
        <label for="<?= $options['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">
            <?= htmlspecialchars($options['label']) ?>
            <?php if ($options['required']): ?>
            <span class="text-red-500 ml-1">*</span>
            <?php endif; ?>
        </label>
        <?php endif; ?>
        
        <textarea 
            name="<?= $options['name'] ?>"
            id="<?= $options['id'] ?>"
            rows="<?= $options['rows'] ?>"
            placeholder="<?= htmlspecialchars($options['placeholder']) ?>"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out hover:border-gray-400 resize-vertical <?= $options['error'] ? 'border-red-500 focus:ring-red-500' : '' ?> <?= $options['disabled'] ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
            <?= $options['required'] ? 'required' : '' ?>
            <?= $options['disabled'] ? 'disabled' : '' ?>
            <?= $options['readonly'] ? 'readonly' : '' ?>
            <?php foreach ($options['attributes'] as $key => $value): ?>
            <?= $key ?>="<?= htmlspecialchars($value) ?>"
            <?php endforeach; ?>
        ><?= htmlspecialchars($options['value']) ?></textarea>
        
        <?php if ($options['error']): ?>
        <p class="mt-2 text-sm text-red-600">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($options['error']) ?>
        </p>
        <?php endif; ?>
        
        <?php if ($options['help'] && !$options['error']): ?>
        <p class="mt-2 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            <?= htmlspecialchars($options['help']) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Checkbox
 */
function renderCheckbox($options = []) {
    $defaults = [
        'name' => '',
        'id' => '',
        'label' => '',
        'value' => '1',
        'checked' => false,
        'disabled' => false,
        'error' => '',
        'help' => '',
        'extraClasses' => '',
        'attributes' => []
    ];
    
    $options = array_merge($defaults, $options);
    
    if (!$options['id']) {
        $options['id'] = $options['name'];
    }
    
    ob_start();
    ?>
    <div class="mb-4 <?= $options['extraClasses'] ?>">
        <div class="flex items-center">
            <input 
                type="checkbox"
                name="<?= $options['name'] ?>"
                id="<?= $options['id'] ?>"
                value="<?= htmlspecialchars($options['value']) ?>"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition duration-200"
                <?= $options['checked'] ? 'checked' : '' ?>
                <?= $options['disabled'] ? 'disabled' : '' ?>
                <?php foreach ($options['attributes'] as $key => $value): ?>
                <?= $key ?>="<?= htmlspecialchars($value) ?>"
                <?php endforeach; ?>
            >
            <?php if ($options['label']): ?>
            <label for="<?= $options['id'] ?>" class="ml-2 block text-sm text-gray-700 <?= $options['disabled'] ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' ?>">
                <?= htmlspecialchars($options['label']) ?>
            </label>
            <?php endif; ?>
        </div>
        
        <?php if ($options['error']): ?>
        <p class="mt-2 text-sm text-red-600">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($options['error']) ?>
        </p>
        <?php endif; ?>
        
        <?php if ($options['help'] && !$options['error']): ?>
        <p class="mt-2 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            <?= htmlspecialchars($options['help']) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Grupo de Radio Buttons
 */
function renderRadioGroup($options = []) {
    $defaults = [
        'name' => '',
        'label' => '',
        'options' => [],
        'value' => '',
        'required' => false,
        'disabled' => false,
        'error' => '',
        'help' => '',
        'extraClasses' => '',
        'inline' => false
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <div class="mb-4 <?= $options['extraClasses'] ?>">
        <?php if ($options['label']): ?>
        <fieldset>
            <legend class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($options['label']) ?>
                <?php if ($options['required']): ?>
                <span class="text-red-500 ml-1">*</span>
                <?php endif; ?>
            </legend>
            
            <div class="<?= $options['inline'] ? 'flex flex-wrap gap-4' : 'space-y-2' ?>">
                <?php foreach ($options['options'] as $optionValue => $optionText): ?>
                <div class="flex items-center">
                    <input 
                        type="radio"
                        name="<?= $options['name'] ?>"
                        id="<?= $options['name'] ?>_<?= $optionValue ?>"
                        value="<?= htmlspecialchars($optionValue) ?>"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 transition duration-200"
                        <?= $options['value'] == $optionValue ? 'checked' : '' ?>
                        <?= $options['disabled'] ? 'disabled' : '' ?>
                        <?= $options['required'] ? 'required' : '' ?>
                    >
                    <label for="<?= $options['name'] ?>_<?= $optionValue ?>" class="ml-2 block text-sm text-gray-700 <?= $options['disabled'] ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' ?>">
                        <?= htmlspecialchars($optionText) ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php endif; ?>
        
        <?php if ($options['error']): ?>
        <p class="mt-2 text-sm text-red-600">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($options['error']) ?>
        </p>
        <?php endif; ?>
        
        <?php if ($options['help'] && !$options['error']): ?>
        <p class="mt-2 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            <?= htmlspecialchars($options['help']) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>