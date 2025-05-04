<?php include '../app/views/layouts/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Exportar Asistencia</h1>
        <div>
            <button id="btnImprimir" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-print mr-1"></i> Imprimir
            </button>
            <a href="index.php?page=exportar&sesion_id=<?= intval($sesion['id']) ?>&format=pdf&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
            </a>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <div id="formato-asistencia" class="bg-white p-4 border border-gray-300 rounded-lg">
        <!-- Formato oficial Universidad del Tolima -->
        <div class="w-full" style="page-break-after: always;">
            <table class="w-full border-collapse border border-black" style="table-layout: fixed;">
                <!-- Fila 1: Logo, Título, Página -->
                <tr>
                    <td class="border border-black p-2 text-center" style="width: 20%;">
                        <img src="../../public/assets/img/logo.png" alt="Logo Universidad del Tolima" class="h-16 mx-auto">
                        <div class="text-xs mt-1">Universidad del Tolima</div>
                    </td>
                    <td class="border border-black p-2 text-center font-bold" style="width: 60%;">
                        <div class="text-lg">PLANIFICACIÓN, DESARROLLO Y VERIFICACIÓN DE LA LABOR ACADÉMICA</div>
                        <div class="text-base mt-2">CONTROL ASISTENCIA ESTUDIANTES</div>
                    </td>
                    <td class="border border-black p-2" style="width: 20%;">
                        <div class="text-xs">Página 1 de 1</div>
                        <div class="text-xs mt-2">Código: FO-P06-F08</div>
                        <div class="text-xs mt-2">Versión: 05</div>
                        <div class="text-xs mt-1">Fecha Aprobación: 27-01-2017</div>
                    </td>
                </tr>
            </table>
            
            <!-- Fila 2: Área, Programa, Código -->
            <table class="w-full border-collapse border border-black mt-0" style="table-layout: fixed;">
                <tr>
                    <td class="border border-black p-1" style="width: 33%;">
                        <span class="font-bold text-xs">ÁREA: </span>
                        <span class="text-xs"><?= htmlspecialchars($sesion['area'] ?? '') ?></span>
                    </td>
                    <td class="border border-black p-1" style="width: 33%;">
                        <span class="font-bold text-xs">PROGRAMA: </span>
                        <span class="text-xs"><?= htmlspecialchars($sesion['programa'] ?? '') ?></span>
                    </td>
                    <td class="border border-black p-1" style="width: 34%;">
                        <span class="font-bold text-xs">CÓDIGO: </span>
                        <span class="text-xs"><?= htmlspecialchars($sesion['codigo'] ?? '') ?></span>
                    </td>
                </tr>
            </table>
            
            <!-- Fila 3: Semestre, Grupo, Aula, Sede, Fecha -->
            <table class="w-full border-collapse border border-black mt-0" style="table-layout: fixed;">
                <tr>
                    <td class="border border-black p-1" style="width: 20%;">
                        <span class="font-bold text-xs">SEMESTRE: </span>
                        <span class="text-xs"><?= htmlspecialchars($sesion['semestre'] ?? '') ?></span>
                    </td>
                    <td class="border border-black p-1" style="width: 20%;">
                        <span class="font-bold text-xs">GRUPO: </span>
                        <span class="text-xs"><?= htmlspecialchars($sesion['grupo'] ?? '') ?></span>
                    </td>
                    <td class="border border-black p-1" style="width: 20%;">
                        <span class="font-bold text-xs">AULA No. </span>
                        <span class="text-xs"><?= $sesion['aula'] ?? '' ?></span>
                    </td>
                    <td class="border border-black p-1" style="width: 20%;">
                        <span class="font-bold text-xs">SEDE: </span>
                        <span class="text-xs"><?= $sesion['sede'] ?? '' ?></span>
                    </td>
                    <td class="border border-black p-1" style="width: 20%;">
                        <span class="font-bold text-xs">FECHA: </span>
                        <span class="text-xs"><?= isset($sesion['fecha']) ? date('d/m/Y', strtotime($sesion['fecha'])) : '' ?></span>
                    </td>
                </tr>
            </table>
            
            <!-- Lista de asistencia -->
            <table class="w-full border-collapse border border-black mt-0" style="table-layout: fixed;">
                <thead>
                    <tr>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 20%;">NOMBRE ESTUDIANTE</th>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 12%;">DOCUMENTO IDENTIFICACIÓN</th>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 8%;">CÓDIGO</th>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 10%;">TELÉFONO</th>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 20%;">DIRECCIÓN</th>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 20%;">CORREO ELECTRÓNICO</th>
                        <th class="border border-black p-1 text-xs font-bold text-center" style="width: 10%;">FIRMA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($asistencias)): ?>
                        <?php for ($i = 0; $i < 30; $i++): ?>
                            <tr>
                                <td class="border border-black p-1" style="height: 20px;"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                            </tr>
                        <?php endfor; ?>
                    <?php else: ?>
                        <?php foreach ($asistencias as $asistencia): ?>
                            <tr>
                                <td class="border border-black p-1 text-xs"><?= htmlspecialchars($asistencia['nombre'] ?? '') ?></td>
                                <td class="border border-black p-1 text-xs"><?= htmlspecialchars($asistencia['documento'] ?? '') ?></td>
                                <td class="border border-black p-1 text-xs"><?= htmlspecialchars($asistencia['codigo'] ?? '') ?></td>
                                <td class="border border-black p-1 text-xs"><?= htmlspecialchars($asistencia['telefono'] ?? '') ?></td>
                                <td class="border border-black p-1 text-xs"><?= htmlspecialchars($asistencia['direccion'] ?? '') ?></td>
                                <td class="border border-black p-1 text-xs"><?= htmlspecialchars($asistencia['correo'] ?? '') ?></td>
                                <td class="border border-black p-1 text-center">
                                    <?php if (!empty($asistencia['firma'])): ?>
                                        <img src="<?= $asistencia['firma'] ?>" alt="Firma" style="max-height: 20px; max-width: 100%; display: inline-block;">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php 
                        // Completar con filas vacías hasta llegar a 30
                        $filasRestantes = 30 - count($asistencias);
                        for ($i = 0; $i < $filasRestantes; $i++): 
                        ?>
                            <tr>
                                <td class="border border-black p-1" style="height: 20px;"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                                <td class="border border-black p-1"></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para imprimir
        document.getElementById('btnImprimir').addEventListener('click', function() {
            window.print();
        });
    });
</script>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #formato-asistencia, #formato-asistencia * {
            visibility: visible;
        }
        #formato-asistencia {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        .no-print {
            display: none;
        }
        
        @page {
            size: landscape;
            margin: 0.5cm;
        }
    }
</style>

<?php include '../app/views/layouts/footer.php'; ?>