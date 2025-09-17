<?php
// Incluir componentes necesarios
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';
require_once '../app/views/components/table.php';
require_once '../app/views/components/modal.php';

// Configuración del layout
$pageTitle = 'Gestión de Usuarios - Sistema de Asistencia';
$bodyClass = 'bg-gray-50';

// Procesar mensaje de éxito desde sesión
$mensaje_exito = null;
if (isset($_SESSION['usuario_success'])) {
    $mensaje_exito = $_SESSION['usuario_success'];
    unset($_SESSION['usuario_success']);
}

// Iniciar captura de contenido
ob_start();
?>

<?php
echo renderCard([
    'content' => function() use ($error, $success, $mensaje_exito, $usuarios) {
        ?>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Gestión de Usuarios</h1>
            <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
                <?php
                echo renderButton('Nuevo Usuario', [
                    'id' => 'btnNuevoUsuario',
                    'style' => 'primary',
                    'icon' => 'fas fa-plus',
                    'class' => 'bg-blue-800 hover:bg-blue-700 w-full sm:w-auto'
                ]);
                ?>
            <?php endif; ?>
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
        
        <!-- Formulario para crear/editar usuario (oculto por defecto) -->
        <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
        <div id="formUsuario" class="bg-gray-100 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 hidden">
            <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nuevo Usuario</h2>
            <form method="POST" action="index.php?page=usuarios">
                <input type="hidden" id="id" name="id" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mb-4">
                    <?php
                    // Campo usuario
                    echo renderInput([
                        'name' => 'username',
                        'label' => 'Usuario *',
                        'type' => 'text',
                        'required' => true
                    ]);
                    
                    // Campo nombre
                    echo renderInput([
                        'name' => 'nombre',
                        'label' => 'Nombre Completo *',
                        'type' => 'text',
                        'required' => true
                    ]);
                    
                    // Campo email
                    echo renderInput([
                        'name' => 'email',
                        'label' => 'Email',
                        'type' => 'email'
                    ]);
                    
                    // Campo rol
                    echo renderSelect([
                        'name' => 'rol',
                        'label' => 'Rol *',
                        'options' => [
                            'profesor' => 'Profesor',
                            'admin' => 'Administrador',
                            'super_admin' => 'Super Administrador'
                        ],
                        'required' => true
                    ]);
                    
                    // Campo contraseña
                    echo renderInput([
                        'name' => 'password',
                        'label' => 'Contraseña *',
                        'type' => 'password',
                        'required' => true
                    ]);
                    
                    // Campo estado
                    echo renderSelect([
                        'name' => 'activo',
                        'label' => 'Estado',
                        'options' => [
                            '1' => 'Activo',
                            '0' => 'Inactivo'
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
        <?php endif; ?>
    
        <!-- Lista de usuarios -->
        <?php if (empty($usuarios)): ?>
            <?php
            echo renderAlert([
                'type' => 'warning',
                'message' => 'No hay usuarios registrados.',
                'dismissible' => false
            ]);
            ?>
        <?php else: ?>
            <?php
            // Preparar datos para la tabla
            $tableHeaders = [
                'Usuario',
                'Nombre',
                'Email',
                'Rol',
                'Estado',
                'Fecha Creación'
            ];
            
            if ($_SESSION['user_rol'] === 'super_admin') {
                $tableHeaders[] = 'Acciones';
            }
            
            $tableData = [];
            foreach ($usuarios as $usuario) {
                $rolBadge = '<span class="px-2 py-1 text-xs rounded-full ' . 
                           ($usuario['rol'] === 'super_admin' ? 'bg-purple-100 text-purple-800' : 
                           ($usuario['rol'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800')) . '">' . 
                           ucfirst($usuario['rol']) . '</span>';
                
                $estadoText = '<span class="' . ($usuario['activo'] ? 'text-green-600' : 'text-red-600') . '">' . 
                             ($usuario['activo'] ? 'Activo' : 'Inactivo') . '</span>';
                
                $row = [
                    $usuario['username'],
                    $usuario['nombre'],
                    $usuario['email'] ?: 'No especificado',
                    $rolBadge,
                    $estadoText,
                    date('d/m/Y', strtotime($usuario['created_at']))
                ];
                
                if ($_SESSION['user_rol'] === 'super_admin') {
                    $acciones = [];
                    $acciones[] = '<button class="btnEditar bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs" ' .
                                 'data-id="' . $usuario['id'] . '" ' .
                                 'data-username="' . $usuario['username'] . '" ' .
                                 'data-nombre="' . $usuario['nombre'] . '" ' .
                                 'data-email="' . $usuario['email'] . '" ' .
                                 'data-rol="' . $usuario['rol'] . '" ' .
                                 'data-activo="' . $usuario['activo'] . '">' .
                                 '<i class="fas fa-edit"></i> Editar</button>';
                    
                    if ($usuario['id'] != $_SESSION['user_id']) {
                        $acciones[] = '<a href="index.php?page=usuarios&delete=' . $usuario['id'] . '" ' .
                                     'class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs" ' .
                                     'onclick="return confirm(\'¿Está seguro de eliminar este usuario?\')">' .
                                     '<i class="fas fa-trash"></i> Eliminar</a>';
                    }
                    
                    $row[] = '<div class="flex flex-wrap gap-1">' . implode(' ', $acciones) . '</div>';
                }
                
                $tableData[] = $row;
            }
            
            echo renderTable([
                'headers' => $tableHeaders,
                'data' => $tableData,
                'responsive' => true,
                'mobile_card_template' => function($row, $headers) {
                    $actionsIndex = count($headers) - 1;
                    $hasActions = isset($row[$actionsIndex]) && strpos($row[$actionsIndex], 'btnEditar') !== false;
                    
                    return '
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-sm">' . $row[0] . '</h3>
                                <p class="text-gray-600 text-sm mt-1">' . $row[1] . '</p>
                            </div>
                            <div class="ml-2">' . $row[3] . '</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                            <div><span class="font-medium">Email:</span> ' . $row[2] . '</div>
                            <div><span class="font-medium">Estado:</span> ' . $row[4] . '</div>
                            <div><span class="font-medium">Creado:</span> ' . $row[5] . '</div>
                        </div>' . 
                        ($hasActions ? '<div class="flex gap-2">' . $row[$actionsIndex] . '</div>' : '') . '
                    </div>';
                }
            ]);
            ?>
        <?php endif; ?>
        <?php
    }
]);
?>

<?php
$customJS = '
    // Mostrar formulario para nuevo usuario
    const btnNuevoUsuario = document.getElementById("btnNuevoUsuario");
    const btnCancelar = document.getElementById("btnCancelar");
    const formUsuario = document.getElementById("formUsuario");
    const formTitle = document.getElementById("formTitle");
    const idInput = document.getElementById("id");
    const usernameInput = document.getElementById("username");
    const nombreInput = document.getElementById("nombre");
    const emailInput = document.getElementById("email");
    const rolInput = document.getElementById("rol");
    const passwordInput = document.getElementById("password");
    const activoInput = document.getElementById("activo");
    
    if (btnNuevoUsuario) {
        btnNuevoUsuario.addEventListener("click", function() {
            formTitle.textContent = "Nuevo Usuario";
            idInput.value = "";
            usernameInput.value = "";
            nombreInput.value = "";
            emailInput.value = "";
            rolInput.value = "profesor";
            passwordInput.value = "";
            activoInput.value = "1";
            passwordInput.required = true;
            formUsuario.classList.remove("hidden");
        });
    }
    
    if (btnCancelar) {
        btnCancelar.addEventListener("click", function() {
            formUsuario.classList.add("hidden");
        });
    }
    
    document.querySelectorAll(".btnEditar").forEach(function(btn) {
        btn.addEventListener("click", function() {
            formTitle.textContent = "Editar Usuario";
            idInput.value = this.dataset.id;
            usernameInput.value = this.dataset.username;
            nombreInput.value = this.dataset.nombre;
            emailInput.value = this.dataset.email;
            rolInput.value = this.dataset.rol;
            activoInput.value = this.dataset.activo;
            passwordInput.value = "";
            passwordInput.required = false;
            formUsuario.classList.remove("hidden");
        });
    });';

// Agregar mensaje de éxito si existe
if (isset($mensaje_exito)) {
    $customJS .= '
    // Mostrar mensaje de éxito
    Swal.fire({
        title: "¡Éxito!",
        text: "' . $mensaje_exito . '",
        icon: "success",
        confirmButtonText: "Aceptar",
        confirmButtonColor: "#1e40af"
    });';
}

include '../app/views/layouts/layout_base.php';
?>