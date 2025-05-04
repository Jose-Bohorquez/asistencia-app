<?php include '../app/views/layouts/header.php'; ?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden mt-10">
    <div class="bg-red-800 text-white py-4 px-6">
        <h2 class="text-xl font-bold">Error</h2>
    </div>
    <div class="p-6">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
        
        <div class="flex justify-center mt-4">
            <a href="index.php?page=dashboard" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
            </a>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>