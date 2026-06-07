<?php
/**
 * Componente de Tabla Reutilizable
 */

/**
 * Tabla Principal
 */
function renderTable($options = []) {
    $defaults = [
        'headers' => [],
        'data' => [],
        'actions' => [],
        'searchable' => false,
        'sortable' => false,
        'pagination' => false,
        'striped' => true,
        'hover' => true,
        'responsive' => true,
        'extraClasses' => '',
        'emptyMessage' => 'No hay datos disponibles',
        'id' => 'table_' . uniqid()
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <div class="<?= $options['extraClasses'] ?>">
        <?php if ($options['searchable']): ?>
        <div class="mb-4">
            <div class="relative">
                <input type="text" 
                       id="<?= $options['id'] ?>_search" 
                       placeholder="Buscar en la tabla..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="<?= $options['responsive'] ? 'overflow-x-auto' : '' ?> bg-white rounded-lg shadow">
            <table id="<?= $options['id'] ?>" class="min-w-full divide-y divide-gray-200">
                <?php if (!empty($options['headers'])): ?>
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach ($options['headers'] as $key => $header): ?>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider <?= $options['sortable'] ? 'cursor-pointer hover:bg-gray-100' : '' ?>" <?= $options['sortable'] ? 'onclick="sortTable(\'' . $options['id'] . '\', ' . $key . ')"' : '' ?>>
                            <div class="flex items-center">
                                <?= is_array($header) ? htmlspecialchars($header['label']) : htmlspecialchars($header) ?>
                                <?php if ($options['sortable']): ?>
                                <i class="fas fa-sort ml-2 text-gray-400"></i>
                                <?php endif; ?>
                            </div>
                        </th>
                        <?php endforeach; ?>
                        <?php if (!empty($options['actions'])): ?>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <?php endif; ?>
                
                <tbody class="bg-white divide-y divide-gray-200 <?= $options['striped'] ? 'divide-y divide-gray-200' : '' ?>">
                    <?php if (empty($options['data'])): ?>
                    <tr>
                        <td colspan="<?= count($options['headers']) + (empty($options['actions']) ? 0 : 1) ?>" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium"><?= htmlspecialchars($options['emptyMessage']) ?></p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($options['data'] as $rowIndex => $row): ?>
                        <tr class="<?= $options['hover'] ? 'hover:bg-gray-50' : '' ?> <?= $options['striped'] && $rowIndex % 2 === 1 ? 'bg-gray-50' : '' ?>">
                            <?php foreach ($options['headers'] as $headerKey => $header): ?>
                            <?php 
                                $field = is_array($header) ? $header['field'] : $headerKey;
                                $value = is_array($row) ? ($row[$field] ?? '') : (is_object($row) ? ($row->$field ?? '') : '');
                                
                                // Aplicar formato si está definido
                                if (is_array($header) && isset($header['format'])) {
                                    switch ($header['format']) {
                                        case 'date':
                                            $value = $value ? date('d/m/Y', strtotime($value)) : '';
                                            break;
                                        case 'datetime':
                                            $value = $value ? date('d/m/Y H:i', strtotime($value)) : '';
                                            break;
                                        case 'currency':
                                            $value = '$' . number_format($value, 2);
                                            break;
                                        case 'badge':
                                            $badgeType = $header['badgeType'] ?? 'primary';
                                            $value = renderBadge(['text' => $value, 'type' => $badgeType]);
                                            break;
                                    }
                                }
                            ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= is_array($header) && isset($header['format']) && $header['format'] === 'badge' ? $value : htmlspecialchars($value) ?>
                            </td>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($options['actions'])): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <?php foreach ($options['actions'] as $action): ?>
                                    <?php
                                        $actionUrl = $action['url'] ?? '#';
                                        $actionText = $action['text'] ?? 'Acción';
                                        $actionIcon = $action['icon'] ?? '';
                                        $actionType = $action['type'] ?? 'primary';
                                        $actionClass = $action['class'] ?? '';
                                        $actionOnclick = $action['onclick'] ?? '';
                                        
                                        // Reemplazar placeholders en URL y onclick
                                        if (is_array($row)) {
                                            foreach ($row as $key => $val) {
                                                $safeVal = (string)($val ?? '');
                                                $actionUrl     = str_replace('{' . $key . '}', $safeVal, $actionUrl);
                                                $actionOnclick = str_replace('{' . $key . '}', $safeVal, $actionOnclick);
                                            }
                                        }
                                    ?>
                                    <?= renderButton([
                                        'text' => $actionText,
                                        'type' => $actionType,
                                        'size' => 'sm',
                                        'icon' => $actionIcon,
                                        'href' => $actionUrl !== '#' ? $actionUrl : '',
                                        'onclick' => $actionOnclick,
                                        'extraClasses' => $actionClass
                                    ]) ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($options['pagination'] && !empty($options['data'])): ?>
        <div class="mt-4">
            <?= renderPagination($options['pagination']) ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($options['searchable'] || $options['sortable']): ?>
    <script>
        <?php if ($options['searchable']): ?>
        // Funcionalidad de búsqueda
        document.getElementById('<?= $options['id'] ?>_search').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('<?= $options['id'] ?>');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - <?= empty($options['actions']) ? 0 : 1 ?>; j++) {
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        });
        <?php endif; ?>
        
        <?php if ($options['sortable']): ?>
        // Funcionalidad de ordenamiento
        function sortTable(tableId, columnIndex) {
            const table = document.getElementById(tableId);
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            
            // Determinar dirección de ordenamiento
            const header = table.getElementsByTagName('th')[columnIndex];
            const icon = header.querySelector('i');
            let ascending = true;
            
            if (icon.classList.contains('fa-sort-up')) {
                ascending = false;
                icon.className = 'fas fa-sort-down ml-2 text-gray-600';
            } else {
                ascending = true;
                icon.className = 'fas fa-sort-up ml-2 text-gray-600';
            }
            
            // Resetear otros iconos
            const allIcons = table.querySelectorAll('th i');
            allIcons.forEach((otherIcon, index) => {
                if (index !== columnIndex) {
                    otherIcon.className = 'fas fa-sort ml-2 text-gray-400';
                }
            });
            
            // Ordenar filas
            rows.sort((a, b) => {
                const aText = a.getElementsByTagName('td')[columnIndex].textContent.trim();
                const bText = b.getElementsByTagName('td')[columnIndex].textContent.trim();
                
                // Intentar comparar como números
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return ascending ? aNum - bNum : bNum - aNum;
                }
                
                // Comparar como texto
                return ascending ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            
            // Reordenar en el DOM
            rows.forEach(row => tbody.appendChild(row));
        }
        <?php endif; ?>
    </script>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

/**
 * Paginación
 */
function renderPagination($options = []) {
    $defaults = [
        'currentPage' => 1,
        'totalPages' => 1,
        'baseUrl' => '',
        'showInfo' => true,
        'totalItems' => 0,
        'itemsPerPage' => 10
    ];
    
    $options = array_merge($defaults, $options);
    
    if ($options['totalPages'] <= 1) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="flex flex-col sm:flex-row justify-between items-center">
        <?php if ($options['showInfo']): ?>
        <div class="mb-4 sm:mb-0">
            <p class="text-sm text-gray-700">
                Mostrando 
                <span class="font-medium"><?= (($options['currentPage'] - 1) * $options['itemsPerPage']) + 1 ?></span>
                a 
                <span class="font-medium"><?= min($options['currentPage'] * $options['itemsPerPage'], $options['totalItems']) ?></span>
                de 
                <span class="font-medium"><?= $options['totalItems'] ?></span>
                resultados
            </p>
        </div>
        <?php endif; ?>
        
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Paginación">
                <!-- Botón Anterior -->
                <?php if ($options['currentPage'] > 1): ?>
                <a href="<?= $options['baseUrl'] ?>&page=<?= $options['currentPage'] - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Anterior</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-300 cursor-not-allowed">
                    <i class="fas fa-chevron-left"></i>
                </span>
                <?php endif; ?>
                
                <!-- Números de página -->
                <?php
                $start = max(1, $options['currentPage'] - 2);
                $end = min($options['totalPages'], $options['currentPage'] + 2);
                
                if ($start > 1): ?>
                <a href="<?= $options['baseUrl'] ?>&page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                <?php if ($start > 2): ?>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $options['currentPage']): ?>
                <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600"><?= $i ?></span>
                <?php else: ?>
                <a href="<?= $options['baseUrl'] ?>&page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?= $i ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($end < $options['totalPages']): ?>
                <?php if ($end < $options['totalPages'] - 1): ?>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                <?php endif; ?>
                <a href="<?= $options['baseUrl'] ?>&page=<?= $options['totalPages'] ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?= $options['totalPages'] ?></a>
                <?php endif; ?>
                
                <!-- Botón Siguiente -->
                <?php if ($options['currentPage'] < $options['totalPages']): ?>
                <a href="<?= $options['baseUrl'] ?>&page=<?= $options['currentPage'] + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Siguiente</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-300 cursor-not-allowed">
                    <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Tabla Simple
 */
function renderSimpleTable($headers, $data, $actions = []) {
    return renderTable([
        'headers' => $headers,
        'data' => $data,
        'actions' => $actions,
        'responsive' => true,
        'striped' => true,
        'hover' => true
    ]);
}
?>