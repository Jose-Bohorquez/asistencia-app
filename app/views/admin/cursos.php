<?php
// Incluir componentes necesarios
require_once '../app/views/components/form.php';
require_once '../app/views/components/button.php';
require_once '../app/views/components/alert.php';
require_once '../app/views/components/card.php';
require_once '../app/views/components/table.php';
require_once '../app/views/components/modal.php';

// Configuración del layout
$pageTitle = 'Gestión de Cursos - Sistema de Asistencia';
$bodyClass = 'bg-gray-50';

// Iniciar captura de contenido
ob_start();

// Procesar mensajes
$mensaje_exito = null;
if (isset($_SESSION['curso_success'])) {
    $mensaje_exito = $_SESSION['curso_success'];
    unset($_SESSION['curso_success']);
}
?>

<?php
echo renderCard([
    'content' => function() use ($error, $success, $mensaje_exito, $programas, $profesores, $cursos) {
        ?>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-3 sm:gap-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Gestión de Cursos</h1>
            <?php
            echo renderButton('Nuevo Curso', [
                'id' => 'btnNuevoCurso',
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
        
        <!-- Formulario para crear/editar curso (oculto por defecto) -->
        <div id="formCurso" class="bg-gray-100 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 hidden">
            <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">Nuevo Curso</h2>
            <form method="POST" action="index.php?page=cursos">
                <input type="hidden" id="id" name="id" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mb-4">
                    <?php
                    // Campo código
                    echo renderInput([
                        'name' => 'codigo',
                        'label' => 'Código *',
                        'type' => 'number',
                        'required' => true,
                        'attributes' => 'min="1" step="1"'
                    ]);
                    
                    // Campo nombre
                    echo renderInput([
                        'name' => 'nombre',
                        'label' => 'Nombre *',
                        'type' => 'text',
                        'required' => true
                    ]);
                    
                    // Campo programa
                    $programaOptions = ['' => 'Seleccionar programa'];
                    foreach ($programas as $programa) {
                        $programaOptions[$programa['id']] = htmlspecialchars($programa['nombre']);
                    }
                    echo renderSelect([
                        'name' => 'programa_id',
                        'label' => 'Programa *',
                        'options' => $programaOptions,
                        'required' => true
                    ]);
                    
                    // Campo profesor (solo para admin)
                    if (in_array($_SESSION['user_rol'], ['super_admin', 'admin'])) {
                        $profesorOptions = ['' => 'Seleccionar profesor'];
                        foreach ($profesores as $profesor) {
                            $profesorOptions[$profesor['id']] = htmlspecialchars($profesor['nombre'] . ' (' . $profesor['username'] . ')');
                        }
                        echo renderSelect([
                            'name' => 'profesor_id',
                            'label' => 'Profesor',
                            'options' => $profesorOptions
                        ]);
                    }
                    
                    // Campo área
                    echo renderInput([
                        'name' => 'area',
                        'label' => 'Área',
                        'type' => 'text'
                    ]);
                    
                    // Campo semestre
                    echo renderInput([
                        'name' => 'semestre',
                        'label' => 'Semestre',
                        'type' => 'number',
                        'attributes' => 'min="1" max="12" step="1"'
                    ]);
                    
                    // Campo grupo
                    echo renderInput([
                        'name' => 'grupo',
                        'label' => 'Grupo',
                        'type' => 'number',
                        'attributes' => 'min="1" step="1"'
                    ]);
                    
                    // Campo aula
                    echo renderInput([
                        'name' => 'aula',
                        'label' => 'Aula',
                        'type' => 'number',
                        'attributes' => 'min="1" step="1"'
                    ]);
                    
                    // Campo sede
                    echo renderSelect([
                        'name' => 'sede',
                        'label' => 'Sede',
                        'options' => [
                            'KENNEDY' => 'KENNEDY',
                            'OTRA' => 'OTRA'
                        ],
                        'required' => true
                    ]);
                    ?>
                    
                    <input type="text" id="otraSede" name="otraSede" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 mt-2 hidden" placeholder="Especifique otra sede">
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
        <?php
    },
    'class' => 'bg-white rounded-lg shadow-md',
    'padding' => 'p-4 sm:p-6 mb-4 sm:mb-6'
]);
?>
    
        <!-- Lista de cursos -->
        <?php
        if (empty($cursos)) {
            echo renderCard([
                'content' => '<div class="text-center py-8"><p class="text-gray-500 text-lg">No hay cursos registrados</p></div>',
                'class' => 'bg-white rounded-lg shadow-md',
                'padding' => 'p-4 sm:p-6'
            ]);
        } else {
            // Preparar datos para la tabla
            $headers = ['Código', 'Nombre', 'Programa', 'Profesor', 'Área', 'Sem.', 'Grupo', 'Aula', 'Sede', 'Acciones'];
            
            $tableData = [];
            foreach ($cursos as $curso) {
                $editButton = renderButton('Editar', [
                    'style' => 'warning',
                    'size' => 'sm',
                    'icon' => 'fas fa-edit',
                    'class' => 'btnEditar mr-2',
                    'attributes' => sprintf(
                        'data-id="%s" data-codigo="%s" data-nombre="%s" data-programa-id="%s" data-profesor-id="%s" data-area="%s" data-semestre="%s" data-grupo="%s" data-aula="%s" data-sede="%s"',
                        $curso['id'],
                        $curso['codigo'],
                        htmlspecialchars($curso['nombre']),
                        $curso['programa_id'],
                        $curso['profesor_id'],
                        htmlspecialchars($curso['area']),
                        $curso['semestre'],
                        $curso['grupo'],
                        $curso['aula'],
                        htmlspecialchars($curso['sede'])
                    )
                ]);
                
                $deleteButton = renderButton('Eliminar', [
                    'style' => 'danger',
                    'size' => 'sm',
                    'icon' => 'fas fa-trash',
                    'class' => 'btnEliminar',
                    'attributes' => sprintf(
                        'data-id="%s" data-nombre="%s"',
                        $curso['id'],
                        htmlspecialchars($curso['nombre'])
                    )
                ]);
                
                $tableData[] = [
                    'codigo' => $curso['codigo'],
                    'nombre' => htmlspecialchars($curso['nombre']),
                    'programa_nombre' => htmlspecialchars($curso['programa_nombre']),
                    'profesor_nombre' => htmlspecialchars($curso['profesor_nombre'] ?? 'No asignado'),
                    'area' => htmlspecialchars($curso['area'] ?? 'N/A'),
                    'semestre' => $curso['semestre'] ?? 'N/A',
                    'grupo' => $curso['grupo'] ?? 'N/A',
                    'aula' => $curso['aula'] ?? 'N/A',
                    'sede' => htmlspecialchars($curso['sede']),
                    'acciones' => $editButton . $deleteButton
                ];
            }
            
            echo renderTable([
                'headers' => $headers,
                'data' => $tableData,
                'searchable' => true,
                'sortable' => true,
                'responsive' => true,
                'mobileCards' => true,
                'cardFields' => [
                    'title' => 'nombre',
                    'subtitle' => 'codigo',
                    'fields' => [
                        'Programa' => 'programa_nombre',
                        'Profesor' => 'profesor_nombre',
                        'Área' => 'area',
                        'Semestre' => 'semestre',
                        'Grupo' => 'grupo',
                        'Aula' => 'aula',
                        'Sede' => 'sede'
                    ],
                    'actions' => 'acciones'
                ],
                'class' => 'bg-white rounded-lg shadow-md',
                'title' => 'Lista de Cursos'
            ]);
        }
        ?>
<?php
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
    
    .text-sm {
        font-size: 0.75rem;
    }
    
    .space-y-3 > * + * {
        margin-top: 0.5rem;
    }
}
</style>
';

// JavaScript personalizado
$customJS = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const formCurso = document.getElementById("formCurso");
        const btnNuevoCurso = document.getElementById("btnNuevoCurso");
        const btnCancelar = document.getElementById("btnCancelar");
        const formTitle = document.getElementById("formTitle");
        const idInput = document.getElementById("id");
        const codigoInput = document.getElementById("codigo");
        const nombreInput = document.getElementById("nombre");
        const programaInput = document.getElementById("programa_id");
        const profesorInput = document.getElementById("profesor_id");
        const areaInput = document.getElementById("area");
        const semestreInput = document.getElementById("semestre");
        const grupoInput = document.getElementById("grupo");
        const aulaInput = document.getElementById("aula");
        const sedeInput = document.getElementById("sede");
        const sedeSelect = document.getElementById("sede");
        const otraSedeInput = document.getElementById("otraSede");
        
        sedeSelect.addEventListener("change", function() {
            if (this.value === "OTRA") {
                otraSedeInput.classList.remove("hidden");
                otraSedeInput.required = true;
            } else {
                otraSedeInput.classList.add("hidden");
                otraSedeInput.required = false;
                otraSedeInput.value = "";
            }
        });
        
        // Mostrar formulario para nuevo curso
        btnNuevoCurso.addEventListener("click", function() {
            formTitle.textContent = "Nuevo Curso";
            idInput.value = "";
            codigoInput.value = "";
            nombreInput.value = "";
            programaInput.value = "";
            if (profesorInput) profesorInput.value = "";
            areaInput.value = "";
            semestreInput.value = "";
            grupoInput.value = "";
            aulaInput.value = "";
            sedeInput.value = "";
            formCurso.classList.remove("hidden");
            formCurso.scrollIntoView({ behavior: "smooth" });
        });
        
        // Ocultar formulario
        btnCancelar.addEventListener("click", function() {
            formCurso.classList.add("hidden");
        });
        
        // Editar curso
        document.querySelectorAll(".btnEditar").forEach(function(btn) {
            btn.addEventListener("click", function() {
                formTitle.textContent = "Editar Curso";
                idInput.value = this.dataset.id;
                codigoInput.value = this.dataset.codigo;
                nombreInput.value = this.dataset.nombre;
                programaInput.value = this.dataset.programaId;
                if (profesorInput) profesorInput.value = this.dataset.profesorId;
                areaInput.value = this.dataset.area;
                semestreInput.value = this.dataset.semestre;
                grupoInput.value = this.dataset.grupo;
                aulaInput.value = this.dataset.aula;
                sedeInput.value = this.dataset.sede;
                formCurso.classList.remove("hidden");
                formCurso.scrollIntoView({ behavior: "smooth" });
            });
        });
        
        // Eliminar curso
        document.querySelectorAll(".btnEliminar").forEach(function(btn) {
            btn.addEventListener("click", function() {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;
                
                Swal.fire({
                    title: "¿Está seguro?",
                    text: `¿Desea eliminar el curso "${nombre}"?`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `index.php?page=cursos&delete=${id}`;
                    }
                });
            });
        });
        
        // Mostrar SweetAlert si hay mensaje de éxito
        ' . (isset($mensaje_exito) ? '
        Swal.fire({
            title: "¡Éxito!",
            text: "' . $mensaje_exito . '",
            icon: "success",
            confirmButtonText: "Aceptar",
            confirmButtonColor: "#1e40af"
        });' : '') . '
    });
</script>
';

$content = ob_get_clean();
include '../app/views/layouts/main.php';
?>