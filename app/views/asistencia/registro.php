<?php include '../app/views/layouts/header.php'; ?>

<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
    <div class="bg-blue-800 text-white py-4 px-6">
        <h2 class="text-xl font-bold">Registro de Asistencia</h2>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 m-4 rounded">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 m-4 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if ($sesion): ?>
        <div class="p-6">
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Información de la Sesión</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><span class="font-bold">Curso:</span> <?= $sesion['curso_nombre'] ?></p>
                        <p><span class="font-bold">Programa:</span> <?= $sesion['programa'] ?></p>
                        <p><span class="font-bold">Área:</span> <?= $sesion['area'] ?></p>
                    </div>
                    <div>
                        <p><span class="font-bold">Fecha:</span> <?= date('d/m/Y', strtotime($sesion['fecha'])) ?></p>
                        <p><span class="font-bold">Hora:</span> <?= date('H:i', strtotime($sesion['hora_inicio'])) ?></p>
                        <p><span class="font-bold">Aula:</span> <?= $sesion['aula'] ?> - <?= $sesion['sede'] ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (empty($success)): ?>
                <form method="POST" action="index.php?page=asistencia&sesion_id=<?= $sesion['id'] ?>">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Datos del Estudiante</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="nombre" class="block text-gray-700 font-bold mb-2">Nombre Completo *</label>
                            <input type="text" id="nombre" name="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="documento" class="block text-gray-700 font-bold mb-2">Documento de Identidad *</label>
                            <input type="text" id="documento" name="documento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="codigo" class="block text-gray-700 font-bold mb-2">Código de Estudiante *</label>
                            <input type="text" id="codigo" name="codigo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="telefono" class="block text-gray-700 font-bold mb-2">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="direccion" class="block text-gray-700 font-bold mb-2">Dirección</label>
                            <input type="text" id="direccion" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="correo" class="block text-gray-700 font-bold mb-2">Correo Electrónico</label>
                            <input type="email" id="correo" name="correo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="firma" class="block text-gray-700 font-bold mb-2">Firma *</label>
                        <div class="border border-gray-300 rounded-md p-2 bg-white">
                            <canvas id="signature-pad" class="w-full" style="height:320px; max-height:50vh;"></canvas>
                        </div>
                        <input type="hidden" id="firma" name="firma">
                        <div class="flex justify-end mt-2">
                            <button type="button" id="clear-signature" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Borrar Firma
                            </button>
                        </div>
                    </div>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const canvas = document.getElementById('signature-pad');
                            const signaturePad = new SignaturePad(canvas, {
                                backgroundColor: 'rgba(255, 255, 255, 0)',
                                penColor: 'black'
                            });
                            
                            function resizeCanvas() {
                                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                                // Ajusta el alto del canvas para móvil y escritorio
                                if (window.innerWidth < 600) {
                                    canvas.width = canvas.offsetWidth * ratio;
                                    canvas.height = 320 * ratio;
                                } else {
                                    canvas.width = canvas.offsetWidth * ratio;
                                    canvas.height = 240 * ratio;
                                }
                                canvas.getContext("2d").scale(ratio, ratio);
                                signaturePad.clear();
                            }
                            
                            window.addEventListener("resize", resizeCanvas);
                            resizeCanvas();
                            
                            document.getElementById('clear-signature').addEventListener('click', function() {
                                signaturePad.clear();
                            });
                            
                            document.querySelector('form').addEventListener('submit', function(e) {
                                if (signaturePad.isEmpty()) {
                                    e.preventDefault();
                                    alert('Por favor, firme antes de enviar el formulario.');
                                    return false;
                                }
                                document.getElementById('firma').value = signaturePad.toDataURL();
                                return true;
                            });
                        });
                    </script>
                    
                    <div class="flex justify-end">
                        <button type="submit" id="submit-btn" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Registrar Asistencia
                        </button>
                    </div>
                </form>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const canvas = document.getElementById('signature-pad');
                        const signaturePad = new SignaturePad(canvas, {
                            backgroundColor: 'rgba(255, 255, 255, 0)',
                            penColor: 'black'
                        });
                        
                        // Ajustar tamaño del canvas
                        function resizeCanvas() {
                            const ratio = Math.max(window.devicePixelRatio || 1, 1);
                            canvas.width = canvas.offsetWidth * ratio;
                            canvas.height = canvas.offsetHeight * ratio;
                            canvas.getContext("2d").scale(ratio, ratio);
                            signaturePad.clear(); // Limpiar después de redimensionar
                        }
                        
                        window.addEventListener("resize", resizeCanvas);
                        resizeCanvas();
                        
                        // Limpiar firma
                        document.getElementById('clear-signature').addEventListener('click', function() {
                            signaturePad.clear();
                        });
                        
                        // Guardar firma al enviar formulario
                        document.querySelector('form').addEventListener('submit', function(e) {
                            if (signaturePad.isEmpty()) {
                                e.preventDefault();
                                alert('Por favor, firme antes de enviar el formulario.');
                                return false;
                            }
                            
                            document.getElementById('firma').value = signaturePad.toDataURL();
                            return true;
                        });
                    });
                </script>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">¡Asistencia Registrada!</h3>
                    <p class="text-gray-600 mb-6">Tu asistencia ha sido registrada correctamente.</p>
                    <a href="index.php?page=asistencia&sesion_id=<?= $sesion['id'] ?>" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Registrar otra asistencia
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="p-6 text-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-5xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Sesión no disponible</h3>
            <p class="text-gray-600 mb-6">La sesión solicitada no existe o no está activa.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../app/views/layouts/footer.php'; ?>