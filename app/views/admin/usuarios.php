<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Gestión de Usuarios</h1>
        <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
        <button id="btnNuevoUsuario" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-3 sm:px-4 rounded text-sm sm:text-base w-full sm:w-auto">
            <i class="fas fa-plus mr-1"></i> Nuevo Usuario
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php 
    // Mostrar mensaje de éxito desde sesión
    if (isset($_SESSION['usuario_success'])): 
        $mensaje_exito = $_SESSION['usuario_success'];
        unset($_SESSION['usuario_success']);
    endif;
    ?>
    
    <!-- Formulario para crear/editar usuario (oculto por defecto) -->
    <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
    <div id="formUsuario" class="bg-gray-100 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 hidden">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nuevo Usuario</h2>
        <form method="POST" action="index.php?page=usuarios">
            <input type="hidden" id="id" name="id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mb-4">
                <div>
                    <label for="username" class="block text-gray-700 font-bold mb-2">Usuario *</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="nombre" class="block text-gray-700 font-bold mb-2">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label for="rol" class="block text-gray-700 font-bold mb-2">Rol *</label>
                    <select id="rol" name="rol" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        <option value="profesor">Profesor</option>
                        <option value="admin">Administrador</option>
                        <option value="super_admin">Super Administrador</option>
                    </select>
                </div>
                
                <div>
                    <label for="password" class="block text-gray-700 font-bold mb-2">Contraseña *</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="activo" class="block text-gray-700 font-bold mb-2">Estado</label>
                    <select id="activo" name="activo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="btnCancelar" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Cancelar
                </button>
                <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Guardar
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Lista de usuarios -->
    <div class="w-full">
        <?php if (empty($usuarios)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-3 sm:px-4 py-3 rounded text-sm sm:text-base">
                No hay usuarios registrados.
            </div>
        <?php else: ?>
            <!-- Vista móvil: Tarjetas -->
            <div class="block lg:hidden space-y-3">
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-sm"><?= $usuario['username'] ?></h3>
                                <p class="text-gray-600 text-sm mt-1"><?= $usuario['nombre'] ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?= $usuario['rol'] === 'super_admin' ? 'bg-purple-100 text-purple-800' : ($usuario['rol'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                <?= ucfirst($usuario['rol']) ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                            <div><span class="font-medium">Email:</span> <?= $usuario['email'] ?: 'No especificado' ?></div>
                            <div><span class="font-medium">Estado:</span> 
                                <span class="<?= $usuario['activo'] ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
                        <div class="flex gap-2">
                            <button class="btnEditar bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs"
                                    data-id="<?= $usuario['id'] ?>"
                                    data-username="<?= $usuario['username'] ?>"
                                    data-nombre="<?= $usuario['nombre'] ?>"
                                    data-email="<?= $usuario['email'] ?>"
                                    data-rol="<?= $usuario['rol'] ?>"
                                    data-activo="<?= $usuario['activo'] ?>">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                            <a href="index.php?page=usuarios&delete=<?= $usuario['id'] ?>" 
                               class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs"
                               onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Vista desktop: Tabla -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                            <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $usuario['username'] ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= $usuario['nombre'] ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= $usuario['email'] ?: 'No especificado' ?></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full <?= $usuario['rol'] === 'super_admin' ? 'bg-purple-100 text-purple-800' : ($usuario['rol'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                        <?= ucfirst($usuario['rol']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <span class="<?= $usuario['activo'] ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                                <?php if ($_SESSION['user_rol'] === 'super_admin'): ?>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button class="btnEditar bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs"
                                                data-id="<?= $usuario['id'] ?>"
                                                data-username="<?= $usuario['username'] ?>"
                                                data-nombre="<?= $usuario['nombre'] ?>"
                                                data-email="<?= $usuario['email'] ?>"
                                                data-rol="<?= $usuario['rol'] ?>"
                                                data-activo="<?= $usuario['activo'] ?>">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                        <a href="index.php?page=usuarios&delete=<?= $usuario['id'] ?>" 
                                           class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                                           onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnNuevoUsuario = document.getElementById('btnNuevoUsuario');
        const btnCancelar = document.getElementById('btnCancelar');
        const formUsuario = document.getElementById('formUsuario');
        const formTitle = document.getElementById('formTitle');
        const idInput = document.getElementById('id');
        const usernameInput = document.getElementById('username');
        const nombreInput = document.getElementById('nombre');
        const emailInput = document.getElementById('email');
        const rolInput = document.getElementById('rol');
        const passwordInput = document.getElementById('password');
        const activoInput = document.getElementById('activo');
        
        if (btnNuevoUsuario) {
            btnNuevoUsuario.addEventListener('click', function() {
                formTitle.textContent = 'Nuevo Usuario';
                idInput.value = '';
                usernameInput.value = '';
                nombreInput.value = '';
                emailInput.value = '';
                rolInput.value = 'profesor';
                passwordInput.value = '';
                activoInput.value = '1';
                passwordInput.required = true;
                formUsuario.classList.remove('hidden');
            });
        }
        
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                formUsuario.classList.add('hidden');
            });
        }
        
        document.querySelectorAll('.btnEditar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                formTitle.textContent = 'Editar Usuario';
                idInput.value = this.dataset.id;
                usernameInput.value = this.dataset.username;
                nombreInput.value = this.dataset.nombre;
                emailInput.value = this.dataset.email;
                rolInput.value = this.dataset.rol;
                activoInput.value = this.dataset.activo;
                passwordInput.value = '';
                passwordInput.required = false;
                formUsuario.classList.remove('hidden');
            });
        });
        
        // Mostrar SweetAlert si hay mensaje de éxito
        <?php if (isset($mensaje_exito)): ?>
        Swal.fire({
            title: '¡Éxito!',
            text: '<?= $mensaje_exito ?>',
            icon: 'success',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#1e40af'
        });
        <?php endif; ?>
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>