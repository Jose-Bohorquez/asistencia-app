<?php include '../app/views/layouts/header.php'; ?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden mt-10">
    <div class="bg-blue-800 text-white py-4 px-6">
        <h2 class="text-xl font-bold">Iniciar Sesión</h2>
    </div>
    <div class="p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="index.php?page=login">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-bold mb-2">Usuario</label>
                <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-bold mb-2">Contraseña</label>
                <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Iniciar Sesión
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>