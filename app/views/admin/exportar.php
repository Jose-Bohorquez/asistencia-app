<?php 
$isPrintMode = isset($_GET['print']) && $_GET['print'] == '1';
if (!$isPrintMode) {
    include '../app/views/layouts/header.php'; 
}
?>

<?php if ($isPrintMode): ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Exportar Asistencia - PDF</title>
    <style>
        @media print {
            body { margin: 0; padding: 10px; font-family: Arial, sans-serif; }
            .no-print { display: none !important; }
            table { page-break-inside: avoid; }
            .page-break { page-break-before: always; }
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000; padding: 4px; text-align: center; font-size: 10px; }
        .logo-cell { width: 20%; }
        .title-cell { width: 60%; }
        .info-cell { width: 20%; }
        img { max-height: 60px; }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
<?php else: ?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-2xl font-bold text-gray-800">Exportar Asistencia</h1>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <div>
                <button id="btnImprimir" class="bg-blue-800 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
                <a href="index.php?page=exportar&sesion_id=<?= intval($sesion['id']) ?>&format=pdf&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                </a>
                <!-- Aquí puedes agregar el botón para exportar a Excel -->
                <a href="index.php?page=exportar&sesion_id=<?= intval($sesion['id']) ?>&format=excel&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <div id="formato-asistencia" class="bg-white p-4 border border-gray-300 rounded-lg">
        <div class="overflow-x-auto">
            <!-- Formato oficial Universidad del Tolima -->
            <div class="w-full" style="page-break-after: always;">
                <table class="w-full border-collapse border border-black min-w-[900px]" style="table-layout: fixed;">
                    <!-- Fila 1: Logo, Título, Página -->
                    <tr>
                        <td class="border border-black p-2 text-center" style="width: 20%;">
                            <img src="assets/img/logo.png" alt="Logo Universidad del Tolima" class="h-16 mx-auto">
                            <div class="text-xs mt-1">Universidad del Tolima</div>
                        </td>
                        <td class="border border-black p-2 text-center font-bold" style="width: 60%; vertical-align: top; padding: 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border-bottom: 1.5px solid #000; font-size: 17px; font-weight: bold; text-align: center; padding: 8px 0 4px 0;">
                                        PLANIFICACIÓN, DESARROLLO Y VERIFICACIÓN DE LA LABOR ACADÉMICA
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 15px; font-weight: bold; text-align: center; padding: 8px 0 8px 0;">
                                        CONTROL ASISTENCIA ESTUDIANTES
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="border border-black p-2" style="width: 20%; vertical-align: top; padding: 0;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <tr>
                                    <td style="border-bottom: 1px solid #000; padding: 2px 6px; font-weight: normal; text-align: center;">Página 1 de 1</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #000; padding: 2px 6px; font-weight: bold; text-align: center;">Código: FO-P06-F08</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #000; padding: 2px 6px; font-weight: bold; text-align: center;">Versión: 05</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 6px; font-weight: bold; text-align: center;">
                                        Fecha Aprobación:<br>
                                        <span style="display: block; text-align: center; font-weight: bold;">27-01-2017</span>
                                    </td>
                                </tr>
                            </table>
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
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 18%;">NOMBRE ESTUDIANTE</th>
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 10%;">DOCUMENTO IDENTIFICACIÓN</th>
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 10%;">CÓDIGO</th>
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 12%;">TELÉFONO</th>
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 18%;">DIRECCIÓN</th>
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 18%;">CORREO ELECTRÓNICO</th>
                            <th class="border border-black p-2 text-xs font-bold text-center" style="width: 14%;">FIRMA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asistencias)): ?>
                            <?php for ($i = 0; $i < 30; $i++): ?>
                                <tr>
                                    <td class="border border-black p-2" style="height: 20px;"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                </tr>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php foreach ($asistencias as $asistencia): ?>
                                <tr>
                                    <td class="border border-black p-2 text-xs"><?= htmlspecialchars($asistencia['nombre'] ?? '') ?></td>
                                    <td class="border border-black p-2 text-xs"><?= htmlspecialchars($asistencia['documento'] ?? '') ?></td>
                                    <td class="border border-black p-2 text-xs"><?= htmlspecialchars($asistencia['codigo'] ?? '') ?></td>
                                    <td class="border border-black p-2 text-xs"><?= htmlspecialchars($asistencia['telefono'] ?? '') ?></td>
                                    <td class="border border-black p-2 text-xs"><?= htmlspecialchars($asistencia['direccion'] ?? '') ?></td>
                                    <td class="border border-black p-2 text-xs"><?= htmlspecialchars($asistencia['correo'] ?? '') ?></td>
                                    <td class="border border-black p-2 text-center">
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
                                    <td class="border border-black p-2" style="height: 20px;"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                    <td class="border border-black p-2"></td>
                                </tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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

<?php if ($isPrintMode): ?>
</body>
</html>
<?php else: ?>
<?php include '../app/views/layouts/footer.php'; ?>
<?php endif; ?>