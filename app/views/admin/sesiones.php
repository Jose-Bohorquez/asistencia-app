<?php
// Incluir componentes necesarios
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';
require_once '../app/views/components/table.php';
require_once '../app/views/components/modal.php';

// Configuración del layout
$pageTitle = 'Gestión de Sesiones - Sistema de Asistencia';
$bodyClass = 'bg-gray-50';

// Iniciar captura de contenido
ob_start();
?>

<?php
echo renderCard([
    'content' => function() use ($error, $success, $cursos, $sesiones) {
        ?>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Gestión de Sesiones</h1>
            <?php
            echo renderButton('Nueva Sesión', [
                'id' => 'btnNuevaSesion',
                'style' => 'primary',
                'icon' => 'fas fa-plus',
                'class' => 'bg-blue-800 hover:bg-blue-700 w-full sm:w-auto'
            ]);
            ?>
        </div>
        
        <?php
        // Mostrar alertas
        if (!empty($error)) {
            echo renderAlert([
                'type' => 'error',
                'message' => $error,
                'dismissible' => false
            ]);
        }
        
        if (!empty($success)) {
            echo renderAlert([
                'type' => 'success',
                'message' => $success,
                'dismissible' => false
            ]);
        }
        ?>
        
        <!-- Formulario para crear/editar sesión (oculto por defecto) -->
        <div id="formSesion" class="bg-gray-100 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 hidden">
            <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nueva Sesión</h2>
            <form method="POST" action="index.php?page=sesiones">
                <input type="hidden" id="id" name="id" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mb-4">
                    <?php
                    // Campo curso
                    $cursoOptions = ['' => 'Seleccione un curso'];
                    foreach ($cursos as $curso) {
                        $cursoOptions[$curso['id']] = $curso['codigo'] . ' - ' . $curso['nombre'];
                    }
                    echo renderSelect([
                        'name' => 'curso_id',
                        'label' => 'Curso *',
                        'options' => $cursoOptions,
                        'required' => true
                    ]);
                    
                    // Campo fecha
                    echo renderInput([
                        'name' => 'fecha',
                        'label' => 'Fecha *',
                        'type' => 'date',
                        'required' => true
                    ]);
                    
                    // Campo hora inicio
                    echo renderInput([
                        'name' => 'hora_inicio',
                        'label' => 'Hora Inicio *',
                        'type' => 'time',
                        'required' => true
                    ]);
                    
                    // Campo estado
                    echo renderSelect([
                        'name' => 'estado',
                        'label' => 'Estado',
                        'options' => [
                            'activa' => 'Activa',
                            'finalizada' => 'Finalizada',
                            'cancelada' => 'Cancelada'
                        ]
                    ]);
                    ?>
                </div>
                
                <div class="flex justify-end">
                    <?php
                    echo renderButton('Cancelar', [
                        'id' => 'btnCancelar',
                        'type' => 'button',
                        'style' => 'secondary',
                        'class' => 'mr-2'
                    ]);
                    
                    echo renderButton('Guardar', [
                        'type' => 'submit',
                        'style' => 'primary',
                        'class' => 'bg-blue-800 hover:bg-blue-700'
                    ]);
                    ?>
                </div>
            </form>
        </div>
    
        <!-- Lista de sesiones -->
        <?php if (empty($sesiones)): ?>
            <?php
            echo renderAlert([
                'type' => 'warning',
                'message' => 'No hay sesiones registradas. Cree una nueva sesión para comenzar.',
                'dismissible' => false
            ]);
            ?>
        <?php else: ?>
            <?php
            // Preparar datos para la tabla
            $tableHeaders = [
                'Curso',
                'Fecha', 
                'Hora Inicio',
                'Hora Fin',
                'Estado',
                'Acciones'
            ];
            
            $tableData = [];
            foreach ($sesiones as $sesion) {
                $estadoBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . 
                              ($sesion['estado'] === 'activa' ? 'bg-green-100 text-green-800' : 
                              ($sesion['estado'] === 'finalizada' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) . '">' . 
                              ucfirst($sesion['estado']) . '</span>';
                
                $acciones = [];
                if (!empty($sesion['token'])) {
                    $acciones[] = '<a href="index.php?page=asistencia&token=' . $sesion['token'] . '" class="bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded text-xs" target="_blank"><i class="fas fa-link mr-1"></i> Enlace público</a>';
                }
                
                if ($sesion['estado'] === 'activa') {
                    $acciones[] = '<a href="index.php?page=asistencia&sesion_id=' . $sesion['id'] . '" class="bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded text-xs" target="_blank"><i class="fas fa-link mr-1"></i> Enlace</a>';
                    if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
                        $acciones[] = '<a href="index.php?page=sesiones&deactivate=' . $sesion['id'] . '" class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-xs" onclick="return confirm(\'¿Está seguro de finalizar esta sesión?\');"><i class="fas fa-stop-circle mr-1"></i> Finalizar</a>';
                    }
                }
                
                if ($sesion['estado'] === 'finalizada') {
                    $acciones[] = '<a href="index.php?page=exportar&sesion_id=' . $sesion['id'] . '" class="bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded text-xs"><i class="fas fa-file-export mr-1"></i> Exportar</a>';
                }
                
                if ($sesion['estado'] !== 'activa' && in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
                    $acciones[] = '<a href="index.php?page=sesiones&activate=' . $sesion['id'] . '" class="bg-yellow-500 hover:bg-yellow-700 text-white py-1 px-2 rounded text-xs" onclick="return confirm(\'¿Está seguro de activar esta sesión?\');"><i class="fas fa-play-circle mr-1"></i> Activar</a>';
                }
                
                $tableData[] = [
                    $sesion['curso_nombre'],
                    date('d/m/Y', strtotime($sesion['fecha'])),
                    date('H:i', strtotime($sesion['hora_inicio'])),
                    $sesion['hora_fin'] ? date('H:i', strtotime($sesion['hora_fin'])) : '-',
                    $estadoBadge,
                    '<div class="flex flex-wrap gap-1">' . implode(' ', $acciones) . '</div>'
                ];
            }
            
            echo renderTable([
                'headers' => $tableHeaders,
                'data' => $tableData,
                'responsive' => true,
                'mobile_card_template' => function($row, $headers) {
                    return '
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-sm">' . $row[0] . '</h3>
                                <p class="text-gray-600 text-xs mt-1">' . $row[1] . ' - ' . $row[2] . '</p>
                            </div>
                            <div class="ml-2">' . $row[4] . '</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                            <div><span class="font-medium">Hora Fin:</span> ' . $row[3] . '</div>
                        </div>
                        <div class="flex flex-col gap-2">' . $row[5] . '</div>
                    </div>';
                }
            ]);
            ?>
        <?php endif; ?>
        <?php
    }
]);

// Capturar el contenido
$content = ob_get_clean();

// CSS personalizado
$customCSS = '
<style>
/* Optimizaciones adicionales para pantallas muy pequeñas */
@media (max-width: 480px) {
    .grid {
        gap: 0.5rem;
    }
    
    .p-4 {
        padding: 0.75rem;
    }
    
    .p-3 {
        padding: 0.5rem;
    }
    
    .px-3, .px-4 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .py-3 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    
    .mb-4, .mb-3 {
        margin-bottom: 0.75rem;
    }
    
    .rounded-lg {
        border-radius: 0.5rem;
    }
    
    .text-sm, .text-xs {
        font-size: 0.75rem;
    }
    
    .space-y-3 > * + * {
        margin-top: 0.5rem;
    }
    
    .flex-col {
        gap: 0.5rem;
    }
}
</style>';

// JavaScript personalizado
$customJS = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const formSesion = document.getElementById("formSesion");
        const btnNuevaSesion = document.getElementById("btnNuevaSesion");
        const btnCancelar = document.getElementById("btnCancelar");
        const formTitle = document.getElementById("formTitle");
        const idInput = document.getElementById("id");
        const cursoIdSelect = document.getElementById("curso_id");
        const fechaInput = document.getElementById("fecha");
        const horaInicioInput = document.getElementById("hora_inicio");
        const estadoSelect = document.getElementById("estado");
        
        // Mostrar formulario para nueva sesión
        btnNuevaSesion.addEventListener("click", function() {
            formTitle.textContent = "Nueva Sesión";
            idInput.value = "";
            cursoIdSelect.value = "";
            fechaInput.value = new Date().toISOString().split("T")[0];
            horaInicioInput.value = "";
            estadoSelect.value = "activa";
            formSesion.classList.remove("hidden");
        });
        
        // Ocultar formulario
        btnCancelar.addEventListener("click", function() {
            formSesion.classList.add("hidden");
        });
        
        // Funciones para finalizar y activar sesiones
        window.finalizarSesion = function(id) {
            if (confirm("¿Está seguro de finalizar esta sesión?")) {
                window.location.href = "index.php?page=sesiones&deactivate=" + id;
            }
        };
        
        window.activarSesion = function(id) {
            if (confirm("¿Está seguro de activar esta sesión?")) {
                window.location.href = "index.php?page=sesiones&activate=" + id;
            }
        };
    });' + (!empty($success) ? '

    // Mostrar mensaje de éxito
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: "¡Éxito!",
            text: "' . $success . '",
            icon: "success",
            confirmButtonText: "OK"
        });
    }' : '') + '
</script>';

// Incluir el layout base
include '../app/views/layouts/base.php';
?>
