<?php

class ExportHelper {
    
    /**
     * Exportar datos a Excel usando PhpSpreadsheet
     */
    public static function exportToExcel($datos, $nombreArchivo, $titulo = '', $encabezados = []) {
        if (empty($datos)) {
            throw new Exception('No hay datos para exportar');
        }
        
        // Verificar si PhpSpreadsheet está disponible
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback a CSV si no está disponible
            return self::exportToCSV($datos, $nombreArchivo, $encabezados);
        }
        
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar título si se proporciona
        $startRow = 1;
        if (!empty($titulo)) {
            $sheet->setCellValue('A1', $titulo);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->mergeCells('A1:' . chr(65 + count($encabezados) - 1) . '1');
            $startRow = 3;
        }
        
        // Usar encabezados personalizados o las claves del primer elemento
        $headers = !empty($encabezados) ? $encabezados : array_keys($datos[0]);
        
        // Escribir encabezados
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $startRow, $header);
            $sheet->getStyle($col . $startRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $startRow)->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }
        
        // Escribir datos
        $row = $startRow + 1;
        foreach ($datos as $dato) {
            $col = 'A';
            foreach ($dato as $value) {
                // Limpiar HTML tags si existen
                $cleanValue = strip_tags($value);
                $sheet->setCellValue($col . $row, $cleanValue);
                $col++;
            }
            $row++;
        }
        
        // Autoajustar columnas
        foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Configurar headers para descarga
        $filename = $nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Exportar datos a PDF usando TCPDF
     */
    public static function exportToPDF($datos, $nombreArchivo, $titulo = '', $encabezados = []) {
        if (empty($datos)) {
            throw new Exception('No hay datos para exportar');
        }
        
        // Verificar si TCPDF está disponible
        if (!class_exists('TCPDF')) {
            throw new Exception('TCPDF no está instalado. Por favor instale la librería TCPDF.');
        }
        
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        // Crear nuevo PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configurar información del documento
        $pdf->SetCreator('Sistema de Asistencia');
        $pdf->SetAuthor('Sistema de Asistencia');
        $pdf->SetTitle($titulo ?: 'Reporte');
        
        // Configurar márgenes
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Agregar página
        $pdf->AddPage();
        
        // Título
        if (!empty($titulo)) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
            $pdf->Ln(5);
        }
        
        // Usar encabezados personalizados o las claves del primer elemento
        $headers = !empty($encabezados) ? $encabezados : array_keys($datos[0]);
        
        // Crear tabla HTML
        $html = '<table border="1" cellpadding="4" cellspacing="0">';
        
        // Encabezados
        $html .= '<thead><tr style="background-color: #f0f0f0; font-weight: bold;">';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Datos
        $html .= '<tbody>';
        foreach ($datos as $dato) {
            $html .= '<tr>';
            foreach ($dato as $value) {
                // Limpiar HTML tags y caracteres especiales
                $cleanValue = strip_tags($value);
                $html .= '<td>' . htmlspecialchars($cleanValue) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        // Escribir HTML
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Configurar headers para descarga
        $filename = $nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $pdf->Output($filename, 'D');
        exit;
    }
    
    /**
     * Exportar datos a CSV (fallback)
     */
    public static function exportToCSV($datos, $nombreArchivo, $encabezados = []) {
        if (empty($datos)) {
            throw new Exception('No hay datos para exportar');
        }
        
        $filename = $nombreArchivo . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Usar encabezados personalizados o las claves del primer elemento
        $headers = !empty($encabezados) ? $encabezados : array_keys($datos[0]);
        
        // Escribir encabezados
        fputcsv($output, $headers, ';');
        
        // Escribir datos
        foreach ($datos as $dato) {
            $row = [];
            foreach ($dato as $value) {
                // Limpiar HTML tags
                $row[] = strip_tags($value);
            }
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Preparar datos de asistencia para exportación
     */
    public static function prepareAsistenciaData($asistencias) {
        $datos = [];
        $encabezados = [
            'Sesión',
            'Curso',
            'Programa',
            'Fecha',
            'Hora Inicio',
            'Estudiante',
            'Email',
            'Estado',
            'Hora Llegada',
            'Observaciones'
        ];
        
        foreach ($asistencias as $asistencia) {
            $datos[] = [
                $asistencia['sesion_id'] ?? '',
                $asistencia['curso_nombre'] ?? '',
                $asistencia['programa_nombre'] ?? '',
                $asistencia['fecha'] ?? '',
                $asistencia['hora_inicio'] ?? '',
                $asistencia['estudiante_nombre'] ?? '',
                $asistencia['estudiante_email'] ?? '',
                $asistencia['estado'] ?? '',
                $asistencia['hora_llegada'] ?? '',
                $asistencia['observaciones'] ?? ''
            ];
        }
        
        return ['datos' => $datos, 'encabezados' => $encabezados];
    }
    
    /**
     * Preparar datos de sesiones para exportación
     */
    public static function prepareSesionesData($sesiones) {
        $datos = [];
        $encabezados = [
            'ID',
            'Curso',
            'Programa',
            'Fecha',
            'Hora Inicio',
            'Estado',
            'Total Estudiantes',
            'Presentes',
            'Ausentes',
            'Tardanzas',
            'Creado'
        ];
        
        foreach ($sesiones as $sesion) {
            $datos[] = [
                $sesion['id'] ?? '',
                $sesion['curso_nombre'] ?? '',
                $sesion['programa_nombre'] ?? '',
                $sesion['fecha'] ?? '',
                $sesion['hora_inicio'] ?? '',
                $sesion['estado'] ?? '',
                $sesion['total_estudiantes'] ?? '0',
                $sesion['presentes'] ?? '0',
                $sesion['ausentes'] ?? '0',
                $sesion['tardanzas'] ?? '0',
                $sesion['created_at'] ?? ''
            ];
        }
        
        return ['datos' => $datos, 'encabezados' => $encabezados];
    }
    
    /**
     * Preparar datos de usuarios para exportación
     */
    public static function prepareUsuariosData($usuarios) {
        $datos = [];
        $encabezados = [
            'ID',
            'Usuario',
            'Nombre',
            'Email',
            'Rol',
            'Estado',
            'Fecha Creación',
            'Último Acceso'
        ];
        
        foreach ($usuarios as $usuario) {
            $datos[] = [
                $usuario['id'] ?? '',
                $usuario['username'] ?? '',
                $usuario['nombre'] ?? '',
                $usuario['email'] ?? '',
                $usuario['rol'] ?? '',
                $usuario['activo'] ? 'Activo' : 'Inactivo',
                $usuario['created_at'] ?? '',
                $usuario['last_login'] ?? 'Nunca'
            ];
        }
        
        return ['datos' => $datos, 'encabezados' => $encabezados];
    }
    
    /**
     * Preparar datos de cursos para exportación
     */
    public static function prepareCursosData($cursos) {
        $datos = [];
        $encabezados = [
            'ID',
            'Nombre',
            'Programa',
            'Sede',
            'Estado',
            'Total Sesiones',
            'Sesiones Activas',
            'Total Estudiantes',
            'Fecha Creación'
        ];
        
        foreach ($cursos as $curso) {
            $datos[] = [
                $curso['id'] ?? '',
                $curso['nombre'] ?? '',
                $curso['programa_nombre'] ?? '',
                $curso['sede_nombre'] ?? '',
                $curso['activo'] ? 'Activo' : 'Inactivo',
                $curso['total_sesiones'] ?? '0',
                $curso['sesiones_activas'] ?? '0',
                $curso['total_estudiantes'] ?? '0',
                $curso['created_at'] ?? ''
            ];
        }
        
        return ['datos' => $datos, 'encabezados' => $encabezados];
    }
    
    /**
     * Preparar datos de programas para exportación
     */
    public static function prepareProgramasData($programas) {
        $datos = [];
        $encabezados = [
            'ID',
            'Nombre',
            'Descripción',
            'Estado',
            'Total Cursos',
            'Cursos Activos',
            'Total Estudiantes',
            'Fecha Creación'
        ];
        
        foreach ($programas as $programa) {
            $datos[] = [
                $programa['id'] ?? '',
                $programa['nombre'] ?? '',
                $programa['descripcion'] ?? '',
                $programa['activo'] ? 'Activo' : 'Inactivo',
                $programa['total_cursos'] ?? '0',
                $programa['cursos_activos'] ?? '0',
                $programa['total_estudiantes'] ?? '0',
                $programa['created_at'] ?? ''
            ];
        }
        
        return ['datos' => $datos, 'encabezados' => $encabezados];
    }
    
    /**
     * Preparar datos de estudiantes para exportación
     */
    public static function prepareEstudiantesData($estudiantes) {
        $datos = [];
        $encabezados = [
            'ID',
            'Nombre',
            'Documento',
            'Código',
            'Teléfono',
            'Dirección',
            'Correo',
            'Curso',
            'Estado',
            'Fecha Inscripción'
        ];
        
        foreach ($estudiantes as $estudiante) {
            $datos[] = [
                $estudiante['id'] ?? '',
                $estudiante['nombre'] ?? '',
                $estudiante['documento'] ?? '',
                $estudiante['codigo'] ?? '',
                $estudiante['telefono'] ?? '',
                $estudiante['direccion'] ?? '',
                $estudiante['correo'] ?? '',
                $estudiante['curso_nombre'] ?? '',
                $estudiante['activo'] ? 'Activo' : 'Inactivo',
                $estudiante['created_at'] ?? ''
            ];
        }
        
        return ['datos' => $datos, 'encabezados' => $encabezados];
    }
}
?>